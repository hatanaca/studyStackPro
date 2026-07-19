#!/usr/bin/env bash
# =============================================================================
source "$SCRIPT_DIR/sentry-lib.sh"
# quality-pipeline.sh — Orquestrador da Pipeline de Qualidade
#
# Pipeline completa: evolução → segurança → integridade → testes → desempenho → design
# Em modo fix: aplica correções automaticamente, commita e cria PR com auto-merge.
# Em modo normal: apenas verifica e reporta.
#
# Uso:
#   ./quality-pipeline.sh                        # modo interativo (fix + PR)
#   ./quality-pipeline.sh --cron                 # modo silencioso (cron)
#   ./quality-pipeline.sh --skip-pr              # só checks, sem PR
#   ./quality-pipeline.sh --no-fix               # só verifica, sem auto-fix
#   ./quality-pipeline.sh --no-ai                # pula análise inteligente
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

# ─── GitHub config ────────────────────────────────────────────────────────────
PR_BRANCH="auto-quality/quality-pipeline-merged"
PR_BASE_BRANCH="main"
PR_TITLE="[Auto-Quality] Melhorias $(date '+%Y-%m-%d %H:%M')"

CRON_MODE=false
SKIP_PR=false
NO_FIX=false
NO_AI=false

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
append_report() { echo "$1" >> "$REPORT_FILE"; }

# ─── Executa um check (com --fix se habilitado) ───────────────────────────────
run_check() {
    local name="$1" script="$2" extra_flags="${3:-}" result=0
    local log_file="$LOG_DIR/check-${name}-${TIMESTAMP}.log"
    local args=""

    $NO_FIX || args="$args --fix"
    [ -n "$extra_flags" ] && args="$args $extra_flags"

    echo ""
    log_bold "═══════════════════════════════════════════════════"
    log_bold "  ▶ $name"
    if $NO_FIX; then log_bold "  (modo verificação)"; else log_bold "  (modo reparo automático)"; fi
    if [ -n "$extra_flags" ]; then log_bold "  flags: $extra_flags"; fi
    log_bold "═══════════════════════════════════════════════════"

    if $CRON_MODE; then
        bash "$script" --cron $args > "$log_file" 2>&1
        result=$?
    else
        bash "$script" $args 2>&1 | tee "$log_file"
        result=${PIPESTATUS[0]}
    fi

    local fixes_applied
    fixes_applied=$(grep -ci 'corrigid\|auto-fix\|corrigido\|fixed\|Corrigido\|iniciado\|Atualizada\|Stage pronto\|Executadas\|rebuild\|melhoria' "$log_file" 2>/dev/null || echo 0)

    if [ $result -eq 0 ]; then
        if [ "$fixes_applied" -gt 0 ]; then
            log_ok "$name — ✅ passou ($fixes_applied correção(ões) aplicada(s))"
            append_report "- **$name**: ✅ OK + $fixes_applied auto-fix(es)"
        else
            log_ok "$name — ✅ passou"
            append_report "- **$name**: ✅ Passou"
        fi
    elif [ $result -eq 2 ]; then
        log_warn "$name — ⚠️ parcial"
        append_report "- **$name**: ⚠️ Parcial"
        result=0
    else
        log_error "$name — ❌ falhou"
        append_report "- **$name**: ❌ Falhou"
        [ "$fixes_applied" -gt 0 ] && append_report "  (mas $fixes_applied correção(ões) foram aplicadas)"
    fi

    return $result
}

