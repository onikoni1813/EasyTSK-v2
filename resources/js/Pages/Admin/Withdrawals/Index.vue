<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Top Bar -->
      <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-white">Withdrawal Payout Hub</h1>
          <p class="text-xs text-slate-400">Review pending withdrawals, filter/backup history and cleanup old records</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Bulk Delete Selected Button -->
          <button
            v-if="selectedIds.length > 0"
            @click="confirmBulkDelete"
            class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs flex items-center space-x-1.5 shadow-lg shadow-rose-600/20 transition-all"
          >
            <span>🗑️</span>
            <span>Delete Selected ({{ selectedIds.length }})</span>
          </button>

          <!-- Auto Cleanup Modal Button -->
          <button
            @click="isCleanupModalOpen = true"
            class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded-xl text-xs flex items-center space-x-1.5 shadow-lg shadow-amber-600/20 transition-all"
          >
            <span>🧹</span>
            <span>Auto Cleanup</span>
          </button>

          <!-- Filtered Backup CSV Button -->
          <button 
            @click="openBackupModal" 
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs flex items-center space-x-2 shadow-lg shadow-emerald-600/20 transition-all"
          >
            <span>📥</span>
            <span>Backup CSV (Filtered)</span>
          </button>
        </div>
      </div>

      <!-- Filters & Search Card -->
      <div class="glass-card p-5 rounded-3xl border border-slate-800 space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <!-- Status Tabs -->
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tab in tabs"
              :key="tab.value"
              @click="selectStatus(tab.value)"
              class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center space-x-2"
              :class="activeStatus === tab.value 
                ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' 
                : 'bg-slate-800/80 text-slate-400 hover:bg-slate-800 hover:text-white'"
            >
              <span>{{ tab.label }}</span>
              <span 
                class="px-1.5 py-0.5 rounded-full text-[10px]"
                :class="activeStatus === tab.value ? 'bg-white/20 text-white' : 'bg-slate-700 text-slate-300'"
              >
                {{ counts?.[tab.value] ?? 0 }}
              </span>
            </button>
          </div>

          <!-- Search Input -->
          <div class="relative w-full md:w-72">
            <input
              v-model="searchQuery"
              @input="handleSearch"
              type="text"
              placeholder="Search user, method, account, txn..."
              class="w-full bg-slate-900 border border-slate-700/80 rounded-xl pl-9 pr-4 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors"
            />
            <span class="absolute left-3 top-2 text-slate-500 text-xs">🔍</span>
          </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left text-slate-300">
            <thead class="text-[11px] uppercase bg-slate-900/90 text-slate-400 border-b border-slate-800">
              <tr>
                <th class="px-3 py-3 w-10 text-center">
                  <input
                    type="checkbox"
                    :checked="isAllSelected"
                    @change="toggleSelectAll"
                    class="rounded border-slate-700 bg-slate-800 text-indigo-600 focus:ring-0 cursor-pointer"
                  />
                </th>
                <th class="px-4 py-3">ID & Date</th>
                <th class="px-4 py-3">User</th>
                <th class="px-4 py-3">Method</th>
                <th class="px-4 py-3">Account Details</th>
                <th class="px-4 py-3">Amount</th>
                <th class="px-4 py-3">Status / Txn</th>
                <th class="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="w in withdrawals.data" :key="w.id" class="hover:bg-slate-800/30 transition-colors">
                <td class="px-3 py-3 text-center">
                  <input
                    type="checkbox"
                    :value="w.id"
                    v-model="selectedIds"
                    class="rounded border-slate-700 bg-slate-800 text-indigo-600 focus:ring-0 cursor-pointer"
                  />
                </td>
                <td class="px-4 py-3">
                  <div class="font-mono text-slate-400">#{{ w.id }}</div>
                  <div class="text-[10px] text-slate-500">{{ formatDate(w.created_at) }}</div>
                </td>
                <td class="px-4 py-3 font-semibold text-white">
                  <div>{{ w.user ? w.user.name : 'User' }}</div>
                  <div class="text-[10px] text-slate-400 font-normal">{{ w.user ? w.user.email : '' }}</div>
                </td>
                <td class="px-4 py-3 font-medium text-slate-200">
                  <span class="px-2 py-0.5 rounded bg-slate-800 text-[11px] border border-slate-700/50">
                    {{ w.payment_method }}
                  </span>
                </td>
                <td class="px-4 py-3 font-mono font-bold text-amber-300">
                  {{ w.account_details }}
                </td>
                <td class="px-4 py-3">
                  <div class="font-bold text-emerald-400">৳ {{ w.amount_bdt }}</div>
                  <div class="text-[10px] text-slate-500">{{ w.amount_coins }} Pts<span v-if="w.charge_coins > 0"> (+{{ w.charge_coins }} fee)</span></div>
                </td>
                <td class="px-4 py-3">
                  <span 
                    class="px-2 py-1 rounded text-[10px] uppercase font-bold inline-block mb-1" 
                    :class="{
                      'bg-amber-500/20 text-amber-300 border border-amber-500/30': w.status === 'pending',
                      'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': w.status === 'paid',
                      'bg-rose-500/20 text-rose-300 border border-rose-500/30': w.status === 'rejected',
                    }"
                  >
                    {{ w.status }}
                  </span>
                  <div v-if="w.transaction_id" class="text-[10px] font-mono text-slate-400">
                    Txn: <span class="text-indigo-300">{{ w.transaction_id }}</span>
                  </div>
                  <div v-if="w.admin_note" class="text-[10px] text-rose-400 italic">
                    Reason: {{ w.admin_note }}
                  </div>
                </td>
                <td class="px-4 py-3 text-right space-x-2">
                  <button v-if="w.status === 'pending'" @click="openApproveModal(w)" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg text-[11px] transition-colors">Mark Paid</button>
                  <button v-if="w.status === 'pending'" @click="openRejectModal(w)" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-lg text-[11px] transition-colors">Reject & Refund</button>
                  <button @click="confirmSingleDelete(w)" class="px-2.5 py-1 bg-slate-800 hover:bg-rose-600/30 hover:border-rose-500/50 border border-slate-700 text-slate-400 hover:text-rose-300 font-bold rounded-lg text-[11px] transition-all" title="Delete record">
                    🗑️
                  </button>
                </td>
              </tr>
              <tr v-if="!withdrawals.data || withdrawals.data.length === 0">
                <td colspan="8" class="px-4 py-8 text-center text-slate-500 text-xs">
                  No withdrawal requests found matching your filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div v-if="withdrawals.total > 0" class="flex flex-col sm:flex-row justify-between items-center pt-2 text-xs text-slate-400 gap-2">
          <div>
            Showing <span class="font-bold text-slate-200">{{ withdrawals.from || 0 }}</span> to <span class="font-bold text-slate-200">{{ withdrawals.to || 0 }}</span> of <span class="font-bold text-slate-200">{{ withdrawals.total }}</span> withdrawals
          </div>
          <div class="flex space-x-2">
            <Link 
              v-if="withdrawals.prev_page_url" 
              :href="withdrawals.prev_page_url" 
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl transition-colors font-semibold"
            >
              &larr; Previous
            </Link>
            <span v-else class="px-3 py-1.5 bg-slate-900 text-slate-600 rounded-xl cursor-not-allowed">
              &larr; Previous
            </span>
            
            <Link 
              v-if="withdrawals.next_page_url" 
              :href="withdrawals.next_page_url" 
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-xl transition-colors font-semibold"
            >
              Next &rarr;
            </Link>
            <span v-else class="px-3 py-1.5 bg-slate-900 text-slate-600 rounded-xl cursor-not-allowed">
              Next &rarr;
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Approve Modal -->
    <div v-if="isApproveModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-white mb-2">Approve Withdrawal</h3>
        <p class="text-sm text-slate-400 mb-4">Please enter the transaction ID for this payment.</p>
        <input v-model="transactionId" type="text" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-emerald-500 mb-6" placeholder="Enter Transaction ID (txn)">
        <div class="flex space-x-3 justify-end">
          <button @click="isApproveModalOpen = false" class="px-4 py-2 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold text-sm transition-colors">Cancel</button>
          <button @click="submitApprove" :disabled="isProcessing" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm transition-colors disabled:opacity-50">Confirm Payment</button>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="isRejectModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-rose-500 mb-2">Reject Withdrawal</h3>
        <p class="text-sm text-slate-400 mb-4">Provide a reason for rejection. The amount will be refunded.</p>
        <input v-model="rejectionReason" type="text" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-3 text-white focus:outline-none focus:border-rose-500 mb-6" placeholder="Reason (e.g. Invalid number)">
        <div class="flex space-x-3 justify-end">
          <button @click="isRejectModalOpen = false" class="px-4 py-2 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold text-sm transition-colors">Cancel</button>
          <button @click="submitReject" :disabled="isProcessing" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-sm transition-colors disabled:opacity-50">Reject & Refund</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="isDeleteModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-rose-500 mb-2">Delete Withdrawal History</h3>
        <p class="text-sm text-slate-300 mb-4">
          Are you sure you want to delete <span class="font-bold text-white">{{ deleteTargetText }}</span>?
        </p>
        <p class="text-xs text-rose-400 bg-rose-500/10 p-3 rounded-xl border border-rose-500/20 mb-6">
          ⚠️ Note: Deleting history records cannot be undone.
        </p>
        <div class="flex space-x-3 justify-end">
          <button @click="isDeleteModalOpen = false" class="px-4 py-2 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold text-sm transition-colors">Cancel</button>
          <button @click="submitDelete" :disabled="isProcessing" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-sm transition-colors disabled:opacity-50">Confirm Delete</button>
        </div>
      </div>
    </div>

    <!-- Auto Cleanup Modal -->
    <div v-if="isCleanupModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold text-amber-400 flex items-center space-x-2">
            <span>🧹</span>
            <span>Auto Cleanup History</span>
          </h3>
          <button @click="isCleanupModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <p class="text-xs text-slate-400">
          Select target records and age threshold to delete in bulk. Pending withdrawal requests will be safely preserved.
        </p>

        <!-- Cleanup Target Status -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Target Status to Clean:</label>
          <select v-model="cleanupStatus" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
            <option value="all_completed">All Completed (Paid & Rejected)</option>
            <option value="paid">Paid Requests Only</option>
            <option value="rejected">Rejected Requests Only</option>
          </select>
        </div>

        <!-- Age Threshold -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Age Threshold:</label>
          <select v-model.number="cleanupDays" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-500">
            <option :value="0">All Time (Delete all matching target status)</option>
            <option :value="7">Older than 7 Days</option>
            <option :value="30">Older than 30 Days</option>
            <option :value="90">Older than 90 Days</option>
          </select>
        </div>

        <div class="bg-amber-500/10 border border-amber-500/20 p-3 rounded-xl text-[11px] text-amber-300">
          💡 <strong>Pro Tip:</strong> Click <strong>"Backup CSV"</strong> before running cleanup to keep an offline backup of all historical data.
        </div>

        <div class="flex space-x-3 justify-end pt-2">
          <button @click="isCleanupModalOpen = false" class="px-4 py-2 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold text-sm transition-colors">Cancel</button>
          <button @click="submitCleanup" :disabled="isProcessing" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white font-bold text-sm transition-colors disabled:opacity-50 flex items-center space-x-1.5">
            <span>🧹</span>
            <span>Run One-Click Cleanup</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Backup CSV Filter Modal -->
    <div v-if="isBackupModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
      <div class="bg-slate-900 border border-slate-700 rounded-2xl w-full max-w-md p-6 shadow-2xl space-y-4">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-bold text-emerald-400 flex items-center space-x-2">
            <span>📥</span>
            <span>Export & Backup CSV Options</span>
          </h3>
          <button @click="isBackupModalOpen = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <p class="text-xs text-slate-400">
          Customize the filters below to export specific withdrawal records to CSV format.
        </p>

        <!-- Status Filter -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status:</label>
          <select v-model="backupStatus" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            <option value="all">All Statuses (Pending, Paid & Rejected)</option>
            <option value="pending">Pending Only</option>
            <option value="paid">Paid Only</option>
            <option value="rejected">Rejected Only</option>
          </select>
        </div>

        <!-- Payment Method Filter -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Payment Method:</label>
          <select v-model="backupMethod" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            <option value="all">All Methods</option>
            <option value="Bkash">bKash</option>
            <option value="Nagad">Nagad</option>
            <option value="Rocket">Rocket</option>
            <option value="Recharge">Mobile Recharge</option>
          </select>
        </div>

        <!-- Date Range Filter -->
        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1.5">Date Range:</label>
          <select v-model="backupDateRange" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
            <option value="all">All Time</option>
            <option value="today">Today</option>
            <option value="7days">Last 7 Days</option>
            <option value="30days">Last 30 Days</option>
            <option value="custom">Custom Date Range</option>
          </select>
        </div>

        <!-- Custom Date Inputs -->
        <div v-if="backupDateRange === 'custom'" class="grid grid-cols-2 gap-3 pt-1">
          <div>
            <label class="block text-[11px] font-medium text-slate-400 mb-1">Start Date:</label>
            <input type="date" v-model="backupStartDate" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
          </div>
          <div>
            <label class="block text-[11px] font-medium text-slate-400 mb-1">End Date:</label>
            <input type="date" v-model="backupEndDate" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-500">
          </div>
        </div>

        <div class="flex space-x-3 justify-end pt-2">
          <button @click="isBackupModalOpen = false" class="px-4 py-2 rounded-xl text-slate-300 hover:bg-slate-800 font-semibold text-sm transition-colors">Cancel</button>
          <a :href="generatedBackupUrl" target="_blank" @click="isBackupModalOpen = false" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-sm transition-colors flex items-center space-x-1.5">
            <span>📥</span>
            <span>Download CSV Backup</span>
          </a>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  withdrawals: Object,
  filters: Object,
  counts: Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const activeStatus = ref(props.filters?.status || 'all');
