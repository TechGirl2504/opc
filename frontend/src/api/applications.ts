import api from './index'
import type { AxiosResponse } from 'axios'

export interface Application {
  id: number
  application_number: string
  full_name: string
  national_id: string
  date_of_birth?: string | null
  phone_number?: string | null
  email?: string | null
  district: string
  traditional_authority: string
  village: string
  requested_name: string
  name_change_reason_id?: number | null
  name_change_reason?: {
    id: number
    name: string
    code: string
    description?: string | null
    order?: number | null
    is_active?: boolean
  } | null
  reason?: string | null
  status: {
    id: number
    name: string
    code: string
  }
  created_by: {
    id: number
    username: string
    email: string
  }
  assigned_police_officer?: {
    id: number
    username: string
    email: string
  }
  assigned_nis_officer?: {
    id: number
    username: string
    email: string
  }
  assigned_opc_approver?: {
    id: number
    username: string
    email: string
  }
  submitted_at?: string
  police_vetting_completed_at?: string
  nis_vetting_completed_at?: string
  decided_at?: string
  approver_send_back_reason?: string
  approver_send_back_at?: string
  data_entry_return_reason?: string
  data_entry_return_at?: string
  allowed_actions?: string[]
  created_at: string
  updated_at: string
}

export interface ApplicationListParams {
  page?: number
  per_page?: number
  search?: string
  status_id?: number
  status?: string
  review_state?: 'open' | 'returned'
  created_by?: number
  assigned_police_officer_id?: number
  assigned_nis_officer_id?: number
  assigned_opc_approver_id?: number
  vetting_type?: 'police' | 'nis'
  vetting_state?: 'active' | 'returned' | 'completed'
  date_from?: string
  date_to?: string
  order_by?: string
  order_dir?: 'asc' | 'desc'
}

export interface RejectReason {
  id: number
  name: string
  code: string
  description?: string | null
  order?: number | null
  is_active?: boolean
}

export interface CreateApplicationRequest {
  full_name: string
  national_id: string
  date_of_birth?: string
  phone_number?: string
  email?: string
  district: string
  traditional_authority: string
  village: string
  requested_name: string
  reason_id?: number | null
  reason?: string
}

export interface UpdateApplicationRequest extends Partial<CreateApplicationRequest> {}

export interface AssignPoliceRequest {
  police_officer_id: number
}

export interface AssignNisRequest {
  nis_officer_id: number
}

export const applicationsApi = {
  list: (params?: ApplicationListParams): Promise<AxiosResponse> =>
    api.get('/applications', { params }),

  getNameChangeReasons: (): Promise<AxiosResponse> =>
    api.get('/name-change-reasons'),

  getRejectReasons: (): Promise<AxiosResponse> =>
    api.get('/reject-reasons'),
  
  create: (data: CreateApplicationRequest): Promise<AxiosResponse> =>
    api.post('/applications', data),
  
  createWithFiles: (formData: FormData): Promise<AxiosResponse> =>
    api.post('/applications', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    }),
  
  get: (id: number): Promise<AxiosResponse> =>
    api.get(`/applications/${id}`),
  
  update: (id: number, data: UpdateApplicationRequest): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/update`, data),

  forwardToAdmin: (id: number): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/forward-to-admin`),
  
  delete: (id: number): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/delete`),
  
  assignPolice: (id: number, data: AssignPoliceRequest): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/assign-police`, data),
  
  assignNis: (id: number, data: AssignNisRequest): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/assign-nis`, data),

  forwardToApproval: (id: number): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/forward-to-approval`),

  sendBackToAdmin: (id: number, reason: string): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/send-back-to-admin`, { reason }),

  sendBackToDataEntry: (id: number, reason: string): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/send-back-to-data-entry`, { reason }),

  handleApproverSendBack: (id: number, action: 'send_to_police' | 'send_to_nis', reason?: string): Promise<AxiosResponse> =>
    api.post(`/applications/${id}/handle-approver-send-back`, { action, reason }),
  
  statusHistory: (id: number): Promise<AxiosResponse> =>
    api.get(`/applications/${id}/status`)
}
