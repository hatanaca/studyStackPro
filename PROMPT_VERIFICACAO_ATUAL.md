# Prompt para Implementar Pendências — VERIFICACAO_ATUAL.md

Use este prompt para implementar as pendências identificadas na verificação atual do StudyTrack Pro. Execute por blocos na ordem indicada. Após cada bloco, rode os comandos de verificação e valide antes de prosseguir.

**Contexto:** Backend em `backend/` (Laravel 11), frontend em `frontend/` (Vue 3 + TypeScript). O que já está implementado (segurança, rate limiting, WebSocket, etc.) NÃO deve ser alterado — apenas adicionar o que falta.

---

## BLOCO 1 — CI/CD (Alta prioridade)

### 1.1 Remover `|| true` nos workflows
- **Arquivo:** `.github/workflows/backend-ci.yml`
- **Ação:** Na linha do PHPStan, remover `|| true`. O step deve falhar se `phpstan analyse` retornar erro.
- **Arquivo:** `.github/workflows/frontend-ci.yml`
- **Ação:** Nos steps de Vitest e ESLint, remover `|| true`. O pipeline deve falhar se `npm run test:run` ou `npm run lint` retornarem erro.
- **Critério:** `git push` quebra o CI se testes ou lint falharem.

### 1.2 Garantir PHPStan instalado
- **Arquivo:** `backend/composer.json`
- **Ação:** Se `larastan` ou `phpstan/phpstan` não estiver em devDependencies, adicionar: `"larastan/larastan": "^2.0"` (ou equivalente compatível com Laravel 11).
- **Critério:** `composer install` e `./vendor/bin/phpstan analyse` funcionam.

### 1.3 Garantir ESLint e Vitest configurados
- **Arquivo:** `frontend/package.json`
- **Ação:** Verificar que `npm run lint` e `npm run test:run` existem e funcionam. Se ESLint não tiver config, criar `eslint.config.js` ou `.eslintrc` básico.
- **Critério:** `npm run lint` e `npm run test:run` executam sem erro de comando (podem falhar por código, mas não por "command not found").

---

## BLOCO 2 — TESTE SESSÃO CONCORRENTE (Alta prioridade)

### 2.1 Teste de sessão concorrente
- **Arquivo:** `backend/tests/Feature/StudySessionConcurrentTest.php` (criar)
- **Ação:**
  - Usar `Event::fake()` e `Queue::fake()` no setUp.
  - Criar usuário e iniciar sessão (POST /api/v1/study-sessions/start).
  - Tentar iniciar segunda sessão sem finalizar a primeira.
  - Assert: status 409 ou 422; JSON com `error.code` = `CONCURRENT_SESSION` ou `VALIDATION_ERROR` (se o controller retornar 422 por validação antes do trigger).
  - **Nota:** O trigger do banco lança exceção; o Handler deve retornar envelope JSON com código semântico. Se não houver handler para a exceção SQL, criar middleware ou tratar no repositório para converter em 409 CONCURRENT_SESSION.
- **Critério:** `php artisan test --filter=StudySessionConcurrentTest` passa.

### 2.2 Handler para exceção de sessão concorrente (se necessário)
- **Arquivos:** `backend/app/Exceptions/Handler.php`, repositório ou migration
- **Ação:** Se o trigger do PostgreSQL levantar exceção ao inserir 2ª sessão ativa, capturar no Handler ou no repositório e retornar `response()->json(['success' => false, 'error' => ['code' => 'CONCURRENT_SESSION', 'message' => '...']], 409)`.
- **Critério:** Resposta 409 com código CONCURRENT_SESSION quando usuário tenta 2ª sessão ativa.

---

## BLOCO 3 — DEMODATASEEDER FOCUS_SCORE (Alta prioridade)

### 3.1 Corrigir focus_score no DemoDataSeeder
- **Arquivo:** `backend/database/seeders/DemoDataSeeder.php`
- **Ação:** Alterar `'focus_score' => rand(70, 100)` para `'focus_score' => rand(1, 10)` (ou null com probabilidade), para alinhar com a validação da API (min:1, max:10).
- **Critério:** `php artisan migrate:fresh --seed` roda sem erro; sessões seedadas têm focus_score entre 1 e 10 ou null.

---

## BLOCO 4 — UNIT TESTS BACKEND (Média prioridade)

