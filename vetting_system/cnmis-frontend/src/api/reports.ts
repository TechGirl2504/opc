import api from './index'
import type { AxiosResponse } from 'axios'

export interface ReportParams {
  date_from?: string
  date_to?: string
  status_id?: number
  institution_id?: number
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

