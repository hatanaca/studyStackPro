# Checklist de debug rápido e prompt para diagnóstico – StudyTrackPro

Este documento consolida um **checklist repetível** para quando algo quebrar no ambiente e um **template de prompt** para usar no Cursor em sessões de diagnóstico completo.

---

## 1. Checklist de debug rápido

Sempre que algo falhar, rode na ordem:

1. **Containers**
   - `docker compose ps` → todos os serviços `healthy` (ou `running` onde não houver healthcheck).
   - Se algum estiver `unhealthy` ou `Exit`, inspecione com `docker compose logs <serviço>`.

2. **Health da API**
   - Testar manualmente: `GET http://localhost/health` ou `GET http://localhost/api/health` (conforme sua rota).
   - Deve retornar 200 com `status: "healthy"` e `services: { database, redis, queue, websocket }` ok.

3. **Variáveis de ambiente**
   - Validar vars críticas nos `.env` usados pelos containers/host:
     - **URL/origem:** `APP_URL`, `VITE_API_URL`, `CORS_ALLOWED_ORIGINS` (ex.: `http://localhost:5173` em dev).
     - **DB:** `DB_HOST=postgres`, `DB_PASSWORD` igual ao do container Postgres.
     - **Redis:** `REDIS_HOST=redis`, `REDIS_PASSWORD` igual ao `requirepass` em `docker/redis/redis.conf`.
     - **Reverb:** `REVERB_HOST=reverb` (na rede Docker), `REVERB_PORT=8080`; no frontend, em dev atrás do Nginx: `VITE_REVERB_HOST=localhost`, `VITE_REVERB_PORT=80`.

4. **Frontend e rede**
   - Acessar o frontend dev em `http://localhost:5173` (ou produção em `http://localhost`).
   - Abrir DevTools → aba **Network** e **Console** e reproduzir o fluxo que falha; anotar erros (4xx/5xx, CORS, WS).

5. **Logs dos serviços**
   - `docker compose logs php-fpm` (erros de Laravel, DB, Redis).
   - `docker compose logs horizon` (jobs falhando, conexão Redis).
   - `docker compose logs reverb` (binding, porta, conexões).

Após isso, se o problema não for óbvio, use o **prompt de debug completo** abaixo numa nova sessão do Cursor.

---

## 2. Template de prompt para debug completo

Copie o bloco abaixo no Cursor e preencha o trecho *[descreva aqui]* com o sintoma (ex.: “API retornando 500 em /api/v1/study-sessions”, “WebSocket não atualiza dashboard”, “Docker não sobe postgres”).

```text
Você é um agente full-stack especialista no projeto StudyTrackPro.

Contexto do projeto:
- Frontend: Vue 3 + TypeScript em `frontend/`, usando axios central em `frontend/src/api/client.ts`, stores Pinia, composables (`useWebSocket` em `frontend/src/composables/useWebSocket.ts`) e API REST `/api/v1/*`.
- Backend: Laravel 11 em `backend/`, com módulos em `backend/app/Modules/*`, rotas em `backend/routes/api.php` e `backend/routes/channels.php`, filas com Redis/Horizon e broadcasting com Reverb.
- Infra: Docker com `nginx`, `php-fpm`, `node` (Vite), `postgres`, `redis`, `horizon`, `reverb`, definidos em `docker-compose.yml` e configs em `docker/`.

Objetivo do debug nesta sessão:
- Identificar e corrigir problemas em: [descreva aqui: por exemplo, API retornando 500 em /api/v1/study-sessions, WebSocket não atualizando dashboard, Docker não sobe postgres, etc.]

Instruções para você (agente):
1. Explore primeiro:
   - Arquivos de env: `.env`, `.env.example`, `backend/.env`, `backend/.env.example`, `frontend/.env`, `frontend/.env.example`.
   - Docker: `docker-compose.yml`, `docker-compose.dev.yml`, arquivos em `docker/`.
   - Integração front-back: `frontend/src/api/client.ts`, `frontend/src/composables/useWebSocket.ts`, `frontend/src/api/modules/*`, `backend/routes/api.php`, `backend/routes/channels.php`, configs em `backend/config/{cors.php,broadcasting.php,queue.php,database.php,horizon.php}`.

2. Monte um diagnóstico:
   - Liste inconsistências em variáveis de ambiente (APP_URL, VITE_API_URL, CORS_ALLOWED_ORIGINS, DB_*, REDIS_*, REVERB_*, VITE_REVERB_*).
   - Aponte problemas óbvios em Docker (serviços faltando, portas erradas, conflitos de host).
   - Verifique se o frontend está chamando as URLs certas (baseURL, paths, auth header) e se o backend responde corretamente.

3. Proponha correções:
   - Para cada problema, sugira mudanças específicas em `.env`, `docker-compose.yml`, configs de Laravel ou código TS.
   - Priorize correções que façam o ambiente subir sem erros, depois integração HTTP, depois WebSocket/fila.

4. Valide:
   - Simule a sequência de testes que eu devo fazer (comandos `docker compose`, chamadas HTTP básicas, fluxo de login, criação de sessão de estudo, visualização do dashboard) para confirmar que tudo funciona.

Sempre explique em português, de forma direta e objetiva, e proponha comandos prontos quando fizer sentido.
```

---

## 3. Referências rápidas

- **Rotas API:** `backend/routes/api.php` (prefixo `v1`: auth, technologies, study-sessions, analytics).
- **Health:** `GET /health` (web) e `GET /api/health` (api); valida DB, Redis, queue e WebSocket.
- **CORS:** `backend/config/cors.php` usa `CORS_ALLOWED_ORIGINS` (vírgula); em dev inclua `http://localhost:5173`.
- **WebSocket:** canal privado `dashboard.{userId}`; auth em `POST /api/broadcasting/auth` com `Authorization: Bearer <token>`; Nginx proxy em `location /app/` para Reverb.
- **Agente de integração e debug:** regra em `.cursor/rules/integracao-debug-studytrackpro.mdc` e prompt em `docs/prompt-agente-integracao-debug-studytrackpro.md`.
