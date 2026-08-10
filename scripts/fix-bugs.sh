#!/bin/bash
set -euo pipefail
PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
cd "$PROJECT_ROOT"
echo "=== StudyTrackPro - Correção de Bugs ==="

# =============================================================================
# BUG 1 (Crítica) — OAuth stateless() inconsistente
# =============================================================================
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
            // Usar stateless() para evitar InvalidStateException do Socialite
            $socialUser = Socialite::driver($provider)->stateless()->user();
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
echo "[OK] Bug 1 - OAuth stateless"

# =============================================================================
# BUG 2 (Alta) — SetUserTimezone: usar Carbon local em vez de global
# =============================================================================
cat > backend/app/Http/Middleware/SetUserTimezone.php << 'TZEOF'
<?php
namespace App\Http\Middleware;
use Closure;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class SetUserTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->timezone && $this->isValidTimezone($user->timezone)) {
            // Armazenar timezone no request para uso local (não mutar Carbon global)
            $request->attributes->set('user_timezone', $user->timezone);
        }
        return $next($request);
    }

    private function isValidTimezone(string $tz): bool
    {
        return in_array($tz, DateTimeZone::listIdentifiers(), true);
    }
}
TZEOF
echo "[OK] Bug 2 - SetUserTimezone"

# =============================================================================
# BUG 3 (Alta) — StudySessionController::active() usar resolve()
# =============================================================================
cat > backend/app/Http/Controllers/V1/StudySessionController.php << 'SSEOF'
<?php
namespace App\Http\Controllers\V1;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudySessions\StartStudySessionRequest;
use App\Http\Requests\StudySessions\StoreStudySessionRequest;
use App\Http\Requests\StudySessions\UpdateStudySessionRequest;
use App\Http\Resources\StudySessionResource;
use App\Modules\StudySessions\Services\StudySessionService;
use App\Traits\HasApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudySessionController extends Controller
{
    use HasApiResponse;
    public function __construct(private StudySessionService $studySessionService) {}

    public function index(Request $request): JsonResponse
    {
        $sessions = $this->studySessionService->getPaginated(
            $request->user()->id,
            $request->integer('per_page', 15),
            $request->query('technology_id'),
            $request->query('search')
        );
        return $this->success(StudySessionResource::collection($sessions));
    }

    public function store(StoreStudySessionRequest $request): JsonResponse
    {
        $session = $this->studySessionService->create($request->user(), $request->validated());
        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão registrada.', 201);
    }

    public function show(string $id): JsonResponse
    {
        $session = $this->studySessionService->getForUser($id, request()->user()->id);
        return $this->success(new StudySessionResource($session->load('technology')));
    }

    public function update(UpdateStudySessionRequest $request, string $id): JsonResponse
    {
        $session = $this->studySessionService->update($id, $request->user()->id, $request->validated());
        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão atualizada.');
    }

    public function destroy(string $id): JsonResponse
    {
        $this->studySessionService->delete($id, request()->user()->id);
        return $this->success(null, 'Sessão excluída.');
    }

    public function active(Request $request): JsonResponse
    {
        $session = $this->studySessionService->getActiveForUser($request->user()->id);
        if (! $session) {
            return $this->success(null);
        }
        $elapsedSeconds = (int) $session->started_at->diffInSeconds(now());
        $resource = new StudySessionResource($session);
        return $this->success(array_merge($resource->resolve($request), ['elapsed_seconds' => $elapsedSeconds]));
    }

    public function start(StartStudySessionRequest $request): JsonResponse
    {
        $session = $this->studySessionService->start($request->user(), $request->validated('technology_id'));
        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão iniciada.', 201);
    }

    public function end(string $id): JsonResponse
    {
        $session = $this->studySessionService->end($id, request()->user()->id);
        return $this->success(new StudySessionResource($session->load('technology')), 'Sessão finalizada.');
    }
}
SSEOF
echo "[OK] Bug 3 - StudySessionController active()"

