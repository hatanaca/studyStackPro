# Debug de Arquitetura — StudyTrackPro

Guia de diagnóstico ponta a ponta: Frontend → Nginx → Laravel → PostgreSQL/Redis → Reverb → Frontend.

---

## Fluxo da Aplicação

```
Browser (Vue 3)
   │ HTTP  GET/POST /api/v1/*        (Bearer token)
   ▼
Nginx :80
   │ FastCGI /api/*                  → php-fpm:9000
   │ Proxy WS /app/*                 → reverb:8080
   │ Static  /*                      → /frontend/index.html
   ▼
php-fpm (Laravel 11)
   │ Middleware: EnsureJsonResponse, auth:sanctum, SetUserTimezone, LogApiRequests, throttle nomeados, throttle.sliding (sessões)
   │ FormRequest → Controller → Service → Repository → Eloquent → PostgreSQL
   │ Event → Listener → Job (fila: metrics) → Redis → Horizon → RecalculateMetricsJob
   │                   → ShouldBroadcast → Reverb
   ▼
Reverb :8080  →  canal privado: private-dashboard.{userId}
   ▼
Browser (Laravel Echo / Pusher-JS)
   └─ .metrics.updated, .metrics.recalculating, .session.started, .session.ended
```

---

## Prompt de Debug (use no Composer / IA)

> Cole o trecho abaixo como contexto inicial quando um bug cruzar camadas:

```
Contexto do projeto StudyTrackPro:
- Frontend: Vue 3 + TypeScript, Pinia, Laravel Echo (Reverb), Axios (Bearer token)
- Backend: Laravel 11, Sanctum 4, Reverb 1, Horizon 5, PostgreSQL 16, Redis 7
- Infra: Docker (nginx:80 → php-fpm:9000 / reverb:8080), Vite proxy em dev

Sintoma observado:
[DESCREVA AQUI: URL, método HTTP, resposta recebida, estado da store Pinia, evento WS esperado vs recebido]

Camadas suspeitas:
[ ] Redis (cache/filas/Reverb)
[ ] CORS / Auth (401 / preflight)
[ ] Laravel (500 / 422 / lógica de negócio)
[ ] WebSocket (canal não recebe evento)
[ ] Frontend (store desatualizada / race condition)

Solicito: rastrear a origem, listar camadas afetadas e propor correção em todas elas.
```

---

## Checklist de Diagnóstico por Camada

### 1. Infraestrutura / Docker

```bash
# Verificar containers em execução
docker compose ps

# Logs de cada serviço
docker compose logs nginx --tail=50
docker compose logs php-fpm --tail=50
docker compose logs reverb --tail=50
docker compose logs horizon --tail=50

# Testar conectividade Redis (com senha)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" ping
# Esperado: PONG

# Filas: o nome da chave no Redis depende de REDIS_PREFIX (ex.: studytrackpro_database_)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" KEYS "*queues*metrics*"
# Ou via Artisan (recomendado)
docker compose exec php-fpm php artisan queue:monitor metrics --max=1000
```

**Checklist:**
- [ ] Todos os containers `Up`
- [ ] Redis responde `PONG` com a senha do `redis.conf`
- [ ] `REDIS_PASSWORD` no `.env` igual ao `requirepass` em `docker/redis/redis.conf`
- [ ] Portas `80` e `5173` acessíveis no host

---

### 2. CORS

```bash
# Testar preflight para a API
curl -X OPTIONS http://localhost/api/v1/auth/login \
  -H "Origin: http://localhost:5173" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Authorization, Content-Type" \
  -v 2>&1 | grep -i "access-control"

# Esperado: Access-Control-Allow-Origin: http://localhost:5173
```

**Checklist:**
- [ ] `CORS_ALLOWED_ORIGINS=http://localhost:5173` no `.env`
- [ ] Resposta HTTP contém `Access-Control-Allow-Origin` com a origem correta
- [ ] `supports_credentials=false` em `config/cors.php` (Bearer token, não cookie)

---

### 3. Autenticação (Sanctum Bearer Token)

