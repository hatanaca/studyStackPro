# Migração para PrimeVue

## Concluído

- **Configuração**: PrimeVue 4 + tema Aura + primeicons em `main.ts`.
- **Toast**: `useToast()` passou a usar o Toast do PrimeVue; componente `<Toast />` em `App.vue`.
- **SessionList**: `Button`, `Dialog` (substituem BaseButton, BaseModal).
- **LogSessionForm**: `Button` (PrimeVue).
- **PageView**: `Breadcrumb` (substitui BaseBreadcrumb); tipo `BreadcrumbItem` exportado em `PageView.vue`.
- **TechnologySessionsView**: `Breadcrumb`, `Card`, `Button`.

## Mapeamento de componentes

| Componente antigo   | PrimeVue              | Observações |
|---------------------|------------------------|-------------|
| BaseButton          | Button                 | `label`, `severity` (primary/secondary/danger), `variant="outlined"`, `size="small"`, `:loading` |
| BaseModal           | Dialog                 | `v-model:visible`, `header`, `modal`, `@hide` |
| BaseToast           | Toast + useToast()     | Já migrado via composable |
| BaseBreadcrumb      | Breadcrumb             | `:model="items"` (array com `{ label, to? }`) |
| BaseCard            | Card                   | Slots: `#title`, `#content` (default) |
| BaseInput           | InputText              | `v-model`, `placeholder` |
| BaseBadge           | Tag                    | `:value`, `severity` |
| BaseTabs            | Tabs                   | TabList + TabPanels + Tab + TabPanel |
| BaseDateRangePicker | DatePicker             | `selectionMode="range"`, `v-model` |
| BasePagination      | Paginator              | `:rows`, `:totalRecords`, `@page` |
| BaseProgress        | ProgressBar            | `:value` |
| SkeletonLoader      | Skeleton               | `width`, `height` |
| ErrorCard           | Message                | `severity="error"`, slot ou `content` |
| EmptyState          | Message                | `severity="info"` ou Panel com ícone/texto |
| BaseDropdown        | Menu / Select          | Conforme uso (menu de opções ou select) |
| BaseAccordion       | Accordion              | AccordionPanel + AccordionHeader + AccordionContent |
| ConfirmDialog       | ConfirmDialog          | useConfirm() + `<ConfirmDialog />` |
| FormSection         | Fieldset               | `legend` |
| BaseAvatar          | Avatar                 | `image` ou slot |
| SectionHeader       | —                      | Manter ou usar Divider + título |
| ThemeToggle         | Button                 | `icon="pi pi-moon"` / `"pi pi-sun"` |

## Arquivos que ainda usam componentes antigos

Substituir imports e template conforme o mapeamento acima:

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
- `ConfirmDialog.vue` — BaseModal, BaseButton (substituir por PrimeVue ConfirmDialog)
- `AppSidebar.vue` / `AppTopBar.vue` / `AppMenuDropdown.vue` — ThemeToggle
- `TechDistributionWidget.vue` — SkeletonLoader
- `TimeSeriesWidget.vue` — EmptyState, SkeletonLoader
- `HeatmapWidget.vue` — SkeletonLoader
- `WeeklyComparisonWidget.vue` — SkeletonLoader
- `KpiCards.vue` — StatCard (manter ou virar Card)
- `GoalsWidget.vue` — BaseCard, BaseProgress, BaseButton
- `LogSessionWidget.vue` — BaseCard
- `TechnologyStudyWidget.vue` — BaseCard
- `OnboardingBanner.vue` — BaseButton
- `DataSection.vue` — BaseButton, FormSection
- `SearchInput.vue` — BaseInput
- `GoalCard.vue` — BaseCard, BaseProgress, BaseButton

## Uso do ConfirmDialog (PrimeVue)

```ts
// main.ts ou App.vue: registrar ConfirmDialog e ConfirmationService
import ConfirmationService from 'primevue/confirmationservice'
import ConfirmDialog from 'primevue/confirmdialog'
app.use(ConfirmationService)
app.component('ConfirmDialog', ConfirmDialog)

// No componente:
import { useConfirm } from 'primevue/useconfirm'
const confirm = useConfirm()
confirm.require({
  message: 'Tem certeza?',
  header: 'Confirmação',
  accept: () => { /* ok */ },
  reject: () => { /* cancel */ }
})
```

## Referência

- [PrimeVue 4](https://primevue.org/)
- [Tema Aura](https://primevue.org/theming/#aura) (já configurado)
