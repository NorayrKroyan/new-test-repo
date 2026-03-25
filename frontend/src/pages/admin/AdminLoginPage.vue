<template>
  <div class="flex min-h-screen items-center justify-center bg-slate-100 p-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
      <h1 class="text-2xl font-semibold text-slate-900">Admin Login</h1>
      <p class="mt-1 text-sm text-slate-500">MultiModal Portal</p>

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
              autocomplete="email"
              class="h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-slate-500"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm font-medium text-slate-700">Password</label>
          <input
              v-model="form.password"
              type="password"
              autocomplete="current-password"
              class="h-11 w-full rounded-xl border border-slate-300 px-3 outline-none focus:border-slate-500"
              @keyup.enter="submit"
          />
        </div>

        <div v-if="err" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {{ err }}
        </div>

        <button
            type="button"
            class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="loading"
            @click="submit"
        >
          {{ loading ? 'Signing in...' : 'Login' }}
        </button>

        <p class="text-center text-xs text-slate-400">
          Admin access is available through Google sign-in or manual login.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { adminGoogleLoginUrl, adminLogin } from '../../api/admin';

const router = useRouter();
const route = useRoute();

const loading = ref(false);
const err = ref('');

const form = reactive({
  email: '',
  password: '',
});

const googleErrorMessages = {
  google_auth_failed: 'Google sign-in failed.',
  google_session_expired: 'Google sign-in session expired. Please try again.',
  google_email_missing: 'Google did not return an email address.',
  account_not_found: 'No existing admin account matches that Google email.',
  wrong_account_type: 'That Google account does not match an admin user.',
  account_inactive: 'This admin account is inactive.',
  google_account_conflict: 'That Google account is already linked to another user.',
};

const googleError = computed(() => {
  const code = route.query.auth_error;
  return code ? (googleErrorMessages[code] || 'Google sign-in failed.') : '';
});

function signInWithGoogle() {
  window.location.assign(adminGoogleLoginUrl());
}

async function submit() {
  if (!form.email || !form.password) {
    err.value = 'Email and password are required.';
    return;
  }

  loading.value = true;
  err.value = '';

  try {
    await adminLogin({
      email: form.email,
      password: form.password,
    });

    await router.push('/admin/dashboard');
  } catch (e) {
    err.value =
        e?.response?.data?.message ||
        e?.response?.data?.errors?.email?.[0] ||
        'Login failed';
  } finally {
    loading.value = false;
  }
}
</script>