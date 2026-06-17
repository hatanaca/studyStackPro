<script setup lang="ts">
import { computed, ref, reactive, onMounted, watch } from 'vue'
import { usePlayerStore } from '@/stores/player.store'
import { useAuthStore } from '@/stores/auth.store'
import YouTubeFrame from '@/components/player/YouTubeFrame.vue'

const player = usePlayerStore()
const auth = useAuthStore()

const hasGoogleAccount = computed(() => !!auth.user?.google_id)
const searchInput = ref('')
const showVolume = ref(false)
const seekPercent = ref(-1)
const isSeeking = ref(false)

const playlistTitle = computed(() => player.selectedPlaylist?.snippet?.title ?? 'Selecionar playlist')

// --- Drag ---
const STORAGE_POS_KEY = 'studytrack_miniplayer_pos'
const pos = reactive({ x: 0, y: 0 })
let dragging = false, dragStartX = 0, dragStartY = 0, posStartX = 0, posStartY = 0
const containerRef = ref<HTMLElement | null>(null)

function clampPos() {
  const w = window.innerWidth, h = window.innerHeight
  const pw = containerRef.value?.offsetWidth ?? 280, ph = containerRef.value?.offsetHeight ?? 48
  pos.x = Math.max(0, Math.min(pos.x, w - pw)); pos.y = Math.max(0, Math.min(pos.y, h - ph))
}
function loadPos() {
  try { const r = localStorage.getItem(STORAGE_POS_KEY); pos.x = r ? JSON.parse(r).x ?? 16 : window.innerWidth - 300; pos.y = r ? JSON.parse(r).y ?? window.innerHeight - 250 : window.innerHeight - 250 } catch { pos.x = window.innerWidth - 300; pos.y = window.innerHeight - 250 }
}
function savePos() { try { localStorage.setItem(STORAGE_POS_KEY, JSON.stringify({ x: pos.x, y: pos.y })) } catch {} }
loadPos(); clampPos()

function onDragStart(e: MouseEvent | TouchEvent) {
  if ((e.target as HTMLElement).closest('button, input, select, a')) return
  dragging = true; const p = 'touches' in e ? e.touches[0] : e
  dragStartX = p.clientX; dragStartY = p.clientY; posStartX = pos.x; posStartY = pos.y
  document.addEventListener('mousemove', onDragMove); document.addEventListener('mouseup', onDragEnd)
  document.addEventListener('touchmove', onDragMove, { passive: false }); document.addEventListener('touchend', onDragEnd)
}
function onDragMove(e: MouseEvent | TouchEvent) {
  if (!dragging) return; e.preventDefault()
  const p = 'touches' in e ? e.touches[0] : e
  pos.x = posStartX + (p.clientX - dragStartX); pos.y = posStartY + (p.clientY - dragStartY); clampPos()
}
function onDragEnd() {
  dragging = false; savePos()
  document.removeEventListener('mousemove', onDragMove); document.removeEventListener('mouseup', onDragEnd)
  document.removeEventListener('touchmove', onDragMove); document.removeEventListener('touchend', onDragEnd)
}

onMounted(() => {
  if (hasGoogleAccount.value) player.fetchPlaylists()
})
watch(hasGoogleAccount, (v) => { if (v) player.fetchPlaylists() })

function handleSearch() { if (searchInput.value.trim()) { player.searchVideos(searchInput.value); player.switchMode('search') } }
function onTimeUpdate(time: number, dur: number) {
  if (!isSeeking.value) { player.currentTime = time; player.duration = dur }
}
function onSeekInput(e: Event) {
  const v = Number((e.target as HTMLInputElement).value)
  isSeeking.value = true
  player.currentTime = (v / 100) * player.duration
}
function onSeekChange(e: Event) {
  const v = Number((e.target as HTMLInputElement).value)
  player.currentTime = (v / 100) * player.duration
  seekPercent.value = v
  isSeeking.value = false
  // Reset após seek para não re-enviar
  setTimeout(() => { seekPercent.value = -1 }, 500)
}

