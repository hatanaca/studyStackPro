# Lista de verificação — segurança (deploy)

Use este ficheiro como **roteiro manual** no servidor ou no pipeline de release. Não substitui o código: confirme sempre os ficheiros no Git.

> **Importante:** Não commite `.env` de produção nem exemplos com segredos reais. Gere valores com `openssl rand` e `php artisan key:generate`.

---

## Antes de ir a produção

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` com `https://`
- [ ] `CORS_ALLOWED_ORIGINS` com origens explícitas (sem `*`)
- [ ] `DB_PASSWORD` e `REDIS_PASSWORD` fortes; `requirepass` no Redis alinhado
- [ ] `HORIZON_ADMIN_EMAILS` definido
- [ ] `config/cors.php` e `config/sanctum.php` como na versão atual do repositório
- [ ] TLS terminado no proxy (Nginx/OpenResty) e redirect HTTP → HTTPS
- [ ] `composer audit` e `npm audit` sem vulnerabilidades críticas não tratadas

---

## Ficheiros de referência no repo

| Área | Caminho |
|------|---------|
| CORS | `backend/config/cors.php` |
| Sanctum | `backend/config/sanctum.php` |
| Rate limits | `backend/app/Providers/AppServiceProvider.php`, `backend/routes/api.php` |
| Proxy / headers | `docker/nginx/nginx.conf`, `docker/nginx/conf.d/studytrack.conf` |
| Redis | `docker/redis/redis.conf` |

---

## Documentação relacionada

- [SECURITY_AUDIT.md](SECURITY_AUDIT.md) — visão geral e OWASP
- [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md) — passos
- [SECURITY_FIXES_COMPLETED.md](SECURITY_FIXES_COMPLETED.md) — registo de alterações (sem segredos)

**Pontuação “antes/depois” ou chaves geradas** não devem ser copiadas deste repositório para o ambiente real — são apenas ilustrativas se aparecerem em histórico antigo.
