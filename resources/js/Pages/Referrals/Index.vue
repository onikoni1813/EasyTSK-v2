<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up">
      <!-- Header -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/15 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-40 h-40 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-32 h-32 bg-violet-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10">
          <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
            <div>
              <h1 class="text-2xl font-black text-white flex items-center gap-2">
                <span>👥</span> My Referrals
              </h1>
              <p class="text-sm text-slate-400 mt-1">
                Track your invited friends and monitor your referral bonuses.
              </p>
            </div>
            
            <div class="flex flex-wrap gap-2 justify-start sm:justify-end">
              <div class="glass-pill px-4 py-2 rounded-xl border border-indigo-500/30 flex items-center gap-2">
                <span class="text-[11px] uppercase tracking-wider font-bold text-indigo-300">Invites:</span>
                <span class="text-base font-black text-white">{{ referrals.total }}</span>
              </div>
              <div class="glass-pill px-4 py-2 rounded-xl border border-violet-500/30 flex items-center gap-2">
                <span class="text-[11px] uppercase tracking-wider font-bold text-violet-300">Total Pts:</span>
                <span class="text-base font-black text-white">{{ stats.total_points || 0 }}</span>
              </div>
              <div class="glass-pill px-4 py-2 rounded-xl border border-amber-500/30 flex items-center gap-2">
                <span class="text-[11px] uppercase tracking-wider font-bold text-amber-300">Locked:</span>
                <span class="text-base font-black text-white">{{ stats.locked_points || 0 }}</span>
              </div>
              <div class="glass-pill px-4 py-2 rounded-xl border border-emerald-500/30 flex items-center gap-2">
                <span class="text-[11px] uppercase tracking-wider font-bold text-emerald-300">Unlocked:</span>
                <span class="text-base font-black text-white">{{ stats.unlocked_points || 0 }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Referral Contest Banner -->
      <div class="glass-card p-5 rounded-3xl border border-amber-500/30 bg-gradient-to-r from-amber-500/10 via-purple-500/10 to-indigo-500/10 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/40 flex items-center justify-center text-2xl shrink-0">
            🏆
          </div>
          <div>
            <div class="text-sm font-black text-white">Weekly Top Referrer Contest</div>
            <div class="text-xs text-slate-300">Invite active users & win extra bonus rewards on the leaderboard!</div>
          </div>
        </div>
        <Link
          href="/referral-contest"
          class="shrink-0 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-bold text-xs shadow-md transition-all flex items-center gap-2"
        >
          <span>🏆 View Leaderboard</span>
        </Link>
      </div>

      <!-- Share Link Card -->
      <div class="glass-card p-6 rounded-3xl border border-violet-500/15">
        <div class="text-sm font-bold text-white mb-3">Invite Friends & Earn More</div>
        
        <!-- Full Link Display + Copy Button -->
        <div class="glass-pill rounded-xl border border-indigo-500/25 p-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-2 mb-5">
          <div class="flex-1 min-w-0 px-3 py-2">
            <div class="text-[10px] text-slate-500 mb-0.5 font-semibold uppercase tracking-wider">Your Unique Link</div>
            <div class="text-xs font-mono text-indigo-300 truncate select-all">{{ referralUrl }}</div>
          </div>
          <button
            @click="copyReferral"
            class="shrink-0 px-6 py-3 rounded-lg text-xs font-bold transition-all duration-300 h-full"
            :class="copied
              ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
              : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 hover:bg-indigo-500/30'"
          >
            <span v-if="copied" class="flex items-center justify-center gap-1.5">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
              Copied!
            </span>
            <span v-else class="flex items-center justify-center gap-1.5">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              Copy Link
            </span>
          </button>
        </div>

        <!-- Social Share Buttons -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <!-- WhatsApp -->
          <a :href="shareWhatsApp" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 glass-pill py-3 rounded-xl border border-emerald-500/20 hover:border-emerald-500/50 hover:bg-emerald-500/10 transition-all">
            <span class="text-emerald-400 font-semibold text-xs">WhatsApp</span>
          </a>
          <!-- Telegram -->
          <a :href="shareTelegram" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 glass-pill py-3 rounded-xl border border-sky-500/20 hover:border-sky-500/50 hover:bg-sky-500/10 transition-all">
            <span class="text-sky-400 font-semibold text-xs">Telegram</span>
          </a>
          <!-- Facebook -->
          <a :href="shareFacebook" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 glass-pill py-3 rounded-xl border border-blue-500/20 hover:border-blue-500/50 hover:bg-blue-500/10 transition-all">
            <span class="text-blue-400 font-semibold text-xs">Facebook</span>
          </a>
          <!-- X (Twitter) -->
          <a :href="shareX" target="_blank" rel="noopener noreferrer" class="flex items-center justify-center gap-2 glass-pill py-3 rounded-xl border border-slate-500/20 hover:border-slate-400/50 hover:bg-slate-500/10 transition-all">
            <span class="text-slate-300 font-semibold text-xs">X (Twitter)</span>
          </a>
        </div>
      </div>

      <!-- Referrals Table/List -->
      <div class="glass-card p-6 rounded-3xl border border-indigo-500/15">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-bold text-white">History</h2>
        </div>

        <div v-if="!referrals.data || referrals.data.length === 0" class="text-center py-12 border-t border-white/5">
          <div class="text-4xl mb-3 opacity-50">📭</div>
          <p class="text-sm text-slate-400">You haven't referred anyone yet.</p>
          <p class="text-xs text-slate-500 mt-1">Use the links above to invite friends!</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="ref in referrals.data" :key="ref.id" class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-2xl bg-white/[0.02] border border-white/[0.05] hover:border-indigo-500/20 transition-all">
            <!-- User Info -->
            <div class="flex items-center gap-3 w-full sm:w-1/3 shrink-0">
              <div class="w-10 h-10 rounded-xl bg-indigo-500/20 flex items-center justify-center text-lg font-black text-indigo-400 shrink-0">
                {{ (ref.referred_user?.name || '?')[0].toUpperCase() }}
              </div>
              <div class="min-w-0">
                <div class="text-sm font-bold text-white truncate">{{ ref.referred_user?.name || 'User' }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">Joined {{ ref.joined_at }}</div>
              </div>
            </div>

            <!-- Progress -->
            <div class="w-full sm:flex-1">
              <div class="flex justify-between items-end mb-1.5">
                <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide">Tasks Completed</div>
                <div class="text-xs font-bold text-indigo-300">{{ ref.earned_so_far }} / {{ ref.target_amount }} pts</div>
              </div>
              <div class="progress-track h-2">
                <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-400"
                  :style="{ width: Math.min(100, (ref.earned_so_far / ref.target_amount) * 100) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Status / Reward -->
            <div class="flex items-center justify-between sm:justify-end gap-4 w-full sm:w-1/4 shrink-0 mt-2 sm:mt-0 pt-3 sm:pt-0 border-t sm:border-0 border-white/5">
              <span class="px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider"
                :class="{
                  'bg-emerald-500/20 text-emerald-400 border border-emerald-500/20': ref.status === 'unlocked' || ref.status === 'claimed',
                  'bg-amber-500/20 text-amber-400 border border-amber-500/20': ref.status === 'locked',
                }"
              >{{ ref.status === 'locked' ? '🔒 Locked' : ref.status === 'unlocked' ? '✅ Unlocked' : '💰 Claimed' }}</span>
              
              <div class="text-right">
                <div class="text-sm font-black text-violet-300">+{{ ref.locked_reward }}</div>
                <div class="text-[9px] text-slate-500 uppercase tracking-wider">Reward</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="referrals.links && referrals.links.length > 3" class="mt-8 flex flex-wrap justify-center gap-1.5">
          <Link
            v-for="(link, k) in referrals.links"
            :key="k"
            :href="link.url || '#'"
            class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all border"
            :class="[
              link.active 
                ? 'bg-indigo-500 text-white border-indigo-400 shadow-[0_0_10px_rgba(99,102,241,0.4)]' 
                : 'bg-white/5 text-slate-400 border-white/10 hover:bg-white/10 hover:text-white',
              !link.url ? 'opacity-50 cursor-not-allowed' : ''
            ]"
            v-html="link.label"
          />
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  referrals: Object,
  stats: Object,
  user: Object,
});

const copied = ref(false);

const referralUrl = computed(() =>
  `${window.location.origin}/register?ref=${props.user?.referral_code || ''}`
);

const shareText = computed(() =>
  `🚀 Join Easytsk V2 and start earning! Use my referral link to get bonus points: ${referralUrl.value}`
);

const shareWhatsApp = computed(() =>
  `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText.value)}`
);

const shareTelegram = computed(() =>
  `https://t.me/share/url?url=${encodeURIComponent(referralUrl.value)}&text=${encodeURIComponent('🚀 Join Easytsk V2 and earn rewards!')}`
);

const shareFacebook = computed(() =>
  `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralUrl.value)}`
);

const shareX = computed(() =>
  `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText.value)}`
);

const copyReferral = () => {
  navigator.clipboard.writeText(referralUrl.value).then(() => {
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2500);
  });
};
</script>
