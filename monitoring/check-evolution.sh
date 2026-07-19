#!/usr/bin/env bash
# =============================================================================
# check-evolution.sh — Melhorias Evolutivas (inteligentes + automatizadas)
#
# Esta é a camada "inteligente" da pipeline. Ela analisa o código e aplica
# melhorias evolutivas que vão além da simples correção de problemas:
#
#   1. Dependências desatualizadas → atualiza para versões recentes
#   2. Análise inteligente (via mimo run) → sugere e aplica refatorações
#   3. Modernização de padrões → detecta e atualiza patterns legados
#
# Uso:
#   ./check-evolution.sh                        # modo interativo
#   ./check-evolution.sh --cron                 # modo silencioso (cron)
#   ./check-evolution.sh --fix                  # modo com auto-aplicação
#   ./check-evolution.sh --no-ai                # pula análise IA (só ferramentas)
#   ./check-evolution.sh --help                 # ajuda
# =============================================================================

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
source "$SCRIPT_DIR/sentry-lib.sh"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"
FRONTEND_DIR="$PROJECT_ROOT/frontend"
LOG_DIR="$SCRIPT_DIR/logs"
REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"
REPORT_FILE="$REPORT_DIR/check-evolution-$TIMESTAMP.md"
AI_LOG="$LOG_DIR/evolution-ai-$TIMESTAMP.log"

CRON_MODE=false; JSON_MODE=false; FIX_MODE=false; NO_AI=false
IMPROVEMENTS_APPLIED=0

mkdir -p "$LOG_DIR" "$REPORT_DIR"

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
append_report() { echo "$1" >> "$REPORT_FILE"; }

# ══════════════════════════════════════════════════════════════════════════════
# Fase 1: Atualização de Dependências
# ══════════════════════════════════════════════════════════════════════════════

update_composer_deps() {
    log_info "▶ Dependências PHP (composer)..."
    local outdated
    outdated=$(composer outdated --direct --working-dir="$BACKEND_DIR" 2>&1) || true
    local count
    count=$(echo "$outdated" | grep -c '^[a-z]' 2>/dev/null || echo 0)

    if [ "$count" -eq 0 ]; then
        log_ok "Todas as dependências PHP estão atualizadas"
        append_report "- **PHP Dependencies**: ✅ Atualizadas"
        return 0
    fi

    log_info "  $count dependência(s) desatualizada(s)"
    if $FIX_MODE; then
        log_info "  → Auto-fix: composer update..."
        composer update --working-dir="$BACKEND_DIR" 2>&1 || true
        log_ok "Dependências PHP atualizadas"
        append_report "- **PHP Dependencies**: ✅ $count atualizada(s)"
        ((IMPROVEMENTS_APPLIED++))
        return 0
    else
        echo "$outdated" | head -15 | while IFS= read -r line; do log_info "    $line"; done
        append_report "- **PHP Dependencies**: ⚠️ $count desatualizada(s)"
        return 1
    fi
}

update_npm_deps() {
    log_info "▶ Dependências Node (npm)..."
    if [ ! -d "$FRONTEND_DIR/node_modules" ]; then
        log_warn "node_modules ausente"; append_report "- **NPM Dependencies**: ⏭️"; return 2
    fi

    # Usa npm outdated para verificar
    local outdated
    outdated=$(npm outdated --prefix "$FRONTEND_DIR" 2>&1) || true
    local count
    count=$(echo "$outdated" | grep -c '^[a-z]' 2>/dev/null || echo 0)

    if [ "$count" -eq 0 ]; then
        log_ok "Todas as dependências Node estão atualizadas"
        append_report "- **NPM Dependencies**: ✅ Atualizadas"
        return 0
    fi

    log_info "  $count dependência(s) desatualizada(s)"
    if $FIX_MODE; then
        log_info "  → Auto-fix: npm update..."
        npm update --prefix "$FRONTEND_DIR" 2>&1 || true
        log_ok "Dependências Node atualizadas"
        append_report "- **NPM Dependencies**: ✅ $count atualizada(s)"
        ((IMPROVEMENTS_APPLIED++))
        return 0
    else
        echo "$outdated" | head -15 | while IFS= read -r line; do log_info "    $line"; done
        append_report "- **NPM Dependencies**: ⚠️ $count desatualizada(s)"
        return 1
    fi
}

