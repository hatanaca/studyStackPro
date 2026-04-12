# Docker – StudyTrack Pro

Configuração Docker para o StudyTrack Pro. O proxy HTTP é **OpenResty** (Nginx + Lua), não a imagem `nginx` oficial.

---

## Serviços (`docker-compose.yml`)

| Serviço | Imagem / build | Porta no host | Descrição |
|---------|----------------|---------------|-----------|
| **nginx** | `build: ./docker/nginx` (OpenResty) | 80, 443 | Proxy: API Laravel, SPA estática, WebSocket `/app/`, Horizon |
| **php-fpm** | `build: ./docker/php` | (interna) | Laravel (API); `frontend/dist` montado em `public/frontend` |
| **reverb** | `Dockerfile.cli` | *(não publicada)* | WebSocket na rede interna na porta **8080**; o browser usa **`ws://localhost/app/`** via Nginx na **80** |
| **horizon** | `Dockerfile.cli` | (interna) | Workers das filas (`metrics`, `default`, `scheduler`) |
| **scheduler** | `Dockerfile.cli` | (interna) | `php artisan schedule:work` |
| **node** | `build` (contexto `frontend/`) | **5173** | Vite dev (`npm run dev`); env injeta `VITE_REVERB_PORT=80` para Echo via Nginx |
| **postgres** | `build: ./docker/postgres` | **5432** (configurável) | PostgreSQL 16 com extensões do projeto (ex.: `pllua`); volume `postgres_pllua_data` |
| **redis** | `redis:7-alpine` | **6379** (configurável) | Cache, filas, sessões; `requirepass` via `redis.conf` |

### Extras (`docker-compose.dev.yml`)

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| **pgadmin** | 5050 | Interface web para PostgreSQL |
| **mailpit** | 8025 | Captura de e-mails (desenvolvimento) |

---

## Estrutura

```
docker/
├── nginx/
│   ├── Dockerfile              # openresty/openresty (imagem base)
│   ├── nginx.conf              # Configuração principal OpenResty
│   └── conf.d/
│       └── studytrack.conf     # Rotas, Lua na borda, proxy para PHP e Reverb
├── php/
│   ├── Dockerfile              # php-fpm
│   ├── Dockerfile.cli          # Horizon, Reverb, scheduler
│   ├── php.ini
│   └── www.conf
├── node/
│   └── Dockerfile.frontend     # Node para Vite
├── postgres/
│   ├── Dockerfile              # Postgres 16 + extensões
│   └── init/                   # Scripts SQL (extensions, schema analytics)
└── redis/
    ├── redis.conf
    └── docker-entrypoint.sh
```

---

## Rotas no proxy (resumo)

- `/api/*` → Laravel (`index.php`) — inclui `/api/v1/*`, `/api/health`, `/api/broadcasting/auth`
- `/app/*` → upgrade WebSocket → container **reverb:8080**
- `/horizon` → Laravel (dashboard Horizon; auth por utilizador + `HORIZON_ADMIN_EMAILS`)
- `/health` (web Laravel) e `/api/health` — health da aplicação; `/nginx-health` — probe do container OpenResty
- `/up` — health mínimo do Laravel (bootstrap)
- `/*` → SPA em `frontend/dist` (ficheiros estáticos)

Headers de segurança e lógica Lua na borda: ver `conf.d/studytrack.conf` e [docs/technical/DOCUMENTACAO_TECNICA_LUA.md](../docs/technical/DOCUMENTACAO_TECNICA_LUA.md).

---

## Comandos

```bash
# A partir da raiz do repositório
make dev       # docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
make stop      # docker compose ... down
make build     # docker compose build
make shell-php # docker compose exec php-fpm sh
make shell-vue # docker compose exec node sh
make logs      # docker compose logs -f
```

---

## Variáveis relevantes

- **`DB_PASSWORD` / `POSTGRES_*`** — definidas no compose e no `backend/.env`
- **`REDIS_PASSWORD`** — alinhada entre `backend/.env` e `docker/redis/redis.conf`
- **No serviço `node` (Vite no Docker):** `VITE_REVERB_HOST=localhost`, `VITE_REVERB_PORT=80`, `VITE_REVERB_SCHEME=http` — o Echo fala com o Reverb **através do Nginx** na porta 80, não diretamente na 8080 do container Reverb
- **No browser com Vite só no host** (`npm run dev` fora do Docker): usar `frontend/.env` (ex.: `VITE_REVERB_PORT=8080` **só** se publicar a porta 8080 do Reverb no host; caso contrário prefira `80` + mesmo host da API)

---

## Produção

- Imagens e tags estáveis; secrets via orchestrator, não valores no Git
- TLS no proxy (443) e `APP_URL` / `REVERB_SCHEME` em HTTPS/WSS
- Consulte [docs/operations/DEPLOY_SECURITY_PASSO_A_PASSO.md](../docs/operations/DEPLOY_SECURITY_PASSO_A_PASSO.md)
