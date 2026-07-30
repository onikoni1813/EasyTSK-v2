<template>
  <AdminLayout>
    <div class="max-w-4xl mx-auto space-y-6">
      <!-- Top Navigation -->
      <div class="flex items-center justify-between">
        <Link :href="`${adminPath}/support-tickets`" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 transition-colors">
          <span>&larr; Back to Tickets Overview</span>
        </Link>

        <!-- Status Change Dropdown -->
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-400">Set Status:</span>
          <select
            v-model="ticketStatus"
            @change="changeStatus"
            class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white font-bold focus:outline-none focus:border-indigo-500"
          >
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="resolved">Resolved</option>
            <option value="closed">Closed</option>
          </select>
        </div>
      </div>

      <!-- Ticket & User Details Card -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/20 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="text-xs font-mono font-bold text-indigo-400 bg-indigo-950/60 border border-indigo-500/30 px-2.5 py-0.5 rounded-lg">
                {{ ticket.ticket_number }}
              </span>
              <span class="text-xs font-bold uppercase bg-slate-900 px-2 py-0.5 rounded text-slate-300">
                Category: {{ ticket.category }}
              </span>
            </div>
            <h1 class="text-xl font-black text-white mt-2">{{ ticket.subject }}</h1>
          </div>

          <div v-if="ticket.user" class="glass-card p-3 rounded-2xl border border-slate-800 bg-slate-900/80 shrink-0 text-xs space-y-0.5">
            <p class="font-bold text-white">{{ ticket.user.name }}</p>
            <p class="text-slate-400">Phone: {{ ticket.user.phone }}</p>
            <p v-if="ticket.user.email" class="text-indigo-300">{{ ticket.user.email }}</p>
          </div>
        </div>

        <div class="flex justify-between text-xs text-slate-400">
          <span>Created on {{ formatDate(ticket.created_at) }}</span>
          <span>Last reply {{ formatDate(ticket.last_reply_at || ticket.updated_at) }}</span>
        </div>
      </div>

      <!-- Message History Thread -->
      <div class="space-y-4">
        <div
          v-for="msg in ticket.messages"
          :key="msg.id"
          :class="[
            'glass-card p-5 rounded-3xl border transition-all space-y-2',
            msg.is_admin ? 'border-indigo-500/30 bg-indigo-950/20 ml-4 sm:ml-8' : 'border-slate-800 bg-slate-900/60 mr-4 sm:mr-8'
          ]"
        >
          <div class="flex items-center justify-between text-xs border-b border-white/5 pb-2">
            <div class="flex items-center gap-2">
              <span
                :class="[
                  'w-7 h-7 rounded-xl flex items-center justify-center font-bold text-xs shadow-md',
                  msg.is_admin ? 'bg-indigo-600 text-white' : 'bg-slate-800 text-slate-200'
                ]"
              >
                {{ msg.is_admin ? 'AD' : (msg.sender?.name?.charAt(0) || 'U') }}
              </span>
              <div>
                <span class="font-bold text-white">{{ msg.is_admin ? 'Admin Response' : (msg.sender?.name || 'User') }}</span>
                <span v-if="msg.is_admin" class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-indigo-500/20 text-indigo-300">Staff</span>
              </div>
            </div>
            <span class="text-[11px] text-slate-500">{{ formatDate(msg.created_at) }}</span>
          </div>

          <p class="text-xs text-slate-200 leading-relaxed whitespace-pre-wrap pt-1">
            {{ msg.message }}
          </p>
        </div>
      </div>

      <!-- Admin Reply Box -->
      <div class="glass-card p-5 rounded-3xl border border-indigo-500/30 space-y-3">
        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Reply as Admin</h3>
        <form @submit.prevent="submitReply" class="space-y-3">
          <textarea
            v-model="replyForm.message"
            rows="4"
            placeholder="Type official admin reply to user..."
            required
            class="w-full bg-slate-900 border border-slate-800 rounded-2xl p-3 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-indigo-500"
          ></textarea>

          <div class="flex items-center justify-between gap-4 pt-1">
            <div class="flex items-center gap-2">
              <label class="text-xs text-slate-400">Set Ticket Status on Reply:</label>
              <select v-model="replyForm.status" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white">
                <option value="in_progress">In Progress</option>
                <option value="resolved">Mark Resolved</option>
                <option value="closed">Close Ticket</option>
              </select>
            </div>

            <button
              type="submit"
              :disabled="replyForm.processing || !replyForm.message"
              class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors disabled:opacity-50"
            >
              <span v-if="replyForm.processing">⏳ Sending Reply...</span>
              <span v-else>📩 Send Reply to User</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  ticket: Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const ticketStatus = ref(props.ticket.status);

const replyForm = useForm({
  message: '',
  status: 'in_progress',
});

const submitReply = () => {
  replyForm.post(`${adminPath.value}/support-tickets/${props.ticket.id}/reply`, {
    onSuccess: () => {
      replyForm.reset('message');
    },
  });
};

const changeStatus = () => {
  router.post(`${adminPath.value}/support-tickets/${props.ticket.id}/status`, {
    status: ticketStatus.value,
  });
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
