<template>
  <AdminLayout>
    <div class="space-y-6 animate-slide-in-up">
      <!-- Page Title -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 glass-card p-6 rounded-3xl border border-indigo-500/15">
        <div>
          <h1 class="text-2xl font-black text-white flex items-center gap-2">
            <span>🏆</span> Referral Contests & Leaderboard
          </h1>
          <p class="text-sm text-slate-400 mt-1">
            Manage weekly/monthly top referrer contests, monitor anti-fraud flags, and distribute prizes safely.
          </p>
        </div>
        <button
          @click="showCreateModal = true"
          class="px-5 py-3 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-bold text-xs shadow-lg transition-all flex items-center gap-2 shrink-0 cursor-pointer"
        >
          <span>➕ Create New Contest</span>
        </button>
      </div>

      <!-- Active Contest Banner & Actions -->
      <div v-if="activeContest" class="glass-card p-6 rounded-3xl border border-amber-500/30 bg-amber-500/5">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-6 border-b border-white/10">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/30 mb-2">
              <span>● ACTIVE CONTEST</span>
            </div>
            <h2 class="text-xl font-black text-white">{{ activeContest.title }}</h2>
            <div class="flex flex-wrap gap-4 text-xs text-slate-300 mt-2 font-mono">
              <span>🗓️ Start: {{ formatDate(activeContest.start_date) }}</span>
              <span>🗓️ End: {{ formatDate(activeContest.end_date) }}</span>
              <span>🎯 Min Unlocked: {{ activeContest.min_unlocked_required }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="distributeRewards"
              :disabled="distributing"
              class="px-6 py-3.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-black text-xs shadow-lg shadow-amber-500/20 transition-all flex items-center gap-2"
            >
              <span>🎁 {{ distributing ? 'Distributing...' : 'Distribute Rewards & End Contest' }}</span>
            </button>
            <button
              @click="cancelContest"
              class="px-4 py-3.5 rounded-xl bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/30 font-bold text-xs transition-all"
            >
              Cancel
            </button>
          </div>
        </div>

        <!-- Prizes summary -->
        <div class="pt-4">
          <div class="text-xs uppercase font-bold text-slate-400 mb-2">Prize Pool Structure</div>
          <div class="flex flex-wrap gap-2">
            <div
              v-for="prize in activeContest.prizes"
              :key="prize.rank"
              class="glass-pill px-3 py-1.5 rounded-xl border border-white/10 text-xs flex items-center gap-2"
            >
              <span class="font-bold text-amber-400">Rank #{{ prize.rank }}</span>
              <span class="text-white font-mono font-black">{{ prize.reward }} Coins</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Live Leaderboard with Anti-Fraud Flags -->
      <div v-if="activeContest" class="glass-card p-6 rounded-3xl border border-indigo-500/15">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-bold text-white flex items-center gap-2">
            <span>🛡️</span> Live Security-Inspected Leaderboard
          </h3>
          <span class="text-xs text-slate-400 font-mono">Realtime Query</span>
        </div>

        <div v-if="leaderboard.length === 0" class="text-center py-8 text-slate-400 text-sm">
          No referrers have unlocked any referrals yet for this contest period.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-white/10">
                <th class="py-3 px-4">Rank</th>
                <th class="py-3 px-4">User</th>
                <th class="py-3 px-4">Device & Risk</th>
                <th class="py-3 px-4 text-center">Unlocked Referrals</th>
                <th class="py-3 px-4 text-center">Anti-Fraud Status</th>
                <th class="py-3 px-4 text-right">Est. Reward</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-sm">
              <tr v-for="row in leaderboard" :key="row.user_id" class="hover:bg-white/5 transition-colors">
                <td class="py-3 px-4 font-mono font-bold text-white">#{{ row.rank }}</td>
                <td class="py-3 px-4">
                  <div class="font-bold text-white">{{ row.name }}</div>
                  <div class="text-xs text-slate-400 font-mono">{{ row.email }}</div>
                </td>
                <td class="py-3 px-4 font-mono text-xs text-slate-300">
                  <div>Score: <span :class="row.risk_score >= 50 ? 'text-amber-400 font-bold' : 'text-emerald-400'">{{ row.risk_score }}</span></div>
                  <div class="text-[10px] text-slate-500 truncate max-w-[120px]">{{ row.device_hash || 'No Hash' }}</div>
                </td>
                <td class="py-3 px-4 text-center font-mono font-bold text-emerald-400 text-base">
                  {{ row.unlocked_count }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span v-if="row.suspicious_device_count > 0" class="px-2.5 py-1 rounded-full bg-rose-500/20 text-rose-300 border border-rose-500/30 text-xs font-bold flex items-center justify-center gap-1">
                    <span>⚠️</span> {{ row.suspicious_device_count }} Same Device Ref
                  </span>
                  <span v-else class="px-2.5 py-1 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-xs font-bold">
                    ✓ Clean
                  </span>
                </td>
                <td class="py-3 px-4 text-right font-mono font-bold text-amber-300">
                  {{ row.estimated_prize > 0 ? `${row.estimated_prize} Coins` : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Past Contests History Table -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/15">
        <h3 class="text-lg font-bold text-white mb-4">All Contests History</h3>

        <div v-if="contests.length === 0" class="text-center py-8 text-slate-400 text-sm">
          No contests created yet.
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-white/10">
                <th class="py-3 px-4">Title</th>
                <th class="py-3 px-4">Dates</th>
                <th class="py-3 px-4 text-center">Status</th>
                <th class="py-3 px-4 text-center">Winners</th>
                <th class="py-3 px-4 text-right">Distributed At</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-sm">
              <tr v-for="c in contests" :key="c.id" class="hover:bg-white/5 transition-colors">
                <td class="py-3.5 px-4 font-bold text-white">{{ c.title }}</td>
                <td class="py-3.5 px-4 text-xs font-mono text-slate-300">
                  {{ formatDate(c.start_date) }} → {{ formatDate(c.end_date) }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span
                    class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase"
                    :class="{
                      'bg-amber-500/20 text-amber-300 border border-amber-500/30': c.status === 'active',
                      'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': c.status === 'completed',
                      'bg-slate-500/20 text-slate-400 border border-slate-500/30': c.status === 'cancelled',
                    }"
                  >
                    {{ c.status }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center font-mono text-white">
                  {{ c.winners ? c.winners.length : 0 }}
                </td>
                <td class="py-3.5 px-4 text-right text-xs font-mono text-slate-400">
                  {{ c.distributed_at ? formatDate(c.distributed_at) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create Contest Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
      <div class="glass-card p-6 sm:p-8 rounded-3xl border border-indigo-500/30 max-w-lg w-full bg-slate-900 space-y-5">
        <div class="flex items-center justify-between">
          <h3 class="text-xl font-black text-white">Create Referral Contest</h3>
          <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">✕</button>
        </div>

        <div v-if="activeContest" class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-start gap-2.5">
          <span class="text-base shrink-0">⚠️</span>
          <div>
            <strong>Active Contest Currently Running:</strong> "{{ activeContest.title }}" is active. Please distribute rewards to complete it or cancel it before starting a new contest.
          </div>
        </div>

        <form @submit.prevent="submitCreateContest" class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Contest Title</label>
            <input
              v-model="form.title"
              type="text"
              required
              placeholder="e.g. Weekly Top Referrer Contest #1"
              class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:border-indigo-500 outline-none"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Start Date & Time</label>
              <input
                v-model="form.start_date"
                type="datetime-local"
                required
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-xs focus:border-indigo-500 outline-none"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-300 uppercase mb-1">End Date & Time</label>
              <input
                v-model="form.end_date"
                type="datetime-local"
                required
                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2.5 text-white text-xs focus:border-indigo-500 outline-none"
              />
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold text-slate-300 uppercase mb-1">Min Unlocked Referrals Required</label>
            <input
              v-model.number="form.min_unlocked_required"
              type="number"
              min="1"
              required
              class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-white text-sm focus:border-indigo-500 outline-none"
            />
          </div>

          <!-- Prize list builder -->
          <div>
            <div class="flex items-center justify-between mb-2">
              <label class="block text-xs font-bold text-slate-300 uppercase">Prize Pool (Rank Rewards)</label>
              <button type="button" @click="addPrizeRow" class="text-xs text-indigo-400 font-bold hover:underline">+ Add Rank</button>
            </div>
            <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
              <div v-for="(p, index) in form.prizes" :key="index" class="flex items-center gap-2">
                <span class="text-xs font-mono text-slate-400 shrink-0 w-16">Rank #{{ p.rank }}:</span>
                <input
                  v-model.number="p.reward"
                  type="number"
                  min="1"
                  required
                  placeholder="Reward coins"
                  class="flex-1 bg-slate-950 border border-slate-700 rounded-lg px-3 py-1.5 text-white text-xs focus:border-indigo-500 outline-none font-mono"
                />
                <button v-if="form.prizes.length > 1" type="button" @click="removePrizeRow(index)" class="text-rose-400 hover:text-rose-300 text-xs px-2">✕</button>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-3">
            <button type="button" @click="showCreateModal = false" class="px-4 py-2.5 rounded-xl border border-slate-700 text-slate-300 text-xs font-bold">
              Cancel
            </button>

            <button
              type="submit"
              :disabled="submitting || activeContest !== null"
              class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ submitting ? 'Creating...' : (activeContest ? 'Finish Active Contest First' : 'Create Contest') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  contests: Array,
  activeContest: Object,
  leaderboard: Array,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const showCreateModal = ref(false);
const submitting = ref(false);
const distributing = ref(false);

const form = ref({
  title: 'Weekly Top Referrer Contest',
  start_date: new Date().toISOString().slice(0, 16),
  end_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 16),
  min_unlocked_required: 1,
  prizes: [
    { rank: 1, reward: 5000 },
    { rank: 2, reward: 2500 },
    { rank: 3, reward: 1500 },
    { rank: 4, reward: 500 },
    { rank: 5, reward: 500 },
  ],
});

const addPrizeRow = () => {
  const nextRank = form.value.prizes.length + 1;
  form.value.prizes.push({ rank: nextRank, reward: 500 });
};

const removePrizeRow = (index) => {
  form.value.prizes.splice(index, 1);
  form.value.prizes.forEach((p, idx) => {
    p.rank = idx + 1;
  });
};

const submitCreateContest = () => {
  submitting.value = true;
  router.post(`${adminPath.value}/referral-contests`, form.value, {
    onFinish: () => {
      submitting.value = false;
      showCreateModal.value = false;
    },
  });
};

const distributeRewards = () => {
  if (!confirm(`Are you sure you want to distribute rewards to top referrers for "${props.activeContest.title}"? This will credit their main balances immediately.`)) {
    return;
  }
  distributing.value = true;
  router.post(`${adminPath.value}/referral-contests/${props.activeContest.id}/distribute`, {}, {
    onFinish: () => {
      distributing.value = false;
    },
  });
};

const cancelContest = () => {
  if (!confirm(`Are you sure you want to cancel "${props.activeContest.title}"?`)) {
    return;
  }
  router.post(`${adminPath.value}/referral-contests/${props.activeContest.id}/cancel`);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString();
};
</script>
