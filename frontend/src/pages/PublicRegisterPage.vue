<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Registration</h1>
      <p class="mt-1 text-sm text-slate-500">Create your MultiModal Portal account</p>

      <div class="mt-5 grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
        <button
            class="rounded-lg px-4 py-2 text-sm font-medium"
            :class="role === 'customer' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
            @click="switchRole('customer')"
        >
          Customer
        </button>

        <button
            class="rounded-lg px-4 py-2 text-sm font-medium"
            :class="role === 'carrier' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
            @click="switchRole('carrier')"
        >
          Carrier
        </button>
      </div>

      <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Contact Name</label>
          <input v-model="form.contact_name" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Company Name</label>
          <input v-model="form.company_name" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
          <input v-model="form.email" type="email" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
          <input v-model="form.password" type="password" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Phone</label>
          <input v-model="form.phone" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">City</label>
          <input v-model="form.city" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">State</label>
          <input v-model="form.state" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
        </div>

        <template v-if="role === 'carrier'">
          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">USDOT</label>
            <input v-model="form.usdot" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">MC Number</label>
            <input v-model="form.mc_number" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Truck Count</label>
            <input v-model="form.truck_count" type="number" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
          </div>

          <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">Trailer Count</label>
            <input v-model="form.trailer_count" type="number" class="h-11 w-full rounded-xl border border-slate-300 px-3" />
          </div>
        </template>

        <div v-if="role === 'customer'" class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
          <textarea v-model="form.address" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2"></textarea>
        </div>
      </div>

      <div class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">
        After registration, your account will stay in <strong>Pending Review</strong> until admin changes your status to <strong>Active</strong>.
      </div>

      <div v-if="err" class="mt-4 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">
        {{ err }}
      </div>

      <div class="mt-6 flex flex-col gap-3 sm:flex-row">
        <button
            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800"
            :disabled="loading"
            @click="submit"
        >
          {{ loading ? 'Creating...' : registerButtonLabel }}
        </button>

        <button
            class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="goLogin"
        >
          Back to Login
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { carrierRegister } from '../api/carrier'
import { customerRegister } from '../api/customer'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const err = ref('')
const role = ref(route.query.role === 'carrier' ? 'carrier' : 'customer')

const form = reactive({
  contact_name: '',
  company_name: '',
  email: '',
  password: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  usdot: '',
  mc_number: '',
  carrier_class: '',
  insurance_status: '',
  truck_count: '',
  trailer_count: '',
})

const registerButtonLabel = computed(() => {
  return role.value === 'carrier' ? 'Register as Carrier' : 'Register as Customer'
})

function switchRole(nextRole) {
  role.value = nextRole
  router.replace({
    path: '/register',
    query: { role: nextRole },
  })
}

async function submit() {
  loading.value = true
  err.value = ''

  try {
    if (role.value === 'carrier') {
      await carrierRegister(form)
      router.push({
        path: '/login',
        query: { role: 'carrier', registered: '1' },
      })
      return
    }

    await customerRegister(form)
    router.push({
      path: '/login',
      query: { role: 'customer', registered: '1' },
    })
  } catch (e) {
    err.value =
        e?.response?.data?.message ||
        Object.values(e?.response?.data?.errors || {}).flat()[0] ||
        'Registration failed'
  } finally {
    loading.value = false
  }
}

function goLogin() {
  router.push({
    path: '/login',
    query: { role: role.value },
  })
}
</script>