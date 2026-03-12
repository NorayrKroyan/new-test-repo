<template>
  <CarrierLayout>
    <div class="mb-4">
      <h1 class="text-2xl font-semibold text-slate-900">Carrier Dashboard</h1>
      <div class="mt-1 text-sm text-slate-500">Carrier profile overview</div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Company</div>
        <div class="mt-2 text-xl font-semibold text-slate-900">{{ carrier?.company_name || '—' }}</div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Trucks</div>
        <div class="mt-2 text-xl font-semibold text-slate-900">{{ cards.truck_count || 0 }}</div>
      </div>

      <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="text-sm text-slate-500">Trailers</div>
        <div class="mt-2 text-xl font-semibold text-slate-900">{{ cards.trailer_count || 0 }}</div>
      </div>
    </div>

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
      <div class="text-sm font-semibold text-slate-900">Status</div>
      <div class="mt-2 text-sm text-slate-700">{{ cards.status || 'pending_review' }}</div>
    </div>
  </CarrierLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue'
import CarrierLayout from '../../layouts/CarrierLayout.vue'
import { fetchCarrierDashboard } from '../../api/carrier'

const cards = ref({})
const carrier = ref(null)

onMounted(async () => {
  const data = await fetchCarrierDashboard()
  cards.value = data.cards || {}
  carrier.value = data.carrier || null
})
</script>