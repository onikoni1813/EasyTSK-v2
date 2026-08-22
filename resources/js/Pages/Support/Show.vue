<template>
  <AppLayout>
    <div class="max-w-3xl mx-auto space-y-6 animate-slide-in-up">
      <!-- Top Bar Navigation -->
      <div class="flex items-center justify-between">
        <Link href="/support" class="text-xs font-bold text-slate-400 hover:text-white flex items-center gap-1.5 transition-colors">
          <span>&larr; Back to All Tickets</span>
        </Link>
        <span
          :class="[
            'px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider border',
            ticket.status === 'open' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : '',
            ticket.status === 'in_progress' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : '',
            ticket.status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : '',
            ticket.status === 'closed' ? 'bg-slate-800 text-slate-400 border-slate-700' : ''
          ]"
        >
          Status: {{ ticket.status.replace('_', ' ') }}
        </span>
      </div>

      <!-- Ticket Header Info -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/20 space-y-2 relative overflow-hidden">
        <div class="flex items-center gap-2">
          <span class="text-xs font-mono font-bold text-indigo-400 bg-indigo-950/60 border border-indigo-500/30 px-2.5 py-0.5 rounded-lg">
            {{ ticket.ticket_number }}
          </span>
          <span class="text-xs text-slate-400 font-semibold capitalize">• Category: {{ ticket.category }}</span>
        </div>
        <h1 class="text-xl font-black text-white">{{ ticket.subject }}</h1>
        <p class="text-xs text-slate-500">Created on {{ formatDate(ticket.created_at) }}</p>
      </div>

      <!-- Conversation Messages Thread -->
      <div class="space-y-4">
        <div
          v-for="msg in ticket.messages"
          :key="msg.id"
          :class="[
            'glass-card p-5 rounded-3xl border transition-all space-y-3',
            msg.is_admin ? 'border-amber-500/30 bg-amber-950/10 ml-4 sm:ml-8' : 'border-slate-800 bg-slate-900/60 mr-4 sm:mr-8'
          ]"
        >
          <div class="flex items-center justify-between text-xs border-b border-white/5 pb-2">
            <div class="flex items-center gap-2">
              <span
                :class="[
                  'w-7 h-7 rounded-xl flex items-center justify-center font-bold text-xs shadow-md',
                  msg.is_admin ? 'bg-amber-500 text-slate-950' : 'bg-indigo-600 text-white'
                ]"
              >
                {{ msg.is_admin ? 'AD' : (msg.sender?.name?.charAt(0) || 'U') }}
              </span>
              <div>
                <span class="font-bold text-white">{{ msg.is_admin ? 'Support Team (Admin)' : (msg.sender?.name || 'You') }}</span>
                <span v-if="msg.is_admin" class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-amber-500/20 text-amber-300">Official Reply</span>
              </div>
            </div>
            <span class="text-[11px] text-slate-500">{{ formatDate(msg.created_at) }}</span>
          </div>

          <p class="text-xs text-slate-200 leading-relaxed whitespace-pre-wrap pt-1">
            {{ msg.message }}
          </p>

          <!-- Attached Image -->
          <div v-if="msg.attachment" class="pt-2">
            <div class="inline-block rounded-2xl overflow-hidden border border-slate-700 bg-slate-950 p-1">
              <img
                :src="msg.attachment"
                alt="Attachment"
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

      <!-- Reply Box -->
      <div class="glass-card p-5 rounded-3xl border border-indigo-500/20 space-y-3">
        <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider">Reply to Thread</h3>
        <form @submit.prevent="submitReply" class="space-y-3">
          <textarea
            v-model="replyForm.message"
            rows="3"
            placeholder="Write your response here..."
            required
            class="input-dark text-xs py-2.5"
          ></textarea>

          <div>
            <label class="text-xs font-semibold text-slate-400 block mb-1">Attach Screenshot (Optional)</label>
            <input
              type="file"
              @change="handleFileChange"
              accept="image/*"
              class="w-full text-xs text-slate-400 file:mr-2 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-slate-800 file:text-slate-200 hover:file:bg-slate-700"
            />
          </div>

          <div v-if="replyForm.errors.message" class="text-xs text-rose-400">{{ replyForm.errors.message }}</div>
          <div v-if="replyForm.errors.attachment" class="text-xs text-rose-400">{{ replyForm.errors.attachment }}</div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="replyForm.processing || !replyForm.message"
              class="btn-neon btn-primary px-6 py-2.5 text-xs font-bold text-white rounded-xl disabled:opacity-50"
            >
              <span v-if="replyForm.processing">⏳ Sending...</span>
              <span v-else>💬 Post Reply</span>
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
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  ticket: Object,
});

const activeImageModal = ref(null);

const replyForm = useForm({
  message: '',
  attachment: null,
});

const handleFileChange = (e) => {
  if (e.target.files && e.target.files[0]) {
    replyForm.attachment = e.target.files[0];
  }
};

const submitReply = () => {
  replyForm.post(`/support/${props.ticket.id}/reply`, {
    forceFormData: true,
    onSuccess: () => {
      replyForm.reset();
    },
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
