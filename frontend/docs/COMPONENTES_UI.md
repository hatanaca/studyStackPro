# Componentes UI – Referência

Lista dos componentes base e de feature disponíveis no StudyTrack Pro.

---

## Base (components/ui)

### BaseCard
Container com título opcional e slot de ações.
- **Props**: `title?`
- **Slots**: default, `actions`

### BaseButton
Botão com variantes e tamanhos.
- **Props**: `type`, `variant` (primary | secondary | ghost | danger | outline), `size` (sm | md | lg), `disabled`
- **Slots**: default

### BaseInput
Campo de texto com label e erro.
- **Props**: `modelValue`, `label`, `placeholder`, `type`, `error`, `disabled`, etc.
- **Slots**: default (conteúdo extra após o input)

### BaseModal
Overlay com blur e conteúdo centralizado; animação de escala.
- **Props**: `show`, `title?`
- **Emits**: `close`
- **Slots**: default

### BaseToast
Sistema de notificações temporárias (usado via useToast).

### BaseBadge
Rótulo ou contador pequeno.

### BaseAvatar
Avatar por imagem ou iniciais.
- **Props**: `src?`, `alt?`, `name?`, `size` (sm | md | lg | xl), `backgroundColor?`

### BaseTooltip
Tooltip ao hover/foco.
- **Props**: `content`, `placement` (top | bottom | left | right), `delay`, `disabled`
- **Slots**: default (trigger)

### BaseTabs
Abas com conteúdo único (um painel por vez).
- **Props**: `tabs` (array de { id, label, disabled? }), `modelValue`, `align`, `variant` (line | pill | enclosed)
- **Slots**: default com slot props `activeId`, `activeTab`

### BaseProgress
Barra de progresso.
- **Props**: `value`, `max`, `size`, `variant`, `showLabel`, `label`, `indeterminate`

### BaseDropdown
Menu suspenso.
- **Props**: `align` (left | right | center), `disabled`, `closeOnClickOutside`
- **Slots**: `trigger`, default (conteúdo do painel)

### BaseAccordion
Itens expansíveis.
- **Props**: `items` (array de { id, title, description?, disabled? }), `multiple`, `defaultOpen`
- **Slots**: nome = item.id para cada painel

### BaseDataTable
Tabela com ordenação e slots por coluna.
- **Props**: `columns`, `data`, `rowKey`, `loading`, `sortBy`, `sortOrder`, `emptyMessage`, `striped`, `bordered`, `compact`
- **Emits**: `update:sortBy`, `update:sortOrder`, `row-click`
- **Slots**: nome da coluna = slotName na coluna para célula customizada

### BaseStepper
Indicador de etapas (wizard).
- **Props**: `steps`, `currentStepId`, `allowStepClick`, `orientation` (horizontal | vertical)
- **Emits**: `step-click`

### BasePagination
Controles de paginação.
- **Props**: `page`, `totalPages`, `totalItems?`, `pageSize?`, `showFirstLast`, `maxVisible`
- **Emits**: `update:page`

### BaseDateRangePicker
Seletor de intervalo de datas (dois inputs date).
- **Props**: `modelValue` ({ start, end }), `minDate`, `maxDate`, `placeholderStart`, `placeholderEnd`, `disabled`
- **Emits**: `update:modelValue`

### BaseBreadcrumb
Navegação em migalhas.
- **Props**: `items` (array de { label, to?, href? })

### FormSection
Seção de formulário com título e descrição.
- **Props**: `title`, `description?`, `grouped?`
- **Slots**: default, `description`

### EmptyState
Estado vazio com ícone, título, descrição e ação.
- **Props**: `title`, `description?`, `icon?`, `actionLabel?`, `hideAction?`
- **Emits**: `action`
- **Slots**: `description`, `action`

### StatCard
Card de estatística com label, valor, ícone e tendência.
- **Props**: `label`, `value`, `icon?`, `variant?`, `trend?`, `trendLabel?`

### SkeletonLoader
Placeholder de carregamento (largura/altura customizáveis).

### ErrorCard
Mensagem de erro com botão de retry.

### ThemeToggle
Alternância de tema claro/escuro; variante `sidebar` para fundo escuro.

---

## Charts (components/charts)

- **LineChart**, **BarChart**, **PieChart**, **DonutChart**: wrappers para ApexCharts (vue3-apexcharts).
- **HeatmapChart**: heatmap de atividade por dia.

---

## Layout

- **AppLayout**: layout principal com sidebar e área de conteúdo.
- **AppSidebar**: navegação lateral (desktop: auto-hide; mobile: drawer).
- **AuthLayout**: layout para login/registro.

---

## Features

Componentes por domínio em `features/*/components/`.
Ex.: dashboard (KpiCards, TodaySummaryCard, GoalsWidget, …), sessions (LogSessionForm, SessionCard, …), technologies (TechnologyCard, TechnologyForm, …), goals (GoalCard, GoalForm, GoalList), notifications (NotificationCenter).
