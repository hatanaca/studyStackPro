# Changelog

Alterações notáveis do projeto StudyTrack Pro.

## [1.0.1] - 2025-02-24

### feat
- HealthController extraído; rota GET /health em web.php (acessível sem prefixo /api)
- Headers de segurança no Nginx: X-Content-Type-Options, X-Frame-Options, Referrer-Policy
- PHP-FPM pool configurado (www.conf com pm.max_children)
- Migração de índices em study_sessions (user_started, user_ended, technology_id)
- GenerateWeeklySummaryJob agendado: segunda 03:00, withoutOverlapping, onOneServer
- broadcastAs com prefixo ponto: .metrics.updated e .metrics.recalculating
- .env.production.example para deploy
- Husky pre-commit: Pint + ESLint + type-check
- README: seção Sobre o projeto, Decisões de design
- Comentário em migration explicando duration_min GENERATED STORED

### chore
- .env.example: documentação rate limit Redis

## [1.0.0] - 2025-02

### feat
- Segurança: revogação de tokens ao trocar senha
- Segurança: retorno 403 em acesso cross-user (em vez de 404)
- Rate limiting: 5 req/min em login/register, 30 em writes, 60 em reads
- EnsureJsonResponse aplicado globalmente na API
- Health endpoint com verificação de DB, Redis, Queue e Reverb
- WebSocket: eventos MetricsRecalculated e MetricsRecalculating broadcast
- Laravel Echo + Reverb no frontend
- RealtimeBadge e polling de fallback
- useDashboard composable com isFresh e fetchDashboard(force)
- technologies.store: searchLocal para autocomplete
- SkeletonLoader, ErrorCard, BaseToast
- TechDistributionWidget (gráfico pizza) e HeatmapWidget
- DemoDataSeeder com 6 meses de sessões
- ConcurrentSessionException e handler 409
- Cache::lock em getDashboardData para evitar stampede

### fix
- .gitignore: removido prefixo projeto/

### chore
- .env.example completo (backend e raiz)
- Nginx: gzip e cache de assets
- Redis Horizon database (DB 4)
- Broadcast::routes com auth:sanctum
