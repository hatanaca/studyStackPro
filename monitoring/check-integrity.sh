#!/usr/bin/env bash
# =============================================================================
# check-integrity.sh — Verificação de Integridade
#
# Executa: 1. Git working tree  2. Migrations  3. Permissões  4. Docker services  5. TS schemas
#
# Uso: ./check-integrity.sh [--cron] [--json] [--help]
# =============================================================================

set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"; PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"; FRONTEND_DIR="$PROJECT_ROOT/frontend"
LOG_DIR="$SCRIPT_DIR/logs"; REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"; REPORT_FILE="$REPORT_DIR/check-integrity-$TIMESTAMP.md"
CRON_MODE=false; JSON_MODE=false
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

# ─── 1. Git ──────────────────────────────────────────────────────────────────
check_git_status() {
    log_info "▶ Working tree git..."
    local st; st=$(cd "$PROJECT_ROOT" && git status --porcelain 2>&1)
    if [ -z "$st" ]; then log_ok "Working tree limpo"; append_report "- **Git Working Tree**: ✅ OK"; return 0; fi
    local untracked modified
    untracked=$(echo "$st" | grep -c '^??' || true); modified=$(echo "$st" | grep -cv '^??' || true)
    log_warn "Working tree sujo: $untracked novos, $modified modificados"
    append_report "- **Git Working Tree**: ⚠️ $untracked novos, $modified modificados"; echo "$st" | head -30 >> "$REPORT_FILE"
    return 1
}

# ─── 2. Migrations ────────────────────────────────────────────────────────────
check_migrations() {
    log_info "▶ Migrations..."
    if docker_is_running && service_is_up "php-fpm" && service_is_up "postgres"; then
        if docker compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T php-fpm php artisan migrate:status 2>&1; then
            log_ok "Migrations — OK"; append_report "- **Migrations**: ✅ OK"; return 0
        else
            log_error "Migrations — problema"; append_report "- **Migrations**: ❌"; return 1
        fi
    elif [ -f "$BACKEND_DIR/artisan" ] && [ -f "$BACKEND_DIR/.env" ]; then
        local r; r=$(php "$BACKEND_DIR/artisan" migrate:status 2>&1) || true
        if echo "$r" | grep -q "No migrations\|Ran all\|Migration table created"; then
            log_ok "Migrations — OK"; append_report "- **Migrations**: ✅ OK (local)"; return 0
        else log_warn "DB não acessível"; append_report "- **Migrations**: ⚠️ DB indisponível"; return 2; fi
    else log_warn "PHP/.env ausente"; append_report "- **Migrations**: ⏭️"; return 2; fi
}

# ─── 3. Permissões ────────────────────────────────────────────────────────────
check_permissions() {
    log_info "▶ Permissões..."; local r=0
    for d in "$BACKEND_DIR/storage" "$BACKEND_DIR/storage/logs" "$BACKEND_DIR/storage/framework" "$BACKEND_DIR/storage/framework/cache" "$BACKEND_DIR/storage/framework/sessions" "$BACKEND_DIR/storage/framework/views" "$BACKEND_DIR/bootstrap/cache" "$FRONTEND_DIR/dist"; do
        if [ -d "$d" ]; then [ -w "$d" ] && log_ok "  $d — OK" || { log_warn "  $d — sem escrita"; append_report "- **Perm**: ⚠️ $d"; r=1; }; fi
    done
    [ $r -eq 0 ] && append_report "- **Permissões**: ✅ OK"; return $r
}

# ─── 4. Docker Services ───────────────────────────────────────────────────────
check_docker_services() {
    log_info "▶ Serviços Docker..."
    if ! docker_is_running; then log_info "Docker parado"; append_report "- **Docker**: ⏭️"; return 2; fi
    local all_up=true; local r=0
    for s in postgres redis php-fpm; do
        if service_is_up "$s"; then log_ok "  $s — rodando"; else log_warn "  $s — parado"; all_up=false; r=1; fi
    done
    append_report "- **Docker Services**: $($all_up && echo '✅ OK' || echo '⚠️ Alguns parados')"; return $r
}

# ─── 5. TypeScript Schemas ────────────────────────────────────────────────────
check_typescript_schemas() {
    log_info "▶ TypeScript schemas..."; local r=0
    if [ -d "$FRONTEND_DIR/node_modules" ]; then
        if npm run type-check --prefix "$FRONTEND_DIR" 2>&1; then
            log_ok "TypeScript — OK"; append_report "- **TS Schemas**: ✅ OK"
        else log_error "Erros de tipo"; append_report "- **TS Schemas**: ❌"; r=1; fi
    else log_warn "node_modules ausente"; append_report "- **TS Schemas**: ⏭️"; fi
    return $r
}

# ─── Main ─────────────────────────────────────────────────────────────────────
run_all() {
    echo ""; echo "╔══════════════════════════════════════════════════════╗"
    echo "║       Integridade — $(date '+%Y-%m-%d %H:%M:%S')        ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo ""
    append_report "# Integridade — $(date '+%Y-%m-%d %H:%M:%S')"; append_report ""
    local f=0; check_git_status || ((f++)); echo ""; check_migrations || ((f++)); echo ""
    check_permissions || ((f++)); echo ""; check_docker_services || ((f++)); echo ""
    check_typescript_schemas || ((f++)); echo ""
    append_report ""; [ $f -eq 0 ] && append_report "**Resultado**: ✅ OK" || append_report "**Resultado**: ❌ $f falha(s)"
    echo "╔══════════════════════════════════════════════════════╗"
    [ $f -eq 0 ] && echo "║  TODOS OS CHECKS DE INTEGRIDADE PASSARAM ✅      ║" || echo "║  $f CHECK(S) FALHARAM ❌     ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo "Relatório: $REPORT_FILE"; echo ""; return $f
}

while [[ $# -gt 0 ]]; do case "$1" in --cron) CRON_MODE=true ;; --json) JSON_MODE=true ;; --help|-h) echo "Uso: $0 [--cron] [--json]"; exit 0 ;; *) echo "Arg desconhecido: $1"; exit 1 ;; esac; shift; done
if $CRON_MODE; then run_all > /dev/null 2>&1; exit $?; fi; run_all; exit $?
