<script setup lang="ts">
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import Button from 'primevue/button'
import Skeleton from 'primevue/skeleton'
import EmptyState from '@/components/ui/EmptyState.vue'
import { useTechnologiesStore } from '@/stores/technologies.store'
import { getApiErrorMessage } from '@/api/client'
import { sessionsApi } from '@/api/modules/sessions.api'
import { useToast } from '@/composables/useToast'
import {
  STUDYTRACK_REMINDER_REMOVED_EVENT,
  stripNotesLinesMatching,
} from '@/features/sessions/utils/reminderRemovedSync'

const props = defineProps<{
  showCancel?: boolean
  defaultTechnologyId?: string
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

const router = useRouter()
const technologiesStore = useTechnologiesStore()
const toast = useToast()

const technologyId = ref('')
const sessionTitle = ref('')
const date = ref('')
const durationMinutes = ref(30)
const notes = ref('')
const errors = ref<{
  title?: string
  technology_id?: string
  date?: string
  duration?: string
}>({})
const loading = ref(false)
/** Evita flash de “empty” antes do primeiro fetch da store (loading ainda false). */
const listReady = ref(false)

const today = computed(() => {
  const d = new Date()
  const y = d.getFullYear()
  const m = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${y}-${m}-${day}`
})

/** Na página de uma tecnologia: sessão conta sempre para essa tecnologia (sem troca). */
const technologyFixed = computed(() => !!props.defaultTechnologyId?.trim())

const lockedTechnologyLabel = computed(() => {
  const id = props.defaultTechnologyId?.trim()
  if (!id) return ''
  return technologiesStore.technologies.find((t) => t.id === id)?.name ?? ''
})

watch(
  () => props.defaultTechnologyId,
  (id) => {
    const t = id?.trim()
    if (t) technologyId.value = t
  },
  { immediate: true },
)

onMounted(async () => {
  try {
    if (!technologiesStore.technologies.length) {
      await technologiesStore.fetchTechnologies()
    }
  } catch {
    /* 401/outros: interceptor redireciona; evita unhandled rejection no mount */
  } finally {
    listReady.value = true
  }
  date.value = today.value
  if (!props.defaultTechnologyId?.trim() && technologiesStore.technologies.length) {
    technologyId.value = technologiesStore.technologies[0].id
  }
  window.addEventListener(
    STUDYTRACK_REMINDER_REMOVED_EVENT,
    onStudytrackReminderRemoved as EventListener,
  )
})

onBeforeUnmount(() => {
  window.removeEventListener(
    STUDYTRACK_REMINDER_REMOVED_EVENT,
    onStudytrackReminderRemoved as EventListener,
  )
})

function onStudytrackReminderRemoved(ev: Event) {
  const d = (ev as CustomEvent<{ technologyId?: string; text?: string }>).detail
  // #region agent log
  fetch('http://127.0.0.1:7251/ingest/086e8d00-457e-4a30-82b0-abf450d19c28', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '4b11d9' },
    body: JSON.stringify({
      sessionId: '4b11d9',
      location: 'LogSessionForm.vue:onStudytrackReminderRemoved',
      message: 'reminder removed (registrar sessão)',
      data: {
        hasDetail: !!d,
        tidMatch: d?.technologyId === technologyId.value,
        techId: technologyId.value,
        eventTech: d?.technologyId,
      },
      timestamp: Date.now(),
      hypothesisId: 'H2',
    }),
  }).catch(() => {})
  // #endregion
  if (!d?.technologyId || !d.text?.trim()) return
  if (d.technologyId !== technologyId.value) return
  const before = notes.value
  notes.value = stripNotesLinesMatching(notes.value, d.text)
  // #region agent log
  fetch('http://127.0.0.1:7251/ingest/086e8d00-457e-4a30-82b0-abf450d19c28', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Debug-Session-Id': '4b11d9' },
    body: JSON.stringify({
      sessionId: '4b11d9',
      location: 'LogSessionForm.vue:afterStripNotes',
      message: 'observações após strip',
      data: {
        beforeLen: before.length,
        afterLen: notes.value.length,
        changed: before !== notes.value,
      },
      timestamp: Date.now(),
      hypothesisId: 'H2',
    }),
  }).catch(() => {})
  // #endregion
}

function validate(): boolean {
  const e: typeof errors.value = {}
  if (!sessionTitle.value.trim()) e.title = 'Informe um nome para a sessão (ex.: Funções)'
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
    const start = new Date(`${date.value}T12:00:00`)
    const end = new Date(start.getTime() + durationMinutes.value * 60_000)
    const toISO = (d: Date) => {
      const yy = d.getFullYear()
      const mm = String(d.getMonth() + 1).padStart(2, '0')
      const dd = String(d.getDate()).padStart(2, '0')
      const hh = String(d.getHours()).padStart(2, '0')
      const mi = String(d.getMinutes()).padStart(2, '0')
      const ss = String(d.getSeconds()).padStart(2, '0')
      return `${yy}-${mm}-${dd}T${hh}:${mi}:${ss}`
    }
    await sessionsApi.create({
      technology_id: technologyId.value,
      title: sessionTitle.value.trim(),
      started_at: toISO(start),
      ended_at: toISO(end),
      duration_min: durationMinutes.value,
      notes: notes.value.trim() || undefined,
    })
    toast.success('Sessão registrada com sucesso!')
    const tech = technologiesStore.technologies.find((t) => t.id === technologyId.value)
    const savedTechId = technologyId.value
    const savedDate = date.value
    const savedDuration = durationMinutes.value
    technologyId.value =
      props.defaultTechnologyId ??
      (technologiesStore.technologies.length ? technologiesStore.technologies[0].id : '')
    date.value = today.value
    durationMinutes.value = 30
    sessionTitle.value = ''
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
  <div
    v-if="!listReady || technologiesStore.loading"
    class="log-session-form__loading"
    role="status"
    aria-live="polite"
    aria-label="Carregando tecnologias"
  >
    <Skeleton height="2.75rem" class="log-session-form__skel" />
    <Skeleton height="2.75rem" class="log-session-form__skel" />
    <Skeleton height="5rem" class="log-session-form__skel" />
  </div>
  <EmptyState
    v-else-if="!technologiesStore.technologies.length"
    icon="⚡"
    title="Nenhuma tecnologia cadastrada"
    description="Cadastre ao menos uma tecnologia para categorizar e registrar suas sessões de estudo."
    action-label="Ir para Tecnologias"
    :hide-action="false"
    @action="router.push('/technologies')"
  />
  <form v-else class="log-session-form" @submit="onSubmit">
    <div class="log-session-form__field">
      <label for="log-title" class="log-session-form__label"> Nome / tópico da sessão </label>
      <input
        id="log-title"
        v-model="sessionTitle"
        type="text"
        class="log-session-form__input"
        :class="{ 'log-session-form__input--error': errors.title }"
        maxlength="255"
        placeholder="Ex.: Funções, diretivas, testes unitários…"
        autocomplete="off"
        @input="errors.title = undefined"
      />
      <p v-if="errors.title" class="log-session-form__error">
        {{ errors.title }}
      </p>
    </div>
    <div class="log-session-form__field">
      <template v-if="technologyFixed">
        <span class="log-session-form__label">Tecnologia</span>
        <p class="log-session-form__locked-tech" aria-live="polite">
          {{ lockedTechnologyLabel || '…' }}
        </p>
        <p class="log-session-form__tech-note">
          Nesta página todas as sessões registam-se nesta tecnologia.
        </p>
      </template>
      <template v-else>
        <label for="log-tech" class="log-session-form__label"> Tecnologia </label>
        <select
          id="log-tech"
          v-model="technologyId"
          class="log-session-form__select"
          :class="{ 'log-session-form__select--error': errors.technology_id }"
          aria-label="Selecionar tecnologia da sessão"
          @change="errors.technology_id = undefined"
        >
          <option value="" disabled>Selecione...</option>
          <option v-for="t in technologiesStore.technologies" :key="t.id" :value="t.id">
            {{ t.name }}
          </option>
        </select>
        <p v-if="errors.technology_id" class="log-session-form__error">
          {{ errors.technology_id }}
        </p>
        <button type="button" class="log-session-form__tech-manage" @click="router.push('/technologies')">
          Gerir ou criar tecnologias
        </button>
      </template>
    </div>

    <div class="log-session-form__row">
      <div class="log-session-form__field">
        <label for="log-date" class="log-session-form__label"> Data </label>
        <input
          id="log-date"
          v-model="date"
          type="date"
          class="log-session-form__input"
          :class="{ 'log-session-form__input--error': errors.date }"
          max="2099-12-31"
          @input="errors.date = undefined"
        />
        <p v-if="errors.date" class="log-session-form__error">
          {{ errors.date }}
        </p>
      </div>
    </div>

    <div class="log-session-form__row log-session-form__row--duration-notes">
      <div class="log-session-form__field">
        <label for="log-duration" class="log-session-form__label"> Duração (min) </label>
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
          @input="errors.duration = undefined"
        />
        <p v-if="errors.duration" class="log-session-form__error">
          {{ errors.duration }}
        </p>
      </div>
      <div class="log-session-form__field">
        <label for="log-notes" class="log-session-form__label"> Observações (opcional) </label>
        <textarea
          id="log-notes"
          v-model="notes"
          class="log-session-form__textarea"
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
.log-session-form__locked-tech {
  margin: 0;
  min-height: var(--form-input-height);
  padding: var(--form-input-padding);
  border: 1px solid var(--form-input-border);
  border-radius: var(--form-input-radius);
  font-size: var(--form-input-font-size);
  font-weight: 600;
  background: var(--form-input-bg);
  color: var(--form-input-text);
  line-height: var(--leading-snug);
  display: flex;
  align-items: center;
}
.log-session-form__tech-note {
  margin: 0;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  line-height: var(--leading-snug);
}
.log-session-form__tech-manage {
  align-self: flex-start;
  margin: 0;
  padding: 0;
  border: none;
  background: none;
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-primary);
  text-decoration: underline;
  text-underline-offset: 2px;
  cursor: pointer;
}
.log-session-form__tech-manage:hover {
  color: var(--color-primary-hover);
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
  transition:
    border-color var(--duration-fast) ease,
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
.log-session-form__select:focus-visible,
.log-session-form__input:focus-visible,
.log-session-form__textarea:focus-visible {
  outline: none;
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
  resize: none !important;
  field-sizing: fixed;
  align-self: flex-start;
  height: calc(var(--form-input-height) * 2.5);
  min-height: calc(var(--form-input-height) * 2.5);
  max-height: calc(var(--form-input-height) * 2.5);
  overflow-y: auto;
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
.log-session-form__loading {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
  padding: var(--spacing-sm) 0;
}
.log-session-form__skel {
  border-radius: var(--radius-md);
}
@media (max-width: 640px) {
  .log-session-form__row {
    flex-direction: column;
  }
}
</style>
