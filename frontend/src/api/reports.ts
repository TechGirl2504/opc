import api from './index'
import type { AxiosResponse } from 'axios'

export interface ReportParams {
  search?: string
  date_from?: string
  date_to?: string
  status_id?: number
  institution_id?: number
  assigned_opc_approver_id?: number
  user_id?: number
  action?: string
  model_type?: string
  model_id?: number
  page?: number
  per_page?: number
}

export const reportsApi = {
  dashboard: (params?: ReportParams): Promise<AxiosResponse> =>
    api.get('/reports/dashboard', { params }),
  
  applications: (params?: ReportParams): Promise<AxiosResponse> =>
    api.get('/reports/applications', { params }),
  
  vetting: (params?: ReportParams): Promise<AxiosResponse> =>
    api.get('/reports/vetting', { params }),
  
  audit: (params?: ReportParams): Promise<AxiosResponse> =>
    api.get('/reports/audit', { params }),
  
  export: (params?: ReportParams): Promise<AxiosResponse<Blob>> =>
    api.get('/reports/export', { params, responseType: 'blob' })
}
