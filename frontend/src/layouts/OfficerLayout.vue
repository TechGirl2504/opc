<template>
  <v-app>
    <v-navigation-drawer
      v-model="drawer"
      :rail="mdAndUp ? rail : false"
      :permanent="mdAndUp"
      :temporary="!mdAndUp"
      class="gov-drawer"
    >
      <v-list-item
        prepend-avatar="/cnmis-logo.svg"
        :title="authStore.user?.username || 'Officer'"
        :subtitle="institutionSubtitle"
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
          title="Application Dashboard"
          value="dashboard"
          :to="{ name: 'Dashboard' }"
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
      :elevation="1"
      class="gov-app-bar"
    >
      <v-app-bar-nav-icon
        icon="mdi-menu"
        @click="toggleDrawer"
      />

      <v-toolbar-title class="gov-app-bar__title">CNMIS - Application Dashboard</v-toolbar-title>

      <v-spacer />

      <PwaInstallButton />
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
      <v-container fluid class="pa-2 pa-sm-4 pa-md-6">
        <slot />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useDisplay } from 'vuetify'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useToast } from 'vue-toastification'
import PwaInstallButton from '@/components/PwaInstallButton.vue'
import Notifications from '@/components/Notifications.vue'

const router = useRouter()
const authStore = useAuthStore()
const toast = useToast()
const { mdAndUp } = useDisplay()

const drawer = ref(false)
const rail = ref(false)

const institutionSubtitle = computed(() => {
  const inst = authStore.user?.institution
  if (!inst) return ''
  return typeof inst === 'string' ? inst : inst.name
})

watch(
  mdAndUp,
  (isDesktop) => {
    drawer.value = isDesktop
    if (!isDesktop) {
      rail.value = false
    }
  },
  { immediate: true }
)

function toggleDrawer() {
  if (mdAndUp.value) {
    rail.value = !rail.value
    return
  }

  drawer.value = !drawer.value
}

async function handleLogout() {
  await authStore.logout()
  toast.info('Logged out successfully')
}
</script>

<style scoped>
:deep(.gov-drawer) {
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border-right: 1px solid rgba(18, 56, 95, 0.12);
}

:deep(.gov-drawer .v-list-item) {
  margin-inline: 10px;
  border-radius: 14px;
}

:deep(.gov-drawer .v-list-item--active) {
  background: rgba(18, 56, 95, 0.08);
  color: #12385f;
}

:deep(.gov-app-bar) {
  color: #ffffff;
  background: linear-gradient(90deg, #12385f 0%, #0b2947 100%) !important;
}

.gov-app-bar__title {
  font-weight: 700;
  letter-spacing: 0.03em;
}
</style>
