<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up">

      <!-- Header -->
      <div class="glass-card p-6 rounded-3xl border border-pink-500/15 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-pink-500/8 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="badge badge-violet mb-2">📢 Ad Engine</div>
            <h1 class="text-2xl font-black text-white">Micro-Campaign Hub</h1>
            <p class="text-xs text-slate-400 mt-1">Spend points to promote your link. Others earn by clicking yours.</p>
          </div>
          <div class="text-right shrink-0">
            <div class="text-xs text-slate-500 mb-0.5">Available Balance</div>
            <div class="text-2xl font-black text-emerald-300"><AnimatedNumber :value="user.main_balance" :decimals="0" /> <span class="text-xs font-normal text-slate-500">Pts</span></div>
          </div>
        </div>
      </div>

      <!-- Create Campaign -->
      <div class="glass-card p-6 rounded-3xl border border-violet-500/15">
        <div class="section-header mb-5">
          <span class="section-title">✨ Create Campaign</span>
          <div class="section-header-line"></div>
        </div>

        <form @submit.prevent="createCampaign" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Campaign Title</label>
              <input v-model="form.title" type="text" placeholder="My Telegram Channel" class="input-dark" maxlength="100" required />
            </div>
            <div class="md:col-span-2">
              <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Select Service</label>
              <select v-model="form.campaign_service_id" class="input-dark">
                <option value="" disabled>Choose a service</option>
                <option v-for="service in services" :key="service.id" :value="service.id">
                  {{ campaignIcon(service.platform.toLowerCase()) }} {{ service.platform }} - {{ service.action }} (Cost: {{ service.creator_cost }} pts/click)
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Target URL</label>
            <input v-model="form.target_url" type="url" placeholder="https://t.me/yourchannel" class="input-dark" required />
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Description (optional)</label>
            <textarea v-model="form.description" placeholder="What is this campaign about?" class="input-dark resize-none" rows="2" maxlength="300"></textarea>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 flex items-center justify-between">
              <span>Target Clicks: <span class="text-indigo-400 font-black">{{ form.target_clicks }}</span></span>
              <span class="text-emerald-400 font-bold">Cost: {{ totalCost }} points</span>
            </label>
            <input v-model.number="form.target_clicks" type="range" min="50" max="5000" step="50" class="w-full accent-indigo-500" />
            <div class="flex justify-between text-[10px] text-slate-600 mt-1">
              <span>50 clicks</span>
              <span>5,000 clicks</span>
            </div>
          </div>

          <div v-if="errors.budget" class="text-rose-400 text-xs p-3 bg-rose-500/10 rounded-xl border border-rose-500/25">
            ❌ {{ errors.budget }}
          </div>

          <button
            type="submit"
            :disabled="submitting || totalCost > user.main_balance || !form.campaign_service_id"
            class="btn-neon w-full py-3.5 rounded-2xl text-sm font-black text-white"
            :class="(totalCost <= user.main_balance && form.campaign_service_id) ? 'btn-primary' : 'bg-slate-800 text-slate-500 cursor-not-allowed'"
          >
            <span v-if="submitting" class="flex items-center justify-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              Submitting...
            </span>
            <span v-else-if="!form.campaign_service_id">⚠️ Please Select a Service</span>
            <span v-else-if="totalCost > user.main_balance">⚠️ Insufficient Balance</span>
            <span v-else>🚀 Launch Campaign ({{ totalCost }} pts)</span>
          </button>
        </form>
      </div>

      <!-- Earn by Clicking Active Campaigns -->
      <div v-if="activeCampaigns.length" class="glass-card p-6 rounded-3xl border border-cyan-500/15">
        <div class="section-header mb-5">
          <span class="section-title">⚡ Earn by Clicking</span>
          <div class="section-header-line"></div>
          <span class="badge badge-cyan shrink-0">{{ activeCampaigns.length }} live</span>
        </div>

        <TransitionGroup name="list" tag="div" class="space-y-3 relative">
          <div v-for="campaign in activeCampaigns" :key="campaign.id"
            class="glass-pill p-4 rounded-2xl border border-white/5 flex items-center gap-4 card-hover">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0"
              :class="{
                'bg-blue-500/20': campaign.type === 'telegram',
                'bg-red-500/20': campaign.type === 'youtube',
                'bg-indigo-500/20': campaign.type === 'website',
                'bg-blue-600/20': campaign.type === 'facebook',
                'bg-slate-700': campaign.type === 'other',
              }">
              {{ campaignIcon(campaign.type) }}
            </div>
            <div class="flex-1 min-w-0">
              <div class="text-sm font-bold text-white truncate">{{ campaign.title }}</div>
              <div class="text-[10px] text-slate-400 mt-0.5" v-if="campaign.action">
                Action: <span class="text-white font-medium">{{ campaign.action }}</span>
              </div>
              <div class="text-[10px] text-slate-500 flex items-center gap-1.5 mt-0.5">
                <span class="badge badge-emerald">+{{ campaign.cost_per_click }} pts</span>
                <span>per action</span>
              </div>
            </div>
            <button
              @click="clickCampaign(campaign)"
              :disabled="clickedIds.has(campaign.id) || loadingIds.has(campaign.id)"
              class="btn-neon text-xs py-2 px-4 rounded-xl font-bold text-white shrink-0 min-w-[80px] flex items-center justify-center transition-all"
              :class="clickedIds.has(campaign.id) ? 'bg-slate-700 text-slate-500 cursor-not-allowed' : 'btn-cyan'"
            >
              <svg v-if="loadingIds.has(campaign.id)" class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              <span v-else-if="clickedIds.has(campaign.id)">✅ Done</span>
              <span v-else>👆 Visit</span>
            </button>
          </div>
        </TransitionGroup>
      </div>

      <!-- My Campaigns -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800/50">
        <div class="section-header mb-5">
          <span class="section-title">📊 My Campaigns</span>
          <div class="section-header-line"></div>
          <span class="badge badge-indigo shrink-0">{{ myCampaigns.length }}</span>
        </div>

        <div v-if="myCampaigns.length === 0" class="text-center py-10 text-slate-500 text-sm">
          No campaigns yet. Create your first one above! 🚀
        </div>

        <div v-else class="space-y-4">
          <div v-for="c in myCampaigns" :key="c.id"
            class="glass-pill p-5 rounded-2xl border border-white/5">
            <div class="flex items-start justify-between gap-3 mb-3">
              <div>
                <div class="text-sm font-bold text-white flex items-center gap-2">
                  {{ campaignIcon(c.type) }} {{ c.title }}
                </div>
                <div class="text-[10px] text-slate-400 mt-0.5" v-if="c.action">Action: <span class="text-white">{{ c.action }}</span></div>
                <div class="text-[10px] text-slate-500 mt-0.5">{{ c.created_at }}</div>
              </div>
              <span class="badge shrink-0" :class="statusBadge(c.status)">{{ c.status }}</span>
            </div>

            <!-- Progress -->
            <div class="space-y-1.5">
              <div class="flex justify-between text-[10px] text-slate-400">
                <span>{{ c.total_clicks }} / {{ c.target_clicks }} clicks</span>
                <span class="font-bold text-indigo-400">{{ c.progress }}%</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-400" :style="{ width: c.progress + '%' }"></div>
              </div>
            </div>

            <div class="flex items-center justify-between mt-3 text-[11px]">
              <span class="text-slate-500">Budget: <span class="text-amber-400 font-bold">{{ c.budget_points }} pts</span></span>
              <a :href="c.target_url" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:text-cyan-300 transition-colors">
                🔗 View URL →
              </a>
            </div>

            <div v-if="c.admin_note" class="mt-2 text-[10px] bg-rose-500/10 border border-rose-500/20 rounded-lg p-2 text-rose-400">
              ⚠️ Admin Note: {{ c.admin_note }}
            </div>
          </div>
        </div>
          
        <div v-if="myCampaigns.length >= 5" class="mt-6 text-center">
          <Link href="/campaigns-history" class="btn-neon inline-flex items-center justify-center py-2 px-6 rounded-xl text-sm font-bold text-white btn-primary">
            See More History →
          </Link>
        </div>
      </div>

    </div>

    <!-- Local Toasts (for clicks) -->
    <div class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 pointer-events-none">
      <TransitionGroup name="toast">
        <div v-for="toast in toasts" :key="toast.id" 
          class="bg-emerald-500/90 backdrop-blur-md text-white text-sm font-bold px-4 py-3 rounded-xl shadow-lg border border-emerald-400/20 flex items-center gap-2 pointer-events-auto shadow-emerald-500/20">
          <span>{{ toast.icon }}</span>
          <span>{{ toast.message }}</span>
        </div>
      </TransitionGroup>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import AnimatedNumber from '@/Components/AnimatedNumber.vue';

