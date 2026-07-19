#!/bin/bash
set -euo pipefail
PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_ROOT"
echo "=== StudyTrackPro - Correções de Segurança ==="

# P1 - SafeSvg.vue
cat > frontend/src/components/ui/SafeSvg.vue << 'VEOF'
<script setup lang="ts">
import DOMPurify from 'dompurify'
defineProps<{ content: string }>()
function sanitize(raw: string): string {
  return DOMPurify.sanitize(raw, { USE_PROFILES: { svg: true } })
}
</script>
<template>
  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
    stroke="currentColor" stroke-width="2" stroke-linecap="round"
    stroke-linejoin="round" aria-hidden="true" v-html="sanitize(content)" />
</template>
VEOF
echo "[OK] SafeSvg.vue"

# P1 - OAuthController
cat > backend/app/Http/Controllers/V1/OAuthController.php << 'OEOF'
<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use App\Modules\Auth\Services\SocialAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class OAuthController extends Controller
{
    public function __construct(private SocialAuthService $socialAuthService) {}

    public function redirect(string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'discord', 'linkedin'], true)) {
            abort(400, 'Provider inválido.');
        }
        $driver = Socialite::driver($provider);
        if ($provider === 'google') {
            $driver->scopes(['https://www.googleapis.com/auth/youtube.readonly']);
            $driver->with(['access_type' => 'offline', 'prompt' => 'consent']);
        }
        $state = Str::random(40);
        session(['oauth_state' => $state]);
        return $driver->stateless()->redirect(['state' => $state]);
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        if (! in_array($provider, ['google', 'discord', 'linkedin'], true)) {
            abort(400, 'Provider inválido.');
        }
        $expectedState = session('oauth_state');
        $providedState = $request->input('state');
        if ($expectedState === null || $providedState === null || $expectedState !== $providedState) {
            session()->forget('oauth_state');
            abort(403, 'Estado OAuth inválido (possível ataque CSRF).');
        }
        session()->forget('oauth_state');
        $frontendUrl = config('services.frontend_url');
        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::error('OAuth callback failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            return redirect($frontendUrl.'/login?error=oauth_failed');
        }
        $user = $this->socialAuthService->handleOAuthUser($socialUser, $provider);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        return redirect($frontendUrl.'/auth/callback?status=ok');
    }
}
OEOF
echo "[OK] OAuthController"

# P1 - AuthController
cat > backend/app/Http/Controllers/V1/AuthController.php << 'AEOF'
<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Modules\Auth\DTOs\LoginDTO;
use App\Modules\Auth\DTOs\RegisterDTO;
use App\Modules\Auth\Services\AuthService;
use App\Modules\Auth\Services\TokenService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    use HasApiResponse;
    public function __construct(
        private AuthService $authService,
        private TokenService $tokenService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = new RegisterDTO(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
            timezone: $request->validated('timezone', 'UTC')
        );
        $user = $this->authService->register($dto);
        if ($request->hasSession()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }
        $user->tokens()->where('name', 'auth-token')->delete();
        $token = $user->createToken('auth-token')->plainTextToken;
        return $this->success([
            'user' => new UserResource($user->fresh()),
            'token' => $token,
        ], 'Registrado com sucesso.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = new LoginDTO(
            email: $request->validated('email'),
            password: $request->validated('password'),
            remember: $request->boolean('remember')
        );
        $result = $this->authService->login($dto);
        if (! $result) {
            return $this->error('Credenciais inválidas.', 'UNAUTHENTICATED', null, 401);
        }
        $user = $result['user'];
        if ($request->hasSession()) {
            Auth::guard('web')->login($user, $dto->remember);
            $request->session()->regenerate();
        }
        $user->tokens()->where('name', 'auth-token')->delete();
        $token = $user->createToken('auth-token')->plainTextToken;
        return $this->success([
            'user' => new UserResource($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $currentToken = $request->user()?->currentAccessToken();
        if ($currentToken !== null) {
            $this->tokenService->revoke($currentToken);
        }
        if ($request->hasSession()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        return $this->success(null, 'Sessão terminada.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());
        return $this->success(new UserResource($user), 'Perfil atualizado.');
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        if (! $this->authService->changePassword(
            $user,
            $request->validated('current_password'),
            $request->validated('password')
        )) {
            return $this->error('Senha atual incorreta.', 'VALIDATION_ERROR', null, 422);
        }
        return $this->success(null, 'Senha alterada. Reconecte seus dispositivos.');
    }

    public function tokens(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->get(['id', 'name', 'created_at', 'last_used_at']);
        return $this->success($tokens->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'created_at' => $t->created_at?->toIso8601String(),
            'last_used_at' => $t->last_used_at?->toIso8601String(),
        ]));
    }

    public function revokeAllTokens(Request $request): JsonResponse
    {
        $count = $this->tokenService->revokeMany($request->user()->tokens()->cursor());
        return $this->success(
            ['revoked_count' => $count],
            $count === 1 ? '1 token revogado.' : "{$count} tokens revogados."
        );
    }
}
AEOF
echo "[OK] AuthController"

# P1 - SecurityHeaders
cat > backend/app/Http/Middleware/SecurityHeaders.php << 'SEOF'
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '0');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        return $response;
    }
}
SEOF
echo "[OK] SecurityHeaders"

