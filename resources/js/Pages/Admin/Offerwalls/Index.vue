<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed, watch } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';

const props = defineProps({
  offerwalls: {
    type: Array,
    default: () => []
  },
  logs: {
    type: Object,
    default: () => ({ data: [], links: [] })
  },
  logStats: {
    type: Object,
    default: () => ({})
  },
  providers: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({ search: '', status: 'all', provider: 'all' })
  },
  lastCleanup: {
    type: String,
    default: null
  }
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

// Active Tab ('config' or 'history')
const urlParams = new URLSearchParams(window.location.search);
const initialTab = urlParams.get('tab') === 'history' || urlParams.has('search') || urlParams.has('status') || urlParams.has('provider') || urlParams.has('page')
  ? 'history'
  : 'config';
const activeTab = ref(initialTab);

// Modal Controls for Offerwalls Config
const showModal = ref(false);
const showPostbackModal = ref(false);
const selectedOfferwall = ref(null);

const form = ref({
  name: '', iframe_url_pattern: '', reward_ratio: 1.00, secret_key: '', image_url: '', description: '', status: true,
  param_user_id: 'user_id', param_amount: 'amount', param_transaction_id: 'transaction_id', param_status: 'status', param_secret_key: 'secure', status_chargeback_value: 'reversed', allowed_ips: ''
});
const isEditing = ref(false);
const currentId = ref(null);

function openModal(offerwall = null) {
  if (offerwall) {
    isEditing.value = true;
    currentId.value = offerwall.id;
    form.value = { ...offerwall };
  } else {
    isEditing.value = false;
    currentId.value = null;
    form.value = { 
      name: '', iframe_url_pattern: '', reward_ratio: 1.00, secret_key: '', image_url: '', description: '', status: true,
      param_user_id: 'user_id', param_amount: 'amount', param_transaction_id: 'transaction_id', param_status: 'status', param_secret_key: 'secure', status_chargeback_value: 'reversed', allowed_ips: ''
    };
  }
  showModal.value = true;
}

function save() {
  if (isEditing.value) {
    router.put(`${adminPath.value}/offerwalls/` + currentId.value, form.value, { onSuccess: () => showModal.value = false });
  } else {
    router.post(`${adminPath.value}/offerwalls`, form.value, { onSuccess: () => showModal.value = false });
  }
}

function toggleStatus(ow) {
  router.post(`${adminPath.value}/offerwalls/${ow.id}/toggle`);
}

function deleteOfferwall(id) {
  if (confirm('Are you sure you want to delete this offerwall?')) {
    router.delete(`${adminPath.value}/offerwalls/` + id);
  }
}

function openPostbackGuide(offerwall) {
  selectedOfferwall.value = offerwall;
  showPostbackModal.value = true;
}

const appUrl = computed(() => window.location.origin);

const postbackUrl = computed(() => {
  if (!selectedOfferwall.value) return '';
  const provider = selectedOfferwall.value.name.toLowerCase().replace(/\s+/g, '');
  
  const pUser = selectedOfferwall.value.param_user_id || 'user_id';
  const pAmount = selectedOfferwall.value.param_amount || 'amount';
  const pTx = selectedOfferwall.value.param_transaction_id || 'transaction_id';
  
  let url = `${appUrl.value}/postback/${provider}?${pUser}={user_id}&${pAmount}={reward}&${pTx}={tx_id}`;
  if (selectedOfferwall.value.secret_key) {
    const pSecure = selectedOfferwall.value.param_secret_key || 'secure';
    url += `&${pSecure}=${selectedOfferwall.value.secret_key}`;
  }
  return url;
});

// History & Logs Logic
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || 'all');
const provider = ref(props.filters?.provider || 'all');

let searchDebounce = null;
function onSearchInput() {
  clearTimeout(searchDebounce);
  searchDebounce = setTimeout(() => {
    applyFilters();
  }, 400);
}

