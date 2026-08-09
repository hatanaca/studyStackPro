#!/usr/bin/env bash
# =============================================================================
# setup-cron.sh — Configura o crontab para executar verificações periódicas
#
# Uso:
#   ./setup-cron.sh                       # Modo interativo
#   ./setup-cron.sh --auto                # Configuração automática (padrões)
#   ./setup-cron.sh --slack-webhook URL   # Com notificações Slack
#   ./setup-cron.sh --remove              # Remove os jobs do crontab
# =============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
CRON_FILE="/tmp/studytrack-cron-$$.txt"
REMOVE_MODE=false
AUTO_MODE=false
SLACK_WEBHOOK=""

# Cores
if [ -t 1 ]; then
    RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
else
    RED=''; GREEN=''; YELLOW=''; CYAN=''; NC=''
fi

echo ""
echo "${CYAN}╔══════════════════════════════════════════════════════╗${NC}"
echo "${CYAN}║      Configuração de Monitoramento Periódico        ║${NC}"
echo "${CYAN}╚══════════════════════════════════════════════════════╝${NC}"
echo ""

# Processa argumentos
while [[ $# -gt 0 ]]; do
    case "$1" in
        --remove) REMOVE_MODE=true; shift ;;
        --auto) AUTO_MODE=true; shift ;;
        --slack-webhook) SLACK_WEBHOOK="$2"; shift 2 ;;
        --help|-h)
            echo "Uso: $0 [--auto] [--remove] [--slack-webhook URL]"
            exit 0
            ;;
        *) echo "Argumento desconhecido: $1"; exit 1 ;;
    esac
done

if $REMOVE_MODE; then
    echo "Removendo jobs de monitoramento do crontab..."
    crontab -l 2>/dev/null | grep -v 'monitoring/' > "$CRON_FILE" || true
    crontab "$CRON_FILE"
    rm -f "$CRON_FILE"
    echo "${GREEN}Jobs de monitoramento removidos.${NC}"
    exit 0
fi

# Coleta configurações
if ! $AUTO_MODE; then
    echo "Este script vai configurar o crontab para executar verificações periódicas."
    echo ""
    echo "Os seguintes jobs serão criados:"
    echo ""
    echo "  ├── A cada 5 min  → Health Check (monitora se app está no ar)"
    echo "  ├── A cada 1 h    → Sentry Monitor (consulta erros no Sentry)"
    echo "  ├── A cada 2 h    → Quality Pipeline (checks completos)"
    echo "  ├── A cada 3 h    → MiMo Schedule (prompts AI de teste)"
    echo "  ├── A cada 4 h    → Code Scan (análise estática do código)"
    echo "  └── Diariamente    → Run All (relatório consolidado)"
    echo ""
    echo "Pressione ENTER para continuar ou Ctrl+C para cancelar..."
    read -r
fi

# Cria o arquivo de configuração do Sentry se não existir
if [ ! -f "$SCRIPT_DIR/.sentry.env" ]; then
    SENTRY_ENV_FILE="$SCRIPT_DIR/.sentry.env"
    cat > "$SENTRY_ENV_FILE" << 'EOF'
# Configuração do Sentry para monitoramento
# Preencha com seus dados do Sentry.io
SENTRY_AUTH_TOKEN=""
SENTRY_ORG=""
SENTRY_PROJECT=""
SLACK_WEBHOOK=""
EOF
    chmod 600 "$SENTRY_ENV_FILE" 2>/dev/null || true
    echo "${YELLOW}Arquivo $SENTRY_ENV_FILE criado.${NC}"
    echo "${YELLOW}Preencha SENTRY_AUTH_TOKEN e SENTRY_ORG para ativar o monitoramento Sentry.${NC}"
    echo ""
fi

# Monta o crontab
crontab -l 2>/dev/null | grep -v 'monitoring/' > "$CRON_FILE" || true

