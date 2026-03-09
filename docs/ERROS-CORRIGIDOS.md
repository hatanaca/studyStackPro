# Registro de erros corrigidos — StudyTrackPro

Este documento registra erros identificados e corrigidos no projeto, com breve explicação da causa e da solução. Use-o como histórico e referência para evitar regressões.

---

## Como registrar um novo erro

Para cada correção, adicione uma entrada na seção **Registro de correções** com:

- **Data** (opcional): quando foi corrigido
- **Descrição**: o que estava errado (sintoma)
- **Causa**: por que acontecia
- **Correção**: o que foi alterado (arquivos e mudanças)
- **Como evitar**: dica para não repetir o problema

---

## Registro de correções

### 1. APP_KEY vazio no primeiro setup (documentação)

| Campo | Detalhe |
|-------|---------|
| **Descrição** | Quem copia apenas o `.env.example` para `.env` no backend fica com `APP_KEY=` vazio. O Laravel exige uma chave de aplicação; sem ela, a aplicação pode falhar em runtime (sessão, criptografia, etc.). |
| **Causa** | O `.env.example` não deixava explícito que é obrigatório gerar a chave após copiar o arquivo. |
| **Correção** | Foi adicionado no `backend/.env.example` um comentário na linha do `APP_KEY` informando: *"Gere uma chave após copiar para .env: php artisan key:generate"*. Assim, o desenvolvedor sabe o passo seguinte. |
| **Arquivo(s)** | `backend/.env.example` |
| **Como evitar** | No primeiro setup, após `cp .env.example .env`, rodar `php artisan key:generate`. Em fluxos automatizados (ex.: `make setup`), incluir esse comando. |

---

### 2. Testes do backend falham com "Connection refused" ao rodar fora do Docker

| Campo | Detalhe |
|-------|---------|
| **Descrição** | Ao executar `php artisan test` diretamente na máquina (fora do Docker), todos os testes Feature e vários Unit falham com `SQLSTATE[08006] connection to server at "127.0.0.1", port 5432 failed: Connection refused`. |
| **Causa** | O `phpunit.xml` define `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1` e `DB_DATABASE=studytrack_test`. Se o PostgreSQL não estiver rodando localmente (ou o banco de testes não existir), a conexão falha. O projeto usa recursos específicos do PostgreSQL (schema `analytics`, UUIDs), então não é viável trocar para SQLite nos testes. |
| **Correção** | Documentação: os testes do backend **exigem** PostgreSQL. A forma recomendada é rodar os testes **dentro do ambiente Docker**, onde o Postgres já está disponível: `make test-back` (que cria o banco `studytrack_test` e executa `php artisan test` no container). Se quiser rodar `php artisan test` no host, é necessário ter PostgreSQL em 127.0.0.1:5432 e criar o banco `studytrack_test` (ex.: `createdb studytrack_test`). |
| **Arquivo(s)** | `docs/ERROS-CORRIGIDOS.md`, `Makefile` (já contém `test-db-setup` e `test-back`). |
| **Como evitar** | Usar `make test` ou `make test-back` com o stack Docker levantado (`make dev`). Ou documentar no README/contributing que testes backend requerem Postgres. |

---

### 3. Aviso do Vue "onMounted/onUnmounted called when there is no active component instance" no teste useSessionTimer

| Campo | Detalhe |
|-------|---------|
| **Descrição** | No teste do composable `useSessionTimer`, ao chamar `useSessionTimer()` diretamente no arquivo de teste (fora de um componente), o Vue emite avisos: *"onMounted is called when there is no active component instance"* e o mesmo para `onUnmounted`. O composable usa esses lifecycle hooks internamente. |
| **Causa** | Lifecycle hooks do Vue só podem ser registrados durante o `setup()` de um componente. Chamar o composable no corpo do teste não fornece instância de componente, então o Vue avisa. |
| **Correção** | O teste foi alterado para montar um componente wrapper (`defineComponent` que usa `useSessionTimer()` no `setup()`). Assim, o composable roda no contexto de um componente e os hooks são associados corretamente. Foi adicionado também um mock padrão de `sessionsApi.getActive()` no `beforeEach` (resposta sem sessão ativa), para que o `onMounted` que chama `fetchActive()` não quebre ao destructuring quando o mock não estava definido no primeiro teste. |
| **Arquivo(s)** | `frontend/src/composables/__tests__/useSessionTimer.spec.ts` |
| **Como evitar** | Ao testar composables que usam `onMounted`, `onUnmounted` ou outros lifecycle hooks, rodar o composable dentro de um componente e usar `mount()` do Vue Test Utils; não chamar o composable diretamente no describe/it. |

---

### 4. SessionFilters quebrava se `modelValue` fosse undefined (uso sem v-model)

| Campo | Detalhe |
|-------|---------|
| **Descrição** | O componente `SessionFilters` acessava `props.modelValue.date_from`, `props.modelValue.date_to`, etc. sem tratar o caso em que o pai não passasse `modelValue` (ex.: `<SessionFilters />` sem `v-model`). Isso poderia causar erro em runtime ao acessar propriedade de `undefined`. |
| **Causa** | A prop `modelValue` era obrigatória no tipo, mas em Vue o pai pode não passar a prop; em runtime `modelValue` pode ser `undefined`. |
| **Correção** | A prop foi tornada opcional com `withDefaults(..., { modelValue: () => ({}) })` e todos os acessos passaram a usar optional chaining: `props.modelValue?.date_from ?? ''`, etc. O tipo do emit `update:modelValue` foi explicitado para o objeto de filtros. |
| **Arquivo(s)** | `frontend/src/features/sessions/components/SessionFilters.vue` |
| **Como evitar** | Em componentes que recebem `modelValue` para v-model, considerar valor padrão e optional chaining quando o componente puder ser usado sem binding. |

