# Auditoria de segurança — StudyTrack Pro

**Data do relatório original:** 2026-03-11  
**Última revisão documental:** alinhada ao código no repositório (Laravel 11, Sanctum, Docker/OpenResty).

Este ficheiro resume **riscos típicos** e o que **já está refletido no código** versus o que depende **sempre do ambiente** (`.env` de produção, TLS, segredos).

---

## 1. Estado no código (referência)

| Tema | Onde verificar | Comportamento esperado no repo |
|------|----------------|--------------------------------|
| CORS | `backend/config/cors.php` | Sem fallback para `*` quando `CORS_ALLOWED_ORIGINS` está vazio |
| Tokens Sanctum | `backend/config/sanctum.php` | `expiration` definido (ex.: `1440` minutos) |
| Rate limit login/register | `backend/app/Providers/AppServiceProvider.php` | Limiters `login` (3/min IP) e `register` (5/min IP) |
| Rate limit API | `backend/routes/api.php` | Grupos `throttle:*` e `throttle.sliding` em mutações de sessões |
| Fail-open Lua | `backend/config/services.php` | `rate_limit.fail_open` (env `RATE_LIMIT_FAIL_OPEN`) |
| Horizon | `AppServiceProvider` + `config/app.php` | Acesso por `HORIZON_ADMIN_EMAILS` |
| Borda HTTP | `docker/nginx/conf.d/studytrack.conf` | OpenResty + headers; validação de token revogado (ver `DOCUMENTACAO_TECNICA_LUA.md`) |

---

## 2. Depende exclusivamente de produção / deploy

Estes pontos **não** ficam “corrigidos” só com o código versionado:

| Risco | Mitigação |
|-------|-----------|
| `APP_DEBUG=true` em produção | `APP_DEBUG=false` no `.env` do servidor |
| URLs em HTTP | `APP_URL`, `REVERB_SCHEME`/`wss`, certificados TLS |
| Senhas fracas | `DB_PASSWORD`, `REDIS_PASSWORD` fortes; `requirepass` no Redis |
| CORS aberto | `CORS_ALLOWED_ORIGINS` com origens reais (sem `*`) |
| Segredos no Git | Nunca commitar `.env` com valores reais |
| Dependências vulneráveis | `composer audit`, `npm audit` no CI e localmente |

Passo a passo: [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md).

---

## 3. OWASP — leitura rápida (2023)

| ID | Tema | Notas para este projeto |
|----|------|-------------------------|
| A01 | Controlo de acesso | Ownership em serviços; Horizon restrito por email; canais WS privados |
| A02 | Falhas criptográficas | HTTPS em produção; tokens com expiração; Redis autenticado |
| A03 | Injeção | Eloquent + Form Requests; manter validação em novos endpoints |
| A04 | Design inseguro | Revisar decisões “fail-open” no edge e no rate limit Lua |
| A05 | Configuração incorreta | `.env` de produção, headers TLS, debug desligado |
| A06 | Componentes vulneráveis | Auditorias Composer/npm periódicas |
| A07 | Falhas de autenticação | Throttles em login/register; Sanctum |
| A09 | Logging / monitorização | `LogApiRequests`, health checks; evoluir para agregação centralizada |

---

## 4. Ações recomendadas (contínuas)

1. Manter [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md) sincronizado com o que o servidor realmente usa.  
2. Rodar `composer audit` e `npm audit` antes de releases.  
3. Rever periodicamente `docs/technical/DOCUMENTACAO_TECNICA_LUA.md` após mudanças em OpenResty/Redis/PL-Lua.  
4. Registar incidentes e correções em [SECURITY_FIXES_COMPLETED.md](SECURITY_FIXES_COMPLETED.md) (sem expor segredos).

---

**Resumo:** o repositório inclui várias **defesas em profundidade** no código; **produção segura** exige `.env`, TLS, Redis e políticas de deploy corretas — não deployar com valores de exemplo.