# P1 - bootstrap/app.php
cat > backend/bootstrap/app.php << 'BEOF'
<?php
if (! defined('SIGINT')) { define('SIGINT', 2); }
if (! defined('SIGTERM')) { define('SIGTERM', 15); }
if (! defined('SIGTSTP')) { define('SIGTSTP', 20); }
use App\Http\Middleware\EnsureJsonResponse;
use App\Http\Middleware\LogApiRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetUserTimezone;
use App\Http\Middleware\SlidingWindowRateLimit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use SocialiteProviders\Manager\ServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withProviders([ServiceProvider::class])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['throttle.sliding' => SlidingWindowRateLimit::class]);
        $middleware->statefulApi();
        if (env('APP_ENV') === 'testing') {
            $middleware->validateCsrfTokens(except: ['api/*', 'sanctum/*']);
        }
        $trustedProxies = env('TRUSTED_PROXIES');
        if (is_string($trustedProxies) && trim($trustedProxies) !== '') {
            $trimmed = trim($trustedProxies);
            if ($trimmed === '*') {
                $middleware->trustProxies(at: '*');
            } else {
                $at = array_values(array_filter(array_map('trim', explode(',', $trustedProxies))));
                if ($at !== []) { $middleware->trustProxies(at: $at); }
            }
        }
        $middleware->append(SecurityHeaders::class);
        $middleware->api(prepend: [EnsureJsonResponse::class]);
        $middleware->api(append: [SetUserTimezone::class, LogApiRequests::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->expectsJson());
    })->create();
BEOF
echo "[OK] bootstrap/app.php"

# P1 - Remover bash do sandbox
sed -i "/'bash' => \\\$this->executeBash(\\\$(code)),/d" backend/app/Modules/CodeExecution/Services/DockerSandboxService.php
echo "[OK] DockerSandboxService"

# P2 - js-executor.worker
cat > frontend/src/features/code-terminal/workers/js-executor.worker.ts << 'WEOF'
const blockedGlobals = ['fetch','XMLHttpRequest','importScripts','navigator','location','history','localStorage','sessionStorage','indexedDB','openDatabase']
const MAX_EXECUTION_MS = 5_000
const MAX_CODE_LENGTH = 10_000
const logs: string[] = []
let timedOut = false

const fakeConsole = {
  log: (...args: unknown[]) => { logs.push(args.map(stringify).join(' ')) },
  error: (...args: unknown[]) => { logs.push('[ERROR] ' + args.map(stringify).join(' ')) },
  warn: (...args: unknown[]) => { logs.push('[WARN] ' + args.map(stringify).join(' ')) },
  info: (...args: unknown[]) => { logs.push('[INFO] ' + args.map(stringify).join(' ')) },
  debug: (...args: unknown[]) => { logs.push('[DEBUG] ' + args.map(stringify).join(' ')) },
}

function stringify(val: unknown): string {
  if (val === undefined) return 'undefined'
  if (val === null) return 'null'
  if (typeof val === 'string') return val
  if (typeof val === 'function') return `[Function: ${val.name || 'anonymous'}]`
  try { return JSON.stringify(val, null, 2) } catch { return String(val) }
}

