# PrimeVue Migration

## Completed

- **Configuration**: PrimeVue 4 + Aura theme + primeicons in `main.ts`.
- **Toast**: `useToast()` now uses PrimeVue's Toast; `<Toast />` component in `App.vue`.
- **SessionList**: `Button`, `Dialog` (replacing BaseButton, BaseModal).
- **LogSessionForm**: `Button` (PrimeVue).
- **PageView**: `Breadcrumb` (replacing BaseBreadcrumb); `BreadcrumbItem` type exported in `PageView.vue`.
- **TechnologySessionsView**: `Breadcrumb`, `Card`, `Button`.

## Component Mapping

| Old Component | PrimeVue | Notes |
|---------------|----------|-------|
| BaseButton | Button | `label`, `severity` (primary/secondary/danger), `variant="outlined"`, `size="small"`, `:loading` |
| BaseModal | Dialog | `v-model:visible`, `header`, `modal`, `@hide` |
| BaseToast | Toast + useToast() | Already migrated via composable |
| BaseBreadcrumb | Breadcrumb | `:model="items"` (array with `{ label, to? }`) |
| BaseCard | Card | Slots: `#title`, `#content` (default) |
| BaseInput | InputText | `v-model`, `placeholder` |
| BaseBadge | Tag | `:value`, `severity` |
| BaseTabs | Tabs | TabList + TabPanels + Tab + TabPanel |
| BaseDateRangePicker | DatePicker | `selectionMode="range"`, `v-model` |
| BasePagination | Paginator | `:rows`, `:totalRecords`, `@page` |
| BaseProgress | ProgressBar | `:value` |
| SkeletonLoader | Skeleton | `width`, `height` |
| ErrorCard | Message | `severity="error"`, slot or `content` |
| EmptyState | Message | `severity="info"` or Panel with icon/text |
| BaseDropdown | Menu / Select | Depending on usage (options menu or select) |
| BaseAccordion | Accordion | AccordionPanel + AccordionHeader + AccordionContent |
| ConfirmDialog | ConfirmDialog | useConfirm() + `<ConfirmDialog />` |
| FormSection | Fieldset | `legend` |
| BaseAvatar | Avatar | `image` or slot |
| SectionHeader | — | Keep or use Divider + title |
| ThemeToggle | Button | `icon="pi pi-moon"` / `"pi pi-sun"` |

## Files Still Using Old Components

Replace imports and template according to the mapping above:

- `DashboardView.vue` — SkeletonLoader, ErrorCard, EmptyState
- `SessionDetailView.vue` — BaseBadge, BaseButton, ErrorCard, KeyValueList, SkeletonLoader
- `ProfileView.vue` — BaseCard, BaseInput, BaseButton, BaseTabs, BaseAvatar
- `ReportsView.vue` — BaseCard, BaseDateRangePicker, BaseButton, EmptyState
- `ExportView.vue` — BaseCard, BaseButton, BaseDateRangePicker, FormSection
- `TechnologyDetailView.vue` — BaseButton, BaseCard, ErrorCard, SkeletonLoader
- `SettingsView.vue` — BaseTabs, BaseCard
- `AppearanceSection.vue` — FormSection, BaseButton
- `HelpView.vue` — BaseCard, BaseAccordion
- `GoalsView.vue` — GoalList (BaseButton, BaseModal, ConfirmDialog, EmptyState), GoalForm (BaseInput, BaseButton, FormSection), GoalCard (BaseCard, BaseProgress, BaseButton)
- `LoginForm.vue` / `RegisterForm.vue` — BaseInput, BaseButton
- `TechnologyList.vue` — BaseButton
- `DashboardHeader.vue` — BaseButton
- `RemindersWidget.vue` — BaseButton
- `NotificationCenter.vue` — BaseDropdown, BaseButton
- `TechnologyForm.vue` — BaseInput, BaseButton
- `TechnologyDetailMural.vue` — BaseButton
- `TechnologyDetailReminders.vue` — BaseButton
- `FilterBar.vue` — BaseButton, BaseInput, BaseDateRangePicker
- `ConfirmDialog.vue` — BaseModal, BaseButton (replace with PrimeVue ConfirmDialog)
- `AppSidebar.vue` / `AppTopBar.vue` / `AppMenuDropdown.vue` — ThemeToggle
- `TechDistributionWidget.vue` — SkeletonLoader
- `TimeSeriesWidget.vue` — EmptyState, SkeletonLoader
- `HeatmapWidget.vue` — SkeletonLoader
- `WeeklyComparisonWidget.vue` — SkeletonLoader
- `KpiCards.vue` — StatCard (keep or convert to Card)
- `GoalsWidget.vue` — BaseCard, BaseProgress, BaseButton
- `LogSessionWidget.vue` — BaseCard
- `TechnologyStudyWidget.vue` — BaseCard
- `OnboardingBanner.vue` — BaseButton
- `DataSection.vue` — BaseButton, FormSection
- `SearchInput.vue` — BaseInput
- `GoalCard.vue` — BaseCard, BaseProgress, BaseButton

## ConfirmDialog Usage (PrimeVue)

```ts
// main.ts or App.vue: register ConfirmDialog and ConfirmationService
import ConfirmationService from 'primevue/confirmationservice'
import ConfirmDialog from 'primevue/confirmdialog'
app.use(ConfirmationService)
app.component('ConfirmDialog', ConfirmDialog)

// In the component:
import { useConfirm } from 'primevue/useconfirm'
const confirm = useConfirm()
confirm.require({
  message: 'Are you sure?',
  header: 'Confirmation',
  accept: () => { /* ok */ },
  reject: () => { /* cancel */ }
})
```

## Reference

- [PrimeVue 4](https://primevue.org/)
- [Aura Theme](https://primevue.org/theming/#aura) (already configured)
