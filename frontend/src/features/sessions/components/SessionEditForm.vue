<script setup lang="ts">
import Button from 'primevue/button'
import type { EditFormState } from '@/features/sessions/composables/useSessionEdit'
import type { StudySession } from '@/types/domain.types'

defineProps<{
  session: StudySession | null
  loading: boolean
}>()

const emit = defineEmits<{
  save: []
  cancel: []
  'update:editForm': [value: EditFormState]
}>()

const editForm = defineModel<EditFormState>('editForm', { required: true })
</script>

<template>
  <div class="edit-form-scroll">
    <form class="edit-form" @submit.prevent="emit('save')">
      <div class="edit-form__field">
        <label class="edit-form__label">Nome / tópico da sessão</label>
        <input
          v-model="editForm.title"
          type="text"
          class="edit-form__input"
          maxlength="255"
          placeholder="Ex.: Funções, rotas, testes…"
          autocomplete="off"
        />
      </div>
      <div class="edit-form__field">
        <span class="edit-form__label">Tecnologia</span>
        <p class="edit-form__tech-readonly" aria-live="polite">
          {{ session?.technology?.name ?? '—' }}
        </p>
        <p class="edit-form__tech-note">A tecnologia da sessão não pode ser alterada.</p>
      </div>

      <div class="edit-form__row">
        <div class="edit-form__field">
          <label class="edit-form__label">Data</label>
          <input v-model="editForm.date" type="date" class="edit-form__input" />
        </div>
        <div class="edit-form__field">
          <label class="edit-form__label">Duração (min)</label>
          <input
            v-model.number="editForm.duration"
            type="number"
            min="1"
            max="1440"
            class="edit-form__input"
          />
        </div>
      </div>

      <div class="edit-form__field">
        <label class="edit-form__label">Observações</label>
        <textarea v-model="editForm.notes" rows="4" class="edit-form__textarea" />
      </div>

      <div class="edit-form__actions">
        <Button
          type="submit"
          :label="loading ? 'Salvando...' : 'Salvar'"
          :loading="loading"
        />
        <Button
          label="Cancelar"
          severity="secondary"
          variant="outlined"
          @click="emit('cancel')"
        />
      </div>
    </form>
  </div>
</template>

<style scoped>
.edit-form-scroll {
  max-height: min(70vh, 26rem);
  overflow-y: auto;
  padding-right: var(--spacing-2xs);
}
.edit-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.edit-form__label {
  display: block;
  font-size: var(--form-label-size);
  font-weight: var(--form-label-weight);
  letter-spacing: var(--form-label-tracking);
  margin-bottom: 0;
  color: var(--form-label-color);
}
.edit-form__field {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--form-field-gap);
}
.edit-form__tech-readonly {
  margin: 0;
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  font-weight: 600;
  background: var(--color-bg-soft);
  color: var(--form-input-text);
  line-height: var(--leading-snug);
  display: flex;
  align-items: center;
  box-sizing: border-box;
}
.edit-form__tech-note {
  margin: 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.edit-form__row {
  display: flex;
  gap: var(--spacing-lg);
}
.edit-form__select,
.edit-form__input {
  width: 100%;
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  box-sizing: border-box;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.edit-form__select:focus-visible,
.edit-form__input:focus-visible {
  outline: none;
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.edit-form__textarea {
  width: 100%;
  min-height: 6rem;
  max-height: 9rem;
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  resize: none;
  overflow-y: auto;
  box-sizing: border-box;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  transition:
    border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.edit-form__textarea:focus-visible {
  outline: none;
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.edit-form__actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}

@media (max-width: 640px) {
  .edit-form__row {
    flex-direction: column;
  }
}
</style>