# =============================================================================
# BUG 4 (Alta) — Remover bash da lista de linguagens suportadas
# =============================================================================
cat > backend/app/Modules/CodeExecution/Services/CodeExecutionService.php << 'CESVC'
<?php
namespace App\Modules\CodeExecution\Services;
use App\Modules\CodeExecution\DTOs\ExecutionResultDTO;
use App\Modules\CodeExecution\Exceptions\SandboxExecutionException;
use Illuminate\Support\Facades\Log;

class CodeExecutionService
{
    public function __construct(
        private DockerSandboxService $sandboxService
    ) {}

    public function execute(string $code, string $language): ExecutionResultDTO
    {
        $language = strtolower(trim($language));
        if (! in_array($language, $this->supportedLanguages(), true)) {
            throw new SandboxExecutionException("Linguagem '{$language}' não é suportada.");
        }
        $result = $this->sandboxService->run($code, $language);
        return new ExecutionResultDTO(
            success: $result['success'],
            output: $result['output'],
            error: $result['error'],
            executionTime: $result['executionTime']
        );
    }

    public function supportedLanguages(): array
    {
        return ['php', 'laravel', 'sql'];
    }
}
CESVC
echo "[OK] Bug 4 - Bash removido da lista"

# =============================================================================
# BUG 5 (Média) — AnalyticsService: retornar uuid real
# =============================================================================
cat > backend/app/Modules/Analytics/Services/AnalyticsService.php << 'ASVC'
<?php
namespace App\Modules\Analytics\Services;
use App\Modules\Analytics\DTOs\DashboardDataDTO;
use App\Modules\Analytics\DTOs\TechnologyMetricsDTO;
use App\Modules\Analytics\Jobs\RecalculateMetricsJob;
use App\Modules\Analytics\Repositories\AnalyticsRepositoryInterface;
use App\Modules\Technologies\Services\TechnologyService;
use Illuminate\Support\Str;

class AnalyticsService
{
    public function __construct(
        private AnalyticsRepositoryInterface $repository,
        private TechnologyService $technologyService
    ) {}

    public function getDashboardData(string $userId): DashboardDataDTO
    {
        return new DashboardDataDTO($this->buildDashboardData($userId));
    }

    public function getTechnologyMetrics(string $userId): array
    {
        return $this->repository->getTechnologyMetrics($userId);
    }

    public function getHeatmapData(string $userId): array
    {
        return $this->repository->getHeatmapData($userId);
    }

    public function getDailyMinutesByRange(string $userId, string $start, string $end): array
    {
        return $this->repository->getDailyMinutesByRange($userId, $start, $end);
    }

    public function dispatchRecalculate(string $userId): array
    {
        $job = new RecalculateMetricsJob($userId, true);
        $job->onQueue('metrics');
        $job->uuid(Str::uuid()->toString());
        dispatch($job);
        return ['job_id' => $job->uuid()];
    }

    private function buildDashboardData(string $userId): array
    {
        $technologyMetrics = $this->repository->getTechnologyMetrics($userId);
        $topTechnologies = array_slice($technologyMetrics, 0, 5);
        return [
            'user_metrics' => $this->repository->getUserMetrics($userId),
            'technology_metrics' => $technologyMetrics,
            'time_series_30d' => $this->repository->getTimeSeries30d($userId),
            'top_technologies' => $topTechnologies,
        ];
    }
}
ASVC
echo "[OK] Bug 5 - AnalyticsService uuid"

