<template>
  <AdminLayout>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Customers</h1>
      </div>

      <div class="flex gap-2">
        <input
            v-model="q"
            class="h-11 w-72 rounded-xl border border-slate-300 bg-white px-3"
            placeholder="Search customer"
            @keyup.enter="loadRows"
        />
        <button class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white" @click="openCreate">Add</button>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead>
          <tr class="border-b border-slate-200 text-slate-500">
            <th class="px-4 py-3">Company</th>
            <th class="px-4 py-3">Contact</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Location</th>
            <th class="px-4 py-3">Status</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <button class="font-medium text-sky-700 hover:underline" @click="editRow(row)">
                {{ row.company_name || row.contact_name || '—' }}
              </button>
            </td>
            <td class="px-4 py-3">{{ row.contact_name || '—' }}</td>
            <td class="px-4 py-3">{{ row.email || '—' }}</td>
            <td class="px-4 py-3">{{ row.city || '—' }}, {{ row.state || '—' }}</td>
            <td class="px-4 py-3">{{ row.status || 'pending_review' }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <CustomerModal
        :open="open"
        :saving="saving"
        :deleting="deleting"
        :form="form"
        @close="closeModal"
        @save="saveRow"
        @delete="deleteCurrent"
    />
  </AdminLayout>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import AdminLayout from '../../layouts/AdminLayout.vue'
import CustomerModal from '../../components/admin/CustomerModal.vue'
import { deleteCustomer, fetchCustomers, saveCustomer } from '../../api/admin'

const q = ref('')
const rows = ref([])
const open = ref(false)
const saving = ref(false)
const deleting = ref(false)

const form = reactive({
  id: null,
  company_name: '',
  contact_name: '',
  email: '',
  password: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  status: 'pending_review',
  notes: '',
})

function resetForm() {
  Object.assign(form, {
    id: null,
    company_name: '',
    contact_name: '',
    email: '',
    password: '',
    phone: '',
    address: '',
    city: '',
    state: '',
    status: 'pending_review',
    notes: '',
  })
}

async function loadRows() {
  const data = await fetchCustomers({ q: q.value })
  rows.value = data.data || []
}

function openCreate() {
  resetForm()
  open.value = true
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    company_name: row.company_name || '',
    contact_name: row.contact_name || '',
    email: row.email || '',
    password: '',
    phone: row.phone || '',
    address: row.address || '',
    city: row.city || '',
    state: row.state || '',
    status: row.status || 'pending_review',
    notes: row.notes || '',
  })

  open.value = true
}

function closeModal() {
  open.value = false
}

async function saveRow() {
  saving.value = true
  try {
    await saveCustomer(form, form.id)
    open.value = false
    resetForm()
    await loadRows()
  } finally {
    saving.value = false
  }
}

async function deleteCurrent() {
  if (!form.id) return

  deleting.value = true
  try {
    await deleteCustomer(form.id)
    open.value = false
    resetForm()
    await loadRows()
  } finally {
    deleting.value = false
  }
}

onMounted(loadRows)
</script>