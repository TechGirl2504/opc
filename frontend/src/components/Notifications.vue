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
    <v-card min-width="350" max-height="500">
      <v-card-title class="d-flex justify-space-between align-center">
        <span>Notifications</span>
        <v-btn
          v-if="unreadCount > 0"
          size="small"
          variant="text"
          @click="markAllAsRead"
          :loading="markingAllRead"
        >
          Mark all read
        </v-btn>
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
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useToast } from 'vue-toastification'
import { notificationsApi, type Notification } from '@/api/notifications'
import { format } from 'date-fns'

const router = useRouter()
const toast = useToast()

const notifications = ref<Notification[]>([])
const loading = ref(false)
const markingAllRead = ref(false)

const unreadCount = computed(() => {
  return notifications.value.filter(n => !n.is_read).length
})

let refreshInterval: number | null = null

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
  loading.value = true
  try {
    const response = await notificationsApi.list()
    if (response.data.success) {
      notifications.value = response.data.data || []
    }
  } catch (err: any) {
    console.error('Failed to load notifications:', err)
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

function handleNotificationClick(notification: Notification) {
  if (!notification.is_read) {
    markAsRead(notification.id)
  }
  
  // Navigate based on notification type
  const appId = (notification.data as any)?.application_id
  if (typeof appId === 'number' || typeof appId === 'string') {
    router.push({ name: 'ApplicationDetail', params: { id: String(appId) } })
  }
}

onMounted(() => {
  loadNotifications()
  // Refresh notifications every 30 seconds
  refreshInterval = window.setInterval(() => {
    loadNotifications()
  }, 30000)
})

onUnmounted(() => {
  if (refreshInterval) {
    clearInterval(refreshInterval)
  }
})
</script>

<style scoped>
.v-list-item {
  cursor: pointer;
}
</style>

