# Relatório de Verificação Minusciosa do Código — StudyTrack Pro

**Data:** 2026-07-21
**Escopo:** Revisão completa do codebase full-stack (Laravel 12 + Vue 3)

---

## Resumo Executivo

| Severidade | Achados |
|------------|---------|
| **CRÍTICO** | 3 |
| **ALTO** | 7 |
| **MÉDIO** | 12 |
| **BAIXO** | 8 |
| **Total** | **30** |

---

## CRÍTICO — Requer Ação Imediata

### C1. `Reminder.php` — `user_id` em `$fillable` (Vulnerabilidade de Mass Assignment)

**Arquivo:** `backend/app/Models/Reminder.php:9-14`

```php
protected $fillable = [
    'user_id',        // ← PROBLEMA: todos os outros modelos excluem user_id
    'technology_id',
    'text',
    'completed',
];
```

**Problema:** Todos os outros modelos (StudySession, Technology, Goal, etc.) excluem `user_id` de `$fillable` para forçar o uso de `forceCreate()` e prevenir mass assignment. O Reminder é a única exceção, criando uma superfície de ataque para IDOR — um atacante poderia enviar `user_id` diferente no payload de criação.

**Impacto:** Um usuário malicioso poderia criar lembretes para outros usuários.

**Correção:**
```php
protected $fillable = [
    'technology_id',
    'text',
    'completed',
];
// Usar forceCreate() no ReminderService/Controller
```

---

### C2. `DockerSandboxService.php` — Caminho do arquivo temporário exposto em erro

**Arquivo:** `backend/app/Modules/CodeExecution/Services/DockerSandboxService.php:61-63`

```php
Log::error('Docker sandbox error', [
    'language' => $language,
    'error' => $e->getMessage(),  // ← Pode expor caminhos internos
]);
```

**Problema:** Mensagens de erro de processos Docker podem conter caminhos de sistema operacional (ex: `/tmp/sandbox_php_abc123`). Embora o log seja server-side, em cenários de debug error messages podem vazar para o cliente.

**Impacto:** Informação de sistema (path disclosure).

**Correção:** Logar apenas `$e->getMessage()` em contexto de log estruturado, nunca retornar ao cliente. Já está parcialmente mitigado pela mensagem genérica na linha 69, mas o log pode expor em logs de produção.

---

### C3. `deploy.yml` — Trivy scanner não-bloqueante para vulnerabilidades CRITICAL

**Arquivo:** `.github/workflows/deploy.yml:45-52, 66-73`

```yaml
continue-on-error: true  # ← CRÍTICO: permite deploy com vulnerabilidades CRITICAL
```

**Problema:** Ambos os scans Trivy (backend e frontend) usam `continue-on-error: true`, permitindo que imagens com vulnerabilidades CRITICAL sejam publicadas no Docker Hub.

**Impacto:** Vulnerabilidades conhecidas em imagens de produção.

**Correção:** Para produção, usar `continue-on-error: false` ou criar um scan separado que bloqueie o deploy.

---

## ALTO — Requer Atenção Prioritária

### A1. Pre-commit hooks são completamente não-bloqueantes

**Arquivo:** `.husky/pre-commit:1-3`

```bash
#!/bin/sh
(cd backend && php vendor/bin/pint --test 2>/dev/null) || true
(cd frontend && npm run lint --if-present && npm run type-check --if-present) || true
```

**Problema:** O `|| true` faz com que código com erros de linting, formatação ou tipos possa ser commitado. Os hooks servem apenas como warnings.

**Impacto:** Qualidade do código degradada gradualmente; bugs de tipo passam despercebidos.

**Correção:** Remover `|| true` ou usar `--staged` para apenas verificar arquivos staged:
```bash
#!/bin/sh
(cd backend && php vendor/bin/pint --test) || exit 1
(cd frontend && npx eslint --max-warnings 0 . && npx vue-tsc --noEmit) || exit 1
```

---

### A2. `SocialAuthService.php` — Senha aleatória para contas OAuth

**Arquivo:** `backend/app/Modules/Auth/Services/SocialAuthService.php:33`

```php
'password' => Str::random(60),
```

**Problema:** Contas criadas via OAuth recebem uma senha aleatória nunca usada. Essas contas nunca poderão fazer login por senha, mas a senha existe no banco. Se o hashing falhar ou o banco for comprometido, a senha é inútil mas representa superfície de ataque desnecessária.

