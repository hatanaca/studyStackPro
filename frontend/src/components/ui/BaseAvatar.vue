<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    /** URL da imagem ou iniciais (ex: "JD" para João Silva) */
    src?: string | null
    /** Texto alternativo / iniciais quando não há imagem */
    alt?: string
    /** Nome para gerar iniciais automaticamente (ex: "João Silva" → "JS") */
    name?: string
    /** Tamanho: sm (2rem), md (2.5rem), lg (3rem), xl (4rem) */
    size?: 'sm' | 'md' | 'lg' | 'xl'
    /** Cor de fundo quando exibindo iniciais (hex ou var) */
    backgroundColor?: string
  }>(),
  { src: null, alt: '', name: '', size: 'md', backgroundColor: '' }
)

const initials = computed(() => {
  if (props.alt && props.alt.length <= 3) return props.alt
  if (!props.name) return props.alt || '?'
  const parts = props.name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
  }
  return props.name.slice(0, 2).toUpperCase()
})

const sizeClass = computed(() => `base-avatar--${props.size}`)
const hasImage = computed(() => Boolean(props.src))
</script>

<template>
  <div
    class="base-avatar"
    :class="[sizeClass, { 'base-avatar--img': hasImage }]"
    :style="backgroundColor && !hasImage ? { backgroundColor } : undefined"
    role="img"
    :aria-label="alt || name || 'Avatar'"
  >
    <img
      v-if="hasImage"
      :src="src!"
      :alt="alt || name || ''"
      class="base-avatar__img"
      @error="(e: Event) => (e.currentTarget as HTMLImageElement).style.display = 'none'"
    >
    <span
      v-else
      class="base-avatar__initials"
      :aria-hidden="true"
    >
      {{ initials }}
    </span>
  </div>
</template>

<style scoped>
.base-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: var(--radius-full);
  overflow: hidden;
  background: var(--color-primary-soft);
  color: var(--color-primary);
  font-weight: 600;
  font-size: 0.75em;
  line-height: 1;
  user-select: none;
  transition: transform var(--duration-fast) var(--ease-out-expo);
}
.base-avatar:hover {
  transform: scale(1.02);
}
.base-avatar--sm {
  width: var(--avatar-size-sm, 2rem);
  height: var(--avatar-size-sm, 2rem);
  font-size: 0.625rem;
}
.base-avatar--md {
  width: var(--avatar-size-md, 2.5rem);
  height: var(--avatar-size-md, 2.5rem);
  font-size: 0.75rem;
}
.base-avatar--lg {
  width: var(--avatar-size-lg, 3rem);
  height: var(--avatar-size-lg, 3rem);
  font-size: 0.875rem;
}
.base-avatar--xl {
  width: var(--avatar-size-xl, 4rem);
  height: var(--avatar-size-xl, 4rem);
  font-size: 1rem;
}
.base-avatar__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.base-avatar__initials {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}
</style>
