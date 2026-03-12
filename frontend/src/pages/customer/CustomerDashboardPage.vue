<template>
  <CustomerLayout>
    <div class="mb-4">
      <h1 class="text-2xl font-semibold text-slate-900">Customer Dashboard</h1>
      <div class="mt-1 text-sm text-slate-500">Customer profile overview</div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Company</div>
        <div class="mt-2 text-xl font-semibold text-slate-900">
          {{ customer?.company_name || '—' }}
        </div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Jobs Submitted</div>
        <div class="mt-2 text-xl font-semibold text-slate-900">
          {{ cards.jobs_count || 0 }}
        </div>
      </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="text-sm font-semibold text-slate-900">Status</div>
      <div class="mt-2 text-sm text-slate-700">
        {{ cards.status || 'active' }}
      </div>
    </div>
  </CustomerLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import CustomerLayout from '../../layouts/CustomerLayout.vue'
import { fetchCustomerDashboard } from '../../api/customer'

const cards = ref({})
const customer = ref(null)

onMounted(async () => {
  const data = await fetchCustomerDashboard()
  cards.value = data.cards || {}
  customer.value = data.customer || null
})
</script>