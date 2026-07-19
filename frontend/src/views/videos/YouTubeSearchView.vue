<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useYouTubeStore } from '@/stores/youtube.store'
import PageView from '@/components/layout/PageView.vue'

const store = useYouTubeStore()
const query = ref('')
const descExpanded = ref(false)
const resultsVisible = ref(true)

/** Debounce helper */
let debounceTimer: ReturnType<typeof setTimeout> | null = null

function onInput() {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    if (query.value.trim().length >= 2) {
      store.search(query.value.trim())
    }
  }, 500)
}

function onSearch() {
  if (debounceTimer) clearTimeout(debounceTimer)
  const q = query.value.trim()
  if (q.length < 2) return
  store.search(q)
}

function selectVideo(videoId: string) {
  store.loadVideoDetail(videoId)
  descExpanded.value = false
}

function nextPage() {
  if (store.nextPageToken && query.value.trim()) {
    store.search(query.value.trim(), store.nextPageToken)
  }
}

function prevPage() {
  if (store.prevPageToken && query.value.trim()) {
    store.search(query.value.trim(), store.prevPageToken)
  }
}

function formatDuration(iso: string): string {
  const match = iso.match(/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/)
  if (!match) return iso
  const h = parseInt(match[1] || '0')
  const m = parseInt(match[2] || '0')
  const s = parseInt(match[3] || '0')
  if (h > 0) return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
  return `${m}:${String(s).padStart(2, '0')}`
}

function formatViews(n: string): string {
  const num = parseInt(n)
  if (isNaN(num)) return n
  if (num >= 1_000_000) return `${(num / 1_000_000).toFixed(1)}M`
  if (num >= 1_000) return `${(num / 1_000).toFixed(1)}K`
  return String(num)
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('pt-BR', {
    year: 'numeric', month: 'short', day: 'numeric',
  })
}

onUnmounted(() => {
  store.clear()
})
</script>

