#!/usr/bin/env bash
# =============================================================================
# sentry-check.sh — Consulta a API do Sentry para monitorar erros recentes
#
# Requer: SENTRY_AUTH_TOKEN (gerado em https://sentry.io/settings/account/api/auth-tokens/)
#          SENTRY_ORG (slug da organização no Sentry, ex: "studytrack")
#
# Uso:
#   export SENTRY_AUTH_TOKEN="sntrys_..."
#   export SENTRY_ORG="studytrack"
#   ./sentry-check.sh                          # Erros das últimas 24h
#   ./sentry-check.sh --hours 48               # Erros das últimas 48h
#   ./sentry-check.sh --project studytrack-pro # Filtrar por projeto
#   ./sentry-check.sh --loop                   # Monitoramento contínuo
#   ./sentry-check.sh --slack-webhook URL      # Notificar Slack
#   ./sentry-check.sh --setup-cron             # Instruções de configuração
# =============================================================================

set -euo pipefail

# Carrega configuração do arquivo .sentry.env se existir
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
if [ -f "$SCRIPT_DIR/.sentry.env" ]; then
    set -a
    source "$SCRIPT_DIR/.sentry.env"
    set +a
fi

SENTRY_AUTH_TOKEN="${SENTRY_AUTH_TOKEN:-}"
SENTRY_ORG="${SENTRY_ORG:-}"
SENTRY_PROJECT="${SENTRY_PROJECT:-}"
HOURS="${HOURS:-24}"
LOG_DIR="$SCRIPT_DIR/logs"
SENTRY_LOG="$LOG_DIR/sentry.log"
ERROR_LOG="$LOG_DIR/errors.log"
SLACK_WEBHOOK="${SLACK_WEBHOOK:-}"
LOOP_MODE=false

mkdir -p "$LOG_DIR"

# Cores
if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; BOLD=''; NC=''
fi

