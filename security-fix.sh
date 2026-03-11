#!/bin/bash
# StudyTrackPro Security Quick-Fix Script
# Run this BEFORE production deployment

echo "🔐 StudyTrackPro Security Configuration..."

# 1. Disable CORS Wildcard
echo "1️⃣ Fixing CORS configuration..."
sed -i "s/: \['\\*'\]/: []/g" backend/config/cors.php
echo "   ✅ CORS wildcard disabled"

# 2. Set token expiration
echo "2️⃣ Setting Sanctum token expiration..."
sed -i "s/'expiration' => null,/'expiration' => 1440,  \/\/ 24 hours/g" backend/config/sanctum.php
echo "   ✅ Token expiration set to 24 hours"

# 3. Generate secure secrets
echo "3️⃣ Generating secure secrets..."
APP_KEY=\IwWcH3hm17DBj8Yyen5obncP9IL5uC8akR4XSoQ6tbs=
REVERB_KEY=\
REVERB_SECRET=\
DB_PASSWORD=\
REDIS_PASSWORD=\

# Update .env.production
echo "   Creating .env.production with secure values..."
cat > backend/.env.production << EOF
APP_NAME=StudyTrackPro
APP_ENV=production
APP_KEY=base64:\
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=studytrack
DB_USERNAME=studytrack
DB_PASSWORD=\

REDIS_HOST=redis
REDIS_PASSWORD=\
REDIS_PORT=6379

CACHE_STORE=redis
CACHE_DATABASE=1
QUEUE_CONNECTION=redis
REDIS_QUEUE_DATABASE=2
SESSION_DRIVER=redis
REDIS_SESSION_DATABASE=3
REDIS_HORIZON_DATABASE=4

REVERB_APP_ID=studytrack
REVERB_APP_KEY=\
REVERB_APP_SECRET=\
REVERB_HOST=reverb
REVERB_PORT=8080
REVERB_SCHEME=wss

SANCTUM_STATEFUL_DOMAINS=yourdomain.com,app.yourdomain.com
CORS_ALLOWED_ORIGINS=https://yourdomain.com,https://app.yourdomain.com
HORIZON_ADMIN_EMAILS=admin@yourdomain.com
EOF

echo "   ✅ Secure .env.production created"
echo ""
echo "4️⃣ Required manual steps:"
echo "   • Review backend/.env.production"
echo "   • Update APP_URL, VITE_API_URL with your domain"
echo "   • Set CORS_ALLOWED_ORIGINS to your frontend domain"
echo "   • Set HORIZON_ADMIN_EMAILS to admin email(s)"
echo "   • Enable HTTPS in Nginx with SSL certificate"
echo "   • Add security headers to Nginx"
echo ""
echo "✅ Quick-fix complete!"