# ─── Sobe serviços Docker se necessário ───────────────────────────────────────
setup_docker_if_needed() {
    if ! docker info > /dev/null 2>&1; then
        log_warn "Docker não está rodando. Tentando iniciar..."
        docker compose -f "$PROJECT_ROOT/docker-compose.yml" up -d postgres redis 2>&1 || true
        sleep 5
        docker info > /dev/null 2>&1 || log_warn "Docker não disponível"
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
    append_report "## Resultados"
    append_report ""

    # 1. Evolução (inteligente, com IA)
    local evo_flags=""
    $NO_AI && evo_flags="--no-ai"
    run_check "Evolução" "$SCRIPT_DIR/check-evolution.sh" "$evo_flags" || ((failed++)); ((total++)); echo ""
    # 2. Segurança
    run_check "Segurança" "$SCRIPT_DIR/check-security.sh" || ((failed++)); ((total++)); echo ""
    # 3. Integridade
    run_check "Integridade" "$SCRIPT_DIR/check-integrity.sh" || ((failed++)); ((total++)); echo ""
    # 4. Testes
    run_check "Testes" "$SCRIPT_DIR/check-tests.sh" || ((failed++)); ((total++)); echo ""
    # 5. Desempenho
    run_check "Desempenho" "$SCRIPT_DIR/check-performance.sh" || ((failed++)); ((total++)); echo ""
    # 6. Design
    # Sentry
    run_check "Sentry" "$SCRIPT_DIR/check-sentry.sh" || ((failed++)); ((total++)); echo ""
    run_check "Design" "$SCRIPT_DIR/check-design.sh" || ((failed++)); ((total++)); echo ""

    echo ""
    log_bold "═══════════════════════════════════════════════════"
    if [ $failed -eq 0 ]; then
        log_ok "RESUMO: $total/$total checks passaram ✅"
        append_report ""; append_report "**Resumo**: ✅ $total/$total checks passaram"
    else
        log_error "RESUMO: $failed de $total checks falharam ❌"
        append_report ""; append_report "**Resumo**: ❌ $failed de $total checks falharam"
    fi
    log_bold "═══════════════════════════════════════════════════"

    return $failed
}

# ══════════════════════════════════════════════════════════════════════════════
# Fase 2: Commit + PR
# ══════════════════════════════════════════════════════════════════════════════

commit_improvements() {
    echo ""
    log_bold "═══════════════════════════════════════════════════"
    log_bold "  ▶ Commit das melhorias..."
    log_bold "═══════════════════════════════════════════════════"

    if git -C "$PROJECT_ROOT" diff --quiet && git -C "$PROJECT_ROOT" diff --cached --quiet; then
        log_info "Nada para commitar"
        return 1
    fi

    git -C "$PROJECT_ROOT" add -A 2>&1 || true

    if git -C "$PROJECT_ROOT" diff --cached --quiet; then
        log_info "Nada após stage"
        return 1
    fi

    local file_count
    file_count=$(git -C "$PROJECT_ROOT" diff --cached --stat | tail -1 | grep -oP '\d+(?= files? changed)' || echo "vários")

    local commit_msg="chore: auto-quality — melhorias automáticas ($(date '+%Y-%m-%d %H:%M'))

## Melhorias aplicadas
- Evolução: dependências, análise IA, métricas de arquitetura
- Segurança: vulnerabilidades, code scan, secrets
- Integridade: permissões, migrations, Docker
- Testes: setup de ambiente
- Desempenho: PHPStan baseline, build
- Design: Pint, Prettier, ESLint
"

    git -C "$PROJECT_ROOT" commit -m "$commit_msg" 2>&1 || true
    log_ok "Commit: $file_count arquivo(s) alterado(s)"
    append_report "## Melhorias Commitadas"
    git -C "$PROJECT_ROOT" diff --stat HEAD~1 HEAD 2>/dev/null | while IFS= read -r line; do
        append_report "- $line"
    done
    return 0
}

update_pr() {
    log_bold "═══════════════════════════════════════════════════"
    log_bold "  ▶ Atualizando PR..."
    log_bold "═══════════════════════════════════════════════════"

    if ! gh auth status 2>&1 | grep -q "Logged in"; then
        log_error "gh não autenticado"; return 1
    fi

    git -C "$PROJECT_ROOT" push --force origin "$PR_BRANCH" 2>&1 || { log_error "Push falhou"; return 1; }

    cp "$REPORT_FILE" "$PROJECT_ROOT/quality-pipeline-report.md"
    local body; body=$(cat "$REPORT_FILE")

    local existing_pr
    existing_pr=$(gh pr list --head "$PR_BRANCH" --json number --jq '.[0].number' 2>/dev/null || true)

    if [ -n "$existing_pr" ]; then
        gh pr edit "$existing_pr" --title "$PR_TITLE" --body "$body" 2>&1 || true
        log_ok "PR #$existing_pr atualizado"
    else
        gh pr create --base "$PR_BASE_BRANCH" --head "$PR_BRANCH" --title "$PR_TITLE" --body "$body" 2>&1 || { log_error "Falha ao criar PR"; return 1; }
    fi

    local pr_number
    pr_number=$(gh pr list --head "$PR_BRANCH" --json number --jq '.[0].number' 2>/dev/null || true)
    if [ -n "$pr_number" ]; then
        gh pr merge "$pr_number" --auto --merge 2>&1 || gh pr merge "$pr_number" --auto --squash 2>&1 || true
        log_ok "Auto-merge para PR #$pr_number"
    fi
    return 0
}

