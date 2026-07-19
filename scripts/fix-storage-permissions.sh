#!/bin/bash
# =============================================================================
# fix-storage-permissions.sh — Corrige permissões do storage no servidor Docker
#
# Uso: ssh ao servidor e execute:
#   curl -s https://raw.githubusercontent.com/.../fix-storage-permissions.sh | bash
#
# Ou copie para o servidor e execute com sudo.
# =============================================================================

set -euo pipefail

echo "Corrigindo permissões do storage nos containers..."

# Lista de containers que compartilham o volume backend
CONTAINERS=("php-fpm" "scheduler" "horizon" "reverb")

for container in "${CONTAINERS[@]}"; do
    if docker ps --format '{{.Names}}' | grep -q "^${container}$"; then
        echo "→ Container: ${container}"

        # Cria diretórios se não existirem
        docker exec "$container" mkdir -p /var/www/html/storage/logs
        docker exec "$container" mkdir -p /var/www/html/storage/framework/sessions
        docker exec "$container" mkdir -p /var/www/html/storage/framework/views
        docker exec "$container" mkdir -p /var/www/html/storage/framework/cache
        docker exec "$container" mkdir -p /var/www/html/bootstrap/cache

        # Ajusta proprietário e permissões
        docker exec "$container" chown -R www-data:www-data \
            /var/www/html/bootstrap/cache \
            /var/www/html/storage
        docker exec "$container" chmod -R 775 /var/www/html/storage

        echo "  ✓ Permissões corrigidas"
    else
        echo "  - Container ${container} não encontrado (pode não estar rodando)"
    fi
done

echo ""
echo "Permissões do storage corrigidas em todos os containers ativos."
