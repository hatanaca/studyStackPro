#!/usr/bin/env bash
# =============================================================================
# health-check.sh — Verificação periódica de saúde da aplicação
#
# Uso:
#   ./health-check.sh                    # executa uma verificação única
#   ./health-check.sh --loop             # executa em loop a cada 5 minutos
#   ./health-check.sh --cron             # modo silencioso para cron (só loga erros)
#   ./health-check.sh --slack-webhook URL # notifica Slack em caso de falha
#
# Endpoints verificados:
#   - GET /api/health                    # backend: DB, Redis, Queue, WebSocket
#   - GET /                              # frontend: SPA carregou
# =============================================================================

set -euo pipefail

APP_URL="${APP_URL:-http://177.112.223.72:5173}"
LOG_DIR="$(dirname "$0")/logs"
HEALTH_LOG="$LOG_DIR/health.log"
ERROR_LOG="$LOG_DIR/errors.log"
SLACK_WEBHOOK=""
LOOP_MODE=false
CRON_MODE=false

mkdir -p "$LOG_DIR"

# Cores (desliga se não for terminal)
if [ -t 1 ]; then
    RED='\033[0;31m'
    GREEN='\033[0;32m'
    YELLOW='\033[1;33m'
    CYAN='\033[0;36m'
    NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; NC=''
fi

log_info()  { echo -e "${CYAN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

log_file() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$HEALTH_LOG"
}

log_error_file() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" >> "$ERROR_LOG"
}

send_slack() {
    local message="$1"
    local color="${2:-danger}"
    if [ -n "$SLACK_WEBHOOK" ]; then
        curl -s -X POST "$SLACK_WEBHOOK" \
            -H "Content-Type: application/json" \
            -d "{\"attachments\":[{\"color\":\"$color\",\"text\":\"$message\",\"ts\":$(date +%s)}]}" \
            > /dev/null 2>&1 || true
    fi
}

check_endpoint() {
    local name="$1"
    local url="$2"
    local expected_status="${3:-200}"
    local response
    local http_code
    local duration

    local start_time_s
    start_time_s=$(date +%s 2>/dev/null || echo 0)

    response=$(curl -s -o /tmp/health_response.txt -w "%{http_code}" \
        --connect-timeout 10 --max-time 15 \
        "$url" 2>&1) || true
    http_code="$response"

    if [ "$start_time_s" != "0" ]; then
        local end_time_s
        end_time_s=$(date +%s 2>/dev/null || echo 0)
        duration=$(( (end_time_s - start_time_s) * 1000 ))
    else
        duration=0
    fi

    if [ "$http_code" = "$expected_status" ]; then
        log_ok "$name — $http_code (${duration}ms)"
        log_file "OK $name — $http_code (${duration}ms)"
        return 0
    else
        log_error "$name — esperado $expected_status, recebido $http_code (${duration}ms)"
        log_error_file "FAIL $name — esperado $expected_status, recebido $http_code (${duration}ms)"
        send_slack "🚨 *Health Check Falhou*\n• *Serviço:* $name\n• *Esperado:* $expected_status\n• *Recebido:* $http_code\n• *URL:* $url"
        return 1
    fi
}

check_health_json() {
    local url="${APP_URL}/api/health"
    local response
    response=$(curl -s --connect-timeout 10 --max-time 15 "$url" 2>&1) || true

    if echo "$response" | grep -q '"status":"healthy"'; then
        # Extrai status de cada serviço
        local db=$(echo "$response" | grep -oP '"database":"\K[^"]+' || echo "?")
        local redis=$(echo "$response" | grep -oP '"redis":"\K[^"]+' || echo "?")
        local queue=$(echo "$response" | grep -oP '"queue":"\K[^"]+' || echo "?")
        local ws=$(echo "$response" | grep -oP '"websocket":"\K[^"]+' || echo "?")

        log_ok "API Health: DB=$db Redis=$redis Queue=$queue WebSocket=$ws"
        log_file "OK API Health: DB=$db Redis=$redis Queue=$queue WebSocket=$ws"

        # Alerta se algum serviço estiver degradado
        if [ "$db" != "ok" ] || [ "$redis" != "ok" ] || [ "$queue" != "ok" ] || [ "$ws" != "ok" ]; then
            log_warn "Serviços degradados detectados!"
            send_slack "⚠️ *Serviços Degradados*\nDB=$db Redis=$redis Queue=$queue WebSocket=$ws" "warning"
            return 1
        fi
        return 0
    else
        log_error "API Health retornou formato inesperado: $(echo "$response" | head -c 200)"
        log_error_file "FAIL API Health — formato inesperado"
        send_slack "🚨 *API Health Check Falhou*\nResposta: $(echo "$response" | head -c 200)"
        return 1
    fi
}

