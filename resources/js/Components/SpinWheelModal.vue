<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
      <div class="glass-card rounded-3xl border border-violet-500/30 w-full max-w-sm p-6 text-center neon-glow-violet animate-slide-in-up relative overflow-hidden">
        <!-- Background glow -->
        <div class="absolute inset-0 bg-gradient-to-b from-violet-950/30 to-transparent pointer-events-none"></div>

        <div class="relative z-10">
          <!-- Header -->
          <div class="flex items-center justify-between mb-5">
            <div>
              <h2 class="text-lg font-black text-white">🎰 Spin the Wheel</h2>
              <p class="text-xs text-slate-400 mt-0.5">
                <span v-if="canSpin" class="text-amber-400 font-bold">🎉 You have a free spin!</span>
                <span v-else class="text-slate-500">Earn by completing 7-day streak</span>
              </p>
            </div>
            <button @click="$emit('close')" class="w-8 h-8 rounded-xl glass-pill flex items-center justify-center text-slate-400 hover:text-white transition-colors">
              ✕
            </button>
          </div>

          <!-- Wheel Canvas -->
          <div class="wheel-wrapper mx-auto mb-5" style="width: 260px; height: 260px;">
            <canvas ref="wheelCanvas" width="260" height="260" class="relative z-10 rounded-full"></canvas>
            <!-- Center pin -->
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-gradient-to-br from-white to-slate-300 border-4 border-slate-900 shadow-2xl z-20 flex items-center justify-center">
              <span class="text-slate-900 font-black text-xs">●</span>
            </div>
          </div>

          <!-- Pointer arrow (above wheel, pointing down) -->

          <!-- Prize Result -->
          <Transition name="prize-pop">
            <div v-if="prizeResult" class="mb-4 py-3.5 px-4 rounded-2xl border transition-all" 
                 :class="prizeResult.type === 'jackpot' 
                    ? 'bg-gradient-to-r from-amber-500/20 via-rose-500/20 to-amber-500/20 border-amber-400/60 shadow-[0_0_30px_rgba(245,158,11,0.4)]'
                    : prizeResult.value > 0 
                      ? 'bg-emerald-500/15 border-emerald-500/40' 
                      : 'bg-slate-800/60 border-slate-700/40'">
              <div v-if="prizeResult.type === 'jackpot'" class="text-[10px] uppercase font-black tracking-widest text-amber-300 mb-0.5 animate-pulse">
                💥 GRAND JACKPOT WINNER! 💥
              </div>
              <div class="text-2xl font-black" :class="prizeResult.type === 'jackpot' ? 'text-amber-300 drop-shadow-[0_0_10px_rgba(245,158,11,0.6)]' : prizeResult.value > 0 ? 'text-emerald-300 neon-text-emerald' : 'text-slate-400'">
                {{ prizeResult.label }}
              </div>
              <div v-if="prizeResult.value > 0" class="text-xs mt-0.5 font-semibold" :class="prizeResult.type === 'jackpot' ? 'text-amber-300 font-bold' : 'text-emerald-400'">
                +{{ prizeResult.value }} points added directly to your main balance!
              </div>
              <div v-else class="text-xs text-slate-500 mt-0.5">Better luck next time!</div>
            </div>
          </Transition>

          <!-- Spin Button -->
          <button
            @click="doSpin"
            :disabled="spinning || !canSpin || alreadySpun"
            class="btn-neon w-full py-3.5 rounded-2xl text-sm font-bold text-white transition-all"
            :class="canSpin && !spinning && !alreadySpun ? 'btn-amber neon-glow-amber' : 'bg-slate-800 text-slate-500 cursor-not-allowed'"
          >
            <span v-if="spinning">🌀 Spinning...</span>
            <span v-else-if="alreadySpun">✅ Spin Used!</span>
            <span v-else-if="canSpin">🎰 SPIN NOW!</span>
            <span v-else>No Spin Available</span>
          </button>

          <p class="text-[10px] text-slate-600 mt-3">Complete a 7-day daily streak to earn your next spin.</p>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const props = defineProps({
  canSpin: Boolean,
});
const emit = defineEmits(['close', 'spin-complete']);

const wheelCanvas = ref(null);
const spinning    = ref(false);
const alreadySpun = ref(false);
const prizeResult = ref(null);

const prizes      = ref([]);

const segments     = computed(() => prizes.value.length);
const arcSize      = computed(() => segments.value > 0 ? (2 * Math.PI) / segments.value : 0);
let   currentAngle = 0;
let   animFrame    = null;

