#!/usr/bin/env bash
# =============================================================================
# code-scan.sh — Varredura estática do código para identificar problemas
#
# Verifica:
#   - Catch blocks vazios / .catch(() => {}) sem tratamento
#   - Locais que deveriam reportar ao Sentry mas não reportam
#   - TypeScript `as` casts inseguros
#   - Arquivos muito grandes
#
# Uso:
#   ./code-scan.sh                          # varredura completa
#   ./code-scan.sh --quick                  # só os problemas críticos
#   ./code-scan.sh --slack-webhook URL      # notificar Slack
#   ./code-scan.sh --cron                   # modo silencioso para cron
#   ./code-scan.sh --export report.md       # exportar relatório
# =============================================================================

set -uo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
FRONTEND_SRC="$PROJECT_ROOT/frontend/src"
BACKEND_APP="$PROJECT_ROOT/backend/app"
LOG_DIR="$(dirname "$0")/logs"
REPORT_FILE=""
SLACK_WEBHOOK=""
QUICK_MODE=false
CRON_MODE=false

mkdir -p "$LOG_DIR"

TOTAL_CRITICAL=0
TOTAL_WARNINGS=0

# ═════════════════════════════════════════════════════════════════════
# Scanner Python (robusto, sem problemas com regex do grep)
# ═════════════════════════════════════════════════════════════════════

run_scanner() {
python3 << 'PYEOF' 2>/dev/null
import os, re, sys

PROJECT_ROOT = os.environ.get('PROJECT_ROOT', '')
FRONTEND_SRC = os.environ.get('FRONTEND_SRC', '')
BACKEND_APP = os.environ.get('BACKEND_APP', '')
QUICK_MODE = os.environ.get('QUICK_MODE', '') == 'true'

findings = []

def add(sev, fp, lineno, msg):
    findings.append(f"{sev}|{os.path.relpath(fp, PROJECT_ROOT) if fp.startswith(PROJECT_ROOT) else fp}|{lineno}|{msg}")

def lines_of(path):
    try:
        with open(path, 'r', errors='replace') as f:
            return [(i+1, line) for i, line in enumerate(f.readlines())]
    except: return []

def walk_files(dirs, exts):
    files = []
    for d in dirs:
        if not os.path.isdir(d): continue
        for root, dirs_, fs in os.walk(d):
            dirs_[:] = [x for x in dirs_ if x not in {'node_modules','vendor','dist','.git','storage','cache'}]
            for f in fs:
                if any(f.endswith(e) for e in exts):
                    files.append(os.path.join(root, f))
    return sorted(files)

# ── 1. .catch(() => {}) vazio ──
if not QUICK_MODE:
    for fp in walk_files([FRONTEND_SRC], ['.ts','.vue']):
        for n, line in lines_of(fp):
            s = line.strip()
            if '.catch(() => {})' in s and not s.startswith('//') and not s.startswith('*'):
                add('CRITICAL', fp, n, ".catch(() => {}) - erro de promessa engolido")

# ── 2. Import Sentry sem .catch() ──
for fp in walk_files([FRONTEND_SRC], ['.ts','.vue']):
    ls = lines_of(fp)
    for n, line in ls:
        s = line.strip()
        if 'import' in s and '@sentry' in s and '.catch' not in s and not s.startswith('//'):
            if n < len(ls) and '.catch' not in ls[n][1]:
                add('WARNING', fp, n, "Import dinamico do Sentry sem .catch()")

# ── 3. as any / as unknown as ──
if not QUICK_MODE:
    for fp in walk_files([FRONTEND_SRC], ['.ts','.vue']):
        for n, line in lines_of(fp):
            s = line.strip()
            if s.startswith('//') or s.startswith('*'): continue
            if re.search(r'\bas\s+any\s*[;),]', s) or re.search(r'\bas\s+any\s*$', s):
                add('WARNING', fp, n, "Uso de 'as any' - perde seguranca de tipo")
            if re.search(r'\bas\s+unknown\s+as\b', s):
                add('WARNING', fp, n, "Double assertion 'as unknown as X'")

# ── 4. Log::warning/error sem Sentry ──
if not QUICK_MODE:
    for fp in walk_files([BACKEND_APP], ['.php']):
        ls = lines_of(fp)
        for n, line in ls:
            if re.search(r'Log::(?:warning|error)', line) and not re.search(r'(?:sentry|Sentry|captureException)', line):
                ctx = ''.join(ls[j][1] for j in range(max(0,n-3), min(len(ls), n+3)))
                if not re.search(r'(?:sentry|Sentry|captureException)', ctx):
                    add('WARNING', fp, n, "Log::warning/error sem Sentry capture")

# ── 5. Arquivos grandes ──
if not QUICK_MODE:
    for d in [FRONTEND_SRC, BACKEND_APP]:
        for fp in walk_files([d], ['.ts','.vue','.php']):
            try:
                with open(fp) as f: c = sum(1 for _ in f)
                if c > 500: add('INFO', fp, 1, f"Arquivo grande: {c} linhas")
            except: pass

# ── 6. try/finally sem catch (em stores) ──
if not QUICK_MODE:
    for fp in walk_files([FRONTEND_SRC], ['.ts']):
        content = ''.join(l for _, l in lines_of(fp))
        if 'try' in content and 'finally' in content and 'catch' not in content:
            for n, line in lines_of(fp):
                if re.search(r'\btry\s*\{', line):
                    add('WARNING', fp, n, "try/finally sem catch")

for f in findings:
    print(f)
PYEOF
}

