<script setup lang="ts">
/**
 * Ícones da sidebar (stroke 20x20). Centraliza os SVGs que eram inline no AppSidebar.
 */
defineProps<{ name: string }>()

interface SvgPrimitive {
  tag: 'path' | 'circle' | 'polyline' | 'line' | 'rect'
  attrs: Record<string, string>
}

const icons: Record<string, SvgPrimitive[]> = {
  dashboard: [
    { tag: 'path', attrs: { d: 'M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8' } },
    {
      tag: 'path',
      attrs: {
        d: 'M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z',
      },
    },
  ],
  sessions: [
    { tag: 'circle', attrs: { cx: '12', cy: '12', r: '10' } },
    { tag: 'polyline', attrs: { points: '12 6 12 12 16 14' } },
  ],
  technologies: [
    { tag: 'path', attrs: { d: 'M12 2 2 7l10 5 10-5-10-5z' } },
    { tag: 'path', attrs: { d: 'm2 17 10 5 10-5' } },
    { tag: 'path', attrs: { d: 'm2 12 10 5 10-5' } },
  ],
  canvas: [
    { tag: 'path', attrs: { d: 'M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7' } },
    {
      tag: 'path',
      attrs: {
        d: 'M18.375 2.625a1 1 0 0 1 3 3l-9.013 9.014a2 2 0 0 1-.853.505l-2.873.84a.5.5 0 0 1-.62-.62l.84-2.873a2 2 0 0 1 .506-.852z',
      },
    },
  ],
  reports: [
    { tag: 'path', attrs: { d: 'M3 3v18h18' } },
    { tag: 'path', attrs: { d: 'm19 9-5 5-4-4-3 3' } },
  ],
  terminal: [
    { tag: 'polyline', attrs: { points: '4 17 10 11 4 5' } },
    { tag: 'line', attrs: { x1: '12', y1: '19', x2: '20', y2: '19' } },
  ],
  exercises: [{ tag: 'path', attrs: { d: 'M18 7V4H6l6 8-6 8h12v-3' } }],
  flashcards: [
    { tag: 'rect', attrs: { x: '3', y: '5', width: '14', height: '14', rx: '2' } },
    { tag: 'path', attrs: { d: 'M7 15h4M7 11h6' } },
  ],
  'ita-study': [
    { tag: 'path', attrs: { d: 'M12 3v18' } },
    { tag: 'path', attrs: { d: 'M5 7h14M5 17h14' } },
    { tag: 'path', attrs: { d: 'M3 7l9-4 9 4M3 17l9 4 9-4' } },
  ],
  settings: [
    {
      tag: 'path',
      attrs: {
        d: 'M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.73l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z',
      },
    },
    { tag: 'circle', attrs: { cx: '12', cy: '12', r: '3' } },
  ],
}
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <component v-for="(p, i) in icons[name] ?? []" :key="i" :is="p.tag" v-bind="p.attrs" />
  </svg>
</template>
