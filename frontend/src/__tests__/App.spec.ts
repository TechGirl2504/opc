import { describe, it, expect, vi, beforeEach } from 'vitest'

import { mount } from '@vue/test-utils'
import App from '../App.vue'

vi.mock('vue-router', () => ({
  useRoute: () => ({ meta: { layout: 'auth' } }),
}))

vi.mock('@/stores/auth', () => ({
  useAuthStore: () => ({
    isAdmin: false,
    isAuthenticated: true,
  }),
}))

vi.mock('@/layouts/AuthLayout.vue', () => ({
  default: { name: 'AuthLayout', template: '<div data-test="auth"><slot /></div>' },
}))
vi.mock('@/layouts/AdminLayout.vue', () => ({
  default: { name: 'AdminLayout', template: '<div data-test="admin"><slot /></div>' },
}))
vi.mock('@/layouts/OfficerLayout.vue', () => ({
  default: { name: 'OfficerLayout', template: '<div data-test="officer"><slot /></div>' },
}))
vi.mock('@/components/PwaInstallPrompt.vue', () => ({
  default: { name: 'PwaInstallPrompt', template: '<div data-test="pwa" />' },
}))

describe('App', () => {
  it('uses auth layout on auth routes', () => {
    const wrapper = mount(App, {
      global: {
        stubs: {
          RouterView: { name: 'RouterView', template: '<div data-test="router-view" />' },
        },
      },
    })

    expect(wrapper.find('[data-test=\"auth\"]').exists()).toBe(true)
    expect(wrapper.find('[data-test=\"router-view\"]').exists()).toBe(true)
  })
})