# ══════════════════════════════════════════════════════════════════════════════
# Fase 2: Análise Inteligente via MIMO
# ══════════════════════════════════════════════════════════════════════════════

run_ai_evolution() {
    if $NO_AI; then
        log_info "Análise IA pulada (--no-ai)"
        append_report "- **AI Evolution**: ⏭️ Pulado"
        return 2
    fi

    local mimo_bin
    mimo_bin=$(command -v mimo 2>/dev/null || echo "/home/ThiagoHatanaka/.mimocode/bin/mimo")

    if [ ! -x "$mimo_bin" ]; then
        log_warn "mimo CLI não encontrado — pulando análise IA"
        append_report "- **AI Evolution**: ⏭️ mimo não disponível"
        return 2
    fi

    log_info "▶ Análise inteligente via mimo..."

    # Constrói o prompt para o agente
    local prompt="
Você é um engenheiro de software sênior revisando o projeto StudyTrack Pro em $PROJECT_ROOT.

SEU OBJETIVO: Encontrar e aplicar **melhorias evolutivas** — coisas que deixam o código mais moderno, rápido, seguro e manutenível.

FOQUE EM:
1. **Refatorações seguras**: extrair funções muito longas, simplificar lógica complexa, remover código morto
2. **Modernização**: substituir padrões antigos por equivalentes modernos (ex: classes por composables no Vue, queries por relações Eloquent)
3. **Performance**: loops ineficientes, queries N+1, bundles grandes, imports pesados
4. **Arquitetura**: componentes muito grandes que deveriam ser quebrados, responsabilidades misturadas
5. **DX (Developer Experience)**: configurações que faltam, scripts de automação, documentação de código

REGRAS:
- Apenas mudanças que você TEM CERTEZA que são seguras e corretas
- Cada melhoria deve ser atômica (uma coisa por vez)
- Prefira 3 mudanças excelentes a 10 mudanças superficiais
- Arquivos PHP em backend/app/
- Arquivos Vue/TS em frontend/src/
- Não mude testes a menos que eles testem funcionalidade que você alterou

FORMATO DE SAÍDA:
Para cada melhoria aplicada, informe:
- Arquivo e linha
- O que mudou
- Por que é uma melhoria
"

    if $FIX_MODE; then
        # Modo headless com auto-permissão
        log_info "  Executando mimo em modo headless (--dangerously-skip-permissions)..."
        log_warn "  ATENÇÃO: modo auto-permissão ativado — mimo pode editar arquivos"

        echo "$prompt" | "$mimo_bin" run --dangerously-skip-permissions --format json --dir "$PROJECT_ROOT" - 2>"$AI_LOG" | tail -20 > /dev/null 2>&1 || true

        # Verifica se houveram mudanças no git
        local changed_files
        changed_files=$(git -C "$PROJECT_ROOT" diff --name-only 2>/dev/null | wc -l)
        if [ "$changed_files" -gt 0 ]; then
            log_ok "Análise IA aplicou melhorias em $changed_files arquivo(s)"
            append_report "- **AI Evolution**: ✅ $changed_files arquivo(s) melhorado(s)"
            ((IMPROVEMENTS_APPLIED++))
            return 0
        else
            log_info "Nenhuma melhoria aplicada pela IA"
            append_report "- **AI Evolution**: ✅ Nenhuma melhoria necessária"
            return 0
        fi
    else
        log_info "  Modo análise apenas (sem --fix)..."
        log_info "  Execute com --fix para aplicar automaticamente"
        echo "$prompt" | "$mimo_bin" run --format json --dir "$PROJECT_ROOT" - 2>"$AI_LOG" | head -50 || true
        append_report "- **AI Evolution**: ⚠️ Análise concluída (não aplicada, use --fix)"
        return 1
    fi
}

# ══════════════════════════════════════════════════════════════════════════════
# Fase 3: Análise de Arquitetura (métricas)
# ══════════════════════════════════════════════════════════════════════════════

