<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up">
      <!-- Header Banner -->
      <div class="glass-card p-6 sm:p-8 rounded-3xl border border-amber-500/20 relative overflow-hidden bg-gradient-to-r from-amber-500/10 via-indigo-500/10 to-purple-500/10">
        <div class="absolute right-0 top-0 w-64 h-64 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-purple-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/30 mb-3">
              <span>🏆</span> {{ contestStatusBadge }}
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">
              {{ activeContest ? activeContest.title : 'Refer & Win Leaderboard' }}
            </h1>
            <p class="text-sm text-slate-300 mt-1 max-w-xl">
              Only verified <span class="text-emerald-400 font-bold">Unlocked Referrals</span> qualify! Invite active users, climb the rankings, and win bonus rewards credited directly to your main balance.
            </p>
          </div>

          <!-- Countdown Timer -->
          <div v-if="activeContest" class="glass-card p-4 rounded-2xl border border-amber-500/30 text-center w-full md:w-auto shrink-0 bg-slate-900/60 backdrop-blur-md">
            <div class="text-[11px] uppercase tracking-wider font-bold text-amber-400 mb-1">
              {{ countdownTitle }}
            </div>
            <div v-if="!isEnded" class="flex items-center justify-center gap-2 text-white font-mono font-black text-xl sm:text-2xl">
              <div class="bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/30">
                <span>{{ countdown.days }}</span>
                <span class="text-[10px] block font-sans text-amber-300 font-normal uppercase">Days</span>
              </div>
              <span>:</span>
              <div class="bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/30">
                <span>{{ countdown.hours }}</span>
                <span class="text-[10px] block font-sans text-amber-300 font-normal uppercase">Hrs</span>
              </div>
              <span>:</span>
              <div class="bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/30">
                <span>{{ countdown.minutes }}</span>
                <span class="text-[10px] block font-sans text-amber-300 font-normal uppercase">Min</span>
              </div>
              <span>:</span>
              <div class="bg-amber-500/20 px-2.5 py-1 rounded-lg border border-amber-500/30">
                <span>{{ countdown.seconds }}</span>
                <span class="text-[10px] block font-sans text-amber-300 font-normal uppercase">Sec</span>
              </div>
            </div>
            <div v-else class="text-emerald-400 font-bold text-sm py-2">
              ⚡ Calculating Final Results & Rewards...
            </div>
          </div>

          <div v-else class="glass-card p-4 rounded-2xl border border-slate-700 text-center">
            <div class="text-sm text-slate-400 font-bold">No Active Contest</div>
            <div class="text-xs text-slate-500 mt-0.5">Check back soon for the next round!</div>
          </div>
        </div>
      </div>

      <!-- User Position Card -->
      <div v-if="activeContest && currentUserRank" class="glass-card p-6 rounded-3xl border border-indigo-500/20 bg-indigo-950/30">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center text-white text-2xl font-black shadow-lg shadow-indigo-500/20 shrink-0">
              <span v-if="currentUserRank.rank === 1">🥇</span>
              <span v-else-if="currentUserRank.rank === 2">🥈</span>
              <span v-else-if="currentUserRank.rank === 3">🥉</span>
              <span v-else-if="currentUserRank.rank">#{{ currentUserRank.rank }}</span>
              <span v-else>🎗️</span>
            </div>
            <div>
              <div class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Your Position</div>
              <div class="text-xl font-black text-white flex items-center gap-2">
                <span>{{ currentUserRank.rank ? `Rank #${currentUserRank.rank}` : 'Unranked' }}</span>
                <span v-if="currentUserRank.estimated_prize > 0" class="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 font-bold">
                  Est. Prize: {{ currentUserRank.estimated_prize }} Coins
                </span>
              </div>
              <div class="text-xs text-slate-300 mt-1">
                You have <span class="text-emerald-400 font-bold">{{ currentUserRank.unlocked_count }}</span> unlocked referrals in this contest.
              </div>
            </div>
          </div>

          <Link
            href="/referrals"
            class="px-5 py-3 rounded-xl bg-gradient-to-r from-indigo-500 to-violet-600 hover:from-indigo-600 hover:to-violet-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2"
          >
            <span>🚀 Share Referral Link</span>
          </Link>
        </div>
      </div>

      <!-- Top 3 Winners Podium Cards -->
      <div v-if="topThree.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- 1st Place Card -->
        <div class="glass-card p-6 rounded-3xl border border-amber-500/40 bg-gradient-to-b from-amber-500/10 to-transparent text-center relative overflow-hidden md:order-2">
          <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-32 h-32 bg-amber-500/20 rounded-full blur-2xl pointer-events-none"></div>
          <div class="text-4xl mb-2">🥇</div>
          <div class="text-xs uppercase font-black text-amber-400 tracking-wider">1st Place Champion</div>
          <div class="text-lg font-black text-white mt-1 truncate">{{ topThree[0].name }}</div>
          <div class="mt-2 text-2xl font-black text-amber-300 font-mono">{{ topThree[0].unlocked_count }} <span class="text-xs font-sans text-amber-200">Unlocked</span></div>
          <div class="mt-2 inline-block px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/30">
            Prize: {{ topThree[0].estimated_prize }} Coins
          </div>
        </div>

        <!-- 2nd Place Card -->
        <div v-if="topThree.length > 1" class="glass-card p-6 rounded-3xl border border-slate-300/30 bg-gradient-to-b from-slate-400/10 to-transparent text-center relative overflow-hidden md:order-1">
          <div class="text-4xl mb-2">🥈</div>
          <div class="text-xs uppercase font-black text-slate-300 tracking-wider">2nd Place</div>
          <div class="text-lg font-black text-white mt-1 truncate">{{ topThree[1].name }}</div>
          <div class="mt-2 text-2xl font-black text-slate-200 font-mono">{{ topThree[1].unlocked_count }} <span class="text-xs font-sans text-slate-400">Unlocked</span></div>
          <div class="mt-2 inline-block px-3 py-1 rounded-full bg-slate-400/20 text-slate-200 text-xs font-bold border border-slate-400/30">
            Prize: {{ topThree[1].estimated_prize }} Coins
          </div>
        </div>

        <!-- 3rd Place Card -->
        <div v-if="topThree.length > 2" class="glass-card p-6 rounded-3xl border border-amber-700/40 bg-gradient-to-b from-amber-700/10 to-transparent text-center relative overflow-hidden md:order-3">
          <div class="text-4xl mb-2">🥉</div>
          <div class="text-xs uppercase font-black text-amber-600 tracking-wider">3rd Place</div>
          <div class="text-lg font-black text-white mt-1 truncate">{{ topThree[2].name }}</div>
          <div class="mt-2 text-2xl font-black text-amber-400 font-mono">{{ topThree[2].unlocked_count }} <span class="text-xs font-sans text-amber-500">Unlocked</span></div>
          <div class="mt-2 inline-block px-3 py-1 rounded-full bg-amber-700/20 text-amber-400 text-xs font-bold border border-amber-700/30">
            Prize: {{ topThree[2].estimated_prize }} Coins
          </div>
        </div>
      </div>

      <!-- Leaderboard Table -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/15">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <span>📊</span> Contest Leaderboard
          </h2>
          <span class="text-xs text-slate-400" v-if="activeContest">Min Unlocked: {{ activeContest.min_unlocked_required }}</span>
        </div>

        <div v-if="leaderboard.length === 0" class="text-center py-12 border-t border-white/5">
          <div class="text-4xl mb-3 opacity-50">🏆</div>
          <p class="text-sm text-slate-400">No participants qualified yet.</p>
          <p class="text-xs text-slate-500 mt-1">Be the first to unlock referrals and grab rank #1!</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="text-[11px] uppercase tracking-wider font-bold text-slate-400 border-b border-white/10">
                <th class="py-3 px-4">Rank</th>
                <th class="py-3 px-4">User</th>
                <th class="py-3 px-4 text-center">Unlocked Referrals</th>
                <th class="py-3 px-4 text-right">Est. Reward</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-sm">
              <tr
                v-for="item in leaderboard"
                :key="item.user_id"
                class="hover:bg-white/5 transition-colors"
                :class="{ 'bg-indigo-500/10 font-bold border-l-4 border-indigo-500': currentUserRank && currentUserRank.rank === item.rank }"
              >
                <td class="py-3.5 px-4 font-mono font-bold text-white">
                  <span v-if="item.rank === 1" class="text-lg">🥇</span>
                  <span v-else-if="item.rank === 2" class="text-lg">🥈</span>
                  <span v-else-if="item.rank === 3" class="text-lg">🥉</span>
                  <span v-else class="text-slate-400">#{{ item.rank }}</span>
                </td>
                <td class="py-3.5 px-4 text-white font-medium flex items-center gap-2">
                  <span>{{ item.name }}</span>
                  <span v-if="currentUserRank && currentUserRank.rank === item.rank" class="text-[10px] px-2 py-0.5 rounded bg-indigo-500/30 text-indigo-300 font-bold uppercase">
                    You
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center font-mono font-bold text-emerald-400">
                  {{ item.unlocked_count }}
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-bold text-amber-300">
                  {{ item.estimated_prize > 0 ? `${item.estimated_prize} Coins` : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Past Winners (Hall of Fame) -->
      <div v-if="pastWinners.length > 0" class="glass-card p-6 rounded-3xl border border-indigo-500/15">
        <h2 class="text-lg font-bold text-white flex items-center gap-2 mb-4">
          <span>🎖️</span> Hall of Fame (Past Winners)
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
          <div
            v-for="winner in pastWinners"
            :key="winner.id"
            class="glass-pill p-3.5 rounded-2xl border border-white/10 flex items-center justify-between"
          >
            <div>
              <div class="text-xs font-bold text-white flex items-center gap-1.5">
                <span v-if="winner.rank === 1">🥇</span>
                <span v-else-if="winner.rank === 2">🥈</span>
                <span v-else-if="winner.rank === 3">🥉</span>
                <span v-else class="text-slate-400 font-mono text-[11px]">#{{ winner.rank }}</span>
                <span>{{ winner.user?.name || 'User' }}</span>
              </div>
              <div class="text-[10px] text-slate-400 mt-0.5">
                {{ winner.contest?.title || 'Contest' }}
              </div>
            </div>
            <div class="text-right font-mono">
              <div class="text-xs font-bold text-amber-300">+{{ winner.reward_amount }} Coins</div>
              <div class="text-[10px] text-emerald-400 font-semibold">{{ winner.unlocked_count }} Unlocked</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  activeContest: Object,
  leaderboard: Array,
  currentUserRank: Object,
  pastWinners: Array,
});