check_frontend() {
    local url="${APP_URL}/"
    local response
    response=$(curl -s --connect-timeout 10 --max-time 15 "$url" 2>&1) || true

    if echo "$response" | grep -q '<div id="app">'; then
        log_ok "Frontend SPA — HTML carregado com #app"
        log_file "OK Frontend SPA"
        return 0
    else
        log_error "Frontend — HTML não contém #app"
        log_error_file "FAIL Frontend — sem #app"
        send_slack "🚨 *Frontend Check Falhou*\nSPA não retornou HTML esperado"
        return 1
    fi
}

run_checks() {
    local exit_code=0

    log_info "═══════════════════════════════════════════════"
    log_info "  Health Check — $(date '+%Y-%m-%d %H:%M:%S')"
    log_info "  URL: $APP_URL"
    log_info "═══════════════════════════════════════════════"

    echo ""

    # 1. Verifica se o servidor está respondendo
    log_info "▶ Verificando conectividade..."
    if ! curl -s --connect-timeout 5 -o /dev/null "$APP_URL" 2>/dev/null; then
        log_error "Servidor não respondedor em $APP_URL"
        log_error_file "CRITICAL Servidor offline: $APP_URL"
        send_slack "🚨🚨 *Servidor Offline!*\n$APP_URL não está respondendo."
        exit_code=1
        return $exit_code
    fi
    log_ok "Servidor respondedor"
    echo ""

    # 2. Frontend SPA
    log_info "▶ Verificando Frontend (SPA)..."
    check_frontend || exit_code=1
    echo ""

    # 3. API Health (backend, DB, Redis, Queue, WebSocket)
    log_info "▶ Verificando API Health..."
    check_health_json || exit_code=1
    echo ""

    # 4. Endpoint de saúde individual
    log_info "▶ Verificando endpoint /api/health (HTTP 200)..."
    check_endpoint "API Health" "${APP_URL}/api/health" 200 || exit_code=1
    echo ""

    if [ $exit_code -eq 0 ]; then
        log_ok "═══════════════════════════════════════════════"
        log_ok "  TODOS OS CHECKS PASSARAM ✅"
        log_ok "═══════════════════════════════════════════════"
    else
        log_error "═══════════════════════════════════════════════"
        log_error "  ALGUNS CHECKS FALHARAM ❌"
        log_error "═══════════════════════════════════════════════"
    fi

    return $exit_code
}

# ─── Processa argumentos ────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
    case "$1" in
        --loop)
            LOOP_MODE=true
            shift
            ;;
        --cron)
            CRON_MODE=true
            shift
            ;;
        --slack-webhook)
            SLACK_WEBHOOK="$2"
            shift 2
            ;;
        --help|-h)
            echo "Uso: $0 [--loop] [--cron] [--slack-webhook URL]"
            echo ""
            echo "  --loop           Executa em loop a cada 5 minutos"
            echo "  --cron           Modo silencioso (só loga erros)"
            echo "  --slack-webhook  URL do webhook do Slack para notificações"
            exit 0
            ;;
        *)
            echo "Argumento desconhecido: $1"
            exit 1
            ;;
    esac
done

if $CRON_MODE; then
    run_checks > /dev/null 2>&1
elif $LOOP_MODE; then
    log_info "Modo loop: verificando a cada 5 minutos"
    log_info "Pressione Ctrl+C para parar"
    echo ""
    while true; do
        run_checks || true
        echo ""
        log_info "Próxima verificação em 5 minutos..."
        sleep 300
    done
else
    run_checks
fi
