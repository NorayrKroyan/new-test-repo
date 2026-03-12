<template>
  <AdminLayout>
    <div class="mb-4">
      <h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
      <div class="mt-1 text-sm text-slate-500">Admin overview</div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Admin Users</div>
        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ cards.admin_users || 0 }}</div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Leads Total</div>
        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ cards.leads_total || 0 }}</div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">New Leads</div>
        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ cards.leads_new || 0 }}</div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Carriers</div>
        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ cards.carriers_total || 0 }}</div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Open Jobs</div>
        <div class="mt-2 text-3xl font-semibold text-slate-900">{{ cards.jobs_open || 0 }}</div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import AdminLayout from '../../layouts/AdminLayout.vue';
import { fetchDashboard } from '../../api/admin';

const cards = ref({});

onMounted(async () => {
  const data = await fetchDashboard();
  cards.value = data.cards || {};
});
</script>