const topThree = computed(() => {
  return (props.leaderboard || []).slice(0, 3);
});

const isUpcoming = computed(() => {
  if (!props.activeContest || !props.activeContest.start_date) return false;
  const isoStart = typeof props.activeContest.start_date === 'string'
    ? props.activeContest.start_date.replace(' ', 'T')
    : props.activeContest.start_date;
  return new Date(isoStart).getTime() > new Date().getTime();
});

const isEnded = computed(() => {
  if (!props.activeContest || !props.activeContest.end_date) return false;
  const isoEnd = typeof props.activeContest.end_date === 'string'
    ? props.activeContest.end_date.replace(' ', 'T')
    : props.activeContest.end_date;
  return new Date(isoEnd).getTime() <= new Date().getTime();
});

const contestStatusBadge = computed(() => {
  if (isUpcoming.value) return 'Upcoming Referral Contest';
  if (isEnded.value) return 'Contest Ended (Payout Pending)';
  return 'Active Top Referrer Contest';
});

const countdownTitle = computed(() => {
  if (isUpcoming.value) return 'Contest Starts In';
  if (isEnded.value) return 'Contest Ended';
  return 'Contest Ends In';
});

// Countdown Timer logic
const countdown = ref({ days: '00', hours: '00', minutes: '00', seconds: '00' });
let timerInterval = null;

