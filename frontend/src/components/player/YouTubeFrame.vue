<script setup lang="ts">
/**
 * Player YouTube usando a IFrame API oficial (YT.Player).
 * Muito mais confiável que postMessage manual.
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
let apiLoaded = false

function loadYT(cb: () => void) {
  if ((window as any).YT?.Player) { cb(); return }
  if (apiLoaded) { setTimeout(() => loadYT(cb), 200); return }
  apiLoaded = true
  const tag = document.createElement('script')
  tag.src = 'https://www.youtube.com/iframe_api'
  const first = document.getElementsByTagName('script')[0]
  first?.parentNode?.insertBefore(tag, first)
  ;(window as any).onYouTubeIframeAPIReady = () => {
    setTimeout(cb, 100)
  }
  // Fallback: tenta mesmo sem callback
  setTimeout(() => {
    if ((window as any).YT?.Player) cb()
  }, 4000)
}

function createPlayer() {
  if (isCreating) return
  if (player) { player.destroy(); player = null }
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
    },
    events: {
      onReady: () => {
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
      onError: () => {
        isCreating = false
        // Tentar novamente após erro
        setTimeout(() => { if (!isReady) { isReady = true; emit('ready') } }, 2000)
      },
    },
  }

  if (props.playlistId) {
    config.playerVars!.list = props.playlistId
    config.playerVars!.listType = 'playlist'
  }

  loadYT(() => {
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
    if (player && isReady) {
      const t = player.getCurrentTime()
      const d = player.getDuration()
      if (typeof t === 'number' && typeof d === 'number' && d > 0) {
        emit('timeUpdate', t, d)
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
watch(() => props.playlistId, () => createPlayer())

watch(() => props.videoId, (newId, oldId) => {
  if (!newId) return
  if (player && isReady && newId !== oldId) {
    // Troca de vídeo sem recriar o player — muito mais rápido
    player.loadVideoById(newId)
    if (!props.isPlaying) player.pauseVideo()
    return
  }
  // Player ainda não está pronto — não recriar, só aguardar
  // onMounted/playlistId watcher já chamaram createPlayer()
})

watch(() => props.videoIndex, (idx) => {
  if (player && isReady) {
    if (props.playlistId) player.playVideoAt(idx)
    if (props.isPlaying) player.playVideo()
  }
})

watch(() => props.isPlaying, (p) => {
  if (!player || !isReady) return
  if (p) player.playVideo()
  else player.pauseVideo()
})
watch(() => props.isShuffled, (v) => { if (player && isReady) player.setShuffle(v) })
watch(() => props.repeatMode, () => { if (player && isReady) updateRepeat() })
watch(() => props.volume, (v) => { if (player && isReady) player.setVolume(v) })
watch(() => props.seekPercent, (v) => {
  if (player && isReady && v >= 0) {
    const dur = player.getDuration()
    if (dur > 0) player.seekTo((v / 100) * dur, true)
  }
})

onUnmounted(() => {
  if (timeInterval) clearInterval(timeInterval)
  player?.destroy()
  player = null
})
</script>

<template>
  <div :id="containerId" class="yt-frame" />
</template>

<style scoped>
.yt-frame {
  width: 200px; height: 200px;
}
</style>
