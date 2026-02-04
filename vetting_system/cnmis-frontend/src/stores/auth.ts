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
    return userPermissions.value.includes(permission) ?? false
  }

  function hasAnyPermission(permissions: string[]): boolean {
    return permissions.some(permission => hasPermission(permission))
  }

  // Permission-based computed properties (recommended)
  // Note: data scoping is still enforced by backend (role/institution logic where needed),
  // but page/action visibility should be permission-driven.
  const canViewAllApplications = computed(() => {
    // OPC roles can see broader sets; keep this as a UI hint.
    return isAdmin.value || hasAnyRole(['opc_data_entry', 'opc_approver'])
  })

  const canEditApplications = computed(() => hasAnyPermission(['create applications', 'edit applications']))

  const canDeleteApplications = computed(() => hasPermission('delete applications'))

  const canAssignOfficers = computed(() => hasPermission('assign applications'))

  // These are kept for compatibility with existing UI logic, but should be phased out.
  const isPoliceOfficer = computed(() => hasPermission('conduct police vetting'))
  const isNisOfficer = computed(() => hasPermission('conduct nis vetting'))
  const isOpcDataEntry = computed(() => hasPermission('create applications'))
  const isOpcApprover = computed(() => hasAnyPermission(['approve applications', 'deny applications']))

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

