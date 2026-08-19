import axios, { type AxiosResponse } from 'axios'
import api from './index'

export interface LoginCredentials {
  username: string
  password: string
}

export interface AuthResponse {
  success: boolean
  data: {
    user: User
  }
}

function getSanctumBaseUrl(): string {
  const apiBase = api.defaults.baseURL ?? '/api/v1'

  if (/^https?:\/\//i.test(apiBase)) {
    const url = new URL(apiBase)
    url.pathname = url.pathname.replace(/\/api\/v1\/?$/, '/sanctum/csrf-cookie')
    return url.toString()
  }

  // Preserve a deployment prefix such as /cnmis-api when the API is served
  // from a subdirectory rather than the domain root.
  return apiBase.replace(/\/api\/v1\/?$/, '/sanctum/csrf-cookie')
}

const sanctumApi = axios.create({
  baseURL: getSanctumBaseUrl(),
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

export interface User {
  id: number
  username: string
  email: string
  roles: string[]
  role?: string
  active_role?: string | null
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
  csrfCookie: (): Promise<AxiosResponse> =>
    sanctumApi.get(''),

  login: (credentials: LoginCredentials): Promise<AxiosResponse<AuthResponse>> =>
    api.post('/auth/login', credentials),
  
  logout: (): Promise<AxiosResponse> =>
    api.post('/auth/logout'),
  
  user: (): Promise<AxiosResponse<{ success: boolean; data: { user: User } }>> =>
    api.get('/auth/user'),

  selectActiveRole: (role: string): Promise<AxiosResponse<{
    success: boolean
    data: { active_role: string; roles: string[]; permissions: string[] }
  }>> => api.post('/auth/active-role', { role }),
  
  refresh: (): Promise<AxiosResponse> =>
    api.post('/auth/refresh'),
  
  forgotPassword: (data: ForgotPasswordRequest): Promise<AxiosResponse> =>
    api.post('/auth/forgot-password', data),
  
  resetPassword: (data: ResetPasswordRequest): Promise<AxiosResponse> =>
    api.post('/auth/reset-password', data),
  
  updatePassword: (data: UpdatePasswordRequest): Promise<AxiosResponse> =>
    api.post('/auth/update-password', data)
}
