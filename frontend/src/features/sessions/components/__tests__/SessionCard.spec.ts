import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import SessionCard from '../SessionCard.vue'
import type { StudySession } from '@/types/domain.types'

const mockSession: StudySession = {
  id: 'sess-1',
  user_id: 'user-1',
  technology_id: 'tech-1',
  technology: {
    id: 'tech-1',
    name: 'Vue.js',
    slug: 'vuejs',
    color: '#42B883',
    is_active: true
  },
  started_at: '2025-02-26T10:00:00Z',
  ended_at: '2025-02-26T11:30:00Z',
  duration_min: 90,
  duration_formatted: '1h 30min',
  notes: 'Sessão produtiva',
  mood: 4,
  created_at: '2025-02-26T10:00:00Z'
}

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: { template: '<div />' } },
    { path: '/sessions/:id', name: 'session-detail', component: { template: '<div />' } }
  ]
})

describe('SessionCard', () => {
  it('renderiza tecnologia e duração', async () => {
    const wrapper = mount(SessionCard, {
      props: { session: mockSession },
      global: { plugins: [router] }
    })

    expect(wrapper.text()).toContain('Vue.js')
    expect(wrapper.text()).toContain('1h 30min')
  })

  it('renderiza notas quando presentes', () => {
    const wrapper = mount(SessionCard, {
      props: { session: mockSession },
      global: { plugins: [router] }
    })

    expect(wrapper.text()).toContain('Sessão produtiva')
  })

  it('renderiza mood quando presente', () => {
    const wrapper = mount(SessionCard, {
      props: { session: mockSession },
      global: { plugins: [router] }
    })

    expect(wrapper.text()).toContain('Mood: 4/5')
  })

  it('emite edit ao clicar em Editar', async () => {
    const wrapper = mount(SessionCard, {
      props: { session: mockSession },
      global: { plugins: [router] }
    })

    const editBtn = wrapper.findAll('button').find((b) => b.text() === 'Editar')
    expect(editBtn).toBeDefined()
    await editBtn!.trigger('click')

    expect(wrapper.emitted('edit')).toBeTruthy()
    expect(wrapper.emitted('edit')?.[0]).toEqual([mockSession])
  })

  it('exibe "Em andamento" quando duration_formatted é null', () => {
    const activeSession = { ...mockSession, ended_at: null, duration_min: null, duration_formatted: null }
    const wrapper = mount(SessionCard, {
      props: { session: activeSession },
      global: { plugins: [router] }
    })

    expect(wrapper.text()).toContain('Em andamento')
  })
})
