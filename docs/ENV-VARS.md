# Variáveis de ambiente

Use os arquivos `.env.example` como base e copie para `.env` no primeiro setup (`make setup` faz isso).

## Backend (`backend/.env.example`)

| Variável | Uso |
|----------|-----|
| `APP_*` | Nome, ambiente, key, debug, URL da aplicação |
| `DB_*` | Conexão PostgreSQL (host, porta, database, usuário, senha) |
| `REDIS_*`, `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` | Redis para cache, filas e sessão |
| `REVERB_*` | Laravel Reverb (WebSocket): app key, host, porta, scheme |
| `SANCTUM_STATEFUL_DOMAINS` | Domínios para cookies de autenticação |
| `CORS_ALLOWED_ORIGINS` | Origens permitidas para CORS (ex.: `http://localhost:5173`) |
| `HORIZON_ADMIN_EMAILS` | Emails (separados por vírgula) que podem acessar o dashboard do Horizon |

O backend repassa algumas variáveis ao frontend via `VITE_*` no mesmo arquivo (ex.: `VITE_API_URL`, `VITE_REVERB_*`).

## Frontend (`frontend/.env.example`)

| Variável | Uso |
|----------|-----|
| `VITE_API_URL` | URL base da API (vazio = proxy relativo em dev) |
| `VITE_REVERB_ENABLED` | `true`/`false` para ativar/desativar WebSocket |
| `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, `VITE_REVERB_SCHEME`, `VITE_REVERB_APP_KEY` | Conexão com o servidor Reverb |

Depois de alterar variáveis no backend que o frontend usa, é necessário rebuild do frontend (`npm run build`) para que as `VITE_*` sejam embutidas.