const searchQuery = ref(props.filters?.search || '');
const selectedIds = ref([]);

const tabs = [
  { label: 'All Requests', value: 'all' },
  { label: 'Pending', value: 'pending' },
  { label: 'Paid', value: 'paid' },
  { label: 'Rejected', value: 'rejected' },
];

const isBackupModalOpen = ref(false);
const backupStatus = ref('all');
const backupMethod = ref('all');
const backupDateRange = ref('all');
const backupStartDate = ref('');
const backupEndDate = ref('');

const openBackupModal = () => {
  backupStatus.value = activeStatus.value;
  isBackupModalOpen.value = true;
};

const generatedBackupUrl = computed(() => {
  const params = new URLSearchParams();
  if (backupStatus.value) params.append('status', backupStatus.value);
  if (backupMethod.value) params.append('method', backupMethod.value);
  if (backupDateRange.value) params.append('date_range', backupDateRange.value);
  if (backupDateRange.value === 'custom') {
    if (backupStartDate.value) params.append('start_date', backupStartDate.value);
    if (backupEndDate.value) params.append('end_date', backupEndDate.value);
  }
  if (searchQuery.value) params.append('search', searchQuery.value);
  return `${adminPath.value}/withdrawals/export-csv?${params.toString()}`;
});

let searchTimeout = null;

