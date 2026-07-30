<template>
  <span
    ref="counterRef"
    class="inline-block transition-transform duration-300 ease-out"
    :class="[
      isAnimating ? 'scale-105' : 'scale-100'
    ]"
  >
    {{ prefix }}{{ displayed }}{{ suffix }}
  </span>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';

const props = defineProps({
  target:   { type: Number, default: 0 },
  duration: { type: Number, default: 2000 },
  prefix:   { type: String, default: '' },
  suffix:   { type: String, default: '' },
});

const counterRef = ref(null);
const displayed = ref('0');
const isAnimating = ref(false);
let hasAnimated = false;
let observer = null;

function animateTo(targetVal) {
  const numTarget = Number(targetVal) || 0;
  if (numTarget <= 0) {
    displayed.value = '0';
    return;
  }

  isAnimating.value = true;
  const start = performance.now();
  const startVal = 0;
  const delta = numTarget;

  function step(now) {
    const elapsed = now - start;
    const progress = Math.min(elapsed / props.duration, 1);
    
    // Ease Out Exponential curve for smooth deceleration
    const ease = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);
    const current = Math.round(startVal + delta * ease);

    displayed.value = current >= 1000 ? current.toLocaleString() : current.toString();

    if (progress < 1) {
      requestAnimationFrame(step);
    } else {
      displayed.value = numTarget >= 1000 ? numTarget.toLocaleString() : numTarget.toString();
      setTimeout(() => {
        isAnimating.value = false;
      }, 300);
    }
  }
  requestAnimationFrame(step);
}

function startObserver() {
  if ('IntersectionObserver' in window && counterRef.value) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting && !hasAnimated) {
            hasAnimated = true;
            animateTo(props.target);
          }
        });
      },
      { threshold: 0.05, rootMargin: '50px' }
    );
    observer.observe(counterRef.value);
  } else {
    hasAnimated = true;
    animateTo(props.target);
  }
}

onMounted(() => {
  startObserver();
});

onUnmounted(() => {
  if (observer && counterRef.value) {
    observer.unobserve(counterRef.value);
  }
});

watch(
  () => props.target,
  (newVal) => {
    if (newVal > 0) {
      hasAnimated = true;
      animateTo(newVal);
    }
  }
);
</script>


