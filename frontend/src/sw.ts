/// <reference lib="webworker" />

import { clientsClaim } from 'workbox-core'
import { precacheAndRoute } from 'workbox-precaching'
import { registerRoute } from 'workbox-routing'
import { CacheFirst, NetworkFirst } from 'workbox-strategies'
import { ExpirationPlugin } from 'workbox-expiration'

declare const self: ServiceWorkerGlobalScope & {
  __WB_MANIFEST: Array<{ url: string; revision: string | null }>
}

clientsClaim()
precacheAndRoute(self.__WB_MANIFEST)

const apiBaseUrl = import.meta.env.VITE_API_BASE_URL || '/api/v1'
const appBasePath = '/cnmis/'

registerRoute(
  ({ url }) => url.origin === self.location.origin && url.pathname.startsWith('/api/'),
  new NetworkFirst({
    cacheName: 'api-cache',
    networkTimeoutSeconds: 10,
    plugins: [
      new ExpirationPlugin({
        maxEntries: 50,
        maxAgeSeconds: 60 * 60 * 24,
      }),
    ],
  }),
)

registerRoute(
  ({ request }) => request.destination === 'image',
  new CacheFirst({
    cacheName: 'images-cache',
    plugins: [
      new ExpirationPlugin({
        maxEntries: 100,
        maxAgeSeconds: 60 * 60 * 24 * 30,
      }),
    ],
  }),
)

self.addEventListener('install', () => {
  self.skipWaiting()
})

self.addEventListener('push', (event) => {
  event.waitUntil(handlePushEvent())
})

self.addEventListener('notificationclick', (event) => {
  event.notification.close()

  const url = (event.notification.data as { url?: string } | undefined)?.url
    || new URL(appBasePath, self.location.origin).toString()

  event.waitUntil(
    (async () => {
      const windowClients = await self.clients.matchAll({
        type: 'window',
        includeUncontrolled: true,
      })

      for (const client of windowClients) {
        if ('focus' in client) {
          const clientUrl = new URL(client.url)
          if (clientUrl.href === url || clientUrl.href.startsWith(url)) {
            return client.focus()
          }
        }
      }

      if (self.clients.openWindow) {
        return self.clients.openWindow(url)
      }

      return null
    })(),
  )
})

async function handlePushEvent(): Promise<void> {
  try {
    const response = await fetch(new URL('/notifications/unread?per_page=1', apiBaseUrl).toString(), {
      credentials: 'include',
      headers: {
        Accept: 'application/json',
      },
    })

    if (!response.ok) {
      await showFallbackNotification()
      return
    }

    const payload = await response.json() as {
      success?: boolean
      data?: {
        count?: number
        notifications?: Array<{
          id: number
          title: string
          message: string
          related_model_id?: number | string | null
          data?: { application_id?: number | string }
        }>
      }
    }

    const notification = payload.data?.notifications?.[0]
    if (!notification) {
      await showFallbackNotification()
      return
    }

    const appId = notification.data?.application_id ?? notification.related_model_id
    const url = typeof appId === 'number' || typeof appId === 'string'
      ? new URL(`${appBasePath}applications/${appId}`, self.location.origin).toString()
      : new URL(appBasePath, self.location.origin).toString()

    await self.registration.showNotification(notification.title, {
      body: notification.message,
      icon: '/cnmis/pwa-192x192.png',
      badge: '/cnmis/pwa-192x192.png',
      data: {
        url,
      },
      tag: `cnmis-${notification.id}`,
    })
  } catch (error) {
    console.error('Push event handling failed:', error)
    await showFallbackNotification()
  }
}

async function showFallbackNotification(): Promise<void> {
  await self.registration.showNotification('CNMIS update', {
    body: 'You have a new workflow update. Open CNMIS to review it.',
    icon: '/cnmis/pwa-192x192.png',
    badge: '/cnmis/pwa-192x192.png',
    data: {
      url: new URL(appBasePath, self.location.origin).toString(),
    },
    tag: 'cnmis-workflow',
  })
}
