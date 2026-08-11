export function base64UrlToUint8Array(base64Url: string): ArrayBuffer {
  const remainder = base64Url.length % 4
  const padding = remainder === 0 ? '' : '='.repeat(4 - remainder)
  const normalized = (base64Url + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/')

  const raw = atob(normalized)
  const output = new Uint8Array(raw.length)

  for (let i = 0; i < raw.length; i++) {
    output[i] = raw.charCodeAt(i)
  }

  return output.buffer.slice(0)
}

export function uint8ArrayToBase64Url(value: Uint8Array | ArrayBuffer): string {
  const bytes = value instanceof Uint8Array ? value : new Uint8Array(value)
  let binary = ''

  for (let i = 0; i < bytes.length; i++) {
    binary += String.fromCharCode(bytes[i] ?? 0)
  }

  return btoa(binary)
    .replace(/\+/g, '-')
    .replace(/\//g, '_')
    .replace(/=+$/g, '')
}

export function supportsBrowserPush(): boolean {
  return typeof window !== 'undefined'
    && 'serviceWorker' in navigator
    && 'PushManager' in window
    && 'Notification' in window
    && typeof Notification.requestPermission === 'function'
}
