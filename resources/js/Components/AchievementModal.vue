<template>
  <Teleport to="body">
    <Transition name="pop">
      <div v-if="activeAchievement" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="dismiss"></div>

        <!-- Confetti Canvas -->
        <canvas ref="confettiCanvas" class="absolute inset-0 pointer-events-none z-10 w-full h-full"></canvas>

        <!-- Modal Box -->
        <div 
          class="relative z-20 max-w-sm w-full bg-[#0a0e1c] rounded-3xl p-6 text-center overflow-hidden animate-slide-in-up transition-all"
          :class="[
            isPromo 
              ? 'border border-violet-500/40 shadow-[0_0_50px_rgba(139,92,246,0.35)]' 
              : isWelcomeBonus
                ? 'border border-cyan-500/40 shadow-[0_0_50px_rgba(6,182,212,0.35)]'
                : isReferralBonus
                  ? 'border border-pink-500/40 shadow-[0_0_50px_rgba(236,72,153,0.35)]'
                  : isLevelUp
                    ? 'border border-amber-500/40 shadow-[0_0_50px_rgba(245,158,11,0.35)]'
                    : isContest
                      ? 'border border-yellow-500/40 shadow-[0_0_50px_rgba(234,179,8,0.35)]'
                      : isStreakOrSpin
                        ? 'border border-orange-500/40 shadow-[0_0_50px_rgba(249,115,22,0.35)]'
                        : isWithdrawalPending
                          ? 'border border-amber-500/40 shadow-[0_0_50px_rgba(245,158,11,0.25)]'
                          : isWithdrawalPaid
                            ? 'border border-emerald-500/40 shadow-[0_0_50px_rgba(16,185,129,0.35)]'
                            : 'border border-indigo-500/40 shadow-[0_0_50px_rgba(99,102,241,0.25)]'
          ]"
        >
          
          <!-- Top Close Button -->
          <button 
            @click="dismiss" 
            class="absolute top-4 right-4 z-30 w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
            title="Close"
          >
            ✕
          </button>

          <!-- Radial background glow -->
          <div 
            class="absolute -top-20 -left-20 w-48 h-48 rounded-full blur-3xl pointer-events-none"
            :class="[
              isPromo ? 'bg-violet-500/25' 
              : isWelcomeBonus ? 'bg-cyan-500/25' 
              : isReferralBonus ? 'bg-pink-500/25' 
              : isLevelUp ? 'bg-amber-500/25' 
              : isContest ? 'bg-yellow-500/25' 
              : isStreakOrSpin ? 'bg-orange-500/25' 
              : isWithdrawalPending ? 'bg-amber-500/20' 
              : isWithdrawalPaid ? 'bg-emerald-500/25' 
              : 'bg-indigo-500/20'
            ]"
          ></div>
          <div 
            class="absolute -bottom-20 -right-20 w-48 h-48 rounded-full blur-3xl pointer-events-none"
            :class="[
              isPromo ? 'bg-emerald-500/20' 
              : isWelcomeBonus ? 'bg-purple-500/20' 
              : isReferralBonus ? 'bg-violet-500/20' 
              : isLevelUp ? 'bg-yellow-500/20' 
              : isContest ? 'bg-amber-500/20' 
              : isStreakOrSpin ? 'bg-red-500/20' 
              : isWithdrawalPending ? 'bg-indigo-500/20' 
              : isWithdrawalPaid ? 'bg-teal-500/20' 
              : 'bg-indigo-500/20'
            ]"
          ></div>

          <!-- Big Icon -->
          <div 
            class="relative mx-auto mb-4 w-20 h-20 rounded-2xl flex items-center justify-center text-4xl animate-bounce"
            :class="[
              isPromo
                ? 'bg-gradient-to-tr from-violet-500/20 via-purple-500/20 to-emerald-500/20 border border-violet-400/40 shadow-[0_0_25px_rgba(139,92,246,0.5)]'
                : isWelcomeBonus
                  ? 'bg-gradient-to-tr from-cyan-500/20 via-indigo-500/20 to-purple-500/20 border border-cyan-400/40 shadow-[0_0_25px_rgba(6,182,212,0.5)]'
                  : isReferralBonus
                    ? 'bg-gradient-to-tr from-pink-500/20 via-purple-500/20 to-indigo-500/20 border border-pink-400/40 shadow-[0_0_25px_rgba(236,72,153,0.5)]'
                    : isLevelUp
                      ? 'bg-gradient-to-tr from-amber-500/20 via-yellow-500/20 to-orange-500/20 border border-amber-400/40 shadow-[0_0_25px_rgba(245,158,11,0.5)]'
                      : isContest
                        ? 'bg-gradient-to-tr from-yellow-500/20 via-amber-500/20 to-rose-500/20 border border-yellow-400/40 shadow-[0_0_25px_rgba(234,179,8,0.5)]'
                        : isStreakOrSpin
                          ? 'bg-gradient-to-tr from-orange-500/20 via-amber-500/20 to-red-500/20 border border-orange-400/40 shadow-[0_0_25px_rgba(249,115,22,0.5)]'
                          : isWithdrawalPending
                            ? 'bg-gradient-to-tr from-amber-500/20 via-orange-500/20 to-indigo-500/20 border border-amber-400/40 shadow-[0_0_20px_rgba(251,191,36,0.4)]'
                            : isWithdrawalPaid
                              ? 'bg-gradient-to-tr from-emerald-500/20 via-teal-500/20 to-cyan-500/20 border border-emerald-400/40 shadow-[0_0_25px_rgba(16,185,129,0.5)]'
                              : 'bg-gradient-to-tr from-indigo-500/20 via-purple-500/20 to-cyan-500/20 border border-indigo-400/40 shadow-[0_0_20px_rgba(99,102,241,0.4)]'
            ]"
          >
            {{ icon }}
          </div>

          <!-- Tag -->
          <span 
            class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest mb-2 shadow-inner"
            :class="[
              isPromo
                ? 'bg-violet-500/20 text-violet-300 border border-violet-500/30'
                : isWelcomeBonus
                  ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30'
                  : isReferralBonus
                    ? 'bg-pink-500/20 text-pink-300 border border-pink-500/30'
                    : isLevelUp
                      ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                      : isContest
                        ? 'bg-yellow-500/20 text-yellow-300 border border-yellow-500/30'
                        : isStreakOrSpin
                          ? 'bg-orange-500/20 text-orange-300 border border-orange-500/30'
                          : isWithdrawalPending
                            ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                            : isWithdrawalPaid
                              ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                              : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30'
            ]"
          >
            {{ tagText }}
          </span>

          <!-- Title -->
          <h2 class="text-xl font-black text-white tracking-tight leading-snug drop-shadow-md">
            {{ activeAchievement.title }}
          </h2>

          <!-- Message -->
          <p class="text-xs text-slate-300 mt-2 leading-relaxed font-medium">
            {{ activeAchievement.message }}
          </p>

          <!-- Button -->
          <div class="mt-6 flex flex-col gap-2">
            <button
              @click="dismiss"
              class="w-full py-3 px-4 rounded-xl text-white font-bold text-xs tracking-wider uppercase transition-all transform active:scale-95 cursor-pointer"
              :class="[
                isPromo
                  ? 'bg-gradient-to-r from-violet-600 via-indigo-600 to-emerald-600 hover:from-violet-500 hover:to-emerald-500 shadow-[0_0_20px_rgba(139,92,246,0.4)] hover:shadow-[0_0_30px_rgba(139,92,246,0.6)]'
                  : isWelcomeBonus
                    ? 'bg-gradient-to-r from-cyan-500 via-indigo-600 to-purple-600 hover:from-cyan-400 hover:to-purple-500 shadow-[0_0_20px_rgba(6,182,212,0.4)] hover:shadow-[0_0_30px_rgba(6,182,212,0.6)]'
                    : isReferralBonus
                      ? 'bg-gradient-to-r from-pink-600 via-purple-600 to-indigo-600 hover:from-pink-500 hover:to-indigo-500 shadow-[0_0_20px_rgba(236,72,153,0.4)] hover:shadow-[0_0_30px_rgba(236,72,153,0.6)]'
                      : isLevelUp
                        ? 'bg-gradient-to-r from-amber-500 via-yellow-600 to-orange-600 hover:from-amber-400 hover:to-yellow-500 shadow-[0_0_20px_rgba(245,158,11,0.4)] hover:shadow-[0_0_30px_rgba(245,158,11,0.6)]'
                        : isContest
                          ? 'bg-gradient-to-r from-yellow-500 via-amber-600 to-rose-600 hover:from-yellow-400 hover:to-rose-500 shadow-[0_0_20px_rgba(234,179,8,0.4)] hover:shadow-[0_0_30px_rgba(234,179,8,0.6)]'
                          : isStreakOrSpin
                            ? 'bg-gradient-to-r from-orange-500 via-amber-600 to-red-600 hover:from-orange-400 hover:to-red-500 shadow-[0_0_20px_rgba(249,115,22,0.4)] hover:shadow-[0_0_30px_rgba(249,115,22,0.6)]'
                            : isWithdrawalPending
                              ? 'bg-gradient-to-r from-amber-500 via-orange-600 to-indigo-600 hover:from-amber-400 hover:to-indigo-500 shadow-[0_0_20px_rgba(245,158,11,0.4)] hover:shadow-[0_0_30px_rgba(245,158,11,0.6)]'
                              : isWithdrawalPaid
                                ? 'bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 shadow-[0_0_20px_rgba(16,185,129,0.4)] hover:shadow-[0_0_30px_rgba(16,185,129,0.6)]'
                                : 'bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 hover:from-indigo-500 hover:to-pink-500 shadow-[0_0_20px_rgba(99,102,241,0.4)] hover:shadow-[0_0_30px_rgba(99,102,241,0.6)]'
              ]"
            >
              {{ buttonText }}
            </button>
          </div>
        </div>

      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  notifications: {
    type: Array,
    default: () => []
  }
});

