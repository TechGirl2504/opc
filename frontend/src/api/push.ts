import api from './index'
import type { AxiosResponse } from 'axios'

export interface PushSubscriptionKeys {
  p256dh: string
  auth: string
}

export interface PushSubscriptionPayload {
  endpoint: string
  keys: PushSubscriptionKeys
  expirationTime?: number | null
  content_encoding?: string | null
  user_agent?: string | null
}

export interface PushSubscriptionRecord {
  id: number
  user_id: number
  endpoint: string
  p256dh: string
  auth_key: string
  content_encoding: string
  user_agent?: string | null
  last_seen_at?: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export const pushApi = {
  list: (): Promise<AxiosResponse<{ success: boolean; data: PushSubscriptionRecord[] }>> =>
    api.get('/push-subscriptions'),

  subscribe: (payload: PushSubscriptionPayload): Promise<AxiosResponse> =>
    api.post('/push-subscriptions', payload),

  unsubscribe: (endpoint: string): Promise<AxiosResponse> =>
    api.post('/push-subscriptions/delete', { endpoint }),
}