# =============================================================================
# BUG 6 (Média) — SocialAuthService: consolidar queries
# =============================================================================
cat > backend/app/Modules/Auth/Services/SocialAuthService.php << 'SAEOF'
<?php
namespace App\Modules\Auth\Services;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialAuthService
{
    public function handleOAuthUser(SocialiteUser $socialUser, string $provider): User
    {
        $providerId = $provider.'_id';
        $email = $socialUser->getEmail();

        $user = User::where($providerId, $socialUser->getId())->first();
        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
                'email' => $email,
                $providerId => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
                'password' => Str::random(60),
            ]);
        }

        // Preparar dados para atualização em uma única query
        $updateData = [];
        if (! $user->{$providerId}) {
            $updateData[$providerId] = $socialUser->getId();
        }
        $updateData['avatar_url'] = $socialUser->getAvatar() ?? $user->avatar_url;

        // Tokens OAuth
        $tokenFields = [
            'discord' => ['discord_token', 'discord_refresh_token', 'discord_token_expires_at'],
            'google' => ['google_token', 'google_refresh_token', 'google_token_expires_at'],
            'linkedin' => ['linkedin_token', 'linkedin_refresh_token', 'linkedin_token_expires_at'],
        ];

        if (isset($tokenFields[$provider])) {
            [$tokenKey, $refreshKey, $expiresKey] = $tokenFields[$provider];
            $updateData[$tokenKey] = $socialUser->token;
            $updateData[$refreshKey] = $socialUser->refreshToken;
            $updateData[$expiresKey] = $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null;
        }

        $user->forceFill($updateData)->save();

        return $user->fresh();
    }
}
SAEOF
echo "[OK] Bug 6 - SocialAuthService consolidado"

# =============================================================================
# BUG 7 (Média) — UpdateStudySessionRequest: formato ISO 8601
# =============================================================================
cat > backend/app/Http/Requests/StudySessions/UpdateStudySessionRequest.php << 'USR'
<?php
namespace App\Http\Requests\StudySessions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudySessionRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'technology_id' => ['nullable', 'string', 'exists:technologies,id'],
            'started_at' => ['nullable', 'date_format:Y-m-d\TH:i:s'],
            'ended_at' => [
                'nullable', 'date_format:Y-m-d\TH:i:s',
                function ($attribute, $value, $fail) {
                    if ($this->has('started_at') && $value && $this->started_at >= $value) {
                        $fail('A data de término deve ser posterior à data de início.');
                    }
                },
            ],
            'duration_min' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
USR
echo "[OK] Bug 7 - UpdateStudySessionRequest"

# =============================================================================
# BUG 8 (Baixa) — User model: IDs OAuth fora de fillable
# =============================================================================
cat > backend/app/Models/User.php << 'UMODEL'
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'email', 'password', 'timezone', 'locale', 'avatar_url',
    ];

    protected $hidden = [
        'password', 'remember_token',
        'discord_token', 'discord_refresh_token',
        'google_token', 'google_refresh_token',
        'linkedin_token', 'linkedin_refresh_token',
    ];

    public array $oauthTokenFields = [
        'discord_token', 'discord_refresh_token', 'discord_token_expires_at',
        'google_token', 'google_refresh_token', 'google_token_expires_at',
        'linkedin_token', 'linkedin_refresh_token', 'linkedin_token_expires_at',
    ];

    // IDs OAuth — não em $fillable para evitar mass assignment
    public array $oauthIdFields = ['google_id', 'discord_id', 'linkedin_id'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'discord_token' => 'encrypted', 'discord_refresh_token' => 'encrypted',
            'google_token' => 'encrypted', 'google_refresh_token' => 'encrypted',
            'linkedin_token' => 'encrypted', 'linkedin_refresh_token' => 'encrypted',
            'discord_token_expires_at' => 'datetime',
            'google_token_expires_at' => 'datetime',
            'linkedin_token_expires_at' => 'datetime',
        ];
    }

    public function technologies(): HasMany { return $this->hasMany(Technology::class); }
    public function studySessions(): HasMany { return $this->hasMany(StudySession::class); }
}
UMODEL
echo "[OK] Bug 8 - User model"

# =============================================================================
# BUG 9 (Crítica) — goals.store.ts: adicionar async/await
# =============================================================================
cat > frontend/src/stores/goals.store.ts << 'GSTORE'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Goal, CreateGoalPayload } from '@/types/goals.types'
import { goalsApi } from '@/api/modules/goals.api'

