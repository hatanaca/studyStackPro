#!/usr/bin/env bash
# =============================================================================
# check-tests.sh — Suíte Completa de Testes
#
# Executa: 1. Backend tests (PHPUnit)  2. Frontend tests (Vitest)  3. Coverage
#
# Uso: ./check-tests.sh [--cron] [--json] [--skip-backend] [--skip-frontend] [--help]
# =============================================================================

set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"; PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"; FRONTEND_DIR="$PROJECT_ROOT/frontend"
LOG_DIR="$SCRIPT_DIR/logs"; REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"; REPORT_FILE="$REPORT_DIR/check-tests-$TIMESTAMP.md"
CRON_MODE=false; JSON_MODE=false; SKIP_BACKEND=false; SKIP_FRONTEND=false
mkdir -p "$LOG_DIR" "$REPORT_DIR"
if [ -t 1 ]; then RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
else RED=''; GREEN=''; YELLOW=''; CYAN=''; NC=''; fi
log_info() { echo -e "${CYAN}[$(date '+%H:%M:%S')]${NC} $1"; }
log_ok() { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn() { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
docker_is_running() { docker info > /dev/null 2>&1; }
service_is_up() { local s="$1"; docker compose -f "$PROJECT_ROOT/docker-compose.yml" ps --services --filter "status=running" 2>/dev/null | grep -q "$s"; }
append_report() { echo "$1" >> "$REPORT_FILE"; }

# ─── 1. Backend Tests ─────────────────────────────────────────────────────────
run_backend_tests() {
    log_info "▶ Testes do Backend..."
    if $SKIP_BACKEND; then log_info "Pulado"; append_report "- **Backend**: ⏭️"; return 2; fi
    if docker_is_running && service_is_up "php-fpm" && service_is_up "postgres" && service_is_up "redis"; then
        if docker compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T php-fpm php artisan test --coverage 2>&1; then
            log_ok "Backend — passou"; append_report "- **Backend Tests**: ✅ OK"; return 0
        else log_error "Backend — falhou"; append_report "- **Backend Tests**: ❌"; return 1; fi
    elif [ -d "$BACKEND_DIR/vendor" ] && [ -f "$BACKEND_DIR/.env" ]; then
        log_info "PHPUnit direto..."; log_warn "Sem Docker"
        if php "$BACKEND_DIR/artisan" test --coverage 2>&1; then
            log_ok "Backend — passou"; append_report "- **Backend Tests**: ✅ OK (local)"; return 0
        else log_error "Backend — falhou"; append_report "- **Backend Tests**: ❌"; return 1; fi
    else log_warn "Backend indisponível"; append_report "- **Backend Tests**: ⏭️"; return 2; fi
}

# ─── 2. Frontend Tests ────────────────────────────────────────────────────────
run_frontend_tests() {
    log_info "▶ Testes do Frontend..."
    if $SKIP_FRONTEND; then log_info "Pulado"; append_report "- **Frontend**: ⏭️"; return 2; fi
    if [ -d "$FRONTEND_DIR/node_modules" ]; then
        if npm run test:run --prefix "$FRONTEND_DIR" 2>&1; then
            log_ok "Frontend — passou"; append_report "- **Frontend Tests**: ✅ OK"; return 0
        else log_error "Frontend — falhou"; append_report "- **Frontend Tests**: ❌"; return 1; fi
    else log_warn "node_modules ausente"; append_report "- **Frontend Tests**: ⏭️"; return 2; fi
}

# ─── 3. Coverage ──────────────────────────────────────────────────────────────
check_coverage() {
    log_info "▶ Cobertura..."; local found=false
    for cf in "$BACKEND_DIR/coverage.xml" "$FRONTEND_DIR/coverage/coverage-final.json"; do
        [ -f "$cf" ] && { found=true; local s; s=$(wc -c < "$cf"); log_ok "  $(basename "$cf") — ${s}B"; append_report "- **Coverage**: $(basename "$cf") — ${s}B"; }
    done
    $found && return 0 || { log_info "Nenhum relatório"; append_report "- **Coverage**: ⚠️ Ausente"; return 1; }
}

# ─── Main ─────────────────────────────────────────────────────────────────────
run_all() {
    echo ""; echo "╔══════════════════════════════════════════════════════╗"
    echo "║        Testes — $(date '+%Y-%m-%d %H:%M:%S')           ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo ""
    append_report "# Testes — $(date '+%Y-%m-%d %H:%M:%S')"; append_report ""
    local f=0; run_backend_tests || ((f++)); echo ""; run_frontend_tests || ((f++)); echo ""
    check_coverage || true; echo ""
    append_report ""; [ $f -eq 0 ] && append_report "**Resultado**: ✅ OK" || append_report "**Resultado**: ❌ $f falha(s)"
    echo "╔══════════════════════════════════════════════════════╗"
    [ $f -eq 0 ] && echo "║      TODOS OS TESTES PASSARAM ✅                 ║" || echo "║      $f SUÍTE(S) FALHARAM ❌                ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo "Relatório: $REPORT_FILE"; echo ""; return $f
}

while [[ $# -gt 0 ]]; do case "$1" in --cron) CRON_MODE=true ;; --json) JSON_MODE=true ;; --skip-backend) SKIP_BACKEND=true ;; --skip-frontend) SKIP_FRONTEND=true ;; --help|-h) echo "Uso: $0 [--cron] [--json] [--skip-backend] [--skip-frontend]"; exit 0 ;; *) echo "Arg desconhecido: $1"; exit 1 ;; esac; shift; done
if $CRON_MODE; then run_all > /dev/null 2>&1; exit $?; fi; run_all; exit $?
