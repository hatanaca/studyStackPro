# StudyTrack Pro — correções de segurança aplicadas

**Data de referência:** 2026-03-11  
**Estado:** registro histórico das alterações prioritárias tratadas no repositório.

> **Nota:** Não commite ficheiros `.env` ou `.env.production` com segredos reais. Use `backend/.env.example` como modelo e gere chaves com `php artisan key:generate` e passwords com `openssl`.

---

## Crítico — tratado

### 1. CORS

- **Ficheiro:** `backend/config/cors.php`
- **Comportamento:** origens a partir de `CORS_ALLOWED_ORIGINS`; sem fallback para `*` em produção.

### 2. APP_DEBUG

- **Produção:** `APP_DEBUG=false` no ambiente de deploy.
- **Desenvolvimento:** `APP_DEBUG=true` é aceitável localmente.

### 3. Palavra-passe da base de dados

- **Produção:** usar valor forte (ex.: 32+ caracteres aleatórios), nunca `secret`.

### 4. Redis com autenticação

- **Ficheiros:** `docker/redis/redis.conf`, variáveis `REDIS_*` no backend, alinhamento com `docker-compose.yml`.

---

## Alta severidade — tratado

### 5. Expiração de tokens Sanctum

- **Ficheiro:** `backend/config/sanctum.php`
- **Campo:** `expiration` (minutos), conforme política do projeto.

### 6. Rate limiting de autenticação

- **Ficheiros:** `backend/app/Providers/AppServiceProvider.php`, `backend/routes/api.php`
- **Limitadores nomeados:** `login` (3/min por IP), `register` (5/min por IP), entre outros — ver código atual.

### 7. HTTPS / WSS em produção

- `APP_URL` e `REVERB_SCHEME` devem refletir HTTPS/WSS em produção.

### 8. Acesso ao Horizon

- **Ficheiro:** `backend/app/Providers/AppServiceProvider.php` (callback `Horizon::auth`)
- **Configuração:** `HORIZON_ADMIN_EMAILS` em `config/app.php` / `.env`.

---

## Média severidade — tratado

### 9. Cabeçalhos de segurança

- **Proxy:** `docker/nginx/nginx.conf` e `docker/nginx/conf.d/studytrack.conf` (OpenResty), incluindo reforço por Lua onde aplicável.

### 10. Utilizador não-root no PHP (quando aplicável)

- **Ficheiro:** `docker/php/Dockerfile` — revisar `USER` e permissões de `storage`.

### 11. Rede Docker

- **Ficheiro:** `docker-compose.yml` — rede interna `app`; apenas o proxy expõe portas públicas por omissão.

### 12. Configuração Redis

- **Ficheiro:** `docker/redis/redis.conf` — `requirepass`, limites de memória e política de eviction conforme ambiente.

---

## Ficheiros tipicamente envolvidos

| Ficheiro | Notas |
|----------|--------|
| `backend/config/cors.php` | CORS |
| `backend/config/sanctum.php` | Tokens |
| `backend/app/Providers/AppServiceProvider.php` | Rate limits, Horizon |
| `backend/routes/api.php` | Throttle por rota |
| `docker/nginx/*.conf` | Cabeçalhos, TLS (quando ativo) |
| `docker/redis/redis.conf` | Auth Redis |
| `docker-compose.yml` | Rede e serviços |

---

## Próximos passos em produção

- [ ] Domínios reais em `CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`, `APP_URL`
- [ ] Certificados TLS e redirecionamento HTTP → HTTPS
- [ ] `HORIZON_ADMIN_EMAILS` definido
- [ ] `composer audit` e `npm audit` periódicos
- [ ] HSTS após TLS estável
- [ ] Backups PostgreSQL e monitorização

---

## Resumo

| Área | Direção |
|------|---------|
| CORS | Restrito por configuração |
| Debug | Desligado em produção |
| Credenciais | Fortes e fora do Git |
| Redis | Autenticado |
| Tokens | Com expiração configurada |
| Login/register | Throttle estrito (ver código) |
| Horizon | Apenas emails autorizados |

Para checklist operacional atualizado, ver [DEPLOY_SECURITY_PASSO_A_PASSO.md](DEPLOY_SECURITY_PASSO_A_PASSO.md).
