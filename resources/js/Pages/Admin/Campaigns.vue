<template>
  <AdminLayout>
    <div class="space-y-4">

      <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex items-center gap-3">
          <h1 class="text-2xl font-extrabold text-white">📢 Campaign Manager</h1>
          <span class="badge badge-amber">{{ pendingCount }} pending</span>
        </div>
        <a href="/admin/campaigns/export" class="btn-neon btn-indigo py-2 px-4 rounded-xl text-xs font-bold text-white flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
          Export Backup (CSV)
        </a>
      </div>

      <!-- Filter tabs -->
      <div class="flex gap-2 flex-wrap">
        <button v-for="tab in tabs" :key="tab.value" @click="activeTab = tab.value"
          class="badge cursor-pointer transition-all"
          :class="activeTab === tab.value ? tab.cls : 'badge-indigo opacity-50'"
        >
          {{ tab.label }} ({{ campaigns.filter(c => tab.value === 'all' || c.status === tab.value).length }})
        </button>
      </div>

      <!-- Campaign Cards -->
      <div v-if="filteredCampaigns.length === 0" class="glass-card p-10 rounded-3xl border border-slate-800 text-center">
        <div class="text-3xl mb-3">📭</div>
        <p class="text-sm text-slate-400">No campaigns in this category.</p>
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-5">
        <div v-for="c in filteredCampaigns" :key="c.id"
          class="glass-card p-5 rounded-2xl border card-hover flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
          :class="{
            'border-amber-500/30 hover:border-amber-500/50 shadow-amber-500/5': c.status === 'pending',
            'border-emerald-500/30 hover:border-emerald-500/50 shadow-emerald-500/5': c.status === 'active',
            'border-rose-500/30 hover:border-rose-500/50 shadow-rose-500/5': c.status === 'rejected',
            'border-slate-700 hover:border-slate-500 shadow-slate-500/5': c.status === 'completed',
          }"
        >
          <div class="flex items-start justify-between gap-3 mb-4">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="badge" :class="statusBadge(c.status)">{{ c.status }}</span>
                <span class="badge badge-indigo">{{ campaignIcon(c.type) }} {{ c.type }}</span>
                <span class="badge badge-cyan" v-if="c.action">{{ c.action }}</span>
              </div>
              <h3 class="text-sm font-bold text-white truncate" :title="c.title">{{ c.title }}</h3>
              <p class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                {{ c.user.name }} ({{ c.user.email }})
              </p>
              <a :href="c.target_url" target="_blank" rel="noopener noreferrer" class="text-xs text-cyan-400 hover:text-cyan-300 hover:underline mt-1.5 flex items-center gap-1 truncate w-fit">
                🔗 {{ c.target_url }}
              </a>
            </div>
            <div class="text-right shrink-0 bg-slate-800/50 p-2 rounded-xl border border-slate-700/50">
              <div class="text-lg font-black text-amber-300 stat-number">{{ c.budget_points }}</div>
              <div class="text-[9px] uppercase tracking-wider text-slate-500 font-bold">Points</div>
            </div>
          </div>

          <!-- Spacer to push progress/actions to bottom -->
          <div class="flex-grow"></div>

          <!-- Progress -->
          <div class="mb-4 mt-2 space-y-1.5 bg-slate-900/40 p-3 rounded-xl border border-slate-800/50">
            <div class="flex justify-between text-[10px] text-slate-400 font-medium">
              <span>{{ c.total_clicks }} / {{ c.target_clicks }} clicks</span>
              <span class="text-indigo-400 font-bold">{{ c.progress }}%</span>
            </div>
            <div class="progress-track bg-slate-800 h-1.5">
              <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-400 h-1.5 rounded-full" :style="{ width: c.progress + '%' }"></div>
            </div>
          </div>

          <!-- Actions -->
          <div v-if="c.status === 'pending'" class="flex gap-2 mt-2">
            <button @click="approve(c)" class="btn-neon btn-emerald flex-1 py-2.5 rounded-xl text-[11px] font-bold text-white uppercase tracking-wide flex justify-center items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
              Approve
            </button>
            <button @click="startReject(c)" class="btn-neon bg-rose-700 hover:bg-rose-600 flex-1 py-2.5 rounded-xl text-[11px] font-bold text-white uppercase tracking-wide flex justify-center items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              Reject
            </button>
          </div>
          <div v-else-if="c.status === 'completed' || c.status === 'rejected'" class="mt-2 space-y-2">
            <div v-if="c.status === 'rejected' && c.admin_note" class="p-2 bg-rose-500/10 border border-rose-500/20 rounded-lg text-xs text-rose-300 flex items-start gap-1">
               <span class="mt-0.5">💬</span>
               <span class="italic">{{ c.admin_note }}</span>
            </div>
            <button @click="deleteCampaign(c)" class="w-full btn-neon bg-slate-800 hover:bg-rose-600/80 py-2 rounded-xl text-[11px] font-bold text-slate-300 hover:text-white uppercase tracking-wide flex justify-center items-center gap-1 transition-all border border-slate-700 hover:border-rose-500">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
              Delete Record
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Reject Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="rejectTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
          <div class="glass-card max-w-sm w-full p-6 rounded-3xl border border-rose-500/30 animate-slide-in-up">
            <h3 class="text-base font-black text-white mb-2">❌ Reject Campaign</h3>
            <p class="text-xs text-slate-400 mb-4">The unspent budget will be refunded to the user.</p>
            <textarea v-model="rejectNote" placeholder="Reason for rejection (required)" class="input-dark text-xs resize-none mb-3" rows="3"></textarea>
            <div class="flex gap-2">
              <button @click="rejectTarget = null" class="flex-1 py-2.5 glass-pill text-xs text-slate-400 rounded-xl border border-white/8">Cancel</button>
              <button @click="confirmReject" class="flex-1 btn-neon bg-rose-700 hover:bg-rose-600 py-2.5 text-xs font-bold text-white rounded-xl">Reject</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
    <!-- Delete Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
          <div class="glass-card max-w-sm w-full p-6 rounded-3xl border border-rose-500/30 animate-slide-in-up">
            <div class="flex items-center gap-3 mb-3">
              <div class="w-10 h-10 rounded-full bg-rose-500/20 flex items-center justify-center text-rose-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
              </div>
              <h3 class="text-base font-black text-white">Delete Campaign?</h3>
            </div>
            <p class="text-xs text-slate-300 mb-2">
              Are you sure you want to permanently delete this <strong class="text-white">{{ deleteTarget.status }}</strong> campaign?
            </p>
            <p class="text-xs text-rose-400 mb-5 font-semibold">
              Please make sure you have downloaded a backup CSV first. This action cannot be undone.
            </p>
            <div class="flex gap-2">
              <button @click="deleteTarget = null" class="flex-1 py-2.5 glass-pill text-xs font-bold text-slate-300 hover:text-white rounded-xl border border-white/10 transition-colors">Cancel</button>
              <button @click="confirmDelete" class="flex-1 btn-neon bg-rose-600 hover:bg-rose-500 py-2.5 text-xs font-bold text-white rounded-xl flex justify-center items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                Delete Now
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({ campaigns: Array });

