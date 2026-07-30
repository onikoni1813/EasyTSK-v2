<template>
  <div v-if="isAdblockActive" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md">
    <div class="glass-card max-w-md w-full p-6 rounded-2xl border border-red-500/30 text-center shadow-2xl space-y-4">
      <div class="w-16 h-16 bg-red-500/20 text-red-400 rounded-full flex items-center justify-center mx-auto animate-pulse">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-white">AdBlocker / Brave Shield Detected</h2>
      <p class="text-sm text-slate-300">
        To continue using Easytsk V2 and complete microtasks, please disable your AdBlocker or Brave Shields for this domain and refresh the page.
      </p>
      <button @click="recheck" class="w-full py-3 px-4 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-500 hover:to-rose-500 text-white font-semibold rounded-xl shadow-lg transition">
        I Have Disabled It (Refresh)
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isAdblockActive = ref(false);

const detectAdblock = () => {
  const fakeAd = document.createElement('div');
  fakeAd.innerHTML = '&nbsp;';
  fakeAd.className = 'adsbygoogle ad-unit ad-zone ad-space google-ad';
  fakeAd.style.position = 'absolute';
  fakeAd.style.top = '-9999px';
  document.body.appendChild(fakeAd);

  window.setTimeout(() => {
    if (fakeAd.offsetHeight === 0 || fakeAd.clientHeight === 0 || window.getComputedStyle(fakeAd).display === 'none') {
      isAdblockActive.value = true;
    }
    fakeAd.remove();
  }, 100);
};

const recheck = () => {
  window.location.reload();
};

onMounted(() => {
  detectAdblock();
});
</script>