log_info()  { echo -e "${CYAN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

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

# ─── Validação ──────────────────────────────────────────────────────

if [ -z "$SENTRY_AUTH_TOKEN" ]; then
    log_error "SENTRY_AUTH_TOKEN não definido."
    echo ""
    echo "Para gerar um token:"
    echo "  1. Acesse https://sentry.io/settings/account/api/auth-tokens/"
    echo "  2. Crie um token com as permissões: 'project:read', 'event:read', 'org:read'"
    echo "  3. Exporte a variável: export SENTRY_AUTH_TOKEN='sntrys_...'"
    echo ""
    echo "Ou use --setup-cron para instruções completas."
    exit 1
fi

if [ -z "$SENTRY_ORG" ]; then
    log_error "SENTRY_ORG não definido."
    echo "Exporte o slug da sua organização no Sentry:"
    echo "  export SENTRY_ORG='sua-organizacao'"
    echo ""
    echo "Dica: o slug aparece na URL do Sentry: https://sentry.io/organizations/<slug>/"
    exit 1
fi

# ─── Funções da API Sentry ──────────────────────────────────────────

SENTRY_API="https://sentry.io/api/0"

sentry_api_get() {
    local endpoint="$1"
    curl -s -H "Authorization: Bearer $SENTRY_AUTH_TOKEN" \
        "${SENTRY_API}${endpoint}" 2>&1
}

list_projects() {
    sentry_api_get "/organizations/${SENTRY_ORG}/projects/"
}

get_issues() {
    local project="$1"
    local stats_period="${2:-24h}"
    sentry_api_get "/projects/${SENTRY_ORG}/${project}/issues/?statsPeriod=${stats_period}&sort=freq&limit=20"
}

get_events() {
    local project="$1"
    local issue_id="$2"
    local limit="${3:-5}"
    sentry_api_get "/projects/${SENTRY_ORG}/${project}/issues/${issue_id}/events/?limit=${limit}"
}

get_project_stats() {
    local project="$1"
    local stats_period="${2:-24h}"
    sentry_api_get "/projects/${SENTRY_ORG}/${project}/stats/?stat=received&resolution=1h&since=$(date -d '-24 hours' +%s 2>/dev/null || echo "")"
}

# ─── Verificações ────────────────────────────────────────────────────

check_sentry_token() {
    local resp
    resp=$(sentry_api_get "/organizations/${SENTRY_ORG}/" 2>&1)
    # Usa python para parsear JSON sem vazamento de saída
    local org_name
    org_name=$(echo "$resp" | python3 -c "
import json, sys
try:
    d = json.load(sys.stdin)
    print(d.get('name', sys.argv[1] if len(sys.argv) > 1 else ''))
except: pass
" 2>/dev/null || echo "$SENTRY_ORG") || true

    if [ -n "$org_name" ]; then
        log_ok "Autenticado no Sentry — Organização: $org_name"
        return 0
    else
        log_error "Falha na autenticação com Sentry."
        return 1
    fi
}

list_sentry_projects() {
    log_info "▶ Projetos no Sentry:"
    local projects
    projects=$(list_projects)
    if echo "$projects" | grep -q '"slug"'; then
        echo "$projects" | grep -oP '"slug":"\K[^"]+' | while read -r slug; do
            echo "   • $slug"
        done
    else
        log_warn "Nenhum projeto encontrado ou erro na API"
        echo "$projects" | head -c 500
    fi
}

check_project_errors() {
    local project="$1"
    local period="${2:-24h}"
    local period_label="últimas ${HOURS}h"

    log_info "▶ Projeto: $BOLD$project$NC ($period_label)"

    local issues
    issues=$(get_issues "$project" "$period")

    local total_count
    total_count=$(echo "$issues" | python3 -c "
import json, sys
try:
    data = json.load(sys.stdin)
    if isinstance(data, list):
        print(sum(int(i.get('count', 0)) for i in data))
    else:
        print(0)
except: print(0)
" 2>/dev/null || echo "0")

    if [ "$total_count" = "0" ] || [ -z "$total_count" ]; then
        log_ok "Nenhum erro nas $period_label"
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$project] OK — 0 erros nas $period_label" >> "$SENTRY_LOG"
        return 0
    fi

    log_warn "$total_count erros encontrados nas $period_label"
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$project] $total_count erros nas $period_label" >> "$SENTRY_LOG"
    echo ""

    # Lista top issues
    echo "$issues" | grep -oP '"title":"\K[^"]+' | head -10 | while read -r title; do
        echo "   📌 $title"
    done

    echo ""

    # Detalhes do top 5 issues
    echo "$issues" | python3 -c "
import json, sys

try:
    data = json.load(sys.stdin)
except json.JSONDecodeError:
    sys.exit(0)

if isinstance(data, list):
    for i, issue in enumerate(data[:5]):
        title = issue.get('title', '?')
        count = issue.get('count', '?')
        level = issue.get('level', '?')
        permalink = issue.get('permalink', '?')
        first_seen = issue.get('firstSeen', '?')[:10]
        last_seen = issue.get('lastSeen', '?')[:10]

        print(f'  {i+1}. [{level.upper()}] {title}')
        print(f'     Ocorrências: {count}')
        print(f'     Primeira vez: {first_seen} | Última vez: {last_seen}')
        print(f'     Link: {permalink}')
        print()
" 2>/dev/null || echo "$issues" | grep -oP '"title":"\K[^"]+' | head -5

    # Alerta no Slack se houver erros novos
    if [ "$total_count" -gt 0 ] && [ -n "$SLACK_WEBHOOK" ]; then
        local top_issue
        top_issue=$(echo "$issues" | grep -oP '"title":"\K[^"]+' | head -1)
        send_slack "🔴 *Sentry Alert — $project*\n• Erros (${period_label}): $total_count\n• Top: $top_issue\n• <https://sentry.io/organizations/${SENTRY_ORG}/issues/?project=${project}|Ver no Sentry>" "danger"
    fi
}

check_all_projects() {
    local exit_code=0
    local period="${1:-24h}"

    log_info "═══════════════════════════════════════════════"
    log_info "  Sentry Monitor — $(date '+%Y-%m-%d %H:%M:%S')"
    log_info "  Organização: $SENTRY_ORG"
    log_info "═══════════════════════════════════════════════"
    echo ""

    # 1. Verificar autenticação
    if ! check_sentry_token; then
        return 1
    fi
    echo ""

    # 2. Se um projeto específico foi definido, verifica só ele
    if [ -n "$SENTRY_PROJECT" ]; then
        check_project_errors "$SENTRY_PROJECT" "$period" || exit_code=1
    else
        # 3. Lista projetos e verifica todos
        log_info "▶ Buscando projetos..."
        local projects
        projects=$(list_projects)
        local slugs
        slugs=$(echo "$projects" | python3 -c "
import json, sys
try:
    data = json.load(sys.stdin)
    if isinstance(data, list):
        for p in data:
            print(p.get('slug', ''))
except: pass
" 2>/dev/null || true)

        if [ -z "$slugs" ]; then
            log_warn "Nenhum projeto encontrado. Verifique se SENTRY_ORG='$SENTRY_ORG' está correto."
            log_info "Projetos disponíveis via API:"
            echo "$projects" | python3 -c "
import json,sys
try:
    d=json.load(sys.stdin)
    if isinstance(d,list):
        for p in d: print(f'  • {p.get(\"slug\",\"?\")} ({p.get(\"name\",\"?\")})')
except: print('  (erro ao parsear resposta)')
" 2>/dev/null || echo "   (resposta bruta: $(echo "$projects" | head -c 200))"
            return 1
        fi

        echo "$slugs" | while read -r slug; do
            check_project_errors "$slug" "$period" || exit_code=1
            echo ""
        done
    fi

    log_info "═══════════════════════════════════════════════"
    if [ $exit_code -eq 0 ]; then
        log_ok "  Monitoramento Sentry concluído ✅"
    else
        log_error "  Erros encontrados no Sentry ❌"
    fi
    log_info "═══════════════════════════════════════════════"

    return $exit_code
}

# ─── Setup Cron ─────────────────────────────────────────────────────

show_setup_cron() {
    local script_path
    script_path="$(cd "$(dirname "$0")" && pwd)/$(basename "$0")"

    echo ""
    echo "═══════════════════════════════════════════════════════════════"
    echo "  Configuração do Cron para Sentry Monitoring"
    echo "═══════════════════════════════════════════════════════════════"
    echo ""
    echo "Para configurar o monitoramento automático via cron:"
    echo ""
    echo "1. Crie um arquivo .env de configuração:"
    echo ""
    echo "  cat > $(dirname "$0")/.sentry.env << 'EOF'"
    echo "  SENTRY_AUTH_TOKEN='sntrys_...'"
    echo "  SENTRY_ORG='sua-organizacao'"
    echo "  SENTRY_PROJECT='studytrack-pro'"
    echo "  SLACK_WEBHOOK='https://hooks.slack.com/services/...'"
    echo "  EOF"
    echo "  chmod 600 $(dirname "$0")/.sentry.env"
    echo ""
    echo "2. Adicione ao crontab (crontab -e):"
    echo ""
    echo "  # Sentry: verificar erros a cada 4 horas"
    echo "  0 */4 * * * cd $(dirname "$0") && . .sentry.env && ./sentry-check.sh --cron 2>&1 | logger -t sentry-check"
    echo ""
    echo "3. Teste a configuração:"
    echo ""
    echo "  export SENTRY_AUTH_TOKEN='sntrys_...'"
    echo "  export SENTRY_ORG='sua-organizacao'"
    echo "  $0"
    echo ""
}

# ─── Processa argumentos ────────────────────────────────────────────

while [[ $# -gt 0 ]]; do
    case "$1" in
        --hours)
            HOURS="$2"
            shift 2
            ;;
        --project)
            SENTRY_PROJECT="$2"
            shift 2
            ;;
        --loop)
            LOOP_MODE=true
            shift
            ;;
        --slack-webhook)
            SLACK_WEBHOOK="$2"
            shift 2
            ;;
        --cron)
            # Modo silencioso para cron
            check_all_projects "${HOURS}h" > /dev/null 2>&1
            exit $?
            ;;
        --setup-cron|--help|-h)
            show_setup_cron
            exit 0
            ;;
        *)
            echo "Argumento desconhecido: $1"
            echo "Use --help para ajuda."
            exit 1
            ;;
    esac
done

if $LOOP_MODE; then
    log_info "Modo loop: verificando Sentry a cada 30 minutos"
    log_info "Pressione Ctrl+C para parar"
    while true; do
        check_all_projects "${HOURS}h" || true
        sleep 1800
    done
else
    check_all_projects "${HOURS}h"
fi