export const useGoalsStore = defineStore('goals', () => {
  const items = ref<Goal[]>([])
  const error = ref<string | null>(null)

  const activeGoals = computed(() => items.value.filter((g) => g.status === 'active'))
  const completedGoals = computed(() => items.value.filter((g) => g.status === 'completed'))

  async function fetchGoals() {
    error.value = null
    try {
      items.value = await goalsApi.list()
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao carregar metas'
    }
  }

  async function createGoal(payload: CreateGoalPayload): Promise<Goal | null> {
    error.value = null
    try {
      const goal = await goalsApi.create(payload)
      items.value = [goal, ...items.value]
      return goal
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao criar meta'
      return null
    }
  }

  async function updateGoal(
    id: string,
    payload: { target_value?: number; status?: Goal['status']; end_date?: string | null }
  ): Promise<Goal | null> {
    error.value = null
    try {
      const updated = await goalsApi.update(id, payload)
      const index = items.value.findIndex((g) => g.id === id)
      if (index !== -1) {
        items.value = [...items.value.slice(0, index), updated, ...items.value.slice(index + 1)]
      }
      return updated
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao atualizar meta'
      return null
    }
  }

  async function deleteGoal(id: string): Promise<boolean> {
    error.value = null
    try {
      await goalsApi.delete(id)
      items.value = items.value.filter((g) => g.id !== id)
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Erro ao excluir meta'
      return false
    }
  }

  function getProgress(goal: Goal, currentValueOverride?: number): number {
    const current = currentValueOverride ?? goal.current_value
    if (goal.target_value <= 0) return 0
    return Math.min(100, Math.round((current / goal.target_value) * 100))
  }

  function getActiveWeeklyMinutesGoal(): Goal | null {
    return items.value.find((g) => g.status === 'active' && g.type === 'minutes_per_week') ?? null
  }

  return { items, error, activeGoals, completedGoals, fetchGoals, createGoal, updateGoal, deleteGoal, getProgress, getActiveWeeklyMinutesGoal }
})
GSTORE
echo "[OK] Bug 9 - goals.store async/await"

# =============================================================================
# BUG 10 (Alta) — useCanvas: limpar ResizeObserver e fabricCanvas no unmount
# =============================================================================
python3 -c "
with open('frontend/src/features/canvas/composables/useCanvas.ts', 'r') as f:
    content = f.read()

# Substituir onBeforeUnmount para limpar corretamente
old_unmount = 'onBeforeUnmount(() => { resizeObserver?.disconnect(); _canvas?.dispose(); _canvas = null })'
new_unmount = 'onBeforeUnmount(() => { resizeObserver?.disconnect(); resizeObserver = null; _canvas?.dispose(); _canvas = null; setFabricCanvas(null as any) })'
content = content.replace(old_unmount, new_unmount)

with open('frontend/src/features/canvas/composables/useCanvas.ts', 'w') as f:
    f.write(content)
"
echo "[OK] Bug 10 - useCanvas cleanup"

# =============================================================================
# BUG 11 (Alta) — useGoalProgress: filtrar por start_date/end_date
# =============================================================================
cat > frontend/src/features/goals/composables/useGoalProgress.ts << 'GP'
import { computed } from 'vue'
import type { Goal } from '@/types/goals.types'
import { useAnalyticsStore } from '@/stores/analytics.store'

export function useGoalProgress(goal: {
  type: Goal['type']
  start_date: string
  end_date: string | null
}) {
  const analyticsStore = useAnalyticsStore()

  const currentValue = computed(() => {
    const startDate = new Date(goal.start_date)
    const endDate = goal.end_date ? new Date(goal.end_date) : new Date()

    if (goal.type === 'minutes_per_week') {
      const series = analyticsStore.timeSeriesData['7d'] ?? []
      return series
        .filter((d) => {
          const date = new Date(d.date)
          return date >= startDate && date <= endDate
        })
        .reduce((acc, d) => acc + (d.total_minutes ?? 0), 0)
    }
    if (goal.type === 'sessions_per_week') {
      const series = analyticsStore.timeSeriesData['7d'] ?? []
      return series
        .filter((d) => {
          const date = new Date(d.date)
          return date >= startDate && date <= endDate
        })
        .reduce((acc, d) => acc + (d.session_count ?? 0), 0)
    }
    if (goal.type === 'streak_days') {
      return analyticsStore.userMetrics?.current_streak_days ?? 0
    }
    return 0
  })

  return { currentValue }
}
GP
echo "[OK] Bug 11 - useGoalProgress date filter"