---

### 5. TechDistributionWidget: índice selecionado fora dos limites ao mudar métricas

| Campo | Detalhe |
|-------|---------|
| **Descrição** | Ao selecionar uma fatia do gráfico (pie/donut) e, em seguida, os dados das métricas mudarem (ex.: menos tecnologias), o índice guardado em `selectedSlice` podia ficar maior ou igual ao novo `slices.value.length`. Chamadas a `getAngles(selectedSlice)` e `slicePct(selectedSlice)` acessavam `slices.value[i]` e geravam erro (undefined). |
| **Causa** | Não havia verificação de que o índice selecionado ainda era válido após a lista de fatias ser recalculada. |
| **Correção** | Em `getAngles` e `slicePct` foi adicionada guarda para índice fora do intervalo (retorno seguro). Foi adicionado um `watch` em `slices.value.length` que zera `selectedSlice` e `phase` quando o índice fica fora dos limites. |
| **Arquivo(s)** | `frontend/src/features/dashboard/components/TechDistributionWidget.vue` |
| **Como evitar** | Ao armazenar índice em lista reativa, revalidar ou resetar quando a lista mudar (watch no length ou na própria lista). |

---

### 6. truncate(): slice com length negativo quando maxLength &lt; 3

| Campo | Detalhe |
|-------|---------|
| **Descrição** | A função `truncate(text, maxLength)` usava `text.slice(0, maxLength - 3) + '...'`. Para `maxLength` 2 ou 1, isso virava `slice(0, -1)` ou `slice(0, -2)`, truncando do fim em vez de respeitar o limite e podendo produzir resultado inesperado. |
| **Causa** | Uso direto de `maxLength - 3` sem garantir que o primeiro argumento de `slice` não fosse negativo. |
| **Correção** | Uso de `Math.max(0, maxLength - 3)` para o comprimento a manter antes das reticências. Testes unitários para `truncate` (incluindo `maxLength` 2 e 3) foram adicionados em `formatters.spec.ts`. |
| **Arquivo(s)** | `frontend/src/utils/formatters.ts`, `frontend/src/utils/__tests__/formatters.spec.ts` |
| **Como evitar** | Em funções de truncamento, garantir que índices/comprimentos nunca sejam negativos (ex.: `Math.max(0, n)`). |

---

## Análise estática realizada (sem erros críticos)

Na análise do repositório foram verificados:

- **Lint**: Nenhum erro de lint reportado em `backend/app` e `frontend/src`.
- **Imports**: Nenhum import quebrado encontrado (componentes, composables, tipos e utilitários existem e batem com os imports).
- **Contrato API**: Rotas em `backend/routes/api.php` (prefixo `v1`) e módulos em `frontend/src/api/` estão alinhados. Goals é apenas frontend (localStorage), conforme `docs/GOALS-FRONTEND-ONLY.md`.
- **Migrations**: Sintaxe e ordem das migrations (transactional e analytics) estão coerentes.
- **CORS**: `config/cors.php` trata `CORS_ALLOWED_ORIGINS` vazio usando `['*']` em desenvolvimento; em produção deve-se definir as origens (ver `docs/ENV-VARS.md`).
- **Autenticação**: Frontend usa Bearer token; `withCredentials: false` no cliente Axios e `supports_credentials => false` no CORS estão consistentes.

**Recomendação**: Executar os testes após alterações. No backend use `make test-back` (com Docker) ou garanta PostgreSQL + banco `studytrack_test` se rodar `php artisan test` no host. No frontend: `cd frontend && npm run test:run`. Rodar também `npm run type-check` no frontend antes de commitar.

---

## Como rodar os testes

| Ambiente | Comando | Observação |
|----------|---------|------------|
| Backend (recomendado) | `make test-back` | Requer Docker (`make dev`). Cria `studytrack_test` e roda PHPUnit no container. |
| Backend (no host) | `cd backend && php artisan test` | Requer PostgreSQL em 127.0.0.1:5432 e banco `studytrack_test`. |
| Frontend | `cd frontend && npm run test:run` | Vitest; não depende de serviços externos. |
| Tudo | `make test` | Backend (Docker) + frontend. |

---

## Resumo rápido

| # | Problema | Status |
|---|----------|--------|
| 1 | APP_KEY vazio no primeiro setup (falta de instrução) | Corrigido (documentação em `.env.example`) |
| 2 | Testes backend falham com Connection refused (PostgreSQL) | Documentado (usar `make test-back` ou ter Postgres local) |
| 3 | Aviso Vue lifecycle no teste useSessionTimer | Corrigido (wrapper component + mock padrão) |
| 4 | SessionFilters quebrava com modelValue undefined | Corrigido (prop opcional + optional chaining) |
| 5 | TechDistributionWidget índice fora dos limites | Corrigido (guarda em getAngles/slicePct + watch) |
| 6 | truncate() com maxLength &lt; 3 | Corrigido (Math.max(0, …) + testes) |

*Última atualização: busca de bugs de todos os tipos, correções em TechDistributionWidget, formatters e registro em março de 2025.*
