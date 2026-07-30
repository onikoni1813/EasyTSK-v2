<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-black text-white flex items-center gap-2">
            <span>🔐 Password Reset Tickets</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
              {{ stats.pending }} Pending
            </span>
          </h1>
          <p class="text-xs text-slate-400 mt-1">Manage user account recovery and password reset support requests</p>
        </div>
      </div>

      <!-- Stats Summary Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60">
          <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Tickets</p>
          <p class="text-xl font-black text-white mt-1">{{ stats.total }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-amber-500/30 bg-amber-500/5">
          <p class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Pending</p>
          <p class="text-xl font-black text-amber-300 mt-1">{{ stats.pending }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/5">
          <p class="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider">Approved</p>
          <p class="text-xl font-black text-emerald-300 mt-1">{{ stats.approved }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-indigo-500/30 bg-indigo-500/5">
          <p class="text-[11px] font-semibold text-indigo-400 uppercase tracking-wider">Completed</p>
          <p class="text-xl font-black text-indigo-300 mt-1">{{ stats.completed }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-rose-500/30 bg-rose-500/5 col-span-2 sm:col-span-1">
          <p class="text-[11px] font-semibold text-rose-400 uppercase tracking-wider">Rejected</p>
          <p class="text-xl font-black text-rose-300 mt-1">{{ stats.rejected }}</p>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="glass-card p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
          <button
            v-for="st in ['all', 'pending', 'approved', 'completed', 'rejected']"
            :key="st"
            @click="filterStatus(st)"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-semibold capitalize whitespace-nowrap transition-all',
              currentStatus === st ? 'bg-indigo-600 text-white shadow-lg' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'
            ]"
          >
            {{ st }}
          </button>
        </div>

        <div class="w-full md:w-64">
          <input
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Search phone, ticket code..."
            class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
          />
        </div>
      </div>

      <!-- Tickets Table -->
      <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-900/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                <th class="py-3.5 px-4">Ticket Code</th>
                <th class="py-3.5 px-4">User Info</th>
                <th class="py-3.5 px-4">Note / Reason</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4">Reset Code</th>
                <th class="py-3.5 px-4">Date</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr v-if="tickets.data.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-500 font-medium">
                  No password reset tickets found.
                </td>
              </tr>

              <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-900/40 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-amber-400 whitespace-nowrap">
                  {{ ticket.ticket_code }}
                </td>

                <td class="py-3.5 px-4">
                  <div v-if="ticket.user" class="space-y-0.5">
                    <p class="font-bold text-white">{{ ticket.user.name }}</p>
                    <p class="text-[11px] text-slate-400">{{ ticket.phone }}</p>
                    <p v-if="ticket.user.email" class="text-[10px] text-indigo-300">{{ ticket.user.email }}</p>
                  </div>
                  <div v-else class="text-slate-400">
                    <p class="font-bold text-white">{{ ticket.phone }}</p>
                    <p class="text-[10px] text-rose-400">Unmatched User Account</p>
                  </div>
                </td>

                <td class="py-3.5 px-4 max-w-xs">
                  <p class="text-slate-300 truncate" :title="ticket.message">{{ ticket.message || 'No note provided' }}</p>
                  <p v-if="ticket.admin_note" class="text-[10px] text-amber-300 mt-1 italic">
                    Admin: {{ ticket.admin_note }}
                  </p>
                </td>

                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border',
                      ticket.status === 'pending' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 animate-pulse' : '',
                      ticket.status === 'approved' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : '',
                      ticket.status === 'completed' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : '',
                      ticket.status === 'rejected' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : ''
                    ]"
                  >
                    {{ ticket.status }}
                  </span>
                </td>

                <td class="py-3.5 px-4 font-mono text-sm font-bold text-emerald-400 whitespace-nowrap">
                  <span v-if="ticket.reset_code" class="bg-emerald-950/60 border border-emerald-600/40 px-2 py-0.5 rounded-lg tracking-wider">
                    {{ ticket.reset_code }}
                  </span>
                  <span v-else class="text-slate-600 text-xs">-</span>
                </td>

                <td class="py-3.5 px-4 text-[11px] text-slate-400 whitespace-nowrap">
                  {{ formatDate(ticket.created_at) }}
                </td>

                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <div v-if="ticket.status === 'pending'" class="flex items-center justify-end gap-2">
                    <button
                      @click="openApproveModal(ticket)"
                      class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs transition-colors shadow-sm"
                    >
                      Approve Reset
                    </button>
                    <button
                      @click="openRejectModal(ticket)"
                      class="px-3 py-1.5 rounded-xl bg-rose-600/30 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 font-bold text-xs transition-colors"
                    >
                      Reject
                    </button>
                  </div>
                  <div v-else-if="ticket.status === 'approved'" class="text-xs text-emerald-400 font-semibold">
                    Approved (Waiting User)
                  </div>
                  <div v-else-if="ticket.status === 'completed'" class="text-xs text-indigo-400 font-semibold">
                    Password Reset Done
                  </div>
                  <div v-else class="text-xs text-rose-400">
                    Rejected
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="tickets.links.length > 3" class="p-4 border-t border-slate-800 flex justify-end gap-1">
          <Link
            v-for="link in tickets.links"
            :key="link.label"
            :href="link.url || '#'"
            v-html="link.label"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors',
              link.active ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900 border border-slate-800',
              !link.url ? 'opacity-40 cursor-not-allowed' : ''
            ]"
          />
        </div>
      </div>

      <!-- Approve Modal -->
      <Teleport to="body">
        <div v-if="selectedTicketForApprove" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-emerald-500/30 bg-slate-950 space-y-4">
            <div class="flex justify-between items-center">
              <h3 class="text-base font-black text-white flex items-center gap-2">
                <span>✅ Approve Password Reset</span>
              </h3>
              <button @click="selectedTicketForApprove = null" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-300">
              Approving this ticket will generate a 6-digit Reset OTP/Code for <strong class="text-white">{{ selectedTicketForApprove.phone }}</strong>.
            </p>

            <div>
              <label class="text-xs font-semibold text-slate-400 block mb-1">Admin Note (Optional)</label>
              <textarea
                v-model="approveForm.admin_note"
                rows="3"
                placeholder="e.g. Identity verified via phone call / Support approval"
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500"
              ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button @click="selectedTicketForApprove = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                Cancel
              </button>
              <button
                @click="submitApprove"
                :disabled="approveForm.processing"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors disabled:opacity-50"
              >
                Confirm Approval & Generate Code
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Reject Modal -->
      <Teleport to="body">
        <div v-if="selectedTicketForReject" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-rose-500/30 bg-slate-950 space-y-4">
            <div class="flex justify-between items-center">
              <h3 class="text-base font-black text-rose-400 flex items-center gap-2">
                <span>🚫 Reject Reset Request</span>
              </h3>
              <button @click="selectedTicketForReject = null" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div>
              <label class="text-xs font-semibold text-slate-400 block mb-1">Reason for Rejection (Required)</label>
              <textarea
                v-model="rejectForm.admin_note"
                rows="3"
                placeholder="e.g. Phone number does not match registered owner"
                required
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-rose-500"
              ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button @click="selectedTicketForReject = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                Cancel
              </button>
              <button
                @click="submitReject"
                :disabled="rejectForm.processing || !rejectForm.admin_note"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors disabled:opacity-50"
              >
                Confirm Rejection
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  tickets: Object,
  filters: Object,
  stats: Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const currentStatus = ref(props.filters.status || 'all');
const searchQuery   = ref(props.filters.search || '');

const selectedTicketForApprove = ref(null);
const selectedTicketForReject  = ref(null);

const approveForm = useForm({
  admin_note: 'Identity verified. Reset approved.',
});

const rejectForm = useForm({
  admin_note: '',
});

const filterStatus = (st) => {
  currentStatus.value = st;
  router.get(`${adminPath.value}/password-tickets`, {
    status: st,
    search: searchQuery.value,
  }, { preserveState: true, replace: true });
};

const handleSearch = () => {
  router.get(`${adminPath.value}/password-tickets`, {
    status: currentStatus.value,
    search: searchQuery.value,
  }, { preserveState: true, replace: true });
};

const openApproveModal = (ticket) => {
  selectedTicketForApprove.value = ticket;
  approveForm.admin_note = 'Identity verified. Reset approved.';
};

const openRejectModal = (ticket) => {
  selectedTicketForReject.value = ticket;
  rejectForm.admin_note = '';
};

const submitApprove = () => {
  if (!selectedTicketForApprove.value) return;
  approveForm.post(`${adminPath.value}/password-tickets/${selectedTicketForApprove.value.id}/approve`, {
    onSuccess: () => {
      selectedTicketForApprove.value = null;
    },
  });
};

const submitReject = () => {
  if (!selectedTicketForReject.value) return;
  rejectForm.post(`${adminPath.value}/password-tickets/${selectedTicketForReject.value.id}/reject`, {
    onSuccess: () => {
      selectedTicketForReject.value = null;
    },
  });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
