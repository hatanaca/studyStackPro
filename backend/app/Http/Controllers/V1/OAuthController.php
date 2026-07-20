<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Modules\Auth\Services\SocialAuthService;
use App\Modules\Auth\Services\TokenService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private SocialAuthService $socialAuthService,
        private TokenService $tokenService
    ) {}

    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'discord', 'linkedin'], true)) {
            abort(400, 'Provider inválido.');
        }
        $driver = Socialite::driver($provider)->stateless();
        if ($provider === 'google') {
            // Sem scopes extras — login OAuth básico (openid, profile, email).
            // O YouTube usa API key separada (YOUTUBE_API_KEY), não o token OAuth.
            $driver->with(['access_type' => 'offline', 'prompt' => 'consent']);
        }
        // Gera estado CSRF autocontido (criptografado) — não depende de sessão,
        // sobrevive a cross-port (Vite:5173 → Nginx:8080).
        $nonce = bin2hex(random_bytes(16));
        Redis::set("oauth:nonce:{$nonce}", '1', 'EX', 600);
        $csrfToken = Crypt::encryptString(json_encode(['ts' => time(), 'nonce' => $nonce]));
        $response = $driver->redirect();
        $url = $response->getTargetUrl();
        $url .= (str_contains($url, '?') ? '&' : '?').'state='.urlencode($csrfToken);

        return redirect($url);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'discord', 'linkedin'], true)) {
            Log::warning('OAuth callback: provider inválido', ['provider' => $provider]);

            return redirect(config('services.frontend_url').'/login?error=oauth_failed');
        }
        // Valida o estado CSRF assinado (autocontido, sem sessão)
        $state = $request->input('state');
        if ($state === null) {
            Log::warning('OAuth callback: state ausente', ['provider' => $provider]);

            return redirect(config('services.frontend_url').'/login?error=oauth_failed');
        }
        try {
            $payload = json_decode(Crypt::decryptString($state), true);
            if (! isset($payload['ts']) || (time() - $payload['ts']) > 600) {
                Log::warning('OAuth callback: state expirado', ['provider' => $provider]);

                return redirect(config('services.frontend_url').'/login?error=oauth_failed');
            }
            // Replay protection: nonce must be unused
            if (isset($payload['nonce'])) {
                $nonceKey = 'oauth:nonce:'.$payload['nonce'];
                if (! Redis::del($nonceKey)) {
                    Log::warning('OAuth callback: nonce already used', ['provider' => $provider]);

                    return redirect(config('services.frontend_url').'/login?error=oauth_failed');
                }
            }
        } catch (\Exception $e) {
            Log::error('OAuth callback: state inválido', ['provider' => $provider, 'error' => $e->getMessage()]);

            return redirect(config('services.frontend_url').'/login?error=oauth_failed');
        }
        $frontendUrl = config('services.frontend_url');
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception $e) {
            Log::error('OAuth callback: Socialite user() failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'class' => get_class($e),
            ]);

            return redirect($frontendUrl.'/login?error=oauth_failed');
        }

        try {
            $user = $this->socialAuthService->handleOAuthUser($socialUser, $provider);
        } catch (\Throwable $e) {
            Log::error('OAuth handleOAuthUser failed', ['provider' => $provider, 'exception' => $e]);

            return redirect($frontendUrl.'/login?error=oauth_failed');
        }
        // Token assinado para criar sessão no port do frontend (5173)
        $tokenNonce = bin2hex(random_bytes(16));
        Redis::set("oauth:token:{$tokenNonce}", '1', 'EX', 600);
        $token = Crypt::encryptString(json_encode([
            'user_id' => $user->id,
            'ts' => time(),
            'token_nonce' => $tokenNonce,
        ]));

        return redirect($frontendUrl.'/auth/callback?status=ok&token='.urlencode($token));
    }

    /**
     * Troca o token OAuth assinado por uma sessão web no port do frontend.
     */
    public function oauthComplete(Request $request): JsonResponse
    {
        $token = $request->input('token');
        if (! is_string($token) || $token === '') {
            return $this->error('Token ausente.', 'VALIDATION_ERROR', null, 422);
        }
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
            if (! $payload || ! isset($payload['user_id'], $payload['ts'])) {
                return $this->error('Token inválido.', 'UNAUTHENTICATED', null, 401);
            }
            if (time() - $payload['ts'] > 600) {
                return $this->error('Token expirado.', 'UNAUTHENTICATED', null, 401);
            }
            if (isset($payload['token_nonce'])) {
                $nonceKey = 'oauth:token:'.$payload['token_nonce'];
                if (! Redis::del($nonceKey)) {
                    return $this->error('Token já utilizado.', 'UNAUTHENTICATED', null, 401);
                }
            }
        } catch (\Exception) {
            return $this->error('Token inválido.', 'UNAUTHENTICATED', null, 401);
        }
        $user = User::find($payload['user_id']);
        if (! $user) {
            return $this->error('Utilizador não encontrado.', 'NOT_FOUND', null, 404);
        }
        try {
            Auth::guard('web')->login($user);
        } catch (\Throwable $e) {
            // Sessão web indisponível (ex.: requisição API sem session cookie).
            // Não falha — o token Bearer abaixo ainda funciona perfeitamente.
            Log::warning('oauthComplete: web login skipped (no session)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
        // Gera Bearer token como fallback para autenticação via API
        $bearer = $this->tokenService->createApiToken($user, 'oauth-token');

        return $this->success([
            'user' => new UserResource($user),
            'token' => $bearer,
        ]);
    }
}
