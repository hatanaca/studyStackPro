# Prompt: Implementar melhorias no frontend StudyTrackPro

Use este prompt quando quiser que o Composer (ou outro agente) **execute** melhorias no frontend do StudyTrackPro. As tarefas estão priorizadas. Pode solicitar "implementar todas as de alta prioridade" ou "implementar apenas o item 2".

**Contexto:** Vue 3 + TypeScript, design system em `frontend/src/assets/styles/variables.css`, componentes em `frontend/src/components/ui/` e `layout/`, views em `frontend/src/views/` e features em `frontend/src/features/`. Regras do agente frontend: `docs/prompt-agente-frontend-studytrackpro.md`. Design: `docs/prompt-agente-design-frontend-studytrackpro.md`.

---

## Instrução para o agente

Você deve implementar as melhorias de frontend listadas abaixo na ordem indicada (ou apenas as que o usuário solicitar). Para cada item:

1. **Não quebre** o que já funciona; rode os testes e o build após as mudanças.
2. Use **apenas tokens** de `variables.css` em CSS; não adicione valores hardcoded de cor, espaçamento, sombra ou tipografia em componentes.
3. Mantenha **acessibilidade** (contraste, foco visível, ARIA quando necessário).
4. Respeite **`prefers-reduced-motion`** em qualquer animação ou transição nova.

---

## Melhorias de alta prioridade

### 1. Substituir valores hardcoded nos componentes UI por tokens do design system

