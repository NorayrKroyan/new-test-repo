<template>
  <AdminLayout>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Jobs Available</h1>
      </div>

      <div class="flex gap-2">
        <input
            v-model="q"
            class="h-11 w-72 rounded-xl border border-slate-300 bg-white px-3"
            placeholder="Search job"
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
import { onMounted, reactive, ref } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import JobAvailableModal from '../../components/admin/JobAvailableModal.vue';
import { deleteJobAvailable, fetchJobsAvailable, saveJobAvailable } from '../../api/admin';

const q = ref('');
const rows = ref([]);
const open = ref(false);
const saving = ref(false);
const deleting = ref(false);

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
});

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
  });
}

async function loadRows() {
  const data = await fetchJobsAvailable({ q: q.value });
  rows.value = data.data || [];
}

function openCreate() {
  resetForm();
  open.value = true;
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
  });

  open.value = true;
}

function closeModal() {
  open.value = false;
}

async function saveRow() {
  saving.value = true;
  try {
    await saveJobAvailable(form, form.id);
    open.value = false;
    resetForm();
    await loadRows();
  } finally {
    saving.value = false;
  }
}

async function deleteCurrent() {
  if (!form.id) return;

  deleting.value = true;
  try {
    await deleteJobAvailable(form.id);
    open.value = false;
    resetForm();
    await loadRows();
  } finally {
    deleting.value = false;
  }
}

onMounted(loadRows);
</script>