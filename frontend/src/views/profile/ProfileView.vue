<script setup lang="ts">
import { ref, computed, onMounted, watch, defineAsyncComponent, h } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import PageView from '@/components/layout/PageView.vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Tabs from 'primevue/tabs'
import TabList from 'primevue/tablist'
import Tab from 'primevue/tab'
import TabPanels from 'primevue/tabpanels'
import TabPanel from 'primevue/tabpanel'
import Avatar from 'primevue/avatar'
import Skeleton from 'primevue/skeleton'
import { useConfirm } from 'primevue/useconfirm'
import { useAuthStore } from '@/stores/auth.store'
import { authApi, type TokenInfo } from '@/api/modules/auth.api'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()
const toast = useToast()
const confirm = useConfirm()

const GoalList = defineAsyncComponent({
  loader: () => import('@/features/goals/components/GoalList.vue'),
  loadingComponent: {
    name: 'ProfileGoalListLoading',
    setup() {
      return () =>
        h(
          'div',
          {
            class: 'profile-goals__async-placeholder',
            role: 'status',
            'aria-live': 'polite',
            'aria-label': 'Carregando metas',
          },
          [
            h(Skeleton, {
              width: '38%',
              height: '1rem',
              class: 'profile-goals__async-placeholder__line',
            }),
            h(Skeleton, { width: '100%', height: '10rem' }),
            h(Skeleton, {
              width: '100%',
              height: '6rem',
              class: 'profile-goals__async-placeholder__line',
            }),
          ]
        )
    },
  },
  delay: 100,
})

const profileTabs = [
  { id: 'profile', label: 'Perfil' },
  { id: 'password', label: 'Senha' },
  { id: 'goals', label: 'Metas' },
  { id: 'sessions', label: 'Sessões' },
]
const activeTab = ref('profile')

watch(
  () => route.query.tab,
  (tab) => {
    if (tab === 'goals') activeTab.value = 'goals'
  },
  { immediate: true }
)

watch(activeTab, (tab) => {
  if (tab === 'goals') {
    if (route.query.tab !== 'goals') {
      router.replace({ name: 'profile', query: { ...route.query, tab: 'goals' } })
    }
  } else if (route.query.tab === 'goals') {
    const nextQuery = { ...route.query } as Record<string, string | string[] | undefined>
    delete nextQuery.tab
    router.replace({ name: 'profile', query: nextQuery })
  }
})
const avatarLabel = computed(() => {
  const name = authStore.user?.name ?? ''
  const parts = name.trim().split(/\s+/)
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  return name.slice(0, 2).toUpperCase() || '?'
})

const profileLoading = ref(false)
const profileForm = ref({ name: '', timezone: 'UTC' })
const profileErrors = ref<{ name?: string; timezone?: string }>({})

const passwordLoading = ref(false)
const passwordForm = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})
const passwordErrors = ref<Record<string, string>>({})

const tokens = ref<TokenInfo[]>([])
const tokensLoading = ref(false)
const revokeLoading = ref(false)

onMounted(async () => {
  if (authStore.user) {
    profileForm.value = {
      name: authStore.user.name,
      timezone: authStore.user.timezone ?? 'UTC',
    }
  }
  await loadTokens()
})

async function loadTokens() {
  tokensLoading.value = true
  try {
    const { data } = await authApi.getTokens()
    if (data.success && data.data) {
      tokens.value = data.data
    }
  } catch {
    toast.error('Erro ao carregar sessões')
  } finally {
    tokensLoading.value = false
  }
}

async function saveProfile() {
  profileErrors.value = {}
  if (!profileForm.value.name.trim()) {
    profileErrors.value.name = 'Nome é obrigatório'
    return
  }
  profileLoading.value = true
  try {
    const { data } = await authApi.updateProfile({
      name: profileForm.value.name,
      timezone: profileForm.value.timezone,
    })
    if (data.success && data.data) {
      authStore.updateUser(data.data)
      toast.success('Perfil atualizado com sucesso')
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { error?: { details?: Record<string, string[]> } } } }
    const details = err.response?.data?.error?.details
    if (details) {
      profileErrors.value = Object.fromEntries(
        Object.entries(details).map(([k, v]) => [k, Array.isArray(v) ? v[0] : String(v)])
      )
    } else {
      toast.error('Erro ao atualizar perfil')
    }
  } finally {
    profileLoading.value = false
  }
}

function validatePassword(): boolean {
  const e: Record<string, string> = {}
  if (!passwordForm.value.current_password) e.current_password = 'Senha atual é obrigatória'
  if (!passwordForm.value.password) e.password = 'Nova senha é obrigatória'
  else if (passwordForm.value.password.length < 8) e.password = 'Mínimo 8 caracteres'
  else if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
    e.password_confirmation = 'Confirmação não confere'
  }
  passwordErrors.value = e
  return Object.keys(e).length === 0
}

