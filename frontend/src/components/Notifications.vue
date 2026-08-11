<template>
  <v-menu location="bottom end" offset-y>
    <template v-slot:activator="{ props }">
      <v-badge
        :content="unreadCount"
        :model-value="unreadCount > 0"
        color="error"
        overlap
      >
        <v-btn
          icon="mdi-bell"
          v-bind="props"
          variant="text"
        ></v-btn>
      </v-badge>
    </template>
    <v-card class="notifications-menu" max-height="500">
      <v-card-title class="pb-2">
        <div class="d-flex align-start justify-space-between gap-3 w-100">
          <div>
            <div class="text-h6 font-weight-medium">Notifications</div>
            <div class="text-caption text-medium-emphasis">
              Workflow alerts linked to the current application record
            </div>
          </div>

          <div class="d-flex flex-wrap justify-end ga-2">
            <v-btn
              v-if="pushActionVisible"
              size="small"
              variant="text"
              :disabled="pushBusy || !pushSupported"
              :loading="pushBusy"
              @click="togglePushNotifications"
            >
              {{ pushActionLabel }}
            </v-btn>

            <v-btn
              v-if="unreadCount > 0"
              size="small"
              variant="text"
              @click="markAllAsRead"
              :loading="markingAllRead"
            >
              Mark all read
            </v-btn>
          </div>
        </div>

        <div
          v-if="pushStatusText"
          class="text-caption text-medium-emphasis mt-1"
        >
          {{ pushStatusText }}
        </div>
      </v-card-title>
      <v-divider></v-divider>
      <v-list v-if="notifications.length > 0" class="overflow-y-auto" style="max-height: 400px">
        <v-list-item
          v-for="notification in notifications"
          :key="notification.id"
          :class="{ 'bg-grey-lighten-4': !notification.is_read }"
          @click="handleNotificationClick(notification)"
        >
          <template v-slot:prepend>
            <v-icon
              :color="getNotificationColor(notification.type)"
              :icon="getNotificationIcon(notification.type)"
            ></v-icon>
          </template>
          <v-list-item-title>{{ notification.title }}</v-list-item-title>
          <v-list-item-subtitle>{{ notification.message }}</v-list-item-subtitle>
          <v-list-item-subtitle class="text-caption">
            {{ formatDate(notification.created_at) }}
          </v-list-item-subtitle>
          <template v-slot:append>
            <v-btn
              v-if="!notification.is_read"
              icon="mdi-check"
              size="small"
              variant="text"
              @click.stop="markAsRead(notification.id)"
            ></v-btn>
          </template>
        </v-list-item>
      </v-list>
      <v-card-text v-else class="text-center text-grey">
        No notifications
      </v-card-text>
    </v-card>
  </v-menu>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { notificationsApi, type Notification } from '@/api/notifications'
import { pushApi } from '@/api/push'
import { format } from 'date-fns'
import { base64UrlToUint8Array, supportsBrowserPush, uint8ArrayToBase64Url } from '@/utils/webPush'

import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const toast = useToast()
const auth = useAuthStore()

const notifications = ref<Notification[]>([])
const loading = ref(false)
const markingAllRead = ref(false)
const pushBusy = ref(false)
const pushSupported = ref(supportsBrowserPush() && !import.meta.env.DEV)
const pushSubscribed = ref(false)
const pushPermission = ref<NotificationPermission>(
  typeof window !== 'undefined' && 'Notification' in window ? Notification.permission : 'denied',
)

const vapidPublicKey = import.meta.env.VITE_WEB_PUSH_VAPID_PUBLIC_KEY?.trim() || ''

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.is_read).length
})

const pushActionVisible = computed(() => {
  return auth.isAuthenticated && pushSupported.value && vapidPublicKey !== ''
})

const pushActionLabel = computed(() => {
  if (!pushSupported.value) {
    return 'Alerts unavailable'
  }

  if (pushPermission.value === 'denied') {
    return 'Alerts blocked'
  }

  return pushSubscribed.value ? 'Disable alerts' : 'Enable alerts'
})

const pushStatusText = computed(() => {
  if (!auth.isAuthenticated || !pushSupported.value) {
    return ''
  }

  if (pushPermission.value === 'denied') {
    return 'Browser notifications are blocked for this site. Re-enable them in your browser settings.'
  }

  return pushSubscribed.value
    ? 'Browser alerts are enabled for this device.'
    : 'Enable browser alerts to receive workflow updates even when CNMIS is not open.'
})

let refreshInterval: number | null = null

function stopRefresh() {
  if (refreshInterval) {
    clearInterval(refreshInterval)
    refreshInterval = null
  }
}

function startRefresh() {
  stopRefresh()
  refreshInterval = window.setInterval(() => {
    loadNotifications()
  }, 30000)
}

async function getCurrentSubscription() {
  if (!pushSupported.value) {
    return null
  }

  const registration = await navigator.serviceWorker.ready
  return registration.pushManager.getSubscription()
}

async function refreshPushState() {
  pushSupported.value = supportsBrowserPush() && !import.meta.env.DEV

  if (import.meta.env.DEV) {
    pushSubscribed.value = false
    return
  }

  if (!auth.isAuthenticated || !pushSupported.value || vapidPublicKey === '') {
    pushSubscribed.value = false
    return
  }

  try {
    const subscription = await getCurrentSubscription()
    pushSubscribed.value = !!subscription
    pushPermission.value = Notification.permission
  } catch (error) {
    console.error('Failed to refresh push state:', error)
    pushSubscribed.value = false
  }
}