### 4.1 Unit test UserMetricsAggregator
- **Arquivo:** `backend/tests/Unit/MetricsAggregatorTest.php` (criar)
- **Ação:**
  - Usar RefreshDatabase e factories.
  - Casos: streak zero (sem sessões), streak N dias (sessões consecutivas), gap quebra streak, personal best preservado após gap.
  - Mock do repositório ou usar banco real conforme estrutura do MetricsAggregator.
- **Critério:** `php artisan test --filter=MetricsAggregatorTest` passa.

### 4.2 Unit test RecalculateMetricsJob
- **Arquivo:** `backend/tests/Unit/RecalculateMetricsJobTest.php` (criar)
- **Ação:**
  - Mock MetricsAggregator e AnalyticsService.
  - Verificar que `recalculateUserMetrics`, `recalculateTechnologyMetrics`, `recalculateDailyMinutes` são chamados.
  - Verificar que evento `MetricsRecalculated` é disparado após o job.
  - Verificar que `failed()` chama `Log::error` com userId, attempt, error (usar Log::spy ou similar).
- **Critério:** `php artisan test --filter=RecalculateMetricsJobTest` passa.

---

## BLOCO 5 — VITEST FRONTEND (Média prioridade)

### 5.1 Vitest analytics.store
- **Arquivo:** `frontend/src/stores/__tests__/analytics.store.spec.ts` (criar)
- **Ação:**
  - Mock apiClient.get.
  - Testar: fetchDashboard preenche dashboard e lastFetchAt; isFresh retorna true dentro do TTL e false fora; updateFromWebSocket substitui dashboard; setRecalculating altera isRecalculating.
- **Critério:** `npm run test:run` executa o spec e passa.

### 5.2 Vitest useSessionTimer
- **Arquivo:** `frontend/src/composables/__tests__/useSessionTimer.spec.ts` (criar)
- **Ação:**
  - Testar: init com elapsed_seconds do servidor; incremento do timer a cada segundo (ou conforme implementação); cleanup no unmount (clearInterval/clearTimeout).
- **Critério:** `npm run test:run` executa o spec e passa.

---

## BLOCO 6 — CACHE LOCK (Média prioridade)

### 6.1 Cache::lock em getDashboardData
- **Arquivo:** `backend/app/Modules/Analytics/Services/AnalyticsService.php`
- **Ação:** Em `getDashboardData`, envolver a lógica de `Cache::tags()->remember` em um lock:
  ```php
  return Cache::lock('dashboard:lock:' . $userId, 10)->get(function () use ($userId) {
      return Cache::tags(['analytics', "user:{$userId}"])->remember(
          "dashboard:{$userId}",
          now()->addMinutes(5),
          fn () => $this->buildDashboardData($userId)
      );
  });
  ```
  - Ajustar conforme API do Laravel (Cache::lock pode ter assinatura diferente).
- **Critério:** Sob múltiplos requests simultâneos para o mesmo usuário, apenas um reconstrói o cache; os demais aguardam o lock.

---

## BLOCO 7 — README E DOCUMENTAÇÃO (Baixa prioridade)

### 7.1 Badges CI no README
- **Arquivo:** `README.md`
- **Ação:** Adicionar no topo, após o título:
  ```markdown
  [![Backend CI](https://github.com/USER/REPO/actions/workflows/backend-ci.yml/badge.svg)](https://github.com/USER/REPO/actions/workflows/backend-ci.yml)
  [![Frontend CI](https://github.com/USER/REPO/actions/workflows/frontend-ci.yml/badge.svg)](https://github.com/USER/REPO/actions/workflows/frontend-ci.yml)
  ```
  - Substituir USER/REPO pelo owner e nome do repositório no GitHub.
- **Critério:** README exibe badges que refletem o status dos workflows.

### 7.2 Tabela Stack com motivação
- **Arquivo:** `README.md`
- **Ação:** Substituir ou complementar a lista "Stack" por tabela:
  | Tecnologia | Motivação |
  |------------|-----------|
  | Vue 3 + TypeScript | ... |
  | Laravel 11 | ... |
  | PostgreSQL | ... |
  | Redis | ... |
  | etc. |
- **Critério:** Cada tecnologia tem uma breve justificativa.

