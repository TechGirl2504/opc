import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi, type Notification } from '@/api/notifications'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const loading = ref(false)

  const unreadNotifications = computed(() => 
    notifications.value.filter(n => !n.is_read)
  )

  async function fetchNotifications(params?: { page?: number; per_page?: number; is_read?: boolean }) {
    loading.value = true
    try {
      const response = await notificationsApi.list(params)
      if (response.data.success) {
        notifications.value = response.data.data
        unreadCount.value = response.data.meta?.unread_count || 0
      }
    } catch (error) {
      console.error('Fetch notifications error:', error)
    } finally {
      loading.value = false
    }
  }

  async function fetchUnread() {
    try {
      const response = await notificationsApi.unread()
      if (response.data.success) {
        unreadCount.value = response.data.data.count
        return response.data.data.notifications
      }
    } catch (error) {
      console.error('Fetch unread error:', error)
    }
  }

  async function markAsRead(id: number) {
    try {
      const response = await notificationsApi.markAsRead(id)
      if (response.data.success) {
        const notification = notifications.value.find(n => n.id === id)
        if (notification) {
          notification.is_read = true
          notification.read_at = response.data.data?.read_at
          unreadCount.value = Math.max(0, unreadCount.value - 1)
        }
      }
    } catch (error) {
      console.error('Mark as read error:', error)
    }
  }

  async function markAllAsRead() {
    try {
      const response = await notificationsApi.markAllAsRead()
      if (response.data.success) {
        notifications.value.forEach(n => {
          n.is_read = true
        })
        unreadCount.value = 0
      }
    } catch (error) {
      console.error('Mark all as read error:', error)
    }
  }

  return {
    notifications,
    unreadCount,
    loading,
    unreadNotifications,
    fetchNotifications,
    fetchUnread,
    markAsRead,
    markAllAsRead
  }
})

