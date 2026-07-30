<template>
  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col items-center justify-center p-4 relative overflow-hidden font-sans">
    
    <!-- Background Glow Effects -->
    <div class="absolute -top-40 -left-40 w-96 h-96 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"></div>

    <div class="w-full max-w-xl relative z-10">
      
      <!-- Admin Mode Banner Box -->
      <div v-if="isAdmin" class="mb-4 p-4 rounded-2xl bg-amber-500/15 border border-amber-500/40 text-amber-300 text-xs space-y-2.5 shadow-xl backdrop-blur-md">
        <div class="flex items-center justify-between">
          <span class="font-extrabold flex items-center gap-1.5 text-amber-400">
            <ShieldCheckIcon class="w-4 h-4 text-amber-400 shrink-0" />
            ADMIN MODE DETECTED
          </span>
          <span class="px-2 py-0.5 rounded bg-amber-400/20 text-[10px] font-mono font-bold uppercase tracking-wider">Administrator</span>
        </div>
        <p class="text-[11px] text-slate-300 leading-relaxed">
          Maintenance mode is currently <strong>ACTIVE</strong>. Standard users & guests are seeing this screen. As an admin, you can manage settings or preview the live site.
        </p>
        <div class="flex flex-wrap items-center gap-2 pt-1">
          <Link :href="'/' + ($page.props.admin_path || 'admin') + '/settings'" class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold transition-all text-xs flex items-center gap-1">
            <SettingsIcon class="w-3.5 h-3.5" />
            Admin Settings
          </Link>
          <a href="/?bypass=1" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 border border-amber-500/30 text-amber-300 font-semibold transition-all text-xs flex items-center gap-1">
            <EyeIcon class="w-3.5 h-3.5" />
            Preview Live Site (?bypass=1)
          </a>
        </div>
      </div>

      <!-- Glass Card -->
      <div class="glass-card p-8 md:p-10 rounded-3xl border border-slate-800/80 bg-slate-900/60 shadow-2xl backdrop-blur-xl text-center space-y-6">
        
        <!-- Header / Original Easytsk V2 Logo Branding -->
        <div class="flex justify-center items-center gap-2.5">
          <template v-if="$page.props.siteSettings?.site_logo">
            <img :src="$page.props.siteSettings.site_logo" alt="Site Logo" class="h-10 w-auto max-h-10 object-contain drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]" />
          </template>
          <template v-else>
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-violet-600 to-cyan-400 flex items-center justify-center font-black text-white text-lg shadow-[0_0_15px_rgba(99,102,241,0.5)]">
              E
            </div>
            <div class="flex items-baseline gap-1.5">
              <span class="font-black text-2xl tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-purple-300 to-cyan-300 bg-[length:200%_auto] animate-gradient-x drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]">
                {{ $page.props.siteSettings?.site_short_name || 'Easytsk' }}
              </span>
              <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold uppercase tracking-wider shadow-[0_0_8px_rgba(99,102,241,0.2)]">V2</span>
            </div>
          </template>
        </div>

        <!-- Animated Icon Container -->
        <div class="relative w-24 h-24 mx-auto flex items-center justify-center">
          <div class="absolute inset-0 bg-amber-500/20 rounded-full animate-ping opacity-30"></div>
          <div class="w-24 h-24 rounded-full bg-slate-900 border border-amber-500/30 flex items-center justify-center text-amber-400 shadow-xl shadow-amber-500/10 relative">
            <WrenchIcon class="w-12 h-12 animate-pulse" />
          </div>
        </div>

        <!-- Status Badge -->
        <div>
          <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs font-bold uppercase tracking-wider">
            <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
            Scheduled Maintenance Active
          </span>
        </div>

        <!-- Title & Notice -->
        <div class="space-y-3">
          <h1 class="text-3xl font-extrabold text-white tracking-tight">
            We'll be back shortly!
          </h1>
          <p class="text-slate-300 text-sm md:text-base leading-relaxed px-2">
            {{ message || 'We are currently performing scheduled maintenance to upgrade our platform. Please check back shortly!' }}
          </p>
        </div>

        <!-- Information Info Box -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 space-y-2">
          <div class="flex items-center justify-center gap-2 font-semibold text-slate-300">
            <ShieldAlertIcon class="w-4 h-4 text-amber-400" />
            <span>Your earnings, tasks, and account data are 100% safe.</span>
          </div>
          <p class="text-[11px] text-slate-500">
            All user balances and pending submissions remain intact. Service will resume as soon as systems are synchronized.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="pt-2 flex items-center justify-center">
          <button @click="refreshPage" class="w-full sm:w-auto inline-flex items-center justify-center px-8 py-3.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm transition-all shadow-lg shadow-indigo-900/30">
            <RefreshCwIcon class="w-4 h-4 mr-2" />
            Check Status Again
          </button>
        </div>

      </div>

      <!-- Footer Note -->
      <div class="text-center mt-6 text-xs text-slate-500">
        &copy; {{ new Date().getFullYear() }} {{ $page.props.siteSettings?.site_name || 'Easytsk V2' }}. All rights reserved.
      </div>
    </div>

  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { WrenchIcon, ShieldAlertIcon, ShieldCheckIcon, RefreshCwIcon, SettingsIcon, EyeIcon } from 'lucide-vue-next';

defineProps({
  message: String,
  isAdmin: Boolean,
});

const refreshPage = () => {
  window.location.reload();
};
</script>
