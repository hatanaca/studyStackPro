<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import Button from 'primevue/button'

const props = defineProps<{
  technologyId: string
}>()

export interface MuralItem {
  id: string
  type: 'image' | 'quote'
  url?: string
  text?: string
}

const STORAGE_KEY_PREFIX = 'studytrack.mural.'
const newUrl = ref('')
const newQuote = ref('')
const showAddImage = ref(false)
const showAddQuote = ref(false)

const storageKey = computed(() => `${STORAGE_KEY_PREFIX}${props.technologyId}`)
const items = ref<MuralItem[]>([])

function loadFromStorage() {
  try {
    const raw = localStorage.getItem(storageKey.value)
    if (!raw) {
      items.value = []
      return
    }
    const parsed = JSON.parse(raw) as MuralItem[]
    if (Array.isArray(parsed)) items.value = parsed
    else items.value = []
  } catch {
    items.value = []
  }
}

function saveToStorage() {
  localStorage.setItem(storageKey.value, JSON.stringify(items.value))
}

function addImage() {
  const url = newUrl.value.trim()
  if (!url) return
  items.value = [
    ...items.value,
    { id: crypto.randomUUID?.() ?? String(Date.now()), type: 'image', url },
  ]
  newUrl.value = ''
  showAddImage.value = false
  saveToStorage()
}

function addQuote() {
  const text = newQuote.value.trim()
  if (!text) return
  items.value = [
    ...items.value,
    { id: crypto.randomUUID?.() ?? String(Date.now()), type: 'quote', text },
  ]
  newQuote.value = ''
  showAddQuote.value = false
  saveToStorage()
}

function removeItem(item: MuralItem) {
  items.value = items.value.filter((i) => i.id !== item.id)
  saveToStorage()
}

function onImageError(e: Event) {
  const target = (e.target as HTMLImageElement)
  if (target) target.style.display = 'none'
}

onMounted(loadFromStorage)
watch(() => props.technologyId, loadFromStorage)
</script>

<template>
  <section class="tech-mural">
    <h2 class="tech-mural__title">
      Mural
    </h2>
    <p class="tech-mural__subtitle">
      Adicione imagens (URL) ou citações para inspirar seus estudos.
    </p>

    <div class="tech-mural__add">
      <Button
        :label="showAddImage ? 'Cancelar' : '+ Imagem'"
        size="small"
        variant="outlined"
        severity="secondary"
        @click="showAddImage = !showAddImage; showAddQuote = false"
      />
      <Button
        :label="showAddQuote ? 'Cancelar' : '+ Citação'"
        size="small"
        variant="outlined"
        severity="secondary"
        @click="showAddQuote = !showAddQuote; showAddImage = false"
      />
    </div>

    <div
      v-if="showAddImage"
      class="tech-mural__form"
    >
      <input
        v-model="newUrl"
        type="url"
        class="tech-mural__input"
        placeholder="https://..."
        @keyup.enter.prevent="addImage"
      >
      <Button label="Adicionar" size="small" :disabled="!newUrl.trim()" @click="addImage" />
    </div>
    <div
      v-if="showAddQuote"
      class="tech-mural__form"
    >
      <textarea
        v-model="newQuote"
        class="tech-mural__textarea"
        rows="2"
        placeholder="Digite uma citação..."
        @keydown.ctrl.enter.prevent="addQuote"
      />
      <Button label="Adicionar" size="small" :disabled="!newQuote.trim()" @click="addQuote" />
    </div>

    <div
      v-if="items.length"
      class="tech-mural__grid"
    >
      <div
        v-for="item in items"
        :key="item.id"
        class="tech-mural__item"
        :class="`tech-mural__item--${item.type}`"
      >
        <div
          v-if="item.type === 'image' && item.url"
          class="tech-mural__image-wrap"
        >
          <img
            :src="item.url"
            :alt="'Imagem do mural'"
            class="tech-mural__image"
            loading="lazy"
            @error="onImageError"
          >
        </div>
        <blockquote
          v-else-if="item.type === 'quote' && item.text"
          class="tech-mural__quote"
        >
          {{ item.text }}
        </blockquote>
        <button
          type="button"
          class="tech-mural__remove"
          aria-label="Remover"
          @click="removeItem(item)"
        >
          ✕
        </button>
      </div>
    </div>
    <p
      v-else
      class="tech-mural__empty"
    >
      Nenhum item no mural. Adicione uma imagem ou citação acima.
    </p>
  </section>
</template>

<style scoped>
.tech-mural {
  background: var(--color-bg-card);
  border-radius: var(--radius-md);
  padding: var(--widget-padding);
  border: 1px solid var(--color-border);
}
.tech-mural__title {
  font-size: var(--text-base);
  font-weight: 600;
  color: var(--color-text);
  margin: 0 0 var(--spacing-xs);
}
.tech-mural__subtitle {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-md);
  line-height: 1.45;
}
.tech-mural__add {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
}
.tech-mural__form {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
  align-items: flex-start;
}
.tech-mural__input {
  flex: 1;
  min-width: 0;
  min-height: var(--input-height-sm);
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.tech-mural__input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.tech-mural__textarea {
  flex: 1;
  min-width: 0;
  padding: var(--spacing-sm) var(--spacing-md);
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  font-size: var(--text-sm);
  font-family: inherit;
  resize: vertical;
  background: var(--color-bg-card);
  color: var(--color-text);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
}
.tech-mural__textarea:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px var(--color-focus-ring);
}
.tech-mural__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: var(--spacing-md);
}
.tech-mural__item {
  position: relative;
  border-radius: var(--radius-md);
  overflow: hidden;
  border: 1px solid var(--color-border);
  background: var(--color-bg-soft);
  transition: border-color var(--duration-fast) ease;
}
.tech-mural__item:hover {
  border-color: color-mix(in srgb, var(--color-primary) 35%, var(--color-border));
}
.tech-mural__item--image {
  aspect-ratio: 1;
}
.tech-mural__image-wrap {
  width: 100%;
  height: 100%;
  min-height: 120px;
}
.tech-mural__image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}
.tech-mural__quote {
  margin: 0;
  padding: var(--widget-padding);
  font-size: var(--text-sm);
  font-style: italic;
  color: var(--color-text);
  border-left: 3px solid var(--color-primary);
  background: var(--color-bg-soft);
  line-height: 1.5;
}
.tech-mural__remove {
  position: absolute;
  top: var(--spacing-xs);
  right: var(--spacing-xs);
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border: none;
  border-radius: 50%;
  background: color-mix(in srgb, var(--color-text) 50%, transparent);
  color: var(--color-bg-card);
  font-size: var(--text-xs);
  cursor: pointer;
  line-height: 1;
  transition: background var(--duration-fast) ease;
}
.tech-mural__remove:hover {
  background: var(--color-error);
  color: #fff;
}
.tech-mural__empty {
  margin: 0;
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: 1.5;
}
</style>
