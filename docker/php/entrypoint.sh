#!/bin/sh
set -e

# Umask: garante que arquivos criados tenham permissão de grupo (rw)
# Evita "Failed to open stream: Permission denied" quando múltiplos containers
# (php-fpm, scheduler, horizon) compartilham o volume de storage
umask 0002

# Garante que os diretórios de cache existem e são graváveis pelo www-data
# (necessário porque o volume host sobrepõe as permissões do build)
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs

chown -R www-data:www-data \
  /var/www/html/bootstrap/cache \
  /var/www/html/storage

# Permissões de escrita para evitar "Failed to open stream: Permission denied"
# em logs rotacionados ou criados por processos diferentes (ex: scheduler, horizon)
chmod -R 775 /var/www/html/storage

# /tmp precisa ser gravável pelos workers www-data (Blade compiler usa tempnam)
chmod 1777 /tmp

# php-fpm master roda como root e faz spawn dos workers como www-data (via www.conf)
exec php-fpm
