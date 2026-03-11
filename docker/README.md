# Docker – StudyTrack Pro

Configuração Docker para o StudyTrack Pro.

---

## Serviços

| Serviço | Imagem/Contexto | Porta | Descrição |
|---------|-----------------|-------|-----------|
| **nginx** | nginx:1.25-alpine | 80, 443 | Proxy reverso (API, SPA, WebSocket, Horizon) |
| **php-fpm** | docker/php | - | Laravel (API + frontend estático) |
| **reverb** | docker/php (Dockerfile.cli) | 8080 | Laravel Reverb (WebSocket) |
| **horizon** | docker/php (Dockerfile.cli) | - | Processamento de filas |
| **scheduler** | docker/php (Dockerfile.cli) | - | Tarefas agendadas (schedule:work) |
| **node** | docker/node (Dockerfile.frontend) | 5173 | Vite dev server |
| **postgres** | postgres:16-alpine | 5432 | Banco de dados |
| **redis** | redis:7-alpine | 6379 | Cache, filas, Reverb |

### Extras (docker-compose.dev.yml)

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| **pgadmin** | 5050 | Interface web para PostgreSQL |
| **mailpit** | 8025 | Captura de emails (desenvolvimento) |

---

## Estrutura

```
docker/
├── nginx/
│   └── nginx.conf          # Proxy, gzip, headers de segurança
├── php/
│   ├── Dockerfile          # Imagem para php-fpm
│   ├── Dockerfile.cli      # Horizon, Reverb, scheduler
│   ├── php.ini
│   └── www.conf
├── node/
│   └── Dockerfile.frontend # Node + npm para Vite
├── postgres/
│   └── init/               # Scripts de inicialização (extensões, schema)
└── redis/
    └── redis.conf
```

---

## Nginx

Rotas principais:
- `/api/*` → Laravel (index.php)
- `/app/*` → Reverb (WebSocket)
- `/horizon` → Laravel (dashboard Horizon)
- `/health` → Health check
- `/*` → SPA Vue (frontend/dist)

Headers de segurança: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy.

---

## Comandos

```bash
# Do diretório raiz
make dev       # docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
make stop      # docker compose down
make build     # docker compose build
make shell-php # docker compose exec php-fpm bash
make shell-vue # docker compose exec node bash
make logs      # docker compose logs -f
```

---

## Variáveis de ambiente (docker-compose)

- `DB_PASSWORD` — Senha do PostgreSQL (default: secret)
- `VITE_REVERB_HOST` — Host do Reverb para o frontend (default: localhost)
- `VITE_REVERB_PORT` — Porta do Reverb (default: 5173 em dev)

---

## Produção

Para produção:
- Use imagens otimizadas (Dockerfile.production no php)
- Configure volumes apenas quando necessário
- Use secrets para senhas
- Ative HTTPS no Nginx
- Consulte [docs/DEPLOY_SECURITY_PASSO_A_PASSO.md](../docs/DEPLOY_SECURITY_PASSO_A_PASSO.md)
