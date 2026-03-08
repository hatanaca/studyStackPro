# Prompt: Implementar melhorias no backend StudyTrackPro

Use este prompt quando quiser que o Composer (ou outro agente) **execute** melhorias no backend do StudyTrackPro. As tarefas estão priorizadas. Pode solicitar "implementar todas as de alta prioridade" ou "implementar apenas o item X".

**Contexto:** Laravel 11, PHP 8.2+, módulos em `app/Modules/` (Auth, StudySessions, Technologies, Analytics), rotas em `routes/api.php`, eventos/listeners/jobs para cache e Reverb. Regras do agente backend: `docs/prompt-agente-backend-studytrackpro.md`.

---

## Instrução para o agente

Você deve implementar as melhorias de backend listadas abaixo na ordem indicada (ou apenas as que o usuário solicitar). Para cada item:

1. **Não quebre** contratos existentes com o frontend (payloads de API e eventos WebSocket); se precisar alterar resposta ou evento, documentar e avisar impacto no frontend.
2. Manter **testes** existentes passando e adicionar testes para funcionalidades novas ou alteradas.
3. Seguir convenções do projeto: Services orquestram regras, Repositories acessam dados, Events/Listeners para efeitos colaterais (cache, broadcast, jobs).

---

## Melhorias de alta prioridade

### 1. API de Goals no backend (ou decisão explícita de não ter)

**Objetivo:** O frontend possui stores, views e rotas de Goals; atualmente não há API de Goals no backend. Ou implementar a API ou desativar/remover Goals no frontend e documentar a decisão.

**Opção A — Implementar API de Goals:**

- Criar migration(s) para metas (ex.: tabela `goals`: user_id, tipo [diário/semanal], valor alvo em minutos, período, etc.).
- Criar model `Goal` (ou equivalente), com scopes e relações.
- Criar módulo `app/Modules/Goals/`: Contract de Repository, EloquentGoalRepository, GoalService, DTOs.
- Registrar rotas em `api.php`: listar, criar, atualizar, excluir metas (autenticadas com `auth:sanctum`).
- Criar Controller (ex.: `GoalsController`), Form Request para validação.
- Emitir eventos se necessário (ex.: GoalCreated para analytics); integrar com cálculo de métricas se "meta do dia" for usado no dashboard.
- Adicionar testes Feature para CRUD de Goals e testes Unit para GoalService.
- Documentar no README ou em docs que Goals passou a ser persistido no backend.

**Opção B — Não implementar (Goals só frontend):**

- Documentar em README ou docs que Goals é apenas local (localStorage/store) e não há persistência no backend.
- Garantir que o frontend não chame endpoints inexistentes (remover ou mockar `goals.api.ts` se fizer chamadas reais).

**Entrega:** Ou API funcional + testes + doc, ou doc explícito + frontend ajustado para não depender de API de Goals.

---

### 2. Atualizar coleção Postman com endpoint de export de analytics

**Objetivo:** A documentação da API (Postman) deve incluir o endpoint real de exportação de analytics.

**Ação:**

- Localizar a coleção Postman em `docs/` (ex.: `StudyTrack_API_Collection.postman.json` ou similar).
- Adicionar request para `GET /api/v1/analytics/export` (ou o path correto) com parâmetros documentados: período (date_from, date_to), formato (csv, xlsx, etc.), se houver.
- Incluir variáveis de ambiente necessárias (token, base URL) e exemplo de resposta/headers.
- Se existir outro formato de documentação (OpenAPI, Markdown), atualizar também.

**Entrega:** Coleção Postman atualizada e commitada.

---

### 3. Testes Feature/Unit para o endpoint de export de analytics

**Objetivo:** O endpoint de export não deve ficar sem cobertura de testes.

**Ação:**

- Identificar o controller e o service que tratam do export (ex.: `AnalyticsController@export`, `AnalyticsService`).
- Criar ou completar testes Feature: chamada autenticada com parâmetros válidos; retorno do formato esperado (CSV/Excel); validação de parâmetros (datas inválidas, período máximo); usuário não autenticado retorna 401.
- Se houver lógica de geração de arquivo no Service, adicionar testes Unit para essa lógica.
- Garantir que os testes passem com `php artisan test` ou `make test`.

**Entrega:** Testes commitados e verificação de que `make test` (backend) passa.

---

### 4. Alinhar payloads dos eventos WebSocket com o frontend

**Objetivo:** Os eventos broadcast (SessionStarted, SessionEnded, MetricsUpdated, etc.) devem enviar exatamente a estrutura esperada pelo frontend (tipos em `websocket.types.ts`).

**Ação:**

