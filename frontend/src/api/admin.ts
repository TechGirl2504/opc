import api from './index'
import type { AxiosResponse } from 'axios'

export interface Institution {
  id: number
  name: string
  code: string
  description?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface ApplicationStatus {
  id: number
  name: string
  code: string
  description?: string
  order: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface VettingType {
  id: number
  name: string
  code: string
  description?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface DocumentType {
  id: number
  name: string
  code: string
  description?: string
  max_size_mb?: number
  allowed_mimes?: string
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface NameChangeReason {
  id: number
  name: string
  code: string
  description?: string
  order: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface RejectReason {
  id: number
  name: string
  code: string
  description?: string
  order: number
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface Role {
  id: number
  name: string
  display_name?: string | null
  guard_name: string
  permissions?: Permission[]
  created_at: string
  updated_at: string
}

export interface Permission {
  id: number
  name: string
  display_name?: string | null
  guard_name: string
}

export interface DecisionValue {
  id: number
  name: string
  code: string
  description?: string
  is_active: boolean
}

export const adminApi = {
  // Institutions
  getInstitutions: (): Promise<AxiosResponse> =>
    api.get('/admin/institutions'),
  
  createInstitution: (data: Partial<Institution>): Promise<AxiosResponse> =>
    api.post('/admin/institutions', data),
  
  updateInstitution: (id: number, data: Partial<Institution>): Promise<AxiosResponse> =>
    api.post(`/admin/institutions/${id}/update`, data),
  
  deleteInstitution: (id: number): Promise<AxiosResponse> =>
    api.post(`/admin/institutions/${id}/delete`),

  // Application Statuses
  getApplicationStatuses: (): Promise<AxiosResponse> =>
    api.get('/admin/application-statuses'),
  
  createApplicationStatus: (data: Partial<ApplicationStatus>): Promise<AxiosResponse> =>
    api.post('/admin/application-statuses', data),
  
  updateApplicationStatus: (id: number, data: Partial<ApplicationStatus>): Promise<AxiosResponse> =>
    api.post(`/admin/application-statuses/${id}/update`, data),
  
  deleteApplicationStatus: (id: number): Promise<AxiosResponse> =>
    api.post(`/admin/application-statuses/${id}/delete`),

  // Vetting Types
  getVettingTypes: (): Promise<AxiosResponse> =>
    api.get('/admin/vetting-types'),
  
  createVettingType: (data: Partial<VettingType>): Promise<AxiosResponse> =>
    api.post('/admin/vetting-types', data),
  
  updateVettingType: (id: number, data: Partial<VettingType>): Promise<AxiosResponse> =>
    api.post(`/admin/vetting-types/${id}/update`, data),
  
  deleteVettingType: (id: number): Promise<AxiosResponse> =>
    api.post(`/admin/vetting-types/${id}/delete`),

  // Document Types
  getDocumentTypes: (): Promise<AxiosResponse> =>
    api.get('/admin/document-types'),
  
  createDocumentType: (data: Partial<DocumentType>): Promise<AxiosResponse> =>
    api.post('/admin/document-types', data),
  
  updateDocumentType: (id: number, data: Partial<DocumentType>): Promise<AxiosResponse> =>
    api.post(`/admin/document-types/${id}/update`, data),
  
  deleteDocumentType: (id: number): Promise<AxiosResponse> =>
    api.post(`/admin/document-types/${id}/delete`),

  // Name Change Reasons
  getNameChangeReasons: (): Promise<AxiosResponse> =>
    api.get('/admin/name-change-reasons'),

  createNameChangeReason: (data: Partial<NameChangeReason>): Promise<AxiosResponse> =>
    api.post('/admin/name-change-reasons', data),

  updateNameChangeReason: (id: number, data: Partial<NameChangeReason>): Promise<AxiosResponse> =>
    api.post(`/admin/name-change-reasons/${id}/update`, data),

  deleteNameChangeReason: (id: number): Promise<AxiosResponse> =>
    api.post(`/admin/name-change-reasons/${id}/delete`),

  // Reject Reasons
  getRejectReasons: (): Promise<AxiosResponse> =>
    api.get('/admin/reject-reasons'),

  createRejectReason: (data: Partial<RejectReason>): Promise<AxiosResponse> =>
    api.post('/admin/reject-reasons', data),

  updateRejectReason: (id: number, data: Partial<RejectReason>): Promise<AxiosResponse> =>
    api.post(`/admin/reject-reasons/${id}/update`, data),

  deleteRejectReason: (id: number): Promise<AxiosResponse> =>
    api.post(`/admin/reject-reasons/${id}/delete`),

  // Roles
  getRoles: (): Promise<AxiosResponse> =>
    api.get('/admin/roles'),
  
  createRole: (data: Partial<Role>): Promise<AxiosResponse> =>
    api.post('/admin/roles', data),
  
  updateRole: (id: number, data: Partial<Role>): Promise<AxiosResponse> =>
    api.post(`/admin/roles/${id}/update`, data),
  
  deleteRole: (id: number): Promise<AxiosResponse> =>
    api.post(`/admin/roles/${id}/delete`),
  
  getRolePermissions: (id: number): Promise<AxiosResponse> =>
    api.get(`/admin/roles/${id}/permissions`),
  
  getAllPermissions: (): Promise<AxiosResponse> =>
    api.get('/admin/permissions'),
  
  // Decision Values
  getDecisionValues: (): Promise<AxiosResponse> =>
    api.get('/admin/decision-values')
}