<template>
  <PageView
    :breadcrumb="[{ label: 'Dashboard', to: '/' }, { label: 'Vídeos' }]"
    title="Vídeos de Estudo"
    subtitle="Busque tutoriais e aulas no YouTube para complementar seus estudos."
    narrow
  >
  <div class="yt-page">
    <form class="yt-search" @submit.prevent="onSearch">
        <div class="yt-search__wrap">
          <svg class="yt-search__icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
            <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
          </svg>
          <input
            v-model="query"
            type="text"
            class="yt-search__input"
            placeholder="Pesquisar vídeos... (ex: React hooks tutorial)"
            @input="onInput"
          />
          <button
            type="submit"
            class="yt-search__btn"
            :disabled="store.loading || query.trim().length < 2"
          >
            <svg v-if="store.loading" class="yt-spinner-sm" viewBox="0 0 24 24" width="16" height="16">
              <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="31.4 31.4" stroke-linecap="round" />
            </svg>
            <span v-else>Buscar</span>
          </button>
        </div>
      </form>

    <!-- Loading: skeletons -->
    <div v-if="store.loading && !store.results.length" class="yt-skeleton-grid">
      <div v-for="i in 6" :key="i" class="yt-skeleton-card">
        <div class="yt-skeleton-thumb" />
        <div class="yt-skeleton-body">
          <div class="yt-skeleton-line w-75" />
          <div class="yt-skeleton-line w-50" />
          <div class="yt-skeleton-line w-33" />
        </div>
      </div>
    </div>

    <!-- Error -->
    <div v-else-if="store.error" class="yt-state">
      <div class="yt-state__icon">⚠️</div>
      <p class="yt-state__text">{{ store.error }}</p>
      <button class="yt-btn" @click="onSearch">Tentar novamente</button>
    </div>

    <template v-else>
      <!-- Player + detalhes -->
      <Transition name="yt-slide">
        <div v-if="store.selectedVideoId" class="yt-player-section">
          <div class="yt-player">
            <iframe
              :key="store.selectedVideoId"
              :src="`https://www.youtube.com/embed/${store.selectedVideoId}?autoplay=1&modestbranding=1&rel=0`"
              title="YouTube video player"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
              class="yt-player__iframe"
            />
            <div v-if="store.videoLoading" class="yt-player__loading">
              <div class="yt-spinner" />
            </div>
          </div>

          <Transition name="yt-fade">
            <div v-if="store.videoDetail" class="yt-detail">
              <h2 class="yt-detail__title">{{ store.videoDetail.snippet.title }}</h2>

              <div class="yt-detail__meta">
                <span class="yt-detail__channel">
                  <span class="yt-detail__channel-avatar">{{ store.videoDetail.snippet.channelTitle.charAt(0).toUpperCase() }}</span>
                  {{ store.videoDetail.snippet.channelTitle }}
                </span>
                <span class="yt-detail__sep">•</span>
                <span>{{ formatViews(store.videoDetail.statistics.viewCount) }} visualizações</span>
                <span class="yt-detail__sep">•</span>
                <span>{{ formatDuration(store.videoDetail.contentDetails.duration) }}</span>
                <span class="yt-detail__sep">•</span>
                <span>{{ formatDate(store.videoDetail.snippet.publishedAt) }}</span>
              </div>

              <div class="yt-detail__stats">
                <span class="yt-detail__stat">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  {{ formatViews(store.videoDetail.statistics.viewCount) }}
                </span>
                <span class="yt-detail__stat">
                  <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                  {{ (store.videoDetail.statistics as Record<string, string>).commentCount || '0' }}
                </span>
              </div>

              <p class="yt-detail__desc" :class="{ 'yt-detail__desc--expanded': descExpanded }">
                {{ store.videoDetail.snippet.description }}
              </p>
              <button
                v-if="store.videoDetail.snippet.description.length > 200"
                class="yt-detail__toggle"
                @click="descExpanded = !descExpanded"
              >
                {{ descExpanded ? 'Mostrar menos' : 'Mostrar mais' }}
              </button>

              <a
                :href="`https://www.youtube.com/watch?v=${store.selectedVideoId}`"
                target="_blank"
                rel="noopener noreferrer"
                class="yt-detail__ext-link"
              >
                <svg viewBox="0 0 24 24" width="14" height="14" fill="currentColor"><path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0C.488 3.45.029 5.804 0 12c.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0C23.512 20.55 23.971 18.196 24 12c-.029-6.185-.484-8.549-4.385-8.816zM9 16V8l8 4-8 4z"/></svg>
                Assistir no YouTube
              </a>
            </div>
          </Transition>
        </div>
      </Transition>

      <!-- Toggle lista de resultados -->
      <div v-if="store.results.length > 0" class="yt-toggle-wrap">
        <button class="yt-toggle-btn" @click="resultsVisible = !resultsVisible">
          <svg
            viewBox="0 0 24 24"
            width="16"
            height="16"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            :style="{ transform: resultsVisible ? 'rotate(0deg)' : 'rotate(-90deg)' }"
          >
            <polyline points="6 9 12 15 18 9" />
          </svg>
          {{ resultsVisible ? 'Esconder lista' : 'Mostrar lista' }}
          <span class="yt-toggle-count">{{ store.totalResults.toLocaleString('pt-BR') }} vídeos</span>
        </button>
      </div>

      <!-- Grid de resultados -->
      <div v-show="resultsVisible" v-if="store.results.length > 0" class="yt-section">
        <div class="yt-section__header">
          <p class="yt-section__count">{{ store.totalResults.toLocaleString('pt-BR') }} vídeos</p>
        </div>

        <TransitionGroup name="yt-card" tag="div" class="yt-grid">
          <article
            v-for="item in store.results"
            :key="item.id.videoId"
            class="yt-card"
            :class="{ 'yt-card--active': store.selectedVideoId === item.id.videoId }"
            tabindex="0"
            role="button"
            :aria-label="`Assistir ${item.snippet.title}`"
            @click="selectVideo(item.id.videoId)"
            @keydown.enter="selectVideo(item.id.videoId)"
            @keydown.space.prevent="selectVideo(item.id.videoId)"
          >
            <div class="yt-card__thumb">
              <img
                :src="item.snippet.thumbnails.medium.url"
                :alt="item.snippet.title"
                loading="lazy"
                class="yt-card__img"
              />
              <div class="yt-card__overlay" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="48" height="48">
                  <path fill="currentColor" d="M8 5v14l11-7z"/>
                </svg>
              </div>
            </div>
            <div class="yt-card__body">
              <div class="yt-card__avatar">{{ item.snippet.channelTitle.charAt(0).toUpperCase() }}</div>
              <div class="yt-card__info">
                <h3 class="yt-card__title">{{ item.snippet.title }}</h3>
                <p class="yt-card__channel">{{ item.snippet.channelTitle }}</p>
                <p class="yt-card__date">{{ formatDate(item.snippet.publishedAt) }}</p>
              </div>
            </div>
          </article>
        </TransitionGroup>

        <!-- Paginação -->
        <div class="yt-pagination">
          <button
            class="yt-pagination__btn"
            :disabled="!store.prevPageToken"
            @click="prevPage"
          >
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
            Anterior
          </button>
          <button
            class="yt-pagination__btn"
            :disabled="!store.nextPageToken"
            @click="nextPage"
          >
            Próxima
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
          </button>
        </div>
      </div>

      <!-- Empty / Initial -->
      <div v-else-if="!store.loading" class="yt-state">
        <div class="yt-state__icon">
          <svg v-if="query.trim()" viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
            <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
          </svg>
          <svg v-else viewBox="0 0 24 24" width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M10 8.5v7l6-3.5-6-3.5z"/><circle cx="12" cy="12" r="10"/>
          </svg>
        </div>
        <p v-if="query.trim()" class="yt-state__text">
          Nenhum vídeo encontrado para "{{ query }}".
        </p>
        <p v-if="query.trim()" class="yt-state__sub">Tente outro termo de busca.</p>
        <p v-else class="yt-state__text">
          Pesquise por tutoriais e aulas para complementar seus estudos.
        </p>
      </div>
    </template>
  </div>
  </PageView>
