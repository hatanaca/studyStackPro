<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useYouTubeStore } from '@/stores/youtube.store'

const store = useYouTubeStore()
const query = ref('')
const playerReady = ref(false)

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
  playerReady.value = false
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

function onPlayerReady() {
  playerReady.value = true
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
  <div class="youtube-page">
    <header class="youtube-page__header">
      <h1 class="youtube-page__title">Vídeos de Estudo</h1>
      <p class="youtube-page__subtitle">
        Busque tutoriais e aulas no YouTube para complementar seus estudos.
      </p>
    </header>

    <!-- Barra de busca -->
    <form class="youtube-search" @submit.prevent="onSearch">
      <div class="youtube-search__input-wrap">
        <svg class="youtube-search__icon" viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
          <path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0 0 16 9.5 6.5 6.5 0 1 0 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
        </svg>
        <input
          v-model="query"
          type="text"
          class="youtube-search__input"
          placeholder="Buscar vídeos... (ex: React hooks tutorial)"
          @input="onInput"
        />
      </div>
      <button
        type="submit"
        class="youtube-search__btn"
        :disabled="store.loading || query.trim().length < 2"
      >
        Buscar
      </button>
    </form>

    <!-- Loading -->
    <div v-if="store.loading" class="youtube-page__loading">
      <div class="youtube-page__spinner" aria-hidden="true" />
      <p>Buscando vídeos...</p>
    </div>

    <!-- Erro -->
    <div v-else-if="store.error" class="youtube-page__error">
      <p>{{ store.error }}</p>
      <button class="youtube-page__retry" @click="onSearch">Tentar novamente</button>
    </div>

    <!-- Conteúdo -->
    <template v-else>
      <!-- Player embed + detalhes -->
      <div v-if="store.selectedVideoId" class="youtube-player-section">
        <div class="youtube-player">
          <iframe
            v-if="store.selectedVideoId"
            :src="`https://www.youtube.com/embed/${store.selectedVideoId}?autoplay=1&modestbranding=1&rel=0`"
            title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
            class="youtube-player__iframe"
            @load="onPlayerReady"
          />
          <div v-if="store.videoLoading" class="youtube-player__loading">
            <div class="youtube-page__spinner" aria-hidden="true" />
          </div>
        </div>

        <!-- Detalhes do vídeo -->
        <div v-if="store.videoDetail" class="youtube-video-info">
          <h2 class="youtube-video-info__title">{{ store.videoDetail.snippet.title }}</h2>
          <div class="youtube-video-info__meta">
            <span>{{ store.videoDetail.snippet.channelTitle }}</span>
            <span>·</span>
            <span>{{ formatViews(store.videoDetail.statistics.viewCount) }} visualizações</span>
            <span>·</span>
            <span>{{ formatDuration(store.videoDetail.contentDetails.duration) }}</span>
            <span>·</span>
            <span>{{ formatDate(store.videoDetail.snippet.publishedAt) }}</span>
          </div>
          <p class="youtube-video-info__desc">{{ store.videoDetail.snippet.description }}</p>

          <a
            :href="`https://www.youtube.com/watch?v=${store.selectedVideoId}`"
            target="_blank"
            rel="noopener noreferrer"
            class="youtube-video-info__link"
          >
            Abrir no YouTube ↗
          </a>
        </div>
      </div>

      <!-- Grid de resultados -->
      <div v-if="store.results.length > 0" class="youtube-results">
        <p class="youtube-results__count">
          {{ store.totalResults.toLocaleString('pt-BR') }} vídeos encontrados
        </p>

        <div class="youtube-grid">
          <article
            v-for="item in store.results"
            :key="item.id.videoId"
            class="youtube-card"
            :class="{ 'youtube-card--active': store.selectedVideoId === item.id.videoId }"
            tabindex="0"
            role="button"
            :aria-label="`Assistir ${item.snippet.title}`"
            @click="selectVideo(item.id.videoId)"
            @keydown.enter="selectVideo(item.id.videoId)"
            @keydown.space.prevent="selectVideo(item.id.videoId)"
          >
            <div class="youtube-card__thumb">
              <img
                :src="item.snippet.thumbnails.medium.url"
                :alt="item.snippet.title"
                loading="lazy"
                class="youtube-card__img"
              />
              <div class="youtube-card__play" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="36" height="36">
                  <path fill="white" d="M8 5v14l11-7z"/>
                </svg>
              </div>
            </div>
            <div class="youtube-card__body">
              <h3 class="youtube-card__title">{{ item.snippet.title }}</h3>
              <p class="youtube-card__channel">{{ item.snippet.channelTitle }}</p>
              <p class="youtube-card__date">{{ formatDate(item.snippet.publishedAt) }}</p>
            </div>
          </article>
        </div>

        <!-- Paginação -->
        <div class="youtube-pagination">
          <button
            class="youtube-pagination__btn"
            :disabled="!store.prevPageToken"
            @click="prevPage"
          >
            ← Anterior
          </button>
          <button
            class="youtube-pagination__btn"
            :disabled="!store.nextPageToken"
            @click="nextPage"
          >
            Próxima →
          </button>
        </div>
      </div>

      <!-- Estado vazio -->
      <div v-else-if="query.trim() && !store.loading" class="youtube-page__empty">
        <p>Nenhum vídeo encontrado para "{{ query }}".</p>
        <p>Tente outro termo de busca.</p>
      </div>

      <!-- Estado inicial -->
      <div v-else class="youtube-page__empty">
        <p>Pesquise por tutoriais e aulas para complementar seus estudos.</p>
      </div>
    </template>
  </div>
</template>

<style scoped>
.youtube-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: var(--spacing-lg) var(--spacing-md);
}
.youtube-page__header {
  margin-bottom: var(--spacing-xl);
}
.youtube-page__title {
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 700;
  margin: 0 0 var(--spacing-xs);
  color: var(--color-text);
}
.youtube-page__subtitle {
  color: var(--color-text-muted);
  margin: 0;
  font-size: var(--text-sm);
}

