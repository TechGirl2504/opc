import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi, type LoginCredentials, type User } from '@/api/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token') || null)
  const loading = ref(false)

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.roles?.includes('admin') ?? false)
  const userRole = computed(() => user.value?.roles?.[0] ?? null)
  const userInstitution = computed(() => user.value?.institution)
  const userPermissions = computed(() => user.value?.permissions || [])

  async function login(credentials: LoginCredentials) {
    loading.value = true
    try {
      const response = await authApi.login(credentials)
      if (response.data.success) {
        token.value = response.data.data.token
        user.value = response.data.data.user
        localStorage.setItem('auth_token', token.value)
        return response.data
      }
    } catch (error) {
      throw error
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await authApi.logout()
    } catch (error) {
      console.error('Logout error:', error)
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('auth_token')
      router.push('/login')
    }
  }

  async function fetchUser() {
    try {
      const response = await authApi.user()
      if (response.data.success) {
        user.value = response.data.data.user
      }
    } catch (error) {
      console.error('Fetch user error:', error)
      logout()
    }
  }

  function hasRole(role: string): boolean {
    return user.value?.roles?.includes(role) ?? false
  }

  function hasAnyRole(roles: string[]): boolean {
    return roles.some(role => hasRole(role))
  }

  function hasPermission(permission: string): boolean {
    if (isAdmin.value) return true // Admin has all permissions
    return userPermissions.value.includes(permission) ?? false
  }

  function hasAnyPermission(permissions: string[]): boolean {
    if (isAdmin.value) return true // Admin has all permissions
    return permissions.some(permission => hasPermission(permission))
  }

  // Role-specific computed properties
  const isPoliceOfficer = computed(() => hasRole('police_officer'))
  const isNisOfficer = computed(() => hasRole('nis_officer'))
  const isOpcDataEntry = computed(() => hasRole('opc_data_entry'))
  const isOpcApprover = computed(() => hasRole('opc_approver'))

  // Check if user can view all applications or only assigned ones
  const canViewAllApplications = computed(() => {
    return isAdmin.value || isOpcDataEntry.value || isOpcApprover.value
  })

  // Check if user can edit applications
  const canEditApplications = computed(() => {
    return isAdmin.value || isOpcDataEntry.value
  })

  // Check if user can delete applications
  const canDeleteApplications = computed(() => {
    return isAdmin.value
  })

  // Check if user can assign officers
  const canAssignOfficers = computed(() => {
    return isAdmin.value || isOpcDataEntry.value
  })

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isAdmin,
    userRole,
    userInstitution,
    userPermissions,
    isPoliceOfficer,
    isNisOfficer,
    isOpcDataEntry,
    isOpcApprover,
    canViewAllApplications,
    canEditApplications,
    canDeleteApplications,
    canAssignOfficers,
    login,
    logout,
    fetchUser,
    hasRole,
    hasAnyRole,
    hasPermission,
    hasAnyPermission
  }
})

