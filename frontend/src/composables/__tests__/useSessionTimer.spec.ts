import { mount } from '@vue/test-utils'
import { defineComponent } from 'vue'
import { setActivePinia, createPinia } from 'pinia'
import { useSessionTimer } from '@/features/sessions/composables/useSessionTimer'
import { sessionsApi } from '@/api/modules/sessions.api'

vi.mock('@/api/modules/sessions.api', () => ({
  sessionsApi: {
    getActive: vi.fn()
  }
}))

/** Wrapper que usa o composable dentro de um componente para que onMounted/onUnmounted tenham contexto. */
const SessionTimerWrapper = defineComponent({
  setup() {
    return useSessionTimer()
  },
  template: '<div></div>'
})

describe('useSessionTimer', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
    vi.useFakeTimers()
    // Mock padrão: sem sessão ativa, para onMounted não quebrar ao montar o wrapper
    vi.mocked(sessionsApi.getActive).mockResolvedValue({
      data: { success: true, data: null }
    } as never)
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('retorna activeSession, elapsedSeconds, formattedTime e refresh', () => {
    const wrapper = mount(SessionTimerWrapper)

    expect(wrapper.vm.activeSession).toBeDefined()
    expect(wrapper.vm.elapsedSeconds).toBeDefined()
    expect(wrapper.vm.formattedTime).toBeDefined()
    expect(typeof wrapper.vm.refresh).toBe('function')
  })

  it('refresh com elapsed_seconds do servidor preenche o store', async () => {
    vi.mocked(sessionsApi.getActive).mockResolvedValue({
      data: {
        success: true,
        data: {
          id: '1',
          elapsed_seconds: 120,
          technology: {}
        }
      }
    } as never)

    const wrapper = mount(SessionTimerWrapper)
    await wrapper.vm.refresh()

    const { useSessionsStore } = await import('@/stores/sessions.store')
    const store = useSessionsStore()
    expect(store.elapsedSeconds).toBe(120)
    expect(store.activeSession).toBeTruthy()
  })
})
