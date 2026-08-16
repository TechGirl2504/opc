import api from './index'
import type { AxiosResponse } from 'axios'

export interface VettingRecord {
  id: number
  application_id: number
  vetting_type: {
    id: number
    name: string
    code: string
  }
  status: {
    id: number
    name: string
    code: string
  }
  recommendation?: {
    id: number
    name: string
    code: string
  }
  findings?: string
  remarks?: string
  notes?: string
  return_reason?: string
  vetting_date?: string
  conducted_by: {
    id: number
    username: string
    email: string
  }
  conducted_at?: string
  completed_at?: string
  created_at: string
  updated_at: string
}

export interface SubmitVettingRequest {
  remarks?: string
  findings?: string
  recommendation_id?: number
  return_reason?: string
  vetting_date?: string
  document?: File
}

export const vettingApi = {
  getPoliceVetting: (applicationId: number): Promise<AxiosResponse> =>
    api.get(`/applications/${applicationId}/vetting/police`),
  
  submitPoliceVetting: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.return_reason) formData.append('return_reason', data.return_reason)
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.post(`/applications/${applicationId}/vetting/police`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  updatePoliceVetting: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.return_reason) formData.append('return_reason', data.return_reason)
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.put(`/applications/${applicationId}/vetting/police`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  getNisVetting: (applicationId: number): Promise<AxiosResponse> =>
    api.get(`/applications/${applicationId}/vetting/nis`),
  
  submitNisVetting: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.return_reason) formData.append('return_reason', data.return_reason)
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.post(`/applications/${applicationId}/vetting/nis`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  updateNisVetting: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.return_reason) formData.append('return_reason', data.return_reason)
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.put(`/applications/${applicationId}/vetting/nis`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },
  
  sendBack: (vettingId: number, reason: string): Promise<AxiosResponse> =>
    api.post(`/vetting/${vettingId}/send-back`, { reason }),

  // Draft and Complete methods
  savePoliceVettingDraft: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.return_reason) formData.append('return_reason', data.return_reason)
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.post(`/applications/${applicationId}/vetting/police/draft`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  completePoliceVetting: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.return_reason) formData.append('return_reason', data.return_reason)
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.post(`/applications/${applicationId}/vetting/police/complete`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  saveNisVettingDraft: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.return_reason) formData.append('return_reason', data.return_reason)
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.post(`/applications/${applicationId}/vetting/nis/draft`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  },

  completeNisVetting: (applicationId: number, data: SubmitVettingRequest): Promise<AxiosResponse> => {
    const formData = new FormData()
    if (data.remarks) formData.append('remarks', data.remarks)
    if (data.findings) formData.append('findings', data.findings)
    if (data.recommendation_id) formData.append('recommendation_id', String(data.recommendation_id))
    if (data.vetting_date) formData.append('vetting_date', data.vetting_date)
    if (data.document) formData.append('document', data.document)
    return api.post(`/applications/${applicationId}/vetting/nis/complete`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
  }
}
