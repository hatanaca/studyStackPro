#!/usr/bin/env bash
# =============================================================================
# run-all.sh — Executa todas as verificações periódicas e gera relatório
#
# Uso:
#   ./run-all.sh                          # executa tudo e mostra na tela
#   ./run-all.sh --slack-webhook URL      # com notificações Slack
#   ./run-all.sh --export relatorio.md    # exporta relatório consolidado
#   ./run-all.sh --cron                   # modo silencioso para cron
# =============================================================================

set -uo pipefail

APP_URL="${APP_URL:-http://177.112.223.72:5173}"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
LOG_DIR="$SCRIPT_DIR/logs"
REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"
REPORT_FILE=""
SLACK_WEBHOOK=""
CRON_MODE=false
EXIT_CODE=0

mkdir -p "$LOG_DIR" "$REPORT_DIR"

if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; BOLD=''; NC=''
fi

log_info()  { echo -e "${CYAN}[$(date '+%H:%M:%S')]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

send_slack() {
    local message="$1"
    if [ -n "$SLACK_WEBHOOK" ]; then
        curl -s -X POST "$SLACK_WEBHOOK" -H "Content-Type: application/json" \
            -d "{\"attachments\":[{\"color\":\"$2\",\"text\":\"$message\",\"ts\":$(date +%s)}]}" \
            > /dev/null 2>&1 || true
    fi
}

# ═════════════════════════════════════════════════════════════════════

echo ""
echo -e "${CYAN}╔══════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║        StudyTrack Pro — Monitoramento Periódico     ║${NC}"
echo -e "${CYAN}║        $(date '+%Y-%m-%d %H:%M:%S')            ║${NC}"
echo -e "${CYAN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

# ─── 1. Health Check ───────────────────────────────────────────────

log_info "▶ Fase 1/3: Health Check..."
echo ""

if $CRON_MODE; then
    bash "$SCRIPT_DIR/health-check.sh" --cron 2>&1 || EXIT_CODE=1
else
    bash "$SCRIPT_DIR/health-check.sh" 2>&1 || EXIT_CODE=1
fi

echo ""
echo "───────────────────────────────────────────────────────────"
echo ""

# ─── 2. Code Scan ─────────────────────────────────────────────────

log_info "▶ Fase 2/3: Code Scan..."
echo ""

SCAN_ARGS=""
if $CRON_MODE; then SCAN_ARGS+=" --cron"; fi

# Temporário: exporta relatório parcial
SCAN_REPORT="$REPORT_DIR/code-scan-$TIMESTAMP.md"
SCAN_ARGS+=" --export $SCAN_REPORT"

if bash "$SCRIPT_DIR/code-scan.sh" $SCAN_ARGS 2>&1; then
    log_ok "Code Scan concluído"
else
    log_error "Code Scan encontrou problemas"
    EXIT_CODE=1
fi

echo ""
echo "───────────────────────────────────────────────────────────"
echo ""

# ─── 3. Sentry Check ──────────────────────────────────────────────

log_info "▶ Fase 3/3: Sentry Monitor..."
echo ""

if [ -f "$SCRIPT_DIR/.sentry.env" ]; then
    source "$SCRIPT_DIR/.sentry.env"
fi

if [ -n "${SENTRY_AUTH_TOKEN:-}" ]; then
    SENTRY_ARGS=""
    if [ -n "${SENTRY_PROJECT:-}" ]; then SENTRY_ARGS+=" --project $SENTRY_PROJECT"; fi
    if [ -n "$SLACK_WEBHOOK" ]; then SENTRY_ARGS+=" --slack-webhook $SLACK_WEBHOOK"; fi
    if $CRON_MODE; then SENTRY_ARGS+=" --cron"; fi

    if bash "$SCRIPT_DIR/sentry-check.sh" $SENTRY_ARGS 2>&1; then
        log_ok "Sentry Monitor concluído"
    else
        log_error "Sentry Monitor encontrou erros"
        EXIT_CODE=1
    fi
else
    log_info "Sentry Monitor pulado (SENTRY_AUTH_TOKEN não configurado)"
    log_info "Para configurar: veja ./sentry-check.sh --setup-cron"
fi

echo ""
echo "───────────────────────────────────────────────────────────"
echo ""

# ─── Relatório Final ──────────────────────────────────────────────

SUMMARY_REPORT="$REPORT_DIR/summary-$TIMESTAMP.md"
{
    echo "# Relatório de Monitoramento — $(date '+%Y-%m-%d %H:%M:%S')"
    echo ""
    echo "## Status"
    echo ""
    if [ $EXIT_CODE -eq 0 ]; then
        echo "✅ **Todas as verificações passaram**"
    else
        echo "❌ **Algumas verificações falharam**"
    fi
    echo ""
    echo "## Verificações Executadas"
    echo ""
    echo "1. **Health Check** — Verificação de disponibilidade da aplicação"
    echo "2. **Code Scan** — Análise estática do código fonte"
    echo "3. **Sentry Monitor** — Consulta de erros recentes no Sentry"
    echo ""
    echo "## Detalhes"
    echo ""
    echo "- **Timestamp:** $(date '+%Y-%m-%d %H:%M:%S')"
    echo "- **Host:** $APP_URL"
    echo "- **Código de saída:** $EXIT_CODE"
    echo ""
} > "$SUMMARY_REPORT"

if [ -n "$REPORT_FILE" ]; then
    cp "$SUMMARY_REPORT" "$REPORT_FILE"
fi

if [ $EXIT_CODE -eq 0 ]; then
    log_ok "╔══════════════════════════════════════════════╗"
    log_ok "║  TODAS AS VERIFICAÇÕES PASSARAM ✅          ║"
    log_ok "╚══════════════════════════════════════════════╝"
    send_slack "✅ *Monitoramento StudyTrack Pro*\nTodas as verificações passaram.\n$(date '+%Y-%m-%d %H:%M:%S')" "good"
else
    log_error "╔══════════════════════════════════════════════╗"
    log_error "║  ALGUMAS VERIFICAÇÕES FALHARAM ❌         ║"
    log_error "╚══════════════════════════════════════════════╝"
    send_slack "❌ *Monitoramento StudyTrack Pro*\nAlgumas verificações falharam.\nVer relatório: $SUMMARY_REPORT" "danger"
fi

echo ""
log_info "Relatório salvo: $SUMMARY_REPORT"

exit $EXIT_CODE