**Objetivo:** Nenhum componente em `src/components/ui/` deve usar valores fixos (px, rem, #hex, rgba) para cores, espaçamento, raio, sombra ou tipografia. Tudo deve vir de `variables.css`.

**Ação:**

- Auditar cada componente em `frontend/src/components/ui/` e listar propriedades com valores fixos.
- Para cada um: mapear para um token existente ou propor um novo token em `variables.css` (com comentário de uso) e então usar `var(--token)` no componente.
- Priorizar: **BaseButton**, **BaseStepper**, **BasePagination**, **Callout**, **ErrorCard**, **EmptyState**, **BaseToast**, **BaseModal**, **BaseInput**, **BaseProgress**, **BaseAvatar**, **StatCard**, **FilterBar**, **LoadingOverlay**.

**Entrega:** Componentes atualizados; se novos tokens forem criados, documentar em comentário no `:root` de `variables.css`.

---

### 2. Respeitar `prefers-reduced-motion` em animações e transições

**Objetivo:** Usuários que preferem menos movimento não devem ser impactados por animações desnecessárias.

**Ação:**

- Identificar em todo o frontend onde há `transition`, `animation`, ou bibliotecas de motion (@vueuse/motion, etc.).
- Envolver animações em `@media (prefers-reduced-motion: no-preference) { ... }` ou usar classe/condição que desativa motion quando `prefers-reduced-motion: reduce`.
- Garantir que estados (loading, sucesso, erro) continuem comunicados por texto/ícone/estado, não só por animação.

**Entrega:** Arquivos alterados listados; uma linha no README ou em docs sobre suporte a reduced-motion (opcional).

---

### 3. Alinhar payloads WebSocket com tipos TypeScript

**Objetivo:** Os eventos recebidos do Reverb (ex.: `session.started`, `session.ended`, `metrics.updated`) devem ser tipados corretamente no frontend; nenhum campo usado no código pode estar ausente ou com tipo errado no backend.

**Ação:**

- Ler `frontend/src/types/websocket.types.ts` e os eventos em `useWebSocket` (ou composable que processa Echo).
- Conferir no backend quais eventos são disparados (BroadcastSessionStarted, BroadcastSessionEnded, BroadcastMetricsUpdate, etc.) e a estrutura do payload (ex.: `technology.slug`, `technology.id`, etc.).
- Ajustar `websocket.types.ts` para refletir exatamente o payload real; ajustar o código que consome os eventos para usar os campos corretos (ex.: slug vs id).
- Corrigir qualquer inconsistência (ex.: backend envia `slug`, frontend espera outro campo).

**Entrega:** Tipos e handlers atualizados; se o backend precisar de mudança, documentar em um comentário ou issue.

---

### 4. Adicionar Prettier no frontend e integrar ao ESLint

**Objetivo:** Formatação consistente e sem conflito com ESLint.

**Ação:**

- Instalar Prettier e `eslint-config-prettier` (e opcionalmente `eslint-plugin-prettier`) no frontend.
- Criar `.prettierrc` (ou `.prettierrc.json`) com preferências do projeto (ex.: singleQuote, trailingComma, tabWidth).
- Garantir que o ESLint não regra estilo já coberta pelo Prettier (usar eslint-config-prettier).
- Adicionar script no `package.json`: `"format": "prettier --write \"src/**/*.{vue,ts,js,json,css}\""`.
- Opcional: configurar format on save no `.vscode/settings.json` do projeto.

**Entrega:** Configuração commitada; README ou CONTRIBUTING com menção a `npm run format`.

---

### 5. Token de foco acessível e uso em controles interativos

**Objetivo:** Todo controle interativo (botão, link, input, tab, etc.) deve ter indicador de foco visível e consistente.

**Ação:**

- Em `variables.css`, definir ou garantir existência de `--shadow-focus` (ou `--color-focus-ring`) adequado para contraste no tema claro e escuro.
- Aplicar esse token em todos os componentes em `components/ui/` que são focáveis (BaseButton, BaseInput, BaseDropdown, BaseTabs, BaseModal trigger, etc.): usar `outline` ou `box-shadow` no `:focus-visible`, nunca `outline: none` sem alternativa.
- Não usar `outline: none` sem substituir por um anel de foco visível.

**Entrega:** Token definido; componentes atualizados; verificação manual ou com axe DevTools.

---

## Melhorias de média prioridade

### 6. Tokens para tamanhos de ícones e elementos repetidos

**Objetivo:** Valores como 1.25rem, 2rem para ícones e controles devem vir de tokens (ex.: `--size-icon-sm`, `--size-icon-md`, `--size-touch-min` para 44px).

**Ação:**

- Adicionar em `variables.css` tokens de tamanho (ícone, área de toque mínima) e usá-los nos componentes que hoje usam esses valores fixos.

---

### 7. Revisar responsividade (375px e 1440px)

**Objetivo:** Dashboard, listagem de sessões e Export devem ser usáveis em mobile (375px) e desktop (1440px).

**Ação:**

- Revisar breakpoints em `variables.css` (--screen-sm, --screen-md, etc.) e uso em cada view principal.
- Ajustar grid, fontes e espaçamentos para não quebrar layout ou legibilidade em 375px e 1440px.

---

### 8. Roles e ARIA em EmptyState e ErrorCard

**Objetivo:** EmptyState e ErrorCard devem ter roles e ARIA adequados para leitores de tela (ex.: `role="status"`, `aria-live` quando dinâmico).

**Ação:**

- Adicionar atributos ARIA e roles conforme o propósito de cada componente (informação, alerta, etc.).

---

### 9. Padronizar tratamento de erro nas chamadas de API

**Objetivo:** Todas as chamadas que passam pelos módulos em `api/modules/` devem tratar erro de forma consistente (toast, mensagem amigável, retry quando fizer sentido).

**Ação:**

- Revisar cada módulo (auth, sessions, technologies, analytics, goals) e garantir que erros são mapeados para mensagem e/ou toast; evitar apenas `console.error` sem feedback ao usuário.

---

## Melhorias de baixa prioridade

### 10. JSDoc nos composables e módulos da API

Adicionar comentários JSDoc nos composables públicos em `composables/` e nas funções exportadas dos módulos em `api/modules/`, descrevendo parâmetros, retorno e uso típico.

### 11. VeeValidate + Zod nos formulários

Avaliar adoção de VeeValidate com Zod nos formulários principais (login, registro, sessão, tecnologia) para alinhar validação ao schema da API e melhorar mensagens de erro.

---

## Checklist antes de dar por concluído

- [ ] `npm run build` sem erros.
- [ ] `npm run lint` sem erros (e, se existir, `npm run test` dos testes unitários do frontend).
- [ ] Nenhum valor hardcoded de cor/espaçamento/sombra/tipografia nos componentes UI alterados; uso exclusivo de tokens.
- [ ] Foco visível em controles alterados; preferência por reduced-motion respeitada onde há animação.
- [ ] Tipos WebSocket e handlers alinhados ao backend.

---

## Como usar este prompt

- **Implementar tudo (alta):** "Implemente todas as melhorias de alta prioridade do documento docs/prompt-implementar-melhorias-frontend.md."
- **Implementar um item:** "Implemente apenas o item 3 (WebSocket) do docs/prompt-implementar-melhorias-frontend.md."
- **Implementar por tema:** "Implemente os itens de acessibilidade (2, 5 e 8) do docs/prompt-implementar-melhorias-frontend.md."

Inclua no contexto os arquivos relevantes (ex.: `variables.css`, `useWebSocket.ts`, `websocket.types.ts`, um componente UI) para o agente ter referência imediata.
