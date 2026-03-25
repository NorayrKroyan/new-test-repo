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

        <div v-if="googleError" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {{ googleError }}
        </div>

        <p class="text-center text-xs text-slate-400">
          Admin access is available through Google sign-in only.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { adminGoogleLoginUrl } from '../../api/admin'

const route = useRoute()

const googleErrorMessages = {
  google_auth_failed: 'Google sign-in failed.',
  google_session_expired: 'Google sign-in session expired. Please try again.',
  google_email_missing: 'Google did not return an email address.',
  account_not_found: 'No existing admin account matches that Google email.',
  wrong_account_type: 'That Google account does not match an admin user.',
  account_inactive: 'This admin account is inactive.',
  google_account_conflict: 'That Google account is already linked to another user.',
}

const googleError = computed(() => {
  const code = route.query.auth_error
  return code ? (googleErrorMessages[code] || 'Google sign-in failed.') : ''
})

function signInWithGoogle() {
  window.location.assign(adminGoogleLoginUrl())
}
</script>