function onPlayerStateChange(s: number) {
  if (s === 1) player.isPlaying = true
  else if (s === 2) player.isPlaying = false
  // Ignora -1 (unstarted), 3 (buffering), 5 (cued)
}

function formatTime(sec: number) {
  if (!isFinite(sec) || sec < 0) return '0:00'
  const m = Math.floor(sec / 60), s = Math.floor(sec % 60)
  return `${m}:${s.toString().padStart(2, '0')}`
}

</script>

<template>
  <aside ref="containerRef" class="mini-player" :class="{ 'mini-player--expanded': player.isExpanded, 'mini-player--dragging': dragging }" :style="player.isExpanded ? { left: pos.x + 'px', top: pos.y + 'px' } : undefined">
    <!-- Iframe invisível -->
    <div class="yt-player-wrapper">
    <YouTubeFrame
      v-if="player.currentPlaylistId || player.currentVideoId"
      :playlist-id="player.currentPlaylistId" :video-id="player.currentVideoId"
      :video-index="player.videoIndex" :is-playing="player.isPlaying"
      :is-shuffled="player.isShuffled" :repeat-mode="player.repeatMode"
      :volume="player.volume" :seek-percent="seekPercent"
      @state-change="onPlayerStateChange"
      @ended="player.nextVideo()"
      @time-update="onTimeUpdate"
    />
    </div>

    <!-- Barra colapsada -->
    <div v-if="!player.isExpanded" class="mini-player__collapse-bar">
      <div v-if="player.currentTrack?.thumbnail" class="mini-player__mini-thumb" :style="{ backgroundImage: `url(${player.currentTrack.thumbnail})` }" />
      <svg v-else class="mini-player__drag-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><circle cx="8" cy="6" r="2"/><circle cx="16" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="12" r="2"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/></svg>
      <span class="mini-player__title-label" @click="player.toggleExpand()">{{ player.currentTrack?.title || playlistTitle }}</span>
      <span v-if="player.isPlaying && !player.currentTrack" class="mini-player__playing-dot" />
      <div v-if="player.hasContent" class="mini-player__collapse-controls">
        <button class="mini-player__btn-icon" aria-label="Anterior" @click.stop="player.prevVideo()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="19 20 9 12 19 4 19 20"/><line x1="5" y1="19" x2="5" y2="5"/></svg>
        </button>
        <button class="mini-player__btn-icon" aria-label="Play/Pause" @click.stop="player.togglePlay()">
          <svg v-if="!player.isPlaying" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
        </button>
        <button class="mini-player__btn-icon" aria-label="Próximo" @click.stop="player.nextVideo()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
        </button>
      </div>
      <button class="mini-player__btn-icon" aria-label="Expandir" title="Expandir" @click.stop="player.toggleExpand()">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"/></svg>
      </button>
    </div>

    <!-- Painel expandido -->
    <div v-else class="mini-player__panel">
      <div class="mini-player__handle" @mousedown="onDragStart" @touchstart="onDragStart">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="currentColor" class="mini-player__drag-icon"><circle cx="8" cy="6" r="2"/><circle cx="16" cy="6" r="2"/><circle cx="8" cy="12" r="2"/><circle cx="16" cy="12" r="2"/><circle cx="8" cy="18" r="2"/><circle cx="16" cy="18" r="2"/></svg>
        <span class="mini-player__header-title">{{ player.mode === 'search' ? 'Busca' : player.mode === 'favorites' ? 'Favoritos' : 'Playlists' }}</span>
        <button v-if="player.mode === 'playlists' && player.selectedPlaylist" class="mini-player__btn-icon" :title="player.isFavorite(player.selectedPlaylist.id) ? 'Salvo' : 'Salvar playlist'" @click.stop="player.addToFavorites()">
          <svg v-if="player.isFavorite(player.selectedPlaylist.id)" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="var(--color-primary)" stroke="none"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </button>
        <button class="mini-player__btn-icon" aria-label="Fechar" @click.stop="player.toggleExpand()">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/></svg>
        </button>
      </div>

      <!-- Capa do álbum -->
      <div class="mini-player__album">
        <div v-if="player.currentTrack?.thumbnail" class="mini-player__album-art" :class="{ 'mini-player__album-art--playing': player.isPlaying }" :style="{ backgroundImage: `url(${player.currentTrack.thumbnail})` }" />
        <div v-else class="mini-player__album-placeholder">
          <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><path d="M10 8.5v7l6-3.5-6-3.5z"/></svg>
        </div>
      </div>

      <!-- Info da faixa -->
      <div v-if="player.currentTrack" class="mini-player__track-info">
        <p class="mini-player__track-title">{{ player.currentTrack.title }}</p>
        <p v-if="player.currentTrack.artist" class="mini-player__track-artist">{{ player.currentTrack.artist }}</p>
      </div>

      <!-- Barra de progresso -->
      <div v-if="player.hasContent && player.duration > 0" class="mini-player__progress-wrap">
        <span class="mini-player__time">{{ formatTime(player.currentTime) }}</span>
        <input type="range" min="0" max="100" step="0.1" :value="player.progress" class="mini-player__progress" @input="onSeekInput" @change="onSeekChange" @mousedown.stop />
        <span class="mini-player__time">{{ formatTime(player.duration) }}</span>
      </div>

      <!-- Controles principais -->
      <div v-if="player.hasContent" class="mini-player__controls">
        <!-- Shuffle -->
        <button class="mini-player__ctrl-btn" :class="{ 'mini-player__ctrl-btn--active': player.isShuffled }" aria-label="Aleatório" @click="player.toggleShuffle()">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
        </button>
        <!-- Anterior -->
        <button class="mini-player__btn-icon" aria-label="Anterior" @click="player.prevVideo()">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="19 20 9 12 19 4 19 20"/><line x1="5" y1="19" x2="5" y2="5"/></svg>
        </button>
        <!-- Play/Pause -->
        <button class="mini-player__btn-play" aria-label="Play/Pause" @click="player.togglePlay()">
          <svg v-if="!player.isPlaying" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" stroke="none"><rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/></svg>
        </button>
        <!-- Próximo -->
        <button class="mini-player__btn-icon" aria-label="Próximo" @click="player.nextVideo()">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="5 4 15 12 5 20 5 4"/><line x1="19" y1="5" x2="19" y2="19"/></svg>
        </button>
        <!-- Repeat -->
        <button class="mini-player__ctrl-btn" :class="{ 'mini-player__ctrl-btn--active': player.repeatMode !== 'none' }" aria-label="Repetir" @click="player.cycleRepeat()">
          <svg v-if="player.repeatMode !== 'single'" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/></svg>
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 014-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 01-4 4H3"/><text x="11" y="16" font-size="8" font-weight="bold" fill="currentColor" stroke="none">1</text></svg>
        </button>
        <!-- Volume -->
        <div class="mini-player__volume-wrap">
          <button class="mini-player__ctrl-btn" aria-label="Volume" @click="showVolume = !showVolume">
            <svg v-if="player.volume === 0" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
            <svg v-else-if="player.volume < 50" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 010 7.07"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M19.07 4.93a10 10 0 010 14.14M15.54 8.46a5 5 0 010 7.07"/></svg>
          </button>
          <input v-if="showVolume" type="range" min="0" max="100" :value="player.volume" class="mini-player__volume-slider" @input="player.setVolume(Number(($event.target as HTMLInputElement).value))" @mousedown.stop />
        </div>
      </div>

      <!-- Tabs -->
      <div class="mini-player__tabs">
        <button class="mini-player__tab" :class="{ 'mini-player__tab--active': player.mode === 'playlists' }" @click="player.switchMode('playlists')">Playlists</button>
        <button class="mini-player__tab" :class="{ 'mini-player__tab--active': player.mode === 'search' }" @click="player.switchMode('search')">Buscar</button>
        <button class="mini-player__tab" :class="{ 'mini-player__tab--active': player.mode === 'favorites' }" @click="player.switchMode('favorites')">
          <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="currentColor" stroke="none" style="margin-right:2px"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          Salvas
        </button>
      </div>

      <!-- Playlists -->
      <div v-if="player.mode === 'playlists'" class="mini-player__section">
        <div v-if="!hasGoogleAccount" class="mini-player__msg"><a href="/api/v1/auth/google" class="mini-player__link">Conectar Google para ver playlists</a></div>
        <div v-else-if="player.loadingPlaylists" class="mini-player__msg">Carregando...</div>
        <div v-else-if="player.playlistError" class="mini-player__msg">{{ player.playlistError }}<br/><a href="/api/v1/auth/google" class="mini-player__link">Reconectar Google</a></div>
        <select v-else-if="player.playlists.length" class="mini-player__select" :value="player.currentPlaylistId" @mousedown.stop @change="player.selectPlaylist(player.playlists.find(p => p.id === ($event.target as HTMLSelectElement).value)!)">
          <option :value="null" disabled>Escolha uma playlist</option>
          <option v-for="pl in player.playlists" :key="pl.id" :value="pl.id">{{ pl.snippet.title }}</option>
        </select>
        <div v-else class="mini-player__msg">Nenhuma playlist</div>
      </div>

      <!-- Buscar -->
      <div v-if="player.mode === 'search'" class="mini-player__section">
        <div class="mini-player__search-bar">
          <input v-model="searchInput" class="mini-player__input" placeholder="Buscar música..." @keyup.enter="handleSearch" @mousedown.stop />
          <button class="mini-player__btn-set" :disabled="player.searching" @click="handleSearch">{{ player.searching ? '...' : 'Buscar' }}</button>
        </div>
        <div v-if="player.searchError" class="mini-player__msg">{{ player.searchError }}</div>
        <div v-if="player.searching" class="mini-player__msg">Buscando...</div>
        <div v-if="player.searchResults.length" class="mini-player__search-results">
          <button v-for="(item, i) in player.searchResults" :key="item.id?.videoId || i" class="mini-player__search-item" :class="{ 'mini-player__search-item--active': i === player.videoIndex && player.isPlaying }" @click="player.playSearchResult(i)">
            <img v-if="item.snippet?.thumbnails?.medium?.url" :src="item.snippet.thumbnails.medium.url" class="mini-player__search-thumb" alt="" />
            <span class="mini-player__search-title">{{ item.snippet?.title }}</span>
          </button>
        </div>
      </div>

      <!-- Favoritos -->
      <div v-if="player.mode === 'favorites'" class="mini-player__section">
        <div v-if="!player.favorites.length" class="mini-player__msg">Nenhuma playlist salva.</div>
        <div v-else class="mini-player__search-results">
          <button v-for="fav in player.favorites" :key="fav.playlistId" class="mini-player__search-item" @click="player.selectFavorite(fav)">
            <img v-if="fav.thumbnail" :src="fav.thumbnail" class="mini-player__search-thumb" alt="" />
            <span class="mini-player__search-title">{{ fav.title }}</span>
            <button class="mini-player__remove-fav" aria-label="Remover" @click.stop="player.removeFavorite(fav.playlistId)">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#e74c3c" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            </button>
          </button>
        </div>
      </div>
    </div>
  </aside>
