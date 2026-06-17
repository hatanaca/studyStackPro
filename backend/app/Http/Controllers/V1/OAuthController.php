<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

/**
 * Controlador de autenticação OAuth (Google, Discord).
 *
 * Redireciona para o provider e processa o callback,
 * iniciando sessão web (cookie Sanctum SPA) como o AuthController::login().
 */
class OAuthController extends Controller
{
    public function __construct(private SocialAuthService $socialAuthService) {}

    /**
     * Redireciona para o provider OAuth.
     */
    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'discord'], true)) {
            abort(400, 'Provider inválido.');
        }

        $driver = Socialite::driver($provider)->stateless();

        // Escopos YouTube para acessar playlists e refresh token offline.
        // access_type=offline → recebe refresh_token.
        // prompt=consent → força re-autorização com novos escopos.
        if ($provider === 'google') {
            $driver->scopes(['https://www.googleapis.com/auth/youtube.readonly']);
            $driver->with([
                'access_type' => 'offline',
                'prompt'      => 'consent',
            ]);
        }

        return $driver->redirect();
    }

    /**
     * Processa o callback do provider, inicia sessão web e redireciona para o frontend.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'discord'], true)) {
            abort(400, 'Provider inválido.');
        }

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:5173');

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Exception) {
            return redirect($frontendUrl . '/login?error=oauth_failed');
        }

        $user = $this->socialAuthService->handleOAuthUser($socialUser, $provider);

        // Inicia sessão web (cookie HttpOnly) — mesmo padrão do AuthController::login()
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        // Redireciona para o frontend — o cookie de sessão já está definido no domínio da API
        return redirect($frontendUrl . '/auth/callback?status=ok');
    }
}
