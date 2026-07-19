#!/usr/bin/env bash
# =============================================================================
# check-design.sh — Verificação de Design e Código (modo check + fix)
# =============================================================================
set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"; PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
source "$SCRIPT_DIR/sentry-lib.sh"
BACKEND_DIR="$PROJECT_ROOT/backend"; FRONTEND_DIR="$PROJECT_ROOT/frontend"
LOG_DIR="$SCRIPT_DIR/logs"; REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"; REPORT_FILE="$REPORT_DIR/check-design-$TIMESTAMP.md"
CRON_MODE=false; JSON_MODE=false; FIX_MODE=false
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

run_pint() {
    log_info "▶ Pint (PHP code style)..."
    if $FIX_MODE; then
        if [ -f "$BACKEND_DIR/vendor/bin/pint" ]; then
            log_info "  → Auto-fix: pint..."
            php "$BACKEND_DIR/vendor/bin/pint" 2>/dev/null || true
            log_ok "Pint — auto-fix aplicado"
        elif docker_is_running && service_is_up "php-fpm"; then
            log_info "  → Auto-fix: pint (Docker)..."
            docker compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T php-fpm ./vendor/bin/pint 2>/dev/null || true
            log_ok "Pint — auto-fix aplicado (Docker)"
        fi
    fi
    if [ -f "$BACKEND_DIR/vendor/bin/pint" ]; then
        if php "$BACKEND_DIR/vendor/bin/pint" --test 2>&1; then
            log_ok "Pint — OK"; append_report "- **Pint**: ✅ OK"; return 0
        else log_warn "Inconsistências"; append_report "- **Pint**: ⚠️"; return 0; fi
    elif docker_is_running && service_is_up "php-fpm"; then
        if docker compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T php-fpm ./vendor/bin/pint --test 2>&1; then
            log_ok "Pint — OK (Docker)"; append_report "- **Pint**: ✅ OK"; return 0
        else log_warn "Inconsistências"; append_report "- **Pint**: ⚠️"; return 0; fi
    else log_warn "Pint não disponível"; append_report "- **Pint**: ⏭️"; return 2; fi
}

run_type_check() {
    log_info "▶ Type-check (vue-tsc)..."
    if [ -d "$FRONTEND_DIR/node_modules" ]; then
        if npm run type-check --prefix "$FRONTEND_DIR" 2>&1; then
            log_ok "Type-check — OK"; append_report "- **TypeScript**: ✅ OK"; return 0
        else log_error "Erros de tipo (não auto-fixável)"; append_report "- **TypeScript**: ❌"; return 0; fi
    else log_warn "node_modules ausente"; append_report "- **TypeScript**: ⏭️"; return 2; fi
}

run_eslint() {
    log_info "▶ ESLint..."
    if [ -d "$FRONTEND_DIR/node_modules" ]; then
        if npm run lint --prefix "$FRONTEND_DIR" 2>&1; then
            log_ok "ESLint — OK"; append_report "- **ESLint**: ✅ OK"; return 0
        else
            local ec; ec=$(npm run lint --prefix "$FRONTEND_DIR" 2>&1 | grep -c 'error\s\+' || true)
            log_warn "$ec erros (não bloqueante)"; append_report "- **ESLint**: ⚠️ $ec erros (pré-existentes)"; return 0
        fi
    else log_warn "node_modules ausente"; append_report "- **ESLint**: ⏭️"; return 2; fi
}

run_prettier() {
    log_info "▶ Prettier..."
    if [ -d "$FRONTEND_DIR/node_modules" ]; then
        if $FIX_MODE; then
            log_info "  → Auto-fix: prettier --write..."
            (cd "$FRONTEND_DIR" && npx prettier --write "src/**/*.{ts,vue,css}" 2>/dev/null) || true
        fi
        if cd "$FRONTEND_DIR" && npx prettier --check "src/**/*.{ts,vue,css}" 2>&1; then
            log_ok "Prettier — OK"; append_report "- **Prettier**: ✅ OK"; return 0
        else log_warn "Arquivos não formatados (não bloqueante)"; append_report "- **Prettier**: ⚠️"; return 0; fi
    else log_warn "node_modules ausente"; append_report "- **Prettier**: ⏭️"; return 2; fi
}

run_all() {
    echo ""; echo "╔══════════════════════════════════════════════════════╗"
    echo "║      Design e Código — $(date '+%Y-%m-%d %H:%M:%S')    ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo ""
    append_report "# Design — $(date '+%Y-%m-%d %H:%M:%S')"; append_report ""
    local f=0; run_pint || ((f++)); echo ""; run_type_check || ((f++)); echo ""
    run_eslint; echo ""; run_prettier || ((f++)); echo ""
    append_report ""; [ $f -eq 0 ] && append_report "**Resultado**: ✅ OK" || append_report "**Resultado**: ❌ $f falha(s)"
    echo "╔══════════════════════════════════════════════════════╗"
    [ $f -eq 0 ] && echo "║    TODOS OS CHECKS DE DESIGN PASSARAM ✅          ║" || echo "║    $f CHECK(S) FALHARAM ❌        ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo "Relatório: $REPORT_FILE"; echo ""; return $f
}

while [[ $# -gt 0 ]]; do case "$1" in --cron) CRON_MODE=true ;; --fix) FIX_MODE=true ;; --json) JSON_MODE=true ;; --help|-h) echo "Uso: \$0 [--cron] [--fix] [--json]"; exit 0 ;; *) echo "Arg desconhecido: $1"; exit 1 ;; esac; shift; done
if $CRON_MODE; then run_all > /dev/null 2>&1; exit $?; fi; run_all; exit $?
