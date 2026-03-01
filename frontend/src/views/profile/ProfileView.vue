<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import BaseCard from '@/components/ui/BaseCard.vue'
import BaseInput from '@/components/ui/BaseInput.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useAuthStore } from '@/stores/auth.store'
import { authApi, type TokenInfo } from '@/api/modules/auth.api'
import { useToast } from '@/composables/useToast'
const authStore = useAuthStore()
const router = useRouter()
const toast = useToast()

const activeTab = ref<'profile' | 'password' | 'sessions'>('profile')

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
    <h1 class="profile-view__title">
      Configurações
    </h1>
    <div class="profile-view__tabs">
      <button
        v-for="t in ([{ k: 'profile', l: 'Perfil' }, { k: 'password', l: 'Senha' }, { k: 'sessions', l: 'Sessões' }] as const)"
        :key="t.k"
        type="button"
        class="tab"
        :class="{ active: activeTab === t.k }"
        @click="activeTab = t.k"
      >
        {{ t.l }}
      </button>
    </div>
    <BaseCard class="profile-view__card">
      <template v-if="activeTab === 'profile'">
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
      <template v-else-if="activeTab === 'password'">
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
      <template v-else>
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
  </div>
</template>

<style scoped>
.profile-view {
  max-width: 480px;
}
.profile-view__title {
  font-size: 1.5rem;
  color: #1e293b;
  margin-bottom: 1rem;
}
.profile-view__tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.tab {
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  border: none;
  border-radius: 0.375rem;
  cursor: pointer;
  font-size: 0.9rem;
}
.tab.active {
  background: #1e293b;
  color: #fff;
}
.profile-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.section-title {
  font-size: 1.1rem;
  margin-bottom: 0.5rem;
}
.section-desc {
  color: #64748b;
  font-size: 0.9rem;
  margin-bottom: 1rem;
}
.tokens-list {
  list-style: none;
  padding: 0;
  margin: 0 0 1rem;
}
.token-item {
  padding: 0.75rem;
  background: #f8fafc;
  border-radius: 0.375rem;
  margin-bottom: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.token-name {
  font-weight: 500;
}
.token-date {
  font-size: 0.85rem;
  color: #64748b;
}
.no-tokens {
  color: #64748b;
  margin-bottom: 1rem;
}
.revoke-btn {
  margin-top: 0.5rem;
}
.loading-msg {
  color: #64748b;
}
</style>
