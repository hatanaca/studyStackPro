/**
 * Carrega a YouTube IFrame API de forma deduplicada.
 *
 * Gerencia globalmente:
 * - Injeção do script <script> uma única vez
 * - Fila de callbacks (__ytCbs) para quando a API estiver pronta
 * - Flag de carregamento (__ytApiLoading) para evitar duplicatas
 * - Timeout de fallback (5s) caso o callback não dispare
 *
 * @example
 * ```ts
 * import { loadYT } from '@/utils/youtubeIframeApi'
 *
 * loadYT(() => {
 *   new YT.Player('container', { videoId: 'abc' })
 * })
 * ```
 */
export function loadYT(cb: () => void): void {
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const w = window as any
  if (w.YT?.Player) {
    cb()
    return
  }

  if (!w.__ytCbs) w.__ytCbs = []
  w.__ytCbs.push(cb)

  if (!w.__ytApiLoading) {
    w.__ytApiLoading = true
    const tag = document.createElement('script')
    tag.src = 'https://www.youtube.com/iframe_api'
    const first = document.getElementsByTagName('script')[0]
    first?.parentNode?.insertBefore(tag, first)

    w.onYouTubeIframeAPIReady = () => {
      setTimeout(() => {
        const queue = w.__ytCbs
        delete w.__ytCbs
        queue?.forEach((f: () => void) => f())
      }, 100)
    }

    setTimeout(() => {
      if (w.YT?.Player && w.__ytCbs) {
        const queue = w.__ytCbs
        delete w.__ytCbs
        queue.forEach((f: () => void) => f())
      }
    }, 5000)
    return
  }

  const check = setInterval(() => {
    if (w.YT?.Player) {
      clearInterval(check)
      cb()
    }
  }, 200)
  setTimeout(() => clearInterval(check), 10000)
}