### 7.3 Seção Arquitetura
- **Arquivo:** `README.md`
- **Ação:** Adicionar seção "Arquitetura" com diagrama (Mermaid ou imagem). Exemplo Mermaid:
  ```mermaid
  flowchart LR
    Frontend[Vue SPA] --> API[Laravel API]
    API --> DB[(PostgreSQL)]
    API --> Redis[(Redis)]
    API --> Reverb[WebSocket]
    Horizon[Horizon] --> Redis
  ```
  - Ajustar conforme componentes reais.
- **Critério:** Diagrama representa fluxo de dados entre frontend, API, banco, Redis, Reverb, Horizon.

### 7.4 Link de demo (placeholder)
- **Arquivo:** `README.md`
- **Ação:** Adicionar "**Demo:** [em breve]" ou link real se deploy existir.
- **Critério:** Seção demo presente.

---

## BLOCO 8 — POSTMAN COLLECTION (Baixa prioridade)

### 8.1 Exportar Postman Collection
- **Ação:** Criar `docs/StudyTrackPro.postman_collection.json` (ou pasta equivalente) com os endpoints documentados:
  - Auth: register, login, logout, me, update profile, change-password, tokens, revoke tokens
  - Technologies: index, search, store, show, update, destroy
  - Study Sessions: index, active, start, end, store, show, update, destroy
  - Analytics: dashboard, user-metrics, tech-stats, time-series, weekly, heatmap, recalculate
  - Health: GET /health
- **Ação:** Cada request com variável `{{baseUrl}}`, `{{token}}`; documentar parâmetros e exemplos.
- **Critério:** Importar no Postman e executar requests de exemplo com sucesso.

---

## BLOCO 9 — OPCIONAIS (Baixa prioridade)

### 9.1 ConcurrentSessionException
- **Arquivo:** `backend/app/Exceptions/ConcurrentSessionException.php` (criar)
- **Ação:** Exceção com código `CONCURRENT_SESSION`, message apropriada. Handler.php tratar e retornar 409.
- **Arquivo:** Onde a concorrência é detectada (controller ou repositório), lançar `ConcurrentSessionException` em vez de retornar resposta diretamente.
- **Critério:** Código semântico; lógica de erro centralizada no Handler.

### 9.2 TechnologyCrudTest
- **Arquivo:** `backend/tests/Feature/Technologies/TechnologyCrudTest.php` (criar)
- **Ação:** Testes para index, store, show, update, destroy com usuário autenticado. Teste cross-user (403).
- **Critério:** `php artisan test --filter=TechnologyCrudTest` passa.

### 9.3 Teste de cache invalidation
- **Arquivo:** `backend/tests/Feature/Analytics/AnalyticsCacheTest.php` (criar)
- **Ação:** Criar sessão → verificar que cache analytics do usuário foi invalidado (Cache::tags()->get retorna null ou novo request não usa cache antigo).
- **Critério:** Teste documenta comportamento de invalidação.

### 9.4 Rate limiting com Redis
- **Arquivo:** `backend/config/app.php` ou `config/cache.php`
- **Ação:** Garantir que em produção o throttle use Redis. Por padrão, ThrottleRequests usa o cache. Se CACHE_STORE=redis, já está correto. Documentar na .env.example.
- **Critério:** Em produção com Redis, rate limit é compartilhado entre workers.

### 9.5 Dockerfiles multi-stage
- **Arquivos:** `docker/php/Dockerfile`, `docker/node/Dockerfile.frontend`
- **Ação:** Stage 1: composer install / npm run build. Stage 2: copiar apenas artefatos para imagem final. Reduzir tamanho da imagem.
- **Critério:** Imagens menores; funcionalidade preservada.

---

## Ordem de execução recomendada

1. **Bloco 1** — CI (remover || true) — garante que próximas alterações quebrem o pipeline se falharem
2. **Bloco 2** — Teste sessão concorrente
3. **Bloco 3** — DemoDataSeeder focus_score
4. **Bloco 4** — Unit tests backend
5. **Bloco 5** — Vitest frontend
6. **Bloco 6** — Cache::lock
7. **Blocos 7 e 8** — Documentação e Postman
8. **Bloco 9** — Opcionais conforme tempo

---

## Comandos de verificação após cada bloco

```bash
# Backend
cd backend
composer install   # se alterou composer.json
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=512M

# Frontend
cd frontend
npm ci            # se alterou package.json
npm run type-check
npm run test:run
npm run lint
npm run build
```

---

*Prompt baseado em VERIFICACAO_ATUAL.md — implementar apenas os itens pendentes.*