const props = defineProps({
  user:            Object,
  myCampaigns:     Array,
  activeCampaigns: Array,
  settings:        Object,
  services:        Array,
});

const form = reactive({
  title:         '',
  description:   '',
  target_url:    '',
  campaign_service_id: '',
  target_clicks: 100,
});

const selectedService = computed(() => {
  return props.services.find(s => s.id === form.campaign_service_id);
});

const totalCost = computed(() => {
  if (!selectedService.value) return 0;
  return form.target_clicks * selectedService.value.creator_cost;
});

const errors     = ref({});
const submitting = ref(false);
const clickedIds = ref(new Set());
const loadingIds = ref(new Set());
const toasts     = ref([]);

const addToast = (message, icon = '✅') => {
  const id = Date.now();
  toasts.value.push({ id, message, icon });
  setTimeout(() => {
    toasts.value = toasts.value.filter(t => t.id !== id);
  }, 3500);
};

const formatBal = (v) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const campaignIcon = (type) => {
  const icons = { website: '🌐', telegram: '✈️', youtube: '▶️', facebook: '📘', other: '📎' };
  return icons[type] || '📎';
};

const statusBadge = (status) => {
  const map = {
    pending: 'badge-amber', active: 'badge-emerald',
    paused: 'badge-violet', completed: 'badge-cyan', rejected: 'badge-rose',
  };
  return map[status] || 'badge-indigo';
};

