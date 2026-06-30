# YouTube Player — Documentação Técnica

> Atualizado em 2026-06-23 após correção de bugs críticos.

## Visão geral

O YouTube Player é um mini-player flutuante que permite buscar e reproduzir vídeos/playlist do YouTube dentro da aplicação. É composto por:

| Componente | Arquivo | Responsabilidade |
|------------|---------|------------------|
| `MiniPlayer` | `frontend/src/components/player/MiniPlayer.vue` | UI: barra colapsada, painel expandido, controles, drag |
| `YouTubeFrame` | `frontend/src/components/player/YouTubeFrame.vue` | Player IFrame API (YT.Player), comunicação com YouTube |
| `player.store` | `frontend/src/stores/player.store.ts` | Estado global: playlist, search, favoritos, controles |
| `youtube.store` | `frontend/src/stores/youtube.store.ts` | Store da view de busca (YouTubeSearchView) |
| `YouTubeSearchView` | `frontend/src/views/videos/YouTubeSearchView.vue` | Página de busca com player embed |

## Fluxo de dados

```
Usuário (botão) → player.store (isPlaying) → watcher YouTubeFrame → YT.Player.playVideo()
                                                                         ↓
YouTube IFrame → onStateChange → emit('stateChange') → MiniPlayer → store.isPlaying
                                                                         ↓
                                              watcher YouTubeFrame → getPlayerState() guard
                                              (só envia comando se estado real difere)
```

### Guard anti-loop (crítico)

**Princípio:** `isPlaying` no store = intenção do usuário. YouTubeFrame apenas obedece, nunca escreve de volta.

```typescript
// YouTubeFrame.vue — watcher apenas envia comando, sem feedback
watch(() => props.isPlaying, (p) => {
  if (!player || !isReady) return
  if (p) safeCall(() => player!.playVideo())
  else safeCall(() => player!.pauseVideo())
})
```

```typescript
// onStateChange — usado APENAS para detectar "ended"
onStateChange: (e) => {
  if (e.data === 0) { // ended
    if (manualChange) { manualChange = false; return }
    if (props.repeatMode === 'single') { player.seekTo(0, true); player.playVideo(); return }
    emit('ended') // → MiniPlayer chama nextVideo()
  }
}
```

**Por que funciona:** não há ciclo. `isPlaying` é controlado apenas pelo store (ações do usuário). YouTubeFrame recebe o valor via props e envia comandos ao YouTube. O YouTube pode mudar seu estado interno (paused, buffering, ended), mas isso NUNCA volta para o store.

**Tentativas anteriores que falharam:**
1. `getPlayerState()` guard — edge cases onde estado real ≠ esperado
2. `applyingCommand` flag — não cobria todos os caminhos (ended, erro, buffering)
3. Checagem de valor no `onPlayerStateChange` — ainda criava ciclo em buffering

### Guard anti-duplicação (`createGeneration`)

`createPlayer()` incrementa um counter `createGeneration` a cada chamada. Callbacks assíncronos de `loadYT` verificam `gen !== createGeneration` e são ignorados se uma nova criação aconteceu. Isso previne players duplicados quando o usuário clica rápido em resultados de busca.

```typescript
function createPlayer() {
  const gen = ++createGeneration
  // ...destroy old player...
  loadYT(() => {
    if (gen !== createGeneration) return // callback obsoleto
    player = new YT.Player(containerId, config)
  })
}
```

### Time polling

O `YouTubeFrame` inicia um `setInterval` de 1s que emite `timeUpdate(time, duration)`. O `MiniPlayer` atualiza `store.currentTime` e `store.duration` (exceto durante seek). A barra de progresso usa o computed `store.progress`.

## Modos do MiniPlayer

| Modo | Fonte de vídeos | `currentPlaylistId` | `currentVideoId` |
|------|-----------------|---------------------|-------------------|
| `playlists` | Playlist do YouTube selecionada | `selectedPlaylist.id` | `null` (player gerencia) |
| `search` | Resultados de busca | `null` | `searchResults[videoIndex].id.videoId` |
| `favorites` | Playlists salvas localmente | `selectedPlaylist.id` | `null` |

## Estado persistido (localStorage)

| Chave | Conteúdo |
|-------|----------|
| `studytrack_miniplayer` | `{ playlist, videoIndex, isPlaying, isExpanded, mode, searchResults }` |
| `studytrack_miniplayer_pos` | `{ x, y }` posição do painel expandido |
| `studytrack_favorites` | `[{ playlistId, title, thumbnail }]` |
| `studytrack_shuffle` | `"true"` / `"false"` |
| `studytrack_repeat` | `"none"` / `"playlist"` / `"single"` |
| `studytrack_volume` | `0-100` |

## Teleport e iframe

O `YouTubeFrame` é renderizado via `<Teleport to="body">` dentro de um wrapper com `clip-path: inset(100%)` (invisível mas funcional). Isso mantém o iframe vivo mesmo quando o MiniPlayer é colapsado.

```css
.yt-player-wrapper {
  position: fixed; top: 0; left: 0;
  width: 200px; height: 200px;
  clip-path: inset(100%);
  pointer-events: none;
}
```

## Bugs corrigidos (2026-06-23)

Ver [ERROS-CORRIGIDOS.md](../operations/ERROS-CORRIGIDOS.md) itens 7-16 para detalhes completos.

| # | Bug | Impacto |
|---|-----|---------|
| 7 | Loop de feedback store ↔ YouTubeFrame | App travava ao clicar qualquer controle |
| 8 | `isPlaying` nunca ia a `false` | Botão play/pause travado, progresso parado |
| 9 | `nextVideo`/`prevVideo` sem guarda | Comportamento indefinido sem resultados |
| 10 | `currentTime`/`duration` não resetavam | Tempo fantasma ao limpar conteúdo |
| 11 | Memory leak `manualChangeTimer` | Timer rodava após desmontar componente |
| 12 | `createPlayer` bloqueado por `isCreating` | App travava ao clicar rápido em resultados |
| 13 | `loadYT` re-registrava callback global | Player nunca ficava pronto em cenários de navegação rápida |
| 14 | Exceções não capturadas em calls ao player | Crash silencioso quando player destruído assincronamente |
| 16 | `onPlayerStateChange` causava loop residual | Edge cases de buffering/ended disparavam comandos desnecessários |
| 17 | Loop persistia (arquitetura bidirecional) | Refatoração para fluxo unidirecional eliminou o ciclo na raiz |

## Limitações conhecidas

- **Duas instâncias de player**: `YouTubeSearchView` cria seu próprio `YT.Player` (player embed grande). Se o MiniPlayer estiver tocando, ambos usam a mesma IFrame API — não há conflito porque são iframes separados, mas o MiniPlayer não pausa quando o embed da view começa.
- **API key necessária**: Busca e playlists exigem `YOUTUBE_API_KEY` no backend (proxy autenticado).
- **OAuth Google para playlists**: Playlists do usuário exigem login com Google (OAuth flow em `/api/v1/auth/google`).