# ══════════════════════════════════════════════════════════════════════════════
# Main
# ══════════════════════════════════════════════════════════════════════════════

run_pipeline() {
    local pipeline_exit=0

    log_bold "═══════════════════════════════════════════════════"
    log_bold "  PIPELINE DE QUALIDADE — $(date '+%Y-%m-%d %H:%M:%S')"
    log_bold "═══════════════════════════════════════════════════"
    echo ""

    setup_docker_if_needed

    local original_branch
    original_branch=$(git -C "$PROJECT_ROOT" rev-parse --abbrev-ref HEAD 2>/dev/null || echo "HEAD")

    local has_stash=false
    if ! git -C "$PROJECT_ROOT" diff --quiet || ! git -C "$PROJECT_ROOT" diff --cached --quiet; then
        log_info "Salvando alterações locais em stash..."
        git -C "$PROJECT_ROOT" stash push -m "quality-pipeline-stash-$TIMESTAMP" 2>&1 || true
        has_stash=true
    fi

    git -C "$PROJECT_ROOT" checkout -B "$PR_BRANCH" 2>&1 || true

    run_checks
    local check_exit=$?

    rm -f "$SUMMARY_FILE"
    cp "$REPORT_FILE" "$SUMMARY_FILE" 2>/dev/null || true

    # Sempre tenta commitar e atualizar PR (mesmo com falhas parciais)
    if ! $NO_FIX; then
        commit_improvements || true
        if ! $SKIP_PR; then
            update_pr || pipeline_exit=1
        fi
    fi

    # Reporta resultado geral ao Sentry
    sentry_report_pipeline $total $failed "Pipeline de qualidade $(date +%Y-%m-%d)"
    # Restaura estado original
    git -C "$PROJECT_ROOT" checkout "$original_branch" 2>/dev/null || true
    $has_stash && git -C "$PROJECT_ROOT" stash pop 2>/dev/null || true

    echo ""
    log_bold "═══════════════════════════════════════════════════"
    if [ $pipeline_exit -eq 0 ] && [ $check_exit -eq 0 ]; then
        log_ok "PIPELINE CONCLUÍDA — REPOSITÓRIO MELHORADO ✅"
    else
        log_warn "PIPELINE CONCLUÍDA (com ressalvas) ⚠️"
    fi
    log_bold "═══════════════════════════════════════════════════"
    echo "Relatório: $REPORT_FILE"
    echo ""

    return $pipeline_exit
}

# ─── Arg parser ──────────────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
    case "$1" in
        --cron) CRON_MODE=true ;;
        --skip-pr) SKIP_PR=true ;;
        --no-fix) NO_FIX=true ;;
        --no-ai) NO_AI=true ;;
        --help|-h)
            echo "Uso: $0 [--cron] [--skip-pr] [--no-fix] [--no-ai]"
            echo ""
            echo "  --cron     Modo silencioso (cron)"
            echo "  --skip-pr  Apenas checks, sem PR"
            echo "  --no-fix   Apenas verificação, sem auto-correção"
            echo "  --no-ai    Pula análise inteligente (evolution)"
            exit 0
            ;;
        *) echo "Arg desconhecido: $1"; exit 1 ;;
    esac
    shift
done

if $CRON_MODE; then
    run_pipeline > "$PIPELINE_LOG" 2>&1
    exit $?
fi

run_pipeline
exit $?
