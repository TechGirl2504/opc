import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: { layout: 'auth', requiresGuest: true }
  },
  {
    path: '/forgot-password',
    name: 'ForgotPassword',
    component: () => import('@/views/auth/ForgotPassword.vue'),
    meta: { layout: 'auth', requiresGuest: true }
  },
  {
    path: '/reset-password',
    name: 'ResetPassword',
    component: () => import('@/views/auth/ResetPassword.vue'),
    meta: { layout: 'auth', requiresGuest: true }
  },
  {
    path: '/',
    redirect: { name: 'Dashboard' }
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/applications',
    name: 'Applications',
    component: () => import('@/views/applications/List.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/applications/create',
    name: 'CreateApplication',
    component: () => import('@/views/applications/Create.vue'),
    meta: { 
      requiresAuth: true,
      permissions: ['create applications']
    }
  },
  {
    path: '/applications/:id/edit',
    name: 'EditApplication',
    component: () => import('@/views/applications/Create.vue'),
    meta: { 
      requiresAuth: true,
      permissions: ['edit applications']
    }
  },
  {
    path: '/applications/:id',
    name: 'ApplicationDetail',
    component: () => import('@/views/applications/Show.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/vetting/police',
    name: 'PoliceVettingList',
    component: () => import('@/views/vetting/PoliceVettingList.vue'),
    meta: { 
      requiresAuth: true,
      permissions: ['conduct police vetting']
    }
  },
  {
    path: '/applications/:id/vetting/police',
    name: 'PoliceVetting',
    component: () => import('@/views/vetting/PoliceVetting.vue'),
    meta: { 
      requiresAuth: true,
      permissions: ['conduct police vetting']
    }
  },
  {
    path: '/vetting/nis',
    name: 'NisVettingList',
    component: () => import('@/views/vetting/NisVettingList.vue'),
    meta: { 
      requiresAuth: true,
      permissions: ['conduct nis vetting']
    }
  },
  {
    path: '/applications/:id/vetting/nis',
    name: 'NisVetting',
    component: () => import('@/views/vetting/NisVetting.vue'),
    meta: { 
      requiresAuth: true,
      permissions: ['conduct nis vetting']
    }
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('@/views/reports/Index.vue'),
    meta: {
      requiresAuth: true,
      permissions: ['view reports'],
    }
  },
  {
    path: '/admin',
    name: 'Admin',
    component: () => import('@/views/admin/Index.vue'),
    meta: { 
      requiresAuth: true,
      permissions: [
        'manage users',
        'manage roles',
        'manage permissions',
        'manage institutions',
        'manage application statuses',
        'manage vetting types',
        'manage document types',
      ]
    }
  },
  {
    path: '/unauthorized',
    name: 'Unauthorized',
    component: () => import('@/views/Unauthorized.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/NotFound.vue')
  }
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// Route guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore()
  
  // Check authentication
  if (to.meta.requiresAuth) {
    const ok = await authStore.ensureUserLoaded()
    if (!ok) {
      next({ name: 'Login' })
      return
    }
  }

  // Redirect authenticated users away from auth pages
  if (to.meta.requiresGuest) {
    if (authStore.user) {
      next({ name: 'Dashboard' })
      return
    }

    // Hydrate a session-backed login if one exists.
    const ok = await authStore.ensureUserLoaded()
    if (ok) {
      next({ name: 'Dashboard' })
      return
    }
  }

  // Check permission-based access (recommended)
  if (to.meta.permissions && !authStore.hasAnyPermission(to.meta.permissions as string[])) {
    next({ name: 'Unauthorized' })
    return
  }

  // Backward compatibility: some routes may still use roles
  if (to.meta.roles && !authStore.hasAnyRole(to.meta.roles as string[])) {
    next({ name: 'Unauthorized' })
    return
  }

  next()
})

export default router