</template>

<style scoped>
/* ─── Esquema de cores próprio (escuro/indigo) ─── */
.mini-player {
  --p-bg: #1a1a2e;
  --p-bg-card: #16213e;
  --p-bg-hover: #1f2b4f;
  --p-border: #2a3a5c;
  --p-text: #e8e8f0;
  --p-text-muted: #8a8aaa;
  --p-primary: #6366f1;
  --p-primary-dim: rgba(99,102,241,.15);
  --p-radius: 8px;
  --p-radius-sm: 4px;

  position: fixed; z-index: 9999; user-select: none;
  font-family: system-ui, sans-serif;
  color-scheme: dark;
}
/* Recolhido: barra fixa no topo */
.mini-player:not(.mini-player--expanded) { top: 0; left: 0; right: 0; }
/* Expandido: posicionado via drag (inline left/top) */
.mini-player--expanded { width: auto; }
.mini-player--dragging { cursor: grabbing; opacity: 0.95; }

.mini-player__collapse-bar {
  display: flex; align-items: center; gap: 8px; width: 100%; padding: 6px 16px;
  background: var(--p-bg-card); color: var(--p-text);
  font-size: 11px; border: 0; border-bottom: 1px solid var(--p-border);
  font-family: inherit;
}
.mini-player__collapse-controls {
  display: flex; align-items: center; gap: 2px; margin-left: auto;
}
.mini-player__mini-thumb { width: 24px; height: 24px; border-radius: var(--p-radius-sm); background-size: cover; background-position: center; flex-shrink: 0; }
.mini-player__playing-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--p-primary); animation: pulse-dot 1.2s ease-in-out infinite; flex-shrink: 0; }
.mini-player__title-label { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--p-text); cursor: pointer; flex-shrink: 1; }
.mini-player__drag-icon { opacity: 0.3; flex-shrink: 0; color: var(--p-text-muted); }

