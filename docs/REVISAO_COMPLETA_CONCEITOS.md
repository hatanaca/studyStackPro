# StudyTrack Pro — Revisão Completa de Conceitos

> Documento de revisão abrangente cobrindo algoritmos, estruturas de dados, arquitetura, design patterns, segurança e conceitos gerais utilizados no projeto.

---

## Sumário

1. [Arquitetura Geral](#1-arquitetura-geral)
2. [Modelagem de Dados & Estruturas](#2-modelagem-de-dados--estruturas)
3. [Algoritmos](#3-algoritmos)
4. [Design Patterns](#4-design-patterns)
5. [Segurança (OWASP)](#5-segurança-owasp)
6. [Concurrency & Tempo Real](#6-concurrency--tempo-real)
7. [Cache & Performance](#7-cache--performance)
8. [Frontend Architecture](#8-frontend-architecture)
9. [Infraestrutura & DevOps](#9-infraestrutura--devops)
10. [Testes & Qualidade](#10-testes--qualidade)

---

## 1. Arquitetura Geral

### 1.1 Stack Tecnológico

| Camada | Tecnologia | Versão |
|--------|-----------|--------|
| Frontend | Vue 3 + TypeScript + Vite | 3.4+ |
| Backend | Laravel (PHP) | 12.x |
| Database | PostgreSQL | 16 |
| Cache/Queue | Redis | 7.2 |
| WebSocket | Laravel Reverb | — |
| Container | Docker + Docker Compose | — |
| Reverse Proxy | OpenResty (Nginx + Lua) | 1.27 |

### 1.2 Arquitetura Modular (Backend)

O backend segue uma **arquitetura modular** onde cada domínio de negócio é encapsulado:

```
backend/app/Modules/
├── Auth/
│   ├── Services/AuthService.php
│   ├── Services/TokenService.php
│   ├── Repositories/Contracts/AuthRepositoryInterface.php
│   └── Repositories/EloquentAuthRepository.php
├── Technologies/
├── StudySessions/
├── Analytics/
│   ├── Aggregators/MetricsAggregator.php
│   └── Services/AnalyticsService.php
├── Goals/
├── Canvas/
├── StudyPaths/
├── Notifications/
├── Gamification/
│   └── Services/AchievementService.php
├── LinkedIn/
└── CodeExecution/
    ├── Services/CodeExecutionService.php
    └── Services/DockerSandboxService.php
```

**Conceito aplicado:** Separation of Concerns (SoC) — cada módulo é responsável por uma única área de negócio.

### 1.3 Frontend Architecture (Feature-Based)

```
frontend/src/
├── features/          # Módulos de feature (cada um self-contained)
│   ├── auth/
│   ├── canvas/
│   ├── code-terminal/
│   ├── dashboard/
│   ├── goals/
│   ├── notifications/
│   ├── profile/
│   ├── sessions/
│   ├── settings/
│   ├── study-path/
│   └── youtube/
├── components/        # Componentes reutilizáveis
├── composables/       # Lógica reativa compartilhada
├── stores/            # Estado global (Pinia)
├── api/               # Camada de API
├── types/             # Definições TypeScript
└── utils/             # Funções puras
```

### 1.4 CQRS-Lite

Separação entre write (transacional) e read (analytics):

- **Write path:** `public` schema — tabelas `users`, `technologies`, `study_sessions`
- **Read path:** `analytics` schema — tabelas `user_metrics`, `technology_metrics`, `daily_minutes`, `weekly_summaries`
- **Sincronização:** Background job `RecalculateMetricsJob` atualiza o schema analítico

---

## 2. Modelagem de Dados & Estruturas

### 2.1 Schema Relacional

**Entidades principais:**

```
users (1) ──< (N) technologies
users (1) ──< (N) study_sessions
users (1) ──< (N) goals
users (1) ──< (N) canvas_artworks
users (1) ──< (N) study_paths
users (1) ──< (N) notifications
users (1) ──< (N) achievements
users (1) ──< (N) reminders

technologies (1) ──< (N) study_sessions
technologies (1) ──< (N) study_paths
technologies (1) ──< (N) reminders
```

### 2.2 Estruturas de Dados

| Estrutura | Onde Usada | Purpose |
|-----------|-----------|---------|
| **JSONB** | `canvas_data`, `nodes`, `edges`, `meta`, `metadata` | Dados semiestruturados (Fabric.js, Vue Flow) |
| **UUID** | Todas as tabelas principais | Chaves primárias não-guessable |
| **Sorted Set (Redis)** | Sliding window rate limiter | Janela deslizante ordenada por timestamp |
| **Hash (Redis)** | Token blacklist, cache entries | Lookup O(1) por chave |
| **Set (Redis)** | Job deduplication | Controle de concorrência |
| **Array (PostgreSQL)** | `analytics.daily_minutes.technologies` | Lista de IDs de tecnologias estudadas |
| **GENERATED STORED** | `duration_min`, `total_hours` | Colunas calculadas automaticamente pelo banco |

### 2.3 Índices (PostgreSQL)

| Tipo | Exemplo | Quando Usado |
|------|---------|--------------|
| **B-tree** | `idx_tech_user_id` | Lookups exatos por user_id |
| **GIN (trigram)** | `name gin_trgm_ops` | Busca fuzzy por nome de tecnologia |
| **BRIN** | `idx_sessions_started_brin` | Dados temporalmente ordenados (minimiza espaço) |
| **Partial** | `idx_sessions_in_progress WHERE ended_at IS NULL` | Queries sobre sessões ativas |
| **Composite** | `idx_sessions_user_tech(user_id, technology_id, started_at DESC)` | Queries filtradas por tech + ordenadas por data |
| **Exclusion** | `btree_gist` | Impedir sessões simultâneas |

### 2.4 Extensões PostgreSQL

| Extensão | Função |
|----------|--------|
| `pgcrypto` | `gen_random_uuid()` — geração de UUIDs |
| `pg_trgm` | Busca fuzzy com operador `ILIKE '%term%'` via GIN |
| `btree_gist` | Índices de exclusão para dados temporais |
| `pllua` | Triggers em Lua (PL/Lua para PL/pgSQL) |

---

## 3. Algoritmos

### 3.1 Productivity Score (Trigger PL/pgSQL)

**Arquivo:** `database/migrations/transactional/2026_04_04_000005_add_productivity_score_to_study_sessions_table.php`

```sql
-- Algoritmo de scoring baseado em duração:
IF duration < 15 THEN
    score = floor(duration * 0.5)   -- Sessões muito curtas: bônus baixo
ELSIF duration < 45 THEN
    score = duration * 1.0          -- Sessões normais: score linear
ELSIF duration < 90 THEN
    score = floor(duration * 1.1)   -- Sessões longas: +10% bônus
ELSE
    score = floor(duration * 1.3)   -- Sessões muito longas: +30% bônus
END IF
```

**Conceito:** Função piecewise (definição por partes) — recompensa sessões mais longas com multiplicadores crescentes.

### 3.2 Streak Calculation (Redis Lua Script)

**Arquivo:** `redis-scripts/streak_update.lua`

```lua
-- Algoritmo de streak (dias consecutivos):
if last_day == today then
    return current_streak           -- Já estudou hoje, mantém
elseif last_day == yesterday then
    return current_streak + 1       -- Dia consecutivo, incrementa
else
    return 1                        -- Streak quebrado, reset para 1
end
```

**Conceito:** Máquina de estados com 3 estados: `mantido`, `incrementado`, `resetado`. Execução atômica via Lua no Redis.

### 3.3 Sliding Window Rate Limiter (Redis Lua Script)

**Arquivo:** `redis-scripts/sliding_window.lua`

```
Algoritmo:
1. REMOVER entradas com score < (agora - window)  -- Podar expirados
2. CONTAR entradas restantes (ZCARD)
3. SE count >= limit:
   - Calcular retry_after = oldest_entry + window - now
   - RETORNAR {0, retry_after}
4. ADicionar entrada com score = now
5. SETEX key window (auto-expire)
6. RETORNAR {1, 0}
```

**Estrutura de dados:** Redis Sorted Set (ZSET) — cada membro tem score = timestamp.

**Conceito:** Janela deslizante (sliding window) — mais preciso que janela fixa (fixed window) pois considera o momento exato de cada requisição.

### 3.4 Job Deduplication (Redis Lua Script)

**Arquivo:** `redis-scripts/job_dedup.lua`

```lua
-- Lock atômico SET NX EX
local acquired = redis.call('SET', key, '1', 'NX', 'EX', ttl)
return acquired and 1 or 0
```

**Conceito:** Distributed lock com TTL — previne execução dupla de jobs quando múltiplos eventos disparam ao mesmo tempo.

### 3.5 Heatmap Grid Generation (Frontend)

**Arquivo:** `frontend/src/components/charts/HeatmapChart.vue`

```
Algoritmo:
1. Criar Map<date, minutes> a partir dos dados da API
2. Encontrar primeiro dia do ano selecionado
3. Calcular padding inicial: (primeiroDiaDaSemana + 6) % 7  (para início em segunda)
4. Iterar cada semana (7 dias), agrupar em colunas
5. Colorir baseado na razão minutes/maxMinutes:
   - 0: vazio
   - < 0.25: nível 1 (claro)
   - < 0.50: nível 2
   - < 0.75: nível 3
   - >= 0.75: nível 4 (escuro)
```

**Conceito:** Grid 2D com mapeamento de cores (color mapping) — padrão do GitHub contribution graph.

### 3.6 Timer Calculation (Frontend)

**Arquivo:** `frontend/src/stores/sessions.store.ts`

```typescript
// Cálculo de elapsed seconds:
elapsedSeconds.value = Math.floor((Date.now() - startedAt) / 1000)

// Formatação HH:MM:SS:
const h = Math.floor(s / 3600)
const m = Math.floor((s % 3600) / 60)
const sec = s % 60
return [h, m, sec].map(n => n.toString().padStart(2, '0')).join(':')
```

**Conceito:** Modular arithmetic para decomposição temporal.

### 3.7 Text Layout & Measurement (Frontend)

**Arquivo:** `frontend/src/composables/useTextMeasure.ts`

```
Algoritmo:
1. Usar @chenglou/pretext para medir texto com precisão
2. Cache LRU (500 entradas) para evitar recálculo
3. Calcular: largura máxima → número de linhas → altura total
4. Usado para virtualização de cards de sessão
```

**Conceito:** LRU Cache (Least Recently Used) — eviction policy que remove a entrada menos recentemente acessada.

### 3.8 Exponential Backoff (WebSocket Reconnection)

**Arquivo:** `frontend/src/composables/useWebSocket.ts`

```
Algoritmo:
delay = min(1000 * 2^attempt, 60000)  // 1s, 2s, 4s, 8s, 16s, 32s, 60s (cap)
maxAttempts = 10
```

**Conceito:** Exponential backoff — estratégia para reconexão que evita thundering herd (múltiplos clientes reconectando simultaneamente).

### 3.9 CTE (Common Table Expression) para Analytics

**Arquivo:** `backend/app/Modules/Analytics/Aggregators/MetricsAggregator.php`

```sql
-- Percentage of total por tecnologia:
WITH tech_totals AS (
    SELECT technology_id, SUM(duration_min) as total
    FROM study_sessions
    WHERE user_id = ?
    GROUP BY technology_id
)
SELECT *,
    ROUND((total::numeric / SUM(total) OVER ()) * 100, 2) as percentage_total
FROM tech_totals
```

**Conceito:** CTE + Window Function — cálculo de proporção relativa em uma única query.

### 3.10 Streak Max (Frontend/Backend)

**Arquivo:** `backend/app/Modules/Analytics/Aggregators/MetricsAggregator.php`

```
Algoritmo (lines 149-201):
1. Buscar todas as datas de estudo dos últimos 730 dias
2. Ordenar datas cronologicamente
3. Calcular streak atual: começar de HOJE, contar dias consecutivos para trás
4. Calcular streak máximo: iterar todas as datas, manter maior sequência consecutiva
```

**Conceito:** Two-pointer / consecutive subsequence detection — encontrar a maior subsequência consecutiva em uma lista ordenada.

---

## 4. Design Patterns

### 4.1 Repository Pattern

**Interface + Implementação:** 9 repositórios com contratos definidos.

```php
interface TechnologyRepositoryInterface {
    public function findForUser(string $userId, string $id): ?Technology;
    public function getAllForUser(string $userId): Collection;
    public function create(array $data): Technology;
    // ...
}

class EloquentTechnologyRepository implements TechnologyRepositoryInterface {
    // Implementação com cache, queries, etc.
}
```

**Benefício:** Desacoplamento entre lógica de negócio e persistência. Facilita testes (mock do repository) e troca de implementação.

### 4.2 DTO (Data Transfer Object)

```php
final readonly class StudySessionDTO {
    public function __construct(
        public string $technologyId,
        public ?string $title,
        public Carbon $startedAt,
        // ...
    ) {}
}
```

**Usado em:** Todos os módulos — GoalDTO, CanvasArtworkDTO, StudyPathDTO, NotificationDTO, ReminderDTO.

**Benefício:** Imutabilidade, validação de entrada, separação entre HTTP request e domínio.

### 4.3 Observer/Event Pattern

```
StudySessionCreated → [
    InvalidateSessionCache,
    DispatchMetricsRecalculation,
    BroadcastSessionStarted,
    BroadcastMetricsRecalculating
]

StudySessionUpdated → [
    InvalidateSessionCache,
    DispatchMetricsRecalculation,
    BroadcastMetricsRecalculating,
    BroadcastSessionEnded
]

StudySessionDeleted → [
    InvalidateSessionCache,
    DispatchMetricsRecalculation,
    BroadcastMetricsRecalculating
]
```

**Conceito:** Event-driven architecture — cada ação dispara eventos que são processados assincronamente por listeners independentes.

### 4.4 Strategy Pattern (Code Execution)

```
Linguagem → Executor:
  JavaScript → Web Worker (client-side)
  Lua        → Fengari VM (client-side)
  HTML/CSS   → Sandboxed iframe (client-side)
  PHP        → Docker container (server-side)
  SQL        → Docker container (server-side)
  Laravel    → Docker container (server-side)
  Bash       → Docker container (server-side)
```

**Conceito:** Strategy pattern — selecionar algoritmo de execução em tempo de execução baseado no tipo de linguagem.

### 4.5 Singleton Pattern (Session Timer)

**Arquivo:** `frontend/src/features/sessions/composables/useSessionTimer.ts`

```typescript
let intervalId: ReturnType<typeof setInterval> | null = null
let consumerCount = 0

export function useSessionTimer() {
  consumerCount++
  onUnmounted(() => {
    consumerCount--
    if (consumerCount === 0 && intervalId) {
      clearInterval(intervalId)
      intervalId = null
    }
  })
  // ...
}
```

**Conceito:** Reference counting singleton — único intervalo compartilhado entre múltiplos consumidores, destruído quando todos removem.

### 4.6 Adapter Pattern (API Client)

```typescript
// Frontend API client adapta responses:
export async function unwrap<T>(response: AxiosResponse<ApiResponse<T>>): Promise<T> {
  return response.data.data  // Extrai do envelope { success, data, message }
}
```

**Conceito:** Adapter — transforma a estrutura de resposta da API em formato utilizável pelo frontend.

### 4.7 Middleware Pipeline

```
Request → EnsureJsonResponse → SecurityHeaders → LogApiRequests → SetUserTimezone → throttle → auth:sanctum → Controller
```

**Conceito:** Pipeline pattern — cada middleware processa a request antes de passar para o próximo.

### 4.8 Optimistic Update (Frontend)

**Arquivo:** `frontend/src/stores/analytics.store.ts`

```
1. Usuário cria sessão → adiciona a pendingSessions (UI atualiza imediatamente)
2. API retorna dados → merge com pendingSessions
3. Backend confirma → reconcilePending() remove de pendingSessions
```

**Conceito:** Optimistic UI — atualizar a interface antes da confirmação do servidor para melhorar percepção de performance.

### 4.9 Facade Pattern (RedisLuaService)

```php
class RedisLuaService {
    public function __construct(protected Cache $cache) {}
    
    public function execute(string $name, array $keys, array $args): mixed {
        $sha = $this->loadScript($name);
        // ... executa via SHA
    }
}
```

**Conceito:** Facade — interface simplificada para operações complexas de Redis Lua (load, cache SHA, retry on NOSCRIPT).

---

## 5. Segurança (OWASP)

### 5.1 Defense in Depth (6 Camadas)

```
┌─────────────────────────────────────────────┐
│  Camada 1: Edge WAF (OpenResty/Lua)         │  ← User-Agent + SQLi + NoSQLi blocking
├─────────────────────────────────────────────┤
│  Camada 2: Token Revocation (Edge)          │  ← SHA-256 hash + Redis blacklist
├─────────────────────────────────────────────┤
│  Camada 3: Security Headers (Lua)           │  ← CSP, X-Frame-Options, HSTS
├─────────────────────────────────────────────┤
│  Camada 4: Rate Limiting                    │  ← Nginx limit_req + Redis sliding window
├─────────────────────────────────────────────┤
│  Camada 5: Laravel (Application)            │  ← Sanctum, validation, authorization
├─────────────────────────────────────────────┤
│  Camada 6: Container Hardening              │  ← Non-root, cap_drop, read-only
└─────────────────────────────────────────────┘
```

### 5.2 OWASP Top 10 Coverage

| OWASP | Mitigação no Projeto |
|-------|---------------------|
| A01: Broken Access Control | Repository pattern com ownership check, `user_id` NOT in `$fillable` |
| A02: Cryptographic Failures | OAuth tokens encrypted at rest (`encrypted` cast), SHA-256 para blacklist |
| A03: Injection | FormRequest validation, parameterized queries (Eloquent), WAF SQLi patterns |
| A04: Insecure Design | Docker sandbox (cap_drop, read-only, network none, pids-limit) |
| A05: Security Misconfiguration | Security headers, `server_tokens off`, trusted proxies |
| A06: Vulnerable Components | Dependabot (weekly), Composer audit, npm audit, Trivy scan |
| A07: Auth Failures | Rate limiting (3/min login), token revocation, bcrypt passwords |
| A08: Data Integrity | CSRF nonce-based OAuth, `ShouldBeUnique` jobs, UUID primary keys |
| A09: Logging | Sentry integration, structured logging (no sensitive data) |
| A10: SSRF | YouTube API proxy (key not exposed to frontend), OAuth redirect validation |

### 5.3 Code Sandbox Security

```
Docker flags para execução de código:
  --network none          ← Sem acesso à rede
  --memory 128m           ← Limite de memória
  --cpus 0.5              ← Limite de CPU
  --read-only             ← Filesystem read-only
  --pids-limit 50         ← Limite de processos
  --user nobody           ← Non-root
  --security-opt no-new-privileges  ← Sem escalation
  --tmpfs /tmp:size=10m   ← /tmp temporário
```

### 5.4 JavaScript Sandbox (Client-Side)

```
Medidas de segurança:
1. Web Worker isolation (thread separada)
2. Global shadowing (self, window, fetch, navigator = undefined)
3. Safe Function constructor com APIs whitelisted
4. Regex blocking: constructor.constructor, __proto__, Reflect, Proxy, import()
5. Timeout: 5 segundos
6. Code length: 10.000 caracteres máximo
```

---

## 6. Concurrency & Tempo Real

### 6.1 Concurrent Session Prevention (Banco + Aplicação)

**Camada 1 — Trigger PostgreSQL:**
```sql
-- check_concurrent_sessions()
-- BEFORE INSERT/UPDATE: se existe sessão ativa, fazer END automaticamente
```

**Camada 2 — Service Layer:**
```php
// StudySessionService::start()
if ($existingActive) {
    throw new ConcurrentSessionException();
}
```

**Conceito:** Defense in depth para concorrência — proteção em múltiplas camadas.

### 6.2 WebSocket Events (Laravel Reverb)

```
Canal privado: dashboard.{userId}

Eventos:
  .session.started      → Frontend: set activeSession
  .session.ended        → Frontend: clear activeSession
  .metrics.recalculating → Frontend: show spinner
  .metrics.updated      → Frontend: refetch dashboard data
```

### 6.3 Job Deduplication

```
Evento → DispatchMetricsRecalculation → Redis Lua job_dedup → RecalculateMetricsJob
                                              ↓
                                    SET NX EX (2s TTL)
                                              ↓
                              1 = dispatch | 0 = skip (duplicate)
```

**Conceito:** At-least-once delivery com deduplication — garante que o job execute pelo menos uma vez, mas evita execuções múltiplas.

### 6.4 ShouldBeUnique (Laravel)

```php
class RecalculateMetricsJob implements ShouldQueue, ShouldBeUnique {
    public function uniqueId(): string {
        return $this->userId;
    }
}
```

**Conceito:** Idempotent jobs — mesmo que múltiplos eventos disparem o mesmo job, apenas uma instância executa por userId.

---

## 7. Cache & Performance

### 7.1 Cache Strategy (Multi-Layer)

| Dados | Invalidated by | TTL | Tags |
|-------|---------------|-----|------|
| Technologies | Create/Update/Deactivate | 5min | `technologies`, `technologies:user:{id}` |
| Active Session | Session start/end | 5min | `sessions`, `sessions:user:{id}` |
| Dashboard | Metrics recalculation | 5min | `analytics:user:{id}` |
| User Timezone | — | 5min | (key-based) |

### 7.2 Generated Stored Columns

```sql
duration_min INTEGER GENERATED ALWAYS AS (
    EXTRACT(EPOCH FROM (ended_at - started_at)) / 60
) STORED

total_hours NUMERIC(8,1) GENERATED ALWAYS AS (
    total_minutes / 60.0
) STORED
```

**Conceito:** Persistência calculada — valores computados são armazenados físicamente para evitar recálculo em queries frequentes.

### 7.3 Vite Code Splitting

```javascript
// Chunk splitting manual:
chunks: {
  'http-vendor': ['axios'],
  'ws-vendor': ['pusher-js', 'laravel-echo'],
  'vue-vendor': ['vue', 'vue-router', 'pinia'],
  'primevue-vendor': ['primevue'],
  'charts-apex': ['apexcharts', 'vue3-apexcharts'],
  'pdf-vendor': ['jspdf', '@chenglou/pretext'],
  'vue-flow-vendor': ['@vue-flow/core'],
}
```

**Conceito:** Code splitting — dividir o bundle em chunks menores para carregamento incremental (lazy loading).

### 7.4 Route Prefetching

```typescript
// Prefetch no hover da sidebar:
el.addEventListener('mouseenter', () => {
  import('@/views/DashboardView.vue')  // Pré-baixa o chunk
})
```

**Conceito:** Predictive prefetching — antecipar carregamento baseado no comportamento do usuário.

### 7.5 OPcache + JIT (PHP)

```ini
; Produção:
opcache.validate_timestamps = 0    ; Não revalida arquivos
opcache.jit = tracing              ; JIT tracing mode
opcache.jit_buffer_size = 64M     ; Buffer do JIT
```

**Conceito:** Just-In-Time compilation — compilação de PHP para bytecode nativo em tempo de execução.

---

## 8. Frontend Architecture

### 8.1 Dual State Management

```
Pinia Stores (Global State)          TanStack Query (Server State)
       ↓                                       ↓
  auth store                            analytics query
  sessions store                        sessions query
  analytics store                       technologies query
  technologies store                            ↓
       ↑                              Watcher sincroniza
       └──────────────────────────────→ para o store
```

**Conceito:** Separation of client state (UI, auth) from server state (cached API data).

### 8.2 shallowRef para Performance

```typescript
const sessions = shallowRef<StudySession[]>([])

// shallowRef evita deep reactivity:
// - Não observa propriedades internas dos objetos
// - Mais performático para arrays grandes
// - Re-render apenas quando a referência muda
```

**Conceito:** Lazy reactivity — limitar a granularidade da reatividade para melhorar performance.

### 8.3 Composition API (100% do Projeto)

```vue
<script setup lang="ts">
const props = defineProps<{ technologyId: string }>()
const store = useTechnologiesStore()
// Tudo é declarativo, sem Options API
</script>
```

**Conceito:** Composition API — agrupar lógica relacionada (ao invés de separar por option type: data, methods, computed).

### 8.4 WebSocket Composable (Progressive Enhancement)

```
1. Probe Reverb (WebSocket connection test)
2. Se disponível → Dynamic import de laravel-echo + pusher-js
3. Conectar ao canal privado dashboard.{userId}
4. Se WebSocket falhar → Fallback para polling (120s)
5. Reconexão: exponential backoff (1s → 60s cap, max 10 tentativas)
```

**Conceito:** Progressive enhancement — funcionalidade avançada (real-time) com fallback para funcionalidade básica (polling).

### 8.5 Zod Schema Validation (Runtime)

```typescript
const dashboardDataSchema = z.object({
  user_metrics: userMetricsSchema,
  technology_metrics: z.array(technologyMetricSchema),
  time_series_30d: z.array(dailyMinuteSchema),
})

// Parse com runtime validation:
export function parseDashboardResponse(data: unknown) {
  return dashboardDataSchema.parse(data)
}
```

**Conceito:** Runtime type validation — validar dados da API em tempo de execução, não apenas em compile time.

### 8.6 Custom CSS Design System (Sem Tailwind)

```css
:root {
  --spacing-2xs: 2px;
  --spacing-xs: 4px;
  /* ... 200+ design tokens */
  
  --color-primary-500: #3b82f6;
  --color-success-500: #22c55e;
  /* ... */
}

[data-theme='dark'] {
  --color-primary-500: #60a5fa;
  /* ... */
}
```

**Conceito:** Design tokens — valores centralizados que garantem consistência visual em toda a aplicação.

---

## 9. Infraestrutura & DevOps

### 9.1 Docker Compose (8+ serviços)

```
nginx (OpenResty) → php-fpm → postgres
                ↘ reverb (WebSocket)
                ↘ horizon (Queue worker)
                ↘ scheduler (Cron)
node (Vite dev) ↗
redis ↗
```

### 9.2 Multi-Stage Docker Build

```dockerfile
# Stage 1: Builder
FROM composer:2 AS builder
RUN composer install --no-dev --no-scripts --prefer-dist

# Stage 2: Runtime
FROM php:8.4-fpm-alpine
COPY --from=builder /app/vendor /app/vendor
USER www-data  # Non-root
```

**Conceito:** Multi-stage build — separar dependências de build do runtime para reduzir tamanho da imagem.

### 9.3 Health Checks

```yaml
healthcheck:
  test: ["CMD", "pg_isready", "-U", "studytrack"]
  interval: 5s
  timeout: 5s
  retries: 5
  start_period: 10s
```

**Conceito:** Container health checks — monitoramento contínuo da saúde dos serviços para orquestração.

### 9.4 Log Rotation

```yaml
logging:
  driver: "json-file"
  options:
    max-size: "10m"
    max-file: "3"
```

**Conceito:** Log rotation — prevenir filling do disco com logs antigos.

### 9.5 CI/CD Pipeline

```
Push to main/develop →
  ├→ Backend CI (tests + pint + phpstan + gitleaks)
  ├→ Frontend CI (type-check + vitest + eslint + prettier + build + playwright)
  └→ Deploy (build docker + trivy scan + push to registry)
```

### 9.6 Backup Strategy

```bash
# pg_dump com compressão gzip, retenção de 7 dias
pg_dump | gzip > backup_$(date +%Y%m%d_%H%M%S).sql.gz
find /backups -name "*.sql.gz" -mtime +7 -delete
```

---

## 10. Testes & Qualidade

### 10.1 Test Pyramid

```
         ╱╲
        ╱  ╲        E2E (Playwright): 5 specs
       ╱    ╲       → Flows de usuário completos
      ╱──────╲
     ╱        ╲     Integration: ~15 testes
    ╱          ╲    → API + Cache + Jobs + Events + Lua
   ╱────────────╲
  ╱              ╲  Unit: ~65 testes
 ╱                ╲ → Services, Jobs, Listeners, Middleware, DTOs
╱──────────────────╲
```

### 10.2 Cobertura

- **Backend:** ~85 test files (Feature + Unit + Integration)
- **Frontend:** ~43 test files (Unit) + 5 E2E specs
- **Segurança:** 16+ testes de segurança (SQL injection, XSS, CSRF, IDOR, etc.)

### 10.3 Quality Pipeline (7 checks)

```
1. Evolution   → Deps atualizadas, AI-powered refactoring
2. Security    → Tests, audits, secret scanning
3. Integrity   → Git state, migrations, Docker services
4. Tests       → PHPUnit + Vitest + coverage
5. Performance → PHPStan, health checks, build time
6. Sentry      → Error monitoring
7. Design      → Pint, vue-tsc, ESLint, Prettier
```

---

## Resumo de Conceitos Aplicados

| Categoria | Conceitos |
|-----------|----------|
| **Algoritmos** | Piecewise scoring, sliding window, exponential backoff, LRU cache, CTE + window functions, consecutive subsequence detection |
| **Estruturas de Dados** | JSONB, UUID, Sorted Set (Redis), Hash (Redis), Array (PostgreSQL), GENERATED STORED columns |
| **Design Patterns** | Repository, DTO, Observer/Event, Strategy, Singleton, Adapter, Middleware Pipeline, Optimistic Update, Facade |
| **Arquitetura** | Modular architecture, CQRS-lite, Event-driven, Feature-based frontend, Defense in depth |
| **Segurança** | OWASP Top 10 coverage, sandboxing (Docker + Web Worker), encrypted tokens, WAF (Lua), sliding window rate limiting |
| **Performance** | Code splitting, lazy loading, prefetching, shallowRef, OPcache + JIT, cache tags, BRIN indexes |
| **DevOps** | Multi-stage builds, health checks, CI/CD, backup strategy, log rotation, Dependabot |
| **Testes** | Test pyramid, contract tests, security tests, Lua integration tests, E2E with Playwright |
