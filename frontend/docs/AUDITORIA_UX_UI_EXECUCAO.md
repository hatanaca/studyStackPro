# Execucao dos To-dos de Auditoria UX/UI

Este documento consolida a execucao dos 4 to-dos atribuídos da auditoria UX/UI, com foco em correcoes de impacto real, padrao unico de experiencia e quick wins de baixo risco.

## 1) `audit-p0-confiabilidade` (Concluido)

### Objetivo
Priorizar correcoes de confiabilidade em acoes e overlays para reduzir comportamento enganoso e risco de abandono de fluxo.

### Diagnostico confirmado
- CTA de export local em `src/views/settings/DataSection.vue` exibe progresso, mas nao gera arquivo nem feedback final persistente.
- Modal base em `src/components/ui/BaseModal.vue` ainda sem semantica minima de dialogo (`role="dialog"`, `aria-modal`) e sem ciclo de foco.
- Drawer mobile em `src/components/layout/AppSidebar.vue` abre/fecha visualmente, mas sem gestao robusta de foco e sem fechamento por `Escape`.

### Priorizacao por impacto
- **P0.1 (alto impacto, baixo esforco):** Ajustar copy/feedback do CTA de dados locais para evitar expectativa de exportacao de sessoes do servidor.
- **P0.2 (alto impacto, medio esforco):** Evoluir `BaseModal` para contrato a11y minimo (semantica, foco inicial, retorno de foco, `Escape`, lock de scroll).
- **P0.3 (alto impacto, medio esforco):** Aplicar foco guiado no drawer mobile (`AppSidebar`) reutilizando `useFocusTrap` e definindo elemento inicial focado.

### Resultado esperado
- Menos acoes "falsas", overlays previsiveis e navegacao por teclado confiavel em fluxos criticos.

## 2) `audit-p1-consistencia-fluxo` (Concluido)

### Objetivo
Mapear divergencias de estados de tela, feedback global e confirmacoes destrutivas e definir padrao unico.

### Divergencias mapeadas
- **Confirmacoes destrutivas**
  - `src/features/technologies/components/TechnologyList.vue`: `confirm(...)` nativo.
  - `src/views/profile/ProfileView.vue`: `window.confirm(...)` para revogacao global.
  - `src/features/goals/components/GoalList.vue`: `useConfirm` (PrimeVue ConfirmDialog).
  - `src/features/sessions/components/SessionList.vue`: `Dialog` custom para delete.
- **Estados de loading/empty/error**
  - `src/views/Dashboard/DashboardView.vue`: combina `Skeleton`, `Message` e empty state com copy mais detalhada.
  - `src/features/sessions/components/SessionList.vue`: loading/empty textual simples.
  - `src/views/profile/ProfileView.vue`: loading parcial por aba e erros via toast.
- **Feedback global**
  - `src/App.vue` usa `Toast` + `ConfirmDialog` do PrimeVue.
  - `src/composables/useToast.ts` delega para PrimeVue e manteve API legada.
  - `src/components/ui/BaseToast.vue` ficou como componente legado sem uso funcional real.

### Padrao unico proposto
- **Confirmacao destrutiva:** centralizar em `ConfirmDialog` (PrimeVue) com severidade, verbos e copy padrao por risco.
- **Estados de tela:** contrato unico `loading / empty / error / ready` com componentes base (`Skeleton`, `EmptyState`, `ErrorCard`) e microcopy consistente.
- **Feedback global:** manter apenas `useToast` + `Toast` PrimeVue; descontinuar `BaseToast` para evitar dupla fonte de verdade.

### Regras de UX para todo fluxo
- Erro sempre com proxima acao clara ("Tentar novamente", "Recarregar", "Voltar").
- Estado vazio sempre com CTA contextual.
- Acao destrutiva sempre com verbo explicito (`Excluir`, `Revogar`) e escopo na copy.

## 3) `audit-design-system-gaps` (Concluido)

### Objetivo
Consolidar lacunas de tokenizacao e breakpoints com quick wins de baixo risco.

