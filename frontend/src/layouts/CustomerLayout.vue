<template>
  <div class="min-h-screen bg-slate-100">
    <div class="flex min-h-screen">
      <aside class="hidden w-72 shrink-0 border-r border-slate-200 bg-slate-950 text-white lg:flex lg:flex-col">
        <div class="border-b border-slate-800 px-6 py-5">
          <div class="text-xs uppercase tracking-[0.2em] text-slate-400">MultiModal Portal</div>
          <div class="mt-2 text-xl font-semibold">Customer Portal</div>
        </div>

        <nav class="space-y-1 p-4">
          <RouterLink to="/customer/dashboard" class="block rounded-xl px-4 py-3 text-sm hover:bg-slate-900">Dashboard</RouterLink>
        </nav>

        <div class="mt-auto border-t border-slate-800 p-4">
          <button
              class="w-full rounded-xl border border-slate-700 px-4 py-3 text-left text-sm font-medium text-slate-200 hover:bg-slate-900 disabled:opacity-50"
              :disabled="loggingOut"
              @click="onLogout"
          >
            {{ loggingOut ? 'Logging out...' : 'Logout' }}
          </button>
        </div>
      </aside>

      <main class="min-w-0 flex-1">
        <div class="mx-auto w-full max-w-[1600px] p-4">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { customerLogout } from '../api/customer'

const router = useRouter()
const loggingOut = ref(false)

async function onLogout() {
  loggingOut.value = true

  try {
    await customerLogout()
  } catch (e) {
  } finally {
    loggingOut.value = false
    router.push('/customer/login')
  }
}
</script>