import api from './index'
import type { AxiosResponse } from 'axios'

export interface Notification {
  id: number
  type: string
  title: string
  message: string
  related_model_type?: string | null
  related_model_id?: number | string | null
  data?: {
    application_id?: number | string
    [key: string]: unknown
  }
  is_read: boolean
  read_at?: string
  created_at: string
  updated_at: string
}

export interface NotificationListParams {
  page?: number
  per_page?: number
  is_read?: boolean
}

export const notificationsApi = {
  list: (params?: NotificationListParams): Promise<AxiosResponse> =>
    api.get('/notifications', { params }),
  
  unread: (): Promise<AxiosResponse<{ success: boolean; data: { count: number; notifications: Notification[] } }>> =>
    api.get('/notifications/unread'),
  
  markAsRead: (id: number): Promise<AxiosResponse> =>
    api.post(`/notifications/${id}/read`),
  
  markAllAsRead: (): Promise<AxiosResponse> =>
    api.post('/notifications/read-all')
}