const confettiCanvas = ref(null);
const activeAchievement = ref(null);
const dismissedIds = ref(new Set());

const isPromo = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Promo Code') || title.includes('Promo');
});

const isWelcomeBonus = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Welcome Bonus') || title.includes('Welcome');
});

const isReferralBonus = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Referral Bonus') || title.includes('Referral');
});

const isLevelUp = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Level Upgraded') || title.includes('Level');
});

const isContest = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Contest Champion') || title.includes('Contest');
});

const isStreakOrSpin = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Streak') || title.includes('Spin Unlocked');
});

const isWithdrawalPending = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Withdrawal Request') || title.includes('Withdrawal Submitted');
});

const isWithdrawalPaid = computed(() => {
  if (!activeAchievement.value) return false;
  const title = activeAchievement.value.title;
  return title.includes('Withdrawal Paid') || title.includes('Payout Successful');
});

const tagText = computed(() => {
  if (!activeAchievement.value) return 'Notification';
  const title = activeAchievement.value.title;
  if (title.includes('Promo Code') || title.includes('Promo')) return 'Promo Code Reward 🎟️';
  if (title.includes('Welcome Bonus')) return 'Welcome Bonus 🚀';
  if (title.includes('Referral Bonus') || title.includes('Referral')) return 'Referral Bonus Unlocked 🎁';
  if (title.includes('Level Upgraded') || title.includes('Level')) return 'Level Upgraded ⚡';
  if (title.includes('Contest')) return 'Contest Champion 🏆';
  if (title.includes('Spin') || title.includes('Streak')) return 'Daily Milestone 🔥';
  if (title.includes('Withdrawal Paid') || title.includes('Paid')) return 'Payout Successful 💸';
  if (title.includes('Withdrawal Request') || title.includes('Withdrawal Submitted') || title.includes('Withdrawal')) return 'Payout Request Submitted ⏳';
  if (activeAchievement.value.is_popup) return 'Special Notification';
  return 'Achievement Unlocked';
});

