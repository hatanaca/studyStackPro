<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import BaseBreadcrumb from '@/components/ui/BaseBreadcrumb.vue'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseTabs from '@/components/ui/BaseTabs.vue'
import BaseAvatar from '@/components/ui/BaseAvatar.vue'
import { useAuthStore } from '@/stores/auth.store'
import { authApi, type TokenInfo } from '@/api/modules/auth.api'
import { useToast } from '@/composables/useToast'

const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()

const profileTabs = [
  { id: 'profile', label: 'Perfil', disabled: false },
  { id: 'password', label: 'Senha', disabled: false },
  { id: 'sessions', label: 'Sessões', disabled: false },
]
const activeTab = ref('profile')

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

async function revokeAllTokens() {
  const ok = window.confirm(
    'Revogar todas as sessões? Você será desconectado de todos os dispositivos e precisará fazer login novamente.'
  )
  if (!ok) return
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
  <div class="profile-view">
    <BaseBreadcrumb
      :items="[{ label: 'Dashboard', to: '/' }, { label: 'Configurações' }]"
      class="profile-view__breadcrumb"
    />
    <header class="profile-view__header">
      <BaseAvatar
        :name="authStore.user?.name"
        size="xl"
        class="profile-view__avatar"
      />
      <div class="profile-view__header-text">
        <h1 class="profile-view__title">
          Configurações
        </h1>
        <p class="profile-view__subtitle">
          Gerencie seu perfil, senha e dispositivos conectados.
        </p>
      </div>
    </header>
    <BaseTabs
      v-model="activeTab"
      :tabs="profileTabs"
      variant="pill"
      align="start"
      class="profile-view__tabs"
    >
      <template #default="{ activeId }">
        <BaseCard class="profile-view__card">
          <template v-if="activeId === 'profile'">
            <h2 class="section-title">
              Dados do perfil
            </h2>
            <form
              class="profile-form"
              @submit.prevent="saveProfile"
            >
              <BaseInput
                v-model="profileForm.name"
                label="Nome"
                placeholder="Seu nome"
                :error="profileErrors.name"
              />
              <BaseInput
                v-model="profileForm.timezone"
                label="Fuso horário"
                placeholder="UTC"
                :error="profileErrors.timezone"
              />
              <BaseButton
                type="submit"
                :disabled="profileLoading"
              >
                {{ profileLoading ? 'Salvando...' : 'Salvar perfil' }}
              </BaseButton>
            </form>
          </template>
          <template v-else-if="activeId === 'password'">
            <h2 class="section-title">
              Alterar senha
            </h2>
            <p class="section-desc">
              Após alterar a senha, você será desconectado de todos os dispositivos.
            </p>
            <form
              class="profile-form"
              @submit.prevent="changePassword"
            >
              <!-- Campo oculto para acessibilidade em formulários de senha -->
              <input
                type="text"
                name="username"
                autocomplete="username"
                style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0"
                tabindex="-1"
                aria-hidden="true"
              >
              <BaseInput
                v-model="passwordForm.current_password"
                type="password"
                label="Senha atual"
                placeholder="••••••••"
                :error="passwordErrors.current_password"
                autocomplete="current-password"
              />
              <BaseInput
                v-model="passwordForm.password"
                type="password"
                label="Nova senha"
                placeholder="••••••••"
                :error="passwordErrors.password"
                autocomplete="new-password"
              />
              <BaseInput
                v-model="passwordForm.password_confirmation"
                type="password"
                label="Confirmar nova senha"
                placeholder="••••••••"
                :error="passwordErrors.password_confirmation"
                autocomplete="new-password"
              />
              <BaseButton
                type="submit"
                :disabled="passwordLoading"
              >
                {{ passwordLoading ? 'Alterando...' : 'Alterar senha' }}
              </BaseButton>
            </form>
          </template>
          <template v-else-if="activeId === 'sessions'">
            <h2 class="section-title">
              Sessões ativas
            </h2>
            <p class="section-desc">
              Gerencie os dispositivos onde você está logado.
            </p>
            <div
              v-if="tokensLoading"
              class="loading-msg"
            >
              Carregando...
            </div>
            <template v-else>
              <ul
                v-if="tokens.length"
                class="tokens-list"
              >
                <li
                  v-for="t in tokens"
                  :key="t.id"
                  class="token-item"
                >
                  <span class="token-name">{{ t.name }}</span>
                  <span class="token-date">Criado: {{ formatDate(t.created_at) }}</span>
                  <span class="token-date">Último uso: {{ formatDate(t.last_used_at) }}</span>
                </li>
              </ul>
              <p
                v-else
                class="no-tokens"
              >
                Nenhuma sessão ativa.
              </p>
              <BaseButton
                class="revoke-btn"
                variant="danger"
                :disabled="revokeLoading || tokens.length <= 1"
                @click="revokeAllTokens"
              >
                {{ revokeLoading ? 'Revogando...' : 'Sair de todos os dispositivos' }}
              </BaseButton>
            </template>
          </template>
        </BaseCard>
      </template>
    </BaseTabs>
  </div>
</template>

<style scoped>
.profile-view {
  max-width: var(--page-max-width-narrow);
  margin: 0 auto;
}
.profile-view__breadcrumb {
  margin-bottom: var(--page-breadcrumb-margin-bottom);
}
.profile-view__header {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
  margin-bottom: var(--page-header-margin-bottom);
  padding: var(--spacing-lg);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
}
.profile-view__avatar {
  flex-shrink: 0;
}
.profile-view__header-text {
  min-width: 0;
}
.profile-view__title {
  font-size: var(--text-xl);
  font-weight: 700;
  letter-spacing: -0.025em;
  color: var(--color-text);
  margin: 0 0 var(--page-header-gap);
}
.profile-view__subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
  line-height: 1.5;
}
.profile-view__tabs {
  margin-bottom: var(--spacing-md);
}
.profile-view__card {
  margin-top: 0;
  border-radius: var(--radius-md);
  overflow: hidden;
}
.profile-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.section-title {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm);
  letter-spacing: -0.01em;
}
.section-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-md);
  line-height: 1.5;
}
.tokens-list {
  list-style: none;
  padding: 0;
  margin: 0 0 var(--spacing-md);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
}
.token-item {
  padding: var(--spacing-md);
  background: color-mix(in srgb, var(--color-bg-soft) 70%, var(--color-bg-card));
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
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
}
.no-tokens {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-md);
}
.revoke-btn {
  margin-top: var(--spacing-sm);
}
.loading-msg {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  padding: var(--spacing-md) 0;
}
@media (max-width: 480px) {
  .profile-view__header {
    flex-direction: column;
    text-align: center;
    padding: var(--spacing-md);
  }
  .profile-view__title {
    font-size: var(--text-lg);
  }
}
</style>
