<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up">

      <!-- Header -->
      <div class="glass-card p-6 rounded-3xl border border-pink-500/15 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-pink-500/8 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="badge badge-violet mb-2">📜 History</div>
            <h1 class="text-2xl font-black text-white">Campaigns History</h1>
            <p class="text-xs text-slate-400 mt-1">View the full history of your campaigns.</p>
          </div>
          <div class="text-right shrink-0">
            <Link href="/campaigns" class="btn-neon inline-flex items-center justify-center py-2 px-6 rounded-xl text-sm font-bold text-white btn-cyan">
              🔙 Back to Campaigns
            </Link>
          </div>
        </div>
      </div>

      <!-- My Campaigns History -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800/50">
        <div class="section-header mb-5">
          <span class="section-title">📊 Full History</span>
          <div class="section-header-line"></div>
          <span class="badge badge-indigo shrink-0">{{ myCampaigns.length }}</span>
        </div>

        <div v-if="myCampaigns.length === 0" class="text-center py-10 text-slate-500 text-sm">
          No campaigns found. 🚀
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
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  myCampaigns: Array,
});

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