async function changePassword() {
  if (!validatePassword()) return
  passwordLoading.value = true
  try {
    const { data } = await authApi.changePassword(passwordForm.value)
    if (data.success) {
      toast.success('Senha alterada. Você será desconectado.')
      authStore.logout()
      router.push('/login')
    }
  } catch (e: unknown) {
    const err = e as { response?: { data?: { error?: { message?: string } } } }
    const msg = err.response?.data?.error?.message ?? 'Erro ao alterar senha'
    if (msg.toLowerCase().includes('incorreta')) {
      passwordErrors.value = { current_password: 'Senha atual incorreta' }
    } else {
      toast.error(msg)
    }
  } finally {
    passwordLoading.value = false
  }
}

function revokeAllTokens() {
  confirm.require({
    header: 'Revogar todas as sessões',
    message: 'Você será desconectado de todos os dispositivos e precisará fazer login novamente.',
    acceptLabel: 'Revogar',
    rejectLabel: 'Cancelar',
    acceptClass: 'p-button-danger',
    async accept() {
      revokeLoading.value = true
      try {
        const { data } = await authApi.revokeAllTokens()
        if (data.success && data.data) {
          toast.success(`${data.data.revoked_count} sessão(ões) revogada(s)`)
          authStore.logout()
          router.push('/login')
        }
      } catch {
        toast.error('Erro ao revogar sessões')
      } finally {
        revokeLoading.value = false
      }
    },
  })
}

function formatDate(iso: string | null): string {
  if (!iso) return '—'
  try {
    return new Date(iso).toLocaleString('pt-BR')
  } catch {
    return iso
  }
}
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Perfil' }]"
    title="Perfil"
    subtitle="Gerencie seu perfil, senha e dispositivos conectados."
    narrow
  >
    <div class="profile-view">
      <div class="profile-view__avatar-wrap">
        <Avatar :label="avatarLabel" size="xlarge" class="profile-view__avatar" />
      </div>
      <Tabs v-model:value="activeTab" class="profile-view__tabs">
        <TabList>
          <Tab v-for="tab in profileTabs" :key="tab.id" :value="tab.id">
            {{ tab.label }}
          </Tab>
        </TabList>
        <TabPanels>
          <TabPanel value="profile">
            <Card class="profile-view__card">
              <template #content>
                <h2 class="section-title">Dados do perfil</h2>
                <form class="profile-form" @submit.prevent="saveProfile">
                  <div class="p-field">
                    <label for="profile-name">Nome</label>
                    <InputText
                      id="profile-name"
                      v-model="profileForm.name"
                      placeholder="Seu nome"
                      class="w-full"
                      :class="{ 'p-invalid': profileErrors.name }"
                    />
                    <small v-if="profileErrors.name" class="p-error">{{
                      profileErrors.name
                    }}</small>
                  </div>
                  <div class="p-field">
                    <label for="profile-timezone">Fuso horário</label>
                    <InputText
                      id="profile-timezone"
                      v-model="profileForm.timezone"
                      placeholder="UTC"
                      class="w-full"
                      :class="{ 'p-invalid': profileErrors.timezone }"
                    />
                    <small v-if="profileErrors.timezone" class="p-error">{{
                      profileErrors.timezone
                    }}</small>
                  </div>
                  <Button
                    type="submit"
                    :label="profileLoading ? 'Salvando...' : 'Salvar perfil'"
                    :loading="profileLoading"
                  />
                </form>
              </template>
            </Card>
          </TabPanel>
          <TabPanel value="goals">
            <Card class="profile-view__card">
              <template #content>
                <h2 class="section-title">Metas de estudo</h2>
                <p class="section-desc">
                  Defina e acompanhe metas por semana ou sequência de dias. O progresso semanal
                  também aparece no dashboard.
                </p>
                <GoalList />
              </template>
            </Card>
          </TabPanel>
          <TabPanel value="password">
            <Card class="profile-view__card">
              <template #content>
                <h2 class="section-title">Alterar senha</h2>
                <p class="section-desc">
                  Após alterar a senha, você será desconectado de todos os dispositivos.
                </p>
                <form class="profile-form" @submit.prevent="changePassword">
                  <input
                    type="text"
                    name="username"
                    autocomplete="username"
                    style="
                      position: absolute;
                      width: 1px;
                      height: 1px;
                      padding: 0;
                      margin: -1px;
                      overflow: hidden;
                      clip: rect(0, 0, 0, 0);
                      border: 0;
                    "
                    tabindex="-1"
                    aria-hidden="true"
                  />
                  <div class="p-field">
                    <label>Senha atual</label>
                    <InputText
                      v-model="passwordForm.current_password"
                      type="password"
                      placeholder="••••••••"
                      autocomplete="current-password"
                      class="w-full"
                      :class="{ 'p-invalid': passwordErrors.current_password }"
                    />
                    <small v-if="passwordErrors.current_password" class="p-error">{{
                      passwordErrors.current_password
                    }}</small>
                  </div>
                  <div class="p-field">
                    <label>Nova senha</label>
                    <InputText
                      v-model="passwordForm.password"
                      type="password"
                      placeholder="••••••••"
                      autocomplete="new-password"
                      class="w-full"
                      :class="{ 'p-invalid': passwordErrors.password }"
                    />
                    <small v-if="passwordErrors.password" class="p-error">{{
                      passwordErrors.password
                    }}</small>
                  </div>
                  <div class="p-field">
                    <label>Confirmar nova senha</label>
                    <InputText
                      v-model="passwordForm.password_confirmation"
                      type="password"
                      placeholder="••••••••"
                      autocomplete="new-password"
                      class="w-full"
                      :class="{ 'p-invalid': passwordErrors.password_confirmation }"
                    />
                    <small v-if="passwordErrors.password_confirmation" class="p-error">{{
                      passwordErrors.password_confirmation
                    }}</small>
                  </div>
                  <Button
                    type="submit"
                    :label="passwordLoading ? 'Alterando...' : 'Alterar senha'"
                    :loading="passwordLoading"
                  />
                </form>
              </template>
            </Card>
          </TabPanel>
          <TabPanel value="sessions">
            <Card class="profile-view__card">
              <template #content>
                <h2 class="section-title">Sessões ativas</h2>
                <p class="section-desc">Gerencie os dispositivos onde você está logado.</p>
                <div
                  v-if="tokensLoading"
                  class="profile-view__tokens-skeleton"
                  role="status"
                  aria-live="polite"
                  aria-label="Carregando sessões ativas"
                >
                  <Skeleton height="4.5rem" class="profile-view__token-skel" />
                  <Skeleton height="4.5rem" class="profile-view__token-skel" />
                </div>
                <template v-else>
                  <ul v-if="tokens.length" class="tokens-list">
                    <li v-for="t in tokens" :key="t.id" class="token-item">
                      <span class="token-name">{{ t.name }}</span>
                      <span class="token-date">Criado: {{ formatDate(t.created_at) }}</span>
                      <span class="token-date">Último uso: {{ formatDate(t.last_used_at) }}</span>
                    </li>
                  </ul>
                  <p v-else class="no-tokens">Nenhuma sessão ativa.</p>
                  <Button
                    class="revoke-btn"
                    severity="danger"
                    :label="revokeLoading ? 'Revogando...' : 'Sair de todos os dispositivos'"
                    :loading="revokeLoading"
                    :disabled="tokens.length <= 1"
                    @click="revokeAllTokens"
                  />
                </template>
              </template>
            </Card>
          </TabPanel>
        </TabPanels>
      </Tabs>
    </div>
  </PageView>