**Impacto:** Baixo risco direto, mas anti-pattern de segurança.

**Correção:** Usar hash inválido ou null:
```php
'password' => null, // ou Hash::make(Str::random(60))
```
Verificar se o modelo permite `password` nullable.

---

### A3. `MetricsAggregator.php` — Streak calculation em PHP com loop

**Arquivo:** `backend/app/Modules/Analytics/Aggregators/MetricsAggregator.php:149-201`

**Problema:** O cálculo de streaks usa um loop PHP sobre resultados de query. Para usuários com anos de dados (730 dias de histórico), isso pode ser lento. Além disso, o cálculo de streak atual (linhas 173-180) usa `strtotime()` que pode ter problemas com timezone.

**Impacto:** Performance degradada para usuários com muitos dados; possíveis bugs de timezone.

**Correção:** Considerar cálculo em SQL puro ou otimizar o loop PHP.

---

### A4. `SecurityHeaders.php` — CSP contém `unsafe-inline` para styles

**Arquivo:** `backend/app/Http/Middleware/SecurityHeaders.php:20`

```php
"style-src 'self' 'unsafe-inline';"
```

**Problema:** `unsafe-inline` para estilos permite injeção CSS. Embora CSS injection seja menos grave que XSS, pode ser explorada em cenários avançados.

**Impacto:** Potencial CSS injection.

**Correção:** Usar nonce ou hash para estilos inline quando possível.

---

### A5. `OAuthController.php` — Token de sessão via query string

**Arquivo:** `backend/app/Http/Controllers/V1/OAuthController.php:116`

```php
return redirect($frontendUrl.'/auth/callback?status=ok&token='.urlencode($token));
```

**Problema:** O token de sessão é passado via query string no redirect. Query strings ficam no histórico do navegador, logs do servidor, e headers HTTP (Referer).

**Impacto:** Token pode vazar via histórico, logs, ou Referer header.

