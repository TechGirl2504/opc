import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi, type LoginCredentials, type User } from '@/api/auth'
import router from '@/router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('auth_token') || null)
  const loading = ref(false)
  let ensureUserPromise: Promise<boolean> | null = null

  const isAuthenticated = computed(() => !!token.value)
  const isAdmin = computed(() => user.value?.roles?.includes('admin') ?? false)
  const userRole = computed(() => user.value?.roles?.[0] ?? null)
  const userInstitution = computed(() => user.value?.institution)
  const userPermissions = computed(() => user.value?.permissions || [])

  function clearAuth() {
    token.value = null
    user.value = null
    localStorage.removeItem('auth_token')
  }

  /**
   * Ensure `user` is loaded when a token exists.
   *
   * This prevents "dashboard flashes" on refresh when a stale token is present:
   * we only treat the session as valid after `/auth/user` succeeds.
   */
  async function ensureUserLoaded(): Promise<boolean> {
    if (!token.value) return false
    if (user.value) return true

    if (ensureUserPromise) return ensureUserPromise

    ensureUserPromise = (async () => {
      try {
        const response = await authApi.user()
        if (response.data.success) {
          user.value = response.data.data.user
          return true
        }
      } catch (error) {
        // ignore, handled below
      }

      // Token is missing/expired/invalid.
      clearAuth()
      return false
    })()

    const result = await ensureUserPromise
    ensureUserPromise = null
    return result
  }

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
      clearAuth()
      router.push({ name: 'Login' })
    }
  }

  async function fetchUser() {
    const ok = await ensureUserLoaded()
    if (!ok) {
      // Keep behavior: if something calls fetchUser and it fails, return to login.
      await logout()
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
    ensureUserLoaded,
    hasRole,
    hasAnyRole,
    hasPermission,
    hasAnyPermission
  }
})

