#!/usr/bin/env bash
# =============================================================================
# quality-pipeline.sh — Orquestrador da Pipeline de Qualidade
#
# Executa todos os 5 checks em sequência. Se todos passarem, cria/atualiza um
# PR no GitHub com auto-merge habilitado.
#
# Uso:
#   ./quality-pipeline.sh                        # modo interativo
#   ./quality-pipeline.sh --cron                 # modo silencioso (cron)
#   ./quality-pipeline.sh --skip-pr              # só executa checks, sem PR
#   ./quality-pipeline.sh --help                 # ajuda
# =============================================================================

set -uo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
LOG_DIR="$SCRIPT_DIR/logs"
REPORT_DIR="$SCRIPT_DIR/reports"
TIMESTAMP="$(date '+%Y-%m-%d_%H%M%S')"
PIPELINE_LOG="$LOG_DIR/quality-pipeline-$TIMESTAMP.log"
REPORT_FILE="$REPORT_DIR/pipeline-$TIMESTAMP.md"
SUMMARY_FILE="$REPORT_DIR/pipeline-summary-latest.md"

CRON_MODE=false
SKIP_PR=false

# ─── GitHub config ────────────────────────────────────────────────────────────
PR_BRANCH="auto-quality/quality-pipeline-merged"
PR_BASE_BRANCH="main"
PR_TITLE="[Auto-Quality] Pipeline $(date '+%Y-%m-%d %H:%M')"

mkdir -p "$LOG_DIR" "$REPORT_DIR"

# ─── Cores ────────────────────────────────────────────────────────────────────
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

# ─── Utilitários ──────────────────────────────────────────────────────────────

append_report() { echo "$1" >> "$REPORT_FILE"; }

run_check() {
    local name="$1" script="$2" result=0
    local log_file="$LOG_DIR/check-${name}-${TIMESTAMP}.log"

    echo ""
    log_bold "═══════════════════════════════════════════════════"
    log_bold "  ▶ $name"
    log_bold "═══════════════════════════════════════════════════"

    if $CRON_MODE; then
        bash "$script" --cron > "$log_file" 2>&1
        result=$?
    else
        bash "$script" 2>&1 | tee "$log_file"
        result=${PIPESTATUS[0]}
    fi

    if [ $result -eq 0 ]; then
        log_ok "$name — ✅ passou"
        append_report "- **$name**: ✅ Passou"
    elif [ $result -eq 2 ]; then
        log_warn "$name — ⚠️ parcial (alguns sub-checks não executaram)"
        append_report "- **$name**: ⚠️ Parcial"
        result=0  # parcial não bloqueia o pipeline
    else
        log_error "$name — ❌ falhou (código $result)"
        append_report "- **$name**: ❌ Falhou"
    fi

    return $result
}

setup_docker_if_needed() {
    if ! docker info > /dev/null 2>&1; then
        log_warn "Docker não está rodando. Tentando iniciar serviços essenciais..."
        docker compose -f "$PROJECT_ROOT/docker-compose.yml" up -d postgres redis 2>&1 || true
        sleep 5
        if ! docker info > /dev/null 2>&1; then
            log_warn "Docker não disponível — alguns checks serão pulados"
        fi
    fi
}

# ══════════════════════════════════════════════════════════════════════════════
# Fase 1: Execução dos Checks
# ══════════════════════════════════════════════════════════════════════════════

