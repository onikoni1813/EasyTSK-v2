<template>
  <span class="stat-number" :class="{ 'animate-counter': bump }">{{ display }}{{ suffix }}</span>
</template>

<script setup>
/**
 * Animates a numeric value with a smooth count-up/count-down tween whenever `value` changes,
 * and re-triggers the `.animate-counter` pop-in keyframe (defined in app.css) on every change.
 * Satisfies the "micro-interactions for balance updates" requirement from Global Project Rules.
 */
import { ref, watch, onBeforeUnmount } from 'vue';

const props = defineProps({
  value: { type: [Number, String], default: 0 },
  decimals: { type: Number, default: 0 },
  duration: { type: Number, default: 700 },
  suffix: { type: String, default: '' },
});

const toNumber = (v) => Number(v || 0);

const formatNumber = (n) =>
  n.toLocaleString('en-US', {
    minimumFractionDigits: props.decimals,
    maximumFractionDigits: props.decimals,
  });

const display = ref(formatNumber(toNumber(props.value)));
const bump = ref(false);

let rafId = null;
let bumpTimeoutId = null;
let currentValue = toNumber(props.value);

const triggerBump = () => {
  bump.value = false;
  requestAnimationFrame(() => {
    bump.value = true;
    clearTimeout(bumpTimeoutId);
    bumpTimeoutId = setTimeout(() => { bump.value = false; }, 400);
  });
};

const animateTo = (from, to) => {
  if (rafId) cancelAnimationFrame(rafId);
  if (from === to) {
    display.value = formatNumber(to);
    return;
  }

  const start = performance.now();

  const step = (now) => {
    const elapsed = now - start;
    const progress = Math.min(elapsed / props.duration, 1);
    const eased = 1 - Math.pow(1 - progress, 3); // ease-out cubic
    const current = from + (to - from) * eased;
    display.value = formatNumber(current);

    if (progress < 1) {
      rafId = requestAnimationFrame(step);
    } else {
      display.value = formatNumber(to);
      rafId = null;
    }
  };

  rafId = requestAnimationFrame(step);
};

watch(
  () => props.value,
  (newVal) => {
    const to = toNumber(newVal);
    const from = currentValue;
    currentValue = to;
    if (to === from) return;
    animateTo(from, to);
    triggerBump();
  }
);

onBeforeUnmount(() => {
  if (rafId) cancelAnimationFrame(rafId);
  clearTimeout(bumpTimeoutId);
});
</script>
