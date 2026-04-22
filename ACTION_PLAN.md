# QUICK ACTION PLAN - DO THIS NOW

## 🔴 CRITICAL ACTIONS (Do Today/This Week)

### Action 1: Update redis:7-alpine [5 CRITICAL CVEs]
Status: redis container UP, 55 total CVEs

\\\ash
# Pull latest patched version
docker pull redis:latest-alpine

# Or use specific version
docker pull redis:7.2-alpine

# Update docker-compose
docker compose pull redis
docker compose up -d redis

# Verify vulnerabilities reduced
docker scout cves redis:latest-alpine --only-severity critical
\\\

---

### Action 2: Fix studytrackpro-node [4 CRITICAL CVEs]
Status: node container UP (unhealthy), 53 total CVEs

\\\ash
# 1. Edit your Dockerfile - Update Node.js base image
# Change this line:
#   FROM node:20-alpine
# To this:
#   FROM node:22-alpine

# 2. Add npm security audit to build
# Add this line in build stage:
#   RUN npm audit fix

# 3. Rebuild image
docker compose build --no-cache node

# 4. Restart container
docker compose up -d node

# 5. Verify
docker scout cves studytrackpro-node:latest --only-severity critical
\\\

---

### Action 3: Fix dpage/pgadmin4:latest [1 CRITICAL CVE]
Status: pgadmin container UP, 33 total CVEs

\\\ash
# 1. Edit docker-compose.yml
# Change:
#   image: dpage/pgadmin4:latest
# To:
#   image: dpage/pgadmin4:8.12

# 2. Update container
docker compose pull pgadmin4
docker compose up -d pgadmin4

# 3. Verify
docker scout cves dpage/pgadmin4:8.12 --only-severity critical
\\\

---

## 🟠 HIGH PRIORITY (This Month)

### Action 4: Update nginx:1.25-alpine [4 CRITICAL, 29 HIGH CVEs]

\\\ash
docker pull nginx:latest-alpine
docker compose build --no-cache nginx
docker compose up -d nginx
\\\

### Action 5: Update postgres:18-alpine [1 CRITICAL, 18 HIGH CVEs]

\\\ash
docker pull postgres:18-alpine
docker compose up -d postgres
\\\

### Action 6: Rebuild studytrackpro-php-fpm [17 HIGH CVEs]

\\\ash
docker compose build --no-cache php-fpm
docker compose up -d php-fpm
\\\

---

## 🟡 CLEANUP & PREVENTION (Next 60 days)

### Action 7: Remove old exited containers

\\\ash
# View exited containers
docker ps -a --filter "status=exited"

# Remove them
docker container prune

# Remove dangling images
docker image prune -a
\\\

### Action 8: Set up weekly scans

\\\ash
# Create a cron job or scheduled task to run weekly:
docker scout cves redis:latest-alpine --only-severity critical,high
docker scout cves studytrackpro-node:latest --only-severity critical,high
docker scout cves nginx:latest-alpine --only-severity critical,high
\\\

### Action 9: Update docker-compose.yml with version pinning

\\\yaml
services:
  redis:
    image: redis:7.2-alpine  # Pinned version instead of latest
  
  postgres:
    image: postgres:18-alpine
  
  nginx:
    image: nginx:1.27-alpine
  
  pgadmin:
    image: dpage/pgadmin4:8.12  # Pinned instead of latest
\\\

---

## VERIFICATION COMMANDS

\\\ash
# Check container status
docker ps -a

# Scan updated images
docker scout cves redis:latest-alpine
docker scout cves studytrackpro-node:latest --only-severity critical

# Compare before/after
docker scout cves redis:7-alpine --only-severity critical,high
docker scout cves redis:latest-alpine --only-severity critical,high
\\\

---

## TIMELINE

- **TODAY**: Read this and Action 1-3
- **THIS WEEK**: Complete Actions 1-3, start Action 4-6
- **THIS MONTH**: Complete all Actions 4-6
- **NEXT 60 DAYS**: Complete Actions 7-9

---

**Total Vulnerabilities Found**: 234+
- 🔴 CRITICAL: 12
- 🟠 HIGH: 127+
- 🟡 MEDIUM: 80+
- 🟢 LOW: 15+

**Next Step**: Start with Action 1 (redis) - should take 5 minutes
