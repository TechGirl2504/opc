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
const csrfCookieMock = vi.fn()
const userMock = vi.fn()

vi.mock('@/api/auth', () => ({
  authApi: {
    csrfCookie: csrfCookieMock,
    login: loginMock,
    logout: logoutMock,
    user: userMock,
  },
}))

function installMockStorage() {
  let store: Record<string, string> = {}
  const storageMock = {
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

  Object.defineProperty(window, 'sessionStorage', {
    value: storageMock,
    configurable: true,
  })
}

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    installMockStorage()
    window.sessionStorage.clear()
    pushMock.mockClear()
    loginMock.mockReset()
    logoutMock.mockReset()
    csrfCookieMock.mockReset()
    userMock.mockReset()
    vi.resetModules()
  })

  it('loads the user on successful login', async () => {
    csrfCookieMock.mockResolvedValue({})
    loginMock.mockResolvedValue({
      data: {
        success: true,
        data: {
          user: { id: 1, roles: ['admin'], permissions: [] },
        },
      },
    })

    const { useAuthStore } = await import('@/stores/auth')
    const store = useAuthStore()

    await store.login({ username: 'u', password: 'p' })

    expect(store.isAuthenticated).toBe(true)
    expect(store.user?.id).toBe(1)
    expect(csrfCookieMock).toHaveBeenCalledTimes(1)
  })

  it('clears user and redirects on logout (even if API errors)', async () => {
    logoutMock.mockRejectedValue(new Error('network'))
    vi.spyOn(console, 'error').mockImplementation(() => {})

    const { useAuthStore } = await import('@/stores/auth')
    const store = useAuthStore()
    store.user = { id: 2, roles: ['admin'], permissions: [] } as any

    await store.logout()

    expect(store.user).toBe(null)
    expect(pushMock).toHaveBeenCalledWith({ name: 'Login' })
  })
})