function applyFilters() {
  router.get(`${adminPath.value}/offerwalls`, {
    tab: 'history',
    search: search.value || undefined,
    status: status.value !== 'all' ? status.value : undefined,
    provider: provider.value !== 'all' ? provider.value : undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
}

function resetFilters() {
  search.value = '';
  status.value = 'all';
  provider.value = 'all';
  router.get(`${adminPath.value}/offerwalls`, { tab: 'history' }, {
    preserveState: true,
    preserveScroll: true,
  });
}

// Force release individual log
const releasingLogId = ref(null);
function forceReleaseLog(log) {
  if (confirm(`Are you sure you want to immediately release ${log.amount} pts for user "${log.user?.name || log.user_id}" to main balance?`)) {
    releasingLogId.value = log.id;
    router.post(`${adminPath.value}/offerwalls/logs/${log.id}/release`, {}, {
      preserveScroll: true,
      onFinish: () => releasingLogId.value = null
    });
  }
}

// Cleanup Modal Logic
const showCleanupModal = ref(false);
const cleanupDays = ref(30);
const isCleaning = ref(false);

function executeCleanup() {
  isCleaning.value = true;
  router.post(`${adminPath.value}/offerwalls/logs/cleanup`, { days: cleanupDays.value }, {
    preserveScroll: true,
    onFinish: () => {
      isCleaning.value = false;
      showCleanupModal.value = false;
    }
  });
}

// Helper functions
const copiedId = ref(null);
function copyToClipboard(text, id = null) {
  navigator.clipboard.writeText(text);
  if (id) {
    copiedId.value = id;
    setTimeout(() => { copiedId.value = null; }, 1800);
  } else {
    alert('Copied to clipboard!');
  }
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  });
}

function getReleaseStatus(log) {
  if (log.status !== 'pending') return null;
  if (!log.release_time) return { label: 'Due for release', isDue: true };
  const releaseDate = new Date(log.release_time);
  const now = new Date();
  if (releaseDate <= now) {
    return { label: 'Due for release', isDue: true };
  }
  const diffMs = releaseDate - now;
  const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
  const diffMins = Math.floor((diffMs % (1000 * 60 * 60)) / (1000 * 60));
  return {
    label: diffHours > 0 ? `Unlocks in ${diffHours}h ${diffMins}m` : `Unlocks in ${diffMins}m`,
    isDue: false
  };
}
</script>

