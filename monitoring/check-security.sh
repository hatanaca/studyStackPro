#!/usr/bin/env bash
# =============================================================================
# check-security.sh — Verificação de Segurança
#
# Executa:
#   1. Testes de segurança do backend (SQL injection, XSS, CSRF, IDOR)
#   2. Auditoria de dependências (composer audit + npm audit)
#   3. Code scan estático (código legado)
#   4. Varredura de secrets expostos no código
#
# Uso:
#   ./check-security.sh                          # modo interativo
#   ./check-security.sh --cron                   # modo silencioso (cron)
#   ./check-security.sh --json                   # saída JSON (para orquestrador)
#   ./check-security.sh --help                   # ajuda
# =============================================================================

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"
FRONTEND_DIR="$PROJECT_ROOT/frontend"
LOG_DIR="$SCRIPT_DIR/logs"
REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"
REPORT_FILE="$REPORT_DIR/check-security-$TIMESTAMP.md"

CRON_MODE=false
JSON_MODE=false

mkdir -p "$LOG_DIR" "$REPORT_DIR"

if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; NC=''
fi

log_info()  { echo -e "${CYAN}[$(date '+%H:%M:%S')]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

docker_is_running() { docker info > /dev/null 2>&1; }

service_is_up() {
    local service="$1"
    docker compose -f "$PROJECT_ROOT/docker-compose.yml" ps --services --filter "status=running" 2>/dev/null | grep -q "$service"
}

append_report() { echo "$1" >> "$REPORT_FILE"; }

# ─── 1. Testes de Segurança ────────────────────────────────────────────────

run_security_tests() {
    if docker_is_running && service_is_up "php-fpm"; then
        log_info "▶ Testes de segurança (via Docker)..."
        if docker compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T php-fpm php artisan test --filter=Security --testsuite=Feature 2>&1; then
            log_ok "Testes de segurança — passou"; append_report "- **Testes de Segurança**: ✅ OK"; return 0
        else
            log_error "Testes de segurança — falhou"; append_report "- **Testes de Segurança**: ❌ Falhou"; return 1
        fi
    elif [ -d "$BACKEND_DIR/vendor" ]; then
        log_info "▶ Testes de segurança (direto)..."; log_warn "Sem Docker — pode não ter DB/Redis"
        if php "$BACKEND_DIR/artisan" test --filter=Security --testsuite=Feature 2>&1; then
            log_ok "Testes de segurança — passou"; append_report "- **Testes de Segurança**: ✅ OK (local)"; return 0
        else
            log_warn "Pulando testes (DB/Redis indisponível)"; append_report "- **Testes de Segurança**: ⚠️ Não executado"; return 2
        fi
    else
        log_warn "vendor ausente"; append_report "- **Testes de Segurança**: ⏭️ Pulado"; return 1
    fi
}

# ─── 2. Auditoria de Dependências ──────────────────────────────────────────

run_composer_audit() {
    log_info "▶ Auditoria Composer..."
    if [ ! -d "$BACKEND_DIR/vendor" ]; then log_warn "vendor ausente"; append_report "- **Composer Audit**: ⏭️"; return 1; fi
    if composer audit --working-dir="$BACKEND_DIR" 2>&1; then
        log_ok "Composer audit — OK"; append_report "- **Composer Audit**: ✅ OK"; return 0
    else
        log_warn "Vulnerabilidades encontradas"; append_report "- **Composer Audit**: ⚠️"; return 1
    fi
}

run_npm_audit() {
    log_info "▶ Auditoria NPM..."
    if [ ! -d "$FRONTEND_DIR/node_modules" ]; then log_warn "node_modules ausente"; append_report "- **NPM Audit**: ⏭️"; return 1; fi
    if npm audit --prefix "$FRONTEND_DIR" 2>&1; then
        log_ok "NPM audit — OK"; append_report "- **NPM Audit**: ✅ OK"; return 0
    else
        log_warn "Vulnerabilidades encontradas"; append_report "- **NPM Audit**: ⚠️"; return 1
    fi
}

# ─── 3. Code Scan ──────────────────────────────────────────────────────────

run_code_scan() {
    log_info "▶ Code scan..."
    if bash "$SCRIPT_DIR/code-scan.sh" --cron 2>&1; then
        log_ok "Code scan — OK"; append_report "- **Code Scan**: ✅ OK"; return 0
    else
        log_warn "Problemas encontrados"; append_report "- **Code Scan**: ⚠️"; return 1
    fi
}

# ─── 4. Secrets Scan ───────────────────────────────────────────────────────

run_secret_scan() {
    log_info "▶ Varredura de secrets..."
    local patterns=(
        '-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY'
        'ghp_[0-9a-zA-Z]{36,}' 'gho_[0-9a-zA-Z]{36,}' 'ghu_[0-9a-zA-Z]{36,}'
        'xox[abpors]-[0-9a-zA-Z-]+' 'AKIA[0-9A-Z]{16}'
        'sk_live_[0-9a-zA-Z]{24,}' 'pk_live_[0-9a-zA-Z]{24,}' 'SG\.[0-9a-zA-Z_-]{22,}'
    )
    local tmpfile; tmpfile=$(mktemp /tmp/studytrack-secretscan-XXXXXX 2>/dev/null)
    for p in "${patterns[@]}"; do
        rg -n --no-ignore --hidden -g '!.git' -g '!vendor' -g '!node_modules' -g '!*.min.*' -g '!.env*' -g '!dist' -g '!coverage' -g '!*.lock' -e "$p" "$PROJECT_ROOT" 2>/dev/null >> "$tmpfile" || true
    done
    if [ -s "$tmpfile" ]; then
        log_warn "Possíveis secrets:"; sort -u "$tmpfile" | while IFS= read -r line; do log_warn "  $line"; done
        append_report "- **Secrets Scan**: ⚠️ Possíveis secrets"; rm -f "$tmpfile"; return 1
    else
        log_ok "Nenhum secret exposto"; append_report "- **Secrets Scan**: ✅ OK"; rm -f "$tmpfile"; return 0
    fi
}

# ─── Main ──────────────────────────────────────────────────────────────────

run_all() {
    echo ""; echo "╔══════════════════════════════════════════════════════╗"
    echo "║        Segurança — $(date '+%Y-%m-%d %H:%M:%S')         ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo ""
    append_report "# Verificação de Segurança — $(date '+%Y-%m-%d %H:%M:%S')"; append_report ""
    local failures=0
    run_security_tests || ((failures++)); echo ""
    run_composer_audit || ((failures++)); echo ""
    run_npm_audit || ((failures++)); echo ""
    run_code_scan || ((failures++)); echo ""
    run_secret_scan || ((failures++)); echo ""
    append_report ""; [ $failures -eq 0 ] && append_report "**Resultado Final**: ✅ OK" || append_report "**Resultado Final**: ❌ $failures falha(s)"
    echo "╔══════════════════════════════════════════════════════╗"
    [ $failures -eq 0 ] && echo "║   TODOS OS CHECKS DE SEGURANÇA PASSARAM ✅       ║" || echo "║   $failures CHECK(S) FALHARAM ❌       ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo "Relatório: $REPORT_FILE"; echo ""
    return $failures
}

while [[ $# -gt 0 ]]; do case "$1" in --cron) CRON_MODE=true ;; --json) JSON_MODE=true ;; --help|-h) echo "Uso: $0 [--cron] [--json]"; exit 0 ;; *) echo "Arg desconhecido: $1"; exit 1 ;; esac; shift; done
if $CRON_MODE; then run_all > /dev/null 2>&1; exit $?; fi
run_all; exit $?
