import { describe, it, expect, vi, beforeEach } from 'vitest'

type AxiosCreateConfig = { baseURL?: string; headers?: Record<string, string>; withCredentials?: boolean }
type AxiosRequestConfig = { headers?: Record<string, string> }

let requestInterceptor: ((config: AxiosRequestConfig) => AxiosRequestConfig) | undefined

const createMock = vi.fn((_config: AxiosCreateConfig) => {
  return {
    interceptors: {
      request: {
        use: vi.fn((onFulfilled: any) => {
          requestInterceptor = onFulfilled
          return 0
        }),
      },
      response: {
        use: vi.fn(() => 0),
      },
    },
  }
})

vi.mock('axios', () => ({
  default: {
    create: createMock,
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

beforeEach(() => {
  createMock.mockClear()
  requestInterceptor = undefined
  installMockLocalStorage()
  window.localStorage.clear()
  ;(window as any).__ENV__ = undefined
  vi.resetModules()
})

describe('api client', () => {
  it('prefers runtime-injected VITE_API_BASE_URL', async () => {
    ;(window as any).__ENV__ = { VITE_API_BASE_URL: 'https://example.test/api/v1' }

    await import('@/api')

    expect(createMock).toHaveBeenCalledTimes(1)
    expect(createMock.mock.calls[0]?.[0]?.baseURL).toBe('https://example.test/api/v1')
  })

  it('falls back to localhost base URL when unset', async () => {
    await import('@/api')

    expect(createMock).toHaveBeenCalledTimes(1)
    expect(createMock.mock.calls[0]?.[0]?.baseURL).toBe('http://localhost:8000/api/v1')
  })

  it('adds Authorization header when token exists', async () => {
    window.localStorage.setItem('auth_token', 'token-123')
    await import('@/api')

    expect(requestInterceptor).toBeTypeOf('function')
    const config = requestInterceptor?.({ headers: {} as Record<string, string> })
    expect(config?.headers?.Authorization).toBe('Bearer token-123')
  })
})