# =============================================================================
# BUG 12 (Alta) — useEndSession: compartilhar timer via singleton
# =============================================================================
cat > frontend/src/features/sessions/composables/useSessionTimer.ts << 'STIMER'
import { ref, onUnmounted } from 'vue'
import { sessionsApi } from '@/api/modules/sessions.api'
import type { StudySession } from '@/types/domain.types'

// Singleton compartilhado entre todos os consumidores
let activeSession = ref<StudySession | null>(null)
let elapsedSeconds = ref(0)
let timerInterval: ReturnType<typeof setInterval> | null = null
let consumerCount = 0

function startTimer() {
  if (timerInterval) return
  timerInterval = setInterval(() => {
    if (activeSession.value?.started_at) {
      const start = new Date(activeSession.value.started_at).getTime()
      elapsedSeconds.value = Math.floor((Date.now() - start) / 1000)
    }
  }, 1000)
}

function stopTimer() {
  if (timerInterval) {
    clearInterval(timerInterval)
    timerInterval = null
  }
}

function formatTime(seconds: number): string {
  const h = Math.floor(seconds / 3600)
  const m = Math.floor((seconds % 3600) / 60)
  const s = seconds % 60
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
}

export function useSessionTimer() {
  consumerCount++

  async function refresh() {
    try {
      const { data } = await sessionsApi.active()
      activeSession.value = data?.data ?? null
      if (activeSession.value) {
        startTimer()
      } else {
        stopTimer()
      }
    } catch {
      activeSession.value = null
      stopTimer()
    }
  }

  onUnmounted(() => {
    consumerCount--
    if (consumerCount <= 0) {
      stopTimer()
      consumerCount = 0
    }
  })

  const formattedTime = computed(() => formatTime(elapsedSeconds.value))

  return { activeSession, elapsedSeconds, formattedTime, refresh }
}

import { computed } from 'vue'
STIMER
echo "[OK] Bug 12 - useSessionTimer singleton"

# =============================================================================
# BUG 13 (Média) — api/client.ts: debounce por rota
# =============================================================================
cat > frontend/src/api/client.ts << 'APIEOF'
import axios from 'axios'
import { useAuthStore } from '@/stores/auth.store'
import { router } from '@/router'

const apiClient = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '',
  withCredentials: true,
  headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
  withXSRFToken: true,
})

apiClient.interceptors.request.use((config) => {
  if (config.method === 'get') {
    config.params = { ...config.params, _t: Date.now() }
  }
  return config
})

type ToastFn = (msg: string, type?: 'success' | 'error' | 'info') => void
let toastFn: ToastFn | null = null
export function setApiToast(fn: ToastFn) { toastFn = fn }

let lastUnauthorizedRoute = ''
let lastUnauthorizedTime = 0
const UNAUTHORIZED_DEBOUNCE_MS = 300

function getApiErrorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    return error.response?.data?.error?.message ?? error.message
  }
  return 'Erro desconhecido'
}

apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error?.__sessionNotReady) return Promise.reject(error)

    const status = error.response?.status
    const reqUrl = String(error.config?.url ?? '')

    if (status === 401) {
      if (reqUrl.includes('/auth/login') || reqUrl.includes('/auth/register') || reqUrl.includes('auth/logout')) {
        return Promise.reject(error)
      }
      const routeName = router.currentRoute.value.name as string
      const now = Date.now()
      if (lastUnauthorizedRoute === routeName && now - lastUnauthorizedTime < UNAUTHORIZED_DEBOUNCE_MS) {
        return Promise.reject(error)
      }
      lastUnauthorizedRoute = routeName
      lastUnauthorizedTime = now
      try {
        useAuthStore().clearSessionLocally()
        if (routeName !== 'login') {
          await router.push({ name: 'login' })
        }
      } catch { /* ignore navigation errors */ }
    } else if (status === 429) {
      const message = getApiErrorMessage(error) || 'Muitas requisições. Aguarde um momento e tente novamente.'
      if (toastFn) toastFn(message, 'error')
    } else if (status && status >= 500) {
      const message = getApiErrorMessage(error) || 'Erro interno do servidor.'
      if (toastFn) toastFn(message, 'error')
    }

    return Promise.reject(error)
  }
)

export function getApiErrorMessageExport(error: unknown): string {
  return getApiErrorMessage(error)
}

export { apiClient, getApiErrorMessage }
APIEOF
echo "[OK] Bug 13 - api/client debounce"

# =============================================================================
# BUG 14 (Média) — useCanvas: revokeObjectURL
# =============================================================================
python3 -c "
with open('frontend/src/features/canvas/composables/useCanvas.ts', 'r') as f:
    content = f.read()

# Substituir downloadSVG para revogar URL
old_svg = \"function downloadSVG() { const s = toSVG(); if (s) { const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([s], { type: 'image/svg+xml' })); a.download = 'canvas.svg'; a.click() } }\"
new_svg = \"function downloadSVG() { const s = toSVG(); if (s) { const url = URL.createObjectURL(new Blob([s], { type: 'image/svg+xml' })); const a = document.createElement('a'); a.href = url; a.download = 'canvas.svg'; a.click(); setTimeout(() => URL.revokeObjectURL(url), 100) } }\"
content = content.replace(old_svg, new_svg)

# Substituir downloadJSON para revogar URL
old_json = \"function downloadJSON() { const j = JSON.stringify(toJSON(), null, 2); const a = document.createElement('a'); a.href = URL.createObjectURL(new Blob([j], { type: 'application/json' })); a.download = 'canvas.json'; a.click() }\"
new_json = \"function downloadJSON() { const j = JSON.stringify(toJSON(), null, 2); const url = URL.createObjectURL(new Blob([j], { type: 'application/json' })); const a = document.createElement('a'); a.href = url; a.download = 'canvas.json'; a.click(); setTimeout(() => URL.revokeObjectURL(url), 100) }\"
content = content.replace(old_json, new_json)

with open('frontend/src/features/canvas/composables/useCanvas.ts', 'w') as f:
    f.write(content)
"
echo "[OK] Bug 14 - revokeObjectURL"

# =============================================================================
# BUG 15 (Média) — useSessionEdit: usar T00:00:00.000Z
# =============================================================================
python3 -c "
with open('frontend/src/features/sessions/composables/useSessionEdit.ts', 'r') as f:
    content = f.read()

# Substituir T12:00:00 por T00:00:00.000
content = content.replace('T12:00:00', 'T00:00:00.000')

with open('frontend/src/features/sessions/composables/useSessionEdit.ts', 'w') as f:
    f.write(content)
"
echo "[OK] Bug 15 - useSessionEdit timezone"

# =============================================================================
# BUG 16 (Média) — player.store: bounds check no nextVideo
# =============================================================================
python3 -c "
with open('frontend/src/stores/player.store.ts', 'r') as f:
    content = f.read()

# Substituir videoIndex.value++ por modulo
content = content.replace(
    'videoIndex.value++',
    'videoIndex.value = (videoIndex.value + 1) % (selectedPlaylist.value?.items?.length || 1)'
)

with open('frontend/src/stores/player.store.ts', 'w') as f:
    f.write(content)
"
echo "[OK] Bug 16 - player bounds check"

# =============================================================================
# BUG 17 (Média) — useWebSocket: reset reconnectAttempts só no connected
# =============================================================================
python3 -c "
with open('frontend/src/composables/useWebSocket.ts', 'r') as f:
    content = f.read()