```bash
# Login — deve retornar { success, data: { user, token, token_type } }
curl -X POST http://localhost/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"user@example.com","password":"password"}' | jq .

# Registro — deve retornar { success, data: { user, token, token_type } } (igual ao login)
curl -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"name":"Test","email":"test@example.com","password":"password","password_confirmation":"password"}' | jq .

# Usar token retornado para acessar rota protegida
TOKEN="<cole o token aqui>"
curl -X GET http://localhost/api/v1/auth/me \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | jq .
```

**Checklist:**
- [ ] Login retorna `data.token` (não apenas no header)
- [ ] Register retorna `data.token` e `data.user` (não apenas `data` com o user)
- [ ] `/auth/me` retorna `200` com token válido; `401` sem token ou com token expirado
- [ ] `EnsureJsonResponse` middleware ativo: erros de auth retornam JSON, não HTML redirect

---

### 4. Endpoints da API

```bash
TOKEN="<seu token>"

# Health
curl http://localhost/api/health | jq .

# Tecnologias
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/technologies | jq .

# Sessão ativa
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/study-sessions/active | jq .

# Iniciar sessão
curl -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"technology_id":"<uuid>"}' \
  http://localhost/api/v1/study-sessions/start | jq .

# Dashboard analytics
curl -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/analytics/dashboard | jq .
```

**Checklist de respostas esperadas:**
- [ ] Todos retornam `{ success: true, data: {...} }` em `200`
- [ ] Erros de validação retornam `422` com `{ success: false, error: { code, message, details } }`
- [ ] Rate limit retorna `429` (não `500`)
- [ ] Rotas sem auth retornam `401` JSON (não redirect HTML)

---

### 5. Filas / Horizon

```bash
# Status do Horizon
docker compose exec php-fpm php artisan horizon:status

# Jobs com falha
docker compose exec php-fpm php artisan queue:failed

# Reprocessar falhas
docker compose exec php-fpm php artisan queue:retry all

# Monitorar jobs em tempo real
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" MONITOR
# Filtrar: ^"LPUSH\|RPOP"
```

**Checklist:**
- [ ] Horizon `running` (não `paused` ou `inactive`)
- [ ] Fila `metrics` sem jobs acumulados (após encerrar sessão, o job deve processar em ~2s)
- [ ] Nenhum job em `queue:failed`
- [ ] `RecalculateMetricsJob` finaliza e dispara `MetricsRecalculated` event

---

### 6. Cache Redis (tags por usuário)

```bash
# Listar chaves de cache (prefixo vem de REDIS_PREFIX no .env)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" \
  KEYS "*analytics*"

# Verificar TTL de uma chave (substitua pelo nome real retornado em KEYS)
docker compose exec redis redis-cli -a "$REDIS_PASSWORD" \
  TTL "<chave>"

# Limpar cache manualmente (em dev)
docker compose exec php-fpm php artisan cache:clear
```

**Checklist:**
- [ ] Cache invalida após criar/atualizar/deletar sessão (`InvalidateSessionCache` listener)
- [ ] Tags `['analytics', 'user:{userId}']` são limpas antes do recálculo
- [ ] Cache é repopulado por `UpdateCacheWithFreshData` após `MetricsRecalculated`

---

### 7. WebSocket (Reverb)

```bash
# Verificar que Reverb está rodando
docker compose logs reverb --tail=20

# Testar autenticação do canal privado
TOKEN="<seu token>"
USER_ID="<uuid do usuário>"
curl -X POST http://localhost/api/broadcasting/auth \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d "{\"socket_id\":\"123.456\",\"channel_name\":\"private-dashboard.${USER_ID}\"}" | jq .
# Esperado: { "auth": "local-key:...<assinatura>" }
```

**No DevTools do browser (aba Network > WS):**
1. Filtrar por `app/` — deve aparecer uma conexão WebSocket ativa
2. Na aba Messages, observar frames bidirecionais
3. Após criar/encerrar sessão, verificar se chega frame com evento `.metrics.updated`

