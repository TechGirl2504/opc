import api from './index'
import type { AxiosResponse } from 'axios'

export interface LoginCredentials {
  username: string
  password: string
}

export interface AuthResponse {
  success: boolean
  data: {
    token: string
    user: User
  }
}

export interface User {
  id: number
  username: string
  email: string
  roles: string[]
  role?: string
  permissions?: string[]
  institution?: string | {
    id: number
    name: string
    code: string
  }
  institution_id?: number
  profile_picture?: string
  is_active: boolean
  last_login_at?: string
}

export interface ForgotPasswordRequest {
  email: string
}

export interface ResetPasswordRequest {
  email: string
  token: string
  password: string
  password_confirmation: string
}

export interface UpdatePasswordRequest {
  current_password: string
  password: string
  password_confirmation: string
}

export const authApi = {
  login: (credentials: LoginCredentials): Promise<AxiosResponse<AuthResponse>> =>
    api.post('/auth/login', credentials),
  
  logout: (): Promise<AxiosResponse> =>
    api.post('/auth/logout'),
  
  user: (): Promise<AxiosResponse<{ success: boolean; data: { user: User } }>> =>
    api.get('/auth/user'),
  
  refresh: (): Promise<AxiosResponse> =>
    api.post('/auth/refresh'),
  
  forgotPassword: (data: ForgotPasswordRequest): Promise<AxiosResponse> =>
    api.post('/auth/forgot-password', data),
  
  resetPassword: (data: ResetPasswordRequest): Promise<AxiosResponse> =>
    api.post('/auth/reset-password', data),
  
  updatePassword: (data: UpdatePasswordRequest): Promise<AxiosResponse> =>
    api.post('/auth/update-password', data)
}