const icon = computed(() => {
  if (!activeAchievement.value) return '🏆';
  const title = activeAchievement.value.title;
  if (title.includes('Promo Code') || title.includes('Promo')) return '🎁';
  if (title.includes('Welcome Bonus') || title.includes('Welcome')) return '🚀';
  if (title.includes('Referral Bonus') || title.includes('Referral')) return '🎁';
  if (title.includes('Level')) return '⚡';
  if (title.includes('Contest') || title.includes('Champion')) return '🏆';
  if (title.includes('Spin')) return '🎰';
  if (title.includes('Streak')) return '🔥';
  if (title.includes('Withdrawal Request') || title.includes('Withdrawal Submitted')) return '⏳';
  if (title.includes('Withdrawal Paid') || title.includes('Paid')) return '💸';
  if (activeAchievement.value.is_popup) {
    const type = activeAchievement.value.type;
    if (type === 'danger') return '🚨';
    if (type === 'warning') return '⚠️';
    if (type === 'success') return '🎉';
    return '📢';
  }
  return '🎉';
});

const buttonText = computed(() => {
  if (isPromo.value) return 'Claim Reward 🎉';
  if (isWelcomeBonus.value) return 'Awesome! 🚀';
  if (isReferralBonus.value) return 'Claim Referral Bonus 🎁';
  if (isLevelUp.value) return 'View Level Perks ⚡';
  if (isContest.value) return 'Awesome! 🏆';
  if (isStreakOrSpin.value) return 'Spin & Win 🎰';
  if (isWithdrawalPending.value) return 'Got It! ⏳';
  if (isWithdrawalPaid.value) return 'Awesome! 💸';
  return 'Awesome! 🎉';
});

