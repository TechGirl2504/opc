<template>
  <v-card class="mt-4">
    <v-card-title class="d-flex justify-space-between align-center">
      <span>User Management</span>
      <v-btn color="primary" @click="openCreateDialog">
        <v-icon start>mdi-plus</v-icon>
        Add User
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-data-table
        :headers="headers"
        :items="users"
        :loading="loading"
        class="elevation-1"
        item-value="id"
      >
        <template v-slot:item.institution="{ item }">
          {{ typeof item.institution === 'string' ? item.institution : item.institution?.name || 'N/A' }}
        </template>
        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
          </v-chip>
        </template>
        <template v-slot:item.roles="{ item }">
          <v-chip
            v-for="role in item.roles"
            :key="role"
            size="small"
            class="mr-1"
          >
            {{ role }}
          </v-chip>
        </template>
        <template v-slot:item.actions="{ item }">
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
          <v-btn
            :icon="item.is_active ? 'mdi-account-off' : 'mdi-account-check'"
            size="small"
            variant="text"
            @click="toggleActive(item)"
          ></v-btn>
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="600">
      <v-card>
        <v-card-title>{{ editingUser ? 'Edit User' : 'Create User' }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef" @submit.prevent="handleSubmit">
            <v-text-field
              v-model="form.username"
              label="Username"
              :rules="[rules.required]"
            ></v-text-field>
            <v-text-field
              v-model="form.email"
              label="Email"
              type="email"
              :rules="editingUser ? [rules.emailOptional] : [rules.required, rules.email]"
            ></v-text-field>
            <v-text-field
              v-model="form.password"
              label="Password"
              type="password"
              :hint="editingUser ? 'Leave blank to keep the current password.' : 'Use at least 8 characters.'"
              persistent-hint
              :rules="editingUser ? [rules.passwordOptional] : [rules.required, rules.minLength]"
            ></v-text-field>
            <v-text-field
              v-if="!editingUser"
              v-model="form.password_confirmation"
              label="Confirm Password"
              type="password"
              :rules="[rules.required, rules.passwordMatch]"
            ></v-text-field>
            <v-select
              v-model="form.institution_id"
              :items="institutions"
              item-title="name"
              item-value="id"
              label="Institution"
              :rules="[rules.required]"
            ></v-select>
            <v-select
              v-model="form.role"
              :items="availableRoles"
              label="Role"
              :rules="[rules.required]"
            ></v-select>
            <v-switch
              v-if="editingUser"
              v-model="form.is_active"
              label="Active"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="handleSubmit" :loading="submitting">
            {{ editingUser ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useToast } from 'vue-toastification'
import { usersApi, type CreateUserRequest, type UpdateUserRequest } from '@/api/users'
import { adminApi, type Institution, type Role } from '@/api/admin'
import type { User } from '@/api/auth'

const toast = useToast()

const loading = ref(false)
const submitting = ref(false)
const dialog = ref(false)
const editingUser = ref<User | null>(null)
const formRef = ref<HTMLFormElement | null>(null)

const users = ref<User[]>([])
const institutions = ref<Institution[]>([])
const availableRoles = ref<string[]>([])

const form = reactive<CreateUserRequest & { is_active?: boolean }>({
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  institution_id: 0,
  role: '',
  is_active: true
})

const headers = [
  { title: 'Username', key: 'username' },
  { title: 'Email', key: 'email' },
  { title: 'Institution', key: 'institution' },
  { title: 'Roles', key: 'roles' },
  { title: 'Status', key: 'is_active' },
  { title: 'Actions', key: 'actions', sortable: false }
]

const rules = {
  required: (v: any) => !!v || 'This field is required',
  email: (v: string) => /.+@.+\..+/.test(v) || 'Email must be valid',
  emailOptional: (v: string) => !v || /.+@.+\..+/.test(v) || 'Email must be valid',
  minLength: (v: string) => v.length >= 8 || 'Password must be at least 8 characters',
  passwordOptional: (v: string) =>
    !v || v.length >= 8 || 'Password must be at least 8 characters',
  passwordMatch: (v: string) => v === form.password || 'Passwords must match'
}

function getPrimaryRole(user: User): string {
  if (Array.isArray(user.roles) && user.roles.length > 0) {
    const firstRole = user.roles[0] as unknown
    if (typeof firstRole === 'string') {
      return firstRole
    }

    if (firstRole && typeof firstRole === 'object' && 'name' in firstRole) {
      return String((firstRole as { name?: string }).name || '')
    }
  }

  if (typeof user.role === 'string') {
    return user.role
  }

  return ''
}

async function loadUsers() {
  loading.value = true
  try {
    const response = await usersApi.list({ per_page: 100 })
    if (response.data.success) {
      // Handle both paginated and non-paginated responses
      if (Array.isArray(response.data.data)) {
        users.value = response.data.data
      } else if (response.data.data?.data) {
        users.value = response.data.data.data
      } else {
        users.value = []
      }
    }
  } catch (err: any) {
    toast.error('Failed to load users')
    console.error('Error loading users:', err)
  } finally {
    loading.value = false
  }
}

async function loadInstitutions() {
  try {
    const response = await adminApi.getInstitutions()
    if (response.data.success) {
      institutions.value = response.data.data || []
    }
  } catch (err) {
    console.error('Failed to load institutions:', err)
  }
}

async function loadRoles() {
  try {
    const response = await adminApi.getRoles()
    if (response.data.success) {
      availableRoles.value = (response.data.data || []).map((r: Role) => r.name)
    }
  } catch (err) {
    console.error('Failed to load roles:', err)
  }
}

function openCreateDialog() {
  editingUser.value = null
  resetForm()
  dialog.value = true
}

function openEditDialog(user: User) {
  editingUser.value = user
  form.username = user.username
  form.email = user.email
  form.institution_id = user.institution_id || 0
  form.role = getPrimaryRole(user)
  form.is_active = user.is_active
  form.password = ''
  form.password_confirmation = ''
  dialog.value = true
}

function resetForm() {
  form.username = ''
  form.email = ''
  form.password = ''
  form.password_confirmation = ''
  form.institution_id = 0
  form.role = ''
  form.is_active = true
}

async function handleSubmit() {
  if (!editingUser.value) {
    form.password_confirmation = form.password
  }

  const { valid } = await formRef.value?.validate()
  if (!valid) return

  submitting.value = true
  try {
    if (editingUser.value) {
      const updateData: UpdateUserRequest = {
        username: form.username,
        is_active: form.is_active
      }
      if (form.email) {
        updateData.email = form.email
      }
      if (form.institution_id > 0) {
        updateData.institution_id = form.institution_id
      }
      if (form.role) {
        updateData.role = form.role
      }
      if (form.password) {
        updateData.password = form.password
        updateData.password_confirmation = form.password_confirmation
      }
      const response = await usersApi.update(editingUser.value.id, updateData)
      if (response.data.success) {
        toast.success('User updated successfully')
        dialog.value = false
        await loadUsers()
      }
    } else {
      const createData: CreateUserRequest = {
        username: form.username,
        email: form.email,
        password: form.password,
        password_confirmation: form.password,
        institution_id: form.institution_id,
        role: form.role
      }
      const response = await usersApi.create(createData)
      if (response.data.success) {
        toast.success('User created successfully')
        dialog.value = false
        await loadUsers()
      }
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to save user')
  } finally {
    submitting.value = false
  }
}

async function confirmDelete(user: User) {
  if (confirm(`Are you sure you want to delete user ${user.username}?`)) {
    try {
      const response = await usersApi.delete(user.id)
      if (response.data.success) {
        toast.success('User deleted successfully')
        await loadUsers()
      }
    } catch (err: any) {
      toast.error(err.response?.data?.error?.message || 'Failed to delete user')
    }
  }
}

async function toggleActive(user: User) {
  try {
    const response = user.is_active
      ? await usersApi.deactivate(user.id)
      : await usersApi.activate(user.id)
    if (response.data.success) {
      toast.success(`User ${user.is_active ? 'deactivated' : 'activated'} successfully`)
      await loadUsers()
    }
  } catch (err: any) {
    toast.error(err.response?.data?.error?.message || 'Failed to update user status')
  }
}

onMounted(async () => {
  await Promise.all([
    loadUsers(),
    loadInstitutions(),
    loadRoles()
  ])
})
</script>
