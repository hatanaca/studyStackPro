# YouTube Player — Technical Documentation

> Updated on 2026-06-23 after critical bug fixes.

## Overview

The YouTube Player is a floating mini-player that allows searching and playing YouTube videos/playlists within the application. It is composed of:

| Component | File | Responsibility |
|-----------|------|----------------|
| `MiniPlayer` | `frontend/src/components/player/MiniPlayer.vue` | UI: collapsed bar, expanded panel, controls, drag |
| `YouTubeFrame` | `frontend/src/components/player/YouTubeFrame.vue` | Player IFrame API (YT.Player), YouTube communication |
| `player.store` | `frontend/src/stores/player.store.ts` | Global state: playlist, search, favorites, controls |
| `youtube.store` | `frontend/src/stores/youtube.store.ts` | Search view store (YouTubeSearchView) |
| `YouTubeSearchView` | `frontend/src/views/videos/YouTubeSearchView.vue` | Search page with embedded player |

## Data Flow

```
User (button) → player.store (isPlaying) → watcher YouTubeFrame → YT.Player.playVideo()
                                                                         ↓
YouTube IFrame → onStateChange → emit('stateChange') → MiniPlayer → store.isPlaying
                                                                         ↓
                                              watcher YouTubeFrame → getPlayerState() guard
                                              (only sends command if real state differs)
```

### Anti-Loop Guard (Critical)

**Principle:** `isPlaying` in the store = user intent. YouTubeFrame only obeys, never writes back.

```typescript
// YouTubeFrame.vue — watcher only sends command, no feedback
watch(() => props.isPlaying, (p) => {
  if (!player || !isReady) return
  if (p) safeCall(() => player!.playVideo())
  else safeCall(() => player!.pauseVideo())
})
```

```typescript
// onStateChange — used ONLY to detect "ended"
onStateChange: (e) => {
  if (e.data === 0) { // ended
    if (manualChange) { manualChange = false; return }
    if (props.repeatMode === 'single') { player.seekTo(0, true); player.playVideo(); return }
    emit('ended') // → MiniPlayer calls nextVideo()
  }
}
```

**Why it works:** there is no cycle. `isPlaying` is controlled only by the store (user actions). YouTubeFrame receives the value via props and sends commands to YouTube. YouTube can change its internal state (paused, buffering, ended), but this NEVER goes back to the store.

**Previous attempts that failed:**
1. `getPlayerState()` guard — edge cases where real state ≠ expected
2. `applyingCommand` flag — didn't cover all paths (ended, error, buffering)
3. Value check in `onPlayerStateChange` — still created a cycle in buffering

### Anti-Duplication Guard (`createGeneration`)

`createPlayer()` increments a counter `createGeneration` on each call. Async callbacks from `loadYT` check `gen !== createGeneration` and are ignored if a new creation happened. This prevents duplicate players when the user clicks quickly on search results.

```typescript
function createPlayer() {
  const gen = ++createGeneration
  // ...destroy old player...
  loadYT(() => {
    if (gen !== createGeneration) return // obsolete callback
    player = new YT.Player(containerId, config)
  })
}
```

### Time Polling

The `YouTubeFrame` starts a 1s `setInterval` that emits `timeUpdate(time, duration)`. The `MiniPlayer` updates `store.currentTime` and `store.duration` (except during seek). The progress bar uses the computed `store.progress`.

## MiniPlayer Modes

| Mode | Video Source | `currentPlaylistId` | `currentVideoId` |
|------|-------------|---------------------|-------------------|
| `playlists` | Selected YouTube playlist | `selectedPlaylist.id` | `null` (player manages) |
| `search` | Search results | `null` | `searchResults[videoIndex].id.videoId` |
| `favorites` | Locally saved playlists | `selectedPlaylist.id` | `null` |

## Persisted State (localStorage)

| Key | Content |
|-----|---------|
| `studytrack_miniplayer` | `{ playlist, videoIndex, isPlaying, isExpanded, mode, searchResults }` |
| `studytrack_miniplayer_pos` | `{ x, y }` expanded panel position |
| `studytrack_favorites` | `[{ playlistId, title, thumbnail }]` |
| `studytrack_shuffle` | `"true"` / `"false"` |
| `studytrack_repeat` | `"none"` / `"playlist"` / `"single"` |
| `studytrack_volume` | `0-100` |

## Teleport and Iframe

The `YouTubeFrame` is rendered via `<Teleport to="body">` inside a wrapper with `clip-path: inset(100%)` (invisible but functional). This keeps the iframe alive even when the MiniPlayer is collapsed.

```css
.yt-player-wrapper {
  position: fixed; top: 0; left: 0;
  width: 200px; height: 200px;
  clip-path: inset(100%);
  pointer-events: none;
}
```

## Fixed Bugs (2026-06-23)

See [ERROS-CORRIGIDOS.md](../operations/ERROS-CORRIGIDOS.md) items 7-16 for full details.

| # | Bug | Impact |
|---|-----|--------|
| 7 | Feedback loop store ↔ YouTubeFrame | App froze when clicking any control |
| 8 | `isPlaying` never went to `false` | Play/pause button stuck, progress stopped |
| 9 | `nextVideo`/`prevVideo` without guard | Undefined behavior without results |
| 10 | `currentTime`/`duration` didn't reset | Ghost time when clearing content |
| 11 | Memory leak `manualChangeTimer` | Timer ran after component unmounted |
| 12 | `createPlayer` blocked by `isCreating` | App froze when clicking quickly on results |
| 13 | `loadYT` re-registered global callback | Player never became ready in fast navigation scenarios |
| 14 | Uncaptured exceptions in player calls | Silent crash when player destroyed asynchronously |
| 16 | `onPlayerStateChange` caused residual loop | Buffering/ended edge cases triggered unnecessary commands |
| 17 | Loop persisted (bidirectional architecture) | Refactoring to unidirectional flow eliminated the cycle at root |

## Known Limitations

- **Two player instances**: `YouTubeSearchView` creates its own `YT.Player` (large embedded player). If the MiniPlayer is playing, both use the same IFrame API — there is no conflict because they are separate iframes, but the MiniPlayer doesn't pause when the view embed starts.
- **API key required**: Search and playlists require `YOUTUBE_API_KEY` on the backend (authenticated proxy).
- **Google OAuth for playlists**: User playlists require Google login (OAuth flow at `/api/v1/auth/google`).
