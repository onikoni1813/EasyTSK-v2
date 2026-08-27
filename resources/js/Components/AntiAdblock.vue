<template>
  <div v-if="isAdblockActive" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md animate-fade-in">
    <div class="glass-card max-w-md w-full p-6 sm:p-8 rounded-3xl border border-red-500/40 text-center shadow-[0_0_60px_rgba(239,68,68,0.3)] space-y-5 relative overflow-hidden animate-slide-in-up">
      <!-- Glow effect -->
      <div class="absolute -top-20 -left-20 w-40 h-40 bg-red-500/20 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-20 -right-20 w-40 h-40 bg-rose-500/20 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Icon -->
      <div class="relative w-20 h-20 bg-gradient-to-tr from-red-500/20 via-rose-500/20 to-orange-500/20 text-red-400 rounded-3xl border border-red-500/40 flex items-center justify-center mx-auto shadow-[0_0_25px_rgba(239,68,68,0.4)] animate-bounce">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-red-400 drop-shadow-[0_0_8px_rgba(248,113,113,0.8)]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>

      <!-- Badge -->
      <span class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-500/20 text-red-300 border border-red-500/30">
        AdBlocker / Shield Detected 🛑
      </span>

      <!-- Title & Message -->
      <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
        Please Disable Your AdBlocker
      </h2>
      <p class="text-xs sm:text-sm text-slate-300 leading-relaxed font-medium">
        To continue using Easytsk V2, verifying visits, and earning reward points from micro-tasks, please disable your <strong>AdBlocker</strong>, <strong>AdGuard</strong>, or <strong>Brave Shields</strong> for this site.
      </p>

      <!-- Action Button -->
      <div class="pt-2">
        <button 
          @click="recheck" 
          class="w-full py-3.5 px-5 bg-gradient-to-r from-red-600 via-rose-600 to-orange-600 hover:from-red-500 hover:to-orange-500 text-white font-bold text-xs tracking-wider uppercase rounded-xl shadow-[0_0_25px_rgba(239,68,68,0.4)] hover:shadow-[0_0_35px_rgba(239,68,68,0.6)] transition-all transform active:scale-95 cursor-pointer"
        >
          🔄 I Have Disabled It (Refresh Page)
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const isAdblockActive = ref(false);

const runDetection = async () => {
  const triggerAdblock = () => {
    isAdblockActive.value = true;
  };

  // ── Vector 1: Network Filter Detection (Catches AdBlockers in Optimal / Standard Mode) ──
  const testUrls = [
    'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js',
    'https://adservice.google.com/adsid/integrator.js?domain=easytsk.com',
    'https://securepubads.g.doubleclick.net/tag/js/gpt.js'
  ];

  testUrls.forEach(url => {
    fetch(new Request(url, { method: 'HEAD', mode: 'no-cors', cache: 'no-store' }))
      .then(response => {
        if (response.redirected && response.url.includes('0.0.0.0')) {
          triggerAdblock();
        }
      })
      .catch(() => {
        // Network level rejection (ERR_BLOCKED_BY_CLIENT / Failed to fetch)
        triggerAdblock();
      });
  });

  // ── Vector 2: Ad Script Bait Element ──
  try {
    const baitScript = document.createElement('script');
    baitScript.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-0000000000000000';
    baitScript.async = true;
    baitScript.onerror = () => {
      triggerAdblock();
      baitScript.remove();
    };
    baitScript.onload = () => {
      baitScript.remove();
    };
    document.head.appendChild(baitScript);
  } catch (e) {
    triggerAdblock();
  }

  // ── Vector 3: DOM Element Bait & Cosmetic Filter Check ──
  const baitDiv = document.createElement('div');
  baitDiv.className = 'adsbox ad-placement pub_300x250 pub_300x250m pub_728x90 text-ad textAd text_ad text_ads text-ads text-ad-links banner-ad google-ad adsbygoogle';
  baitDiv.id = 'ad-banner-placement';
  baitDiv.style.cssText = 'position: absolute !important; left: -9999px !important; top: -9999px !important; width: 300px !important; height: 250px !important; pointer-events: none !important; opacity: 0.01 !important;';
  baitDiv.innerHTML = '&nbsp;';
  document.body.appendChild(baitDiv);

  setTimeout(() => {
    if (
      baitDiv.offsetParent === null ||
      baitDiv.offsetHeight === 0 ||
      baitDiv.clientHeight === 0 ||
      window.getComputedStyle(baitDiv).display === 'none' ||
      window.getComputedStyle(baitDiv).visibility === 'hidden'
    ) {
      triggerAdblock();
    }
    baitDiv.remove();
  }, 150);

  // ── Vector 4: Brave Shields Built-in Query ──
  if (navigator.brave && typeof navigator.brave.isBrave === 'function') {
    navigator.brave.isBrave().then(isBrave => {
      if (isBrave) {
        fetch('https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', { mode: 'no-cors' })
          .catch(() => triggerAdblock());
      }
    });
  }
};

const recheck = () => {
  window.location.reload();
};

onMounted(() => {
  runDetection();
});
</script>
