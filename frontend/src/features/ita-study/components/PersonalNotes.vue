<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useNoteQuery, useSaveNoteMutation, useDeleteNoteMutation } from '../composables/useUserStudyQuery'

const props = defineProps<{
  subTopicId: string
}>()

const { data: note, isLoading } = useNoteQuery(computed(() => props.subTopicId))
const saveMutation = useSaveNoteMutation()
const deleteMutation = useDeleteNoteMutation()

const content = ref('')
const savedAt = ref<string | null>(null)

watch(note, (value) => {
  content.value = value?.content ?? ''
  savedAt.value = value?.updated_at ?? null
}, { immediate: true })

const isDirty = computed(() => {
  const original = note.value?.content ?? ''
  return content.value !== original
})

const isSaving = computed(() => saveMutation.isPending.value)

function save() {
  if (!content.value.trim()) {
    deleteMutation.mutate(props.subTopicId)
    return
  }
  saveMutation.mutate(
    { subTopicId: props.subTopicId, content: content.value },
    {
      onSuccess: () => {
        savedAt.value = new Date().toISOString()
      },
    }
  )
}

function formatDate(iso: string | null): string {
  if (!iso) return ''
  return new Date(iso).toLocaleString('pt-BR', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
  })
}
</script>

<template>
  <div class="personal-notes">
    <div class="notes-toolbar">
      <div class="notes-status">
        <i v-if="isLoading" class="pi pi-spin pi-spinner"></i>
        <span v-else-if="savedAt" class="saved-hint">
          Salvo em {{ formatDate(savedAt) }}
        </span>
        <span v-else-if="content" class="saved-hint">Nota salva</span>
        <span v-else class="saved-hint">Sem notas ainda</span>
      </div>
      <div class="notes-actions">
        <Button
          label="Salvar"
          icon="pi pi-save"
          size="small"
          :loading="isSaving"
          :disabled="!isDirty"
          @click="save"
        />
      </div>
    </div>

    <Textarea
      v-model="content"
      :placeholder="'Anote suas dúvidas, resumos e insights sobre este tópico...'"
      rows="5"
      class="notes-textarea"
      auto-resize
    />
  </div>
</template>

<style scoped>
.personal-notes {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.notes-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.notes-status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: var(--p-text-muted-color);
}

.saved-hint {
  font-style: italic;
}

.notes-textarea {
  width: 100%;
}
</style>
