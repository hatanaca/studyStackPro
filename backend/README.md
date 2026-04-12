# StudyTrack Pro – Backend

API REST do **StudyTrack Pro** construída com Laravel 11, PostgreSQL e Redis.

---

## Stack

| Componente | Tecnologia |
|------------|------------|
| Framework | Laravel 11 |
| PHP | 8.2+ |
| Banco de dados | PostgreSQL 16 |
| Cache / Filas | Redis 7 |
| Autenticação | Laravel Sanctum |
| WebSocket | Laravel Reverb |
| Filas | Laravel Horizon |

---

## Estrutura do código

```
app/
├── Events/                 # Eventos de domínio
│   ├── StudySession/       # StudySessionCreated, Updated, Deleted
│   └── Analytics/          # MetricsRecalculated
├── Http/
│   ├── Controllers/V1/     # Auth, StudySession, Technology, Analytics
│   ├── Middleware/         # SetUserTimezone, etc.
│   ├── Requests/           # Form Requests (validação)
│   └── Resources/          # API Resources (User, StudySession, etc.)
├── Jobs/                   # RecalculateMetricsJob
├── Listeners/              # InvalidateCache, DispatchRecalculation, Broadcast
├── Models/                 # User, Technology, StudySession, BaseModel
├── Modules/                # Módulos por domínio
│   ├── Auth/               # Services, DTOs, Repositories
│   ├── StudySessions/
│   ├── Technologies/
│   └── Analytics/          # Services, Aggregators, Repositories
├── Providers/              # EventServiceProvider, AppServiceProvider
└── Traits/                 # HasApiResponse, HasUuid

database/
├── migrations/
│   ├── transactional/      # users, technologies, study_sessions, etc.
│   └── analytics/          # user_metrics, technology_metrics, daily_minutes
└── seeders/

routes/
├── api.php                 # /api/v1/* (negócio) e GET /api/health
├── web.php                 # Raiz, GET /health (mesmo HealthController)
└── channels.php            # Canal privado dashboard.{userId}
```

---

## Convenções

- **Controllers** são thin: delegam para Services, usam Form Requests e Resources.
- **Services** contêm regras de negócio; acessam dados via Repositories (interfaces).
- **Repositories** abstraem Eloquent. Implementam contratos em `Contracts/`.
- **DTOs** são `readonly` e transportam dados validados entre camadas.
- **Events** no passado (Created, Updated, Deleted).
- **Listeners** rápidos: invalidam cache, disparam jobs. Evite lógica pesada.
- **Cache** usa tags: `['analytics', "user:{$id}"]` para flush por usuário.

---

## API v1

Todos os endpoints abaixo usam o prefixo **`/api/v1`** (exceto health da API, ver fim da seção).

### Autenticação
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/api/v1/auth/register` | Registro |
| POST | `/api/v1/auth/login` | Login |
| POST | `/api/v1/auth/logout` | Logout |
| GET | `/api/v1/auth/me` | Usuário atual |
| PUT | `/api/v1/auth/me` | Atualizar perfil |
| POST | `/api/v1/auth/change-password` | Trocar senha |
| GET | `/api/v1/auth/tokens` | Listar tokens |
| DELETE | `/api/v1/auth/tokens` | Revogar todos |

### Tecnologias
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/v1/technologies` | Listar |
| GET | `/api/v1/technologies/search?q=` | Buscar (autocomplete) |
| GET | `/api/v1/technologies/{id}` | Detalhar |
| POST | `/api/v1/technologies` | Criar |
| PUT | `/api/v1/technologies/{id}` | Atualizar |
| DELETE | `/api/v1/technologies/{id}` | Desativar |

### Sessões
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/v1/study-sessions` | Listar (filtros, paginação) |
| GET | `/api/v1/study-sessions/active` | Sessão ativa |
| GET | `/api/v1/study-sessions/{id}` | Detalhar |
| POST | `/api/v1/study-sessions` | Criar (log manual) |
| POST | `/api/v1/study-sessions/start` | Iniciar sessão |
| PATCH | `/api/v1/study-sessions/{id}/end` | Encerrar sessão |
| PUT/PATCH | `/api/v1/study-sessions/{id}` | Atualizar |
| DELETE | `/api/v1/study-sessions/{id}` | Deletar |

### Analytics
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/v1/analytics/dashboard` | Payload completo |
| GET | `/api/v1/analytics/user-metrics` | Métricas do usuário |
| GET | `/api/v1/analytics/tech-stats` | Por tecnologia |
| GET | `/api/v1/analytics/time-series?days=` | Séries temporais |
| GET | `/api/v1/analytics/weekly` | Comparação semanal |
| GET | `/api/v1/analytics/heatmap?year=` | Heatmap |
| POST | `/api/v1/analytics/recalculate` | Disparar recálculo |
| GET | `/api/v1/analytics/export?start=&end=` | Exportar JSON |

### Health e probes

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/health` | Health JSON da aplicação (DB, Redis, fila, WebSocket) — `routes/api.php` |
| GET | `/health` | Mesmo controller via `routes/web.php` (útil na raiz da app) |
| GET | `/up` | Health mínimo do Laravel 11 (`bootstrap/app.php`) |

---

## Rate limiting

Definido em `app/Providers/AppServiceProvider.php` e aplicado em `routes/api.php`.

| Nome do limiter | Limite |
|-----------------|--------|
| `login` | 3/min por IP |
| `register` | 5/min por IP |
| `search` | 120/min por usuário (ou IP) |
| `sensitive` (ex.: change-password) | 5/min por usuário (ou IP) |
| `recalculate` | 2/min por usuário (ou IP) |
| `export` | 30/min por usuário (ou IP) |
| `health` | 300/min por IP |

Grupos adicionais em `api.php`:

- leituras autenticadas: `throttle:60,1` (60/min);
- escrita genérica autenticada: `throttle:30,1` (30/min);
- rotas de mutação de sessão: middleware `throttle.sliding` (janela deslizante via Redis Lua; limites por rota no próprio `api.php`).

---

## Instalação

### Com Docker (recomendado)

Do diretório raiz do projeto:

```bash
make dev
make shell-php
php artisan key:generate
php artisan migrate:fresh --seed
```

### Local

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` com PostgreSQL e Redis. Depois:

```bash
php artisan migrate:fresh --seed
php artisan reverb:start   # Em outro terminal
php artisan horizon       # Em outro terminal
```

---

## Comandos úteis

```bash
php artisan migrate              # Rodar migrations
php artisan migrate:fresh --seed # Reset + seed
php artisan horizon              # Iniciar Horizon
php artisan reverb:start         # Iniciar Reverb
php artisan test                 # PHPUnit
./vendor/bin/pint                # Formatação (Pint)
```

---

## Variáveis de ambiente

Veja `backend/.env.example`. Principais:

- `APP_KEY` — gerar com `php artisan key:generate`
- `DB_*` — conexão PostgreSQL
- `REDIS_*` — cache, filas, Reverb
- `REVERB_*` — WebSocket
- `CORS_ALLOWED_ORIGINS` — origens do frontend (produção)
- `HORIZON_ADMIN_EMAILS` — emails autorizados em `/horizon`