<template>
  <AdminLayout title="Offerwalls Hub">
    <!-- Top Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
      <div>
        <h2 class="text-2xl font-bold text-white tracking-tight flex items-center gap-3">
          <span>💰 Offerwalls & Postback Hub</span>
        </h2>
        <p class="text-sm text-slate-400 mt-1">Manage network integrations, conversion callbacks, and hold liability.</p>
      </div>

      <div class="flex items-center gap-3">
        <button 
          v-if="activeTab === 'config'"
          @click="openModal()" 
          class="btn-neon bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white px-4 py-2.5 rounded-2xl text-xs font-bold shadow-lg shadow-emerald-500/20 flex items-center gap-2 transition-all cursor-pointer"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
          </svg>
          Add Offerwall
        </button>

        <button 
          v-if="activeTab === 'history'"
          @click="showCleanupModal = true" 
          class="px-4 py-2.5 rounded-2xl text-xs font-bold bg-rose-500/10 hover:bg-rose-500/20 text-rose-300 border border-rose-500/30 flex items-center gap-2 transition-all cursor-pointer"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
          </svg>
          🧹 Clean Old Logs
        </button>
      </div>
    </div>

    <!-- Modern Tabs Navigation -->
    <div class="flex items-center gap-2 p-1.5 bg-slate-900/80 rounded-2xl border border-slate-800 mb-6 max-w-xl">
      <button 
        @click="activeTab = 'config'"
        class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
        :class="activeTab === 'config' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'"
      >
        <span>⚙️ Offerwalls Integration</span>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="activeTab === 'config' ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400'">
          {{ offerwalls.length }}
        </span>
      </button>

      <button 
        @click="activeTab = 'history'"
        class="flex-1 py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer"
        :class="activeTab === 'history' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-500/25' : 'text-slate-400 hover:text-white hover:bg-white/5'"
      >
        <span>📜 Conversion History</span>
        <span v-if="logStats?.pending_count > 0" class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500 text-slate-950 animate-pulse">
          {{ logStats.pending_count }} Pending
        </span>
        <span v-else class="px-2 py-0.5 rounded-full text-[10px] font-extrabold" :class="activeTab === 'history' ? 'bg-white/20 text-white' : 'bg-slate-800 text-slate-400'">
          {{ (logStats?.total_conversions || 0).toLocaleString() }}
        </span>
      </button>
    </div>

    <!-- TAB 1: OFFERWALLS CONFIGURATION -->
    <div v-show="activeTab === 'config'" class="space-y-6">
      <div v-if="offerwalls.length === 0" class="glass-card p-12 text-center rounded-3xl border border-white/5 space-y-4">
        <div class="text-4xl">💰</div>
        <h3 class="text-lg font-bold text-white">No Offerwalls Configured</h3>
        <p class="text-xs text-slate-400 max-w-sm mx-auto">Get started by adding your first network provider (BitLabs, Timewall, Notik, etc.).</p>
        <button @click="openModal()" class="px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold transition-all cursor-pointer">
          Add Offerwall Now
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        <div v-for="ow in offerwalls" :key="ow.id" class="bg-[#1e293b]/50 backdrop-blur-xl border border-white/5 hover:border-emerald-500/30 transition-colors p-6 rounded-3xl shadow-xl relative overflow-hidden group">
          <div class="absolute top-0 right-0 p-4 opacity-75 group-hover:opacity-100 transition-opacity">
            <button 
              @click.stop="toggleStatus(ow)"
              title="Click to toggle status"
              :class="ow.status ? 'bg-emerald-500/20 text-emerald-400 hover:bg-emerald-500/30' : 'bg-rose-500/20 text-rose-400 hover:bg-rose-500/30'" 
              class="px-3 py-1 rounded-full text-xs font-bold tracking-wide uppercase transition-colors cursor-pointer flex items-center gap-1.5"
            >
              <span class="w-1.5 h-1.5 rounded-full" :class="ow.status ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
              {{ ow.status ? 'Active' : 'Inactive' }}
            </button>
          </div>
          
          <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-2xl bg-black/40 p-2 flex items-center justify-center border border-white/10">
              <img v-if="ow.image_url && !ow.image_error" :src="ow.image_url" class="max-w-full max-h-full rounded-xl object-contain" :alt="ow.name" @error="ow.image_error = true">
              <span v-else class="text-2xl font-bold text-slate-400">{{ ow.name.charAt(0) }}</span>
            </div>
            <div>
              <h3 class="text-xl font-bold text-white">{{ ow.name }}</h3>
              <p class="text-sm text-slate-400">Ratio: <span class="text-emerald-400 font-semibold">{{ ow.reward_ratio }}x</span></p>
              <p v-if="ow.description" class="text-xs text-slate-500 mt-1.5 leading-relaxed line-clamp-2" :title="ow.description">{{ ow.description }}</p>
            </div>
          </div>

          <div class="space-y-3 mt-4">
            <button @click="openPostbackGuide(ow)" class="w-full bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 py-2.5 rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-2 cursor-pointer">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
              </svg>
              Postback Setup
            </button>
            
            <div class="flex gap-3">
              <button @click="openModal(ow)" class="flex-1 bg-white/5 text-white hover:bg-white/10 py-2.5 rounded-xl text-sm font-semibold transition-colors cursor-pointer">Edit</button>
              <button @click="deleteOfferwall(ow.id)" class="flex-1 bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 py-2.5 rounded-xl text-sm font-semibold transition-colors cursor-pointer">Delete</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TAB 2: CONVERSION & POSTBACK HISTORY -->
    <div v-show="activeTab === 'history'" class="space-y-6">
      <!-- 4 Stat Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Conversions -->
        <div class="glass-card p-4 rounded-2xl border border-indigo-500/15 relative overflow-hidden">
          <div class="text-[10px] font-bold uppercase tracking-wider mb-1 text-indigo-400">Total Conversions</div>
          <div class="text-2xl font-black text-white stat-number">{{ (logStats?.total_conversions || 0).toLocaleString() }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">{{ Number(logStats?.total_amount || 0).toLocaleString() }} pts credited</div>
        </div>

        <!-- Pending Holds (Liability) -->
        <div class="glass-card p-4 rounded-2xl border border-amber-500/20 relative overflow-hidden bg-amber-500/5">
          <div class="absolute top-0 right-0 w-16 h-16 rounded-full blur-2xl opacity-20 bg-amber-500"></div>
          <div class="text-[10px] font-bold uppercase tracking-wider mb-1 text-amber-400 flex items-center justify-between">
            <span>Pending Holds</span>
            <span v-if="logStats?.pending_count > 0" class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
          </div>
          <div class="text-2xl font-black text-amber-300 stat-number">{{ (logStats?.pending_count || 0).toLocaleString() }}</div>
          <div class="text-[10px] text-amber-400/80 mt-0.5 font-semibold">{{ Number(logStats?.pending_amount || 0).toLocaleString() }} pts on 24h hold</div>
        </div>

        <!-- Approved -->
        <div class="glass-card p-4 rounded-2xl border border-emerald-500/15 relative overflow-hidden">
          <div class="text-[10px] font-bold uppercase tracking-wider mb-1 text-emerald-400">Approved & Unlocked</div>
          <div class="text-2xl font-black text-white stat-number">{{ (logStats?.approved_count || 0).toLocaleString() }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">{{ Number(logStats?.approved_amount || 0).toLocaleString() }} pts in main balances</div>
        </div>

        <!-- Reversed -->
        <div class="glass-card p-4 rounded-2xl border border-rose-500/15 relative overflow-hidden">
          <div class="text-[10px] font-bold uppercase tracking-wider mb-1 text-rose-400">Reversed / Chargebacks</div>
          <div class="text-2xl font-black text-rose-400 stat-number">{{ (logStats?.reversed_count || 0).toLocaleString() }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">{{ Number(logStats?.reversed_amount || 0).toLocaleString() }} pts clawed back</div>
        </div>
      </div>

      <!-- Filters & Actions Toolbar -->
      <div class="glass-card p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
        <div class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
          <!-- Search -->
          <div class="relative flex-1">
            <input 
              v-model="search" 
              @input="onSearchInput"
              type="text" 
              placeholder="Search by User, Email, User ID, or Tx ID..." 
              class="w-full bg-slate-950/80 border border-slate-700/80 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:border-indigo-500 outline-none"
            >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>

          <!-- Status Filter -->
          <select 
            v-model="status" 
            @change="applyFilters" 
            class="bg-slate-950/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-300 focus:border-indigo-500 outline-none cursor-pointer"
          >
            <option value="all">All Statuses</option>
            <option value="pending">⏳ Pending Holds Only</option>
            <option value="approved">✅ Approved Only</option>
            <option value="reversed">↩️ Reversed / Chargebacks Only</option>
          </select>

          <!-- Provider Filter -->
          <select 
            v-model="provider" 
            @change="applyFilters" 
            class="bg-slate-950/80 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-slate-300 focus:border-indigo-500 outline-none cursor-pointer"
          >
            <option value="all">All Providers</option>
            <option v-for="p in providers" :key="p" :value="p">{{ p }}</option>
          </select>

          <!-- Reset Filter -->
          <button 
            v-if="search || status !== 'all' || provider !== 'all'"
            @click="resetFilters" 
            class="px-3 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-semibold transition-all shrink-0 cursor-pointer"
            title="Reset Filters"
          >
            Reset
          </button>
        </div>

        <div class="text-[11px] text-slate-500 flex items-center gap-2 self-end md:self-center">
          <span>Auto-Clean: <strong>Every night (30+ days)</strong></span>
          <span v-if="lastCleanup" class="text-slate-400">• Last: {{ formatDate(lastCleanup) }}</span>
        </div>
      </div>

      <!-- Logs Table -->
      <div class="glass-card rounded-3xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-900/60 text-slate-400 uppercase tracking-wider text-[10px] border-b border-white/5 font-semibold">
              <tr>
                <th class="px-5 py-3.5">User</th>
                <th class="px-4 py-3.5">Provider</th>
                <th class="px-4 py-3.5">Transaction ID</th>
                <th class="px-4 py-3.5">Points</th>
                <th class="px-4 py-3.5">Status & Hold</th>
                <th class="px-4 py-3.5">Recorded At</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-slate-300 font-medium">
              <tr v-if="logs.data.length === 0">
                <td colspan="7" class="text-center py-12 text-slate-500 text-xs">
                  <div class="text-3xl mb-2">📜</div>
                  No conversion postback logs found matching your filters.
                </td>
              </tr>

              <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-900/40 transition-colors">
                <!-- User -->
                <td class="px-5 py-3">
                  <div v-if="log.user">
                    <Link 
                      :href="`${adminPath}/users?search=${encodeURIComponent(log.user.email || log.user.name)}`" 
                      class="font-bold text-white hover:text-indigo-400 hover:underline transition-colors flex items-center gap-1.5"
                    >
                      <span>{{ log.user.name }}</span>
                      <span class="text-[10px] px-1.5 py-0.2 rounded bg-indigo-500/20 text-indigo-300 font-mono">#{{ log.user.id }}</span>
                    </Link>
                    <div class="text-[10px] text-slate-500 truncate max-w-[160px]">{{ log.user.email || log.user.phone || 'No contact' }}</div>
                  </div>
                  <div v-else class="text-slate-500">
                    <span class="font-bold">User #{{ log.user_id }}</span>
                    <div class="text-[10px] text-rose-400/80">Deleted Account</div>
                  </div>
                </td>

                <!-- Provider -->
                <td class="px-4 py-3">
                  <span class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-800 text-slate-200 border border-slate-700/60 inline-flex items-center gap-1">
                    {{ log.provider }}
                  </span>
                </td>

                <!-- Transaction ID -->
                <td class="px-4 py-3">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-[11px] text-indigo-300 select-all truncate max-w-[150px]" :title="log.transaction_id">
                      {{ log.transaction_id }}
                    </span>
                    <button 
                      @click="copyToClipboard(log.transaction_id, log.id)"
                      class="text-slate-500 hover:text-white transition-colors cursor-pointer shrink-0" 
                      title="Copy transaction ID"
                    >
                      <span v-if="copiedId === log.id" class="text-emerald-400 text-[10px]">✓</span>
                      <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                      </svg>
                    </button>
                  </div>
                </td>

                <!-- Amount -->
                <td class="px-4 py-3">
                  <span class="font-black text-sm" :class="log.status === 'reversed' ? 'text-rose-400 line-through' : 'text-emerald-400'">
                    +{{ Number(log.amount || 0).toLocaleString() }}
                  </span>
                  <span class="text-[10px] text-slate-500 ml-1">pts</span>
                </td>

                <!-- Status & Hold Duration -->
                <td class="px-4 py-3">
                  <div v-if="log.status === 'pending'" class="space-y-0.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-ping"></span>
                      HOLD PENDING
                    </span>
                    <div v-if="getReleaseStatus(log)" class="text-[10px]" :class="getReleaseStatus(log).isDue ? 'text-emerald-400 font-bold' : 'text-slate-400'">
                      {{ getReleaseStatus(log).label }}
                    </div>
                  </div>

                  <div v-else-if="log.status === 'approved'">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                      APPROVED
                    </span>
                  </div>

                  <div v-else-if="log.status === 'reversed'" class="space-y-0.5">
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/20 text-rose-300 border border-rose-500/30">
                      REVERSED
                    </span>
                    <div v-if="log.reason" class="text-[10px] text-rose-400/80 truncate max-w-[130px]" :title="log.reason">
                      {{ log.reason }}
                    </div>
                  </div>
                </td>

                <!-- Recorded At -->
                <td class="px-4 py-3 text-[11px] text-slate-400">
                  <div>{{ formatDate(log.created_at) }}</div>
                  <div v-if="log.release_time" class="text-[10px] text-slate-500">
                    Release: {{ formatDate(log.release_time) }}
                  </div>
                </td>

                <!-- Actions -->
                <td class="px-5 py-3 text-right">
                  <button 
                    v-if="log.status === 'pending'"
                    @click="forceReleaseLog(log)"
                    :disabled="releasingLogId === log.id"
                    class="px-3 py-1.5 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-[11px] font-bold transition-all cursor-pointer disabled:opacity-50 inline-flex items-center gap-1"
                    title="Transfer this hold amount directly into the user's main balance now"
                  >
                    <svg v-if="releasingLogId === log.id" class="animate-spin h-3 w-3 text-amber-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>⚡ Release Now</span>
                  </button>
                  <span v-else class="text-slate-600 text-xs">—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="logs.links && logs.links.length > 3" class="px-5 py-4 bg-slate-950/60 border-t border-white/5 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <div class="text-slate-500">
            Showing <strong class="text-slate-300">{{ logs.from || 0 }}</strong> to <strong class="text-slate-300">{{ logs.to || 0 }}</strong> of <strong class="text-slate-300">{{ logs.total }}</strong> conversions
          </div>

          <div class="flex items-center gap-1 flex-wrap">
            <template v-for="(link, idx) in logs.links" :key="idx">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                class="px-3 py-1.5 rounded-xl transition-all"
                :class="link.active ? 'bg-indigo-600 text-white font-bold shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
              />
              <span 
                v-else 
                v-html="link.label" 
                class="px-3 py-1.5 rounded-xl opacity-30 text-slate-500 select-none"
              />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Manual Cleanup Modal -->
    <div v-if="showCleanupModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
      <div class="bg-[#0f172a] border border-white/10 rounded-3xl p-6 sm:p-8 w-full max-w-lg shadow-2xl space-y-5" @click.stop>
        <div class="flex items-start justify-between">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-rose-500/20 text-rose-400 flex items-center justify-center text-xl shrink-0">
              🧹
            </div>
            <div>
              <h3 class="text-lg font-bold text-white">Clean Old Offerwall History</h3>
              <p class="text-xs text-slate-400">Prune completed and reversed logs to save database space</p>
            </div>
          </div>
          <button @click="showCleanupModal = false" class="text-slate-400 hover:text-white p-1 rounded-xl">✕</button>
        </div>

        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-300 flex items-start gap-2.5">
          <span class="text-base shrink-0">🛡️</span>
          <div>
            <strong>Strict Balance Protection:</strong>
            Pending hold transactions will <u>NEVER</u> be deleted. Only completed (Approved) and Reversed logs will be purged.
          </div>
        </div>

        <div class="space-y-2">
          <label class="block text-xs font-semibold text-slate-300">Delete completed logs older than:</label>
          <select v-model="cleanupDays" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-4 py-3 text-sm text-white focus:border-indigo-500 outline-none cursor-pointer">
            <option :value="7">Older than 7 days</option>
            <option :value="15">Older than 15 days</option>
            <option :value="30">Older than 30 days (Recommended)</option>
            <option :value="60">Older than 60 days</option>
            <option :value="90">Older than 90 days</option>
            <option :value="180">Older than 6 months</option>
            <option :value="365">Older than 1 year</option>
          </select>
        </div>

        <div class="text-[11px] text-slate-500">
          💡 The system also automatically prunes logs older than 30 days every night via the scheduled cron task.
        </div>

        <div class="flex gap-3 pt-2">
          <button 
            @click="showCleanupModal = false" 
            class="flex-1 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition-colors"
          >
            Cancel
          </button>
          <button 
            @click="executeCleanup" 
            :disabled="isCleaning"
            class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50 cursor-pointer shadow-lg shadow-rose-600/20"
          >
            <svg v-if="isCleaning" class="animate-spin h-3.5 w-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>{{ isCleaning ? 'Cleaning...' : 'Confirm & Delete' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Edit/Add Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
      <div class="bg-[#0f172a] border border-white/10 rounded-3xl p-8 w-full max-w-2xl shadow-2xl transform transition-all max-h-[90vh] overflow-y-auto" @click.stop>
        <h3 class="text-2xl font-bold text-white mb-6">{{ isEditing ? 'Edit Offerwall' : 'Add New Offerwall' }}</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Provider Name</label>
            <input v-model="form.name" placeholder="e.g. Timewall" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
          </div>
          
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Reward Ratio</label>
            <input v-model="form.reward_ratio" type="number" step="any" placeholder="1.00" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Iframe URL Pattern</label>
            <input v-model="form.iframe_url_pattern" placeholder="e.g. https://timewall.io/offerwall?user={user_id}" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
            <p class="text-xs text-slate-500 mt-1">Use <code class="text-emerald-400 bg-emerald-400/10 px-1 rounded">{user_id}</code> as a macro for the authenticated user's ID.</p>
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Secret Key (For Postback Security)</label>
            <input v-model="form.secret_key" placeholder="Optional but recommended" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Logo URL <span class="text-slate-500 normal-case font-normal">(Optional)</span></label>
            <div class="flex gap-4 items-center">
              <input v-model="form.image_url" placeholder="https://..." class="flex-1 w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
              <div v-if="form.image_url" class="w-12 h-12 bg-white/5 rounded-xl border border-white/10 flex items-center justify-center p-1 shrink-0 shadow-inner">
                <img :src="form.image_url" class="max-w-full max-h-full object-contain rounded-lg" alt="Preview">
              </div>
            </div>
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Description</label>
            <textarea v-model="form.description" rows="3" placeholder="Brief description of the offerwall..." class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all"></textarea>
          </div>
          
          <div class="md:col-span-2 pt-4 pb-2 border-b border-white/10">
            <h4 class="text-sm font-bold text-white uppercase tracking-wider">Advanced Postback Parameters</h4>
            <p class="text-xs text-slate-400">Map custom parameter names for this provider's postback requests.</p>
          </div>
          
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">User ID Parameter</label>
            <input v-model="form.param_user_id" placeholder="e.g. subId, user_id" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Amount Parameter</label>
            <input v-model="form.param_amount" placeholder="e.g. amount, reward, payout" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Transaction ID Parameter</label>
            <input v-model="form.param_transaction_id" placeholder="e.g. tx_id, transId" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Secret Key Parameter</label>
            <input v-model="form.param_secret_key" placeholder="e.g. secure, hash" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Parameter</label>
            <input v-model="form.param_status" placeholder="e.g. status, type" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Chargeback Value</label>
            <input v-model="form.status_chargeback_value" placeholder="e.g. reversed, 2, chargeback" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Secret Key <span class="text-slate-500 normal-case font-normal">(Optional)</span></label>
            <input v-model="form.secret_key" placeholder="Enter provider's secret key" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Leave blank if the provider uses complex MD5/SHA1 Signature Hashes. Instead, use <b class="text-slate-400">Allowed IPs</b> below for security.</p>
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Allowed IPs <span class="text-slate-500 normal-case font-normal">(Comma separated, Optional)</span></label>
            <input v-model="form.allowed_ips" placeholder="e.g. 192.168.1.1, 10.0.0.5" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
            <p class="text-xs text-slate-500 mt-1">If set, ONLY postbacks from these IPs will be accepted. Excellent for security if provider sends signature hashes.</p>
          </div>

          <div class="md:col-span-2 flex items-center mt-4">
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.status" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
              <span class="ml-3 text-sm font-medium text-white">Offerwall is Active</span>
            </label>
          </div>
        </div>

        <div class="flex gap-4 mt-8">
          <button @click="showModal = false" class="flex-1 bg-white/5 hover:bg-white/10 py-3 rounded-xl text-sm font-bold text-white transition-colors cursor-pointer">Cancel</button>
          <button @click="save()" class="flex-1 bg-emerald-500 hover:bg-emerald-600 shadow-lg shadow-emerald-500/20 py-3 rounded-xl text-sm font-bold text-white transition-all hover:-translate-y-0.5 cursor-pointer">Save Offerwall</button>
        </div>
      </div>
    </div>

    <!-- Postback Setup Guide Modal -->
    <div v-if="showPostbackModal && selectedOfferwall" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
      <div class="bg-[#0f172a] border border-white/10 rounded-3xl p-8 w-full max-w-3xl shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-white">Postback Setup: {{ selectedOfferwall.name }}</h3>
            <p class="text-slate-400 mt-1">Configure your offerwall provider to send callbacks to this system.</p>
          </div>
          <button @click="showPostbackModal = false" class="text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-full p-2 transition-colors cursor-pointer">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="bg-black/40 border border-white/5 rounded-2xl p-5 mb-6">
          <h4 class="text-sm font-semibold text-emerald-400 uppercase tracking-wider mb-3">Your Global Postback URL</h4>
          <div class="flex items-center gap-3">
            <code class="flex-1 block bg-black/60 border border-white/10 rounded-xl p-4 text-sm text-indigo-300 break-all select-all font-mono">
              {{ postbackUrl }}
            </code>
            <button @click="copyToClipboard(postbackUrl)" class="bg-white/10 hover:bg-white/20 p-4 rounded-xl text-white transition-colors cursor-pointer" title="Copy to clipboard">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
          <p class="text-xs text-slate-500 mt-3">
            <strong>Note:</strong> You must replace <code class="text-slate-300">{user_id}</code>, <code class="text-slate-300">{reward}</code>, and <code class="text-slate-300">{tx_id}</code> with the actual macro tags provided by your offerwall network (e.g., <code class="text-slate-300">{userID}</code> or <code class="text-slate-300">[SUBID]</code>).
          </p>
        </div>

        <div class="space-y-4">
          <h4 class="text-lg font-bold text-white border-b border-white/10 pb-2">Supported Parameter Macros</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">User Identifier <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_user_id || 'user_id' }})</span></p>
              <p class="text-xs text-slate-400">Pass the authenticated user's ID to this parameter.</p>
            </div>
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">Transaction ID <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_transaction_id || 'transaction_id' }})</span></p>
              <p class="text-xs text-slate-400">Pass the unique offerwall transaction hash to this parameter.</p>
            </div>
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">Reward Amount <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_amount || 'amount' }})</span></p>
              <p class="text-xs text-slate-400">Pass the rewarded provider currency to this parameter.</p>
            </div>
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">Status / Chargeback <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_status || 'status' }})</span></p>
              <p class="text-xs text-slate-400">Pass <code class="text-rose-400 bg-rose-400/10 px-1 rounded">{{ selectedOfferwall.status_chargeback_value || 'reversed' }}</code> to reverse a transaction.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
