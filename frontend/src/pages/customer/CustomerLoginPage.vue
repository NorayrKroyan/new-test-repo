<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Customer Login</h1>
      <p class="mt-1 text-sm text-slate-500">MultiModal Portal</p>

      <div class="mt-6 space-y-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
          <input
              v-model="form.email"
              type="email"
              class="h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-slate-500"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
          <input
              v-model="form.password"
              type="password"
              class="h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-slate-500"
          />
        </div>

        <div v-if="err" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {{ err }}
        </div>

        <button
            class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white hover:bg-slate-800"
            :disabled="loading"
            @click="submit"
        >
          {{ loading ? 'Signing in...' : 'Login' }}
        </button>

        <RouterLink
            to="/customer/register"
            class="block text-center text-sm font-medium text-sky-700 hover:underline"
        >
          Register as Customer
        </RouterLink>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { customerLogin } from '../../api/customer'

const router = useRouter()
const loading = ref(false)
const err = ref('')

const form = reactive({
  email: '',
  password: '',
})

async function submit() {
  loading.value = true
  err.value = ''

  try {
    await customerLogin(form)
    router.push('/customer/dashboard')
  } catch (e) {
    err.value = e?.response?.data?.errors?.email?.[0] || 'Login failed'
  } finally {
    loading.value = false
  }
}
</script>