**Correção:** Usar fragmento (#) em vez de query string, ou armazenar em cookie temporário.

---

### A6. `auth.store.ts` — Token Bearer em localStorage

**Arquivo:** `frontend/src/stores/auth.store.ts:46-48`

```typescript
function storeToken(token: string) {
  localStorage.setItem(TOKEN_KEY, token)
}
```

**Problema:** Tokens de autenticação em localStorage são acessíveis via JavaScript, tornando-vulnerável a XSS. Embora o projeto use DOMPurify e CSP, qualquer falha de XSS expõe todos os tokens.

**Impacto:** Se XSS ocorrer, tokens são comprometidos.

**Correção:** Usar HttpOnly cookies (já parcialmente implementado via Sanctum SPA mode), mas o token Bearer em localStorage é um fallback que reduz a segurança.

---

### A7. `useWebSocket.ts` — Conexão via ws:// (não TLS)

**Arquivo:** `frontend/src/composables/useWebSocket.ts:60`

```typescript
const url = `ws://${host}:${port}/app/local-key?protocol=7&client=js&version=8.5.0`
```

**Problema:** A probe inicial usa `ws://` mesmo quando o scheme é HTTPS. Em produção com TLS, isso causa mixed content.

**Impacto:** Falha de conexão em produção HTTPS; potencial MITM.

**Correção:** Usar o scheme correto para a probe:
```typescript
const scheme = import.meta.env.VITE_REVERB_SCHEME === 'https' ? 'wss' : 'ws'
const url = `${scheme}://${host}:${port}/app/local-key?...`
```

---

## MÉDIO — Melhorias Recomendadas

### M1. Dois padrões de resposta API inconsistentes

**Frontend:** Alguns módulos usam `unwrap()` (goals, canvas, notifications), outros retornam resposta bruta do Axios.

**Impacto:** Inconsistência de código; mais difícil de manter.

**Recomendação:** Padronizar em `unwrap()` para todos os módulos.

---

### M2. `MetricsAggregator.php` — SQL com placeholders não parameterizados corretamente

**Arquivo:** `backend/app/Modules/Analytics/Aggregators/MetricsAggregator.php:119-143`

```php
DB::statement('
    INSERT INTO analytics.daily_minutes (...)
    SELECT ...
    FROM public.study_sessions ss
    WHERE ss.user_id = ?::uuid AND ss.ended_at IS NOT NULL
    GROUP BY 1, 2
    ...
', [$userTimezone, $userId]);
```

**Problema:** `$userTimezone` é passado como parâmetro binding, mas é interpolado diretamente no SQL como `AT TIME ZONE ?`. Isso é correto para prevenir SQL injection, mas o `GROUP BY 1, 2` usa números de coluna, que é frágil se a query for alterada.

**Impacto:** Manutenção difícil; potencial bug silencioso.

---

### M3. `useFocusTrap.ts` — Fallback para `tabindex="-1"` pode perder elementos

**Arquivo:** `frontend/src/composables/useFocusTrap.ts:4`

```typescript
const FOCUSABLE_SELECTOR =
  'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
```

**Problema:** O seletor exclui elementos com `tabindex="-1"`, mas esses elementos podem ser focáveis programaticamente. O focus trap pode não capturar todos os elementos focáveis.

**Impacto:** Usuários de tecnologia assistiva podem perder foco em modais.

---

### M4. `analytics.store.ts` — `sessionCountAtPendingStart` pode ficar desatualizado

**Arquivo:** `frontend/src/stores/analytics.store.ts:71, 234-242`

**Problema:** Se o dashboard não for fetched enquanto há pending sessions, `sessionCountAtPendingStart` pode não refletir o estado real do servidor.

**Impacto:** Dados de optimistic update podem ficar inconsistentes.

---

### M5. `ui.store.ts` — `SAFE_CSS_VALUE_RE` pode bloquear valores válidos

**Arquivo:** `frontend/src/stores/ui.store.ts:83`

```typescript
const SAFE_CSS_VALUE_RE = /^[a-zA-Z0-9\s\-#,.%()/]+$/u
```

**Problema:** A regex não permite caracteres como `:` (em `rgb(0, 0, 0)`) ou `var()` (em custom properties). Valores CSS válidos podem ser bloqueados.

**Impacto:** Custom themes podem falhar silenciosamente.

**Correção:** Usar allowlist mais abrangente ou validação por property type.

---

### M6. `Handler.php` — `P0001` SQLSTATE pode não cobrir todas as violações de constraint

**Arquivo:** `backend/app/Exceptions/Handler.php:111-116`

**Problema:** A detecção de sessão concorrente usa apenas `P0001` (raise_exception). Outras violações de constraint (unique, check) usam SQLstates diferentes.

**Impacto:** Erros de constraint podem ser tratados como erros internos (500) em vez de 409/422.

---

### M7. `SlidingWindowRateLimit.php` — `fail_open` pode expor endpoints sem rate limit

**Arquivo:** `backend/app/Http/Middleware/SlidingWindowRateLimit.php:47`

**Problema:** Quando `fail_open=true` e Redis está indisponível, requests passam sem rate limiting.

**Impacto:** Abuso de endpoints quando Redis cai.

**Correção:** Documentar claramente o comportamento e considerar fallback para rate limiting em memória.

---

### M8. `DockerSandboxService.php` — Bind mount com path do host

**Arquivo:** `backend/app/Modules/CodeExecution/Services/DockerSandboxService.php:108`

```php
'--tmpfs /tmp:size=10m -v %s:/tmp/code.php:ro %s php /tmp/code.php',
```

**Problema:** O bind mount usa o path completo do host. Se o container tizer acesso a outros volumes, o path pode ser explorado.

**Impacto:** Mitigado pelo `--read-only` e `--network none`, mas o path do host é visível dentro do container.

---

### M9. `guards.ts` — `fetchMePromise` pode causar redirect loop

**Arquivo:** `frontend/src/router/guards.ts:5, 16-18`

**Problema:** Se `fetchMe()` falhar repetidamente (ex: rede instável), `fetchMePromise` é resetada no `.finally()`, mas o guard pode ser chamado novamente antes da promise resolver.

**Impacto:** Possível redirect loop em condições de rede instável.

---

### M10. `DockerSandboxService.php` — Linguagem 'bash' não é suportada

**Arquivo:** `backend/app/Modules/CodeExecution/Services/DockerSandboxService.php:51-55`

```php
$result = match ($language) {
    'php', 'laravel' => $this->executePhp($code, $language),
    'sql' => $this->executeSql($code),
    default => ['success' => false, ...],
};
```

**Problema:** O `match` não tem case para `bash`, mas o `docker/code-sandbox/` tem um Dockerfile para bash. Se o frontend permitir bash, receberá erro genérico.

**Impacto:** UX confusa; feature incompleta.

---

### M11. `TokenService.php` — Blacklist de 7 dias para tokens sem expiração

**Arquivo:** `backend/app/Modules/Auth/Services/TokenService.php:92-94`

```php
return 60 * 60 * 24 * 7; // 7 dias
```

**Problema:** Tokens sem expiração são blacklistados por apenas 7 dias. Após 7 dias, um token revogado poderia ser reutilizado se o Redis perder os dados.

**Impacto:** Tokens revogados podem ser reutilizados após 7 dias em cenários de falha Redis.

---

### M12. `AnalyticsController.php` — Export de dados pode expor dados sensíveis

**Arquivo:** `backend/routes/api.php:88-90`

**Problema:** O endpoint de export não tem rate limiting adicional além do `throttle:60,1`. Um atacante pode fazer export repetido para exfiltrar dados.

**Impacto:** Potencial exfiltração de dados via export.

---

## BAIXO — Nitpicks e Melhorias Estéticas

### B1. `SecurityHeaders.php` — HSTS sem `preload`

**Arquivo:** `backend/app/Http/Middleware/SecurityHeaders.php:23`

```php
'Strict-Transport-Security: max-age=31536000; includeSubDomains'
```

**Recomendação:** Adicionar `; preload` para HSTS preload list.

---

### B2. `docker-compose.yml` — Falta healthcheck em alguns serviços

**Problema:** Nem todos os serviços têm `healthcheck` definido, dificultando orquestração.

---

### B3. Documentação duplicada

**Arquivo:** `vulnerability_scan_report.md` existe tanto na raiz quanto em `docs/security/`.

---

### B4. `quality-pipeline-report.md` — 4/7 checks falhando

**Problema:** O último run (2026-07-19) mostrou 4 falhas: evolution, security, integrity, tests.

---

### B5. `Bugfix report` — 22 bugs não corrigidos

**Arquivo:** `BUGFIX_REPORT.md` documenta 36/58 bugs corrigidos.

---

### B6. `commitlint.config.js` — Não valida corpo do commit

O commitlint valida header mas não valida corpo ou footer.

---

### B7. `README.md` — Diagrama de arquitetura pode estar desatualizado

---

### B8. `AGENTS.md` — Referências a `docs/prompt-agente-*.md` podem estar quebradas

---

## Boas Práticas Identificadas

1. **Segurança multicamada:** WAF (Lua) + rate limiting (nginx + Laravel) + token blacklist (Redis + Lua)
2. **Isolamento de usuários:** Todo service verifica `user_id` antes de retornar dados
3. **DTOs readonly:** Padronização de data transfer objects
4. **Repository pattern:** 9 interfaces + Eloquent implementations
5. **WebSocket com fallback:** Polling automático quando Reverb está indisponível
6. **Circuit breaker:** Dashboard para polling após 3 erros consecutivos
7. **Optimistic updates:** Analytics store com pending session reconciliation
8. **CSS injection prevention:** `SAFE_CSS_VALUE_RE` no ui store
9. **Docker hardening:** `cap_drop: ALL`, read-only, resource limits
10. **Test suite:** 80+ testes cobrindo segurança, features, integração

---

## Prioridades de Correção

### Imediato (esta semana)
1. **C1** — Corrigir `$fillable` no Reminder.php
2. **C3** — Tornar Trivy scanner bloqueante para CRITICAL
3. **A1** — Tornar pre-commit hooks bloqueantes

### Curto prazo (2 semanas)
4. **A5** — Mover token de query string para fragmento
5. **A7** — Corrigir scheme na probe WebSocket
6. **A4** — Revisar CSP para unsafe-inline

### Médio prazo (1 mês)
7. **M1** — Padronizar padrão de resposta API
8. **M3** — Melhorar seletor de focus trap
9. **M5** — Revisar regex de CSS validation

### Contínuo
10. Completar 22 bugs restantes do BUGFIX_REPORT
11. Executar 7 ações manuais do BUGFIX_REPORT
12. Atualizar scan de vulnerabilidades Docker (abril 2026 está desatualizado)
