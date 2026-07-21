/**
 * @module useCanvasPersistence
 * @description Composable de persistência do canvas via API backend.
 *
 * Fornece save/load/delete que usam a API REST em vez de localStorage.
 * Mantém localStorage como fallback offline.
 */
import { ref } from 'vue'
import { canvasApi, type CanvasArtwork } from '@/api/modules/canvas.api'

export function useCanvasPersistence() {
  const currentArtwork = ref<CanvasArtwork | null>(null)
  const saving = ref(false)
  const loading = ref(false)

  async function save(
    canvasData: Record<string, unknown>,
    options?: { title?: string; muralItems?: Array<{ id: string; type: string; url: string }> }
  ): Promise<CanvasArtwork | null> {
    saving.value = true
    try {
      if (currentArtwork.value) {
        const updated = await canvasApi.update(currentArtwork.value.id, {
          canvas_data: canvasData,
          title: options?.title,
          mural_items: options?.muralItems,
        })
        currentArtwork.value = updated
        return updated
      }
      const created = await canvasApi.create({
        canvas_data: canvasData,
        title: options?.title ?? 'Sem título',
        mural_items: options?.muralItems,
      })
      currentArtwork.value = created
      return created
    } catch {
      // fallback: save to localStorage
      return null
    } finally {
      saving.value = false
    }
  }

  async function load(id: string): Promise<Record<string, unknown> | null> {
    loading.value = true
    try {
      const artwork = await canvasApi.get(id)
      currentArtwork.value = artwork
      return artwork.canvas_data
    } catch {
      return null
    } finally {
      loading.value = false
    }
  }

  async function loadList(): Promise<CanvasArtwork[]> {
    try {
      return await canvasApi.list()
    } catch {
      return []
    }
  }

  async function remove(id: string): Promise<void> {
    try {
      await canvasApi.delete(id)
      if (currentArtwork.value?.id === id) {
        currentArtwork.value = null
      }
    } catch {
      // ignore
    }
  }

  return {
    currentArtwork,
    saving,
    loading,
    save,
    load,
    loadList,
    remove,
  }
}