function preventSandboxEscape(code: string): string {
  const patterns = [/\.constructor\.constructor\s*\(/g,/\["constructor"\]\s*\["constructor"\]\s*\(/g,/(?:__lookupGetter__|__lookupSetter__|__defineGetter__|__defineSetter__)\s*\(/g]
  let sanitized = code
  for (const pattern of patterns) { sanitized = sanitized.replace(pattern, '/*blocked*/(') }
  return sanitized
}

function createSafeTimeout(fn: Function, _ms: number): number {
  if (timedOut) return 0
  return setTimeout(() => { if (!timedOut) fn() }, _ms) as unknown as number
}
function createSafeInterval(fn: Function, _ms: number): number {
  if (timedOut) return 0
  return setInterval(() => { if (!timedOut) fn() }, _ms) as unknown as number
}

self.onmessage = function (e: MessageEvent) {
  const { code, id } = e.data
  logs.length = 0
  timedOut = false
  if (typeof code !== 'string' || code.length > MAX_CODE_LENGTH) {
    self.postMessage({ id, success: false, output: '', error: `Código excede o limite de ${MAX_CODE_LENGTH} caracteres.`, executionTime: 0 })
    return
  }
  const startTime = performance.now()
  const timeoutId = setTimeout(() => { timedOut = true }, MAX_EXECUTION_MS)
  try {
    const safeCode = preventSandboxEscape(code)
    const sandboxedFn = new Function(
      'console','Math','Date','JSON','parseInt','parseFloat','isNaN','isFinite',
      'encodeURIComponent','decodeURIComponent','encodeURI','decodeURI',
      'String','Number','Boolean','Array','Object','RegExp',
      'Error','TypeError','RangeError','SyntaxError',
      'setTimeout','clearTimeout','setInterval','clearInterval',
      `"use strict"; ${safeCode}`
    )
    const result = sandboxedFn(
      fakeConsole,Math,Date,JSON,parseInt,parseFloat,isNaN,isFinite,
      encodeURIComponent,decodeURIComponent,encodeURI,decodeURI,
      String,Number,Boolean,Array,Object,RegExp,
      Error,TypeError,RangeError,SyntaxError,
      createSafeTimeout,clearTimeout,createSafeInterval,clearInterval,
    )
    clearTimeout(timeoutId)
    if (timedOut) {
      self.postMessage({ id, success: false, output: logs.join('\n'), error: `Execução excedeu o limite de ${MAX_EXECUTION_MS / 1000}s.`, executionTime: MAX_EXECUTION_MS })
      return
    }
    const executionTime = Math.round(performance.now() - startTime)
    if (result !== undefined && logs.length === 0) { logs.push(stringify(result)) }
    self.postMessage({ id, success: true, output: logs.join('\n'), error: null, executionTime })
  } catch (err) {
    clearTimeout(timeoutId)
    const executionTime = Math.round(performance.now() - startTime)
    const errorMessage = err instanceof Error ? err.message : String(err)
    self.postMessage({ id, success: false, output: logs.join('\n'), error: errorMessage, executionTime })
  }
}
WEOF
echo "[OK] js-executor.worker"

# P2 - canvasInstance
cat > frontend/src/features/canvas/composables/canvasInstance.ts << 'CEOF'
import type { Ref } from 'vue'
let fabricCanvas: Ref<any> | null = null
export function setFabricCanvas(c: Ref<any>) {
  fabricCanvas = c
  if (import.meta.env.DEV) { ;(window as any).__fabricCanvas = c.value }
}
export function getFabricCanvas() { return fabricCanvas }
CEOF
echo "[OK] canvasInstance.ts"

# P2 - docker-compose ports
python3 -c "
import re
with open('docker-compose.yml', 'r') as f:
    content = f.read()
content = re.sub(r'    ports:\n      # Host:.*\n      - \"127\.0\.0\.1:\\\$\{POSTGRES_PUBLISH_PORT:-5432\}:5432\"\n', '', content)
content = re.sub(r'    ports:\n      - \"127\.0\.0\.1:\\\$\{REDIS_PUBLISH_PORT:-6379\}:6379\"\n', '', content)
with open('docker-compose.yml', 'w') as f:
    f.write(content)
"
echo "[OK] docker-compose.yml"

# P3 - ChangePasswordRequest
cat > backend/app/Http/Requests/Auth/ChangePasswordRequest.php << 'CPWEOF'
<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->mixedCase()->numbers()],
        ];
    }
    public function messages(): array
    {
        return [
            'current_password.required' => 'A senha atual é obrigatória.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }
}
CPWEOF
echo "[OK] ChangePasswordRequest"

# P3 - RegisterRequest
cat > backend/app/Http/Requests/Auth/RegisterRequest.php << 'RWEOF'
<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'timezone' => ['nullable', 'string', 'timezone'],
        ];
    }
}
RWEOF
echo "[OK] RegisterRequest"

# P3 - .env.example
sed -i 's|^REVERB_APP_KEY=local-key|# MUDE ESTES VALORES EM PRODUÇÃO\nREVERB_APP_KEY=CHANGE_ME_local_key|' backend/.env.example
sed -i 's|^REVERB_APP_SECRET=local-secret|REVERB_APP_SECRET=CHANGE_ME_local_secret|' backend/.env.example
echo "[OK] .env.example"

