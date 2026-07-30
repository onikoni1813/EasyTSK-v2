<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-extrabold text-white flex items-center gap-2">
            <span>⏱️</span> Cron Jobs Setup
          </h1>
          <p class="text-xs text-slate-400">Manage, monitor and manually trigger background tasks for your server</p>
        </div>

        <button 
          @click="runJob('all')" 
          :disabled="runningTask !== null"
          class="btn-neon bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-2xl text-xs font-bold shadow-lg shadow-indigo-500/25 flex items-center gap-2 disabled:opacity-50 transition-all"
        >
          <svg v-if="runningTask === 'all'" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span>⚡ Run All Jobs Now</span>
        </button>
      </div>

      <!-- Success Alert Banner -->
      <div v-if="$page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center gap-3 text-emerald-400 text-sm font-semibold shadow-lg shadow-emerald-500/5 animate-fade-in">
        <span class="text-lg">✅</span>
        <span>{{ $page.props.flash.success }}</span>
      </div>

      <!-- Error Alert Banner -->
      <div v-if="$page.props.errors?.cron" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center gap-3 text-rose-400 text-sm font-semibold shadow-lg shadow-rose-500/5 animate-fade-in">
        <span class="text-lg">⚠️</span>
        <span>{{ $page.props.errors.cron }}</span>
      </div>

      <!-- How to Setup Cron Job Card -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/30">
        <h2 class="text-lg font-bold text-white mb-2">How to Setup Cron Job</h2>
        <p class="text-sm text-slate-400 mb-6">
          To run automated background tasks like releasing pending balances and cleaning up orphaned screenshots, 
          you need to add the following command to your cPanel or server's Cron Jobs list. 
          Set the frequency to <strong>Every Minute (* * * * *)</strong>.
        </p>

        <div class="bg-slate-900/50 border border-slate-700 p-4 rounded-xl flex items-center justify-between gap-4">
          <code class="text-emerald-400 text-sm font-mono overflow-x-auto whitespace-nowrap flex-1 select-all">
            * * * * * cd {{ base_path }} && php artisan schedule:run >> /dev/null 2>&1
          </code>
          <button @click="copyCommand" class="btn-neon btn-primary px-4 py-2 rounded-xl text-xs font-bold text-white whitespace-nowrap shrink-0">
            {{ copied ? '✅ Copied!' : '📋 Copy Command' }}
          </button>
        </div>
      </div>

      <!-- Active Scheduled Tasks Section -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800">
        <h3 class="text-base font-bold text-white mb-4">Active Scheduled Tasks</h3>
        
        <div class="space-y-4">
          <!-- Task 1: Offerwall Pending Release -->
          <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-slate-900/30 border border-slate-800/60 flex-wrap sm:flex-nowrap">
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-full bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-lg shrink-0">
                🕒
              </div>
              <div>
                <h4 class="font-bold text-white text-sm">Offerwall Pending Release</h4>
                <p class="text-xs text-slate-400 mt-1">Automatically checks and releases "held" offerwall earnings into the user's main balance after 24 hours.</p>
                <span class="inline-block mt-2 text-[10px] font-bold px-2 py-1 bg-slate-800 text-slate-300 rounded-md">Runs: Hourly</span>
              </div>
            </div>
            <button 
              @click="runJob('offerwall:release-pending')" 
              :disabled="runningTask !== null"
              class="px-3.5 py-2 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50"
            >
              <svg v-if="runningTask === 'offerwall:release-pending'" class="animate-spin h-3.5 w-3.5 text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>▶️ Run Now</span>
            </button>
          </div>

          <!-- Task 2: Proof Screenshot Cleanup -->
          <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-slate-900/30 border border-slate-800/60 flex-wrap sm:flex-nowrap">
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-400 text-lg shrink-0">
                🧹
              </div>
              <div>
                <h4 class="font-bold text-white text-sm">Proof Screenshot Cleanup</h4>
                <p class="text-xs text-slate-400 mt-1">Scans for reviewed user task proofs (approved/rejected) and permanently deletes their physical screenshot files 24 hours later to save server storage space.</p>
                <span class="inline-block mt-2 text-[10px] font-bold px-2 py-1 bg-slate-800 text-slate-300 rounded-md">Runs: Hourly</span>
              </div>
            </div>
            <button 
              @click="runJob('proofs:cleanup-screenshots')" 
              :disabled="runningTask !== null"
              class="px-3.5 py-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50"
            >
              <svg v-if="runningTask === 'proofs:cleanup-screenshots'" class="animate-spin h-3.5 w-3.5 text-rose-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>▶️ Run Now</span>
            </button>
          </div>

          <!-- Task 3: Health Regeneration -->
          <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-slate-900/30 border border-slate-800/60 flex-wrap sm:flex-nowrap">
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-lg shrink-0">
                ❤️
              </div>
              <div>
                <h4 class="font-bold text-white text-sm">Health Regeneration</h4>
                <p class="text-xs text-slate-400 mt-1">Passively regenerates +20 Health points daily for all users to keep them engaged, capped at a maximum of 100 Health.</p>
                <span class="inline-block mt-2 text-[10px] font-bold px-2 py-1 bg-slate-800 text-slate-300 rounded-md">Runs: Daily (Midnight)</span>
              </div>
            </div>
            <button 
              @click="runJob('health:regenerate-daily')" 
              :disabled="runningTask !== null"
              class="px-3.5 py-2 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50"
            >
              <svg v-if="runningTask === 'health:regenerate-daily'" class="animate-spin h-3.5 w-3.5 text-emerald-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>▶️ Run Now</span>
            </button>
          </div>

          <!-- Task 4: Top Referrer Contest Auto-Payout -->
          <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 flex-wrap sm:flex-nowrap">
            <div class="flex items-start gap-4">
              <div class="w-10 h-10 rounded-full bg-amber-500/20 flex items-center justify-center text-amber-300 text-lg shrink-0">
                🏆
              </div>
              <div>
                <h4 class="font-bold text-white text-sm">Top Referrer Contest Auto-Payout</h4>
                <p class="text-xs text-slate-400 mt-1">Automatically checks for expired active referral contests and credits the prize money directly into top referrers' main balance.</p>
                <span class="inline-block mt-2 text-[10px] font-bold px-2 py-1 bg-amber-500/20 text-amber-300 rounded-md border border-amber-500/30">Runs: Every 5 Minutes</span>
              </div>
            </div>
            <button 
              @click="runJob('referral-contest:distribute')" 
              :disabled="runningTask !== null"
              class="px-3.5 py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50"
            >
              <svg v-if="runningTask === 'referral-contest:distribute'" class="animate-spin h-3.5 w-3.5 text-amber-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>▶️ Run Now</span>
            </button>
          </div>

        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  base_path: String,
});

const copied = ref(false);
const runningTask = ref(null);

const copyCommand = async () => {
  const command = `* * * * * cd ${props.base_path} && php artisan schedule:run >> /dev/null 2>&1`;
  try {
    await navigator.clipboard.writeText(command);
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2000);
  } catch (err) {
    console.error('Failed to copy text: ', err);
    alert('Failed to copy to clipboard. Please copy manually.');
  }
};

const runJob = (target) => {
  runningTask.value = target;
  router.post(route('admin.cron-jobs.run'), { target }, {
    preserveScroll: true,
    onFinish: () => {
      runningTask.value = null;
    }
  });
};
</script>
