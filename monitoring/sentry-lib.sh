# =============================================================================
# sentry-lib.sh — Funções compartilhadas de integração com Sentry
#
# Uso nos scripts:
#   source "$(dirname "$0")/sentry-lib.sh"
#   sentry_event "error" "check-security" "Falha no code scan"
#   sentry_report_pipeline "check-security" $? "Verificação de segurança"
# =============================================================================

SENTRY_LIB_LOADED=${SENTRY_LIB_LOADED:-false}
if ! $SENTRY_LIB_LOADED; then

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Carrega configuração Sentry
SENTRY_AUTH_TOKEN=""
SENTRY_ORG=""
SENTRY_PROJECT=""
if [ -f "$SCRIPT_DIR/.sentry.env" ]; then
    while IFS='=' read -r key val; do
        [ -z "$key" ] && continue
        case "$key" in
            SENTRY_AUTH_TOKEN) SENTRY_AUTH_TOKEN="$val" ;;
            SENTRY_ORG) SENTRY_ORG="$val" ;;
            SENTRY_PROJECT) SENTRY_PROJECT="$val" ;;
        esac
    done < "$SCRIPT_DIR/.sentry.env"
fi

# ─── Envia evento para o Sentry via API ──────────────────────────────────────
sentry_event() {
    local level="${1:-error}"      # fatal, error, warning, info, debug
    local logger="${2:-pipeline}"  # nome do componente
    local message="${3:-}"         # mensagem principal
    local extra_data="${4:-}"      # JSON adicional

    [ -z "$SENTRY_AUTH_TOKEN" ] && return 1
    [ -z "$SENTRY_ORG" ] && return 1
    [ -z "$SENTRY_PROJECT" ] && return 1
    [ -z "$message" ] && return 1

    local payload
    payload=$(cat << PAYEOF
{
  "event": {
    "message": "$message",
    "level": "$level",
    "logger": "$logger",
    "tags": {
      "pipeline": "true",
      "component": "$logger"
    },
    "extra": {
      "host": "$(hostname 2>/dev/null || echo 'unknown')",
      "timestamp": "$(date -u '+%Y-%m-%dT%H:%M:%SZ')",
      "working_dir": "$(pwd 2>/dev/null || echo 'unknown')",
      "data": $([ -n "$extra_data" ] && echo "$extra_data" || echo '{}')
    }
  }
}
PAYEOF
)

    curl -s -X POST "https://sentry.io/api/0/projects/${SENTRY_ORG}/${SENTRY_PROJECT}/events/" \
        -H "Authorization: Bearer $SENTRY_AUTH_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$payload" > /dev/null 2>&1 || true
}

# ─── Reporta resultado de um check ao Sentry ────────────────────────────────
sentry_report_check() {
    local check_name="$1"
    local exit_code="$2"
    local description="${3:-}"

    if [ "$exit_code" -eq 0 ]; then
        # Passou — loga como info
        sentry_event "info" "check-$check_name" "Pipeline OK: $description"
    else
        # Falhou — loga como error
        sentry_event "error" "check-$check_name" "Pipeline FAIL: $description (exit $exit_code)"
    fi
}

# ─── Reporta resultado geral da pipeline ao Sentry ──────────────────────────
sentry_report_pipeline() {
    local total="$1"
    local failed="$2"
    local summary="${3:-}"

    if [ "$failed" -eq 0 ]; then
        sentry_event "info" "pipeline" "Pipeline completa: $total/$total checks OK. $summary"
    elif [ "$failed" -lt "$total" ]; then
        sentry_event "warning" "pipeline" "Pipeline parcial: $failed/$total checks falharam. $summary"
    else
        sentry_event "error" "pipeline" "Pipeline falhou: $failed/$total checks falharam. $summary"
    fi
}

SENTRY_LIB_LOADED=true
fi
