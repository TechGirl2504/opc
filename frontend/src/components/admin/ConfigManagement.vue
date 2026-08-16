<template>
  <v-card class="mt-4">
    <v-card-title class="d-flex justify-space-between align-center">
      <span>{{ title }}</span>
      <v-btn color="primary" @click="openCreateDialog">
        <v-icon start>mdi-plus</v-icon>
        Add
      </v-btn>
    </v-card-title>
    <v-card-text>
      <v-data-table
        :headers="headers"
        :items="items"
        :loading="loading"
        class="elevation-1"
      >
        <template v-slot:item.is_active="{ item }">
          <v-chip :color="item.is_active ? 'success' : 'error'" size="small">
            {{ item.is_active ? 'Active' : 'Inactive' }}
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
        </template>
      </v-data-table>
    </v-card-text>

    <!-- Create/Edit Dialog -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card>
        <v-card-title>{{ editingItem ? 'Edit' : 'Create' }} {{ title }}</v-card-title>
        <v-card-text>
          <v-form ref="formRef" @submit.prevent="handleSubmit">
            <v-text-field
              v-model="form.name"
              label="Name"
              :rules="[rules.required]"
            ></v-text-field>
            <v-text-field
              v-model="form.code"
              label="Code"
              :rules="[rules.required]"
              hint="Unique identifier (lowercase, no spaces)"
              persistent-hint
            ></v-text-field>
            <v-textarea
              v-model="form.description"
              label="Description"
              rows="3"
            ></v-textarea>
            <v-text-field
              v-if="showOrder"
              v-model.number="form.order"
              label="Order"
              type="number"
            ></v-text-field>
            <v-text-field
              v-if="showMaxSize"
              v-model.number="form.max_size_mb"
              label="Max Size (MB)"
              type="number"
            ></v-text-field>
            <v-text-field
              v-if="showAllowedMimes"
              v-model="form.allowed_mimes"
              label="Allowed MIME Types"
              hint="Comma-separated (e.g., pdf,jpg,png)"
              persistent-hint
            ></v-text-field>
            <v-switch
              v-model="form.is_active"
              label="Active"
            ></v-switch>
          </v-form>
        </v-card-text>
        <v-card-actions>
          <v-spacer></v-spacer>
          <v-btn variant="text" @click="dialog = false">Cancel</v-btn>
          <v-btn color="primary" @click="handleSubmit" :loading="submitting">
            {{ editingItem ? 'Update' : 'Create' }}
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-card>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { useToast } from 'vue-toastification'

const props = defineProps<{
  title: string
  items: any[]
  loading: boolean
  headers: any[]
  showOrder?: boolean
}>()

const emit = defineEmits<{
  create: [data: any]
  update: [id: number, data: any]
  delete: [id: number]
  refresh: []
}>()

const toast = useToast()

const dialog = ref(false)
const submitting = ref(false)
const editingItem = ref<any | null>(null)
const formRef = ref<HTMLFormElement | null>(null)

const form = reactive({
  name: '',
  code: '',
  description: '',
  order: 0,
  max_size_mb: undefined as number | undefined,
  allowed_mimes: '',
  is_active: true
})

const showOrder = computed(() => props.showOrder ?? props.title.includes('Status'))
const showMaxSize = computed(() => props.title.includes('Document'))
const showAllowedMimes = computed(() => props.title.includes('Document'))

const rules = {
  required: (v: any) => !!v || 'This field is required'
}

function openCreateDialog() {
  editingItem.value = null
  resetForm()
  dialog.value = true
}

function openEditDialog(item: any) {
  editingItem.value = item
  form.name = item.name || ''
  form.code = item.code || ''
  form.description = item.description || ''
  form.order = item.order || 0
  form.max_size_mb = item.max_size_mb
  form.allowed_mimes = item.allowed_mimes || ''
  form.is_active = item.is_active !== undefined ? item.is_active : true
  dialog.value = true
}

function resetForm() {
  form.name = ''
  form.code = ''
  form.description = ''
  form.order = 0
  form.max_size_mb = undefined
  form.allowed_mimes = ''
  form.is_active = true
}

async function handleSubmit() {
  const { valid } = await formRef.value?.validate()
  if (!valid) return

  submitting.value = true
  try {
    const data = { ...form }
    if (editingItem.value) {
      emit('update', editingItem.value.id, data)
    } else {
      emit('create', data)
    }
    dialog.value = false
  } catch (err: any) {
    toast.error('Failed to save')
  } finally {
    submitting.value = false
  }
}

function confirmDelete(item: any) {
  if (confirm(`Are you sure you want to delete ${item.name}?`)) {
    emit('delete', item.id)
  }
}
</script>
