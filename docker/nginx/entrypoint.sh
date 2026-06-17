#!/bin/sh
# Entrypoint para Nginx/OpenResty: aguarda DNS resolver php-fpm e reverb antes de iniciar.
# Necessário porque o upstream estático do nginx resolve hostnames no parse da config,
# e o DNS interno do Docker (127.0.0.11) pode não estar pronto imediatamente.

set -e

MAX_RETRIES=30
RETRY_INTERVAL=2

for HOST in php-fpm reverb; do
    echo "Verificando DNS para $HOST..."
    for i in $(seq 1 $MAX_RETRIES); do
        if getent hosts "$HOST" > /dev/null 2>&1; then
            IP=$(getent hosts "$HOST" | awk '{ print $1 }')
            echo "OK: $HOST → $IP (tentativa $i)"
            break
        fi
        if [ "$i" -eq "$MAX_RETRIES" ]; then
            echo "ERRO: Não foi possível resolver $HOST após ${MAX_RETRIES} tentativas" >&2
            exit 1
        fi
        echo "   Aguardando DNS... (tentativa $i/$MAX_RETRIES)"
        sleep "$RETRY_INTERVAL"
    done
done

echo "Todos os hosts resolvidos. Iniciando nginx..."
exec /usr/local/openresty/bin/openresty -g "daemon off;"