**Checklist:**
- [ ] Conexão WebSocket estabelecida (status `101 Switching Protocols`)
- [ ] Canal `private-dashboard.{userId}` autenticado com sucesso
- [ ] `VITE_REVERB_APP_KEY` igual a `REVERB_APP_KEY` no backend
- [ ] Eventos escutados com ponto inicial (`.session.started`, `.metrics.updated`) — o ponto omite o namespace do app no Laravel Echo
- [ ] `forceTLS: false` em HTTP / `forceTLS: true` em HTTPS
- [ ] `wsPort` e `wssPort` apontam para a porta correta (nginx `:80` em produção, `:5173` via proxy Vite em dev)

---

### 8. Frontend (Vue DevTools + Network)

**Vue DevTools — aba Pinia:**
- `auth` → `token` preenchido, `user` com id
- `sessions` → `activeSession` atualiza ao iniciar sessão; `null` ao encerrar
- `analytics` → `dashboard` atualiza após `MetricsRecalculated`; `isRecalculating: true` durante o job

**Network — requisições a observar:**

| Ação do usuário | Requisição esperada | Resposta esperada |
|---|---|---|
| Login | `POST /api/v1/auth/login` | `200 { data: { user, token } }` |
| Registro | `POST /api/v1/auth/register` | `201 { data: { user, token } }` |
| Iniciar sessão | `POST /api/v1/study-sessions/start` | `200 { data: StudySession }` |
| Encerrar sessão | `PATCH /api/v1/study-sessions/:id/end` | `200 { data: StudySession }` |
| Dashboard | `GET /api/v1/analytics/dashboard` | `200 { data: DashboardData }` |

**Checklist:**
- [ ] Interceptor Axios injeta `Authorization: Bearer <token>` em todas as rotas protegidas
- [ ] `401` redireciona para `/login` e limpa localStorage
- [ ] `429` exibe toast de rate limit (não crash silencioso)
- [ ] Store `analytics` não é sobrescrita por `refetchOnWindowFocus` do TanStack Query após atualização via WebSocket (verificar ordem dos handlers)

---

## Mapa Sintoma → Causa Raiz → Correção

| Sintoma | Causa mais provável | Como confirmar | Correção |
|---|---|---|---|
| `500` em qualquer endpoint | Redis sem senha / sem conexão | `docker compose exec redis redis-cli -a <senha> ping` | Alinhar `REDIS_PASSWORD` com `requirepass` em `redis.conf` |
| `401` mesmo com token válido | Token expirado (1440 min) ou `EnsureJsonResponse` ausente | Checar `created_at` do token via `/auth/tokens` | Re-logar; confirmar middleware |
| CORS bloqueado (`preflight` 403) | `CORS_ALLOWED_ORIGINS` vazio ou origin errada | `curl -X OPTIONS` com header `Origin` | Definir `CORS_ALLOWED_ORIGINS=http://localhost:5173` |
| Métricas não atualizam ao encerrar sessão | Bug `array_keys()` em `DispatchMetricsRecalculation` | Ver jobs na fila `metrics`; checar `fullRecalc` | **Já corrigido**: usar `$event->changedFields` diretamente |
| WebSocket não conecta | Porta 8080 não exposta / `VITE_REVERB_PORT` errado | `curl ws://localhost:8080/app/` | Usar porta `80` via nginx; `VITE_REVERB_PORT=80` |
| Canal privado não autorizado (`403` no broadcasting/auth) | Token não enviado no header do Echo ou `authEndpoint` errado | Ver request `POST /api/broadcasting/auth` no Network | Confirmar `auth.headers.Authorization` no Echo config |
| Dashboard "trava" ao deletar sessão (sem spinner) | `BroadcastMetricsRecalculating` ausente no `StudySessionDeleted` | Ver eventos no canal WS após delete | **Já corrigido**: listener adicionado ao `EventServiceProvider` |
| Registro cria conta mas não loga | `authApi.register` não retornava token | Ver response de `POST /api/v1/auth/register` | **Já corrigido**: backend e frontend alinhados |
| `wssPort: 443` em HTTP | Bug hardcoded nos dois branches do ternário | Inspecionar config do Echo no DevTools | **Já corrigido**: `wssPort` usa a porta configurada |
| Jobs acumulados na fila `metrics` | Horizon parado ou Redis inacessível | `php artisan horizon:status` + `redis-cli ping` | Reiniciar Horizon; corrigir senha Redis |
| `session_count` undefined em metas | Backend não retorna o campo em `/analytics/time-series` | Logar `d.session_count` em `useGoalProgress` | Incluir `session_count` na query do `AnalyticsRepository` |