const activeTab   = ref('pending');
const rejectTarget = ref(null);
const rejectNote  = ref('');
const deleteTarget = ref(null);

const tabs = [
  { value: 'all',       label: 'All',       cls: 'badge-indigo' },
  { value: 'pending',   label: 'Pending',   cls: 'badge-amber'  },
  { value: 'active',    label: 'Active',    cls: 'badge-emerald'},
  { value: 'completed', label: 'Completed', cls: 'badge-cyan'   },
  { value: 'rejected',  label: 'Rejected',  cls: 'badge-rose'   },
];

const filteredCampaigns = computed(() =>
  activeTab.value === 'all' ? props.campaigns : props.campaigns.filter(c => c.status === activeTab.value)
);

const pendingCount = computed(() => props.campaigns.filter(c => c.status === 'pending').length);

const campaignIcon = (type) => ({ website: '🌐', telegram: '✈️', youtube: '▶️', facebook: '📘', other: '📎' })[type] || '📎';
const statusBadge  = (s)    => ({ pending: 'badge-amber', active: 'badge-emerald', completed: 'badge-cyan', rejected: 'badge-rose' })[s] || 'badge-indigo';

const approve = (c) => {
  router.post(`/admin/campaigns/${c.id}/approve`, {}, { preserveScroll: true });
};

const startReject = (c) => {
  rejectTarget.value = c;
  rejectNote.value   = '';
};

const confirmReject = () => {
  if (!rejectNote.value.trim()) return;
  router.post(`/admin/campaigns/${rejectTarget.value.id}/reject`, { admin_note: rejectNote.value }, {
    preserveScroll: true,
    onSuccess: () => { rejectTarget.value = null; },
  });
};

const deleteCampaign = (c) => {
  deleteTarget.value = c;
};

const confirmDelete = () => {
  if (!deleteTarget.value) return;
  router.delete(`/admin/campaigns/${deleteTarget.value.id}`, { 
    preserveScroll: true,
    onSuccess: () => { deleteTarget.value = null; },
  });
};
</script>

<style scoped>
.modal-enter-active { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.modal-leave-active { transition: all 0.2s ease-in; }
.modal-enter-from   { transform: scale(0.85); opacity: 0; }
.modal-leave-to     { transform: scale(1.05); opacity: 0; }
</style>