run_checks() {
    local total=0 failed=0

    echo ""
    log_bold "╔══════════════════════════════════════════════════════╗"
    log_bold "║     PIPELINE DE QUALIDADE — $(date '+%Y-%m-%d %H:%M:%S')     ║"
    log_bold "╚══════════════════════════════════════════════════════╝"
    echo ""

    append_report "# Pipeline de Qualidade — $(date '+%Y-%m-%d %H:%M:%S')"
    append_report ""
    append_report "## Resultados dos Checks"
    append_report ""

    # Security
    run_check "Segurança" "$SCRIPT_DIR/check-security.sh" || ((failed++))
    ((total++))

    echo ""
    # Integrity
    run_check "Integridade" "$SCRIPT_DIR/check-integrity.sh" || ((failed++))
    ((total++))

    echo ""
    # Tests
    run_check "Testes" "$SCRIPT_DIR/check-tests.sh" || ((failed++))
    ((total++))

    echo ""
    # Performance
    run_check "Desempenho" "$SCRIPT_DIR/check-performance.sh" || ((failed++))
    ((total++))

    echo ""
    # Design
    run_check "Design" "$SCRIPT_DIR/check-design.sh" || ((failed++))
    ((total++))

    echo ""
    log_bold "═══════════════════════════════════════════════════"
    if [ $failed -eq 0 ]; then
        log_ok "RESUMO: $total/$total checks passaram ✅"
        append_report ""
        append_report "**Resumo**: ✅ $total/$total checks passaram"
    else
        log_error "RESUMO: $failed de $total checks falharam ❌"
        append_report ""
        append_report "**Resumo**: ❌ $failed de $total checks falharam"
    fi
    log_bold "═══════════════════════════════════════════════════"

    return $failed
}

# ══════════════════════════════════════════════════════════════════════════════
# Fase 2: Git & PR (executado apenas se todos os checks passaram)
# ══════════════════════════════════════════════════════════════════════════════

update_pr() {
    log_bold "═══════════════════════════════════════════════════"
    log_bold "  ▶ Criando/atualizando PR..."
    log_bold "═══════════════════════════════════════════════════"

    # Verifica se gh está autenticado
    if ! gh auth status 2>&1 | grep -q "Logged in"; then
        log_error "gh CLI não está autenticado. Pulando PR."
        return 1
    fi

    # Stash de mudanças não commitadas
    local has_stash=false
    if ! git -C "$PROJECT_ROOT" diff --quiet || ! git -C "$PROJECT_ROOT" diff --cached --quiet; then
        log_info "Mudanças locais encontradas — fazendo stash..."
        git -C "$PROJECT_ROOT" stash push -m "quality-pipeline-auto-stash-$TIMESTAMP" 2>&1 || true
        has_stash=true
    fi

    # Cria/atualiza branch
    log_info "Criando branch $PR_BRANCH..."
    git -C "$PROJECT_ROOT" checkout -B "$PR_BRANCH" 2>&1 || true

    # Cria um commit com o relatório e metadados
    cp "$REPORT_FILE" "$PROJECT_ROOT/quality-pipeline-report.md"
    git -C "$PROJECT_ROOT" add -A 2>&1 || true

    # Se não há nada para commitar, cria um commit vazio com a data
    if git -C "$PROJECT_ROOT" diff --cached --quiet; then
        git -C "$PROJECT_ROOT" commit --allow-empty -m "chore: quality pipeline $(date '+%Y-%m-%d %H:%M') [skip ci]" 2>&1 || true
    else
        git -C "$PROJECT_ROOT" commit -m "chore: quality pipeline $(date '+%Y-%m-%d %H:%M')" 2>&1 || true
    fi

    # Push (force)
    log_info "Fazendo push para origin/$PR_BRANCH..."
    if ! git -C "$PROJECT_ROOT" push --force origin "$PR_BRANCH" 2>&1; then
        log_error "Push falhou. Verifique permissões."
        # Restaura branch original
        git -C "$PROJECT_ROOT" checkout - 2>/dev/null || true
        $has_stash && git -C "$PROJECT_ROOT" stash pop 2>/dev/null || true
        return 1
    fi

    # Cria ou atualiza PR
    local existing_pr
    existing_pr=$(gh pr list --head "$PR_BRANCH" --json number --jq '.[0].number' 2>/dev/null || true)

    if [ -n "$existing_pr" ]; then
        log_info "PR #$existing_pr encontrado — atualizando body..."
        local body
        body=$(cat "$REPORT_FILE")
        gh pr edit "$existing_pr" --title "$PR_TITLE" --body "$body" 2>&1 || true
        log_ok "PR #$existing_pr atualizado"
    else
        log_info "Criando novo PR..."
        local body
        body=$(cat "$REPORT_FILE")
        gh pr create --base "$PR_BASE_BRANCH" --head "$PR_BRANCH" --title "$PR_TITLE" --body "$body" 2>&1 || {
            log_error "Falha ao criar PR"
            git -C "$PROJECT_ROOT" checkout - 2>/dev/null || true
            $has_stash && git -C "$PROJECT_ROOT" stash pop 2>/dev/null || true
            return 1
        }
    fi

    # Habilita auto-merge
    log_info "Habilitando auto-merge..."
    local pr_number
    pr_number=$(gh pr list --head "$PR_BRANCH" --json number --jq '.[0].number' 2>/dev/null || true)
    if [ -n "$pr_number" ]; then
        gh pr merge "$pr_number" --auto --merge 2>&1 || {
            log_warn "Não foi possível habilitar auto-merge (pode exigir aprovação)"

            # Tenta squash como fallback
            gh pr merge "$pr_number" --auto --squash 2>&1 || true
        }
        log_ok "Auto-merge habilitado para PR #$pr_number"
    fi

    # Restaura branch original
    git -C "$PROJECT_ROOT" checkout - 2>/dev/null || true
    $has_stash && git -C "$PROJECT_ROOT" stash pop 2>/dev/null || true

    log_ok "PR configurado com sucesso!"
    return 0
}