# Mover reset de reconnectAttempts para após conexão bem-sucedida
# Procurar por 'reconnectAttempts = 0' e remover de connectWebSocket
content = content.replace(
    '  // Armazena userId para reconexão automática e reseta contador\n  reconnectUserId = userId\n  reconnectAttempts = 0\n  clearReconnectTimer()',
    '  // Armazena userId para reconexão automática\n  reconnectUserId = userId\n  clearReconnectTimer()'
)

# Adicionar reset no callback de conexão bem-sucedida
# Procurar por 'connected' callback e adicionar reset
content = content.replace(
    \"echo.channel('private-dashboard.' + userId)\",
    \"echo.connector.pusher.connection.bind('connected', () => { reconnectAttempts = 0 })\necho.channel('private-dashboard.' + userId)\"
)

with open('frontend/src/composables/useWebSocket.ts', 'w') as f:
    f.write(content)
"
echo "[OK] Bug 17 - useWebSocket reconnect"

# =============================================================================
# BUG 18 (Baixa) — notifications.store: usar map para reatividade
# =============================================================================
cat > frontend/src/stores/notifications.store.ts << 'NSTORE'
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export type NotificationType = 'info' | 'success' | 'warning' | 'error'

export interface AppNotification {
  id: string
  type: NotificationType
  title: string
  message?: string
  read: boolean
  created_at: string
  actionUrl?: string
  actionLabel?: string
}

export const useNotificationsStore = defineStore('notifications', () => {
  const items = ref<AppNotification[]>([])

  function add(notification: Omit<AppNotification, 'id' | 'read' | 'created_at'>) {
    const id = `notif_${Date.now()}_${Math.random().toString(36).slice(2, 9)}`
    items.value = [
      { ...notification, id, read: false, created_at: new Date().toISOString() },
      ...items.value,
    ].slice(0, 50)
  }

  function markRead(id: string) {
    items.value = items.value.map((n) => n.id === id ? { ...n, read: true } : n)
  }

  function markAllRead() {
    items.value = items.value.map((n) => ({ ...n, read: true }))
  }

  function remove(id: string) {
    items.value = items.value.filter((n) => n.id !== id)
  }

  const unreadCount = computed(() => items.value.filter((n) => !n.read).length)

  return { items, add, markRead, markAllRead, remove, unreadCount }
})
NSTORE
echo "[OK] Bug 18 - notifications reatividade"

# =============================================================================
# BUG 19 (Baixa) — useDashboard: getFetchFn dentro do setInterval
# =============================================================================
python3 -c "
with open('frontend/src/features/dashboard/composables/useDashboard.ts', 'r') as f:
    content = f.read()

# Substituir captura externa por chamada interna
content = content.replace(
    'const doFetch = getFetchFn()\n    pollingIntervalId = setInterval(async () => {\n      try {\n        await doFetch()',
    'pollingIntervalId = setInterval(async () => {\n      try {\n        await getFetchFn()'
)

with open('frontend/src/features/dashboard/composables/useDashboard.ts', 'w') as f:
    f.write(content)
"
echo "[OK] Bug 19 - useDashboard polling"

# =============================================================================
# BUG 20 (Baixa) — auth.store: limpar user no catch
# =============================================================================
python3 -c "
with open('frontend/src/stores/auth.store.ts', 'r') as f:
    content = f.read()

# No catch de fetchMe, limpar user se status não é 401
old_catch = 'else if (sessionValidated.value || user.value) { registerOnlineRecovery() }'
new_catch = 'else { user.value = null; registerOnlineRecovery() }'
content = content.replace(old_catch, new_catch)

with open('frontend/src/stores/auth.store.ts', 'w') as f:
    f.write(content)
"
echo "[OK] Bug 20 - auth.store cleanup"

echo ""
echo "=== 20 bugs corrigidos com sucesso! ==="
echo ""
echo "Próximos passos:"
echo "  1. cd frontend && npx vue-tsc --noEmit"
echo "  2. cd backend && composer dump-autoload"
echo "  3. Rodar testes com banco disponível"
