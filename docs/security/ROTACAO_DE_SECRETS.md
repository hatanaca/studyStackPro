# Rotação de Secrets — StudyTrackPro

## Contexto

Em 2026-06-25, uma revisão de segurança identificou que os seguintes secrets estavam expostos no repositório (commitados em `backend/.env.example` e `backend/.env.production.example`):

- Google OAuth Client ID + Secret
- Discord OAuth Client ID + Secret + Bot Token
- YouTube Data API Key
- Bot API Secret

**Estes tokens estão comprometidos e devem ser rotacionados antes de qualquer deploy.**

---

## Pré-requisitos

- Conta Google (para YouTube/Google OAuth)
- Conta Discord com acesso ao Developer Portal
- Acesso ao repositório para atualizar os `.env`

---

## Passo 1 — Google (YouTube + OAuth)

### 1.1 Acessar o Console
1. Acesse [https://console.cloud.google.com/apis/credentials](https://console.cloud.google.com/apis/credentials)
2. Selecione o projeto associado ao app

### 1.2 Revogar OAuth Client antigo
1. Em **Credentials**, localize o "OAuth 2.0 Client ID" com Client ID `356749384049-...`
2. Clique nele → **Delete** (lixeira no canto superior)
3. Confirme a exclusão

### 1.3 Criar novo OAuth Client
1. Clique em **+ CREATE CREDENTIALS** → **OAuth client ID**
2. Application type: **Web application**
3. Nome: `StudyTrackPro` (ou similar)
4. **Authorized redirect URIs** → adicione:
   - `http://localhost:8080/api/v1/auth/google/callback` (dev)
   - `https://seu-dominio.com/api/v1/auth/google/callback` (prod)
5. Clique em **Create**
6. Anote o **Client ID** e **Client Secret** gerados

### 1.4 Revogar API Key antiga (YouTube)
1. Em **Credentials**, localize a API Key com valor `AIzaSyCmVLRCyuVog...`
2. Clique nela → **Delete**
3. Crie nova: **+ CREATE CREDENTIALS** → **API key**
4. Restrinja a key em **API restrictions** → YouTube Data API v3
5. Anote a nova API Key

### 1.5 Atualizar consent screen (se necessário)
1. Vai em **OAuth consent screen**
2. Atualize os URIs de redirecionamento se necessário
3. Salve

---

## Passo 2 — Discord (OAuth + Bot)

### 2.1 Acessar o Developer Portal
1. Acesse [https://discord.com/developers/applications](https://discord.com/developers/applications)
2. Localize o app com Application ID `<OLD_DISCORD_APP_ID>`

### 2.2 Revogar app antigo
1. Clique no app → **General** → **Delete Application** (botão no final)
2. Confirme com o nome do app

### 2.3 Criar novo app
1. Clique em **New Application**
2. Nome: `StudyTrackPro` (ou similar)
3. Anote o novo **Application ID**

### 2.4 Configurar OAuth2
1. Vai em **OAuth2** → **General**
2. **Redirects** → adicione:
   - `http://localhost:8080/api/v1/auth/discord/callback` (dev)
   - `https://seu-dominio.com/api/v1/auth/discord/callback` (prod)
3. **Scopes**: marque `identify`, `email`, `guilds`
4. Anote o novo **Client ID** e **Client Secret**

### 2.5 Criar novo Bot
1. Vai em **Bot** → **Reset Token**
2. Anote o novo **Bot Token**
3. Em **Privileged Gateway Intents**, ative:
   - ✅ MESSAGE CONTENT INTENT (se necessário para ler mensagens)
4. Em **Bot Permissions**, marque: `Send Messages`, `Read Message History`, `View Channels`

### 2.6 Convidar o bot para servidores
1. Vai em **OAuth2** → **URL Generator**
2. Scopes: `bot`
3. Bot Permissions: `Send Messages`, `Read Message History`, `View Channels`
4. Copie a URL gerada e abra no navegador para convidar o bot

---

## Passo 3 — Atualizar variáveis de ambiente

### 3.1 Dev (`backend/.env`)

```bash
cd backend
nano .env
```

Substitua:

```
GOOGLE_CLIENT_ID=<novo-client-id>
GOOGLE_CLIENT_SECRET=<novo-client-secret>

DISCORD_CLIENT_ID=<novo-application-id>
DISCORD_CLIENT_SECRET=<novo-client-secret>
DISCORD_BOT_TOKEN=<novo-bot-token>

YOUTUBE_API_KEY=<nova-api-key>

BOT_API_SECRET=<gere-novo-com: openssl rand -hex 32>
```

### 3.2 Produção (`backend/.env.production`)

```bash
cd backend
cp .env.production.example .env.production
nano .env.production
```

Preencha TODOS os campos (não deixe nenhum vazio):

```
APP_NAME=StudyTrackPro
APP_ENV=production
APP_KEY=<gere-com: php artisan key:generate>
APP_DEBUG=false
APP_URL=https://seu-dominio.com

TRUSTED_PROXIES=*

DB_HOST=<host-do-banco>
DB_DATABASE=<nome-do-banco>
DB_USERNAME=<usuario>
DB_PASSWORD=<senha-forte>

REDIS_HOST=<host-do-redis>
REDIS_PASSWORD=<senha-forte>

REVERB_APP_KEY=<gere-com: openssl rand -hex 32>
REVERB_APP_SECRET=<gere-com: openssl rand -hex 32>
REVERB_HOST=seu-dominio.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_API_URL=https://seu-dominio.com
VITE_REVERB_HOST=seu-dominio.com
VITE_REVERB_PORT=443

SANCTUM_STATEFUL_DOMAINS=seu-dominio.com
SESSION_DOMAIN=.seu-dominio.com

CORS_ALLOWED_ORIGINS=https://app.seu-dominio.com

GOOGLE_CLIENT_ID=<novo-client-id>
GOOGLE_CLIENT_SECRET=<novo-client-secret>
GOOGLE_REDIRECT_URI=https://seu-dominio.com/api/v1/auth/google/callback

DISCORD_CLIENT_ID=<novo-application-id>
DISCORD_CLIENT_SECRET=<novo-client-secret>
DISCORD_REDIRECT_URI=https://seu-dominio.com/api/v1/auth/discord/callback

FRONTEND_URL=https://app.seu-dominio.com

YOUTUBE_API_KEY=<nova-api-key>
```

### 3.3 Gerar senhas fortes

```bash
# Senha do banco
openssl rand -base64 32

# Senha do Redis
openssl rand -hex 32

# Reverb keys
openssl rand -hex 32  # repita 2x (APP_KEY e APP_SECRET)

# Bot API Secret
openssl rand -hex 32
```

---

## Passo 4 — Verificar

### 4.1 Dev
```bash
docker compose up -d
# Acesse http://localhost:8080
# Teste login com Google e Discord
```

### 4.2 Produção
```bash
make prod-build
make prod-up
# Verifique logs: docker compose logs php-fpm
# Teste login em https://seu-dominio.com
```

### 4.3 Checklist pós-rotação
- [ ] Google OAuth login funciona
- [ ] Discord OAuth login funciona
- [ ] YouTube search funciona
- [ ] Discord chat (bot mensagens) funciona
- [ ] Nenhum erro de "invalid_client" ou "unauthorized" nos logs
- [ ] `.env.example` e `.env.production.example` contêm apenas placeholders

---

## Troubleshooting

| Erro | Causa | Solução |
|------|-------|---------|
| `invalid_client` no Google | Client Secret errado ou revogado | Verifique se copiou o Client Secret correto |
| `redirect_uri_mismatch` | URI de callback não confere | Adicione TODOS os URIs no Google/Discord Console |
| Discord bot não responde | Bot não está no servidor | Convide o bot via URL Generator |
| YouTube search 403 | API Key inválida ou quota excedida | Verifique a API Key e as APIs habilitadas |
| `BOT_API_SECRET` mismatch | Backend e bot usam secret diferente | Use o mesmo valor no backend e no bot |

---

## Notas

- O `BOT_API_SECRET` deve ser o mesmo no backend (`.env`) e no bot Discord
- Em dev, os URIs usam `http://localhost:8080` (porta do nginx)
- Em prod, use `https://` com o domínio real
- Nunca commite os `.env` ou `.env.production` — estão no `.gitignore`
