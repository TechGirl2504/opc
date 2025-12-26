import api from './index'
import type { AxiosResponse } from 'axios'

export interface Decision {
  id: number
  application_id: number
  decision_type: {
    id: number
    name: string
    code: string
  }
  decision_value: {
    id: number
    name: string
    code: string
  }
  reason?: string
  decided_by: {
    id: number
    username: string
    email: string
  }
  decided_at: string
  created_at: string
  updated_at: string
}

export interface ApproveApplicationRequest {
  notes?: string
}

export interface DenyApplicationRequest {
  reason: string
}

export const decisionsApi = {
  approve: (applicationId: number, data: ApproveApplicationRequest): Promise<AxiosResponse> =>
    api.post(`/applications/${applicationId}/approve`, data),
  
  deny: (applicationId: number, data: DenyApplicationRequest): Promise<AxiosResponse> =>
    api.post(`/applications/${applicationId}/deny`, data),
  
  history: (applicationId: number): Promise<AxiosResponse> =>
    api.get(`/applications/${applicationId}/decisions`)
}

