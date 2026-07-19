# Secrets Rotation — StudyTrackPro

## Context

On 2026-06-25, a security review identified that the following secrets were exposed in the repository (committed in `backend/.env.example` and `backend/.env.production.example`):

- Google OAuth Client ID + Secret
- Discord OAuth Client ID + Secret + Bot Token
- YouTube Data API Key
- Bot API Secret

**These tokens are compromised and must be rotated before any deploy.**

---

## Prerequisites

- Google account (for YouTube/Google OAuth)
- Discord account with Developer Portal access
- Repository access to update `.env` files

---

## Step 1 — Google (YouTube + OAuth)

### 1.1 Access the Console
1. Go to [https://console.cloud.google.com/apis/credentials](https://console.cloud.google.com/apis/credentials)
2. Select the project associated with the app

### 1.2 Revoke Old OAuth Client
1. In **Credentials**, find the "OAuth 2.0 Client ID" with Client ID `356749384049-...`
2. Click on it → **Delete** (trash icon in the upper right)
3. Confirm deletion

### 1.3 Create New OAuth Client
1. Click **+ CREATE CREDENTIALS** → **OAuth client ID**
2. Application type: **Web application**
3. Name: `StudyTrackPro` (or similar)
4. **Authorized redirect URIs** → add:
   - `http://localhost:8080/api/v1/auth/google/callback` (dev)
   - `https://yourdomain.com/api/v1/auth/google/callback` (prod)
5. Click **Create**
6. Note the generated **Client ID** and **Client Secret**

### 1.4 Revoke Old API Key (YouTube)
1. In **Credentials**, find the API Key with value `AIzaSyCmVLRCyuVog...`
2. Click on it → **Delete**
3. Create new: **+ CREATE CREDENTIALS** → **API key**
4. Restrict the key in **API restrictions** → YouTube Data API v3
5. Note the new API Key

### 1.5 Update Consent Screen (If Needed)
1. Go to **OAuth consent screen**
2. Update redirect URIs if needed
3. Save

---

## Step 2 — Discord (OAuth + Bot)

### 2.1 Access the Developer Portal
1. Go to [https://discord.com/developers/applications](https://discord.com/developers/applications)
2. Find the app with Application ID `<OLD_DISCORD_APP_ID>`

### 2.2 Revoke Old App
1. Click on the app → **General** → **Delete Application** (button at the bottom)
2. Confirm with the app name

### 2.3 Create New App
1. Click **New Application**
2. Name: `StudyTrackPro` (or similar)
3. Note the new **Application ID**

### 2.4 Configure OAuth2
1. Go to **OAuth2** → **General**
2. **Redirects** → add:
   - `http://localhost:8080/api/v1/auth/discord/callback` (dev)
   - `https://yourdomain.com/api/v1/auth/discord/callback` (prod)
3. **Scopes**: check `identify`, `email`, `guilds`
4. Note the new **Client ID** and **Client Secret**

### 2.5 Create New Bot
1. Go to **Bot** → **Reset Token**
2. Note the new **Bot Token**
3. In **Privileged Gateway Intents**, enable:
   - ✅ MESSAGE CONTENT INTENT (if needed to read messages)
4. In **Bot Permissions**, check: `Send Messages`, `Read Message History`, `View Channels`

### 2.6 Invite Bot to Servers
1. Go to **OAuth2** → **URL Generator**
2. Scopes: `bot`
3. Bot Permissions: `Send Messages`, `Read Message History`, `View Channels`
4. Copy the generated URL and open it in a browser to invite the bot

---

## Step 3 — Update Environment Variables

### 3.1 Dev (`backend/.env`)

```bash
cd backend
nano .env
```

Replace:

```
GOOGLE_CLIENT_ID=<new-client-id>
GOOGLE_CLIENT_SECRET=<new-client-secret>

DISCORD_CLIENT_ID=<new-application-id>
DISCORD_CLIENT_SECRET=<new-client-secret>
DISCORD_BOT_TOKEN=<new-bot-token>

YOUTUBE_API_KEY=<new-api-key>

BOT_API_SECRET=<generate with: openssl rand -hex 32>
```

### 3.2 Production (`backend/.env.production`)

```bash
cd backend
cp .env.production.example .env.production
nano .env.production
```

Fill in ALL fields (don't leave any empty):

```
APP_NAME=StudyTrackPro
APP_ENV=production
APP_KEY=<generate with: php artisan key:generate>
APP_DEBUG=false
APP_URL=https://yourdomain.com

TRUSTED_PROXIES=*

DB_HOST=<database-host>
DB_DATABASE=<database-name>
DB_USERNAME=<user>
DB_PASSWORD=<strong-password>

REDIS_HOST=<redis-host>
REDIS_PASSWORD=<strong-password>

REVERB_APP_KEY=<generate with: openssl rand -hex 32>
REVERB_APP_SECRET=<generate with: openssl rand -hex 32>
REVERB_HOST=yourdomain.com
REVERB_PORT=443
REVERB_SCHEME=https

VITE_API_URL=https://yourdomain.com
VITE_REVERB_HOST=yourdomain.com
VITE_REVERB_PORT=443

SANCTUM_STATEFUL_DOMAINS=yourdomain.com
SESSION_DOMAIN=.yourdomain.com

CORS_ALLOWED_ORIGINS=https://app.yourdomain.com

GOOGLE_CLIENT_ID=<new-client-id>
GOOGLE_CLIENT_SECRET=<new-client-secret>
GOOGLE_REDIRECT_URI=https://yourdomain.com/api/v1/auth/google/callback

DISCORD_CLIENT_ID=<new-application-id>
DISCORD_CLIENT_SECRET=<new-client-secret>
DISCORD_REDIRECT_URI=https://yourdomain.com/api/v1/auth/discord/callback

FRONTEND_URL=https://app.yourdomain.com

YOUTUBE_API_KEY=<new-api-key>
```

### 3.3 Generate Strong Passwords

```bash
# Database password
openssl rand -base64 32

# Redis password
openssl rand -hex 32

# Reverb keys
openssl rand -hex 32  # repeat 2x (APP_KEY and APP_SECRET)

# Bot API Secret
openssl rand -hex 32
```

---

## Step 4 — Verify

### 4.1 Dev
```bash
docker compose up -d
# Access http://localhost:8080
# Test login with Google and Discord
```

### 4.2 Production
```bash
make prod-build
make prod-up
# Check logs: docker compose logs php-fpm
# Test login at https://yourdomain.com
```

### 4.3 Post-Rotation Checklist
- [ ] Google OAuth login works
- [ ] Discord OAuth login works
- [ ] YouTube search works
- [ ] Discord chat (bot messages) works
- [ ] No "invalid_client" or "unauthorized" errors in logs
- [ ] `.env.example` and `.env.production.example` contain only placeholders

---

## Troubleshooting

| Error | Cause | Solution |
|-------|-------|----------|
| `invalid_client` on Google | Wrong or revoked Client Secret | Verify you copied the correct Client Secret |
| `redirect_uri_mismatch` | Callback URI doesn't match | Add ALL URIs in Google/Discord Console |
| Discord bot not responding | Bot not in the server | Invite the bot via URL Generator |
| YouTube search 403 | Invalid API Key or quota exceeded | Check the API Key and enabled APIs |
| `BOT_API_SECRET` mismatch | Backend and bot use different secret | Use the same value in backend and bot |

---

## Notes

- The `BOT_API_SECRET` must be the same in the backend (`.env`) and in the Discord bot
- In dev, URIs use `http://localhost:8080` (nginx port)
- In prod, use `https://` with the real domain
- Never commit `.env` or `.env.production` — they are in `.gitignore`
