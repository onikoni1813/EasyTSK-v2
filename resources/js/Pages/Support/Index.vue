<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 class="text-2xl font-black text-white flex items-center gap-2.5">
            <svg class="w-6 h-6 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <span>Help & Support Center</span>
          </h1>
          <p class="text-xs text-slate-400 mt-1">Have a problem or question? Create a ticket to chat directly with Support</p>
        </div>

        <button
          @click="showCreateModal = true"
          class="btn-neon btn-primary px-5 py-3 rounded-2xl text-xs font-bold text-white flex items-center gap-2 shadow-lg shadow-indigo-500/20"
        >
          <span>➕ Create New Ticket</span>
        </button>
      </div>

      <!-- Tickets List -->
      <div class="glass-card rounded-3xl border border-indigo-500/20 p-5 space-y-4">
        <div v-if="tickets.data.length === 0" class="text-center py-12 space-y-3">
          <div class="w-16 h-16 rounded-3xl bg-indigo-600/10 text-indigo-400 flex items-center justify-center text-3xl mx-auto border border-indigo-500/20">
            💬
          </div>
          <h3 class="text-base font-bold text-white">No Support Tickets Yet</h3>
          <p class="text-xs text-slate-400 max-w-sm mx-auto">
            If you face any issues with withdrawals, tasks, or your account, open a support ticket and our team will assist you!
          </p>
          <button @click="showCreateModal = true" class="btn-neon btn-primary px-4 py-2 text-xs font-bold text-white rounded-xl">
            Open First Ticket
          </button>
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="ticket in tickets.data"
            :key="ticket.id"
            class="glass-card p-4 rounded-2xl border border-slate-800/80 hover:border-indigo-500/30 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4 card-hover"
          >
            <div class="space-y-1.5 flex-1">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="text-xs font-mono font-bold text-indigo-400 bg-indigo-950/60 border border-indigo-500/30 px-2 py-0.5 rounded-lg">
                  {{ ticket.ticket_number }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border bg-slate-900 text-slate-300 border-slate-700">
                  {{ getCategoryLabel(ticket.category) }}
                </span>
                <span
                  :class="[
                    'px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider border',
                    ticket.status === 'open' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : '',
                    ticket.status === 'in_progress' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : '',
                    ticket.status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : '',
                    ticket.status === 'closed' ? 'bg-slate-800 text-slate-400 border-slate-700' : ''
                  ]"
                >
                  {{ ticket.status.replace('_', ' ') }}
                </span>
              </div>

              <h3 class="text-sm font-bold text-white">{{ ticket.subject }}</h3>
              <p v-if="ticket.latest_message" class="text-xs text-slate-400 line-clamp-1">
                <span class="font-semibold text-slate-300">{{ ticket.latest_message.is_admin ? 'Admin:' : 'You:' }}</span>
                {{ ticket.latest_message.message }}
              </p>
            </div>

            <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-800">
              <span class="text-[11px] text-slate-500">{{ formatDate(ticket.updated_at) }}</span>
              <Link
                :href="`/support/${ticket.id}`"
                class="btn-neon btn-primary px-4 py-2 text-xs font-bold text-white rounded-xl flex items-center gap-1"
              >
                <span>View Chat &rarr;</span>
              </Link>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="tickets.links && tickets.links.length > 3" class="pt-4 flex justify-end gap-1 border-t border-slate-800">
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

      <!-- Create Support Ticket Modal -->
      <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md">
          <div class="glass-card max-w-lg w-full p-6 rounded-3xl border border-indigo-500/30 neon-glow-indigo animate-slide-in-up space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                  <span>📩 Create Support Ticket</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Select a category and describe your issue</p>
              </div>
              <button @click="showCreateModal = false" class="w-8 h-8 rounded-xl glass-pill flex items-center justify-center text-slate-400 hover:text-white transition-colors text-sm">✕</button>
            </div>

            <form @submit.prevent="submitTicket" class="space-y-3">
              <div>
                <label class="text-xs font-semibold text-slate-400 block mb-1">Issue Category</label>
                <select v-model="form.category" required class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-indigo-500">
                  <option value="withdrawal">💸 Withdrawal / Payout Issue</option>
                  <option value="task">🧩 Task & Offerwall Issue</option>
                  <option value="account">🔐 Account & Security Issue</option>
                  <option value="general">💬 General Question / Feedback</option>
                </select>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-400 block mb-1">Subject</label>
                <input v-model="form.subject" type="text" placeholder="e.g. Withdrawal pending for 24 hours" required class="input-dark text-xs" />
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-400 block mb-1">Detailed Message</label>
                <textarea v-model="form.message" rows="4" placeholder="Please provide all details (transaction ID, task title, etc.) so we can help you fast..." required class="input-dark text-xs py-2.5"></textarea>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-400 block mb-1">Attach Screenshot (Optional)</label>
                <input
                  type="file"
                  @change="handleFileChange"
                  accept="image/*"
                  class="w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700"
                />
              </div>

              <div v-if="form.errors.category" class="text-xs text-rose-400">{{ form.errors.category }}</div>
              <div v-if="form.errors.subject" class="text-xs text-rose-400">{{ form.errors.subject }}</div>
              <div v-if="form.errors.message" class="text-xs text-rose-400">{{ form.errors.message }}</div>
              <div v-if="form.errors.attachment" class="text-xs text-rose-400">{{ form.errors.attachment }}</div>

              <div class="flex gap-2 pt-2">
                <button type="button" @click="showCreateModal = false" class="flex-1 py-3 glass-pill text-xs text-slate-400 rounded-xl border border-white/8 hover:text-white transition-colors">Cancel</button>
                <button type="submit" :disabled="form.processing" class="flex-1 btn-neon btn-primary py-3 text-xs font-bold text-white rounded-xl disabled:opacity-50">
                  <span v-if="form.processing">⏳ Submitting...</span>
                  <span v-else>🚀 Send Ticket</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </Teleport>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  tickets: Object,
});

const showCreateModal = ref(false);

const form = useForm({
  category:   'withdrawal',
  subject:    '',
  message:    '',
  attachment: null,
});

const handleFileChange = (e) => {
  if (e.target.files && e.target.files[0]) {
    form.attachment = e.target.files[0];
  }
};

const submitTicket = () => {
  form.post('/support', {
    forceFormData: true,
    onSuccess: () => {
      showCreateModal.value = false;
      form.reset();
    },
  });
};

const getCategoryLabel = (cat) => {
  const map = {
    withdrawal: '💸 Withdrawal',
    task:       '🧩 Task',
    account:    '🔐 Account',
    general:    '💬 General',
  };
  return map[cat] || cat;
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
