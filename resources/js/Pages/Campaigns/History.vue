<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up max-w-5xl mx-auto">

      <!-- Header -->
      <div class="glass-card p-6 rounded-3xl border border-pink-500/15 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-pink-500/8 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <Link href="/campaigns" class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-300 transition-colors">
                ←
              </Link>
              <div class="badge badge-violet">📜 History</div>
            </div>
            <h1 class="text-2xl font-black text-white">My Campaigns History</h1>
            <p class="text-xs text-slate-400 mt-1">Full history of all campaigns you have created and promoted.</p>
          </div>
          <div class="text-right shrink-0">
            <Link href="/campaigns" class="btn-neon inline-flex items-center justify-center py-2.5 px-5 rounded-xl text-xs font-bold text-white bg-gradient-to-r from-pink-600 to-violet-600 hover:from-pink-500 hover:to-violet-500 shadow-lg shadow-pink-500/20">
              ➕ Create New Campaign
            </Link>
          </div>
        </div>
      </div>

      <!-- My Campaigns History List -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800/50">
        <div class="section-header mb-5 flex justify-between items-center">
          <div class="flex items-center gap-3">
            <span class="section-title">📊 All Campaigns</span>
            <span class="badge badge-indigo shrink-0">{{ campaignList.length }}</span>
          </div>
          <p class="text-xs text-slate-400 hidden sm:block">Inspect your active and past campaigns with worker submissions</p>
        </div>

        <div v-if="campaignList.length === 0" class="text-center py-12 text-slate-500 text-sm">
          <div class="text-4xl mb-2">📢</div>
          <p class="font-bold text-white mb-1">No campaigns created yet</p>
          <p class="text-xs text-slate-400 mb-4">Start your first micro-campaign to promote your links and channels.</p>
          <Link href="/campaigns" class="btn-neon py-2 px-5 rounded-xl text-xs font-bold text-white btn-primary inline-flex items-center gap-1.5">
            🚀 Launch First Campaign
          </Link>
        </div>

        <div v-else class="space-y-4">
          <div v-for="c in campaignList" :key="c.id"
            class="glass-pill p-5 rounded-2xl border border-white/5 space-y-3">
            <div class="flex items-start justify-between gap-3">
              <div>
                <div class="text-sm font-bold text-white flex items-center gap-2 flex-wrap">
                  <span>{{ campaignIcon(c.type) }} {{ c.title }}</span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 capitalize">
                    🛡️ {{ formatProofType(c.proof_type) }}
                  </span>
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5" v-if="c.action">Action: <span class="text-white">{{ c.action }}</span></div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ c.created_at }}</div>
              </div>
              <span class="badge shrink-0" :class="statusBadge(c.status)">{{ c.status }}</span>
            </div>

            <!-- Progress -->
            <div class="space-y-1.5">
              <div class="flex justify-between text-[10px] text-slate-400">
                <span>{{ c.total_clicks }} / {{ c.target_clicks }} verified completions</span>
                <span class="font-bold text-indigo-400">{{ c.progress }}%</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-400" :style="{ width: c.progress + '%' }"></div>
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-800/80 text-[11px]">
              <div class="flex items-center gap-3">
                <span class="text-slate-500">Budget: <strong class="text-amber-400">{{ c.budget_points }} pts</strong></span>
                <a :href="c.target_url" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:text-cyan-300 transition-colors">
                  🔗 Target URL →
                </a>
              </div>

              <!-- View Proofs Button -->
              <button
                type="button"
                @click="openSubmissionsModal(c)"
                class="px-3.5 py-1.5 bg-indigo-500/15 hover:bg-indigo-500/25 border border-indigo-500/30 text-indigo-300 hover:text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-sm active:scale-95"
              >
                <span>👁️</span>
                <span>View Proofs</span>
                <span v-if="c.submissions_count > 0" class="px-1.5 py-0.5 bg-indigo-500 text-white rounded-md text-[10px] font-mono font-bold">{{ c.submissions_count }}</span>
              </button>
            </div>

            <div v-if="c.admin_note" class="mt-2 text-[10px] bg-rose-500/10 border border-rose-500/20 rounded-lg p-2 text-rose-400">
              ⚠️ Admin Note: {{ c.admin_note }}
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="myCampaigns && myCampaigns.links && myCampaigns.links.length > 3" class="mt-6 flex flex-wrap justify-center gap-1.5">
          <Link v-for="(link, i) in myCampaigns.links" :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
            :class="[
              link.active 
                ? 'bg-pink-500/20 border-pink-500/40 text-pink-300' 
                : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10 hover:text-white',
              !link.url ? 'opacity-50 cursor-not-allowed' : ''
            ]"
            preserve-scroll
          />
        </div>
      </div>

    </div>

    <!-- Submissions / Proofs Modal -->
    <Transition name="modal">
      <div v-if="selectedCampaignForProof" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" @click="selectedCampaignForProof = null">
        <div class="relative w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-3xl p-5 sm:p-6 shadow-2xl space-y-4 max-h-[85vh] flex flex-col" @click.stop>
          <!-- Modal Header -->
          <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <div>
              <h3 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                <span>📋 Submissions:</span>
                <span class="text-indigo-400 truncate max-w-xs sm:max-w-md">{{ selectedCampaignForProof.title }}</span>
              </h3>
              <p class="text-xs text-slate-400 mt-0.5">
                Target: {{ selectedCampaignForProof.total_clicks }}/{{ selectedCampaignForProof.target_clicks }} verified completions
              </p>
            </div>
            <button @click="selectedCampaignForProof = null" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">✕</button>
          </div>

          <!-- Loading state -->
          <div v-if="loadingSubmissions" class="py-12 flex flex-col items-center justify-center space-y-2 text-indigo-400">
            <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-xs text-slate-400">Loading submitted proofs...</span>
          </div>

          <!-- Empty state -->
          <div v-else-if="campaignSubmissions.length === 0" class="py-12 text-center text-slate-500 text-xs">
            <div class="text-3xl mb-2">📭</div>
            <p>No worker submissions received yet.</p>
            <p class="text-[11px] text-slate-600 mt-1">Once approved by Admin, workers' proofs will appear here.</p>
          </div>

          <!-- Submissions list -->
          <div v-else class="space-y-3 overflow-y-auto pr-1 flex-grow">
            <div v-for="sub in campaignSubmissions" :key="sub.id" class="bg-slate-950/70 border border-slate-800 rounded-2xl p-3.5 space-y-2.5 text-xs">
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-white">{{ sub.user_name }}</span>
                  <span class="text-slate-500 font-mono text-[10px]">({{ sub.user_phone }})</span>
                </div>
                <div class="flex items-center gap-2">
                  <span v-if="sub.status === 'approved'" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded-md text-[10px] font-bold">Approved ✅</span>
                  <span v-else-if="sub.status === 'pending'" class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded-md text-[10px] font-bold">Admin Reviewing ⏳</span>
                  <span v-else class="px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded-md text-[10px] font-bold">Rejected ❌</span>
                  <span class="text-slate-500 text-[10px]">{{ sub.submitted_at }}</span>
                </div>
              </div>

              <!-- Submitted Username / Link -->
              <div v-if="sub.submitted_data?.username_link" class="p-2.5 bg-slate-900 rounded-xl border border-slate-800/80 flex items-center justify-between gap-2">
                <span class="text-cyan-400 font-medium shrink-0">🔗 Submitted Profile:</span>
                <span class="font-mono text-white select-all break-all text-right">{{ sub.submitted_data.username_link }}</span>
              </div>

              <!-- Submitted Secret Code -->
              <div v-if="sub.submitted_data?.secret_code" class="p-2.5 bg-slate-900 rounded-xl border border-slate-800/80 flex items-center justify-between gap-2">
                <span class="text-amber-400 font-medium shrink-0">🔑 Secret Code:</span>
                <span class="font-mono text-amber-200 select-all">{{ sub.submitted_data.secret_code }}</span>
              </div>

              <!-- Submitted Screenshot Thumbnail -->
              <div v-if="sub.screenshot_path" class="flex items-center gap-2 pt-1">
                <span class="text-slate-400 shrink-0">📸 Screenshot:</span>
                <div
                  @click="previewImage = '/storage/' + sub.screenshot_path"
                  class="relative group cursor-pointer w-20 h-16 rounded-xl overflow-hidden border border-slate-700 hover:border-indigo-500 transition-colors shrink-0 shadow-md"
                >
                  <img :src="'/storage/' + sub.screenshot_path" class="w-full h-full object-cover group-hover:scale-110 transition-transform" alt="Proof" />
                  <div class="absolute inset-0 bg-indigo-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-xs text-white">🔍 View</div>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-800 flex justify-end">
            <button type="button" @click="selectedCampaignForProof = null" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs transition-colors">
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Image Zoom Modal -->
    <Transition name="modal">
      <div v-if="previewImage" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md" @click="previewImage = null">
        <div class="relative max-w-3xl max-h-[90vh] bg-slate-900 rounded-2xl overflow-hidden border border-slate-800 p-2" @click.stop>
          <img :src="previewImage" class="w-full h-auto max-h-[80vh] object-contain rounded-xl" alt="Proof Preview" />
          <button @click="previewImage = null" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-950/80 text-white flex items-center justify-center hover:bg-rose-600 transition-colors">✕</button>
        </div>
      </div>
    </Transition>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  myCampaigns: [Array, Object],
});

