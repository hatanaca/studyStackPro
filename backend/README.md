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
├── api.php                 # Rotas /api/v1/*
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

### Autenticação
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/auth/register` | Registro |
| POST | `/auth/login` | Login |
| POST | `/auth/logout` | Logout |
| GET | `/auth/me` | Usuário atual |
| PUT | `/auth/me` | Atualizar perfil |
| POST | `/auth/change-password` | Trocar senha |
| GET | `/auth/tokens` | Listar tokens |
| DELETE | `/auth/tokens` | Revogar todos |

### Tecnologias
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/technologies` | Listar |
| GET | `/technologies/search?q=` | Buscar (autocomplete) |
| GET | `/technologies/{id}` | Detalhar |
| POST | `/technologies` | Criar |
| PUT | `/technologies/{id}` | Atualizar |
| DELETE | `/technologies/{id}` | Desativar |

### Sessões
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/study-sessions` | Listar (filtros, paginação) |
| GET | `/study-sessions/active` | Sessão ativa |
| GET | `/study-sessions/{id}` | Detalhar |
| POST | `/study-sessions` | Criar (log manual) |
| POST | `/study-sessions/start` | Iniciar sessão |
| PATCH | `/study-sessions/{id}/end` | Encerrar sessão |
| PUT/PATCH | `/study-sessions/{id}` | Atualizar |
| DELETE | `/study-sessions/{id}` | Deletar |

### Analytics
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/analytics/dashboard` | Payload completo |
| GET | `/analytics/user-metrics` | Métricas do usuário |
| GET | `/analytics/tech-stats` | Por tecnologia |
| GET | `/analytics/time-series?days=` | Séries temporais |
| GET | `/analytics/weekly` | Comparação semanal |
| GET | `/analytics/heatmap?year=` | Heatmap |
| POST | `/analytics/recalculate` | Disparar recálculo |
| GET | `/analytics/export?start=&end=` | Exportar JSON |

### Health
| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/health` | Status (DB, Redis, queue, WebSocket) |

---

## Rate limiting

| Rota / Grupo | Limite |
|--------------|--------|
| login | 5/min |
| register | 3/min |
| search | 30/min |
| sensitive (change-password) | 5/min |
| recalculate | 10/min |
| health | 60/min |
| demais autenticadas | 60/min |

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
