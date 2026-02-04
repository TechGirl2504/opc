import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

const pushMock = vi.fn()

vi.mock('@/router', () => ({
  default: {
    push: pushMock,
  },
}))

const loginMock = vi.fn()
const logoutMock = vi.fn()
const userMock = vi.fn()

vi.mock('@/api/auth', () => ({
  authApi: {
    login: loginMock,
    logout: logoutMock,
    user: userMock,
  },
}))

function installMockLocalStorage() {
  let store: Record<string, string> = {}
  const localStorageMock = {
    getItem: (key: string) => (Object.prototype.hasOwnProperty.call(store, key) ? store[key] : null),
    setItem: (key: string, value: string) => {
      store[key] = String(value)
    },
    removeItem: (key: string) => {
      delete store[key]
    },
    clear: () => {
      store = {}
    },
  }

  Object.defineProperty(window, 'localStorage', {
    value: localStorageMock,
    configurable: true,
  })
}

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    installMockLocalStorage()
    window.localStorage.clear()
    pushMock.mockClear()
    loginMock.mockReset()
    logoutMock.mockReset()
    userMock.mockReset()
    vi.resetModules()
  })

  it('stores token and user on successful login', async () => {
    loginMock.mockResolvedValue({
      data: {
        success: true,
        data: {
          token: 't-1',
          user: { id: 1, roles: ['admin'], permissions: [] },
        },
      },
    })

    const { useAuthStore } = await import('@/stores/auth')
    const store = useAuthStore()

    await store.login({ username: 'u', password: 'p' })

    expect(store.token).toBe('t-1')
    expect(store.isAuthenticated).toBe(true)
    expect(window.localStorage.getItem('auth_token')).toBe('t-1')
    expect(store.user?.id).toBe(1)
  })

  it('clears token and redirects on logout (even if API errors)', async () => {
    logoutMock.mockRejectedValue(new Error('network'))
    vi.spyOn(console, 'error').mockImplementation(() => {})

    window.localStorage.setItem('auth_token', 't-2')
    const { useAuthStore } = await import('@/stores/auth')
    const store = useAuthStore()

    await store.logout()

    expect(store.token).toBe(null)
    expect(store.user).toBe(null)
    expect(window.localStorage.getItem('auth_token')).toBe(null)
    expect(pushMock).toHaveBeenCalledWith('/login')
  })
})