const campaignList = computed(() => {
  if (Array.isArray(props.myCampaigns)) return props.myCampaigns;
  if (props.myCampaigns && Array.isArray(props.myCampaigns.data)) return props.myCampaigns.data;
  return [];
});

const selectedCampaignForProof = ref(null);
const campaignSubmissions = ref([]);
const loadingSubmissions = ref(false);
const previewImage = ref(null);

const openSubmissionsModal = async (campaign) => {
  selectedCampaignForProof.value = campaign;
  loadingSubmissions.value = true;
  campaignSubmissions.value = [];
  try {
    const res = await axios.get(`/campaigns/${campaign.id}/submissions`);
    if (res.data && res.data.submissions) {
      campaignSubmissions.value = res.data.submissions;
    }
  } catch (err) {
    console.error('Failed to load submissions', err);
  } finally {
    loadingSubmissions.value = false;
  }
};

const formatProofType = (type) => {
  const map = {
    screenshot: 'Screenshot',
    username_link: 'Username / Link',
    secret_code: 'Secret Code',
    screenshot_username: 'Screenshot + Username',
    screenshot_code: 'Screenshot + Code',
    username_code: 'Username + Code',
    all: 'Screenshot + User + Code',
  };
  return map[type] || type || 'Screenshot';
};

const campaignIcon = (type) => {
  const icons = { website: '🌐', telegram: '✈️', youtube: '▶️', facebook: '📘', other: '📎' };
  return icons[type?.toLowerCase()] || '📎';
};

const statusBadge = (status) => {
  const map = {
    pending: 'badge-amber', active: 'badge-emerald',
    paused: 'badge-violet', completed: 'badge-cyan', rejected: 'badge-rose',
  };
  return map[status] || 'badge-indigo';
};
</script>
