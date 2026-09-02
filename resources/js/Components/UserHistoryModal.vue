<template>
  <Transition name="modal">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
      <div class="bg-slate-900 border border-slate-700/60 rounded-3xl p-6 w-full max-w-4xl shadow-[0_0_50px_-12px_rgba(79,70,229,0.35)] max-h-[90vh] flex flex-col transform transition-all">
        
        <!-- Header -->
        <div class="flex justify-between items-start pb-4 border-b border-slate-800">
          <div>
            <div class="flex items-center space-x-3">
              <h2 class="text-xl font-black text-white">📜 Activity History: <span class="text-indigo-400">{{ user?.name }}</span></h2>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                #{{ user?.id }}
              </span>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Phone: {{ user?.phone }} • Email: {{ user?.email || 'N/A' }}</p>
          </div>
          <button @click="$emit('close')" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">
            ✕
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="loading" class="flex-1 flex flex-col items-center justify-center py-16">
          <div class="w-10 h-10 border-4 border-indigo-500/30 border-t-indigo-500 rounded-full animate-spin"></div>
          <p class="text-xs text-slate-400 mt-3 font-medium">Fetching history log...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="errorMsg" class="flex-1 flex flex-col items-center justify-center py-12 text-center">
          <div class="text-rose-400 text-3xl mb-2">⚠️</div>
          <p class="text-sm font-bold text-rose-300">{{ errorMsg }}</p>
          <button @click="fetchHistory" class="mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl">
            Retry Loading
          </button>
        </div>

        <template v-else-if="historyData">
          <!-- Summary Quick Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 my-3 sm:my-4">
            <!-- Card 1: User Total Income -->
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between">
              <div>
                <p class="text-[10px] sm:text-[11px] text-slate-400 font-semibold uppercase tracking-wider">User Total Income</p>
                <p class="text-base sm:text-lg font-black text-emerald-400 mt-0.5">{{ historyData.stats.total_user_income }} <span class="text-xs font-normal text-slate-400">pts</span></p>
              </div>
              <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 text-base sm:text-lg">
                💰
              </div>
            </div>

            <!-- Card 2: Admin Revenue -->
            <div class="bg-slate-800/60 border border-amber-500/30 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between bg-amber-500/5">
              <div>
                <p class="text-[10px] sm:text-[11px] text-amber-400 font-semibold uppercase tracking-wider">Admin Revenue</p>
                <p class="text-base sm:text-lg font-black text-amber-400 mt-0.5">{{ historyData.stats.total_admin_revenue }} <span class="text-xs font-normal text-slate-400">pts</span></p>
              </div>
              <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-400 text-base sm:text-lg">
                💎
              </div>
            </div>

            <!-- Card 3: Total Withdrawn -->
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between">
              <div>
                <p class="text-[10px] sm:text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Total Withdrawn</p>
                <p class="text-base sm:text-lg font-black text-indigo-400 mt-0.5">৳{{ historyData.stats.total_withdrawn_bdt }}</p>
              </div>
              <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-indigo-400 text-base sm:text-lg">
                💸
              </div>
            </div>

            <!-- Card 4: Tasks & Referrals -->
            <div class="bg-slate-800/60 border border-slate-700/50 rounded-2xl p-3 sm:p-3.5 flex items-center justify-between">
              <div>
                <p class="text-[10px] sm:text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Tasks & Refs</p>
                <p class="text-xs font-bold text-white mt-0.5">
                  📋 {{ historyData.stats.approved_tasks }} <span class="text-[10px] text-slate-400 font-normal">approved</span>
                </p>
                <p class="text-xs font-bold text-emerald-400">
                  👥 {{ historyData.stats.total_referrals }} <span class="text-[10px] text-slate-400 font-normal">refs</span>
                </p>
              </div>
              <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 text-base sm:text-lg">
                📊
              </div>
            </div>
          </div>

          <!-- Navigation Tabs -->
          <div class="flex flex-wrap gap-2 border-b border-slate-800 pb-2 mb-3">
            <button 
              @click="activeTab = 'tasks'"
              :class="activeTab === 'tasks' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'"
              class="px-4 py-2 rounded-xl text-xs flex items-center space-x-2 transition-all"
            >
              <span>📋 Task History</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-900/60 font-mono">{{ historyData.tasks.length }}</span>
            </button>

            <button 
              @click="activeTab = 'referrals'"
              :class="activeTab === 'referrals' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'"
              class="px-4 py-2 rounded-xl text-xs flex items-center space-x-2 transition-all"
            >
              <span>👥 Referral History</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-900/60 font-mono">{{ historyData.referrals.length }}</span>
            </button>

            <button 
              @click="activeTab = 'withdrawals'"
              :class="activeTab === 'withdrawals' ? 'bg-indigo-600 text-white font-bold' : 'bg-slate-800/80 text-slate-400 hover:text-white hover:bg-slate-800'"
              class="px-4 py-2 rounded-xl text-xs flex items-center space-x-2 transition-all"
            >
              <span>💸 Withdrawal History</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] bg-slate-900/60 font-mono">{{ historyData.withdrawals.length }}</span>
            </button>
          </div>

          <!-- Content Tab Area -->
          <div class="flex-1 overflow-y-auto pr-1 space-y-4">
            
            <!-- 1. TAB: TASKS -->
            <div v-if="activeTab === 'tasks'">
              <div v-if="historyData.tasks.length === 0" class="py-12 text-center text-slate-500 text-xs">
                No tasks completed or submitted yet.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-300">
                  <thead class="text-[11px] uppercase bg-slate-900/90 text-slate-400 sticky top-0">
                    <tr>
                      <th class="px-3 py-2.5">Task</th>
                      <th class="px-3 py-2.5">Reward</th>
                      <th class="px-3 py-2.5">Status</th>
                      <th class="px-3 py-2.5">Submitted Proof</th>
                      <th class="px-3 py-2.5 text-right">Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="t in historyData.tasks" :key="t.id" class="border-b border-slate-800/60 hover:bg-slate-800/30">
                      <td class="px-3 py-2.5 font-semibold text-white">
                        <div class="font-bold text-white text-xs">{{ t.task_title }}</div>
                        <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                          <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                            {{ getTaskTypeIcon(t.task_type) }} {{ formatTaskType(t.task_type) }}
                          </span>
                        </div>
                      </td>
                      <td class="px-3 py-2.5 font-bold text-amber-400">
                        {{ t.reward_points }} pts
                      </td>
                      <td class="px-3 py-2.5">
                        <span v-if="t.status === 'approved'" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] uppercase font-bold">Approved</span>
                        <span v-else-if="t.status === 'rejected'" class="px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded text-[10px] uppercase font-bold">Rejected</span>
                        <span v-else class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-[10px] uppercase font-bold">Pending</span>
                      </td>
                      <td class="px-3 py-2.5 max-w-[280px]">
                        <!-- Valid Screenshot Image Thumbnails -->
                        <div v-if="getValidScreenshots(t.screenshot_hashes).length > 0" class="flex flex-wrap gap-2 mb-1.5">
                          <div v-for="sh in getValidScreenshots(t.screenshot_hashes)" :key="sh.id"
                               class="relative group/thumb w-14 h-14 rounded-lg overflow-hidden border border-slate-700 hover:border-indigo-500 cursor-pointer transition-colors shadow"
                               @click="openImage('/storage/' + sh.file_path)">
                            <img :src="'/storage/' + sh.file_path" class="w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-110" alt="Proof" />
                            <div class="absolute inset-0 bg-indigo-900/50 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center">
                              <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </div>
                          </div>
                        </div>

                        <!-- Formatted Text / Secret Code / Dynamic Data Proof -->
                        <div v-if="hasSubmittedData(t.submitted_data)" class="bg-slate-950 p-2.5 rounded-lg border border-slate-800 text-[11px] text-slate-300 font-mono break-all max-h-28 overflow-y-auto space-y-1">
                          <template v-if="typeof t.submitted_data === 'object' && !Array.isArray(t.submitted_data)">
                            <div v-for="(val, key) in t.submitted_data" :key="key" class="leading-snug">
                              <span class="text-slate-400 font-sans font-bold text-[10px] uppercase block tracking-wider">{{ formatProofLabel(key, val) }}</span> 
                              <a v-if="typeof val === 'string' && (val.startsWith('http') || val.startsWith('/storage'))" :href="val" target="_blank" @click.stop="val.startsWith('/storage') ? openImage(val) : null" class="text-indigo-400 underline font-bold">View Attachment 🔗</a>
                              <span v-else class="text-emerald-400 font-semibold">{{ formatProofValue(val, getValidScreenshots(t.screenshot_hashes).length > 0) }}</span>
                            </div>
                          </template>
                          <template v-else>
                            <span class="text-emerald-400 font-semibold">{{ t.submitted_data }}</span>
                          </template>
                        </div>

                        <!-- Secret Code / Auto Verified Fallback when no text/image proof -->
                        <div v-else-if="getValidScreenshots(t.screenshot_hashes).length === 0" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] font-semibold">
                          <span v-if="t.task_type === 'secret_code'">🔑 Secret Code Verified</span>
                          <span v-else-if="t.task_type === 'website'">🌐 Site Visit Verified</span>
                          <span v-else>✓ Verified Task Submission</span>
                        </div>

                        <div v-if="t.admin_note" class="text-[10px] text-rose-400 mt-1 italic">
                          Note: {{ t.admin_note }}
                        </div>
                      </td>
                      <td class="px-3 py-2.5 text-right text-slate-500 font-mono text-[11px] whitespace-nowrap">
                        {{ t.created_at }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 2. TAB: REFERRALS -->
            <div v-if="activeTab === 'referrals'">
              <div v-if="historyData.referrals.length === 0" class="py-12 text-center text-slate-500 text-xs">
                No users referred yet.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-300">
                  <thead class="text-[11px] uppercase bg-slate-900/90 text-slate-400 sticky top-0">
                    <tr>
                      <th class="px-3 py-2.5">Referred User</th>
                      <th class="px-3 py-2.5">Tasks Done</th>
                      <th class="px-3 py-2.5">Reward</th>
                      <th class="px-3 py-2.5">Status</th>
                      <th class="px-3 py-2.5 text-right">Joined Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="ref in historyData.referrals" :key="ref.id" class="border-b border-slate-800/60 hover:bg-slate-800/30">
                      <td class="px-3 py-2.5">
                        <div class="font-bold text-white">{{ ref.referred_user?.name || 'Deleted User' }}</div>
                        <div class="text-[11px] text-slate-400">{{ ref.referred_user?.phone }}</div>
                      </td>
                      <td class="px-3 py-2.5 font-bold text-indigo-400">
                        {{ ref.tasks_completed }} tasks
                      </td>
                      <td class="px-3 py-2.5 font-bold text-emerald-400">
                        {{ ref.locked_reward }} pts
                      </td>
                      <td class="px-3 py-2.5">
                        <span v-if="ref.status === 'claimed' || ref.status === 'unlocked'" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] uppercase font-bold">Unlocked</span>
                        <span v-else class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-[10px] uppercase font-bold">Locked</span>
                      </td>
                      <td class="px-3 py-2.5 text-right text-slate-500 font-mono text-[11px] whitespace-nowrap">
                        {{ ref.created_at }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- 3. TAB: WITHDRAWALS -->
            <div v-if="activeTab === 'withdrawals'">
              <div v-if="historyData.withdrawals.length === 0" class="py-12 text-center text-slate-500 text-xs">
                No withdrawal requests found.
              </div>
              <div v-else class="overflow-x-auto">
                <table class="w-full text-xs text-left text-slate-300">
                  <thead class="text-[11px] uppercase bg-slate-900/90 text-slate-400 sticky top-0">
                    <tr>
                      <th class="px-3 py-2.5">Method & Account</th>
                      <th class="px-3 py-2.5">Amount (BDT)</th>
                      <th class="px-3 py-2.5">Points</th>
                      <th class="px-3 py-2.5">Status</th>
                      <th class="px-3 py-2.5">Trx ID / Note</th>
                      <th class="px-3 py-2.5 text-right">Date</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="w in historyData.withdrawals" :key="w.id" class="border-b border-slate-800/60 hover:bg-slate-800/30">
                      <td class="px-3 py-2.5">
                        <span class="px-2 py-0.5 bg-slate-800 font-bold text-white rounded text-[11px] uppercase border border-slate-700">
                          {{ w.payment_method }}
                        </span>
                        <div class="font-mono text-emerald-400 font-bold mt-1 text-[11px]">{{ w.account_details }}</div>
                      </td>
                      <td class="px-3 py-2.5 font-bold text-emerald-400 text-sm">
                        ৳{{ w.amount_bdt }}
                      </td>
                      <td class="px-3 py-2.5 font-bold text-amber-400">
                        {{ w.amount_coins }} pts
                      </td>
                      <td class="px-3 py-2.5">
                        <span v-if="w.status === 'approved' || w.status === 'paid'" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] uppercase font-bold">Paid</span>
                        <span v-else-if="w.status === 'rejected'" class="px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded text-[10px] uppercase font-bold">Rejected</span>
                        <span v-else class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-[10px] uppercase font-bold">Pending</span>
                      </td>
                      <td class="px-3 py-2.5 max-w-[180px]">
                        <div v-if="w.transaction_id" class="font-mono text-[10px] text-slate-300">Trx: {{ w.transaction_id }}</div>
                        <div v-if="w.admin_note" class="text-[10px] text-slate-400">{{ w.admin_note }}</div>
                        <div v-if="w.rejection_reason" class="text-[10px] text-rose-400 font-medium">Reason: {{ w.rejection_reason }}</div>
                      </td>
                      <td class="px-3 py-2.5 text-right text-slate-500 font-mono text-[11px] whitespace-nowrap">
                        {{ w.created_at }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

          </div>
        </template>

        <!-- Footer -->
        <div class="flex justify-end pt-4 border-t border-slate-800 mt-auto">
          <button @click="$emit('close')" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs transition-colors">
            Close History
          </button>
        </div>

      </div>
    </div>
  </Transition>

  <!-- Image Lightbox Preview Modal -->
  <Teleport to="body">
    <Transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="selectedImage" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-md p-4 sm:p-8" @click="selectedImage = null">
        <button class="absolute top-4 right-4 sm:top-6 sm:right-6 text-slate-300 hover:text-white bg-slate-800/60 hover:bg-rose-500/80 p-2 sm:px-4 sm:py-2 rounded-xl backdrop-blur-sm transition-colors flex items-center gap-2" @click="selectedImage = null">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
          <span class="font-bold hidden sm:inline text-xs">Close Preview</span>
        </button>
        <img :src="selectedImage" class="max-w-full max-h-full object-contain rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-100 transition-transform duration-300" @click.stop />
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const props = defineProps({
  show: Boolean,
  user: Object,
});

const emit = defineEmits(['close']);

const activeTab = ref('tasks');
const loading = ref(false);
const errorMsg = ref('');
const historyData = ref(null);
const selectedImage = ref(null);

const openImage = (url) => {
  selectedImage.value = url;
};

const getTaskTypeIcon = (type) => {
  const icons = {
    secret_code: '🔑',
    website: '🌐',
    telegram: '✈️',
    youtube: '▶️',
    facebook: '📘',
    app_download: '📲',
    screenshot: '📸',
  };
  return icons[type] || '📌';
};

const hasSubmittedData = (data) => {
  if (!data) return false;
  if (Array.isArray(data)) return data.length > 0;
  if (typeof data === 'object') return Object.keys(data).length > 0;
  return String(data).trim().length > 0;
};

const getValidScreenshots = (hashes) => {
  if (!Array.isArray(hashes)) return [];
  return hashes.filter((sh) => sh && sh.file_path && String(sh.file_path).trim() !== '');
};

const formatProofLabel = (key, val) => {
  try {
    if (val && typeof val === 'object') {
      if (val.label) return String(val.label);
      if (val.type === 'image') return 'Screenshot Submit';
      if (val.type === 'text') return 'Text Proof';
    }
    if (key === null || key === undefined) return 'Task Proof';
    const strKey = String(key);
    if (strKey.startsWith('req_') || strKey.startsWith('REQ ')) return 'Requirement Proof';
    return strKey.replace('_', ' ');
  } catch (e) {
    return 'Task Proof';
  }
};

const formatProofValue = (val, hasValidScreenshot = false) => {
  try {
    if (val === null || val === undefined) return '';
    if (typeof val === 'string' || typeof val === 'number' || typeof val === 'boolean') {
      return String(val);
    }
    if (typeof val === 'object') {
      if (val.type === 'image') {
        if (hasValidScreenshot) return 'Image Attached 🖼️';
        return '📸 Screenshot Uploaded & Verified (Storage Saved)';
      }
      if (val.type === 'text' || val.value !== undefined) {
        return val.value || val.text_proof || 'Submitted';
      }
      if (val.secret_codes) return String(val.secret_codes);
      if (val.text_proof) return String(val.text_proof);
      return Object.values(val).filter((v) => typeof v !== 'object').join(' | ') || 'Submitted';
    }
    return String(val);
  } catch (e) {
    return 'Submitted';
  }
};

const formatTaskType = (type) => {
  if (!type || typeof type !== 'string') return 'General';
  return type.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());
};

const fetchHistory = async () => {
  if (!props.user?.id) return;
  loading.value = true;
  errorMsg.value = '';
  historyData.value = null;
  try {
    const res = await axios.get(`${adminPath.value}/users/${props.user.id}/history`);
    historyData.value = res.data;
  } catch (err) {
    console.error('Failed to load user history', err);
    errorMsg.value = err.response?.data?.message || 'Could not load user activity history. Please try again.';
  } finally {
    loading.value = false;
  }
};

watch(() => props.show, (newVal) => {
  if (newVal && props.user) {
    activeTab.value = 'tasks';
    fetchHistory();
  }
});
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.25s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active > div, .modal-leave-active > div {
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from > div {
  transform: scale(0.96) translateY(12px);
}
.modal-leave-to > div {
  transform: scale(0.96) translateY(12px);
}
</style>
