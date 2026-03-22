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
        <button
            type="button"
            class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50"
            @click="signInWithGoogle"
        >
          Continue with Google
        </button>

        <div class="relative">
          <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-slate-200"></div>
          </div>

          <div class="relative flex justify-center">
            <span class="bg-white px-3 text-xs font-medium uppercase tracking-wide text-slate-400">or</span>
          </div>
        </div>

        <div v-if="googleError" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {{ googleError }}
        </div>

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
import { carrierGoogleLoginUrl, carrierLogin } from '../api/carrier'
import { customerGoogleLoginUrl, customerLogin } from '../api/customer'

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

const googleErrorMessages = {
  google_auth_failed: 'Google sign-in failed.',
  google_session_expired: 'Google sign-in session expired. Please try again.',
  google_email_missing: 'Google did not return an email address.',
  account_not_found: 'No existing account matches that Google email.',
  wrong_account_type: 'That Google account belongs to a different account type.',
  account_inactive: 'This account is inactive.',
  carrier_profile_missing: 'Carrier profile is missing for that account.',
  carrier_not_active: 'Your carrier account is pending admin activation.',
  customer_profile_missing: 'Customer profile is missing for that account.',
  customer_not_active: 'Your customer account is pending admin activation.',
  google_account_conflict: 'That Google account is already linked to another user.',
}

const googleError = computed(() => {
  const code = route.query.auth_error
  return code ? (googleErrorMessages[code] || 'Google sign-in failed.') : ''
})

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
      ...(route.query.auth_error ? { auth_error: route.query.auth_error } : {}),
    },
  })
}

function signInWithGoogle() {
  const url = role.value === 'carrier'
      ? carrierGoogleLoginUrl()
      : customerGoogleLoginUrl()

  window.location.assign(url)
}

async function submit() {
  loading.value = true
  err.value = ''

  try {
    if (role.value === 'carrier') {
      const data = await carrierLogin(form)

      if (data?.user?.must_change_password) {
        router.push('/carrier/change-password')
        return
      }

      router.push('/carrier/dashboard')
      return
    }

    const data = await customerLogin(form)

    if (data?.user?.must_change_password) {
      router.push('/customer/change-password')
      return
    }

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
