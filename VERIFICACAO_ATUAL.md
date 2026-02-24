# Verificação Atual — StudyTrack Pro

*Data: 2025-02-23 — Reverificação após implementações*

---

## Resumo executivo

| Área | Implementado | Parcial | Pendente |
|------|--------------|---------|----------|
| Segurança | 4 | 0 | 0 |
| Infraestrutura | 8 | 1 | 2 |
| API e Backend | 12 | 1 | 2 |
| WebSocket | 6 | 1 | 1 |
| Frontend | 10 | 2 | 3 |
| Testes | 2 | 1 | 6 |
| CI/CD | 4 | 1 | 2 |
| Documentação | 2 | 2 | 4 |

---

## 1. SEGURANÇA — ✅ IMPLEMENTADO

| Item | Status | Evidência |
|------|--------|-----------|
| change-password revoga TODOS os tokens | ✅ | `AuthService::changePassword` chama `$user->tokens()->delete()` |
| 403 (não 404) em acesso cross-user | ✅ | `StudySessionService::findForUser` → `abort(403)`; `TechnologyRepository::findForUser` → `AuthorizationException` |
| 401 sem user enumeration | ✅ | "Credenciais inválidas" em login |
| auth:sanctum em recursos | ✅ | Grupo protegido em api.php |

---

## 2. INFRAESTRUTURA DOCKER

| Item | Status | Evidência |
|------|--------|-----------|
| 7 serviços no docker-compose | ✅ | nginx, php-fpm, reverb, horizon, scheduler, postgres, redis |
| .env.example completo (raiz) | ✅ | CACHE_DATABASE, REDIS_QUEUE/SESSION_DATABASE, VITE_* |
| .env.example completo (backend) | ✅ | + REDIS_HORIZON_DATABASE=4, REVERB_HOST/PORT/SCHEME |
| .gitignore correto | ✅ | Sem prefixo projeto/; .env, vendor, node_modules, storage |
| Nginx gzip | ✅ | gzip on, gzip_types, gzip_comp_level 6 |
| Nginx cache de assets | ✅ | location ~* \.(js|css|...), expires 1y, Cache-Control |
| Volumes persistentes | ✅ | postgres_data, redis_data |
| Health checks | ✅ | pg_isready, redis-cli ping |
| Dockerfiles multi-stage | ❌ | PHP e Node ainda em single-stage |
| Redis multi-db | ⚠️ | backend/.env tem REDIS_HORIZON_DATABASE; config/database.php pode não ter conexão horizon explícita |

---

## 3. RATE LIMITING E API

| Item | Status | Evidência |
|------|--------|-----------|
| throttle auth (5/min) | ✅ | Route::middleware('throttle:5,1') em register e login |
| throttle reads (60/min) | ✅ | Grupo GET com throttle:60,1 |
| throttle writes (30/min) | ✅ | Grupo POST/PUT/PATCH/DELETE com throttle:30,1 |
| EnsureJsonResponse global | ✅ | bootstrap/app.php api prepend |
| GET /health completo | ✅ | database, redis, queue, reverb (fsockopen) |
| Broadcast::routes | ✅ | api.php com Broadcast::routes |

---

## 4. WEBSOCKET (REVERB)

| Item | Status | Evidência |
|------|--------|-----------|
| MetricsRecalculated ShouldBroadcast | ✅ | broadcastOn, broadcastAs, broadcastWith |
| MetricsRecalculating ShouldBroadcast | ✅ | Idem |
| Sequência recalculating → job → updated | ✅ | RecalculateMetricsJob dispara MetricsRecalculating no início |
| Canal dashboard.{userId} | ✅ | channels.php |
| Laravel Echo + pusher-js | ✅ | package.json |
| useWebSocket com Echo real | ✅ | connect, disconnect, listen .metrics.updated, .metrics.recalculating |
| RealtimeBadge | ✅ | Verde/vermelho, AppLayout |
| AppLayout chama connect/disconnect | ✅ | onMounted/onUnmounted, handleLogout |
| broadcastAs com ponto | ⚠️ | Atualmente retorna `metrics.updated`; checklist pede `.metrics.updated`. Laravel Echo escuta com `.metrics.updated` — funciona. |
| Polling de fallback | ✅ | useDashboard: startPolling quando desconectado > 5s |

---

## 5. FRONTEND VUE

| Item | Status | Evidência |
|------|--------|-----------|
| analytics.store: isFresh, fetchDashboard(force) | ✅ | lastFetchAt, TTL 5min, force bypass |
| analytics.store: setRecalculating | ✅ | Integrado ao WebSocket |
| useDashboard composable | ✅ | visibilitychange, polling, fetchDashboard |
| useWebSocket disconnect no unmount | ✅ | AppLayout onUnmounted |
| technologies.store searchLocal | ✅ | Filtra por name/slug |
| Skeleton loaders | ✅ | DashboardView com SkeletonLoader em KPIs |
| ErrorCard com "Tentar novamente" | ✅ | :on-retry="retry" |
| BaseToast | ✅ | App.vue |
| TechDistributionWidget | ✅ | DashboardView |
| HeatmapWidget | ✅ | DashboardView |
| Layout responsivo (4→2→1) | ✅ | media queries 640px, 1024px |
| useDashboard init fetch | ⚠️ | View chama fetchDashboard em onMounted; useDashboard não faz init automático — OK |

