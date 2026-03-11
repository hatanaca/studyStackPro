# StudyTrack Pro

<p align="center">
  <strong>Plataforma para desenvolvedores e estudantes acompanharem sessões de estudo e métricas de produtividade</strong>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Vue-3.4-4FC08D?logo=vue.js" alt="Vue 3" />
  <img src="https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel" alt="Laravel 11" />
  <img src="https://img.shields.io/badge/TypeScript-5.4-3178C6?logo=typescript" alt="TypeScript" />
  <img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql" alt="PostgreSQL" />
  <img src="https://img.shields.io/badge/Docker-Ready-2496ED?logo=docker" alt="Docker" />
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#stack">Stack</a> •
  <a href="#conceitos">Conceitos</a> •
  <a href="#instalação">Instalação</a> •
  <a href="#estrutura">Estrutura</a> •
  <a href="#documentação">Documentação</a>
</p>

---

## Sobre o projeto

**StudyTrack Pro** é uma aplicação full-stack para **desenvolvedores e estudantes** registrarem sessões de estudo, visualizar métricas de produtividade e manter consistência em rotinas de aprendizado. O sistema permite categorizar tempo por tecnologia (linguagens, frameworks, ferramentas), exibe gráficos de distribuição, heatmap de atividade no estilo GitHub e streaks de dias consecutivos.

**Para quem:** desenvolvedores autodidatas, participantes de bootcamps e quem busca medir evolução técnica.

**Por quê:** portfólio full-stack demonstrando arquitetura event-driven, cache distribuído, TypeScript, WebSocket em tempo real e boas práticas (DDD, testes, CI/CD).

---

## Features

| Feature | Descrição |
|---------|-----------|
| **Sessões de estudo** | Registro manual ou timer em tempo real. Vincule sessões a tecnologias. |
| **Dashboard** | KPIs (horas totais, sessões, streak), gráficos de séries temporais e distribuição por tecnologia. |
| **Heatmap** | Visualização de atividade por dia/semana (estilo GitHub). |
| **Tecnologias** | CRUD de tecnologias (nome, cor, ícone). Busca para autocomplete. |
| **Metas (Goals)** | Defina metas de horas/semana. Persistência em localStorage (frontend-only). |
| **Exportação** | Exporte dados de analytics em JSON para período customizado. |
| **Tema claro/escuro** | Suporte a dark mode e tema customizável. |
| **Tempo real** | Dashboard atualiza via WebSocket (Laravel Reverb) quando sessões mudam ou métricas são recalculadas. |
| **Autenticação** | Registro, login, tokens Sanctum. Gestão de dispositivos (revogar tokens). |

---

## Stack

| Camada | Tecnologia | Motivação |
|--------|------------|-----------|
| **Frontend** | Vue 3, TypeScript 5.4, Vite 5 | SPA reativa, tipagem estática, DX moderna |
| **Estado** | Pinia, TanStack Query | Store reativa e cache de dados da API |
| **UI** | PrimeVue, ApexCharts | Componentes prontos, gráficos profissionais |
| **Backend** | Laravel 11, PHP 8.2 | API REST, filas, broadcasting, ecosystem maduro |
| **Auth** | Laravel Sanctum | Tokens API stateless |
| **Banco** | PostgreSQL 16 | ACID, JSON, schemas (public + analytics) |
| **Cache/Filas** | Redis 7 | Cache com tags, filas, Reverb pub/sub |
| **WebSocket** | Laravel Reverb | Canal privado por usuário |
| **Jobs** | Laravel Horizon | Processamento de filas (recálculo de métricas) |
| **Infra** | Docker, Nginx | Containerização e proxy reverso |

---

## Conceitos

### Arquitetura event-driven

- **Controllers** fazem chamadas aos **Services** e disparam **Events**.
- **Listeners** invalidam cache, enfileiram **Jobs** e fazem broadcast via WebSocket.
- Lógica de negócio isolada nos Services; acesso a dados via **Repositories** (Contracts + Eloquent).

### Schemas separados (PostgreSQL)

- **`public`**: dados transacionais (users, technologies, study_sessions).
- **`analytics`**: métricas pré-calculadas (user_metrics, technology_metrics, daily_minutes).
- Facilita CQRS e consultas pesadas sem impactar writes.

### Cache com tags

- `Cache::tags(['analytics', "user:{$id}"])` permite flush por usuário sem listar chaves.
- TTLs: dashboard 5min, heatmap 1h, export sem cache.

### Fluxo de recálculo de métricas

1. Sessão criada/atualizada/deletada → Event → Listener.
2. `RecalculateMetricsJob` em fila `metrics` (delay 2s para agrupar).
3. Job: recalcula user_metrics, technology_metrics, daily_minutes em transação.
4. Flush cache analytics do usuário.
5. Event `MetricsRecalculated` (ShouldBroadcast) → frontend recebe via Reverb.

