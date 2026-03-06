<script setup lang="ts">
import { ref, onMounted } from 'vue'
import FormSection from '@/components/ui/FormSection.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import { useUiStore } from '@/stores/ui.store'
import type { CustomThemeOptions } from '@/stores/ui.store'

const uiStore = useUiStore()
const form = ref<CustomThemeOptions>({
  primary: '',
  bg: '',
  bgCard: '',
  text: '',
  textMuted: '',
  border: '',
  fontSans: '',
})

const FONT_OPTIONS = [
  { value: '', label: 'Padrão (Inter)' },
  { value: 'Inter, system-ui, sans-serif', label: 'Inter' },
  { value: 'system-ui, -apple-system, sans-serif', label: 'System UI' },
  { value: 'Georgia, serif', label: 'Georgia' },
  { value: '"Segoe UI", Tahoma, sans-serif', label: 'Segoe UI' },
]

onMounted(() => {
  form.value = { ...uiStore.customTheme }
})

function save() {
  uiStore.setCustomTheme(form.value)
}

function reset() {
  uiStore.resetCustomTheme()
  form.value = { primary: '', bg: '', bgCard: '', text: '', textMuted: '', border: '', fontSans: '' }
}
</script>

<template>
  <FormSection
    title="Aparência"
    description="Personalize cores e fonte. O tema claro/escuro fica no menu principal (ícone Menu)."
    grouped
  >
    <p class="appearance-section__hint">
      Altere o tema (claro/escuro) pelo menu principal. Abaixo, customize cores e fonte.
    </p>
    <div class="appearance-section__editor">
      <h3 class="appearance-section__subtitle">
        Cores
      </h3>
      <div class="appearance-section__grid">
        <label class="appearance-section__field">
          <span>Primária</span>
          <input
            v-model="form.primary"
            type="color"
            class="appearance-section__color"
            @change="save"
          >
          <input
            v-model="form.primary"
            type="text"
            class="appearance-section__text"
            placeholder="#3b82f6"
            @blur="save"
          >
        </label>
        <label class="appearance-section__field">
          <span>Fundo</span>
          <input
            v-model="form.bg"
            type="color"
            class="appearance-section__color"
            @change="save"
          >
          <input
            v-model="form.bg"
            type="text"
            class="appearance-section__text"
            placeholder="#f8fafc"
            @blur="save"
          >
        </label>
        <label class="appearance-section__field">
          <span>Fundo card</span>
          <input
            v-model="form.bgCard"
            type="color"
            class="appearance-section__color"
            @change="save"
          >
          <input
            v-model="form.bgCard"
            type="text"
            class="appearance-section__text"
            placeholder="#ffffff"
            @blur="save"
          >
        </label>
        <label class="appearance-section__field">
          <span>Texto</span>
          <input
            v-model="form.text"
            type="color"
            class="appearance-section__color"
            @change="save"
          >
          <input
            v-model="form.text"
            type="text"
            class="appearance-section__text"
            placeholder="#0f172a"
            @blur="save"
          >
        </label>
        <label class="appearance-section__field">
          <span>Texto secundário</span>
          <input
            v-model="form.textMuted"
            type="color"
            class="appearance-section__color"
            @change="save"
          >
          <input
            v-model="form.textMuted"
            type="text"
            class="appearance-section__text"
            placeholder="#64748b"
            @blur="save"
          >
        </label>
        <label class="appearance-section__field">
          <span>Borda</span>
          <input
            v-model="form.border"
            type="color"
            class="appearance-section__color"
            @change="save"
          >
          <input
            v-model="form.border"
            type="text"
            class="appearance-section__text"
            placeholder="#e2e8f0"
            @blur="save"
          >
        </label>
      </div>
      <h3 class="appearance-section__subtitle">
        Fonte
      </h3>
      <div class="appearance-section__font-row">
        <select
          v-model="form.fontSans"
          class="appearance-section__select"
          @change="save"
        >
          <option
            v-for="opt in FONT_OPTIONS"
            :key="opt.value || 'default'"
            :value="opt.value"
          >
            {{ opt.label }}
          </option>
        </select>
      </div>
      <div class="appearance-section__actions">
        <BaseButton
          variant="outline"
          size="sm"
          @click="reset"
        >
          Resetar estilo
        </BaseButton>
      </div>
    </div>
  </FormSection>
</template>

<style scoped>
.appearance-section__hint {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-md);
  line-height: 1.5;
}
.appearance-section__editor {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}
.appearance-section__subtitle {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-xs);
  text-transform: uppercase;
  letter-spacing: 0.04em;
}
.appearance-section__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--spacing-md);
}
.appearance-section__field {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-xs);
  font-size: var(--text-sm);
  color: var(--color-text-muted);
}
.appearance-section__field span {
  font-weight: 600;
  font-size: var(--text-xs);
  color: var(--color-text);
}
.appearance-section__color {
  width: 100%;
  height: var(--input-height-sm);
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  cursor: pointer;
  background: transparent;
  transition: border-color var(--duration-fast) ease;
}
.appearance-section__color:hover {
  border-color: var(--color-primary);
}
.appearance-section__text {
  padding: 0.35rem 0.5rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-family: monospace;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.appearance-section__text:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
  outline: none;
}
.appearance-section__font-row {
  display: flex;
  gap: var(--spacing-sm);
}
.appearance-section__select {
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  background: var(--color-bg-card);
  color: var(--color-text);
  min-width: 200px;
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.appearance-section__select:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
  outline: none;
}
.appearance-section__actions {
  margin-top: var(--spacing-sm);
}
</style>
