#!/usr/bin/env bash
# =============================================================================
# check-sentry.sh — Verificação de Erros no Sentry
#
# Consulta a API do Sentry para erros recentes e reporta no pipeline.
# Se houver erros críticos, o check falha (não-bloqueante para a pipeline).
#
# Uso: ./check-sentry.sh [--cron] [--fix] [--json] [--help]
# =============================================================================

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_DIR="$SCRIPT_DIR/logs"
REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"
REPORT_FILE="$REPORT_DIR/check-sentry-$TIMESTAMP.md"

source "$SCRIPT_DIR/sentry-lib.sh"

CRON_MODE=false; JSON_MODE=false; FIX_MODE=false
mkdir -p "$LOG_DIR" "$REPORT_DIR"

if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
else RED=''; GREEN=''; YELLOW=''; CYAN=''; NC=''; fi

log_info()  { echo -e "${CYAN}[$(date '+%H:%M:%S')]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
append_report() { echo "$1" >> "$REPORT_FILE"; }

check_sentry_errors() {
    local result=0

    if [ -z "$SENTRY_AUTH_TOKEN" ] || [ -z "$SENTRY_ORG" ]; then
        log_info "Sentry não configurado — pulando verificação"
        append_report "- **Sentry Monitor**: ⏭️ Não configurado"
        return 2
    fi

    log_info "▶ Consultando Sentry (projeto: ${SENTRY_PROJECT:-todos})..."

    # Usa o script sentry-check.sh existente
    if bash "$SCRIPT_DIR/sentry-check.sh" --cron 2>&1; then
        log_ok "Sentry — sem erros críticos"
        append_report "- **Sentry Monitor**: ✅ OK"
    else
        local error_count
        error_count=$(grep -c 'erros encontrados' "$LOG_DIR/sentry.log" 2>/dev/null || echo 0)
        log_warn "Sentry — $error_count projeto(s) com erros"
        append_report "- **Sentry Monitor**: ⚠️ $error_count projeto(s) com erros"

        # Reporta ao Sentry (auto-referência)
        sentry_event "info" "check-sentry" "Pipeline detectou erros no Sentry: $error_count projetos"

        return 1
    fi

    return $result
}

run_all() {
    echo ""
    echo "╔══════════════════════════════════════════════════════╗"
    echo "║        Sentry — $(date '+%Y-%m-%d %H:%M:%S')          ║"
    echo "╚══════════════════════════════════════════════════════╝"
    echo ""

    append_report "# Sentry Monitor — $(date '+%Y-%m-%d %H:%M:%S')"
    append_report ""

    check_sentry_errors
    local exit_code=$?

    append_report ""
    [ $exit_code -eq 0 ] && append_report "**Resultado**: ✅ OK" || append_report "**Resultado**: ⚠️ Erros detectados"

    echo "╔══════════════════════════════════════════════════════╗"
    [ $exit_code -eq 0 ] && echo "║       SENTRY — OK ✅                            ║" || echo "║       SENTRY — ERROS DETECTADOS ⚠️               ║"
    echo "╚══════════════════════════════════════════════════════╝"
    echo "Relatório: $REPORT_FILE"
    echo ""

    return $exit_code
}

while [[ $# -gt 0 ]]; do case "$1" in --cron) CRON_MODE=true ;; --fix) FIX_MODE=true ;; --json) JSON_MODE=true ;; --help|-h) echo "Uso: \$0 [--cron] [--fix] [--json]"; exit 0 ;; *) echo "Arg desconhecido: $1"; exit 1 ;; esac; shift; done
if $CRON_MODE; then run_all > /dev/null 2>&1; exit $?; fi; run_all; exit $?
