<script setup lang="ts">
import { ref, onMounted } from 'vue'
import Fieldset from 'primevue/fieldset'
import Button from 'primevue/button'
import { useUiStore } from '@/stores/ui.store'
import type { CustomThemeOptions } from '@/stores/ui.store'
import { safeHexColor } from '@/utils/color'

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
  { value: '', label: 'Padrão (DM Sans)' },
  { value: "'DM Sans', system-ui, sans-serif", label: 'DM Sans' },
  { value: 'system-ui, -apple-system, sans-serif', label: 'System UI' },
  { value: 'Georgia, serif', label: 'Georgia' },
  { value: '"Segoe UI", Tahoma, sans-serif', label: 'Segoe UI' },
]

onMounted(() => {
  form.value = { ...uiStore.customTheme }
})

function setColor(key: keyof CustomThemeOptions, value: string) {
  form.value[key] = value
  save()
}

function save() {
  uiStore.setCustomTheme(form.value)
}

function reset() {
  uiStore.resetCustomTheme()
  form.value = { primary: '', bg: '', bgCard: '', text: '', textMuted: '', border: '', fontSans: '' }
}
</script>

<template>
  <Fieldset legend="Aparência">
    <p class="appearance-section__desc">
      Personalize cores e fonte. O tema claro/escuro fica no menu principal (ícone Menu).
    </p>
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
            :value="safeHexColor(form.primary, '#3b82f6')"
            type="color"
            class="appearance-section__color"
            @input="setColor('primary', ($event.target as HTMLInputElement).value)"
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
            :value="safeHexColor(form.bg, '#f8fafc')"
            type="color"
            class="appearance-section__color"
            @input="setColor('bg', ($event.target as HTMLInputElement).value)"
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
            :value="safeHexColor(form.bgCard, '#ffffff')"
            type="color"
            class="appearance-section__color"
            @input="setColor('bgCard', ($event.target as HTMLInputElement).value)"
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
            :value="safeHexColor(form.text, '#0f172a')"
            type="color"
            class="appearance-section__color"
            @input="setColor('text', ($event.target as HTMLInputElement).value)"
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
            :value="safeHexColor(form.textMuted, '#64748b')"
            type="color"
            class="appearance-section__color"
            @input="setColor('textMuted', ($event.target as HTMLInputElement).value)"
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
            :value="safeHexColor(form.border, '#e2e8f0')"
            type="color"
            class="appearance-section__color"
            @input="setColor('border', ($event.target as HTMLInputElement).value)"
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
        <Button
          label="Resetar estilo"
          severity="secondary"
          variant="outlined"
          size="small"
          @click="reset"
        />
      </div>
    </div>
  </Fieldset>
</template>

<style scoped>
.appearance-section__desc,
.appearance-section__hint {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
  line-height: var(--leading-normal);
  letter-spacing: var(--tracking-tight);
}
.appearance-section__editor {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}
.appearance-section__subtitle {
  font-size: var(--text-xs);
  font-weight: 600;
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-xs);
  text-transform: uppercase;
  letter-spacing: var(--tracking-wide);
}
.appearance-section__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: var(--spacing-lg);
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
.appearance-section__color:focus-visible,
.appearance-section__text:focus-visible,
.appearance-section__select:focus-visible {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.appearance-section__text {
  padding: var(--spacing-xs) var(--spacing-sm);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-family: monospace;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.appearance-section__font-row {
  display: flex;
  gap: var(--spacing-sm);
}
.appearance-section__select {
  min-height: var(--input-height-sm);
  padding: var(--spacing-sm) var(--spacing-xl);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  background: var(--color-bg-card);
  color: var(--color-text);
  min-width: clamp(10rem, 46vw, 16rem);
  width: 100%;
  max-width: 20rem;
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.appearance-section__actions {
  margin-top: var(--spacing-sm);
}
.appearance-section__actions :deep(.p-button) {
  min-height: var(--touch-target-min);
}
.appearance-section__actions :deep(.p-button:focus-visible) {
  outline: none;
  box-shadow: var(--shadow-focus);
}
@media (max-width: 640px) {
  .appearance-section__font-row {
    width: 100%;
  }
  .appearance-section__select {
    min-width: 0;
    max-width: none;
  }
}
</style>
