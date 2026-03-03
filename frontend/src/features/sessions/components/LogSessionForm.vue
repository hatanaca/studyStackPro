<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'

defineProps<{
  showCancel?: boolean
}>()

const emit = defineEmits<{
  success: []
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
    date.value = today.value
    durationMinutes.value = 30
    notes.value = ''
    emit('success')
  } catch (err: unknown) {
    const msg = err && typeof err === 'object' && 'response' in err
      ? (err as { response?: { data?: { message?: string } } }).response?.data?.message
      : 'Erro ao registrar sessão'
    toast.error(typeof msg === 'string' ? msg : 'Erro ao registrar sessão')
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
    Cadastre ao menos uma tecnologia em <router-link to="/technologies">Tecnologias</router-link> antes de registrar sessões.
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
          step="5"
          class="log-session-form__input"
          :class="{ 'log-session-form__input--error': errors.duration }"
          placeholder="30"
        >
        <p
          v-if="errors.duration"
          class="log-session-form__error"
        >
          {{ errors.duration }}
        </p>
      </div>
    </div>

    <div class="log-session-form__field">
      <label
        for="log-notes"
        class="log-session-form__label"
      >
        Observações (opcional)
      </label>
      <textarea
        id="log-notes"
        v-model="notes"
        class="log-session-form__textarea"
        rows="2"
        placeholder="O que você estudou..."
      />
    </div>

    <div class="log-session-form__actions">
      <BaseButton
        type="submit"
        :disabled="loading"
      >
        {{ loading ? 'Salvando...' : 'Registrar sessão' }}
      </BaseButton>
      <BaseButton
        v-if="showCancel"
        type="button"
        variant="outline"
        @click="onCancel"
      >
        Cancelar
      </BaseButton>
    </div>
  </form>
</template>

<style scoped>
.log-session-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.log-session-form__label {
  display: block;
  font-size: 0.875rem;
  margin-bottom: 0.25rem;
  color: #475569;
}
.log-session-form__field {
  flex: 1;
  min-width: 0;
}
.log-session-form__row {
  display: flex;
  gap: 1rem;
}
.log-session-form__select,
.log-session-form__input {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  box-sizing: border-box;
}
.log-session-form__select--error,
.log-session-form__input--error {
  border-color: #dc2626;
}
.log-session-form__textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  resize: vertical;
  box-sizing: border-box;
}
.log-session-form__error {
  font-size: 0.75rem;
  color: #dc2626;
  margin-top: 0.25rem;
}
.log-session-form__actions {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.log-session-form__empty {
  color: #64748b;
  font-size: 0.875rem;
  margin: 0;
}
.log-session-form__empty a {
  color: #3b82f6;
  text-decoration: none;
}
.log-session-form__empty a:hover {
  text-decoration: underline;
}
</style>
