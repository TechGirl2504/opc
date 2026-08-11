import { describe, it, expect, vi, beforeEach } from 'vitest'

type AxiosCreateConfig = { baseURL?: string; headers?: Record<string, string>; withCredentials?: boolean; withXSRFToken?: boolean }
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

beforeEach(() => {
  createMock.mockClear()
  requestInterceptor = undefined
  installMockStorage()
  window.sessionStorage.clear()
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

  it('falls back to same-origin api base URL when unset', async () => {
    await import('@/api')

    expect(createMock).toHaveBeenCalledTimes(1)
    expect(createMock.mock.calls[0]?.[0]?.baseURL).toBe('/api/v1')
  })

  it('enables XSRF forwarding for cross-origin SPA requests', async () => {
    await import('@/api')

    expect(createMock.mock.calls[0]?.[0]?.withXSRFToken).toBe(true)
  })

  it('does not inject a bearer token header', async () => {
    await import('@/api')

    expect(requestInterceptor).toBeTypeOf('function')
    const config = requestInterceptor?.({ headers: {} as Record<string, string> })
    expect(config?.headers?.Authorization).toBeUndefined()
  })
})
