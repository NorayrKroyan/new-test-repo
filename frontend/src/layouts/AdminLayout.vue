<template>
  <div class="min-h-screen bg-slate-100">
    <div class="flex min-h-screen">
      <!-- Desktop sidebar -->
      <aside class="hidden w-72 shrink-0 border-r border-slate-200 bg-slate-950 text-white lg:flex lg:flex-col">
        <div class="border-b border-slate-800 px-6 py-5">
          <div class="text-xs uppercase tracking-[0.2em] text-slate-400">MultiModal Portal</div>
          <div class="mt-2 text-xl font-semibold">Admin Panel</div>
        </div>

        <nav class="space-y-1 p-4">
          <RouterLink :to="'/admin/dashboard'" :class="navLinkClass('/admin/dashboard')">Dashboard</RouterLink>
          <RouterLink :to="'/admin/admin-users'" :class="navLinkClass('/admin/admin-users')">Admin Users</RouterLink>
          <RouterLink :to="'/admin/carriers'" :class="navLinkClass('/admin/carriers')">Carriers</RouterLink>
          <RouterLink :to="'/admin/customers'" :class="navLinkClass('/admin/customers')">Customers</RouterLink>
          <RouterLink :to="'/admin/leads'" :class="navLinkClass('/admin/leads')">Leads</RouterLink>
          <RouterLink :to="'/admin/stages'" :class="navLinkClass('/admin/stages')">Stages</RouterLink>
          <RouterLink :to="'/admin/jobs-available'" :class="navLinkClass('/admin/jobs-available')">Jobs Available</RouterLink>
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
        <!-- Mobile header -->
        <div class="border-b border-slate-200 bg-white lg:hidden">
          <div class="flex items-center justify-between px-3 py-3">
            <div class="min-w-0">
              <div class="text-[11px] uppercase tracking-[0.18em] text-slate-400">MultiModal Portal</div>
              <div class="truncate text-base font-semibold text-slate-900">Admin Panel</div>
            </div>

            <button
                class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="mobileNavOpen = !mobileNavOpen"
            >
              {{ mobileNavOpen ? 'Close' : 'Menu' }}
            </button>
          </div>

          <div v-if="mobileNavOpen" class="border-t border-slate-200 px-3 py-3">
            <nav class="grid grid-cols-1 gap-2">
              <RouterLink to="/admin/dashboard" :class="mobileNavLinkClass('/admin/dashboard')" @click="closeMobileNav">Dashboard</RouterLink>
              <RouterLink to="/admin/admin-users" :class="mobileNavLinkClass('/admin/admin-users')" @click="closeMobileNav">Admin Users</RouterLink>
              <RouterLink to="/admin/carriers" :class="mobileNavLinkClass('/admin/carriers')" @click="closeMobileNav">Carriers</RouterLink>
              <RouterLink to="/admin/customers" :class="mobileNavLinkClass('/admin/customers')" @click="closeMobileNav">Customers</RouterLink>
              <RouterLink to="/admin/leads" :class="mobileNavLinkClass('/admin/leads')" @click="closeMobileNav">Leads</RouterLink>
              <RouterLink to="/admin/stages" :class="mobileNavLinkClass('/admin/stages')" @click="closeMobileNav">Stages</RouterLink>
              <RouterLink to="/admin/jobs-available" :class="mobileNavLinkClass('/admin/jobs-available')" @click="closeMobileNav">Jobs Available</RouterLink>

              <button
                  class="mt-2 rounded-xl border border-slate-300 px-4 py-3 text-left text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50"
                  :disabled="loggingOut"
                  @click="onLogout"
              >
                {{ loggingOut ? 'Logging out...' : 'Logout' }}
              </button>
            </nav>
          </div>
        </div>

        <div class="mx-auto w-full max-w-[1600px] p-3 sm:p-4 md:p-5">
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { adminLogout } from '../api/admin'

const router = useRouter()
const route = useRoute()

const loggingOut = ref(false)
const mobileNavOpen = ref(false)

function isActive(path) {
  return route.path === path
}

function navLinkClass(path) {
  return [
    'block rounded-xl px-4 py-3 text-sm transition',
    isActive(path) ? 'bg-slate-900 text-white' : 'text-slate-200 hover:bg-slate-900',
  ]
}

function mobileNavLinkClass(path) {
  return [
    'block rounded-xl px-4 py-3 text-sm transition',
    isActive(path)
        ? 'bg-slate-900 text-white'
        : 'border border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
  ]
}

function closeMobileNav() {
  mobileNavOpen.value = false
}

async function onLogout() {
  loggingOut.value = true

  try {
    await adminLogout()
  } catch (e) {
  } finally {
    loggingOut.value = false
    mobileNavOpen.value = false
    router.push('/admin/login')
  }
}
</script>