<template>
  <Transition name="cookie-slide">
    <div
      v-if="!accepted"
      class="fixed bottom-0 left-0 right-0 z-[9999] p-4"
      role="dialog"
      aria-label="Cookie consent"
    >
      <div class="max-w-4xl mx-auto glass-card rounded-2xl border border-indigo-500/20 shadow-[0_0_40px_rgba(99,102,241,0.15)] overflow-hidden">
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 px-5 py-4">

          <!-- Icon -->
          <div class="shrink-0 w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/25 flex items-center justify-center">
            <span class="text-xl">🍪</span>
          </div>

          <!-- Text -->
          <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-slate-200 mb-0.5">We use cookies</p>
            <p class="text-xs text-slate-500 leading-relaxed">
              We use cookies and similar technologies to enhance your experience, show personalized content, and for analytics. By clicking "Accept All", you consent to our use of cookies.
              <a href="/cookie-policy" class="text-indigo-400 hover:text-indigo-300 underline underline-offset-2 ml-1 transition-colors">Learn more</a>
            </p>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto">
            <button
              @click="rejectNonEssential"
              class="flex-1 sm:flex-none px-4 py-2 text-xs font-semibold text-slate-400 border border-slate-600/50 rounded-xl hover:border-slate-500 hover:text-slate-300 transition-all duration-200"
            >
              Reject
            </button>
            <button
              @click="acceptAll"
              id="cookie-accept-btn"
              class="flex-1 sm:flex-none btn-neon btn-primary px-5 py-2 text-xs font-bold text-white rounded-xl"
            >
              Accept All
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const STORAGE_KEY = 'easytsk_cookie_consent';
const accepted = ref(true); // start hidden, reveal after mount check

onMounted(() => {
  const stored = localStorage.getItem(STORAGE_KEY);
  if (!stored) {
    // small delay so it slides in after page load
    setTimeout(() => { accepted.value = false; }, 800);
  }
});

function acceptAll() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ accepted: true, type: 'all', date: new Date().toISOString() }));
  accepted.value = true;
}

function rejectNonEssential() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ accepted: true, type: 'essential', date: new Date().toISOString() }));
  accepted.value = true;
}
</script>

<style scoped>
.cookie-slide-enter-active { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.4s ease; }
.cookie-slide-leave-active { transition: transform 0.3s ease, opacity 0.3s ease; }
.cookie-slide-enter-from  { transform: translateY(100%); opacity: 0; }
.cookie-slide-leave-to    { transform: translateY(100%); opacity: 0; }
</style>