# P3 - LogApiRequests
cat > backend/app/Http/Middleware/LogApiRequests.php << 'LREQEOF'
<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('_request_start', microtime(true));
        return $next($request);
    }
    public function terminate(Request $request, Response $response): void
    {
        $start = $request->attributes->get('_request_start', microtime(true));
        // Nunca logar $request->all() — contém dados sensíveis (senhas, tokens)
        Log::channel('single')->info('API Request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $request->user()?->id,
            'status' => $response->getStatusCode(),
            'duration_ms' => round((microtime(true) - $start) * 1000, 2),
        ]);
    }
}
LREQEOF
echo "[OK] LogApiRequests"

# P3 - auth.store.ts
cat > frontend/src/stores/auth.store.ts << 'ASTOREEOF'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types/domain.types'
import { authApi } from '@/api/modules/auth.api'
import { fetchSanctumCsrfCookie } from '@/api/sanctum'
import { useSessionsStore } from '@/stores/sessions.store'

const USER_KEY = 'studytrack_user'
const CACHE_TTL_MS = 24 * 60 * 60 * 1000
interface CachedUser { user: User; ts: number }

function loadCachedUser(): User | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    if (!raw) return null
    const cached = JSON.parse(raw) as CachedUser | User
    if ('ts' in cached && 'user' in cached) {
      if (Date.now() - cached.ts > CACHE_TTL_MS) { localStorage.removeItem(USER_KEY); return null }
      return (cached as CachedUser).user
    }
    return cached as User
  } catch { return null }
}

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(loadCachedUser())
  const sessionValidated = ref(false)
  const isAuthenticated = computed(() => sessionValidated.value && !!user.value)
  function cacheUser(u: User) { localStorage.setItem(USER_KEY, JSON.stringify({ user: u, ts: Date.now() })) }

  async function login(email: string, password: string) {
    await fetchSanctumCsrfCookie()
    const { data } = await authApi.login(email, password)
    if (data.success && data.data) {
      const { user: u } = data.data; user.value = u; cacheUser(u); sessionValidated.value = true
    } else {
      throw new Error((data as unknown as { error?: { message?: string } }).error?.message ?? 'Credenciais inválidas.')
    }
  }

  async function register(name: string, email: string, password: string, passwordConfirmation: string, timezone = 'UTC') {
    await fetchSanctumCsrfCookie()
    const { data } = await authApi.register({ name, email, password, password_confirmation: passwordConfirmation, timezone })
    if (data.success && data.data) {
      const { user: u } = data.data; user.value = u; cacheUser(u); sessionValidated.value = true
    } else {
      throw new Error((data as unknown as { error?: { message?: string } }).error?.message ?? 'Falha no cadastro.')
    }
  }

  async function fetchMe() {
    try {
      const { data } = await authApi.me()
      if (data.success && data.data) { user.value = data.data; cacheUser(data.data); sessionValidated.value = true }
    } catch (e) {
      const status = (e as { response?: { status?: number } })?.response?.status
      if (status === 401) { /* interceptor */ } else if (sessionValidated.value || user.value) { registerOnlineRecovery() }
      throw e
    }
  }

  function updateUser(updated: User) { user.value = updated; cacheUser(updated) }

  function clearSessionLocally() {
    user.value = null; sessionValidated.value = false; localStorage.removeItem(USER_KEY)
    try { useSessionsStore().$reset() } catch { /* ok */ }
    if (onlineHandler) { window.removeEventListener('online', onlineHandler); onlineHandler = null }
  }

  let onlineHandler: (() => void) | null = null
  function registerOnlineRecovery() {
    if (onlineHandler) return
    onlineHandler = async () => {
      if (!sessionValidated.value) { try { await fetchMe() } catch { /* */ } }
      window.removeEventListener('online', onlineHandler!); onlineHandler = null
    }
    window.addEventListener('online', onlineHandler)
  }

  async function logout() {
    const hadSession = sessionValidated.value
    try { if (hadSession) { await authApi.logout() } } catch { /* */ }
    finally { clearSessionLocally() }
  }

  return { user, sessionValidated, isAuthenticated, login, register, fetchMe, logout, clearSessionLocally, updateUser }
})
ASTOREEOF
echo "[OK] auth.store.ts"

echo ""
echo "=== 14 correções aplicadas com sucesso! ==="
echo ""
echo "Próximos passos:"
echo "  1. cd frontend && npm install"
echo "  2. cd backend && composer dump-autoload"
echo "  3. cd frontend && npx vue-tsc --noEmit"
echo "  4. cd backend && php artisan test --filter=Security"
echo "  5. cd frontend && npx vitest run"
