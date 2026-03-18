<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import Button from 'primevue/button'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'

defineProps<{
  showCancel?: boolean
}>()

export interface SessionSavedPayload {
  date: string
  durationMinutes: number
  technologyId: string
  technologyName: string
  technologyColor: string
}

const emit = defineEmits<{
  success: [payload: SessionSavedPayload]
  cancel: []
}>()

const technologiesStore = useTechnologiesStore()
const toast = useToast()

const technologyId = ref('')
const date = ref('')
const durationMinutes = ref(30)
const notes = ref('')
const errors = ref<{
  technology_id?: string
  date?: string
  duration?: string
}>({})
const loading = ref(false)

const today = computed(() => {
  const d = new Date()
  return d.toISOString().slice(0, 10)
})

onMounted(async () => {
  await technologiesStore.fetchTechnologies()
  date.value = today.value
  if (technologiesStore.technologies.length) {
    technologyId.value = technologiesStore.technologies[0].id
  }
})

function validate(): boolean {
  const e: typeof errors.value = {}
  if (!technologyId.value) e.technology_id = 'Selecione uma tecnologia'
  if (!date.value) e.date = 'Data é obrigatória'
  if (!durationMinutes.value || durationMinutes.value < 1) {
    e.duration = 'Informe a duração em minutos'
  } else if (durationMinutes.value > 1440) {
    e.duration = 'Máximo 24 horas (1440 min)'
  }
  errors.value = e
  return Object.keys(e).length === 0
}

async function onSubmit(e: Event) {
  e.preventDefault()
  if (!validate() || loading.value) return

  loading.value = true
  try {
    const startedAt = new Date(`${date.value}T12:00:00`)
    const endedAt = new Date(startedAt.getTime() + durationMinutes.value * 60 * 1000)
    await sessionsApi.create({
      technology_id: technologyId.value,
      started_at: startedAt.toISOString(),
      ended_at: endedAt.toISOString(),
      notes: notes.value.trim() || undefined,
    })
    toast.success('Sessão registrada com sucesso!')
    const tech = technologiesStore.technologies.find(t => t.id === technologyId.value)
    const savedTechId = technologyId.value
    const savedDate = date.value
    const savedDuration = durationMinutes.value
    technologyId.value = technologiesStore.technologies.length ? technologiesStore.technologies[0].id : ''
    date.value = today.value
    durationMinutes.value = 30
    notes.value = ''
    emit('success', {
      date: savedDate,
      durationMinutes: savedDuration,
      technologyId: savedTechId,
      technologyName: tech?.name ?? '',
      technologyColor: tech?.color ?? 'var(--color-primary)',
    })
  } catch (err: unknown) {
    const msg = getApiErrorMessage(err) || 'Erro ao registrar sessão'
    toast.error(msg)
  } finally {
    loading.value = false
  }
}

function onCancel() {
  emit('cancel')
}
</script>