- Listar eventos broadcast no backend (BroadcastSessionStarted, BroadcastSessionEnded, BroadcastMetricsUpdate, BroadcastMetricsRecalculating).
- Ver a estrutura atual de cada evento (propriedades da classe ou do payload).
- Comparar com o que o frontend espera (ex.: `technology.slug` vs `technology.id`); ajustar o backend para enviar os campos necessários (ex.: incluir `slug` no objeto technology do SessionStarted).
- Documentar em comentário no Event ou no README a estrutura de cada evento para referência futura.

**Entrega:** Eventos ajustados; testes que verificam o payload (opcional); doc ou comentário com a estrutura.

---

### 5. Unificar seeders de dados de demonstração

**Objetivo:** Evitar duplicidade (ex.: dois DemoDataSeeder ou entradas duplicadas) e ter um único ponto de entrada para dados de demo.

**Ação:**

- Identificar todos os seeders que criam dados de demonstração (ex.: DemoDataSeeder, GenericTwoMonthsDailyStudySeeder, StudySpreadsheetUserSeeder).
- Decidir um fluxo único: por exemplo, `DatabaseSeeder` chama `DemoDataSeeder`, que por sua vez chama os outros seeders necessários na ordem correta.
- Remover ou depreciar seeders duplicados; garantir que `php artisan db:seed` (ou `make fresh`) produza um estado consistente e sem duplicação de dados.
- Documentar no README como rodar apenas dados de demo, se aplicável.

**Entrega:** Seeders reorganizados; README atualizado; nenhum seeder duplicado ativo.

---

## Melhorias de média prioridade

### 6. Documentação OpenAPI/Swagger da API

**Objetivo:** Ter uma spec OpenAPI (Swagger) gerada ou mantida para a API, além da coleção Postman.

**Ação:**

- Avaliar uso de Scramble, L5-Swagger ou anotações em controllers/resources para gerar OpenAPI.
- Configurar geração da spec (por exemplo, rota `/api/documentation` ou arquivo `openapi.yaml`).
- Garantir que os principais endpoints (auth, technologies, sessions, analytics, export) estejam documentados com parâmetros e respostas.
- Opcional: usar a spec para gerar tipos TypeScript no frontend.

---

### 7. Rate limiting e throttles documentados

Documentar em README ou em docs quais rotas têm throttle (ex.: login 5 req/min, export 10 req/min), para que integradores e frontend saibam o comportamento esperado em 429.

---

### 8. Testes E2E ou de integração para fluxos críticos

Avaliar adoção de Laravel Dusk ou testes de integração que cubram: login → dashboard → iniciar sessão → receber evento WebSocket → encerrar sessão. Isso pode ser feito em conjunto com o frontend (Playwright/Cypress) ou apenas backend simulando cliente.

---

### 9. Variáveis de ambiente documentadas

**Objetivo:** Um único ponto de referência para variáveis do backend (e, se possível, do frontend).

**Ação:**

- Garantir que `.env.example` contenha todas as variáveis necessárias para rodar a API, Reverb, Horizon, Redis, PostgreSQL, etc., com descrição em comentário.
- Opcional: criar `docs/env.md` ou seção no README listando cada variável e seu propósito.

---

## Melhorias de baixa prioridade

### 10. PHPStan level 6 ou superior

Subir o level do PHPStan em `phpstan.neon` para 6 (ou mais) e corrigir os issues reportados, melhorando a segurança de tipos no backend.

### 11. Laravel Telescope em ambiente local

Configurar Telescope para rodar apenas quando `APP_ENV=local` (ou similar), para debug de requests, jobs e exceções sem impacto em produção.

### 12. Comando ou job para limpar dados antigos

Se houver necessidade de retenção de dados (ex.: sessões ou logs antigos), considerar comando Artisan ou job agendado para arquivar ou apagar dados conforme política definida.

---

## Checklist antes de dar por concluído

- [ ] `php artisan test` (ou `make test` no backend) passa.
- [ ] Nenhum contrato de API ou evento WebSocket quebrado sem documentar e (se possível) avisar no frontend.
- [ ] Migrations rodam em ordem sem erro (`php artisan migrate`).
- [ ] README ou docs atualizados quando a melhoria alterar comportamento ou setup.

---

## Como usar este prompt

- **Implementar tudo (alta):** "Implemente todas as melhorias de alta prioridade do documento docs/prompt-implementar-melhorias-backend.md."
- **Implementar um item:** "Implemente apenas o item 2 (Postman) do docs/prompt-implementar-melhorias-backend.md."
- **Implementar por tema:** "Implemente os itens relacionados a documentação (2, 6 e 9) do docs/prompt-implementar-melhorias-backend.md."

Inclua no contexto os arquivos relevantes (ex.: `routes/api.php`, `app/Events/`, `app/Http/Controllers/V1/AnalyticsController.php`, coleção Postman) para o agente ter referência imediata.
