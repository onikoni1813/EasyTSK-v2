<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up">

      <!-- ── Header Banner ─────────────────────────────────────────────── -->
      <div class="glass-card p-6 rounded-3xl border border-violet-500/20 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-48 h-48 bg-violet-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-5">
          <div class="space-y-1.5">
            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-violet-500/15 border border-violet-500/30 text-violet-300 text-xs font-bold shadow-sm">
              <span>⚡</span>
              <span>Gamification & VIP Tiers</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
              Level Progression Roadmap
            </h1>
            <p class="text-xs sm:text-sm text-slate-400 max-w-xl leading-relaxed">
              Earn XP by completing microtasks, campaigns, and daily streaks. Unlock instant bonus points and prestigious status badges as you level up!
            </p>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex items-center gap-3 shrink-0">
            <Link
              href="/tasks"
              class="btn-neon btn-primary px-4 py-2.5 text-xs text-white rounded-2xl flex items-center gap-2 shadow-lg shadow-indigo-500/20 active:scale-95 transition-all"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              <span>Earn XP Now</span>
            </Link>
            <Link
              href="/dashboard"
              class="px-4 py-2.5 bg-slate-900/80 hover:bg-slate-800 border border-slate-700/60 text-slate-300 hover:text-white text-xs font-bold rounded-2xl transition-all"
            >
              ← Dashboard
            </Link>
          </div>
        </div>
      </div>

      <!-- ── Current Level Status (Hero Card) ─────────────────────────── -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/25 relative overflow-hidden bg-gradient-to-br from-slate-900/90 via-slate-950 to-indigo-950/40 shadow-2xl">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
          
          <!-- Current Level Avatar & Info (Left / 4 cols) -->
          <div class="lg:col-span-4 flex items-center gap-4 border-b lg:border-b-0 lg:border-r border-slate-800/80 pb-5 lg:pb-0 lg:pr-6">
            <!-- Glowing Shield Avatar -->
            <div class="relative shrink-0">
              <div class="w-20 h-20 rounded-3xl bg-gradient-to-br from-violet-600 via-indigo-600 to-purple-700 p-0.5 shadow-[0_0_25px_rgba(139,92,246,0.5)] flex items-center justify-center">
                <div class="w-full h-full bg-slate-950 rounded-[22px] flex flex-col items-center justify-center relative overflow-hidden">
                  <span class="text-xs font-black uppercase text-violet-400 tracking-wider">Level</span>
                  <span class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-white via-indigo-200 to-violet-300">
                    {{ user.level }}
                  </span>
                  <div class="absolute inset-0 bg-violet-500/10 pointer-events-none"></div>
                </div>
              </div>
              <div class="absolute -bottom-1.5 -right-1.5 bg-amber-400 text-slate-950 text-[10px] font-black px-1.5 py-0.5 rounded-full shadow-md flex items-center gap-0.5">
                <span>⚡</span>
              </div>
            </div>

            <!-- User Status Text -->
            <div class="min-w-0">
              <div class="text-xs text-slate-400 font-medium">Player Status</div>
              <h2 class="text-lg font-black text-white truncate">{{ user.name }}</h2>
              <div class="inline-flex items-center gap-1.5 mt-1 px-2 py-0.5 rounded-md bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-[11px] font-mono font-bold">
                <span>✨</span>
                <span>{{ Number(user.xp_points).toLocaleString() }} Total XP</span>
              </div>
            </div>
          </div>

          <!-- Progress Bar & Next Level Target (Right / 8 cols) -->
          <div class="lg:col-span-8 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div>
                <span class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                  <span>🎯</span>
                  <span>Next Milestone: <strong>Level {{ user.next_level_number }}</strong></span>
                </span>
                <p class="text-[11px] text-slate-400 mt-0.5">
                  <span v-if="user.remaining_xp > 0">
                    Need <strong class="text-amber-400 font-mono">{{ Number(user.remaining_xp).toLocaleString() }} XP</strong> more to advance.
                  </span>
                  <span v-else class="text-emerald-400 font-bold">
                    Max level target achieved! Keep earning XP.
                  </span>
                </p>
              </div>

              <!-- Next Level Reward Badge -->
              <div v-if="user.next_bonus_reward > 0" class="shrink-0 flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-300 text-xs font-bold shadow-inner">
                <span>🎁</span>
                <span>Reward: +{{ user.next_bonus_reward }} Pts Bonus</span>
              </div>
            </div>

            <!-- Dynamic Animated Progress Bar -->
            <div class="space-y-1.5">
              <div class="flex justify-between text-[11px] font-mono font-semibold">
                <span class="text-slate-400">{{ user.xp_points }} / {{ user.next_level_xp }} XP</span>
                <span class="text-indigo-400 font-bold">{{ user.progress_pct }}% Complete</span>
              </div>
              <div class="w-full bg-slate-950 rounded-full h-3.5 p-0.5 border border-slate-800 shadow-inner overflow-hidden">
                <div
                  class="h-full rounded-full bg-gradient-to-r from-indigo-500 via-purple-500 to-cyan-400 shadow-[0_0_12px_rgba(99,102,241,0.6)] transition-all duration-700 relative"
                  :style="{ width: user.progress_pct + '%' }"
                >
                  <div class="absolute inset-0 bg-white/20 animate-pulse-neon pointer-events-none"></div>
                </div>
              </div>
            </div>

          </div>

        </div>
      </div>

      <!-- ── Level Roadmap (All Tiers) ─────────────────────────────────── -->
      <div class="glass-card p-6 rounded-3xl border border-white/5 space-y-5">
        <div class="section-header">
          <div class="flex items-center gap-2">
            <span class="text-lg">🗺️</span>
            <span class="section-title">All Level Tiers & Rewards</span>
          </div>
          <div class="section-header-line"></div>
          <span class="badge badge-indigo shrink-0">{{ levels.length }} Tiers Configured</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="lvl in levels"
            :key="lvl.id"
            class="p-5 rounded-2xl border transition-all duration-300 relative overflow-hidden flex flex-col justify-between space-y-4"
            :class="lvl.is_current 
              ? 'bg-gradient-to-br from-indigo-950/60 via-slate-900 to-violet-950/60 border-indigo-500/60 shadow-[0_0_30px_rgba(99,102,241,0.25)] ring-1 ring-indigo-500/40' 
              : (lvl.is_unlocked 
                ? 'bg-slate-900/60 border-emerald-500/30 hover:border-emerald-500/50 hover:bg-slate-900/80' 
                : 'bg-slate-950/70 border-slate-800/80 opacity-75 hover:opacity-100 hover:border-slate-700')"
          >
            <!-- Top Row: Icon + Badge Status -->
            <div class="flex items-start justify-between gap-3">
              <div class="flex items-center gap-3">
                <div
                  class="w-12 h-12 rounded-2xl flex items-center justify-center text-lg font-black shrink-0 transition-transform shadow-inner"
                  :class="lvl.is_current 
                    ? 'bg-indigo-500/25 border border-indigo-500/50 text-indigo-300 shadow-[0_0_15px_rgba(99,102,241,0.4)] animate-float' 
                    : (lvl.is_unlocked 
                      ? 'bg-emerald-500/20 border border-emerald-500/40 text-emerald-300' 
                      : 'bg-slate-800/80 border border-slate-700/60 text-slate-400')"
                >
                  <span>{{ getLevelEmoji(lvl.level_number) }}</span>
                </div>
                <div>
                  <div class="text-xs font-semibold text-slate-400">Tier {{ lvl.level_number }}</div>
                  <h3 class="text-base font-black text-white flex items-center gap-1.5">
                    <span>Level {{ lvl.level_number }}</span>
                  </h3>
                </div>
              </div>

              <!-- Status Badge -->
              <span
                v-if="lvl.is_current"
                class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 shadow-sm flex items-center gap-1 animate-pulse"
              >
                <span>⚡</span> Current
              </span>
              <span
                v-else-if="lvl.is_unlocked"
                class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center gap-1"
              >
                <span>✓</span> Unlocked
              </span>
              <span
                v-else
                class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-800 text-slate-400 border border-slate-700 flex items-center gap-1"
              >
                <span>🔒</span> Locked
              </span>
            </div>

            <!-- Middle: Requirements & Perks -->
            <div class="space-y-2 pt-2 border-t border-slate-800/80">
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">XP Required:</span>
                <span class="font-mono font-bold text-white">{{ Number(lvl.xp_required).toLocaleString() }} XP</span>
              </div>
              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">Level-Up Bonus:</span>
                <span class="font-bold font-mono" :class="lvl.bonus_reward > 0 ? 'text-amber-400' : 'text-slate-500'">
                  {{ lvl.bonus_reward > 0 ? `+${lvl.bonus_reward} Pts` : 'Base Tier' }}
                </span>
              </div>
            </div>

            <!-- Bottom Progress / Status Indicator -->
            <div class="pt-2">
              <div v-if="lvl.is_current" class="text-[11px] text-indigo-300 font-bold flex items-center justify-between bg-indigo-500/10 p-2 rounded-xl border border-indigo-500/20">
                <span>Active Rank</span>
                <span>{{ user.progress_pct }}% to Lv {{ lvl.level_number + 1 }}</span>
              </div>
              <div v-else-if="lvl.is_unlocked" class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1.5 bg-emerald-500/10 p-2 rounded-xl border border-emerald-500/20">
                <span>✅</span>
                <span>Completed & Rewards Claimed</span>
              </div>
              <div v-else class="text-[11px] text-slate-400 flex items-center justify-between bg-slate-950 p-2 rounded-xl border border-slate-800">
                <span>Locked</span>
                <span class="text-amber-400 font-mono font-semibold">{{ Math.max(0, lvl.xp_required - user.xp_points) }} XP remaining</span>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- ── How to Earn XP Fast Guide ─────────────────────────────────── -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/15 space-y-4">
        <div class="section-header">
          <div class="flex items-center gap-2">
            <span class="text-lg">💡</span>
            <span class="section-title">How to Earn XP & Level Up Fast</span>
          </div>
          <div class="section-header-line"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
          <!-- Item 1 -->
          <Link href="/tasks" class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-indigo-500/40 hover:bg-slate-900/60 transition-all card-hover group">
            <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">⚡</div>
            <h4 class="text-xs font-bold text-white mb-1">Microtasks & Codes</h4>
            <p class="text-[11px] text-slate-400 leading-relaxed">
              Complete daily shortlinks, secret codes, and social tasks to earn guaranteed XP and point rewards.
            </p>
          </Link>

          <!-- Item 2 -->
          <Link href="/tasks" class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-violet-500/40 hover:bg-slate-900/60 transition-all card-hover group">
            <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📢</div>
            <h4 class="text-xs font-bold text-white mb-1">Community Campaigns</h4>
            <p class="text-[11px] text-slate-400 leading-relaxed">
              Join peer-submitted campaigns, complete verified proof tasks, and collect high XP boosts.
            </p>
          </Link>

          <!-- Item 3 -->
          <Link href="/dashboard" class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-amber-500/40 hover:bg-slate-900/60 transition-all card-hover group">
            <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🔥</div>
            <h4 class="text-xs font-bold text-white mb-1">Maintain Daily Streak</h4>
            <p class="text-[11px] text-slate-400 leading-relaxed">
              Keep your daily streak alive by finishing at least 1 task every 24 hours to earn streak bonuses.
            </p>
          </Link>

          <!-- Item 4 -->
          <Link href="/tasks#offerwall" class="p-4 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-cyan-500/40 hover:bg-slate-900/60 transition-all card-hover group">
            <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🏆</div>
            <h4 class="text-xs font-bold text-white mb-1">Offerwalls & Surveys</h4>
            <p class="text-[11px] text-slate-400 leading-relaxed">
              Complete premium partner offerwall apps, surveys, and games for massive point and XP windfalls.
            </p>
          </Link>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  user: Object,
  levels: Array,
});

const getLevelEmoji = (levelNumber) => {
  switch (levelNumber) {
    case 1:
      return '🌱';
    case 2:
      return '⚡';
    case 3:
      return '🔥';
    case 4:
      return '💎';
    case 5:
      return '👑';
    default:
      return '🚀';
  }
};
</script>
