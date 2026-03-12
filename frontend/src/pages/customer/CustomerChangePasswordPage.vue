<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Change Customer Password</h1>
      <p class="mt-1 text-sm text-slate-500">Your account is active, but you must change your password first.</p>

      <div class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800">
        If your account was activated by admin, your temporary password is <strong>ChangeMe123!</strong>
      </div>

      <div class="mt-6 space-y-4">
        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Current Password</label>
          <input
              v-model="form.current_password"
              type="password"
              class="h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-slate-500"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">New Password</label>
          <input
              v-model="form.password"
              type="password"
              class="h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-slate-500"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Confirm New Password</label>
          <input
              v-model="form.password_confirmation"
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
          {{ loading ? 'Saving...' : 'Change Password' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { changeCustomerPassword } from '../../api/customer'

const router = useRouter()

const loading = ref(false)
const err = ref('')

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
})

async function submit() {
  loading.value = true
  err.value = ''

  try {
    await changeCustomerPassword(form)
    router.push('/customer/dashboard')
  } catch (e) {
    err.value =
        e?.response?.data?.message ||
        e?.response?.data?.errors?.current_password?.[0] ||
        e?.response?.data?.errors?.password?.[0] ||
        'Password change failed'
  } finally {
    loading.value = false
  }
}
</script>