echo "" >> "$CRON_FILE"
echo "# === StudyTrack Pro — Monitoramento Periódico ===" >> "$CRON_FILE"
echo "# Adicionado em $(date '+%Y-%m-%d %H:%M:%S')" >> "$CRON_FILE"

# Health check a cada 5 minutos
if [ -n "$SLACK_WEBHOOK" ]; then
    echo "*/5 * * * * cd $SCRIPT_DIR && bash health-check.sh --cron --slack-webhook '$SLACK_WEBHOOK' 2>&1 | logger -t studytrack-health" >> "$CRON_FILE"
else
    echo "*/5 * * * * cd $SCRIPT_DIR && bash health-check.sh --cron 2>&1 | logger -t studytrack-health" >> "$CRON_FILE"
fi

# Code scan a cada 4 horas
if [ -n "$SLACK_WEBHOOK" ]; then
    echo "0 */4 * * * cd $SCRIPT_DIR && bash code-scan.sh --cron --slack-webhook '$SLACK_WEBHOOK' 2>&1 | logger -t studytrack-codescan" >> "$CRON_FILE"
else
    echo "0 */4 * * * cd $SCRIPT_DIR && bash code-scan.sh --cron 2>&1 | logger -t studytrack-codescan" >> "$CRON_FILE"
fi

# Sentry monitor a cada hora (se configurado)
echo "# Sentry Monitor (requer .sentry.env configurado)" >> "$CRON_FILE"
echo "0 * * * * cd $SCRIPT_DIR && [ -f .sentry.env ] && . .sentry.env && [ -n \"\$SENTRY_AUTH_TOKEN\" ] && bash sentry-check.sh --cron 2>&1 | logger -t studytrack-sentry || true" >> "$CRON_FILE"

# Run-all uma vez por dia
echo "0 6 * * * cd $SCRIPT_DIR && bash run-all.sh --cron 2>&1 | logger -t studytrack-all" >> "$CRON_FILE"

# Pipeline de Qualidade a cada 2 horas
echo "# Pipeline de Qualidade (segurança, integridade, testes, desempenho, design) a cada 2 horas" >> "$CRON_FILE"
echo "0 */2 * * * cd $SCRIPT_DIR && bash quality-pipeline.sh --cron 2>&1 | logger -t studytrack-quality" >> "$CRON_FILE"

# MiMo Schedule — execução agendada de prompts a cada 3 horas
echo "# MiMo Schedule — prompts de teste agendados" >> "$CRON_FILE"
echo "0 */3 * * * cd $SCRIPT_DIR && bash mimo-schedule.sh --once 2>&1 | logger -t studytrack-mimo" >> "$CRON_FILE"

# Instala o crontab
if crontab "$CRON_FILE"; then
    echo "${GREEN}✅ Crontab configurado com sucesso!${NC}"
    echo ""
    echo "${CYAN}Jobs instalados:${NC}"
    echo ""
    echo "  ┌─────────────────────┬──────────────────────────────────────────┐"
    echo "  │ Intervalo           │ Job                                      │"
    echo "  ├─────────────────────┼──────────────────────────────────────────┤"
    echo "  │ A cada 5 min        │ health-check.sh                          │"
    echo "  │ A cada 1 h          │ sentry-check.sh                          │"
    echo "  │ A cada 2 h          │ quality-pipeline.sh                      │"
    echo "  │ A cada 3 h          │ mimo-schedule.sh (prompts AI)            │"
    echo "  │ A cada 4 h          │ code-scan.sh                             │"
    echo "  │ Diariamente às 6h   │ run-all.sh (relatório completo)          │"
    echo "  └─────────────────────┴──────────────────────────────────────────┘"
    echo ""
    echo "Logs: ${SCRIPT_DIR}/logs/"
    echo ""
else
    echo "${RED}❌ Erro ao instalar crontab.${NC}"
    exit 1
fi

rm -f "$CRON_FILE"

echo ""
echo "${CYAN}Para remover os jobs:${NC} $0 --remove"
echo "${CYAN}Para ver os jobs ativos:${NC} crontab -l"
echo ""