/* Search */
.youtube-search {
  display: flex;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-xl);
}
.youtube-search__input-wrap {
  flex: 1;
  position: relative;
}
.youtube-search__icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--color-text-muted);
  pointer-events: none;
}
.youtube-search__input {
  width: 100%;
  padding: var(--spacing-sm) var(--spacing-md) var(--spacing-sm) 40px;
  border-radius: var(--radius-lg);
  border: 2px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-size: var(--text-sm);
  transition: border-color var(--duration-fast) ease;
  box-sizing: border-box;
}
.youtube-search__input:focus {
  outline: none;
  border-color: var(--color-primary);
  box-shadow: var(--shadow-focus);
}
.youtube-search__btn {
  padding: var(--spacing-sm) var(--spacing-xl);
  border-radius: var(--radius-lg);
  border: none;
  background: var(--color-primary);
  color: var(--color-primary-contrast, #fff);
  font-weight: 600;
  cursor: pointer;
  transition: background var(--duration-fast) ease;
  white-space: nowrap;
}
.youtube-search__btn:hover:not(:disabled) { background: var(--color-primary-hover); }
.youtube-search__btn:disabled { opacity: 0.5; cursor: not-allowed; }

/* Loading / Error / Empty */
.youtube-page__loading,
.youtube-page__error,
.youtube-page__empty {
  text-align: center;
  padding: var(--spacing-3xl) var(--spacing-md);
  color: var(--color-text-muted);
}
.youtube-page__spinner {
  width: 36px; height: 36px;
  border: 3px solid var(--color-border);
  border-top-color: var(--color-primary);
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto var(--spacing-md);
}
@keyframes spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) {
  .youtube-page__spinner { animation: none; }
}
.youtube-page__retry {
  margin-top: var(--spacing-md);
  padding: var(--spacing-xs) var(--spacing-lg);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
  cursor: pointer;
}