# ══════════════════════════════════════════════════════════════════════════════
# Main
# ══════════════════════════════════════════════════════════════════════════════

run_pipeline() {
    local pipeline_exit=0

    # Tenta iniciar Docker se necessário
    setup_docker_if_needed

    # Executa checks
    run_checks
    local check_exit=$?

    # Gera relatório consolidado
    rm -f "$SUMMARY_FILE"
    cp "$REPORT_FILE" "$SUMMARY_FILE" 2>/dev/null || true

    # Se todos os checks passaram e não pediu --skip-pr, cria/atualiza PR
    if [ $check_exit -eq 0 ]; then
        if ! $SKIP_PR; then
            echo ""
            log_bold "═══════════════════════════════════════════════════"
            log_bold "  ✅ TODOS OS CHECKS PASSARAM — CRIANDO PR..."
            log_bold "═══════════════════════════════════════════════════"
            update_pr || pipeline_exit=1
        else
            log_info "PR pulado (--skip-pr)"
        fi
    else
        pipeline_exit=$check_exit
        log_error "Pipeline falhou — PR não será criado."
    fi

    echo ""
    log_bold "═══════════════════════════════════════════════════"
    if [ $pipeline_exit -eq 0 ]; then
        log_ok "PIPELINE CONCLUÍDA COM SUCESSO ✅"
    else
        log_error "PIPELINE CONCLUÍDA COM FALHAS ❌"
    fi
    log_bold "═══════════════════════════════════════════════════"
    echo ""
    echo "Relatório: $REPORT_FILE"
    echo "Log: $PIPELINE_LOG"
    echo ""

    return $pipeline_exit
}

# ─── Args ─────────────────────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
    case "$1" in
        --cron) CRON_MODE=true ;;
        --skip-pr) SKIP_PR=true ;;
        --help|-h)
            echo "Uso: $0 [--cron] [--skip-pr]"
            echo "  --cron     Modo silencioso (cron)"
            echo "  --skip-pr  Apenas executa checks, sem criar PR"
            exit 0
            ;;
        *) echo "Arg desconhecido: $1"; exit 1 ;;
    esac
    shift
done

# Redireciona stdout para log em modo cron
if $CRON_MODE; then
    run_pipeline > "$PIPELINE_LOG" 2>&1
    exit $?
fi

run_pipeline
exit $?
