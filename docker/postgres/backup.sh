#!/bin/sh
set -e

# Backup PostgreSQL — executa pg_dump e comprime.
# Uso: docker compose exec postgres /backup/backup.sh [backup_dir]
# Ou agendado via serviço separado no docker-compose.
#
# Variáveis de ambiente esperadas (definidas no docker-compose):
#   POSTGRES_DB, POSTGRES_USER, POSTGRES_PASSWORD, PGPASSWORD

BACKUP_DIR="${1:-/var/lib/postgresql/backups}"
DB_NAME="${POSTGRES_DB:-studytrack}"
DB_USER="${POSTGRES_USER:-studytrack}"
RETENTION_DAYS=7
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
FILENAME="${BACKUP_DIR}/${DB_NAME}_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

pg_dump -U "$DB_USER" -d "$DB_NAME" --no-owner --no-acl | gzip > "$FILENAME"

# Limpar backups mais antigos que RETENTION_DAYS
find "$BACKUP_DIR" -name "${DB_NAME}_*.sql.gz" -mtime "+${RETENTION_DAYS}" -delete

echo "Backup created: $FILENAME ($(du -h "$FILENAME" | cut -f1))"
echo "Retention: $RETENTION_DAYS days"
