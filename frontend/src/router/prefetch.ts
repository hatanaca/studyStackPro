/**
 * Pré-carrega chunks de vistas no hover da sidebar (antes do clique).
 * Caminhos devem coincidir com os `import()` em `router/routes/*.routes.ts`.
 */
export function prefetchDashboardView() {
  void import('@/views/Dashboard/DashboardView.vue')
}

export function prefetchSessionsView() {
  void import('@/views/sessions/SessionsView.vue')
}

export function prefetchSessionFocusView() {
  void import('@/views/sessions/SessionFocusView.vue')
}

export function prefetchGoalsView() {
  void import('@/views/profile/ProfileView.vue')
  void import('@/features/goals/components/GoalList.vue')
}

export function prefetchTechnologiesView() {
  void import('@/views/technologies/TechnologiesView.vue')
}

export function prefetchTechnologyDetailView() {
  void import('@/views/technologies/TechnologyDetailLayout.vue')
}

export function prefetchSettingsView() {
  void import('@/views/settings/SettingsLayout.vue')
}

export function prefetchProfileView() {
  void import('@/views/profile/ProfileView.vue')
}
