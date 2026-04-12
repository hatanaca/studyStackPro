#!/bin/sh
set -e

CONF_FILE="/usr/local/etc/redis/redis.conf"
RUNTIME_CONF="/tmp/redis-runtime.conf"

cp "$CONF_FILE" "$RUNTIME_CONF"

if [ -n "$REDIS_PASSWORD" ]; then
    # printf %s evita que caracteres especiais (espaços, #, ;) corrompam o conf
    printf 'requirepass %s\n' "$REDIS_PASSWORD" >> "$RUNTIME_CONF"
fi

exec redis-server "$RUNTIME_CONF"
