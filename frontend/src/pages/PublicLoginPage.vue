<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Login</h1>
      <p class="mt-1 text-sm text-slate-500">MultiModal Portal</p>

      <div class="mt-5 grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1">
        <button
            class="rounded-lg px-4 py-2 text-sm font-medium"
            :class="role === 'customer' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
            @click="setRole('customer')"
        >
          Customer
        </button>

        <button
            class="rounded-lg px-4 py-2 text-sm font-medium"
            :class="role === 'carrier' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
            @click="setRole('carrier')"
        >
          Carrier
        </button>
      </div>

      <div
          v-if="registeredNotice"
          class="mt-4 rounded-xl bg-amber-50 px-3 py-2 text-sm text-amber-800"
      >
        Registration submitted. An admin must change your status to <strong>Active</strong> before you can log in.
      </div>

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
          {{ loading ? 'Signing in...' : loginButtonLabel }}
        </button>

        <div class="border-t border-slate-200 pt-4 text-center">
          <div class="text-sm text-slate-500">Not registered yet?</div>

          <div class="mt-3 flex flex-col gap-2 sm:flex-row">
            <button
                class="flex-1 rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="goRegister('customer')"
            >
              Register as Customer
            </button>

            <button
                class="flex-1 rounded-xl border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="goRegister('carrier')"
            >
              Register as Carrier
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { carrierLogin } from '../api/carrier'
import { customerLogin } from '../api/customer'

const router = useRouter()
const route = useRoute()

const loading = ref(false)
const err = ref('')
const role = ref(route.query.role === 'carrier' ? 'carrier' : 'customer')

watch(
    () => route.query.role,
    (v) => {
      role.value = v === 'carrier' ? 'carrier' : 'customer'
    }
)

const registeredNotice = computed(() => route.query.registered === '1')

const form = reactive({
  email: '',
  password: '',
})

const loginButtonLabel = computed(() => {
  return role.value === 'carrier' ? 'Login as Carrier' : 'Login as Customer'
})

function setRole(nextRole) {
  role.value = nextRole
  router.replace({
    path: '/login',
    query: {
      role: nextRole,
      ...(route.query.registered === '1' ? { registered: '1' } : {}),
    },
  })
}

async function submit() {
  loading.value = true
  err.value = ''

  try {
    if (role.value === 'carrier') {
      await carrierLogin(form)
      router.push('/carrier/dashboard')
      return
    }

    await customerLogin(form)
    router.push('/customer/dashboard')
  } catch (e) {
    err.value =
        e?.response?.data?.message ||
        e?.response?.data?.errors?.email?.[0] ||
        'Login failed'
  } finally {
    loading.value = false
  }
}

function goRegister(nextRole) {
  router.push({
    path: '/register',
    query: { role: nextRole },
  })
}
</script>