function formatDate(date: string) {
  return format(new Date(date), 'MMM dd, HH:mm')
}

function getNotificationColor(type?: string) {
  const colors: Record<string, string> = {
    application_created: 'primary',
    application_updated: 'info',
    vetting_completed: 'success',
    decision_made: 'warning',
    document_uploaded: 'info'
  }
  return colors[type || ''] || 'default'
}

function getNotificationIcon(type?: string) {
  const icons: Record<string, string> = {
    application_created: 'mdi-file-plus',
    application_updated: 'mdi-file-edit',
    vetting_completed: 'mdi-check-circle',
    decision_made: 'mdi-gavel',
    document_uploaded: 'mdi-file-upload'
  }
  return icons[type || ''] || 'mdi-bell'
}

async function loadNotifications() {
  // Notifications endpoints require auth; avoid noisy 401s before login.
  if (!auth.isAuthenticated) {
    notifications.value = []
    return
  }

  loading.value = true
  try {
    const response = await notificationsApi.list()
    if (response.data.success) {
      notifications.value = response.data.data || []
    }
  } catch (err: any) {
    // 401 is expected if token is missing/expired; interceptor handles redirect.
    if (err?.response?.status !== 401) {
      console.error('Failed to load notifications:', err)
    }
  } finally {
    loading.value = false
  }
}

async function markAsRead(id: number) {
  try {
    const response = await notificationsApi.markAsRead(id)
    if (response.data.success) {
      const notification = notifications.value.find(n => n.id === id)
      if (notification) {
        notification.is_read = true
      }
    }
  } catch (err: any) {
    toast.error('Failed to mark notification as read')
  }
}

async function markAllAsRead() {
  markingAllRead.value = true
  try {
    const response = await notificationsApi.markAllAsRead()
    if (response.data.success) {
      notifications.value.forEach(n => n.is_read = true)
      toast.success('All notifications marked as read')
    }
  } catch (err: any) {
    toast.error('Failed to mark all as read')
  } finally {
    markingAllRead.value = false
  }
}

async function enableBrowserAlerts() {
  if (!pushSupported.value || vapidPublicKey === '') {
    toast.error('Browser alerts are not supported in this browser')
    return
  }

  pushBusy.value = true
  try {
    const permission = await Notification.requestPermission()
    pushPermission.value = permission

    if (permission !== 'granted') {
      toast.info('Browser alerts were not enabled')
      return
    }

    const registration = await navigator.serviceWorker.ready
    const existingSubscription = await registration.pushManager.getSubscription()
    const subscription = existingSubscription || await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: base64UrlToUint8Array(vapidPublicKey),
    })

    await pushApi.subscribe({
      endpoint: subscription.endpoint,
      keys: {
        p256dh: subscription.getKey('p256dh')
          ? uint8ArrayToBase64Url(subscription.getKey('p256dh') as ArrayBuffer)
          : '',
        auth: subscription.getKey('auth')
          ? uint8ArrayToBase64Url(subscription.getKey('auth') as ArrayBuffer)
          : '',
      },
      expirationTime: subscription.expirationTime,
      content_encoding: 'aes128gcm',
      user_agent: navigator.userAgent,
    })

    pushSubscribed.value = true
    toast.success('Browser alerts enabled')
  } catch (error) {
    console.error('Failed to enable browser alerts:', error)
    toast.error('Failed to enable browser alerts')
  } finally {
    pushBusy.value = false
  }
}

async function disableBrowserAlerts() {
  if (!pushSupported.value) {
    return
  }

  pushBusy.value = true
  try {
    const subscription = await getCurrentSubscription()
    if (!subscription) {
      pushSubscribed.value = false
      return
    }

    await pushApi.unsubscribe(subscription.endpoint)
    await subscription.unsubscribe()

    pushSubscribed.value = false
    toast.info('Browser alerts disabled')
  } catch (error) {
    console.error('Failed to disable browser alerts:', error)
    toast.error('Failed to disable browser alerts')
  } finally {
    pushBusy.value = false
  }
}

async function togglePushNotifications() {
  if (!pushSupported.value) {
    return
  }

  if (pushSubscribed.value) {
    await disableBrowserAlerts()
    return
  }

  await enableBrowserAlerts()
}

function handleNotificationClick(notification: Notification) {
  if (!notification.is_read) {
    markAsRead(notification.id)
  }
  
  // Navigate to the application referenced by the notification.
  const appId =
    (notification.data as any)?.application_id ??
    notification.related_model_id

  if (typeof appId === 'number' || typeof appId === 'string') {
    router.push({ name: 'ApplicationDetail', params: { id: String(appId) } })
  }
}

onMounted(() => {
  if (auth.isAuthenticated) {
    loadNotifications()
    startRefresh()
    refreshPushState()
  }
})

onUnmounted(() => {
  stopRefresh()
})

watch(
  () => auth.isAuthenticated,
  (isAuth) => {
    if (isAuth) {
      loadNotifications()
      startRefresh()
      refreshPushState()
    } else {
      notifications.value = []
      stopRefresh()
      pushSubscribed.value = false
    }
  },
)
</script>

<style scoped>
.notifications-menu {
  width: min(380px, calc(100vw - 24px));
  min-width: min(350px, calc(100vw - 24px));
  max-width: calc(100vw - 24px);
}
</style>

<style scoped>
.v-list-item {
  cursor: pointer;
}

@media (max-width: 600px) {
  .notifications-menu {
    width: calc(100vw - 16px);
    min-width: 0;
    max-width: calc(100vw - 16px);
  }
}
</style>
