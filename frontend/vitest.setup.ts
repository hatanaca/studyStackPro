/** Evita carregar vue-router (createWebHistory) ao importar módulos que puxam `@/api/client`. */
vi.mock('@/router', () => ({
  default: {
    push: vi.fn(),
    replace: vi.fn(),
    currentRoute: { value: { name: 'home', path: '/' } },
  },
}))
