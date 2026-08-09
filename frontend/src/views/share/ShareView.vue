<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { linkedinApi } from '@/api/modules/linkedin.api'
import { queryKeys } from '@/api/queryKeys'
import { useToast } from '@/composables/useToast'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'
import PageView from '@/components/layout/PageView.vue'
import type { LinkedInStatus } from '@/types/domain.types'

const toast = useToast()
const queryClient = useQueryClient()

const postText = ref('')
const maxChars = 3000

const charCount = computed(() => postText.value.length)
const isOverLimit = computed(() => charCount.value > maxChars)
const canPost = computed(() => postText.value.trim().length > 0 && !isOverLimit.value)

const { data: linkedinStatus, isLoading: statusLoading } = useQuery({
  queryKey: queryKeys.linkedin.status(),
  queryFn: async () => {
    const { data } = await linkedinApi.status()
    return data.data as LinkedInStatus
  },
  staleTime: 5 * 60 * 1000,
})

const isLinked = computed(() => linkedinStatus.value?.connected === true)

const shareMutation = useMutation({
  mutationFn: async (text: string) => {
    const { data } = await linkedinApi.share({ text })
    return data.data
  },
  onSuccess: () => {
    toast.success('Post publicado no LinkedIn com sucesso!')
    postText.value = ''
    queryClient.invalidateQueries({ queryKey: queryKeys.linkedin.status() })
  },
  onError: () => {
    toast.error('Falha ao publicar no LinkedIn. Tente novamente.')
  },
})

const disconnectMutation = useMutation({
  mutationFn: async () => {
    await linkedinApi.disconnect()
  },
  onSuccess: () => {
    toast.success('Conta LinkedIn desconectada.')
    queryClient.invalidateQueries({ queryKey: queryKeys.linkedin.status() })
  },
  onError: () => {
    toast.error('Falha ao desconectar conta LinkedIn.')
  },
})

function handleConnect() {
  const apiOrigin = String(import.meta.env.VITE_API_URL ?? '')
  window.location.href = `${apiOrigin}/api/v1/auth/linkedin`
}

function handlePost() {
  if (!canPost.value) return
  shareMutation.mutate(postText.value)
}

function handleDisconnect() {
  disconnectMutation.mutate()
}
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Compartilhar' }]"
    title="Compartilhar no LinkedIn"
    subtitle="Compartilhe seus estudos com sua rede profissional."
    narrow
  >
    <div class="share-page">
      <!-- Loading state -->
      <div v-if="statusLoading" class="share-page__loading">
        <Skeleton width="100%" height="12rem" border-radius="var(--radius-lg)" />
      </div>

      <!-- Not connected -->
      <div v-else-if="!isLinked" class="share-page__connect">
        <div class="share-page__connect-card">
          <i class="pi pi-linkedin share-page__connect-icon" />
          <h2 class="share-page__connect-title">Conecte sua conta LinkedIn</h2>
          <p class="share-page__connect-desc">
            Para compartilhar seus estudos, primeiro conecte sua conta LinkedIn.
          </p>
          <Button label="Conectar LinkedIn" icon="pi pi-external-link" @click="handleConnect" />
        </div>
      </div>

      <!-- Connected: editor -->
      <div v-else class="share-page__editor">
        <div class="share-page__profile">
          <i class="pi pi-linkedin share-page__profile-icon" />
          <div class="share-page__profile-info">
            <span class="share-page__profile-name">
              {{ linkedinStatus?.profile?.name ?? 'Conta conectada' }}
            </span>
            <span class="share-page__profile-status">Conectado</span>
          </div>
          <Button
            label="Desconectar"
            severity="danger"
            variant="text"
            size="small"
            :disabled="disconnectMutation.isPending.value"
            :loading="disconnectMutation.isPending.value"
            @click="handleDisconnect"
          />
        </div>

        <div class="share-page__form">
          <label for="post-text" class="share-page__label"> O que você estudou hoje? </label>
          <Textarea
            id="post-text"
            v-model="postText"
            placeholder="Ex: Hoje estudei Laravel e Vue.js. Aprendi sobre repositories, services e DTOs. Uma sessão muito produtiva!"
            rows="8"
            class="share-page__textarea"
            :class="{ 'p-invalid': isOverLimit }"
            :disabled="shareMutation.isPending.value"
          />

          <div class="share-page__form-footer">
            <span
              class="share-page__char-count"
              :class="{ 'share-page__char-count--over': isOverLimit }"
            >
              {{ charCount }} / {{ maxChars }}
            </span>
          </div>
        </div>

        <div class="share-page__actions">
          <Button
            label="Publicar no LinkedIn"
            icon="pi pi-send"
            :disabled="!canPost || shareMutation.isPending.value"
            :loading="shareMutation.isPending.value"
            @click="handlePost"
          />
        </div>
      </div>
    </div>
  </PageView>
</template>

<style scoped>
.share-page {
  padding: 0;
}

.share-page__header {
  margin-bottom: var(--spacing-xl);
}

.share-page__title {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 700;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
}

.share-page__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0;
}

.share-page__loading {
  display: flex;
  justify-content: center;
  padding: var(--spacing-xl);
}

.share-page__connect-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: var(--spacing-2xl);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
}

.share-page__connect-icon {
  font-size: 3rem;
  color: #0077b5;
  margin-bottom: var(--spacing-md);
}

.share-page__connect-title {
  font-size: var(--text-lg);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-sm);
}

.share-page__connect-desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
  max-width: 30ch;
}

.share-page__editor {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.share-page__profile {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  background: var(--color-bg-card);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
}

.share-page__profile-icon {
  font-size: 1.5rem;
  color: #0077b5;
}

.share-page__profile-info {
  display: flex;
  flex-direction: column;
  flex: 1;
  min-width: 0;
}

.share-page__profile-name {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--color-text);
}

.share-page__profile-status {
  font-size: var(--text-xs);
  color: var(--color-success);
}

.share-page__form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.share-page__label {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--color-text);
}

.share-page__textarea {
  width: 100%;
  resize: vertical;
  min-height: 10rem;
}

.share-page__form-footer {
  display: flex;
  justify-content: flex-end;
}

.share-page__char-count {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

.share-page__char-count--over {
  color: var(--color-error);
  font-weight: 600;
}

.share-page__actions {
  display: flex;
  justify-content: flex-end;
}
</style>
