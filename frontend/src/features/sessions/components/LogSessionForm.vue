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
  gap: var(--spacing-md);
}
.log-session-form__label {
  display: block;
  font-size: var(--text-xs);
  font-weight: 600;
  margin-bottom: var(--spacing-xs);
  color: var(--color-text-muted);
}
.log-session-form__field {
  flex: 1;
  min-width: 0;
}
.log-session-form__row {
  display: flex;
  gap: var(--spacing-md);
}
.log-session-form__select,
.log-session-form__input {
  width: 100%;
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  box-sizing: border-box;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.log-session-form__select:focus,
.log-session-form__input:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
  outline: none;
}
.log-session-form__select--error,
.log-session-form__input--error {
  border-color: var(--color-error);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--color-error) 22%, transparent);
}
.log-session-form__textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  resize: vertical;
  box-sizing: border-box;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
  min-height: var(--input-height-sm);
}
.log-session-form__textarea:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
  outline: none;
}
.log-session-form__error {
  font-size: var(--text-xs);
  color: var(--color-error);
  margin-top: var(--spacing-xs);
  line-height: 1.35;
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
  line-height: 1.5;
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
@media (max-width: 480px) {
  .log-session-form__row {
    flex-direction: column;
  }
}
</style>
