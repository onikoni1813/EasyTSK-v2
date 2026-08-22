<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-extrabold text-white flex items-center gap-2">
            <span>⏱️</span> Cron Jobs Setup & System Automation
          </h1>
          <p class="text-xs text-slate-400">Manage, monitor, and manually trigger background scheduled tasks for your server</p>
        </div>

        <button 
          @click="runJob('all')" 
          :disabled="runningTask !== null"
          class="btn-neon bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white px-5 py-2.5 rounded-2xl text-xs font-bold shadow-lg shadow-indigo-500/25 flex items-center gap-2 disabled:opacity-50 transition-all cursor-pointer"
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

      <!-- Server Overview Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl border border-slate-800 space-y-1">
          <span class="text-[11px] font-medium text-slate-400">🖥️ Base Path</span>
          <p class="text-xs font-mono font-bold text-slate-200 truncate" :title="base_path">{{ base_path }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-slate-800 space-y-1">
          <span class="text-[11px] font-medium text-slate-400">⚙️ PHP Binary</span>
          <p class="text-xs font-mono font-bold text-slate-200 truncate" :title="php_path">{{ php_path }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-slate-800 space-y-1">
          <span class="text-[11px] font-medium text-slate-400">🕒 Server Time</span>
          <p class="text-xs font-bold text-slate-200">{{ server_time }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-indigo-500/30 space-y-1 bg-indigo-500/5">
          <span class="text-[11px] font-medium text-indigo-400">⚡ Last "Run All" Batch</span>
          <p class="text-xs font-bold text-indigo-200">{{ last_runs?.all ? formatDate(last_runs.all) : 'Not executed yet' }}</p>
        </div>
      </div>

      <!-- How to Setup Cron Job Card -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/30 space-y-6">
        <div>
          <h2 class="text-lg font-bold text-white mb-2">How to Setup Cron Job</h2>
          <p class="text-sm text-slate-400">
            To run automated background tasks (releasing offerwall earnings, cleaning proof screenshots, daily health regeneration, contest auto-payout), 
            add a single cron job on your server to run <strong>Every Minute (* * * * *)</strong>.
          </p>
        </div>

        <!-- Option 1: cPanel Command Box -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs font-bold text-indigo-400">Option 1: For cPanel "Command" Input Box (Recommended)</span>
            <span class="text-[11px] text-slate-500">Do NOT include timing stars * * * * * in cPanel command box!</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-700/80 p-3.5 rounded-xl flex items-center justify-between gap-4">
            <code class="text-emerald-400 text-xs font-mono overflow-x-auto whitespace-nowrap flex-1 select-all">
              cd {{ base_path }} && php artisan schedule:run >> /dev/null 2>&1
            </code>
            <button @click="copyCommand(cpanelCommand, 'cpanel')" class="btn-neon btn-primary px-4 py-2 rounded-xl text-xs font-bold text-white whitespace-nowrap shrink-0">
              {{ copiedType === 'cpanel' ? '✅ Copied for cPanel!' : '📋 Copy cPanel Command' }}
            </button>
          </div>
        </div>

        <!-- Option 2: Full Crontab Line for SSH / Server Terminal -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs font-bold text-purple-400">Option 2: Full Crontab Line (For SSH / `crontab -e`)</span>
            <span class="text-[11px] text-slate-500">Includes timing 5-stars at the beginning</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-700/80 p-3.5 rounded-xl flex items-center justify-between gap-4">
            <code class="text-amber-400 text-xs font-mono overflow-x-auto whitespace-nowrap flex-1 select-all">
              * * * * * cd {{ base_path }} && php artisan schedule:run >> /dev/null 2>&1
            </code>
            <button @click="copyCommand(sshCommand, 'ssh')" class="px-4 py-2 bg-purple-600/20 hover:bg-purple-600/30 border border-purple-500/40 text-purple-300 rounded-xl text-xs font-bold whitespace-nowrap shrink-0 transition-all">
              {{ copiedType === 'ssh' ? '✅ Copied Full Line!' : '📋 Copy SSH Line' }}
            </button>
          </div>
        </div>

        <!-- Option 3: Full PHP Binary Path Crontab Line -->
        <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 space-y-3">
          <div class="flex items-center justify-between flex-wrap gap-2">
            <span class="text-xs font-bold text-cyan-400">Option 3: Full PHP Binary Path (For Servers needing custom PHP path)</span>
            <span class="text-[11px] text-slate-500">Explicitly specifies server PHP binary location</span>
          </div>
          <div class="bg-slate-900/80 border border-slate-700/80 p-3.5 rounded-xl flex items-center justify-between gap-4">
            <code class="text-cyan-400 text-xs font-mono overflow-x-auto whitespace-nowrap flex-1 select-all">
              * * * * * {{ php_path }} {{ base_path }}/artisan schedule:run >> /dev/null 2>&1
            </code>
            <button @click="copyCommand(phpBinaryCommand, 'php_binary')" class="px-4 py-2 bg-cyan-600/20 hover:bg-cyan-600/30 border border-cyan-500/40 text-cyan-300 rounded-xl text-xs font-bold whitespace-nowrap shrink-0 transition-all">
              {{ copiedType === 'php_binary' ? '✅ Copied Path Line!' : '📋 Copy PHP Path Line' }}
            </button>
          </div>
        </div>

        <!-- cPanel Common Error Warning -->
        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300 flex items-start gap-2.5">
          <span class="text-base shrink-0">💡</span>
          <div>
            <strong>cPanel "bad command / Invalid crontab file" Error Fix:</strong>
            In cPanel &rarr; Cron Jobs, set frequency dropdown to <strong>Once Per Minute (* * * * *)</strong>, and paste <strong>Option 1</strong> into the "Command" box. If you include `* * * * *` inside cPanel's Command box, cPanel duplicates the stars and causes an <em>"Invalid crontab file, can't install"</em> error.
          </div>
        </div>
      </div>

      <!-- Active Scheduled Tasks Section -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
          <h3 class="text-base font-bold text-white">Active Scheduled Tasks</h3>
          <span class="text-xs text-slate-400">Total 4 Background Jobs Configured</span>
        </div>
        
        <div class="space-y-4">
          <!-- Task 1: Offerwall Pending Release -->
          <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900/40 border border-slate-800 flex-wrap lg:flex-nowrap hover:border-indigo-500/30 transition-all">
            <div class="flex items-start gap-4 flex-1">
              <div class="w-11 h-11 rounded-2xl bg-indigo-500/20 flex items-center justify-center text-indigo-400 text-xl shrink-0">
                🕒
              </div>
              <div class="space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="font-bold text-white text-sm">Offerwall Pending Release</h4>
                  <span class="text-[10px] font-bold px-2 py-0.5 bg-indigo-500/20 text-indigo-300 rounded-md border border-indigo-500/30">Runs: Hourly</span>
                </div>
                <p class="text-xs text-slate-400">Automatically checks and releases "held" offerwall earnings into the user's main balance after 24 hours.</p>
                <div class="flex items-center gap-3 pt-1 flex-wrap text-xs">
                  <span class="px-2.5 py-1 rounded-lg bg-slate-800/80 text-slate-300 font-semibold border border-slate-700/50">
                    📊 Held Entries: <strong class="text-indigo-400">{{ stats?.pending_offerwall_count || 0 }}</strong> ({{ (stats?.pending_offerwall_amount || 0).toLocaleString() }} Coins)
                  </span>
                  <span class="text-slate-500 text-[11px]">
                    Last Executed: <strong class="text-slate-300">{{ last_runs?.['offerwall:release-pending'] ? formatDate(last_runs['offerwall:release-pending']) : 'Never' }}</strong>
                  </span>
                </div>
              </div>
            </div>

            <button 
              @click="runJob('offerwall:release-pending')" 
              :disabled="runningTask !== null"
              class="px-4 py-2.5 rounded-xl bg-indigo-500/10 hover:bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50 cursor-pointer"
            >
              <svg v-if="runningTask === 'offerwall:release-pending'" class="animate-spin h-3.5 w-3.5 text-indigo-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>▶️ Run Now</span>
            </button>
          </div>

          <!-- Task 2: Proof Screenshot Cleanup -->
          <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900/40 border border-slate-800 flex-wrap lg:flex-nowrap hover:border-rose-500/30 transition-all">
            <div class="flex items-start gap-4 flex-1">
              <div class="w-11 h-11 rounded-2xl bg-rose-500/20 flex items-center justify-center text-rose-400 text-xl shrink-0">
                🧹
              </div>
              <div class="space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="font-bold text-white text-sm">Proof Screenshot Cleanup</h4>
                  <span class="text-[10px] font-bold px-2 py-0.5 bg-rose-500/20 text-rose-300 rounded-md border border-rose-500/30">Runs: Hourly</span>
                </div>
                <p class="text-xs text-slate-400">Scans for reviewed user task proofs (approved/rejected) and permanently deletes physical screenshot files 24 hours later to save server storage space.</p>
                <div class="flex items-center gap-3 pt-1 flex-wrap text-xs">
                  <span class="px-2.5 py-1 rounded-lg bg-slate-800/80 text-slate-300 font-semibold border border-slate-700/50">
                    📊 Ready for Cleanup: <strong class="text-rose-400">{{ stats?.proofs_eligible_for_cleanup || 0 }}</strong> files
                  </span>
                  <span class="text-slate-500 text-[11px]">
                    Last Executed: <strong class="text-slate-300">{{ last_runs?.['proofs:cleanup-screenshots'] ? formatDate(last_runs['proofs:cleanup-screenshots']) : 'Never' }}</strong>
                  </span>
                </div>
              </div>
            </div>

            <button 
              @click="runJob('proofs:cleanup-screenshots')" 
              :disabled="runningTask !== null"
              class="px-4 py-2.5 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50 cursor-pointer"
            >
              <svg v-if="runningTask === 'proofs:cleanup-screenshots'" class="animate-spin h-3.5 w-3.5 text-rose-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>▶️ Run Now</span>
            </button>
          </div>

          <!-- Task 3: Health Regeneration -->
          <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-slate-900/40 border border-slate-800 flex-wrap lg:flex-nowrap hover:border-emerald-500/30 transition-all">
            <div class="flex items-start gap-4 flex-1">
              <div class="w-11 h-11 rounded-2xl bg-emerald-500/20 flex items-center justify-center text-emerald-400 text-xl shrink-0">
                ❤️
              </div>
              <div class="space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="font-bold text-white text-sm">Daily Health Regeneration</h4>
                  <span class="text-[10px] font-bold px-2 py-0.5 bg-emerald-500/20 text-emerald-300 rounded-md border border-emerald-500/30">Runs: Daily (00:05 AM)</span>
                </div>
                <p class="text-xs text-slate-400">Passively regenerates +20 Health points daily for all users to keep them engaged, capped at a maximum of 100 Health.</p>
                <div class="flex items-center gap-3 pt-1 flex-wrap text-xs">
                  <span class="px-2.5 py-1 rounded-lg bg-slate-800/80 text-slate-300 font-semibold border border-slate-700/50">
                    📊 Users Below Max HP: <strong class="text-emerald-400">{{ stats?.users_needing_health_regen || 0 }}</strong> users
                  </span>
                  <span class="text-slate-500 text-[11px]">
                    Last Executed: <strong class="text-slate-300">{{ last_runs?.['health:regenerate-daily'] ? formatDate(last_runs['health:regenerate-daily']) : 'Never' }}</strong>
                  </span>
                </div>
              </div>
            </div>

            <button 
              @click="runJob('health:regenerate-daily')" 
              :disabled="runningTask !== null"
              class="px-4 py-2.5 rounded-xl bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50 cursor-pointer"
            >
              <svg v-if="runningTask === 'health:regenerate-daily'" class="animate-spin h-3.5 w-3.5 text-emerald-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>▶️ Run Now</span>
            </button>
          </div>

          <!-- Task 4: Top Referrer Contest Auto-Payout -->
          <div class="flex items-center justify-between gap-4 p-5 rounded-2xl bg-amber-500/5 border border-amber-500/20 flex-wrap lg:flex-nowrap hover:border-amber-500/40 transition-all">
            <div class="flex items-start gap-4 flex-1">
              <div class="w-11 h-11 rounded-2xl bg-amber-500/20 flex items-center justify-center text-amber-300 text-xl shrink-0">
                🏆
              </div>
              <div class="space-y-1.5">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="font-bold text-white text-sm">Top Referrer Contest Auto-Payout</h4>
                  <span class="text-[10px] font-bold px-2 py-0.5 bg-amber-500/20 text-amber-300 rounded-md border border-amber-500/30">Runs: Every 5 Minutes</span>
                </div>
                <p class="text-xs text-slate-400">Automatically checks for expired active referral contests and credits the prize money directly into top referrers' main balance.</p>
                <div class="flex items-center gap-3 pt-1 flex-wrap text-xs">
                  <span class="px-2.5 py-1 rounded-lg bg-slate-800/80 text-slate-300 font-semibold border border-slate-700/50">
                    📊 Active Contests: <strong class="text-amber-400">{{ stats?.active_referral_contests_count || 0 }}</strong> (Due Payout: {{ stats?.due_referral_contests_count || 0 }})
                  </span>
                  <span class="text-slate-500 text-[11px]">
                    Last Executed: <strong class="text-slate-300">{{ last_runs?.['referral-contest:distribute'] ? formatDate(last_runs['referral-contest:distribute']) : 'Never' }}</strong>
                  </span>
                </div>
              </div>
            </div>

            <button 
              @click="runJob('referral-contest:distribute')" 
              :disabled="runningTask !== null"
              class="px-4 py-2.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-bold flex items-center gap-1.5 transition-all shrink-0 disabled:opacity-50 cursor-pointer"
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
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  base_path: String,
  php_path: String,
  server_time: String,
  stats: Object,
  last_runs: Object,
});

const copiedType = ref(null);
const runningTask = ref(null);

const cpanelCommand = computed(() => `cd ${props.base_path} && php artisan schedule:run >> /dev/null 2>&1`);
const sshCommand = computed(() => `* * * * * cd ${props.base_path} && php artisan schedule:run >> /dev/null 2>&1`);
const phpBinaryCommand = computed(() => `* * * * * ${props.php_path} ${props.base_path}/artisan schedule:run >> /dev/null 2>&1`);

const copyCommand = async (text, type) => {
  try {
    await navigator.clipboard.writeText(text);
    copiedType.value = type;
    setTimeout(() => { copiedType.value = null; }, 2000);
  } catch (err) {
    console.error('Failed to copy text: ', err);
    alert('Failed to copy to clipboard. Please copy manually.');
  }
};

const formatDate = (dateTimeStr) => {
  if (!dateTimeStr) return 'Never';
  const date = new Date(dateTimeStr);
  if (isNaN(date.getTime())) return dateTimeStr;
  return date.toLocaleString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true
  });
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