### Lacunas identificadas
- `src/assets/styles/variables.css` define breakpoints em tokens (`--screen-*`), mas ainda existem media queries hardcoded em componentes.
- `src/components/layout/AppTopBar.vue` usa `0.65rem`, `4px`, `1024px` e `640px` hardcoded.
- `src/components/layout/AppSidebar.vue` usa `769px/768px`, `280px`, `85vw`, `rgba(0, 0, 0, 0.5)` e sombra hardcoded no drawer.
- `src/views/Dashboard/DashboardView.vue` ainda possui breakpoints hardcoded (`480px`, `640px`, `1024px`) em alguns blocos.

### Quick wins de baixo risco (ordem recomendada)
- **QW-1:** Substituir `@media (max-width: 640px|1024px|768px|480px)` por `var(--screen-*)` no shell (`AppLayout`, `AppTopBar`, `AppSidebar`).
- **QW-2:** Promover tokens para radius/texto pequenos (`--radius-xs`, `--text-2xs`) e remover `4px`/`0.65rem` hardcoded.
- **QW-3:** Criar tokens de overlay (`--overlay-backdrop`, `--overlay-shadow`) para drawer/modal.
- **QW-4:** Normalizar larguras mobile (`min(280px, 85vw)`, `min(90vw, 420px)`) via tokens de overlay/painel.

### Ganho esperado
- Menos divergencia visual entre paginas, manutencao mais segura e previsibilidade responsiva entre 375px e 1440px.

## 4) `audit-polimento-a11y-visual` (Concluido)

### Objetivo
Listar ajustes finos de foco, microinteracoes e microcopy por componente para elevar clareza e consistencia sem redesign.

### Lista de ajustes por componente
- `src/features/technologies/components/TechnologyCard.vue`
  - Garantir `:focus-visible` em acoes de card (editar/excluir) com `--shadow-focus`.
  - Padronizar hover/focus para mesma hierarquia de destaque.
- `src/features/sessions/components/SessionList.vue`
  - Migrar inputs custom de `:focus` para `:focus-visible` quando aplicavel.
  - Aplicar foco visivel em botoes de paginacao (`.pagination__btn`).
- `src/components/layout/AppTopBar.vue`
  - Adicionar estado de foco em links/icones (`brand`, `icon-btn`) com token de foco.
  - Ajustar microcopy do titulo dinamico para evitar termos ambiguos ("Top Metricas de Estudo").
- `src/views/reports/ReportsView.vue`, `src/views/settings/AppearanceSection.vue`, `src/views/help/HelpView.vue`
  - Reduzir ruído de CTA placeholder sem acao real.
  - Incluir status explicito ("Em desenvolvimento") com proxima acao concreta.
- `src/components/ui/BaseModal.vue` e `src/components/layout/AppSidebar.vue`
  - Garantir ordem de tab previsivel, foco inicial e retorno de foco para trigger.
  - Fechamento por `Escape` com anuncio coerente para leitores de tela.

### Checklist objetivo de polimento
- Todos os controles interativos com `:focus-visible`.
- Sem CTA sem efeito observavel.
- Sem microcopy vaga em estados vazios/placeholder.
- Microinteracoes consistentes em duracao e intensidade visual.

## Backlog recomendado (2 sprints)

- **Sprint 1 (confiabilidade + consistencia):**
  - P0.1, P0.2, P0.3
  - Padrao unico de confirmacao destrutiva
  - Descontinuacao de `BaseToast`
- **Sprint 2 (design system + polimento):**
  - QW-1 a QW-4
  - Foco/microinteracoes por componente
  - Revisao final de microcopy em telas placeholder

## Criterios de aceite desta execucao

- Itens P0/P1/P2 transformados em backlog acionavel com prioridade clara.
- Divergencias mapeadas com arquivos de origem.
- Padrao alvo definido para estados, feedback e confirmacoes.
- Quick wins de tokenizacao/breakpoints definidos com baixo risco de regressao.