const selectStatus = (status) => {
  activeStatus.value = status;
  selectedIds.value = [];
  triggerFetch();
};

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    selectedIds.value = [];
    triggerFetch();
  }, 350);
};

const triggerFetch = () => {
  router.get(`${adminPath.value}/withdrawals`, {
    status: activeStatus.value,
    search: searchQuery.value,
  }, {
    preserveState: true,
    replace: true,
  });
};

const isAllSelected = computed(() => {
  if (!props.withdrawals.data || props.withdrawals.data.length === 0) return false;
  return props.withdrawals.data.every(w => selectedIds.value.includes(w.id));
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = props.withdrawals.data.map(w => w.id);
  }
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const isApproveModalOpen = ref(false);
const isRejectModalOpen = ref(false);
const isDeleteModalOpen = ref(false);
const isCleanupModalOpen = ref(false);

const cleanupStatus = ref('all_completed');
const cleanupDays = ref(30);

const currentWithdrawal = ref(null);
const isBulkDeleteMode = ref(false);
const transactionId = ref('');
const rejectionReason = ref('Invalid payment number');
const isProcessing = ref(false);

const deleteTargetText = computed(() => {
  if (isBulkDeleteMode.value) {
    return `${selectedIds.value.length} selected records`;
  }
  return currentWithdrawal.value ? `Withdrawal #${currentWithdrawal.value.id}` : 'this record';
});

const openApproveModal = (w) => {
  currentWithdrawal.value = w;
  transactionId.value = '';
  isApproveModalOpen.value = true;
};

const openRejectModal = (w) => {
  currentWithdrawal.value = w;
  rejectionReason.value = 'Invalid payment number';
  isRejectModalOpen.value = true;
};

const confirmSingleDelete = (w) => {
  currentWithdrawal.value = w;
  isBulkDeleteMode.value = false;
  isDeleteModalOpen.value = true;
};

const confirmBulkDelete = () => {
  if (selectedIds.value.length === 0) return;
  isBulkDeleteMode.value = true;
  isDeleteModalOpen.value = true;
};

const submitDelete = () => {
  if (isProcessing.value) return;
  isProcessing.value = true;

  if (isBulkDeleteMode.value) {
    router.post(`${adminPath.value}/withdrawals/bulk-delete`, {
      ids: selectedIds.value,
    }, {
      onSuccess: () => {
        isDeleteModalOpen.value = false;
        selectedIds.value = [];
      },
      onFinish: () => {
        isProcessing.value = false;
      }
    });
  } else {
    router.delete(`${adminPath.value}/withdrawals/${currentWithdrawal.value.id}`, {
      onSuccess: () => {
        isDeleteModalOpen.value = false;
      },
      onFinish: () => {
        isProcessing.value = false;
      }
    });
  }
};

const submitCleanup = () => {
  if (isProcessing.value) return;
  isProcessing.value = true;

  router.post(`${adminPath.value}/withdrawals/cleanup`, {
    status: cleanupStatus.value,
    days: cleanupDays.value,
  }, {
    onSuccess: () => {
      isCleanupModalOpen.value = false;
    },
    onFinish: () => {
      isProcessing.value = false;
    }
  });
};

const submitApprove = () => {
  if (!transactionId.value || isProcessing.value) return;
  isProcessing.value = true;
  
  router.post(`${adminPath.value}/withdrawals/${currentWithdrawal.value.id}/approve`, {
    transaction_id: transactionId.value,
  }, {
    onSuccess: () => {
      isApproveModalOpen.value = false;
    },
    onFinish: () => {
      isProcessing.value = false;
    }
  });
};

const submitReject = () => {
  if (!rejectionReason.value || isProcessing.value) return;
  isProcessing.value = true;

  router.post(`${adminPath.value}/withdrawals/${currentWithdrawal.value.id}/reject`, {
    admin_note: rejectionReason.value,
  }, {
    onSuccess: () => {
      isRejectModalOpen.value = false;
    },
    onFinish: () => {
      isProcessing.value = false;
    }
  });
};
</script>
