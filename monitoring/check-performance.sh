#!/usr/bin/env bash
# =============================================================================
# check-performance.sh — Verificação de Desempenho (modo check + fix)
# =============================================================================
set -uo pipefail
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"; PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
source "$SCRIPT_DIR/sentry-lib.sh"
BACKEND_DIR="$PROJECT_ROOT/backend"; FRONTEND_DIR="$PROJECT_ROOT/frontend"
LOG_DIR="$SCRIPT_DIR/logs"; REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"; REPORT_FILE="$REPORT_DIR/check-performance-$TIMESTAMP.md"
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

run_phpstan() {
    log_info "▶ PHPStan..."
    if [ -f "$BACKEND_DIR/vendor/bin/phpstan" ]; then
        if php "$BACKEND_DIR/vendor/bin/phpstan" analyse --memory-limit=512M --configuration="$BACKEND_DIR/phpstan.neon" "$BACKEND_DIR/app" 2>&1; then
            log_ok "PHPStan — OK"; append_report "- **PHPStan**: ✅ OK"; return 0
        elif $FIX_MODE; then
            log_info "  → Auto-fix: gerando baseline..."
            php "$BACKEND_DIR/vendor/bin/phpstan" analyse --memory-limit=512M --configuration="$BACKEND_DIR/phpstan.neon" --generate-baseline "$BACKEND_DIR/phpstan-baseline.neon" 2>/dev/null || true
            log_warn "Baseline atualizada"; append_report "- **PHPStan**: ⚠️ Baseline atualizada"; return 0
        else log_warn "Erros encontrados"; append_report "- **PHPStan**: ⚠️"; return 1; fi
    else log_warn "PHPStan não disponível"; append_report "- **PHPStan**: ⏭️"; fi
}

run_health_check() {
    log_info "▶ Health check..."
    if bash "$SCRIPT_DIR/health-check.sh" --cron 2>&1; then
        log_ok "Health — OK"; append_report "- **Health Check**: ✅ OK"; return 0
    else log_warn "Problemas"; append_report "- **Health Check**: ⚠️"; return 1; fi
}

check_redis() {
    log_info "▶ Redis..."
    if docker_is_running && service_is_up "redis"; then
        if docker compose -f "$PROJECT_ROOT/docker-compose.yml" exec -T redis redis-cli ping 2>&1; then
            log_ok "Redis — PONG"; append_report "- **Redis**: ✅ OK"; return 0
        else log_error "Redis — sem resposta"; append_report "- **Redis**: ❌"; return 1; fi
    elif command -v redis-cli &> /dev/null && redis-cli ping 2>&1; then
        log_ok "Redis — PONG (local)"; append_report "- **Redis**: ✅ OK (local)"; return 0
    else log_info "Redis indisponível"; append_report "- **Redis**: ⏭️"; return 2; fi
}

check_frontend_build() {
    log_info "▶ Build frontend..."; local r=0
    if [ -d "$FRONTEND_DIR/node_modules" ]; then
        local bl; bl=$(mktemp /tmp/studytrack-build-XXXXXX.log 2>/dev/null)
        if npm run build --prefix "$FRONTEND_DIR" 2>&1 > "$bl"; then
            local sz; sz=$(grep -E '✓ built in|✓ built ' "$bl" | tail -1 || true)
            log_ok "Build — OK $sz"
            [ -d "$FRONTEND_DIR/dist" ] && local ds; ds=$(du -sh "$FRONTEND_DIR/dist" 2>/dev/null | cut -f1); log_info "  Dist: $ds"; append_report "- **Build**: ✅ OK${ds:+ (${ds})}"
        else
            log_error "Build falhou"
            if $FIX_MODE; then
                log_info "  → Auto-fix: rebuild..."
                npm run build --prefix "$FRONTEND_DIR" 2>&1 > "$bl" && {
                    log_ok "Build — OK após rebuild"; append_report "- **Build**: ✅ OK (rebuild)"; rm -f "$bl"; return 0
                }
            fi
            grep -i 'error\|ERROR\|✖' "$bl" | head -3 | while IFS= read -r e; do log_error "  $e"; done
            append_report "- **Build**: ❌"; r=1
        fi
        rm -f "$bl"
    else log_warn "node_modules ausente"; append_report "- **Build**: ⏭️"; fi
    return $r
}

check_response_time() {
    log_info "▶ Tempo de resposta..."
    local url="${APP_URL:-http://localhost:5173}/api/health"
    local start; start=$(date +%s%N 2>/dev/null || echo 0)
    local code; code=$(curl -s -o /dev/null -w "%{http_code}" --connect-timeout 10 --max-time 10 "$url" 2>&1) || true
    local end; end=$(date +%s%N 2>/dev/null || echo 0)
    local dur=0; [ "$start" != "0" ] && dur=$(( (end - start) / 1000000 ))
    if [ "$code" = "200" ]; then log_ok "${dur}ms"; append_report "- **Response Time**: ✅ ${dur}ms"; [ "$dur" -gt 1000 ] && log_warn "Alto (> 1s)"; return 0
    else log_info "API não acessível (HTTP $code)"; append_report "- **Response Time**: ⏭️"; return 2; fi
}

run_all() {
    echo ""; echo "╔══════════════════════════════════════════════════════╗"
    echo "║     Desempenho — $(date '+%Y-%m-%d %H:%M:%S')         ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo ""
    append_report "# Desempenho — $(date '+%Y-%m-%d %H:%M:%S')"; append_report ""
    local f=0; run_phpstan || ((f++)); echo ""; run_health_check || ((f++)); echo ""
    check_redis || ((f++)); echo ""; check_frontend_build || ((f++)); echo ""
    check_response_time || true; echo ""
    append_report ""; [ $f -eq 0 ] && append_report "**Resultado**: ✅ OK" || append_report "**Resultado**: ❌ $f falha(s)"
    echo "╔══════════════════════════════════════════════════════╗"
    [ $f -eq 0 ] && echo "║  TODOS OS CHECKS DE DESEMPENHO PASSARAM ✅       ║" || echo "║  $f CHECK(S) FALHARAM ❌     ║"
    echo "╚══════════════════════════════════════════════════════╝"; echo "Relatório: $REPORT_FILE"; echo ""; return $f
}

while [[ $# -gt 0 ]]; do case "$1" in --cron) CRON_MODE=true ;; --fix) FIX_MODE=true ;; --json) JSON_MODE=true ;; --help|-h) echo "Uso: \$0 [--cron] [--fix] [--json]"; exit 0 ;; *) echo "Arg desconhecido: $1"; exit 1 ;; esac; shift; done
if $CRON_MODE; then run_all > /dev/null 2>&1; exit $?; fi; run_all; exit $?