const updateCountdown = () => {
  if (!props.activeContest) {
    countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' };
    return;
  }

  const rawTargetDate = isUpcoming.value ? props.activeContest.start_date : props.activeContest.end_date;
  if (!rawTargetDate) {
    countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' };
    return;
  }

  const isoStr = typeof rawTargetDate === 'string' ? rawTargetDate.replace(' ', 'T') : rawTargetDate;
  const targetTime = new Date(isoStr).getTime();
  const now = new Date().getTime();
  const diff = targetTime - now;

  if (diff <= 0) {
    countdown.value = { days: '00', hours: '00', minutes: '00', seconds: '00' };
    return;
  }

  const d = Math.floor(diff / (1000 * 60 * 60 * 24));
  const h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
  const m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
  const s = Math.floor((diff % (1000 * 60)) / 1000);

  countdown.value = {
    days: d < 10 ? `0${d}` : `${d}`,
    hours: h < 10 ? `0${h}` : `${h}`,
    minutes: m < 10 ? `0${m}` : `${m}`,
    seconds: s < 10 ? `0${s}` : `${s}`,
  };
};

onMounted(() => {
  updateCountdown();
  timerInterval = setInterval(updateCountdown, 1000);
});

onUnmounted(() => {
  if (timerInterval) {
    clearInterval(timerInterval);
  }
});
</script>
