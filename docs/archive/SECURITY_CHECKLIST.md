# Security Checklist — StudyTrack Pro

> Pontos de segurança identificados na auditoria de 2026-06-23.
> Corrigir antes de deploy em produção.

## CRÍTICO

### 1. Secrets hardcoded no `backend/.env`
- **Arquivo:** `backend/.env:74-84`
- **Problema:** OAuth credentials (Google, Discord) e YouTube API key em texto plano.
- **Risco:** Vazamento se `.gitignore` for alterado ou `git add .` acidental.
- **Correção:** Usar apenas placeholders no `.env.example`. Em produção, usar variáveis de ambiente do sistema ou secret manager (Vault, AWS SSM).

### 2. Tokens OAuth em `$fillable` do model User
- **Arquivo:** `backend/app/Models/User.php:37-42`
- **Problema:** `discord_token`, `google_token`, `discord_refresh_token`, etc. estão em `$fillable`.
- **Risco:** `User::create($request->all())` pode injetar tokens arbitrários via mass assignment.
- **Correção:** Remover tokens de `$fillable`; usar `forceFill` ou update direto nos services.

### 3. Debug mode em produção
- **Arquivo:** `backend/app/Exceptions/Handler.php:72`
- **Problema:** `config('app.debug') ? $e->getMessage()` expõe detalhes internos se `APP_DEBUG=true`.
- **Risco:** Information disclosure (SQL queries, file paths, stack traces).
- **Correção:** Nunca retornar `$e->getMessage()` em produção; usar mensagens genéricas.

### 4. `env()` direto no controller
- **Arquivo:** `backend/app/Http/Controllers/V1/OAuthController.php:56`
- **Problema:** `env('FRONTEND_URL')` retorna `null` após `php artisan config:cache`.
- **Correção:** Usar `config('app.frontend_url')` e criar entrada em `config/app.php`.

## MÉDIO

### 5. Rate limit compartilhado entre endpoints heterogêneos
- **Arquivo:** `backend/routes/api.php:68`
- **Problema:** Logout, update profile, change password, CRUD sessions — todos no bucket `throttle:30,1`.
- **Risco:** Ataque em um endpoint drena a cota dos outros.
- **Correção:** Separar em buckets distintos por categoria.

### 6. CORS — adicionar headers de segurança
- **Problema:** Falta `Strict-Transport-Security`, `X-Content-Type-Options`, `X-Frame-Options` em produção.
- **Correção:** Configurar middleware de headers de segurança no Nginx ou Laravel.

### 7. Session cookie — SameSite e Secure
- **Arquivo:** `backend/.env:42`
- **Problema:** `SESSION_COOKIE` não define `SameSite=Lax/Strict` nem `Secure=true`.
- **Correção:** Configurar em `config/session.php` para produção.

### 8. Horizon — proteção por IP/email
- **Arquivo:** `backend/app/Providers/AppServiceProvider.php:57-73`
- **Problema:** `HORIZON_ADMIN_EMAILS` não configurado no `.env` — qualquer usuário autenticado pode acessar Horizon.
- **Correção:** Definir emails admin no `.env` de produção.

## BAIXO

### 9. Password hashing — verificar rounds
- **Problema:** `bcrypt()` usa rounds padrão (10). Em produção, considerar 12+.
- **Correção:** Configurar `BCRYPT_ROUNDS` no `.env` de produção.

### 10. YouTubeService — cache sem invalidação de segurança
- **Arquivo:** `backend/app/Services/YouTubeService.php:180-191`
- **Problema:** `cache()` helper global sempre existe; fallback nunca executa.
- **Correção:** Melhorar tratamento de erro do cache.

### 11. Logs — não logar tokens/senhas
- **Problema:** Alguns logs podem capturar headers com Authorization.
- **Correção:** Configurar `LogApiRequests` para sanitizar headers sensíveis.

---

**Última atualização:** 2026-06-23
**Status:** Pendente — implementar antes de produção
