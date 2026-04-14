# Guia didático: segurança, performance e correção — StudyTrack Pro

**Objetivo:** servir de material de estudo sobre como o repositório trata riscos comuns, onde olhar no código e o que depende do ambiente (`.env`, TLS, deploy).

**Público:** quem está aprendendo full-stack (Vue + Laravel) ou revisando o projeto.

---

## 1. Mapa mental: três camadas

| Camada | O que significa | Onde ver no repo |
|--------|-----------------|------------------|
| **Segurança** | Autenticação, autorização, limites de abuso, dados sensíveis | Sanctum, throttles, Form Requests, ownership nos services |
| **Performance** | Latência, custo de CPU/IO, evitar trabalho desnecessário | Redis/cache, jobs, `LogApiRequests` em `terminate`, yield no PDF |
| **Correção** | Comportamento certo nos casos limite (0 min, rede, 401) | Testes, guards Axios, `sessionValidated`, composables |

Documentos relacionados: [operations/SECURITY_AUDIT.md](../operations/SECURITY_AUDIT.md), [technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md](../technical/FLUXO_COMPLETO_STUDYTRACK_PRO.md).

---

## 2. Segurança (resumo aplicado ao projeto)

### 2.1 Autenticação e tokens (frontend)

- O **JWT/token Sanctum** fica em `localStorage` (`auth.store.ts`). Isso é comum em SPAs; em ambientes com requisito forte de XSS zero, avaliar httpOnly cookies + CSRF (trade-off com SPA).
- O **cliente Axios** (`api/client.ts`) injeta `Authorization: Bearer`, evita tempestade de 401 com `handlingUnauthorized` e bloqueia requisições com token “não validado” até o `fetchMe` completar (`SESSION_NOT_READY`), alinhado ao guard de rota.

**Conceito:** *session fixation / token morto* — se o utilizador tem `user` em cache mas o token expirou, o guard força validação na API antes de liberar rotas protegidas.

### 2.2 API Laravel

- Rotas sensíveis usam `auth:sanctum` e **rate limiting** por grupo (`routes/api.php`): login/register mais restritos, leituras 60/min, escritas com throttle e sliding window em mutações de sessões.
- **Validação** entra pelos Form Requests; evita mass assignment e dados inesperados nos controllers.
- **CORS** (`config/cors.php`): sem `*` por defeito — origens vêm de `CORS_ALLOWED_ORIGINS` (lista separada por vírgulas). Em produção, definir origens reais.

### 2.3 WebSocket (Reverb)

- Canal **privado** `dashboard.{userId}`; autenticação de broadcasting com o mesmo Bearer. O composable `useWebSocket.ts` só conecta com `sessionValidated` e desliga no logout.

### 2.4 Logging

- `LogApiRequests` regista método, path, `user_id`, status e duração — **não** regista corpo de pedidos (reduz vazamento acidental de PII em logs).

---

## 3. Performance (padrões usados)

| Área | Ideia | Exemplo no projeto |
|------|--------|---------------------|
| API | Não bloquear o cliente com logging pesado | Log em `terminate()` após resposta |
| Frontend | Não travar a UI em relatórios grandes | `setTimeout(0)` a cada N linhas no PDF (`usePdfGenerator.ts`) |
| Backend | Limitar rajadas | Throttle + `SlidingWindowRateLimit` em rotas de escrita |
| Dados | Cache e jobs | Documentado em referência de cache e jobs em `docs/reference/` |

**Nota:** “Performance” também é **UX percebida**: spinners, estados vazios e invalidação TanStack Query após eventos WebSocket evitam dados obsoletos na tela.

---

## 4. Correção (bugs e casos limite)

### 4.1 Exemplo corrigido: duração zero no PDF

Em relatórios PDF, `duration_min === 0` não deve ser tratado como “sem dados”. A função local `formatDuration` em `usePdfGenerator.ts` distingue `null` (ausente) de `0` (zero minutos).

### 4.2 Testes automatizados

- Frontend: Vitest em guards, stores, composables (`frontend/src/**/__tests__`).
- Backend: PHPUnit em contratos, segurança e integração Lua/Redis quando aplicável.

Consulte [testing/ESTRATEGIA_TESTES.md](../testing/ESTRATEGIA_TESTES.md).

---

## 5. Checklist ao alterar código

1. **Novo endpoint:** Form Request + policy/ownership no service + throttle adequado.
2. **Novo dado na UI:** tipo em `types/`, eventual schema Zod se existir no fluxo.
3. **Segredo ou URL:** só `.env`, nunca commitar valores reais.
4. **Lista grande no cliente:** considerar paginação já usada nas listagens de sessões.

---

## 6. Referências externas úteis

- [OWASP Top 10](https://owasp.org/Top10/) — linguagem comum para riscos.
- Documentação [Laravel Sanctum](https://laravel.com/docs/sanctum) e [CORS](https://laravel.com/docs/routing#cors).

Este guia não substitui auditoria profissional nem pentest; consolida o que o repositório já documenta e implementa para fins de aprendizagem.