</template>

<style scoped>
/* ── Page ── */
.yt-page {
  padding: 0 0 var(--spacing-2xl);
}

/* ── Hero ── */
.yt-hero {
  position: relative;
  text-align: center;
  padding: var(--spacing-2xl) var(--spacing-md) var(--spacing-xl);
  margin: 0 calc(-1 * var(--spacing-md)) var(--spacing-xl);
  overflow: hidden;
}
.yt-hero__bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, color-mix(in srgb, var(--color-primary) 8%, transparent), color-mix(in srgb, var(--color-primary) 2%, transparent) 60%);
  border-bottom: 1px solid color-mix(in srgb, var(--color-border) 60%, transparent);
  pointer-events: none;
}
.yt-hero__title {
  position: relative;
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 800;
  margin: 0 0 var(--spacing-xs);
  color: var(--color-text);
  letter-spacing: -0.02em;
}
.yt-hero__subtitle {
  position: relative;
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-lg);
  font-size: var(--text-sm);
  max-width: 480px;
  margin-left: auto;
  margin-right: auto;
}

/* ── Search ── */
.yt-search__wrap {
  position: relative;
  display: flex;
  max-width: 560px;
  margin: 0 auto;
  background: var(--color-bg-card);
  border: 2px solid var(--color-border);
  border-radius: var(--radius-xl);
  transition: border-color var(--duration-fast) ease, box-shadow var(--duration-fast) ease;
  overflow: hidden;
}
.yt-search__wrap:focus-within {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 4px color-mix(in srgb, var(--color-primary) 15%, transparent);
}
.yt-search__icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  pointer-events: none;
  flex-shrink: 0;
}
.yt-search__input {
  flex: 1;
  padding: 12px 14px 12px 44px;
  border: none;
  background: transparent;
  color: var(--color-text);
  font-size: var(--text-sm);
  outline: none;
  min-width: 0;
}
.yt-search__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 12px 24px;
  border: none;
  background: var(--color-primary);
  color: var(--color-primary-contrast, #fff);
  font-weight: 600;
  font-size: var(--text-sm);
  cursor: pointer;
  transition: background var(--duration-fast) ease, opacity var(--duration-fast) ease;
  white-space: nowrap;
  flex-shrink: 0;
}
.yt-search__btn:hover:not(:disabled) { background: var(--color-primary-hover); }
.yt-search__btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* ── Spinner ── */
.yt-spinner {
  width: 32px; height: 32px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
.yt-spinner-sm { animation: spin 0.7s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) {
  .yt-spinner, .yt-spinner-sm { animation: none; }
}

/* ── Skeletons ── */
.yt-skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--spacing-md);
}
.yt-skeleton-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  overflow: hidden;
}
.yt-skeleton-thumb {
  aspect-ratio: 16 / 9;
  background: var(--color-bg-soft);
  animation: shimmer 1.5s ease-in-out infinite;
}
.yt-skeleton-body {
  padding: var(--spacing-sm) var(--spacing-md) var(--spacing-md);
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.yt-skeleton-line {
  height: 12px;
  border-radius: 6px;
  background: var(--color-bg-soft);
  animation: shimmer 1.5s ease-in-out infinite;
}
.yt-skeleton-line.w-75 { width: 75%; }
.yt-skeleton-line.w-50 { width: 50%; }
.yt-skeleton-line.w-33 { width: 33%; }
@keyframes shimmer {
  0% { opacity: 0.6; }
  50% { opacity: 1; }
  100% { opacity: 0.6; }
}

/* ── State ── */
.yt-state {
  text-align: center;
  padding: var(--spacing-3xl) var(--spacing-md);
}
.yt-state__icon {
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-md);
  opacity: 0.5;
}
.yt-state__text {
  color: var(--color-text-muted);
  margin: 0 0 var(--spacing-xs);
  font-size: var(--text-sm);
}
.yt-state__sub {
  color: var(--color-text-muted);
  margin: 0;
  font-size: var(--text-xs);
}
.yt-btn {
  margin-top: var(--spacing-md);
  padding: var(--spacing-xs) var(--spacing-lg);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
  cursor: pointer;
  font-size: var(--text-sm);
  transition: border-color var(--duration-fast) ease, background var(--duration-fast) ease;
}
.yt-btn:hover { border-color: var(--color-primary); background: var(--color-bg-soft); }

