# Passo a passo – Segurança em produção (StudyTrackPro)

Checklist para aplicar as medidas do [SECURITY_AUDIT.md](SECURITY_AUDIT.md) no ambiente de produção.  
Não commitar `.env` de produção nem senhas no repositório.

---

## 1. Variáveis de ambiente (backend)

No servidor, edite o `.env` de produção (ex.: `backend/.env` ou o que seu deploy usa).

| Passo | Variável | Ação |
|-------|----------|------|
| 1.1 | `APP_ENV` | Defina `production`. |
| 1.2 | `APP_DEBUG` | Defina `false`. |
| 1.3 | `APP_URL` | Use HTTPS, ex.: `https://api.seudominio.com`. |
| 1.4 | `APP_KEY` | Gere com `php artisan key:generate` se ainda não existir. |
| 1.5 | `CORS_ALLOWED_ORIGINS` | Liste as origens do frontend separadas por vírgula, ex.: `https://app.seudominio.com`. Sem `*`. |
| 1.6 | `DB_PASSWORD` | Use senha forte. Gere com: `openssl rand -base64 32`. |
| 1.7 | `REDIS_PASSWORD` | Use senha forte (mesma que `requirepass` no Redis, se usar). Gere com: `openssl rand -base64 32`. |
| 1.8 | `HORIZON_ADMIN_EMAILS` | Emails (separados por vírgula) que podem acessar `/horizon`. Ex.: `admin@seudominio.com`. |

---

## 2. Redis

| Passo | Ação |
|-------|------|
| 2.1 | Gere uma senha: `openssl rand -base64 32`. |
| 2.2 | No `.env` do backend, defina `REDIS_PASSWORD=<senha_gerada>`. |
| 2.3 | Em `docker/redis/redis.conf`, defina `requirepass <mesma_senha>` (ou use variável/secret no seu orchestrator). |
| 2.4 | Reinicie o Redis após alterar `redis.conf`. |

---

## 3. HTTPS (Nginx / servidor)

| Passo | Ação |
|-------|------|
| 3.1 | Obtenha certificado SSL (Let’s Encrypt, Cloudflare ou seu provedor). |
| 3.2 | Configure o Nginx para escutar na porta 443 e usar o certificado. |
| 3.3 | Redirecione HTTP (80) para HTTPS (301). |
| 3.4 | No `.env`, `APP_URL` e `VITE_*` devem usar `https://`. |

**Exemplo de redirecionamento no Nginx (antes do `server` principal):**

```nginx
server {
    listen 80;
    server_name api.seudominio.com;
    return 301 https://$server_name$request_uri;
}
```

---

## 4. Headers de segurança (Nginx)

Se ainda não estiverem no `server` que serve HTTPS, adicione:

| Passo | Header | Valor sugerido |
|-------|--------|----------------|
| 4.1 | Strict-Transport-Security | `max-age=31536000; includeSubDomains` (só com HTTPS ativo) |
| 4.2 | X-Content-Type-Options | `nosniff` |
| 4.3 | X-Frame-Options | `SAMEORIGIN` |
| 4.4 | X-XSS-Protection | `1; mode=block` |
| 4.5 | Referrer-Policy | `strict-origin-when-cross-origin` |
| 4.6 | Permissions-Policy | `geolocation=(), microphone=(), camera=()` |

O projeto já inclui vários deles em [docker/nginx/nginx.conf](../../docker/nginx/nginx.conf) e em [docker/nginx/conf.d/studytrack.conf](../../docker/nginx/conf.d/studytrack.conf) (OpenResty). Em produção, ative o HSTS quando o SSL estiver em uso.

---

## 5. Código já corrigido (apenas conferir)

Não é necessário alterar; só validar que a versão em produção é a atual:

| Item | Arquivo | Verificação |
|------|---------|-------------|
| CORS | `backend/config/cors.php` | `allowed_origins` usa `[]` quando `CORS_ALLOWED_ORIGINS` não está definido. |
| Sanctum | `backend/config/sanctum.php` | `expiration => 1440` (tokens em 24h). |
| Rate limit login | `backend/routes/api.php` + `AppServiceProvider` | Rotas de login com `throttle:login` (3 req/min). |

---

## 6. Após alterar .env e Redis

| Passo | Comando / ação |
|-------|----------------|
| 6.1 | `php artisan config:clear` e `php artisan cache:clear`. |
| 6.2 | Reiniciar workers (Horizon/queue) para carregar o novo `.env`. |
| 6.3 | Testar login, uma chamada de API e o dashboard Horizon (com usuário autorizado). |

---

## 7. Auditoria de dependências (recomendado)

No backend e no frontend, rode periodicamente:

```bash
# Backend
cd backend && composer audit

# Frontend (pasta frontend)
npm audit
```

Corrija vulnerabilidades críticas/altas antes de considerar o deploy seguro.

---

## Resumo rápido

1. **.env produção:** `APP_DEBUG=false`, `APP_URL=https://...`, `CORS_ALLOWED_ORIGINS` definido, senhas fortes (DB e Redis), `HORIZON_ADMIN_EMAILS`.
2. **Redis:** `requirepass` em `redis.conf` e `REDIS_PASSWORD` no `.env`.
3. **HTTPS:** Certificado + redirect 80→443; URLs em `.env` com `https://`.
4. **Nginx:** Headers de segurança (incluindo HSTS quando em HTTPS).
5. **Código:** CORS, Sanctum e throttle já ajustados; manter código atualizado.
6. **Depois:** Limpar config/cache, reiniciar workers e rodar `composer audit` / `npm audit`.
