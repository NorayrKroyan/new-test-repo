<template>
  <AdminLayout>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Jobs Available</h1>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row">
        <input
            v-model="q"
            class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 sm:w-72"
            placeholder="Search job"
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
              {{ row.job_number || '—' }}
            </button>
            <div class="mt-1 text-sm text-slate-700">{{ row.title || '—' }}</div>
          </div>

          <div class="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
            {{ row.status || 'open' }}
          </div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-2 text-sm text-slate-700">
          <div>
            <span class="font-medium">Route:</span>
            {{ row.origin_city || '—' }}, {{ row.origin_state || '—' }} →
            {{ row.destination_city || '—' }}, {{ row.destination_state || '—' }}
          </div>
          <div><span class="font-medium">Rate:</span> {{ row.rate || '—' }}</div>
          <div><span class="font-medium">Weight:</span> {{ row.weight || '—' }}</div>
        </div>
      </div>

      <div
          v-if="!rows.length"
          class="rounded-2xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500 shadow-sm"
      >
        No jobs found.
      </div>
    </div>

    <!-- Desktop table -->
    <div class="hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
      <div class="overflow-x-auto">
        <table class="min-w-full text-left text-sm">
          <thead>
          <tr class="border-b border-slate-200 text-slate-500">
            <th class="px-4 py-3">Job #</th>
            <th class="px-4 py-3">Title</th>
            <th class="px-4 py-3">Route</th>
            <th class="px-4 py-3">Rate</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <button class="font-medium text-sky-700 hover:underline" @click="editRow(row)">
                {{ row.job_number || '—' }}
              </button>
            </td>
            <td class="px-4 py-3">{{ row.title || '—' }}</td>
            <td class="px-4 py-3">
              {{ row.origin_city || '—' }}, {{ row.origin_state || '—' }} →
              {{ row.destination_city || '—' }}, {{ row.destination_state || '—' }}
            </td>
            <td class="px-4 py-3">{{ row.rate || '—' }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <JobAvailableModal
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
import JobAvailableModal from '../../components/admin/JobAvailableModal.vue'
import { deleteJobAvailable, fetchJobsAvailable, saveJobAvailable } from '../../api/admin'

const q = ref('')
const rows = ref([])
const open = ref(false)
const saving = ref(false)
const deleting = ref(false)

const form = reactive({
  id: null,
  job_number: '',
  title: '',
  description: '',
  origin_city: '',
  origin_state: '',
  destination_city: '',
  destination_state: '',
  equipment_type: '',
  trailer_type: '',
  weight: '',
  rate: '',
  status: 'open',
})

function resetForm() {
  Object.assign(form, {
    id: null,
    job_number: '',
    title: '',
    description: '',
    origin_city: '',
    origin_state: '',
    destination_city: '',
    destination_state: '',
    equipment_type: '',
    trailer_type: '',
    weight: '',
    rate: '',
    status: 'open',
  })
}

async function loadRows() {
  const data = await fetchJobsAvailable({ q: q.value })
  rows.value = data.data || []
}

function openCreate() {
  resetForm()
  open.value = true
}

function editRow(row) {
  Object.assign(form, {
    id: row.id,
    job_number: row.job_number || '',
    title: row.title || '',
    description: row.description || '',
    origin_city: row.origin_city || '',
    origin_state: row.origin_state || '',
    destination_city: row.destination_city || '',
    destination_state: row.destination_state || '',
    equipment_type: row.equipment_type || '',
    trailer_type: row.trailer_type || '',
    weight: row.weight || '',
    rate: row.rate || '',
    status: row.status || 'open',
  })

  open.value = true
}

function closeModal() {
  open.value = false
}

async function saveRow() {
  saving.value = true
  try {
    await saveJobAvailable(form, form.id)
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
    await deleteJobAvailable(form.id)
    open.value = false
    resetForm()
    await loadRows()
  } finally {
    deleting.value = false
  }
}

onMounted(loadRows)
</script>