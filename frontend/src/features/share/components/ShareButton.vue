<script setup lang="ts">
import { ref, computed } from 'vue'
import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query'
import { linkedinApi } from '@/api/modules/linkedin.api'
import { queryKeys } from '@/api/queryKeys'
import { useToast } from '@/composables/useToast'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import type { LinkedInStatus } from '@/types/domain.types'

const toast = useToast()
const queryClient = useQueryClient()

const visible = ref(false)
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
    visible.value = false
    queryClient.invalidateQueries({ queryKey: queryKeys.linkedin.status() })
  },
  onError: () => {
    toast.error('Falha ao publicar no LinkedIn. Tente novamente.')
  },
})

function handleConnect() {
  const apiOrigin = String(import.meta.env.VITE_API_URL ?? '')
  window.location.href = `${apiOrigin}/api/v1/auth/linkedin`
}

function openModal() {
  if (!isLinked.value) {
    handleConnect()
    return
  }
  visible.value = true
}

function handlePost() {
  if (!canPost.value) return
  shareMutation.mutate(postText.value)
}
</script>

<template>
  <Button
    icon="pi pi-share-alt"
    label="Compartilhar"
    severity="info"
    variant="outlined"
    size="small"
    :loading="statusLoading"
    aria-label="Compartilhar estudo no LinkedIn"
    @click="openModal"
  />

  <Dialog
    v-model:visible="visible"
    modal
    header="Compartilhar no LinkedIn"
    :style="{ width: '32rem' }"
    :closable="!shareMutation.isPending.value"
  >
    <div class="linkedin-share">
      <div class="linkedin-share__profile" v-if="linkedinStatus?.profile">
        <span class="linkedin-share__profile-name">
          {{ linkedinStatus.profile.name }}
        </span>
      </div>

      <Textarea
        v-model="postText"
        placeholder="Escreva sobre o que você estudou hoje..."
        rows="6"
        class="linkedin-share__textarea"
        :class="{ 'p-invalid': isOverLimit }"
        :disabled="shareMutation.isPending.value"
      />

      <div class="linkedin-share__footer">
        <span
          class="linkedin-share__char-count"
          :class="{ 'linkedin-share__char-count--over': isOverLimit }"
        >
          {{ charCount }} / {{ maxChars }}
        </span>
      </div>
    </div>

    <template #footer>
      <Button
        label="Cancelar"
        severity="secondary"
        variant="text"
        :disabled="shareMutation.isPending.value"
        @click="visible = false"
      />
      <Button
        label="Publicar"
        icon="pi pi-send"
        :disabled="!canPost || shareMutation.isPending.value"
        :loading="shareMutation.isPending.value"
        @click="handlePost"
      />
    </template>
  </Dialog>
</template>

<style scoped>
.linkedin-share {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.linkedin-share__profile {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm);
  border-radius: var(--radius-md);
  background: var(--color-bg-soft);
}

.linkedin-share__profile-name {
  font-weight: 600;
  font-size: var(--text-sm);
  color: var(--color-text);
}

.linkedin-share__textarea {
  width: 100%;
  resize: vertical;
  min-height: 8rem;
}

.linkedin-share__footer {
  display: flex;
  justify-content: flex-end;
}

.linkedin-share__char-count {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  font-variant-numeric: tabular-nums;
}

.linkedin-share__char-count--over {
  color: var(--color-error, #e74c3c);
  font-weight: 600;
}
</style>
