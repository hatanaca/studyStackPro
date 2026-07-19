#!/usr/bin/env bash
# =============================================================================
# start-quality.sh — Inicia o Fluxo Completo de Qualidade do StudyTrack Pro
#
# Executa a pipeline completa em modo de melhoria automática: verifica o
# código, aplica correções, commita as melhorias e cria um PR com auto-merge.
#
# Uso:
#   ./start-quality.sh                        # Fluxo completo (fix + PR)
#   ./start-quality.sh --no-fix               # Só verificar, sem correções
#   ./start-quality.sh --no-ai                # Sem análise inteligente
#   ./start-quality.sh --skip-pr              # Sem Pull Request
#   ./start-quality.sh --setup-cron           # Instala no crontab (a cada 2h)
#   ./start-quality.sh --status               # Mostra status do último relatório
#   ./start-quality.sh --help                 # Ajuda
#
# Fluxo executado:
#   1. EVOLUÇÃO  → dependências, IA, métricas de arquitetura
#   2. SEGURANÇA  → testes de segurança, auditoria, code scan
#   3. INTEGRIDADE → git, migrations, permissões, Docker
#   4. TESTES      → backend (PHPUnit) + frontend (Vitest)
#   5. DESEMPENHO  → PHPStan, health check, build
#   6. SENTRY      → consulta erros no Sentry
#   7. DESIGN      → Pint, type-check, ESLint, Prettier
#   → Commit + PR + Auto-merge no GitHub
#
# Relatórios em: monitoring/reports/
# Atalho: ~/qualidade
# =============================================================================

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
PIPELINE_SCRIPT="$PROJECT_ROOT/monitoring/quality-pipeline.sh"
CRON_SCRIPT="$PROJECT_ROOT/monitoring/setup-cron.sh"
REPORT_DIR="$PROJECT_ROOT/monitoring/reports"

# Cores
if [ -t 1 ]; then
    CYAN='\033[0;36m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; RED='\033[0;31m'; BOLD='\033[1m'; NC='\033[0m'
else
    CYAN=''; GREEN=''; YELLOW=''; RED=''; BOLD=''; NC=''
fi

log_info()  { echo -e "${CYAN}[start-quality]${NC} $1"; }
log_ok()    { echo -e "${GREEN}[OK]${NC} $1"; }
log_warn()  { echo -e "${YELLOW}[WARN]${NC} $1"; }
log_error() { echo -e "${RED}[ERROR]${NC} $1"; }

show_help() {
    cat << HELP

${BOLD}start-quality.sh — Fluxo Completo de Qualidade${NC}

Uso: ./start-quality.sh [opções]

Opções:
  --help               Mostra esta ajuda
  --no-fix             Apenas verificação, sem auto-correção
  --no-ai              Pula análise inteligente (evolution)
  --skip-pr            Apenas checks, sem PR
  --setup-cron         Instala no crontab (execução a cada 2h)
  --status             Mostra resumo do último relatório
  --all                Executa pipeline e depois instala cron (padrão)

SEM OPÇÕES: Executa pipeline completa com auto-fix, PR e auto-merge.

Exemplos:
  ./start-quality.sh                    # Tudo: verificar + corrigir + PR
  ./start-quality.sh --no-fix           # Só verificar
  ./start-quality.sh --setup-cron       # Instalar agendamento
  ~/qualidade                           # Mesmo fluxo de qualquer lugar

HELP
}

show_status() {
    echo ""
    echo "${BOLD}═══ Último relatório da pipeline ═══${NC}"
    local latest
    latest=$(ls -t "$REPORT_DIR"/pipeline-*.md 2>/dev/null | head -1)
    if [ -z "$latest" ]; then
        log_info "Nenhum relatório encontrado. Execute a pipeline primeiro."
        return
    fi
    echo "  Arquivo: $latest"
    echo ""
    cat "$latest"
    echo ""
}

setup_cron() {
    echo ""
    log_info "Instalando cron para execução a cada 2 horas..."
    if [ -f "$CRON_SCRIPT" ]; then
        bash "$CRON_SCRIPT" --auto
        log_ok "Cron configurado! A pipeline rodará automaticamente."
    else
        log_error "Script de cron não encontrado: $CRON_SCRIPT"
        return 1
    fi
}

# ══════════════════════════════════════════════════════════════════════════════
# Main
# ══════════════════════════════════════════════════════════════════════════════

case "${1:-}" in
    --help|-h)
        show_help
        exit 0
        ;;
    --status)
        show_status
        exit 0
        ;;
    --setup-cron)
        setup_cron
        exit $?
        ;;
    --all)
        # Executa pipeline + instala cron
        echo ""
        echo "${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
        echo "${BOLD}║   StudyTrack Pro — Quality Pipeline                 ║${NC}"
        echo "${BOLD}║   $(date '+%Y-%m-%d %H:%M:%S')                     ║${NC}"
        echo "${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
        echo ""

        if [ ! -f "$PIPELINE_SCRIPT" ]; then
            log_error "Pipeline não encontrada em $PIPELINE_SCRIPT"
            exit 1
        fi

        bash "$PIPELINE_SCRIPT"
        local exit_code=$?

        echo ""
        setup_cron || true
        exit $exit_code
        ;;
    --no-fix|--no-ai|--skip-pr)
        # Passa argumento diretamente para a pipeline
        echo ""
        echo "${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
        echo "${BOLD}║   StudyTrack Pro — Quality Pipeline                 ║${NC}"
        echo "${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
        echo ""

        if [ ! -f "$PIPELINE_SCRIPT" ]; then
            log_error "Pipeline não encontrada"
            exit 1
        fi

        bash "$PIPELINE_SCRIPT" "$@"
        exit $?
        ;;
    "")
        # Padrão: executa pipeline completa
        echo ""
        echo "${BOLD}╔══════════════════════════════════════════════════════╗${NC}"
        echo "${BOLD}║   StudyTrack Pro — Quality Pipeline                 ║${NC}"
        echo "${BOLD}║   $(date '+%Y-%m-%d %H:%M:%S')                     ║${NC}"
        echo "${BOLD}╚══════════════════════════════════════════════════════╝${NC}"
        echo ""
        echo "  Estágios:"
        echo "    1. Evolução     (dependências + IA + métricas)"
        echo "    2. Segurança    (testes + audit + code scan)"
        echo "    3. Integridade  (git + migrations + Docker)"
        echo "    4. Testes       (PHPUnit + Vitest)"
        echo "    5. Desempenho   (PHPStan + health + build)"
        echo "    6. Sentry       (consulta erros)"
        echo "    7. Design       (Pint + type-check + ESLint + Prettier)"
        echo ""
        echo "  Modo: auto-fix ligado → correções são commitadas e PR criado"
        echo ""

        if [ ! -f "$PIPELINE_SCRIPT" ]; then
            log_error "Pipeline não encontrada em $PIPELINE_SCRIPT"
            exit 1
        fi

        bash "$PIPELINE_SCRIPT"
        exit $?
        ;;
    *)
        log_error "Argumento desconhecido: $1"
        echo "Use --help para ajuda."
        exit 1
        ;;
esac
