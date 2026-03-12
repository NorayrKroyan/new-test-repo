<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Customer Registration</h1>
      <p class="mt-1 text-sm text-slate-500">Create your customer account</p>

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

        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium text-slate-700">Address</label>
          <textarea v-model="form.address" rows="3" class="w-full rounded-xl border border-slate-300 px-3 py-2"></textarea>
        </div>
      </div>

      <div v-if="err" class="mt-4 rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">
        {{ err }}
      </div>

      <div class="mt-6 flex gap-3">
        <button
            class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-medium text-white hover:bg-slate-800"
            :disabled="loading"
            @click="submit"
        >
          {{ loading ? 'Creating...' : 'Register' }}
        </button>

        <RouterLink
            to="/customer/login"
            class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
          Back to Login
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { customerRegister } from '../../api/customer'

const router = useRouter()
const loading = ref(false)
const err = ref('')

const form = reactive({
  contact_name: '',
  company_name: '',
  email: '',
  password: '',
  phone: '',
  address: '',
  city: '',
  state: '',
})

async function submit() {
  loading.value = true
  err.value = ''

  try {
    await customerRegister(form)
    router.push('/customer/dashboard')
  } catch (e) {
    err.value = e?.response?.data?.message || Object.values(e?.response?.data?.errors || {}).flat()[0] || 'Registration failed'
  } finally {
    loading.value = false
  }
}
</script>