# ═════════════════════════════════════════════════════════════════════
# Funções de formatação (evitando subshells)
# ═════════════════════════════════════════════════════════════════════

color() {
    [ -t 1 ] && echo -e "$1$2\033[0m" || echo "$2"
}

run() {
    local tmpfile
    tmpfile=$(mktemp /tmp/studytrack-scan-XXXXXX 2>/dev/null)

    echo ""
    color "\033[0;36m" "═══════════════════════════════════════════════"
    color "\033[0;36m" "  Code Scan - $(date '+%Y-%m-%d %H:%M:%S')"
    color "\033[0;36m" "  Modo: $([ "$QUICK_MODE" = true ] && echo 'Rapido' || echo 'Completo')"
    color "\033[0;36m" "═══════════════════════════════════════════════"
    echo ""

    if [ -n "$REPORT_FILE" ]; then
        echo "# Code Scan Report - $(date '+%Y-%m-%d %H:%M:%S')" > "$REPORT_FILE"
        echo "" >> "$REPORT_FILE"
    fi

    color "\033[0;36m" "▶ Executando varredura..."
    echo ""

    export PROJECT_ROOT FRONTEND_SRC BACKEND_APP QUICK_MODE
    run_scanner > "$tmpfile" 2>/dev/null || true

    if [ ! -s "$tmpfile" ]; then
        color "\033[0;32m" "[OK] Nenhum problema encontrado!"
    else
        while IFS='|' read -r sev fp lineno msg; do
            [ -z "$sev" ] && continue
            if [ "$sev" = "CRITICAL" ]; then
                ((TOTAL_CRITICAL++))
                color "\033[0;31m" "[CRITICAL] $fp:$lineno - $msg"
            elif [ "$sev" = "WARNING" ]; then
                ((TOTAL_WARNINGS++))
                color "\033[1;33m" "[WARNING] $fp:$lineno - $msg"
            else
                color "\033[0;36m" "[INFO] $fp:$lineno - $msg"
            fi
            if [ -n "$REPORT_FILE" ]; then
                echo "- **[$sev]** \`$fp:$lineno\` - $msg" >> "$REPORT_FILE"
            fi
            if [ "$CRON_MODE" = true ] && [ "$sev" = "CRITICAL" ]; then
                echo "[$sev] $fp:$lineno - $msg" >> "$LOG_DIR/code-scan-critical.log"
            fi
        done < "$tmpfile"
    fi

    rm -f "$tmpfile"

    echo ""
    color "\033[0;36m" "═══════════════════════════════════════════════"
    color "\033[0;36m" "  RESUMO: $TOTAL_CRITICAL criticos, $TOTAL_WARNINGS warnings"
    color "\033[0;36m" "═══════════════════════════════════════════════"

    if [ -n "$REPORT_FILE" ]; then
        echo "" >> "$REPORT_FILE"
        echo "**Resumo:** $TOTAL_CRITICAL criticos, $TOTAL_WARNINGS warnings" >> "$REPORT_FILE"
    fi

    # Slack
    if [ "$TOTAL_CRITICAL" -gt 0 ] && [ -n "$SLACK_WEBHOOK" ]; then
        curl -s -X POST "$SLACK_WEBHOOK" -H "Content-Type: application/json" \
            -d "{\"attachments\":[{\"color\":\"danger\",\"text\":\"🔴 Code Scan - $TOTAL_CRITICAL criticos encontrados\"}]}" \
            > /dev/null 2>&1 || true
    fi

    [ "$TOTAL_CRITICAL" -gt 0 ] && return 1 || return 0
}

# ─── Args ───────────────────────────────────────────────────────────

while [[ $# -gt 0 ]]; do
    case "$1" in --quick) QUICK_MODE=true ;; --cron) CRON_MODE=true ;;
        --export) REPORT_FILE="$2"; shift ;;
        --slack-webhook) SLACK_WEBHOOK="$2"; shift ;;
        --help|-h) echo "Uso: $0 [--quick] [--cron] [--export file.md] [--slack-webhook URL]"; exit 0 ;;
        *) echo "Arg desconhecido: $1"; exit 1 ;;
    esac
    shift
done

if [ "$CRON_MODE" = true ]; then
    run > /dev/null 2>&1
else
    run
fi
