#!/usr/bin/env bash
# =============================================================================
# zap-scan.sh — Segurança DAST com OWASP ZAP contra a aplicação em execução
#
# Uso:
#   ./zap-scan.sh              # full scan contra http://localhost:8080
#   ./zap-scan.sh --baseline   # baseline scan (rápido, passivo)
#   ./zap-scan.sh URL          # scan contra URL customizada
#
# Requisitos: app no ar (make dev) e Docker disponível.
# Relatório: monitoring/reports/zap-report-<timestamp>.html
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"
REPORT_FILE="$REPORT_DIR/zap-report-$TIMESTAMP.html"
ZAP_IMAGE="${ZAP_IMAGE:-ghcr.io/zaproxy/zaproxy}"
TARGET="${1:-http://localhost:8080}"
SCAN_TYPE="full"

mkdir -p "$REPORT_DIR"

# Cores (desliga se não for terminal)
if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; NC=''
fi

log_info()  { echo -e "${CYAN}[$(date '+%H:%M:%S')]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

case "$1" in
    --baseline) SCAN_TYPE="baseline"; TARGET="${2:-http://localhost:8080}" ;;
    --help|-h)
        echo "Uso: $0 [--baseline] [URL]"
        echo ""
        echo "  --baseline  Scan baseline (rápido, passivo; padrão é full scan)"
        echo "  URL         Alvo do scan (padrão: http://localhost:8080)"
        exit 0
        ;;
esac

# 1. Docker disponível?
if ! docker info > /dev/null 2>&1; then
    log_error "Docker não está rodando. Inicie o Docker e tente novamente."
    exit 1
fi

# 2. App no ar?
if ! curl -sf --connect-timeout 5 -o /dev/null "$TARGET" 2>/dev/null; then
    log_error "Aplicação não responde em $TARGET. Suba com 'make dev' antes do scan."
    exit 1
fi
log_ok "Aplicação acessível em $TARGET"

# 3. Scan
log_info "▶ Scan $SCAN_TYPE do OWASP ZAP contra $TARGET (imagem: $ZAP_IMAGE)..."
log_info "  Pull da imagem na primeira execução pode demorar."

SCAN_SCRIPT="zap-full-scan.py"
[ "$SCAN_TYPE" = "baseline" ] && SCAN_SCRIPT="zap-baseline.py"

# O container ZAP roda em rede isolada: localhost dentro dele não é o host.
# Resolve o alvo para host.docker.internal (host-gateway) quando apontar para localhost.
TARGET_HOST="host.docker.internal"
case "$TARGET" in
    http://localhost*|http://127.0.0.1*)
        TARGET="${TARGET/http:\/\/localhost/http:\/\/$TARGET_HOST}"
        TARGET="${TARGET/http:\/\/127.0.0.1/http:\/\/$TARGET_HOST}"
        ;;
esac

if docker run --rm -t \
    --add-host=host.docker.internal:host-gateway \
    -v "$REPORT_DIR:/zap/wrk/:rw" \
    "$ZAP_IMAGE" "$SCAN_SCRIPT" \
    -t "$TARGET" \
    -r "$(basename "$REPORT_FILE")"; then
    log_ok "Scan concluído. Relatório: $REPORT_FILE"
    echo ""
    log_info "Alertas do scan (resumo ao final do output acima)."
    log_info "Abra o relatório HTML para detalhes: $REPORT_FILE"
    exit 0
else
    log_warn "Scan terminou com alertas (exit code != 0 é normal quando há achados)."
    log_warn "Relatório gerado: $REPORT_FILE"
    exit 1
fi
