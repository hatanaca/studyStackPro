#!/usr/bin/env bash
# =============================================================================
# mimo-schedule.sh — Agenda execuções periódicas do MiMo com prompts de teste
#
# Uso:
#   ./mimo-schedule.sh --once          # Executa todos os prompts uma vez
#   ./mimo-schedule.sh --list          # Lista prompts disponíveis
#   ./mimo-schedule.sh --run <name>    # Executa um prompt específico
#   ./mimo-schedule.sh --help          # Ajuda
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
MIMO_BIN="$HOME/.mimocode/bin/mimo"
PROMPTS_DIR="$SCRIPT_DIR/prompts"
LOG_DIR="$SCRIPT_DIR/logs"
REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"

# Cores (padrão do projeto)
if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; BOLD=''; NC=''
fi

log_info()  { echo -e "${CYAN}[$(date '+%H:%M:%S')]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }
log_bold()  { echo -e "${BOLD}$1${NC}"; }

# Verifica se o MiMo está disponível
check_mimo() {
    if [ ! -x "$MIMO_BIN" ]; then
        log_error "MiMo não encontrado em: $MIMO_BIN"
        log_error "Instale o MiMo ou ajuste o caminho no script"
        return 1
    fi
    return 0
}

# Executa um prompt do MiMo
run_mimo_prompt() {
    local prompt_name="$1"
    local prompt_file="$PROMPTS_DIR/${prompt_name}.prompt"
    local log_file="$LOG_DIR/mimo-${prompt_name}-${TIMESTAMP}.log"
    local report_file="$REPORT_DIR/mimo-${prompt_name}-${TIMESTAMP}.md"
    local exit_code=0
    
    if [ ! -f "$prompt_file" ]; then
        log_error "Prompt não encontrado: $prompt_file"
        return 1
    fi
    
    local prompt_content
    prompt_content=$(cat "$prompt_file")
    
    log_info "Executando prompt: $prompt_name"
    log_info "Log: $log_file"
    
    # Executa MiMo com o prompt
    echo "$prompt_content" | "$MIMO_BIN" run \
        --dir "$PROJECT_ROOT" \
        --format json \
        --dangerously-skip-permissions \
        - > "$log_file" 2>&1 || exit_code=$?
    
    # Gera relatório markdown
    {
        echo "# Relatório MiMo — $prompt_name"
        echo ""
        echo "**Data**: $(date '+%Y-%m-%d %H:%M:%S')"
        echo "**Prompt**: $prompt_name"
        echo "**Status**: $([ $exit_code -eq 0 ] && echo '✅ Sucesso' || echo "❌ Falhou (exit code: $exit_code)")"
        echo ""
        echo "## Saída do MiMo"
        echo ""
        echo '```json'
        cat "$log_file"
        echo '```'
    } > "$report_file"
    
    if [ $exit_code -eq 0 ]; then
        log_ok "Prompt $prompt_name — concluído com sucesso"
    else
        log_error "Prompt $prompt_name — falhou (exit code: $exit_code)"
    fi
    
    return $exit_code
}

# Lista prompts disponíveis
list_prompts() {
    log_bold "Prompts disponíveis em: $PROMPTS_DIR"
    echo ""
    
    local count=0
    for f in "$PROMPTS_DIR"/*.prompt; do
        if [ -f "$f" ]; then
            local name
            name=$(basename "$f" .prompt)
            local desc
            desc=$(head -1 "$f" | sed 's/^# //' 2>/dev/null || echo "Sem descrição")
            echo "  ${GREEN}•${NC} ${BOLD}$name${NC} — $desc"
            ((count++))
        fi
    done
    
    if [ $count -eq 0 ]; then
        log_warn "Nenhum prompt encontrado em $PROMPTS_DIR"
        echo "  Crie arquivos .prompt no diretório de prompts"
    else
        echo ""
        echo "  Total: $count prompt(s)"
    fi
}

# Executa todos os prompts uma vez
run_all_prompts() {
    local total=0 failed=0
    
    log_bold "╔══════════════════════════════════════════════════════╗"
    log_bold "║     MIMO SCHEDULE — $(date '+%Y-%m-%d %H:%M:%S')         ║"
    log_bold "╚══════════════════════════════════════════════════════╝"
    echo ""
    
    for prompt_file in "$PROMPTS_DIR"/*.prompt; do
        if [ -f "$prompt_file" ]; then
            local name
            name=$(basename "$prompt_file" .prompt)
            ((total++))
            run_mimo_prompt "$name" || ((failed++))
            echo ""
        fi
    done
    
    echo ""
    log_bold "═══════════════════════════════════════════════════"
    if [ $failed -eq 0 ]; then
        log_ok "RESUMO: $total/$total prompts executados com sucesso ✅"
    else
        log_error "RESUMO: $failed de $total prompts falharam ❌"
    fi
    log_bold "═══════════════════════════════════════════════════"
    
    return $failed
}

# ─── Arg parser ──────────────────────────────────────────────────────────────
ACTION=""
PROMPT_NAME=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --once) ACTION="once"; shift ;;
        --list) ACTION="list"; shift ;;
        --run) 
            ACTION="run"
            shift
            [ -z "${1:-}" ] && { log_error "Uso: $0 --run <prompt-name>"; exit 1; }
            PROMPT_NAME="$1"
            shift
            ;;
        --help|-h)
            echo "Uso: $0 [--once|--list|--run <name>|--help]"
            echo ""
            echo "Opções:"
            echo "  --once          Executa todos os prompts uma vez"
            echo "  --list          Lista prompts disponíveis"
            echo "  --run <name>    Executa um prompt específico"
            echo "  --help          Mostra esta ajuda"
            echo ""
            echo "Prompts ficam em: $PROMPTS_DIR"
            echo "Logs em: $LOG_DIR"
            echo "Relatórios em: $REPORT_DIR"
            exit 0
            ;;
        *) 
            log_error "Argumento desconhecido: $1"
            echo "Uso: $0 [--once|--list|--run <name>|--help]"
            exit 1
            ;;
    esac
done

# Validação
[ -z "$ACTION" ] && { log_error "Especifique uma ação: --once, --list, ou --run <name>"; exit 1; }

# Cria diretórios necessários
mkdir -p "$LOG_DIR" "$REPORT_DIR"

# Verifica MiMo (exceto para --list)
if [ "$ACTION" != "list" ]; then
    check_mimo || exit 1
fi

# Executa a ação
case "$ACTION" in
    once) run_all_prompts ;;
    list) list_prompts ;;
    run)  run_mimo_prompt "$PROMPT_NAME" ;;
esac
