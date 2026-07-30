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
                        {{ t.task_title }}
                      </td>
                      <td class="px-3 py-2.5 font-bold text-amber-400">
                        {{ t.reward_points }} pts
                      </td>
                      <td class="px-3 py-2.5">
                        <span v-if="t.status === 'approved'" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] uppercase font-bold">Approved</span>
                        <span v-else-if="t.status === 'rejected'" class="px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded text-[10px] uppercase font-bold">Rejected</span>
                        <span v-else class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded text-[10px] uppercase font-bold">Pending</span>
                      </td>
                      <td class="px-3 py-2.5 max-w-[240px]">
                        <div v-if="t.submitted_data" class="bg-slate-950 p-2 rounded-lg border border-slate-800 text-[11px] text-slate-300 font-mono break-all max-h-24 overflow-y-auto">
                          <template v-if="typeof t.submitted_data === 'object'">
                            <div v-for="(val, key) in t.submitted_data" :key="key" class="mb-1">
                              <span class="text-slate-500">{{ key }}:</span> 
                              <a v-if="typeof val === 'string' && (val.startsWith('http') || val.startsWith('/storage'))" :href="val" target="_blank" class="text-indigo-400 underline ml-1">View Attachment 🔗</a>
                              <span v-else class="text-slate-200 ml-1">{{ val }}</span>
                            </div>
                          </template>
                          <template v-else>
                            {{ t.submitted_data }}
                          </template>
                        </div>
                        <span v-else class="text-slate-600 italic">No proof submitted</span>
                        
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
                        ৳{{ ref.locked_reward }}
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
                      <th class="px-3 py-2.5">Coins</th>
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
                        {{ w.amount_coins }}
                      </td>
                      <td class="px-3 py-2.5">
                        <span v-if="w.status === 'approved'" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] uppercase font-bold">Approved</span>
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
</template>

<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  show: Boolean,
  user: Object,
});

const emit = defineEmits(['close']);

const activeTab = ref('tasks');
const loading = ref(false);
const errorMsg = ref('');
const historyData = ref(null);

const fetchHistory = async () => {
  if (!props.user?.id) return;
  loading.value = true;
  errorMsg.value = '';
  historyData.value = null;
  try {
    const res = await axios.get(`/admin/users/${props.user.id}/history`);
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