const isRecent = (dateStr) => {
  if (!dateStr) return false;
  const created = new Date(dateStr).getTime();
  const now = Date.now();
  return (now - created) < (24 * 60 * 60 * 1000); // Only pop up if less than 24 hours old
};

const checkForAchievement = () => {
  if (!props.notifications || props.notifications.length === 0) {
    activeAchievement.value = null;
    return;
  }

  const achievement = props.notifications.find(n => 
    !n.read_at && 
    !dismissedIds.value.has(n.id) &&
    (
      n.is_popup ||
      (
        isRecent(n.created_at) &&
        (
          n.title.includes('Level Upgraded') || 
          n.title.includes('Referral Bonus Unlocked') || 
          n.title.includes('Contest Champion') || 
          n.title.includes('Withdrawal Paid') ||
          n.title.includes('Withdrawal Request') ||
          n.title.includes('Withdrawal Submitted') ||
          n.title.includes('Withdrawal') ||
          n.title.includes('Welcome Bonus Unlocked') ||
          n.title.includes('Promo Code Redeemed') ||
          n.title.includes('Promo Code')
        )
      )
    )
  );

  if (achievement) {
    activeAchievement.value = achievement;
    setTimeout(() => {
      startConfetti();
    }, 100);
  } else {
    activeAchievement.value = null;
  }
};

watch(() => props.notifications, () => {
  checkForAchievement();
}, { immediate: true, deep: true });

onMounted(() => {
  checkForAchievement();
});

const dismiss = () => {
  if (activeAchievement.value) {
    const item = activeAchievement.value;
    dismissedIds.value.add(item.id);
    activeAchievement.value = null;

    router.post(`/api/notifications/${item.id}/read`, {}, {
      preserveScroll: true,
      onFinish: () => {
        // State successfully updated
      }
    });
  }
};

// Canvas confetti particle animation
const startConfetti = () => {
  const canvas = confettiCanvas.value;
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  canvas.width = window.innerWidth;
  canvas.height = window.innerHeight;

  const particles = [];
  const colors = ['#f59e0b', '#ec4899', '#8b5cf6', '#3b82f6', '#10b981', '#fbbf24'];

  for (let i = 0; i < 90; i++) {
    particles.push({
      x: canvas.width / 2,
      y: canvas.height / 2 - 50,
      vx: (Math.random() - 0.5) * 14,
      vy: (Math.random() - 0.7) * 16,
      size: Math.random() * 8 + 4,
      color: colors[Math.floor(Math.random() * colors.length)],
      rotation: Math.random() * 360,
      rSpeed: (Math.random() - 0.5) * 10,
      opacity: 1
    });
  }

  let animationFrame;
  const render = () => {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    let stillAlive = false;

    particles.forEach(p => {
      p.x += p.vx;
      p.y += p.vy;
      p.vy += 0.35; // gravity
      p.rotation += p.rSpeed;
      p.opacity -= 0.008;

      if (p.opacity > 0) {
        stillAlive = true;
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate((p.rotation * Math.PI) / 180);
        ctx.globalAlpha = Math.max(0, p.opacity);
        ctx.fillStyle = p.color;
        ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size);
        ctx.restore();
      }
    });

    if (stillAlive && activeAchievement.value) {
      animationFrame = requestAnimationFrame(render);
    }
  };

  render();
};
</script>

<style scoped>
.pop-enter-active, .pop-leave-active {
  transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.pop-enter-from, .pop-leave-to {
  opacity: 0;
  transform: scale(0.85);
}
</style>