</template>

<style scoped>
.profile-view {
  max-width: 100%;
}
.profile-view__avatar-wrap {
  margin-bottom: var(--spacing-xl);
  display: flex;
  justify-content: center;
}
.profile-view__avatar {
  flex-shrink: 0;
}
.profile-view__tabs {
  margin-bottom: var(--spacing-lg);
}
.profile-view__card {
  margin-top: 0;
}
.profile-view__card :deep(.p-card-content) {
  padding: var(--spacing-lg) var(--spacing-xl);
}
.profile-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.section-title {
  font-family: var(--font-display);
  font-size: var(--text-base);
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm);
  letter-spacing: var(--tracking-tight);
  line-height: var(--leading-tight);
}
.section-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
  line-height: var(--leading-normal);
}
.tokens-list {
  list-style: none;
  padding: 0;
  margin: 0 0 var(--spacing-lg);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.token-item {
  padding: var(--spacing-lg);
  background: color-mix(in srgb, var(--color-bg-soft) 70%, var(--color-bg-card));
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.token-item:hover {
  border-color: var(--color-primary);
  box-shadow: var(--shadow-sm);
}
.token-name {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--color-text);
}
.token-date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.no-tokens {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-lg);
}
.revoke-btn {
  margin-top: var(--spacing-sm);
}
.revoke-btn :deep(.p-button),
.profile-form :deep(.p-button) {
  min-height: var(--touch-target-min);
}
.profile-form :deep(.p-button:focus-visible),
.profile-form :deep(.p-inputtext:focus-visible),
.revoke-btn :deep(.p-button:focus-visible) {
  outline: none;
  box-shadow: var(--shadow-focus);
}
.profile-goals__async-placeholder {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
  padding: var(--spacing-sm) 0;
}
.profile-goals__async-placeholder__line {
  display: block;
}
.profile-view__tokens-skeleton {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) 0 var(--spacing-lg);
}
.profile-view__token-skel {
  border-radius: var(--radius-md);
}
.p-field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  margin-bottom: var(--spacing-lg);
}
.p-field label {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
  letter-spacing: var(--tracking-tight);
}
.w-full {
  width: 100%;
}
</style>
