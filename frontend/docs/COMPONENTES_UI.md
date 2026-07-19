# UI Components — Reference

List of base and feature components available in StudyTrack Pro.

---

## Base (components/ui)

### BaseCard
Container with optional title and action slot.
- **Props**: `title?`
- **Slots**: default, `actions`

### BaseButton
Button with variants and sizes.
- **Props**: `type`, `variant` (primary | secondary | ghost | danger | outline), `size` (sm | md | lg), `disabled`
- **Slots**: default

### BaseInput
Text field with label and error.
- **Props**: `modelValue`, `label`, `placeholder`, `type`, `error`, `disabled`, etc.
- **Slots**: default (extra content after the input)

### BaseModal
Overlay with blur and centered content; scale animation.
- **Props**: `show`, `title?`
- **Emits**: `close`
- **Slots**: default

### BaseToast
Temporary notification system (used via useToast).

### BaseBadge
Small label or counter.

### BaseAvatar
Avatar by image or initials.
- **Props**: `src?`, `alt?`, `name?`, `size` (sm | md | lg | xl), `backgroundColor?`

### BaseTooltip
Tooltip on hover/focus.
- **Props**: `content`, `placement` (top | bottom | left | right), `delay`, `disabled`
- **Slots**: default (trigger)

### BaseTabs
Tabs with single content (one panel at a time).
- **Props**: `tabs` (array of { id, label, disabled? }), `modelValue`, `align`, `variant` (line | pill | enclosed)
- **Slots**: default with slot props `activeId`, `activeTab`

### BaseProgress
Progress bar.
- **Props**: `value`, `max`, `size`, `variant`, `showLabel`, `label`, `indeterminate`

### BaseDropdown
Dropdown menu.
- **Props**: `align` (left | right | center), `disabled`, `closeOnClickOutside`
- **Slots**: `trigger`, default (panel content)

### BaseAccordion
Expandable items.
- **Props**: `items` (array of { id, title, description?, disabled? }), `multiple`, `defaultOpen`
- **Slots**: name = item.id for each panel

### BaseDataTable
Table with sorting and column slots.
- **Props**: `columns`, `data`, `rowKey`, `loading`, `sortBy`, `sortOrder`, `emptyMessage`, `striped`, `bordered`, `compact`
- **Emits**: `update:sortBy`, `update:sortOrder`, `row-click`
- **Slots**: column name = slotName in column for custom cell

### BaseStepper
Step indicator (wizard).
- **Props**: `steps`, `currentStepId`, `allowStepClick`, `orientation` (horizontal | vertical)
- **Emits**: `step-click`

### BasePagination
Pagination controls.
- **Props**: `page`, `totalPages`, `totalItems?`, `pageSize?`, `showFirstLast`, `maxVisible`
- **Emits**: `update:page`

### BaseDateRangePicker
Date range selector (two date inputs).
- **Props**: `modelValue` ({ start, end }), `minDate`, `maxDate`, `placeholderStart`, `placeholderEnd`, `disabled`
- **Emits**: `update:modelValue`

### BaseBreadcrumb
Breadcrumb navigation.
- **Props**: `items` (array of { label, to?, href? })

### FormSection
Form section with title and description.
- **Props**: `title`, `description?`, `grouped?`
- **Slots**: default, `description`

### EmptyState
Empty state with icon, title, description, and action.
- **Props**: `title`, `description?`, `icon?`, `actionLabel?`, `hideAction?`
- **Emits**: `action`
- **Slots**: `description`, `action`

### StatCard
Statistics card with label, value, icon, and trend.
- **Props**: `label`, `value`, `icon?`, `variant?`, `trend?`, `trendLabel?`

### SkeletonLoader
Loading placeholder (customizable width/height).

### ErrorCard
Error message with retry button.

### ThemeToggle
Light/dark theme switch; `sidebar` variant for dark background.

---

## Charts (components/charts)

- **LineChart**, **BarChart**, **PieChart**, **DonutChart**: wrappers for ApexCharts (vue3-apexcharts).
- **HeatmapChart**: activity heatmap by day.

---

## Layout

- **AppLayout**: main layout with sidebar and content area.
- **AppSidebar**: side navigation (desktop: auto-hide; mobile: drawer).
- **AuthLayout**: layout for login/registration.

---

## Features

Domain components in `features/*/components/`.
E.g., dashboard (KpiCards, TodaySummaryCard, GoalsWidget, ...), sessions (LogSessionForm, SessionCard, ...), technologies (TechnologyCard, TechnologyForm, ...), goals (GoalCard, GoalForm, GoalList), notifications (NotificationCenter).
