<script setup lang="ts">
/**
 * Player YouTube usando a IFrame API oficial (YT.Player).
 */
import { watch, onMounted, onUnmounted } from 'vue'

const props = defineProps<{
  playlistId: string | null
  videoId: string | null
  videoIndex: number
  isPlaying: boolean
  isShuffled: boolean
  repeatMode: 'none' | 'playlist' | 'single'
  volume: number
  seekPercent: number
}>()

const emit = defineEmits<{
  (e: 'ready'): void
  (e: 'stateChange', state: number): void
  (e: 'ended'): void
  (e: 'timeUpdate', time: number, dur: number): void
}>()

const containerId = 'yt-player-' + Math.random().toString(36).slice(2, 8)
let player: YT.Player | null = null
let timeInterval: ReturnType<typeof setInterval> | null = null
let isReady = false
let isCreating = false
let destroyed = false
let apiLoadRetries = 0
const MAX_API_RETRIES = 15

function loadYT(cb: () => void) {
  if (destroyed) return
  if ((window as any).YT?.Player) {
    cb()
    return
  }

  // Limitar retries para evitar loop infinito
  if (apiLoadRetries >= MAX_API_RETRIES) return
  apiLoadRetries++

  const tag = document.createElement('script')
  tag.src = 'https://www.youtube.com/iframe_api'
  const first = document.getElementsByTagName('script')[0]
  first?.parentNode?.insertBefore(tag, first)
  ;(window as any).onYouTubeIframeAPIReady = () => {
    if (!destroyed) cb()
  }
  // Fallback com limite
  setTimeout(() => {
    if (!destroyed && (window as any).YT?.Player) cb()
  }, 5000)
}

function createPlayer() {
  if (isCreating || destroyed) return
  if (!props.playlistId && !props.videoId) return

  isCreating = true
  isReady = false

  const config: YT.PlayerOptions = {
    height: '200',
    width: '200',
    playerVars: {
      enablejsapi: 1,
      modestbranding: 1,
      rel: 0,
      controls: 0,
      disablekb: 1,
      fs: 0,
      iv_load_policy: 3,
      origin: window.location.origin,
    },
    events: {
      onReady: () => {
        if (destroyed) return
        isReady = true
        isCreating = false
        player?.setShuffle(props.isShuffled)
        player?.setVolume(props.volume)
        updateRepeat()
        emit('ready')
        startTimePoll()
        if (props.isPlaying) player?.playVideo()
      },
      onStateChange: (e: YT.OnStateChangeEvent) => {
        if (destroyed) return
        emit('stateChange', e.data)
        if (e.data === 0) {
          if (props.repeatMode === 'single') {
            player?.seekTo(0, true)
            player?.playVideo()
            return
          }
          emit('ended')
        }
      },
      onError: (e: YT.OnErrorEvent) => {
        if (destroyed) return
        isCreating = false
        // Erro 150 = vídeo restrito; 100 = vídeo não encontrado; 2 = parâmetro inválido
        const errCode = typeof e?.data === 'number' ? e.data : -1
        emit('stateChange', -1) // sinaliza estado de erro
        if (errCode === 150 || errCode === 100) {
          emit('ended')
        }
      },
    },
  }

  if (props.playlistId) {
    config.playerVars!.list = props.playlistId
    config.playerVars!.listType = 'playlist'
  }

  loadYT(() => {
    if (destroyed) return
    // Destruir player anterior antes de criar novo
    if (player) {
      player.destroy()
      player = null
    }

    if (props.playlistId) {
      player = new YT.Player(containerId, config)
    } else if (props.videoId) {
      player = new YT.Player(containerId, { ...config, videoId: props.videoId })
    }
  })
}

function startTimePoll() {
  if (timeInterval) clearInterval(timeInterval)
  timeInterval = setInterval(() => {
    if (player && isReady && !destroyed) {
      // Verifica se player é instância válida antes de chamar métodos
      if (typeof player.getCurrentTime !== 'function') return
      try {
        const t = player.getCurrentTime()
        const d = player.getDuration()
        if (typeof t === 'number' && typeof d === 'number' && d > 0) {
          emit('timeUpdate', t, d)
        }
      } catch {
        // Player em estado inválido (ex.: erro 150) — ignora
      }
    }
  }, 1000)
}

function updateRepeat() {
  if (!player || !isReady) return
  player.setLoop(props.repeatMode !== 'none')
}

onMounted(() => createPlayer())

// Reage a mudanças de props
watch(
  () => props.playlistId,
  (newVal, oldVal) => {
    if (newVal !== oldVal) createPlayer()
  }
)

watch(
  () => props.videoId,
  (newId, oldId) => {
    if (!newId || destroyed) return
    if (player && isReady && newId !== oldId) {
      player.loadVideoById(newId)
      if (!props.isPlaying) player.pauseVideo()
      return
    }
  }
)

watch(
  () => props.videoIndex,
  (idx) => {
    if (player && isReady && !destroyed) {
      if (props.playlistId) player.playVideoAt(idx)
      if (props.isPlaying) player.playVideo()
    }
  }
)

watch(
  () => props.isPlaying,
  (p) => {
    if (!player || !isReady || destroyed) return
    if (p) player.playVideo()
    else player.pauseVideo()
  }
)
watch(
  () => props.isShuffled,
  (v) => {
    if (player && isReady) player.setShuffle(v)
  }
)
watch(
  () => props.repeatMode,
  () => {
    if (player && isReady) updateRepeat()
  }
)
watch(
  () => props.volume,
  (v) => {
    if (player && isReady) player.setVolume(v)
  }
)
watch(
  () => props.seekPercent,
  (v) => {
    if (player && isReady && v >= 0) {
      const dur = player.getDuration()
      if (dur > 0) player.seekTo((v / 100) * dur, true)
    }
  }
)

onUnmounted(() => {
  destroyed = true
  if (timeInterval) clearInterval(timeInterval)
  try {
    player?.destroy()
  } catch {}
  player = null
})
</script>

<template>
  <div :id="containerId" class="yt-frame" />
</template>

<style scoped>
.yt-frame {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  pointer-events: none;
  clip: rect(0 0 0 0);
  clip-path: inset(50%);
  contain: strict;
  opacity: 0;
}
</style>