const drawWheel = (angle) => {
  const canvas = wheelCanvas.value;
  if (!canvas) return;
  const ctx    = canvas.getContext('2d');
  const cx     = canvas.width / 2;
  const cy     = canvas.height / 2;
  const r      = cx - 8;

  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Outer ring glow
  ctx.beginPath();
  ctx.arc(cx, cy, r + 6, 0, 2 * Math.PI);
  ctx.strokeStyle = 'rgba(139, 92, 246, 0.4)';
  ctx.lineWidth   = 3;
  ctx.stroke();

  if (prizes.value.length === 0) return;

  prizes.value.forEach((prize, i) => {
    const startAngle = angle + i * arcSize.value;
    const endAngle   = startAngle + arcSize.value;

    // Segment fill
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.arc(cx, cy, r, startAngle, endAngle);
    ctx.closePath();
    ctx.fillStyle = prize.color;
    ctx.fill();

    // Segment border
    ctx.strokeStyle = 'rgba(255,255,255,0.08)';
    ctx.lineWidth   = 1.5;
    ctx.stroke();

    // Label
    ctx.save();
    ctx.translate(cx, cy);
    ctx.rotate(startAngle + arcSize.value / 2);
    ctx.textAlign    = 'right';
    ctx.font         = `bold ${prize.value >= 100 ? '11' : '12'}px Plus Jakarta Sans, sans-serif`;
    ctx.fillStyle    = prize.textColor;
    ctx.shadowColor  = prize.textColor;
    ctx.shadowBlur   = 6;
    ctx.fillText(prize.label, r - 10, 4);
    ctx.restore();
  });

  // Pointer triangle at top
  ctx.beginPath();
  ctx.moveTo(cx - 10, 4);
  ctx.lineTo(cx + 10, 4);
  ctx.lineTo(cx, 24);
  ctx.closePath();
  ctx.fillStyle   = '#f59e0b';
  ctx.shadowColor = '#f59e0b';
  ctx.shadowBlur  = 10;
  ctx.fill();
  ctx.shadowBlur = 0;
};

const doSpin = async () => {
  if (spinning.value || !props.canSpin || alreadySpun.value) return;
  spinning.value  = true;
  prizeResult.value = null;

  let response;
  try {
    response = await axios.post('/wheel/spin');
  } catch (e) {
    spinning.value = false;
    return;
  }

  const prize = response.data.prize;

  // Find the segment index that corresponds to the prize
  const prizeIdx = prizes.value.findIndex(p => p.label === prize.label);

  // Compute the exact angle at which this segment's center should sit under the pointer.
  // Pointer is at the top (12 o'clock) = -π/2 in canvas coords.
  // Add random drift of ±30% of one segment so it doesn't look robotic.
  const randomDrift  = (Math.random() * 0.6 - 0.3) * arcSize.value;
  // The wheel angle where segment[prizeIdx] center is exactly under the top pointer:
  const targetAngle  = -(prizeIdx * arcSize.value + arcSize.value / 2 + randomDrift) - (Math.PI / 2);

  // Normalise targetAngle into [0, 2π)
  const TAU          = Math.PI * 2;
  const normTarget   = ((targetAngle % TAU) + TAU) % TAU;
  // Normalise current startAngle into [0, 2π)
  const startAngle   = currentAngle;
  const normStart    = ((startAngle % TAU) + TAU) % TAU;
  // Delta needed to go from normStart to normTarget (always positive, going forward)
  let   delta        = (normTarget - normStart + TAU) % TAU;
  if (delta < 0.01) delta += TAU; // avoid stopping immediately if already there
  // Add 8 full rotations so it spins visually
  const totalSpins   = startAngle + (TAU * 8) + delta - normStart;
  const duration     = 4000; // 4 seconds
  const startTime    = performance.now();

  const animate = (now) => {
    const elapsed  = now - startTime;
    const progress = Math.min(elapsed / duration, 1);
    // Ease out cubic
    const ease     = 1 - Math.pow(1 - progress, 3);
    currentAngle   = startAngle + (totalSpins - startAngle) * ease;
    drawWheel(currentAngle);

    if (progress < 1) {
      animFrame = requestAnimationFrame(animate);
    } else {
      spinning.value    = false;
      alreadySpun.value = true;
      prizeResult.value = prize;
      emit('spin-complete', { prize, new_balance: response.data.new_balance });
    }
  };

  animFrame = requestAnimationFrame(animate);
};

onMounted(async () => {
  try {
    const res = await axios.get('/wheel/config');
    if (res.data && res.data.prizes) {
      prizes.value = res.data.prizes.map(p => ({
        label: p.label,
        value: p.value,
        type: p.type,
        color: p.color,
        textColor: p.text_color || '#fff'
      }));
      drawWheel(0);
    }
  } catch (e) {
    console.error('Failed to fetch wheel config:', e);
  }
});
</script>

<style scoped>
.prize-pop-enter-active { animation: prize-pop 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
@keyframes prize-pop {
  from { transform: scale(0.5); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}
</style>
