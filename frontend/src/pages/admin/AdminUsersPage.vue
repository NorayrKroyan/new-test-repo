<template>
  <AdminLayout>
    <div class="mb-4 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Admin Users</h1>
      </div>

      <div class="flex gap-2">
        <input
            v-model="q"
            class="h-11 w-72 rounded-xl border border-slate-300 bg-white px-3"
            placeholder="Search admin user"
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
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Email</th>
            <th class="px-4 py-3">Status</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="row in rows" :key="row.id" class="border-b border-slate-100">
            <td class="px-4 py-3">
              <button class="font-medium text-sky-700 hover:underline" @click="editRow(row)">
                {{ row.name || '—' }}
              </button>
            </td>
            <td class="px-4 py-3">{{ row.email }}</td>
            <td class="px-4 py-3">{{ row.is_active ? 'Active' : 'Inactive' }}</td>
          </tr>
          </tbody>
        </table>
      </div>
    </div>

    <AdminUserModal
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
import AdminUserModal from '../../components/admin/AdminUserModal.vue';
import { deleteAdminUser, fetchAdminUsers, saveAdminUser } from '../../api/admin';

const q = ref('');
const rows = ref([]);
const open = ref(false);
const saving = ref(false);
const deleting = ref(false);

const form = reactive({
  id: null,
  name: '',
  email: '',
  password: '',
  is_active: true,
});

function resetForm() {
  form.id = null;
  form.name = '';
  form.email = '';
  form.password = '';
  form.is_active = true;
}

async function loadRows() {
  const data = await fetchAdminUsers({ q: q.value });
  rows.value = data.data || [];
}

function openCreate() {
  resetForm();
  open.value = true;
}

function editRow(row) {
  form.id = row.id;
  form.name = row.name || '';
  form.email = row.email || '';
  form.password = '';
  form.is_active = !!row.is_active;
  open.value = true;
}

function closeModal() {
  open.value = false;
}

async function saveRow() {
  saving.value = true;
  try {
    await saveAdminUser(form, form.id);
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
    await deleteAdminUser(form.id);
    open.value = false;
    resetForm();
    await loadRows();
  } finally {
    deleting.value = false;
  }
}

onMounted(loadRows);
</script>