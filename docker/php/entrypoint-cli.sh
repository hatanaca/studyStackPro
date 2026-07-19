#!/bin/sh
set -e

# Umask: garante que arquivos criados tenham permissão de grupo (rw)
# Evita conflitos de permissão quando múltiplos containers compartilham storage
umask 0002

# Garante que os diretórios de cache existem e são graváveis pelo www-data
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs

chown -R www-data:www-data \
  /var/www/html/bootstrap/cache \
  /var/www/html/storage

# Permissões de escrita para evitar "Failed to open stream: Permission denied"
# em logs rotacionados ou criados por outros processos
chmod -R 775 /var/www/html/storage

# Setgid: arquivos criados dentro de storage/logs herdam o grupo do diretório pai
# Isso evita conflitos entre containers php-fpm (Alpine) e CLI (Debian)
chmod g+s /var/www/html/storage/logs 2>/dev/null || true

# Aguarda Redis ficar pronto (LOADING ao iniciar com AOF grande)
# Evita RedisException: LOADING Redis is loading the dataset in memory
if [ -n "${REDIS_HOST:-}" ] && [ -n "${REDIS_PASSWORD:-}" ]; then
    echo "Aguardando Redis em ${REDIS_HOST}:${REDIS_PORT:-6379}..."
    i=1
    while [ $i -le 30 ]; do
        if redis-cli -h "${REDIS_HOST}" -p "${REDIS_PORT:-6379}" -a "${REDIS_PASSWORD}" ping 2>/dev/null | grep -q PONG; then
            echo "Redis pronto."
            break
        fi
        echo "Aguardando Redis... (tentativa $i/30)"
        i=$((i + 1))
        sleep 1
    done
fi

exec gosu www-data "$@"
