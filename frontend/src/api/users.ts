import api from './index'
import type { AxiosResponse } from 'axios'
import type { User } from './auth'

export interface UserListParams {
  page?: number
  per_page?: number
  search?: string
  role?: string
  institution_id?: number
  is_active?: boolean
}

export interface CreateUserRequest {
  username: string
  email: string
  password: string
  password_confirmation: string
  institution_id: number
  roles: string[]
}

export interface UpdateUserRequest {
  username?: string
  email?: string
  password?: string
  password_confirmation?: string
  institution_id?: number
  roles?: string[]
  is_active?: boolean
}

export const usersApi = {
  list: (params?: UserListParams): Promise<AxiosResponse> =>
    api.get('/users', { params }),
  
  create: (data: CreateUserRequest): Promise<AxiosResponse> =>
    api.post('/users', data),
  
  get: (id: number): Promise<AxiosResponse<{ success: boolean; data: { user: User } }>> =>
    api.get(`/users/${id}`),
  
  update: (id: number, data: UpdateUserRequest): Promise<AxiosResponse> =>
    api.put(`/users/${id}`, data),
  
  delete: (id: number): Promise<AxiosResponse> =>
    api.delete(`/users/${id}`),
  
  activate: (id: number): Promise<AxiosResponse> =>
    api.post(`/users/${id}/activate`),
  
  deactivate: (id: number): Promise<AxiosResponse> =>
    api.post(`/users/${id}/deactivate`)
}

