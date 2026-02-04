<template>
  <v-app>
    <v-navigation-drawer
      v-model="drawer"
      :rail="rail"
      permanent
      :temporary="false"
    >
      <v-list-item
        prepend-avatar="/logo.png"
        :title="authStore.user?.username || 'Admin'"
        :subtitle="authStore.user?.email"
        nav
      >
        <template v-slot:append>
          <v-btn
            icon="mdi-chevron-left"
            variant="text"
            @click.stop="rail = !rail"
          />
        </template>
      </v-list-item>

      <v-divider />

      <v-list density="compact" nav>
        <v-list-item
          prepend-icon="mdi-view-dashboard"
          title="Dashboard"
          value="dashboard"
          :to="{ name: 'Dashboard' }"
        />
        <v-list-item
          prepend-icon="mdi-file-document-multiple"
          title="Applications"
          value="applications"
          :to="{ name: 'Applications' }"
        />
        <v-list-item
          v-if="authStore.hasPermission('create applications')"
          prepend-icon="mdi-file-document-plus"
          title="Create Application"
          value="create"
          :to="{ name: 'CreateApplication' }"
        />
        <v-list-item
          v-if="authStore.hasPermission('view reports')"
          prepend-icon="mdi-chart-box"
          title="Reports"
          value="reports"
          :to="{ name: 'Reports' }"
        />
        <v-list-item
          v-if="authStore.hasAnyPermission([
            'manage users',
            'manage roles',
            'manage permissions',
            'manage institutions',
            'manage application statuses',
            'manage vetting types',
            'manage document types',
          ])"
          prepend-icon="mdi-cog"
          title="Admin Panel"
          value="admin"
          :to="{ name: 'Admin' }"
        />
      </v-list>

      <template v-slot:append>
        <v-divider />
        <v-list density="compact" nav>
          <v-list-item
            prepend-icon="mdi-logout"
            title="Logout"
            value="logout"
            @click="handleLogout"
          />
        </v-list>
      </template>
    </v-navigation-drawer>

    <v-app-bar
      color="primary"
      :elevation="2"
    >
      <v-app-bar-nav-icon
        icon="mdi-menu"
        @click="drawer = !drawer"
      />

      <v-toolbar-title>CNMIS - Admin</v-toolbar-title>

      <v-spacer />

      <Notifications />

      <v-menu>
        <template v-slot:activator="{ props }">
          <v-btn
            icon="mdi-account-circle"
            variant="text"
            v-bind="props"
          />
        </template>
        <v-list>
          <v-list-item>
            <v-list-item-title>{{ authStore.user?.username }}</v-list-item-title>
            <v-list-item-subtitle>{{ authStore.user?.email }}</v-list-item-subtitle>
          </v-list-item>
          <v-divider />
          <v-list-item prepend-icon="mdi-logout" title="Logout" @click="handleLogout" />
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-main>
      <v-container fluid>
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import Notifications from '@/components/Notifications.vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()

const drawer = ref(true)
const rail = ref(false)

async function handleLogout() {
  await authStore.logout()
  toast.info('Logged out successfully')
}
</script>