<template>
  <p
    v-if="!technologiesStore.loading && !technologiesStore.technologies.length"
    class="log-session-form__empty"
  >
    Cadastre ao menos uma tecnologia em <router-link to="/technologies">
      Tecnologias
    </router-link> antes de registrar sessões.
  </p>
  <form
    v-else
    class="log-session-form"
    @submit="onSubmit"
  >
    <div class="log-session-form__field">
      <label
        for="log-tech"
        class="log-session-form__label"
      >
        Tecnologia
      </label>
      <select
        id="log-tech"
        v-model="technologyId"
        class="log-session-form__select"
        :class="{ 'log-session-form__select--error': errors.technology_id }"
        aria-label="Selecionar tecnologia da sessão"
      >
        <option
          value=""
          disabled
        >
          Selecione...
        </option>
        <option
          v-for="t in technologiesStore.technologies"
          :key="t.id"
          :value="t.id"
        >
          {{ t.name }}
        </option>
      </select>
      <p
        v-if="errors.technology_id"
        class="log-session-form__error"
      >
        {{ errors.technology_id }}
      </p>
    </div>

    <div class="log-session-form__row">
      <div class="log-session-form__field">
        <label
          for="log-date"
          class="log-session-form__label"
        >
          Data
        </label>
        <input
          id="log-date"
          v-model="date"
          type="date"
          class="log-session-form__input"
          :class="{ 'log-session-form__input--error': errors.date }"
          max="2099-12-31"
        >
        <p
          v-if="errors.date"
          class="log-session-form__error"
        >
          {{ errors.date }}
        </p>
      </div>
    </div>

    <div class="log-session-form__row log-session-form__row--duration-notes">
      <div class="log-session-form__field">
        <label
          for="log-duration"
          class="log-session-form__label"
        >
          Duração (min)
        </label>
        <input
          id="log-duration"
          v-model.number="durationMinutes"
          type="number"
          min="1"
          max="1440"
          step="1"
          class="log-session-form__input"
          :class="{ 'log-session-form__input--error': errors.duration }"
          placeholder="ex: 10, 45, 120"
        >
        <p
          v-if="errors.duration"
          class="log-session-form__error"
        >
          {{ errors.duration }}
        </p>
      </div>
      <div class="log-session-form__field">
        <label
          for="log-notes"
          class="log-session-form__label"
        >
          Lembretes (opcional)
        </label>
        <textarea
          id="log-notes"
          v-model="notes"
          class="log-session-form__textarea"
          rows="2"
          placeholder="Ex.: revisar ponto X, praticar exercícios..."
        />
      </div>
    </div>

    <div class="log-session-form__actions">
      <Button
        type="submit"
        :label="loading ? 'Salvando...' : 'Registrar sessão'"
        :loading="loading"
      />
      <Button
        v-if="showCancel"
        label="Cancelar"
        severity="secondary"
        variant="outlined"
        @click="onCancel"
      />
    </div>
  </form>
</template>

<style scoped>
.log-session-form {
  display: flex;
  flex-direction: column;
  gap: var(--form-section-gap);
}
.log-session-form__label {
  display: block;
  font-size: var(--form-label-size);
  font-weight: var(--form-label-weight);
  letter-spacing: var(--form-label-tracking);
  color: var(--form-label-color);
  margin-bottom: 0;
}
.log-session-form__field {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: var(--form-field-gap);
}
.log-session-form__row {
  display: flex;
  gap: var(--spacing-lg);
}
.log-session-form__select,
.log-session-form__input,
.log-session-form__textarea {
  width: 100%;
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  font-family: inherit;
  box-sizing: border-box;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  outline: none;
  transition: border-color var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease,
    background var(--duration-fast) ease;
}
.log-session-form__select,
.log-session-form__input {
  min-height: var(--form-input-height);
}
.log-session-form__select::placeholder,
.log-session-form__input::placeholder,
.log-session-form__textarea::placeholder {
  color: var(--form-input-placeholder);
}
.log-session-form__select:focus,
.log-session-form__input:focus,
.log-session-form__textarea:focus {
  border-color: var(--form-input-border-focus);
  box-shadow: var(--form-input-shadow-focus);
}
.log-session-form__select--error,
.log-session-form__input--error,
.log-session-form__textarea--error {
  border-color: var(--form-input-border-error);
  box-shadow: var(--form-input-shadow-error);
}
.log-session-form__textarea {
  resize: vertical;
  min-height: calc(var(--form-input-height) * 2.5);
}
.log-session-form__error {
  font-size: var(--form-label-size);
  color: var(--form-input-border-error);
  margin-top: 0;
  line-height: var(--leading-snug);
}
.log-session-form__actions {
  display: flex;
  gap: var(--spacing-sm);
  flex-wrap: wrap;
}
.log-session-form__empty {
  color: var(--color-text-muted);
  font-size: var(--text-sm);
  margin: 0;
  line-height: var(--leading-normal);
}
.log-session-form__empty a {
  color: var(--color-primary);
  font-weight: 500;
  text-decoration: none;
  transition: color var(--duration-fast) ease;
}
.log-session-form__empty a:hover {
  color: var(--color-primary-hover);
  text-decoration: underline;
}
@media (max-width: 640px) {
  .log-session-form__row {
    flex-direction: column;
  }
}
</style>