const createCampaign = () => {
  errors.value   = {};
  submitting.value = true;
  router.post('/campaigns', form, {
    preserveScroll: true,
    onError: (e) => { errors.value = e; },
    onFinish: () => { submitting.value = false; },
    onSuccess: () => {
      form.title       = '';
      form.description = '';
      form.target_url  = '';
      form.target_clicks = 100;
      form.campaign_service_id = '';
    },
  });
};

const clickCampaign = async (campaign) => {
  if (clickedIds.value.has(campaign.id) || loadingIds.value.has(campaign.id)) return;
  
  loadingIds.value.add(campaign.id);
  try {
    const res = await axios.post(`/campaigns/${campaign.id}/click`, {}, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
    });
    if (res.data.success) {
      clickedIds.value = new Set([...clickedIds.value, campaign.id]);
      window.open(res.data.target_url, '_blank', 'noopener,noreferrer');
      
      addToast(`Earned +${campaign.cost_per_click} pts!`, '🎉');
      
      // Reload inertia props in the background to update balance and remove completed campaigns
      router.reload({ only: ['user', 'activeCampaigns'] });
    }
  } catch (e) {
    console.error(e);
  } finally {
    loadingIds.value.delete(campaign.id);
  }
};
</script>

<style scoped>
.list-enter-active, .list-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}
.list-enter-from, .list-leave-to {
  opacity: 0;
  transform: scale(0.95) translateY(10px);
}
.list-leave-active {
  position: absolute;
  width: 100%;
}

.toast-enter-active, .toast-leave-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.9);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(20px) scale(0.9);
}
</style>