---

## Comandos de Validação Ponta a Ponta (smoke test)

```bash
# 1. Registrar usuário
RESP=$(curl -s -X POST http://localhost/api/v1/auth/register \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Debug","email":"debug@test.com","password":"password123","password_confirmation":"password123"}')
echo $RESP | jq .
TOKEN=$(echo $RESP | jq -r '.data.token')
USER_ID=$(echo $RESP | jq -r '.data.user.id')

# 2. Buscar tecnologias
curl -s -H "Authorization: Bearer $TOKEN" http://localhost/api/v1/technologies | jq '.data[0]'

# 3. Criar tecnologia
TECH=$(curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"TypeScript","color":"#3178C6"}' \
  http://localhost/api/v1/technologies)
TECH_ID=$(echo $TECH | jq -r '.data.id')
echo "Tech ID: $TECH_ID"

# 4. Iniciar sessão
SESSION=$(curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"technology_id\":\"$TECH_ID\"}" \
  http://localhost/api/v1/study-sessions/start)
SESSION_ID=$(echo $SESSION | jq -r '.data.id')
echo "Session ID: $SESSION_ID"

# 5. Encerrar sessão
curl -s -X PATCH -H "Authorization: Bearer $TOKEN" \
  http://localhost/api/v1/study-sessions/$SESSION_ID/end | jq .

# 6. Verificar dashboard (aguardar ~3s para o job processar)
sleep 3
curl -s -H "Authorization: Bearer $TOKEN" \
  http://localhost/api/v1/analytics/dashboard | jq '.data.user_metrics'

# 7. Verificar autorização do canal WebSocket
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d "{\"socket_id\":\"123.456\",\"channel_name\":\"private-dashboard.$USER_ID\"}" \
  http://localhost/api/broadcasting/auth | jq .
```

**Resultado esperado no passo 6:** `today_minutes` > 0 e `today_sessions` = 1  
**Resultado esperado no passo 7:** `{ "auth": "local-key:..." }` (não `403`)

---

## Variáveis de Ambiente — Checklist de Alinhamento

| Variável | `backend/.env` | `frontend/.env` | `docker-compose.yml` | Observação |
|---|---|---|---|---|
| `REDIS_PASSWORD` | `<YOUR_REDIS_PASSWORD>` | — | — | Deve ser igual ao `requirepass` no `redis.conf` |
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173` | — | — | Em produção: URL real do frontend |
| `REVERB_APP_KEY` | `local-key` | — | — | Deve ser igual a `VITE_REVERB_APP_KEY` |
| `VITE_REVERB_APP_KEY` | — | `local-key` | — | Deve ser igual a `REVERB_APP_KEY` |
| `VITE_REVERB_HOST` | — | `localhost` | `localhost` | Em produção: domínio sem protocolo |
| `VITE_REVERB_PORT` | — | `8080` (`.env.example`) | — | Com Docker atual, Reverb escuta **8080** na rede interna; Nginx expõe WebSocket em **`/app/`** na porta **80**. Se o browser não alcançar `localhost:8080`, use **`VITE_REVERB_PORT=80`** (e mesmo host que serve a API) — pode ser necessário configurar `wsPath` no Echo (não está em `useWebSocket.ts` por omissão; alinhar com a doc do Reverb se a conexão falhar). |
| `VITE_REVERB_SCHEME` | — | `http` | `http` | Em produção: `https` / `wss` |
| `VITE_API_URL` | — | `` (vazio = same-origin) | `` | Proxy Vite em dev; em prod costuma ser vazio ou URL pública da API |
| `BROADCAST_CONNECTION` | `reverb` | — | — | Requer `REVERB_*` preenchidos no backend |
