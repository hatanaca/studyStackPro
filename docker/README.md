<p align="center">
  <h1 align="center">🐳 StudyTrack Pro — Docker</h1>
  <p align="center">
    <em>Containerized infrastructure with OpenResty, PHP-FPM, PostgreSQL, and Redis</em>
  </p>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker&logoColor=white" alt="Docker" />
  <img src="https://img.shields.io/badge/OpenResty-Nginx-009639?logo=nginx&logoColor=white" alt="OpenResty" />
  <img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white" alt="PostgreSQL 16" />
  <img src="https://img.shields.io/badge/Redis-7-DC382D?logo=redis&logoColor=white" alt="Redis 7" />
</p>

<p align="center">
  <a href="#services">Services</a> •
  <a href="#architecture">Architecture</a> •
  <a href="#commands">Commands</a> •
  <a href="#troubleshooting">Troubleshooting</a>
</p>

---

## Services

### Main (`docker-compose.yml`)

| Service | Image | Port | Description |
|---------|-------|------|-------------|
| **nginx** | OpenResty | 80, 443 | Proxy: API, SPA, WebSocket, Horizon |
| **php-fpm** | PHP 8.2-FPM | internal | Laravel API |
| **reverb** | Laravel CLI | 8080 (internal) | WebSocket server |
| **horizon** | Laravel CLI | internal | Queue workers |
| **scheduler** | Laravel CLI | internal | `schedule:work` |
| **node** | Node.js | 5173 | Vite dev server |
| **postgres** | PostgreSQL 16 | 5432 | Database |
| **redis** | Redis 7 | 6379 | Cache and queues |

### Extras (`docker-compose.dev.yml`)

| Service | Port | Description |
|---------|------|-------------|
| **pgadmin** | 5050 | Web interface for PostgreSQL |
| **mailpit** | 8025 | Email capture (dev) |

---

## Architecture

```mermaid
graph TB
    subgraph External["External"]
        Browser[Browser]
    end

    subgraph Proxy["Proxy - OpenResty :80"]
        N[nginx]
    end

    subgraph Backend["Backend"]
        PHP[php-fpm]
        Reverb[reverb :8080]
        Horizon[horizon]
        Scheduler[scheduler]
    end

    subgraph Frontend["Frontend"]
        Vite[node :5173]
    end

    subgraph Data["Data"]
        PG[(PostgreSQL :5432)]
        Redis[(Redis :6379)]
    end

    Browser --> N
    N -->|/api/*| PHP
    N -->|/app/*| Reverb
    N -->/*| Vite
    PHP --> PG
    PHP --> Redis
    Horizon --> Redis
    Reverb --> Redis
```

---

## Proxy Routes

| Route | Destination | Description |
|-------|-------------|-------------|
| `/api/*` | Laravel (php-fpm) | REST API |
| `/app/*` | Reverb (WS upgrade) | WebSocket |
| `/horizon` | Laravel | Queue dashboard |
| `/health` | Laravel | Health check |
| `/nginx-health` | OpenResty | Container probe |
| `/*` | SPA (frontend/dist) | Static files |

---

## Structure

```
docker/
├── nginx/
│   ├── Dockerfile              # openresty/openresty
│   ├── nginx.conf              # Main configuration
│   └── conf.d/
│       └── studytrack.conf     # Routes and edge Lua
├── php/
│   ├── Dockerfile              # php-fpm
│   ├── Dockerfile.cli          # Horizon, Reverb, scheduler
│   ├── php.ini
│   └── www.conf
├── node/
│   └── Dockerfile.frontend     # Node for Vite
├── postgres/
│   ├── Dockerfile              # Postgres 16 + extensions
│   └── init/                   # SQL init scripts
└── redis/
    ├── redis.conf
    └── docker-entrypoint.sh
```

---

## Commands

```bash
# From repository root
make dev           # docker compose up -d (dev)
make stop          # docker compose down
make build         # docker compose build
make shell-php     # Shell into PHP container
make shell-vue     # Shell into Node container
make logs          # Logs from all containers
make migrate       # Run migrations
make fresh         # migrate:fresh --seed
make test          # Backend + frontend tests
```

### Direct Docker Commands

```bash
# List containers
docker compose ps

# Logs for a specific service
docker compose logs -f php-fpm
docker compose logs -f nginx

# Restart a service
docker compose restart php-fpm

# Access container
docker compose exec php-fpm sh
docker compose exec postgres psql -U studytrack -d studytrack
```

---

## Environment Variables

| Variable | Service | Description |
|----------|---------|-------------|
| `DB_PASSWORD` | postgres, php-fpm | PostgreSQL password |
| `POSTGRES_*` | postgres | Database configuration |
| `REDIS_PASSWORD` | redis, php-fpm | Redis password |
| `VITE_REVERB_HOST` | node | Reverb host |
| `VITE_REVERB_PORT` | node | Reverb port |
| `VITE_REVERB_SCHEME` | node | http or https |

> ⚠️ In Docker, Echo communicates with Reverb **through Nginx** (port 80), not directly (8080).

---

## Troubleshooting

### Port Already in Use

```bash
# Check what's using the port
lsof -i :80
lsof -i :5432
lsof -i :6379

# Kill the process or change the port in docker-compose.yml
```

### Volume Permissions

```bash
# Fix PostgreSQL permissions
sudo chown -R 1000:1000 docker/postgres/

# Fix storage permissions
sudo chown -R 33:33 backend/storage/
```

### Container Won't Start

```bash
# Check logs
docker compose logs php-fpm
docker compose logs nginx

# Check if .env exists
ls -la backend/.env
```

### Redis Connection Refused

```bash
# Check if Redis is running
docker compose ps redis

# Test connection
docker compose exec redis redis-cli ping
```

### PostgreSQL Not Accepting Connections

```bash
# Check status
docker compose logs postgres

# Test connection
docker compose exec postgres psql -U studytrack -d studytrack -c "SELECT 1"
```

---

## Production

- Stable images and tags
- Secrets via orchestrator (not in Git)
- TLS on proxy (443)
- `APP_URL` and `REVERB_SCHEME` in HTTPS/WSS

> See [docs/operations/DEPLOY_SECURITY_PASSO_A_PASSO.md](../docs/operations/DEPLOY_SECURITY_PASSO_A_PASSO.md)

---

<p align="center">
  <a href="../README.md">← Back to main README</a>
</p>