check_architecture() {
    log_info "▶ Métricas de arquitetura..."
    local warnings=0

    # Arquivos muito grandes (+500 linhas)
    local large_files=0
    log_info "  Verificando arquivos grandes..."
    for dir in "$BACKEND_DIR/app" "$FRONTEND_DIR/src"; do
        while IFS= read -r f; do
            local lines
            lines=$(wc -l < "$f" 2>/dev/null || echo 0)
            if [ "$lines" -gt 500 ]; then
                local rel
                rel=$(echo "$f" | sed "s|$PROJECT_ROOT/||")
                log_warn "  Arquivo grande: $rel ($lines linhas)"
                append_report "- **Métrica**: $rel — $lines linhas (considere quebrar)"
                ((warnings++))
                ((large_files++))
            fi
        done < <(find "$dir" -name '*.php' -o -name '*.ts' -o -name '*.vue' 2>/dev/null)
    done

    if [ "$large_files" -eq 0 ]; then
        log_ok "Nenhum arquivo excessivamente grande"
        append_report "- **Arquitetura**: ✅ OK (sem arquivos >500 linhas)"
    else
        log_warn "$large_files arquivo(s) grande(s) encontrado(s)"
    fi

    return $warnings
}

# ══════════════════════════════════════════════════════════════════════════════
# Main
# ══════════════════════════════════════════════════════════════════════════════

run_all() {
    echo ""
    echo "╔══════════════════════════════════════════════════════╗"
    echo "║      Evolução — $(date '+%Y-%m-%d %H:%M:%S')          ║"
    echo "╚══════════════════════════════════════════════════════╝"
    echo ""

    append_report "# Melhorias Evolutivas — $(date '+%Y-%m-%d %H:%M:%S')"
    append_report ""

    local failures=0

    # Fase 1: Dependências
    log_bold "── Fase 1/3: Dependências ──"
    echo ""
    update_composer_deps || ((failures++))
    echo ""
    update_npm_deps || ((failures++))
    echo ""

    # Fase 2: IA
    log_bold "── Fase 2/3: Análise Inteligente ──"
    echo ""
    run_ai_evolution || ((failures++))
    echo ""

    # Fase 3: Arquitetura
    log_bold "── Fase 3/3: Arquitetura ──"
    echo ""
    check_architecture || ((failures++))
    echo ""

    # Relatório final
    append_report ""
    if [ "$IMPROVEMENTS_APPLIED" -gt 0 ]; then
        append_report "**Melhorias aplicadas**: $IMPROVEMENTS_APPLIED"
    fi
    [ $failures -eq 0 ] && append_report "**Resultado Final**: ✅ OK" || append_report "**Resultado Final**: ❌ $failures oportunidade(s) de melhoria"

    echo "╔══════════════════════════════════════════════════════╗"
    if [ "$IMPROVEMENTS_APPLIED" -gt 0 ]; then
        echo "║   $IMPROVEMENTS_APPLIED MELHORIA(S) APLICADA(S) ✅     ║"
    fi
    [ $failures -eq 0 ] && echo "║    ANÁLISE EVOLUTIVA CONCLUÍDA ✅            ║" || echo "║    $failures OPORTUNIDADE(S) IDENTIFICADAS       ║"
    echo "╚══════════════════════════════════════════════════════╝"
    echo ""
    echo "Relatório: $REPORT_FILE"
    echo ""

    sentry_report_check "check-evolution" $failures "Verificacao: ${script%.sh}"
    return $failures
}

while [[ $# -gt 0 ]]; do
    case "$1" in
        --cron) CRON_MODE=true ;;
        --fix) FIX_MODE=true ;;
        --no-ai) NO_AI=true ;;
        --json) JSON_MODE=true ;;
        --help|-h) echo "Uso: \$0 [--cron] [--fix] [--no-ai] [--json]"; exit 0 ;;
        *) echo "Arg desconhecido: $1"; exit 1 ;;
    esac
    shift
done

if $CRON_MODE; then
    run_all > /dev/null 2>&1
    exit $?
fi

run_all
exit $?
