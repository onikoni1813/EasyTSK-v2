<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row relative overflow-x-hidden">
    <!-- Mobile Header -->
    <div class="md:hidden flex items-center justify-between p-4 glass-card border-b border-slate-800/80 sticky top-0 z-40">
      <div class="flex items-center space-x-3">
        <template v-if="$page.props.siteSettings?.site_logo">
          <img :src="$page.props.siteSettings.site_logo" alt="Admin Logo" class="h-7 w-auto max-h-7 object-contain" />
        </template>
        <template v-else>
          <div class="w-8 h-8 rounded-lg bg-rose-600 flex items-center justify-center font-extrabold text-white text-xs">
            AD
          </div>
        </template>
        <span class="font-extrabold text-sm tracking-tight text-white">Easytsk Admin</span>
      </div>
      <button @click="isSidebarOpen = true" class="text-slate-300 hover:text-white focus:outline-none">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
      </button>
    </div>

    <!-- Mobile Overlay -->
    <div v-if="isSidebarOpen" @click="isSidebarOpen = false" class="fixed inset-0 bg-black/60 z-40 md:hidden backdrop-blur-sm transition-opacity duration-300"></div>

    <!-- Admin Sidebar -->
    <aside :class="['fixed inset-y-0 left-0 z-50 w-64 bg-slate-950/95 md:bg-transparent admin-sidebar border-r border-slate-800/80 p-5 space-y-6 transform transition-transform duration-300 ease-in-out md:static md:translate-x-0 flex-shrink-0', isSidebarOpen ? 'translate-x-0' : '-translate-x-full']">
      <div class="flex items-center justify-between md:justify-start">
        <div class="flex items-center space-x-3">
          <template v-if="$page.props.siteSettings?.site_logo">
            <img :src="$page.props.siteSettings.site_logo" alt="Admin Logo" class="h-8 w-auto max-h-8 object-contain" />
          </template>
          <template v-else>
            <div class="w-9 h-9 rounded-xl bg-rose-600 flex items-center justify-center font-extrabold text-white">
              AD
            </div>
          </template>
          <span class="font-extrabold text-base tracking-tight text-white">Easytsk Admin</span>
        </div>
        <button @click="isSidebarOpen = false" class="md:hidden text-slate-400 hover:text-white focus:outline-none">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <nav class="space-y-1">
        <Link :href="adminPath" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url === adminPath ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>📊 Overview</span>
        </Link>
        <Link :href="`${adminPath}/users`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/users`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>👥 Users</span>
        </Link>
        <Link :href="`${adminPath}/password-tickets`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/password-tickets`) ? 'bg-amber-600 text-white' : 'text-amber-400/90 hover:bg-slate-900'">
          <span>🔐 Password Tickets</span>
        </Link>
        <Link :href="`${adminPath}/support-tickets`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/support-tickets`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>💬 User Support Tickets</span>
        </Link>
        <Link :href="`${adminPath}/reviews`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/reviews`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>📸 Proof Review Hub</span>
        </Link>
        <Link :href="`${adminPath}/tasks`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/tasks`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>🧩 Task Manager</span>
        </Link>
        <Link :href="`${adminPath}/withdrawals`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/withdrawals`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>💸 Withdrawals Payout</span>
        </Link>
        <Link :href="`${adminPath}/offerwalls`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/offerwalls`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>💰 Offerwalls Manager</span>
        </Link>
        <Link :href="`${adminPath}/campaigns`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/campaigns`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>📢 Campaign Review</span>
        </Link>
        <Link :href="`${adminPath}/campaign-services`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/campaign-services`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>🛠️ Campaign Services</span>
        </Link>
        <Link :href="`${adminPath}/promo-codes`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/promo-codes`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>🎟️ Promo Codes</span>
        </Link>
        <Link :href="`${adminPath}/settings`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/settings`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>⚙️ System Settings</span>
        </Link>
        <Link :href="`${adminPath}/levels`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/levels`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>🏆 Levels Manager</span>
        </Link>
        <Link :href="`${adminPath}/cron-jobs`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/cron-jobs`) ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900'">
          <span>⏱️ Cron Jobs</span>
        </Link>
        <Link :href="`${adminPath}/referral-contests`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/referral-contests`) ? 'bg-amber-600 text-white' : 'text-amber-400/90 hover:bg-slate-900'">
          <span>🏆 Referral Contests</span>
        </Link>
        <Link :href="`${adminPath}/deploy`" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold" :class="$page.url.startsWith(`${adminPath}/deploy`) ? 'bg-emerald-600 text-white' : 'text-emerald-400/80 hover:bg-slate-900'">
          <span>🚀 Deployment Center</span>
        </Link>
        <Link href="/dashboard" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-emerald-400 hover:bg-slate-900">
          <span>🏠 Exit to User Site</span>
        </Link>
        <button @click="logout" class="flex items-center space-x-3 px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-400 hover:bg-slate-900 w-full text-left">
          <span>🚪 Logout</span>
        </button>
      </nav>
    </aside>

    <main class="flex-1 w-full p-6 min-w-0 flex flex-col justify-start items-stretch" style="margin-top: 0 !important;">
      <!-- Maintenance Mode Active Banner for Admin -->
      <div v-if="$page.props.siteSettings?.is_maintenance_mode" class="mb-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 text-amber-400 text-xs font-semibold shadow-lg shadow-amber-900/10">
        <div class="flex items-center gap-2">
          <span class="w-2.5 h-2.5 rounded-full bg-amber-400 animate-ping shrink-0"></span>
          <span><strong>Platform Maintenance Mode is ACTIVE!</strong> Standard users see the Maintenance Notice page. Admins have unrestricted access.</span>
        </div>
        <Link :href="`${adminPath}/settings`" class="px-3 py-1.5 bg-amber-500/20 hover:bg-amber-500/30 border border-amber-500/40 rounded-xl text-amber-300 font-bold transition-all shrink-0">
          Manage Settings &rarr;
        </Link>
      </div>

      <Transition name="toast">
        <div v-if="$page.props.flash?.success" class="max-w-md mx-auto mb-4 px-4 py-2.5 toast-success rounded-xl text-xs font-semibold text-center shadow-xl animate-slide-in-up">
          ✅ {{ $page.props.flash.success }}
        </div>
      </Transition>
      <Transition name="toast">
        <div v-if="$page.props.flash?.error" class="max-w-md mx-auto mb-4 px-4 py-2.5 toast-error rounded-xl text-xs font-semibold text-center shadow-xl animate-slide-in-up">
          ❌ {{ $page.props.flash.error }}
        </div>
      </Transition>
      <slot />
    </main>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';

const page = usePage();
const isSidebarOpen = ref(false);
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

watch(() => page.url, () => {
  isSidebarOpen.value = false;
});

const logout = () => router.post('/logout');
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.35s ease; }
.toast-enter-from  { transform: translateY(-10px); opacity: 0; }
.toast-leave-to    { transform: translateY(-10px); opacity: 0; }
</style>
