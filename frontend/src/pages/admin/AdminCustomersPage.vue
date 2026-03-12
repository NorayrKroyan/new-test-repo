<template>
  <AdminLayout>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Customers</h1>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row">
        <input
            v-model="q"
            class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 sm:w-72"
            placeholder="Search customer"
            @keyup.enter="loadRows"
        />
        <button
            class="h-11 rounded-xl bg-slate-900 px-4 text-sm font-medium text-white"
            @click="openCreate"
        >
          Add
        </button>
      </div>
    </div>

    <!-- Mobile cards -->
    <div class="space-y-3 md:hidden">
      <div
          v-for="row in rows"
          :key="row.id"
          class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <button class="text-left font-semibold text-sky-700 hover:underline" @click="editRow(row)">
              {{ row.company_name || row.contact_name || '—' }}
            </button>
            <div class="mt-1 text-sm text-slate-500">{{ row.contact_name || '—' }}</div>
          </div>

          <div class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
            {{ row.status || 'pending_review' }}
          </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-700">
          <div><span class="font-medium">Email:</span> {{ row.email || '—' }}</div>
          <div><span class="font-medium">Location:</span> {{ row.city || '—' }}, {{ row.state || '—' }}</div>
        </div>
      </div>

      <div
          v-if="!rows.length"
          class="rounded-2xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500 shadow-sm"
      >
        No customers found.
      </div>
    </div>

    <!-- Desktop table -->
    <div class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
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