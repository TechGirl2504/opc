import api from './index'
import type { AxiosResponse } from 'axios'

export interface Document {
  id: number
  application_id: number
  document_type: {
    id: number
    name: string
    code: string
  }
  file_name: string
  file_path: string
  file_size: number
  mime_type: string
  uploaded_by: {
    id: number
    username: string
    email: string
  }
  created_at: string
  updated_at: string
}

export interface UploadDocumentRequest {
  document_type_id: number
  file: File
}

export const documentsApi = {
  list: (applicationId: number): Promise<AxiosResponse> =>
    api.get(`/applications/${applicationId}/documents`),
  
  upload: (applicationId: number, formData: FormData): Promise<AxiosResponse> =>
    api.post(`/applications/${applicationId}/documents`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    }),
  
  get: (id: number): Promise<AxiosResponse> =>
    api.get(`/documents/${id}`),
  
  download: (id: number): Promise<AxiosResponse<Blob>> =>
    api.get(`/documents/${id}/download`, { responseType: 'blob' }),
  
  preview: (id: number): Promise<AxiosResponse<Blob>> =>
    api.get(`/documents/${id}/preview`, { responseType: 'blob' }),
  
  delete: (id: number): Promise<AxiosResponse> =>
    api.post(`/documents/${id}/delete`)
}