/* ── Player ── */
.yt-player-section {
  margin-bottom: var(--spacing-xl);
  background: var(--color-bg-card);
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  overflow: hidden;
  box-shadow: var(--shadow-md);
}
.yt-player {
  position: relative;
  padding-bottom: 56.25%;
  background: var(--color-bg);
}
.yt-player__iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
.yt-player__loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--color-bg) 60%, transparent);
}

/* ── Detail ── */
.yt-detail {
  padding: var(--spacing-lg);
}
.yt-detail__title {
  font-size: var(--text-lg);
  font-weight: 600;
  margin: 0 0 var(--spacing-sm);
  color: var(--color-text);
  line-height: var(--leading-relaxed);
}
.yt-detail__meta {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  flex-wrap: wrap;
  margin-bottom: var(--spacing-sm);
}
.yt-detail__channel {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 500;
}
.yt-detail__channel-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
  color: var(--color-primary-contrast, #fff);
  font-size: 10px;
  font-weight: 700;
  flex-shrink: 0;
}
.yt-detail__sep { opacity: 0.4; }
.yt-detail__stats {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
}
.yt-detail__stat {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  padding: 3px 10px;
  border-radius: var(--radius-full);
  background: var(--color-bg-soft);
}
.yt-detail__desc {
  font-size: var(--text-sm);
  color: var(--color-text-secondary);
  line-height: var(--leading-relaxed);
  white-space: pre-wrap;
  max-height: 80px;
  overflow: hidden;
  transition: max-height 0.3s ease;
  margin: 0;
}
.yt-detail__desc--expanded { max-height: 2000px; }
.yt-detail__toggle {
  display: inline-block;
  margin-top: var(--spacing-xs);
  padding: 0;
  border: none;
  background: none;
  color: var(--color-primary);
  font-size: var(--text-xs);
  font-weight: 500;
  cursor: pointer;
}
.yt-detail__toggle:hover { text-decoration: underline; }
.yt-detail__ext-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: var(--spacing-md);
  padding: 8px 16px;
  border-radius: var(--radius-md);
  background: #ff0000;
  color: #fff;
  font-size: var(--text-xs);
  font-weight: 600;
  text-decoration: none;
  transition: opacity var(--duration-fast) ease, transform var(--duration-fast) ease;
}
.yt-detail__ext-link:hover { opacity: 0.9; transform: translateY(-1px); }

/* ── Section ── */
.yt-section {
  margin-top: var(--spacing-lg);
}
.yt-section__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--spacing-md);
}

/* ── Toggle lista ── */
.yt-toggle-wrap {
  margin: var(--spacing-lg) 0 var(--spacing-md);
}
.yt-toggle-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-size: var(--text-xs);
  font-weight: 500;
  cursor: pointer;
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.yt-toggle-btn:hover {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
}
.yt-toggle-btn svg {
  transition: transform 0.2s ease;
}
.yt-toggle-count {
  color: var(--color-text-muted);
  font-weight: 400;
}
.yt-toggle-count::before {
  content: "—";
  margin-right: 4px;
  opacity: 0.4;
}