.mini-player__panel {
  width: 280px; max-height: 85vh; display: flex; flex-direction: column;
  background: var(--p-bg-card); border: 1px solid var(--p-border);
  border-radius: var(--p-radius); box-shadow: 0 10px 40px rgba(0,0,0,.5); overflow: hidden;
}
.mini-player__handle {
  display: flex; align-items: center; gap: 6px; padding: 6px 12px;
  border-bottom: 1px solid var(--p-border); cursor: grab; background: var(--p-bg-card);
}
.mini-player__header-title { flex: 1; font-size: 11px; font-weight: 600; color: var(--p-text-muted); }

.mini-player__album { width: 100%; aspect-ratio: 1; background: var(--p-bg); display: flex; align-items: center; justify-content: center; overflow: hidden; }
.mini-player__album-art { width: 100%; height: 100%; background-size: cover; background-position: center; transition: transform .4s; }
.mini-player__album-art--playing { animation: album-pulse 5s ease-in-out infinite; }
.mini-player__album-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: #555; background: var(--p-bg); }

.mini-player__track-info { padding: 10px 12px 4px; text-align: center; }
.mini-player__track-title { font-size: 13px; font-weight: 600; color: var(--p-text); margin: 0 0 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mini-player__track-artist { font-size: 11px; color: var(--p-text-muted); margin: 0; }

/* Progress */
.mini-player__progress-wrap { display: flex; align-items: center; gap: 6px; padding: 4px 12px; }
.mini-player__time { font-size: 10px; color: var(--p-text-muted); min-width: 32px; font-variant-numeric: tabular-nums; }
.mini-player__progress { flex: 1; height: 4px; -webkit-appearance: none; appearance: none; background: var(--p-border); border-radius: 2px; outline: none; cursor: pointer; }
.mini-player__progress::-webkit-slider-thumb { -webkit-appearance: none; width: 12px; height: 12px; border-radius: 50%; background: var(--p-primary); cursor: pointer; }
.mini-player__progress::-moz-range-thumb { width: 12px; height: 12px; border-radius: 50%; background: var(--p-primary); cursor: pointer; border: none; }

/* Controls */
.mini-player__controls { display: flex; align-items: center; justify-content: center; gap: 12px; padding: 8px 12px; }
.mini-player__btn-icon { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; border: 0; background: transparent; color: var(--p-text-muted); cursor: pointer; border-radius: 50%; transition: background .15s, color .15s; }
.mini-player__btn-icon:hover { background: var(--p-bg-hover); color: var(--p-text); }
.mini-player__btn-play { display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border: 0; background: var(--p-primary); color: #fff; cursor: pointer; border-radius: 50%; transition: transform .15s; }
.mini-player__btn-play:hover { transform: scale(1.1); }
.mini-player__ctrl-btn { display: flex; align-items: center; justify-content: center; width: 24px; height: 24px; border: 0; background: transparent; color: var(--p-text-muted); cursor: pointer; border-radius: 50%; transition: color .15s; }
.mini-player__ctrl-btn:hover { color: var(--p-text); }
.mini-player__ctrl-btn--active { color: var(--p-primary); }

.mini-player__volume-wrap { display: flex; align-items: center; gap: 4px; }
.mini-player__volume-slider { width: 60px; height: 4px; -webkit-appearance: none; appearance: none; background: var(--p-border); border-radius: 2px; outline: none; cursor: pointer; }
.mini-player__volume-slider::-webkit-slider-thumb { -webkit-appearance: none; width: 10px; height: 10px; border-radius: 50%; background: var(--p-text); }
.mini-player__volume-slider::-moz-range-thumb { width: 10px; height: 10px; border-radius: 50%; background: var(--p-text); border: none; }

/* Tabs */
.mini-player__tabs { display: flex; border-top: 1px solid var(--p-border); }
.mini-player__tab { flex: 1; display: flex; align-items: center; justify-content: center; padding: 7px 0; border: 0; background: transparent; color: var(--p-text-muted); cursor: pointer; font-size: 10px; font-weight: 500; border-bottom: 2px solid transparent; transition: color .15s; font-family: inherit; }
.mini-player__tab--active { color: var(--p-primary); border-bottom-color: var(--p-primary); }

.mini-player__section { padding: 8px 12px; overflow-y: auto; max-height: 150px; }
.mini-player__select { width: 100%; padding: 5px 8px; border: 1px solid var(--p-border); border-radius: var(--p-radius-sm); background: var(--p-bg); color: var(--p-text); font-size: 11px; }
.mini-player__msg { font-size: 11px; color: var(--p-text-muted); text-align: center; padding: 10px 0; }
.mini-player__link { color: var(--p-primary); text-decoration: underline; }

.mini-player__search-bar { display: flex; gap: 4px; margin-bottom: 6px; }
.mini-player__input { flex: 1; padding: 5px 8px; border: 1px solid var(--p-border); border-radius: var(--p-radius-sm); background: var(--p-bg); color: var(--p-text); font-size: 11px; outline: none; }
.mini-player__input:focus { border-color: var(--p-primary); }
.mini-player__btn-set { padding: 5px 10px; border: 0; border-radius: var(--p-radius-sm); background: var(--p-primary); color: #fff; cursor: pointer; font-size: 11px; font-weight: 600; }
.mini-player__btn-set:disabled { opacity: 0.6; cursor: default; }

.mini-player__search-results { display: flex; flex-direction: column; gap: 2px; }
.mini-player__search-item { display: flex; align-items: center; gap: 6px; padding: 4px; border: 0; border-radius: var(--p-radius-sm); background: transparent; color: var(--p-text); cursor: pointer; text-align: left; font-size: 11px; transition: background .1s; font-family: inherit; width: 100%; }
.mini-player__search-item:hover { background: var(--p-bg-hover); }
.mini-player__search-item--active { background: var(--p-primary-dim); color: var(--p-primary); }
.mini-player__search-thumb { width: 44px; height: 30px; object-fit: cover; border-radius: var(--p-radius-sm); flex-shrink: 0; }
.mini-player__search-title { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.mini-player__remove-fav { display: flex; align-items: center; justify-content: center; width: 20px; height: 20px; padding: 0; border: 0; background: transparent; cursor: pointer; border-radius: 50%; flex-shrink: 0; }
.mini-player__remove-fav:hover { background: rgba(231,76,60,.1); }

@keyframes pulse-dot { 0%,100%{opacity:1} 50%{opacity:.3} }
@keyframes album-pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.03)} }

@media (max-width: 768px) { .mini-player__panel { width: 260px; } }
.yt-player-wrapper { position: fixed; top: -9999px; left: -9999px; width: 200px; height: 200px; pointer-events: none; }
</style>
