<template>
  <Teleport to="body">
    <Transition name="pop">
      <div v-if="activeAchievement" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md transition-opacity" @click="dismiss"></div>

        <!-- Confetti Canvas -->
        <canvas ref="confettiCanvas" class="absolute inset-0 pointer-events-none z-10 w-full h-full"></canvas>

        <!-- Modal Box -->
        <div class="relative z-20 max-w-sm w-full bg-[#0a0e1c] border border-amber-500/40 rounded-3xl p-6 shadow-[0_0_50px_rgba(245,158,11,0.25)] text-center overflow-hidden animate-slide-in-up">
          
          <!-- Top Close Button -->
          <button 
            @click="dismiss" 
            class="absolute top-4 right-4 z-30 w-8 h-8 rounded-full bg-white/5 hover:bg-white/15 text-slate-400 hover:text-white flex items-center justify-center transition-all cursor-pointer"
            title="Close"
          >
            ✕
          </button>

          <!-- Radial background glow -->
          <div class="absolute -top-20 -left-20 w-48 h-48 bg-amber-500/20 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -bottom-20 -right-20 w-48 h-48 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

          <!-- Big Icon -->
          <div class="relative mx-auto mb-4 w-20 h-20 rounded-2xl bg-gradient-to-tr from-amber-500/20 via-purple-500/20 to-indigo-500/20 border border-amber-400/40 flex items-center justify-center text-4xl shadow-[0_0_20px_rgba(251,191,36,0.4)] animate-bounce">
            {{ icon }}
          </div>

          <!-- Tag -->
          <span class="inline-block px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-black uppercase tracking-widest mb-2 shadow-inner">
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
              class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-amber-500 via-purple-600 to-indigo-600 hover:from-amber-400 hover:to-indigo-500 text-white font-bold text-xs tracking-wider uppercase shadow-[0_0_20px_rgba(245,158,11,0.4)] hover:shadow-[0_0_30px_rgba(245,158,11,0.6)] transition-all transform active:scale-95 cursor-pointer"
            >
              Awesome! 🎉
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

const tagText = computed(() => {
  if (!activeAchievement.value) return 'Notification';
  if (activeAchievement.value.is_popup) return 'System Announcement';
  const title = activeAchievement.value.title;
  if (title.includes('Withdrawal Paid')) return 'Payout Successful';
  if (title.includes('Level Upgraded')) return 'Level Upgrade';
  if (title.includes('Referral')) return 'Referral Bonus';
  if (title.includes('Contest')) return 'Contest Champion';
  return 'Achievement Unlocked';
});

const icon = computed(() => {
  if (!activeAchievement.value) return '🏆';
  if (activeAchievement.value.is_popup) {
    const type = activeAchievement.value.type;
    if (type === 'danger') return '🚨';
    if (type === 'warning') return '⚠️';
    if (type === 'success') return '🎉';
    return '📢';
  }
  const title = activeAchievement.value.title;
  if (title.includes('Level')) return '⚡';
  if (title.includes('Referral')) return '🎁';
  if (title.includes('Contest') || title.includes('Champion')) return '🏆';
  if (title.includes('Withdrawal Paid') || title.includes('Paid')) return '💸';
  if (title.includes('Welcome')) return '🚀';
  return '🎉';
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
          n.title.includes('Welcome Bonus Unlocked')
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