/* ── Grid ── */
.yt-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: var(--spacing-md);
  scrollbar-width: none;
  -ms-overflow-style: none;
}
.yt-grid::-webkit-scrollbar { display: none; }
.yt-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
  overflow: hidden;
  cursor: pointer;
  transition: transform var(--duration-fast) ease, box-shadow var(--duration-fast) ease, border-color var(--duration-fast) ease;
}
.yt-card:hover,
.yt-card:focus-visible {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
  border-color: var(--color-primary);
}
.yt-card--active {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--color-primary) 30%, transparent);
}
.yt-card__thumb {
  position: relative;
  aspect-ratio: 16 / 9;
  background: var(--color-bg-soft);
  overflow: hidden;
}
.yt-card__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.3s ease;
}
.yt-card:hover .yt-card__img { transform: scale(1.05); }
.yt-card__overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--color-bg) 30%, transparent);
  color: #fff;
  opacity: 0;
  transition: opacity var(--duration-fast) ease;
}
.yt-card:hover .yt-card__overlay { opacity: 1; }
.yt-card__body {
  display: flex;
  gap: var(--spacing-sm);
  padding: var(--spacing-sm) var(--spacing-md) var(--spacing-md);
}
.yt-card__avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--color-primary), var(--color-primary-hover));
  color: var(--color-primary-contrast, #fff);
  font-size: 12px;
  font-weight: 700;
  flex-shrink: 0;
  margin-top: 2px;
}
.yt-card__info { flex: 1; min-width: 0; }
.yt-card__title {
  font-size: var(--text-sm);
  font-weight: 600;
  margin: 0 0 2px;
  color: var(--color-text);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  line-height: var(--leading-relaxed);
}
.yt-card__channel,
.yt-card__date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
  line-height: var(--leading-relaxed);
}

/* ── Pagination ── */
.yt-pagination {
  display: flex;
  justify-content: center;
  gap: var(--spacing-md);
  margin-top: var(--spacing-xl);
}
.yt-pagination__btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: var(--spacing-sm) var(--spacing-xl);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-weight: 500;
  font-size: var(--text-sm);
  cursor: pointer;
  transition: background var(--duration-fast) ease, border-color var(--duration-fast) ease, transform var(--duration-fast) ease;
}
.yt-pagination__btn:hover:not(:disabled) {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
  transform: translateY(-1px);
}
.yt-pagination__btn:disabled { opacity: 0.3; cursor: not-allowed; transform: none; }

/* ── Transitions ── */
.yt-fade-enter-active,
.yt-fade-leave-active { transition: opacity 0.25s ease; }
.yt-fade-enter-from,
.yt-fade-leave-to { opacity: 0; }

.yt-slide-enter-active { transition: all 0.3s ease-out; }
.yt-slide-leave-active { transition: all 0.2s ease-in; }
.yt-slide-enter-from { opacity: 0; transform: translateY(-12px); }
.yt-slide-leave-to { opacity: 0; transform: translateY(-8px); }

.yt-card-enter-active,
.yt-card-leave-active { transition: all 0.3s ease; }
.yt-card-enter-from { opacity: 0; transform: scale(0.95); }
.yt-card-leave-to { opacity: 0; transform: scale(0.95); }
.yt-card-move { transition: transform 0.3s ease; }

/* ── Responsive ── */
@media (max-width: 640px) {
  .yt-page {
    padding: 0 var(--spacing-sm) var(--spacing-2xl);
  }
  .yt-hero {
    padding: var(--spacing-xl) var(--spacing-sm) var(--spacing-lg);
    margin: 0 calc(-1 * var(--spacing-sm)) var(--spacing-lg);
  }
  .yt-hero__title { font-size: var(--text-xl); }
  .yt-hero__subtitle { font-size: var(--text-xs); }
  .yt-search__wrap { border-radius: var(--radius-lg); }
  .yt-search__input { padding: 10px 12px 10px 40px; font-size: var(--text-xs); }
  .yt-search__btn { padding: 10px 16px; font-size: var(--text-xs); }
  .yt-grid { grid-template-columns: 1fr; }
  .yt-card__title { font-size: var(--text-xs); -webkit-line-clamp: 1; }
  .yt-player-section { border-radius: var(--radius-lg); }
  .yt-detail { padding: var(--spacing-md); }
  .yt-detail__title { font-size: var(--text-base); }
  .yt-pagination { gap: var(--spacing-sm); }
  .yt-pagination__btn { padding: var(--spacing-sm) var(--spacing-md); font-size: var(--text-xs); }
  .yt-detail__ext-link { width: 100%; justify-content: center; }
}
</style>