---

## 6. BANCO E SEEDERS

| Item | Status | Evidência |
|------|--------|-----------|
| DemoDataSeeder 6 meses | ✅ | Carbon::now()->subMonths(6), iteração por dia |
| Usuário demo@studytrack.local | ✅ | firstOrCreate |
| duration_min GENERATED | ✅ | migration |
| Triggers concurrent_sessions, validate_ended_at | ✅ | migration 000005 |

**Obs:** DemoDataSeeder usa `focus_score: rand(70, 100)` — a validação da API espera 1–10. Coluna aceita; para consistência semântica, considerar alterar para rand(1, 10).

---

## 7. TESTES

| Item | Status | Evidência |
|------|--------|-----------|
| AuthorizationTest (403 cross-user) | ✅ | test_user_b_cannot_access_user_a_session, test_user_b_cannot_update_user_a_session |
| Event::fake, Queue::fake | ✅ | setUp em AuthorizationTest |
| phpunit.xml DB_CONNECTION=pgsql | ✅ | studytrack_test |
| Teste sessão concorrente (409) | ❌ | Ausente |
| Teste de cache invalidation | ❌ | Ausente |
| Unit UserMetricsAggregator | ❌ | Ausente |
| Unit RecalculateMetricsJob | ❌ | Ausente |
| Vitest analytics.store | ❌ | Nenhum *.spec.ts encontrado |
| Vitest useSessionTimer | ❌ | Ausente |
| TechnologyCrudTest | ❌ | Ausente |

---

## 8. CI/CD

| Item | Status | Evidência |
|------|--------|-----------|
| Backend: PHPUnit | ✅ | php artisan test |
| Backend: Pint | ✅ | ./vendor/bin/pint --test |
| Backend: PHPStan | ⚠️ | `|| true` — não falha o pipeline se ausente |
| Frontend: type-check, build | ✅ | npm run type-check, npm run build |
| Frontend: Vitest | ⚠️ | npm run test:run \|\| true |
| Frontend: ESLint | ⚠️ | npm run lint \|\| true |
| Postgres service no CI | ✅ | postgres:16-alpine |
| Migrations no CI | ✅ | php artisan migrate --force |

**Recomendação:** Remover `|| true` de PHPStan, Vitest e ESLint para que falhas reais quebrem o pipeline.

---

## 9. DOCUMENTAÇÃO E PORTFÓLIO

| Item | Status | Evidência |
|------|--------|-----------|
| README com setup | ✅ | make dev, cp .env |
| Badges CI | ❌ | Ausente |
| Link demo | ❌ | Ausente |
| Seção Arquitetura (diagrama) | ❌ | Apenas estrutura de pastas |
| Tabela Stack com motivação | ⚠️ | Lista presente, sem tabela |
| Postman Collection | ❌ | Ausente |
| Deploy público HTTPS | ❌ | Não feito |

---

## 10. OUTROS

| Item | Status | Evidência |
|------|--------|-----------|
| Cache::lock em getDashboardData | ❌ | AnalyticsService não usa lock |
| ConcurrentSessionException | ❌ | Concorrência tratada no controller com mensagem; sem exceção de domínio |
| Rate limiting driver Redis | ⚠️ | ThrottleRequests usa cache; configurar para Redis em produção |

---

## Pendências prioritárias

### Alta prioridade
1. **Remover `|| true` no CI** — PHPStan, Vitest, ESLint devem falhar o pipeline em caso de erro.
2. **Teste sessão concorrente** — Garantir que 2ª sessão ativa retorne 409 ou 422 com código CONCURRENT_SESSION.
3. **DemoDataSeeder focus_score** — Ajustar para 1–10 se a API valida esse intervalo.

### Média prioridade
4. **Unit tests** — UserMetricsAggregator, RecalculateMetricsJob.
5. **Vitest** — analytics.store, useSessionTimer (criar specs).
6. **Cache::lock** em getDashboardData (evitar cache stampede).

### Baixa prioridade
7. **README** — Badges, link demo, diagrama de arquitetura.
8. **Postman Collection** — Exportar e documentar endpoints.
9. **Dockerfiles multi-stage** — Otimização de imagens.
10. **ConcurrentSessionException** — Exceção de domínio para sessão concorrente.

---

## Comandos de verificação rápida

```bash
# Backend
cd backend && php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=512M

# Frontend
cd frontend && npm run type-check
npm run test:run
npm run lint
npm run build
```
