<template>
  <v-card class="mt-4">
    <v-card-title class="d-flex justify-space-between align-center">
      <span>Roles & Permissions</span>
      <v-btn color="primary" @click="openCreateDialog">
        <v-icon start>mdi-plus</v-icon>
        Add Role
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-data-table
        :headers="headers"
        :items="roles"
        :loading="loading"
        :key="roles.length + roles.map(r => r.permissions?.length || 0).join(',')"
        class="elevation-1"
      >
        <template #item.permissions="{ item }">
          <v-chip
            v-for="perm in (item.permissions || []).slice(0, 3)"
            :key="String((perm as any)?.id ?? perm)"
            size="small"
            class="mr-1"
          >
            {{ (perm as any)?.name ?? String(perm) }}
          </v-chip>
          <span v-if="(item.permissions || []).length > 3" class="text-caption">
            +{{ (item.permissions || []).length - 3 }} more
          </span>
        </template>
        <template #item.actions="{ item }">
          <v-btn
            icon="mdi-pencil"
            size="small"
            variant="text"
            @click="openEditDialog(item)"
          ></v-btn>
          <v-btn
            icon="mdi-delete"
            size="small"
            variant="text"
            color="error"
            @click="confirmDelete(item)"
          ></v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600">
      <v-card>
        <v-card-title>{{ editingRole ? 'Edit Role' : 'Create Role' }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef" @submit.prevent="handleSubmit">
            <v-text-field
              v-model="form.name"
              label="Role Name"
              :rules="[rules.required]"
            ></v-text-field>
            <v-select
              v-model="form.permissions"
              :items="allPermissions"
              item-title="name"
              item-value="name"
              label="Permissions"
              multiple
              chips
              closable-chips
              hint="Select permissions for this role"
              persistent-hint
              @update:model-value="handlePermissionsChange"
            ></v-select>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="handleSubmit" :loading="submitting">
            {{ editingRole ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, watch } from 'vue'
import { useToast } from 'vue-toastification'
import { adminApi, type Role, type Permission } from '@/api/admin'

const toast = useToast()

const loading = ref(false)
const submitting = ref(false)
const dialog = ref(false)
const editingRole = ref<Role | null>(null)
const formRef = ref<HTMLFormElement | null>(null)

const roles = ref<Role[]>([])
const allPermissions = ref<Permission[]>([])

const form = reactive({
  name: '',
  permissions: [] as string[]
})

const headers = [
  { title: 'Name', key: 'name' },
  { title: 'Permissions', key: 'permissions' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const rules = {
  required: (v: any) => !!v || 'This field is required'
}

async function loadRoles() {
  loading.value = true
  try {
    const response = await adminApi.getRoles()
    if (response.data.success) {
      // Create a completely new array with new objects to ensure Vue reactivity
      const loadedRoles = (Array.isArray(response.data.data) ? response.data.data : []).map((role: any) => {
        // Check if permissions are already loaded in the response
        const hasPermissions = role.permissions && Array.isArray(role.permissions) && role.permissions.length > 0
        
        return {
          id: role.id,
          name: role.name,
          guard_name: role.guard_name,
          created_at: role.created_at,
          updated_at: role.updated_at,
          permissions: hasPermissions ? [...role.permissions] : [] // Create new array for reactivity
        }
      })
      
      // Load permissions for each role (only if not already loaded)
      const rolesWithPermissions = await Promise.all(
        loadedRoles.map(async (role: any) => {
          // If permissions are already loaded from the response, use them
          if (role.permissions && role.permissions.length > 0 && role.permissions[0]?.id) {
            return role
          }
          
          // Otherwise, fetch permissions
          try {
            const permResponse = await adminApi.getRolePermissions(role.id)
            if (permResponse.data.success) {
              return {
                ...role,
                permissions: permResponse.data.data || []
              }
            } else {
              return {
                ...role,
                permissions: []
              }
            }
          } catch (err) {
            console.error(`Failed to load permissions for role ${role.id}:`, err)
            return {
              ...role,
              permissions: []
            }
          }
        })
      )
      
      // Replace the entire array to ensure Vue reactivity
      roles.value = rolesWithPermissions
      console.log('Roles loaded and updated:', roles.value.length, 'roles')
      if (roles.value.length > 0) {
        console.log('First role permissions count:', roles.value[0]?.permissions?.length || 0)
      }
      console.log('First role permissions count:', roles.value[0]?.permissions?.length || 0)
    }
  } catch (err: any) {
    console.error('Failed to load roles:', err)
    toast.error('Failed to load roles')
  } finally {
    loading.value = false
  }
}

async function loadPermissions() {
  try {
    const response = await adminApi.getAllPermissions()
    if (response.data.success) {
      allPermissions.value = response.data.data || []
    }
  } catch (err) {
    console.error('Failed to load permissions:', err)
  }
}

function openCreateDialog() {
  editingRole.value = null
  resetForm()
  dialog.value = true
}

function openEditDialog(role: Role) {
  editingRole.value = role
  form.name = role.name
  // Create a new array to ensure reactivity
  form.permissions = [...(role.permissions || []).map((p: Permission) => p.name)]
  console.log('Opening edit dialog for role:', role.name)
  console.log('Initial permissions:', form.permissions)
  dialog.value = true
}

function resetForm() {
  form.name = ''
  form.permissions = []
}

function handlePermissionsChange(value: string[]) {
  console.log('Permissions changed:', value)
  console.log('Current form.permissions before update:', form.permissions)
  // Ensure we're working with a fresh array and update the reactive form
  form.permissions.splice(0, form.permissions.length, ...value)
  console.log('Current form.permissions after update:', form.permissions)
}

// Watch for changes to form.permissions
watch(() => form.permissions, (newVal, oldVal) => {
  console.log('Form permissions watcher triggered:', { newVal, oldVal })
}, { deep: true })

async function handleSubmit() {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

  submitting.value = true
  try {
    // Prepare data - ensure permissions is an array of strings (names)
    const data: any = {
      name: form.name.trim()
    }
    
    // Always include permissions array, even if empty (to clear permissions)
    // Filter out any empty strings and ensure we have a clean array
    const cleanPermissions = (form.permissions || [])
      .filter((p: string) => p && typeof p === 'string' && p.trim())
      .map((p: string) => p.trim())
    
    data.permissions = cleanPermissions
    
    console.log('Current form.permissions:', form.permissions)
    console.log('Clean permissions to send:', cleanPermissions)
    console.log('Submitting role data:', data)
    
    if (editingRole.value) {
      const response = await adminApi.updateRole(editingRole.value.id, data)
      console.log('Update response:', response.data)
      if (response.data.success) {
        toast.success('Role updated successfully')
        dialog.value = false
        resetForm()
        editingRole.value = null
        // Small delay to ensure backend has processed the update
        await new Promise(resolve => setTimeout(resolve, 200))
        // Force reload roles to update UI
        await loadRoles()
      }
    } else {
      const response = await adminApi.createRole(data)
      console.log('Create response:', response.data)
      if (response.data.success) {
        toast.success('Role created successfully')
        dialog.value = false
        resetForm()
        // Force reload roles to update UI
        await loadRoles()
      }
    }
  } catch (err: any) {
    console.error('Error saving role:', err)
    console.error('Error response data:', err.response?.data)
    
    // Handle validation errors
    if (err.response?.data?.errors) {
      const validationErrors = err.response.data.errors
      const errorMessages = Object.entries(validationErrors)
        .map(([field, messages]) => {
          const msgArray = Array.isArray(messages) ? messages : [messages]
          return `${field}: ${msgArray.join(', ')}`
        })
        .join('\n')
      toast.error(`Validation failed:\n${errorMessages}`)
    } else {
      const errorMessage = err.response?.data?.error?.message || 
                          err.response?.data?.message ||
                          'Failed to save role'
      toast.error(errorMessage)
    }
  } finally {
    submitting.value = false
  }
}

async function confirmDelete(role: Role) {
  if (confirm(`Are you sure you want to delete role ${role.name}?`)) {
    try {
      const response = await adminApi.deleteRole(role.id)
      if (response.data.success) {
        toast.success('Role deleted successfully')
        await loadRoles()
      }
    } catch (err: any) {
      toast.error(err.response?.data?.error?.message || 'Failed to delete role')
    }
  }
}

onMounted(async () => {
  await Promise.all([
    loadRoles(),
    loadPermissions()
  ])
})
</script>

