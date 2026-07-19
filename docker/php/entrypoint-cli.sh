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

exec gosu www-data "$@"