---

## Instalação

### Pré-requisitos

- **Docker** e **Docker Compose**
- **Git**

### Setup rápido (Docker)

```bash
# 1. Clone o repositório
git clone https://github.com/seu-usuario/studyTrackPro.git
cd studyTrackPro

# 2. Configure variáveis de ambiente
make setup   # ou: cp .env.example .env && cp backend/.env.example backend/.env

# 3. Suba os containers
make dev
# ou sem Make: docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# 4. No backend (primeiro uso): key, migrations e seed
make shell-php   # ou: docker compose exec php-fpm bash
php artisan key:generate
php artisan migrate:fresh --seed
exit

# 5. Build do frontend (produção serve SPA estática em /)
cd frontend && npm install && npm run build && cd ..

# 6. Acesse
# - API + SPA: http://localhost
# - Frontend dev (Vite): http://localhost:5173
# - Health: http://localhost/api/health
# - Horizon: http://localhost/horizon
# - pgAdmin (dev): http://localhost:5050  |  Mailpit: http://localhost:8025
```

> **Nota:** `docker-compose.dev.yml` adiciona pgAdmin e Mailpit. Use apenas `docker-compose.yml` se preferir.

### Setup sem Docker

Consulte os READMEs em [backend/](backend/README.md) e [frontend/](frontend/README.md) para instalação manual (PHP, Node, PostgreSQL, Redis, Reverb).

---

## Estrutura do projeto

```
studyTrackPro/
├── backend/              # Laravel 11 API
│   ├── app/
│   │   ├── Events/       # Eventos (StudySession, Analytics)
│   │   ├── Jobs/         # RecalculateMetricsJob, etc.
│   │   ├── Listeners/    # Cache, broadcast, recálculo
│   │   ├── Modules/      # Auth, StudySessions, Technologies, Analytics
│   │   │   ├── */Services/
│   │   │   ├── */Repositories/
│   │   │   └── */DTOs/
│   │   └── Http/
│   ├── config/
│   ├── database/migrations/
│   └── routes/api.php
├── frontend/             # Vue 3 SPA
│   └── src/
│       ├── api/          # Cliente HTTP, endpoints, módulos
│       ├── components/   # ui, layout, charts
│       ├── composables/
│       ├── features/     # dashboard, sessions, technologies, goals
│       ├── stores/       # Pinia (auth, analytics, sessions, etc.)
│       ├── router/
│       ├── types/
│       └── views/
├── docker/               # Nginx, PHP, Node, Postgres, Redis
├── docs/                 # Documentação adicional
├── Makefile
├── docker-compose.yml
└── docker-compose.dev.yml  # Extras: pgAdmin, Mailpit
```

---

## Comandos úteis

| Comando | Descrição |
|---------|-----------|
| `make setup` | Cria arquivos `.env` (primeira vez) |
| `make dev` | Sobe todos os serviços Docker |
| `make stop` | Para os containers |
| `make shell-php` | Shell no container PHP |
| `make shell-vue` | Shell no container Node |
| `make migrate` | Roda migrations |
| `make fresh` | migrate:fresh --seed |
| `make test` | Testes backend (PHPUnit) + frontend (Vitest) |
| `make logs` | Logs de todos os containers |

---

## Documentação

| Documento | Descrição |
|-----------|-----------|
| [backend/README.md](backend/README.md) | API, estrutura, convenções, setup |
| [frontend/README.md](frontend/README.md) | Stack, estrutura, scripts, design system |
| [docker/README.md](docker/README.md) | Serviços Docker, Nginx, configuração |
| [docs/README.md](docs/README.md) | Índice de toda a documentação |
| [docs/DEPLOY_SECURITY_PASSO_A_PASSO.md](docs/DEPLOY_SECURITY_PASSO_A_PASSO.md) | Segurança em produção |
| [docs/CHECKLIST-E-PROMPTS.md](docs/CHECKLIST-E-PROMPTS.md) | Checklist de entrega, prompts |
| [docs/GOALS-FRONTEND-ONLY.md](docs/GOALS-FRONTEND-ONLY.md) | Metas (frontend-only) |

### Coleção Postman

`docs/StudyTrack_API_Collection.postman.json` — endpoints da API v1.

---

## Variáveis de ambiente

- **Raiz:** `.env.example` → `.env`
- **Backend:** `backend/.env.example` → `backend/.env`
- **Frontend:** `frontend/.env.example` → `frontend/.env`

Em produção: `APP_DEBUG=false`, HTTPS, senhas fortes. Ver [docs/DEPLOY_SECURITY_PASSO_A_PASSO.md](docs/DEPLOY_SECURITY_PASSO_A_PASSO.md).

---

## Licença

Uso educacional e portfólio.