/* Player */
.youtube-player-section {
  margin-bottom: var(--spacing-xl);
  background: var(--color-bg-card);
  border-radius: var(--radius-xl);
  border: 1px solid var(--color-border);
  overflow: hidden;
}
.youtube-player {
  position: relative;
  padding-bottom: 56.25%;
  background: var(--color-bg);
}
.youtube-player__iframe {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
.youtube-player__loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--color-bg) 60%, transparent);
}
.youtube-video-info {
  padding: var(--spacing-lg);
}
.youtube-video-info__title {
  font-size: var(--text-lg);
  font-weight: 600;
  margin: 0 0 var(--spacing-sm);
  color: var(--color-text);
}
.youtube-video-info__meta {
  display: flex;
  gap: var(--spacing-sm);
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  flex-wrap: wrap;
  margin-bottom: var(--spacing-md);
}
.youtube-video-info__desc {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  line-height: var(--leading-relaxed);
  white-space: pre-wrap;
  max-height: 120px;
  overflow-y: auto;
}
.youtube-video-info__link {
  display: inline-block;
  margin-top: var(--spacing-md);
  color: var(--color-primary);
  font-weight: 500;
  font-size: var(--text-sm);
  text-decoration: none;
}
.youtube-video-info__link:hover { text-decoration: underline; }

/* Results */
.youtube-results__count {
  font-size: var(--text-sm);
  color: var(--color-text-muted);
  margin-bottom: var(--spacing-md);
}
.youtube-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: var(--spacing-md);
}
.youtube-card {
  background: var(--color-bg-card);
  border-radius: var(--radius-lg);
  border: 2px solid var(--color-border);
  overflow: hidden;
  cursor: pointer;
  transition:
    border-color var(--duration-fast) ease,
    transform var(--duration-fast) ease,
    box-shadow var(--duration-fast) ease;
}
.youtube-card:hover,
.youtube-card:focus-visible {
  border-color: var(--color-primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}
.youtube-card--active {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb, 99 102 241), 0.3);
}
.youtube-card__thumb {
  position: relative;
  aspect-ratio: 16 / 9;
  background: var(--color-bg-soft);
  overflow: hidden;
}
.youtube-card__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.youtube-card__play {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  background: color-mix(in srgb, var(--color-bg) 30%, transparent);
  opacity: 0;
  transition: opacity var(--duration-fast) ease;
}
.youtube-card:hover .youtube-card__play { opacity: 1; }
.youtube-card__body { padding: var(--spacing-sm) var(--spacing-md) var(--spacing-md); }
.youtube-card__title {
  font-size: var(--text-sm);
  font-weight: 600;
  margin: 0 0 var(--spacing-xs);
  color: var(--color-text);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.youtube-card__channel,
.youtube-card__date {
  font-size: var(--text-xs);
  color: var(--color-text-muted);
  margin: 0;
}

/* Pagination */
.youtube-pagination {
  display: flex;
  justify-content: center;
  gap: var(--spacing-md);
  margin-top: var(--spacing-xl);
}
.youtube-pagination__btn {
  padding: var(--spacing-sm) var(--spacing-xl);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-bg-card);
  color: var(--color-text);
  font-weight: 500;
  cursor: pointer;
  transition:
    background var(--duration-fast) ease,
    border-color var(--duration-fast) ease;
}
.youtube-pagination__btn:hover:not(:disabled) {
  background: var(--color-bg-soft);
  border-color: var(--color-primary);
}
.youtube-pagination__btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .youtube-page {
    padding: var(--spacing-md) var(--spacing-sm);
    padding-bottom: calc(56.25vw + var(--spacing-md));
  }
  .youtube-page__title {
    font-size: var(--text-xl);
  }
  .youtube-page__header {
    margin-bottom: var(--spacing-md);
  }
  .youtube-search {
    flex-direction: column;
    gap: var(--spacing-xs);
  }
  .youtube-search__btn {
    width: 100%;
  }
  .youtube-player-section {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    margin: 0;
    border-radius: 0;
    border: none;
    border-top: 1px solid var(--color-border);
    background: var(--color-bg-card);
  }
  .youtube-player {
    padding-bottom: 56.25%;
  }
  .youtube-video-info {
    display: none;
  }
  .youtube-grid {
    grid-template-columns: 1fr;
    gap: var(--spacing-sm);
  }
  .youtube-card__body {
    padding: var(--spacing-xs) var(--spacing-sm) var(--spacing-sm);
  }
  .youtube-card__title {
    font-size: var(--text-xs);
    -webkit-line-clamp: 1;
  }
}
</style>
