# Revisão de Design — Frontend StudyTrackPro

**Data**: 2026-06-25  
**Escopo**: Design system, componentes, views, consistência visual, acessibilidade

---

## Resumo

O design system está **bem estruturado** com tokens consistentes, suporte a dark mode, e boa acessibilidade. As melhorias abaixo são de polish e consistência, não de refatoração.

---

## 1. Paleta de Cores — Contraste e Consistência

### 1.1 Fundo muito frio no light mode
- **Arquivo**: `frontend/src/assets/styles/variables.css:22`
- **Problema**: `--color-bg: #d0dce9` é um azul-cinza frio que pode causar fadiga visual em uso prolongado
- **Recomendação**: Considerar um tom mais neutro: `#f1f5f9` (slate-100) ou `#f8fafc` (slate-50)
- **Prioridade**: MÉDIA

### 1.2 Texto secundário com token não utilizado
- **Arquivo**: `frontend/src/assets/styles/variables.css:20`
- **Problema**: `--color-text-secondary: #334155` existe mas raramente é usado — `--color-text-muted` domina
- **Recomendação**: Consolidar em um único token ou definir uso claro (secondary = hover states, muted = labels)
- **Prioridade**: BAIXA

---

## 2. Login — Oportunidades de Melhoria Visual

### 2.1 Título genérico "StudyTrack Pro"
- **Arquivo**: `frontend/src/views/auth/LoginView.vue:43`
- **Problema**: `<h1>StudyTrack Pro</h1>` não usa `font-display` e parece placeholder
- **Recomendação**: Adicionar gradiente no título + ícone decorativo, ou usar o logo em vez de texto

### 2.2 Botões OAuth sem identidade visual
- **Arquivo**: `frontend/src/views/auth/LoginView.vue:139-168`
- **Problema**: Botões Google/Discord usam `var(--color-bg-card)` — perdem identidade das marcas
- **Recomendação**: Google button com borda sutil `#4285F4`, Discord com `#5865F2` em hover

---

## 3. Sidebar — Micro-interações

### 3.1 Logo sem animação de colapso
- **Arquivo**: `frontend/src/components/layout/AppSidebar.vue:500-508`
- **Problema**: Logo desaparece abruptamente ao colapsar (opacity 0 + width 0)
- **Recomendação**: Adicionar `transition: opacity` com delay para suavizar

### 3.2 Link hover com translateX pode causar layout shift
- **Arquivo**: `frontend/src/components/layout/AppSidebar.vue:831`
- **Problema**: `transform: translateX(var(--spacing-2xs))` (2px) em hover pode causar micro-jitter
- **Recomendação**: Usar `padding-left` em vez de transform, ou aumentar o gap para compensar

---

## 4. Dashboard — Layout e Visual

### 4.1 Background do dashboard content com mixin opaco
- **Arquivo**: `frontend/src/views/Dashboard/DashboardView.vue:329`
- **Problema**: `background: color-mix(in srgb, var(--color-bg-soft) 40%, var(--color-bg))` — mix complexo
- **Recomendação**: Simplificar para `var(--color-bg-card)` ou criar token dedicado

### 4.2 Stakent style com muitas variáveis globais
- **Arquivo**: `frontend/src/assets/styles/variables.css:296-317`
- **Problema**: `[data-theme='dark'] .app-layout.stakent-style` redefine ~15 variáveis — difícil de manter
- **Recomendação**: Extrair para um arquivo CSS separado ou usar CSS custom properties scoped

---

## 5. Componentes UI — Consistência

### 5.1 EmptyState border com mix complexo
- **Arquivo**: `frontend/src/assets/styles/variables.css:145`
- **Problema**: `--empty-state-border: 1px solid color-mix(...)` — 3 inputs para 1 border
- **Recomendação**: Simplificar para `1px dashed var(--color-primary)` ou `1px solid var(--color-border)`

### 5.2 Form inputs com muitos tokens
- **Arquivo**: `frontend/src/assets/styles/variables.css:168-187`
- **Problema**: 18 tokens para form inputs — sobrecarga cognitiva
- **Recomendação**: Manter apenas: bg, border, border-focus, border-error, radius, height

---

## 6. Acessibilidade — Gaps

### 6.1 Touch targets podem estar abaixo de 44px
- **Arquivo**: `frontend/src/components/layout/AppSidebar.vue:817`
- **Problema**: `min-height: 2.25rem` (36px) nos links da sidebar — abaixo do WCAG 2.5.5 (44px)
- **Recomendação**: Aumentar para `min-height: var(--touch-target-min)` (2.75rem = 44px)

### 6.2 Skip link ausente
- **Problema**: Não há "Skip to content" link para navegação por teclado
- **Recomendação**: Adicionar `<a href="#main" class="sr-only focus:not-sr-only">Pular para conteúdo</a>` no início do body

---

## 7. Performance Visual

### 7.1 Skeleton loaders com borda desnecessária
- **Arquivo**: `frontend/src/views/Dashboard/DashboardView.vue:497-504`
- **Problema**: `.kpi-card-skeleton` tem `border: 1px solid var(--color-border)` — desnecessário para placeholder
- **Recomendação**: Remover borda em skeletons — só background + radius

### 7.2 Muitas media queries duplicadas
- **Arquivo**: `frontend/src/views/Dashboard/DashboardView.vue`
- **Problema**: `@media (min-width: 640px)` aparece 2x, `@media (min-width: 1024px)` aparece 2x
- **Recomendação**: Consolidar em uma media query por breakpoint

---

## 8. Dark Mode — Gaps

### 8.1 Stakent style sem scrollbar customizada
- **Arquivo**: `frontend/src/assets/styles/variables.css:319-370`
- **Problema**: Scrollbar customizada só para `[data-theme='dark']` — não cobre `.stakent-style`
- **Recomendação**: Adicionar `.stakent-style ::-webkit-scrollbar` com cores roxas

### 8.2 Gradient mesh não visível em dark mode
- **Arquivo**: `frontend/src/assets/styles/variables.css:263-265`
- **Problema**: `--gradient-mesh` em dark mode usa tons muito escuros — quase invisível
- **Recomendação**: Aumentar opacidade ou usar tons mais claros

---

## Prioridades de Implementação

### Alta (fazer primeiro)
1. Aumentar touch targets na sidebar para 44px
2. Simplificar form input tokens (reduzir de 18 para ~8)
3. Adicionar skip link para acessibilidade

### Média (próximo sprint)
4. Revisar paleta light mode (fundo menos frio)
5. Melhorar visual dos botões OAuth no login
6. Consolidar media queries duplicadas no Dashboard

### Baixa (quando possível)
7. Simplificar empty state border
8. Adicionar scrollbar customizada para stakent
9. Melhorar visibilidade do gradient mesh em dark mode

---

## Notas Positivas

- ✅ Design tokens bem organizados e documentados
- ✅ Dark mode completo com overrides consistentes
- ✅ `prefers-reduced-motion` respeitado
- ✅ Focus-visible com `--shadow-focus` em todos os componentes interativos
- ✅ Sidebar com animações suaves de colapso
- ✅ Componentes UI reutilizáveis bem estruturados
- ✅ PageView com breadcrumb, header e actions slots
- ✅ Dashboard com lazy loading de widgets pesados
