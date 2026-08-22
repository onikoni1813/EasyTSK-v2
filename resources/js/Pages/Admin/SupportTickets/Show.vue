<template>
  <AdminLayout>
    <div class="max-w-5xl mx-auto space-y-6">
      <!-- Top Navigation -->
      <div class="flex items-center justify-between">
        <Link :href="`${adminPath}/support-tickets`" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 transition-colors">
          <span>&larr; Back to Tickets Overview</span>
        </Link>

        <!-- Status Change Dropdown -->
        <div class="flex items-center gap-2">
          <span class="text-xs font-semibold text-slate-400">Set Ticket Status:</span>
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

      <!-- Ticket & Detailed User Information Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <!-- Left: Ticket Header & Info (2 Columns) -->
        <div class="lg:col-span-2 glass-card p-6 rounded-3xl border border-indigo-500/20 space-y-4">
          <div class="space-y-2 border-b border-slate-800 pb-4">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-mono font-bold text-indigo-400 bg-indigo-950/60 border border-indigo-500/30 px-2.5 py-0.5 rounded-lg">
                {{ ticket.ticket_number }}
              </span>
              <span class="text-xs font-bold uppercase bg-slate-900 px-2.5 py-0.5 rounded text-slate-300 border border-slate-800">
                Category: {{ ticket.category }}
              </span>
              <span
                :class="[
                  'px-2 py-0.5 rounded text-[10px] font-black uppercase border',
                  ticket.priority === 'high' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 animate-pulse' : '',
                  ticket.priority === 'medium' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : '',
                  ticket.priority === 'low' ? 'bg-slate-800 text-slate-400 border-slate-700' : ''
                ]"
              >
                Priority: {{ ticket.priority || 'medium' }}
              </span>
            </div>

            <h1 class="text-xl font-black text-white mt-1">{{ ticket.subject }}</h1>
          </div>

          <div class="flex justify-between items-center text-xs text-slate-400">
            <span>📅 Created: {{ formatDate(ticket.created_at) }}</span>
            <span>⏳ Last Reply: {{ formatDate(ticket.last_reply_at || ticket.updated_at) }}</span>
          </div>
        </div>

        <!-- Right: Comprehensive Dynamic User Profile Card -->
        <div v-if="ticket.user" class="glass-card p-5 rounded-3xl border border-slate-800 bg-slate-900/90 space-y-3">
          <div class="flex items-center justify-between border-b border-slate-800 pb-2.5">
            <div>
              <h3 class="font-bold text-white text-sm flex items-center gap-1.5">
                <span>👤 {{ ticket.user.name }}</span>
              </h3>
              <p class="text-[11px] text-indigo-400 font-medium">{{ ticket.user.email || 'No Email' }}</p>
            </div>
            <span
              :class="[
                'px-2 py-0.5 rounded text-[10px] font-black uppercase border',
                ticket.user.is_banned ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40'
              ]"
            >
              {{ ticket.user.is_banned ? 'Banned' : 'Active' }}
            </span>
          </div>

          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-500 block">Phone / Contact</span>
              <span class="font-bold text-slate-200 text-[11px]">{{ ticket.user.phone }}</span>
            </div>

            <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-500 block">Account Level</span>
              <span class="font-bold text-amber-300 text-[11px]">Level {{ ticket.user.level || 1 }}</span>
            </div>

            <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-500 block">Main Balance</span>
              <span class="font-bold text-emerald-400 text-[11px]">৳{{ ticket.user.main_balance || 0 }}</span>
            </div>

            <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-500 block">Pending Balance</span>
              <span class="font-bold text-indigo-300 text-[11px]">৳{{ ticket.user.pending_balance || 0 }}</span>
            </div>

            <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-500 block">Health Points</span>
              <span class="font-bold text-rose-400 text-[11px]">{{ ticket.user.health ?? 100 }}/100</span>
            </div>

            <div class="bg-slate-950/60 p-2 rounded-xl border border-slate-800">
              <span class="text-[10px] text-slate-500 block">Risk Score</span>
              <span class="font-bold text-slate-200 text-[11px]">{{ ticket.user.risk_score || 0 }}%</span>
            </div>
          </div>

          <div v-if="ticket.user.payment_method" class="bg-indigo-950/30 p-2.5 rounded-xl border border-indigo-500/20 text-xs">
            <span class="text-[10px] text-indigo-300 block font-semibold">Payment Details</span>
            <span class="font-bold text-white uppercase">{{ ticket.user.payment_method }}: </span>
            <span class="font-mono text-slate-300">{{ ticket.user.payment_number }}</span>
          </div>

          <div class="pt-1 flex justify-end">
            <Link
              :href="`${adminPath}/users?search=${encodeURIComponent(ticket.user.phone || ticket.user.email)}`"
              class="text-[11px] font-bold text-indigo-400 hover:text-indigo-300 flex items-center gap-1"
            >
              <span>View User Profile & History &rarr;</span>
            </Link>
          </div>
        </div>
      </div>

      <!-- Message History Thread -->
      <div class="space-y-4">
        <h2 class="text-xs font-bold uppercase text-slate-400 tracking-wider">Conversation History</h2>
        
        <div
          v-for="msg in ticket.messages"
          :key="msg.id"
          :class="[
            'glass-card p-5 rounded-3xl border transition-all space-y-3',
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

          <!-- Attached Screenshot / Image -->
          <div v-if="msg.attachment" class="pt-2">
            <div class="inline-block rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 p-1">
              <img
                :src="msg.attachment"
                alt="Attachment Preview"
                @click="openImageModal(msg.attachment)"
                class="max-h-48 max-w-xs object-cover rounded-xl cursor-pointer hover:opacity-90 transition-opacity"
              />
              <p class="text-[10px] text-slate-400 mt-1 px-1 flex items-center gap-1">
                <span>📎 Image Attachment (Click to expand)</span>
              </p>
            </div>
          </div>
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

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
            <div class="flex flex-wrap items-center gap-4">
              <div class="flex items-center gap-2">
                <label class="text-xs text-slate-400">Set Ticket Status:</label>
                <select v-model="replyForm.status" class="bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-white">
                  <option value="in_progress">In Progress</option>
                  <option value="resolved">Mark Resolved</option>
                  <option value="closed">Close Ticket</option>
                </select>
              </div>

              <!-- Optional Image Attachment -->
              <div>
                <label class="text-xs text-slate-400 block sm:inline mr-2">Attach Screenshot:</label>
                <input
                  type="file"
                  @change="handleFileChange"
                  accept="image/*"
                  class="text-xs text-slate-400 file:mr-2 file:py-1 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700"
                />
              </div>
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

    <!-- Image Lightbox Modal -->
    <Teleport to="body">
      <div v-if="activeImageModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-md" @click="activeImageModal = null">
        <div class="relative max-w-4xl max-h-[90vh] overflow-hidden rounded-2xl border border-slate-700" @click.stop>
          <img :src="activeImageModal" class="w-full h-auto max-h-[85vh] object-contain" />
          <button @click="activeImageModal = null" class="absolute top-3 right-3 bg-black/70 text-white w-8 h-8 rounded-full flex items-center justify-center font-bold">✕</button>
        </div>
      </div>
    </Teleport>
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
const activeImageModal = ref(null);

const replyForm = useForm({
  message: '',
  status: 'in_progress',
  attachment: null,
});

const handleFileChange = (e) => {
  if (e.target.files && e.target.files[0]) {
    replyForm.attachment = e.target.files[0];
  }
};

const submitReply = () => {
  replyForm.post(`${adminPath.value}/support-tickets/${props.ticket.id}/reply`, {
    forceFormData: true,
    onSuccess: () => {
      replyForm.reset('message', 'attachment');
    },
  });
};

const changeStatus = () => {
  router.post(`${adminPath.value}/support-tickets/${props.ticket.id}/status`, {
    status: ticketStatus.value,
  });
};

const openImageModal = (url) => {
  activeImageModal.value = url;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
