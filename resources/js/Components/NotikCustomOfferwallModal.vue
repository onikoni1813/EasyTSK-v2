<template>
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="isOpen" 
        class="fixed inset-0 z-[110] bg-slate-950/95 backdrop-blur-xl flex flex-col justify-between overflow-x-hidden text-slate-100"
      >
        <!-- Top Navbar -->
        <header class="px-3 py-2.5 sm:px-6 sm:py-3.5 bg-slate-900/95 border-b border-cyan-500/20 flex items-center justify-between shrink-0 shadow-lg z-30">
          <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-gradient-to-tr from-amber-500/20 via-cyan-500/20 to-indigo-500/20 border border-cyan-500/40 flex items-center justify-center text-base sm:text-xl shrink-0 shadow-inner">
              ⚡
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1.5 sm:gap-2">
                <h2 class="text-sm sm:text-lg font-black text-white tracking-tight truncate">
                  Notik <span class="text-cyan-400">Offerwall</span>
                </h2>
                <span class="px-1.5 sm:px-2 py-0.5 rounded-full text-[8px] sm:text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-amber-500/20 to-cyan-500/20 text-cyan-300 border border-cyan-500/30 flex items-center gap-1 shrink-0">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                  Native
                </span>
              </div>
              <p class="text-[10px] sm:text-[11px] text-slate-400 hidden sm:block truncate">
                Complete certified tasks, app downloads & surveys to earn instant coins.
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-1.5 sm:gap-3 shrink-0">
            <!-- Refresh Offers Button -->
            <button 
              @click="fetchOffers(true)" 
              :disabled="loading"
              class="p-2 sm:px-3 sm:py-1.5 rounded-xl bg-slate-800/80 hover:bg-slate-700 border border-slate-700 text-xs font-semibold text-slate-300 hover:text-white transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              title="Refresh Offers"
            >
              <span :class="loading ? 'animate-spin inline-block' : ''">🔄</span>
              <span class="hidden sm:inline">Refresh</span>
            </button>


            <!-- Close Modal Button -->
            <button 
              @click="close"
              class="px-2.5 py-1.5 sm:px-3.5 sm:py-1.5 rounded-xl bg-rose-500/20 text-rose-300 hover:bg-rose-500 hover:text-white border border-rose-500/30 text-xs font-black transition cursor-pointer flex items-center gap-1"
            >
              <span>✕</span>
              <span class="hidden sm:inline">Close</span>
            </button>
          </div>
        </header>

        <!-- Main Body Area -->
        <main class="flex-1 overflow-y-auto overflow-x-hidden px-3.5 py-3.5 sm:px-8 sm:py-6 space-y-3.5 sm:space-y-6 max-w-7xl w-full mx-auto">
          
          <!-- Notice / Banner -->
          <div class="p-3 sm:p-5 rounded-2xl sm:rounded-3xl bg-gradient-to-r from-cyan-950/40 via-slate-900/90 to-indigo-950/40 border border-cyan-500/20 shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
              <div class="w-9 h-9 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-lg sm:text-2xl shrink-0">
                💎
              </div>
              <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="text-xs sm:text-base font-bold text-white leading-tight">
                    Special Earning Multiplier
                  </h3>
                  <span class="text-[10px] sm:text-xs font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-lg border border-emerald-500/20 shrink-0">
                    {{ (offerwall?.reward_ratio || 1.0) }}x Payout
                  </span>
                </div>
                <p class="text-[11px] sm:text-xs text-slate-400 mt-0.5 line-clamp-1 sm:line-clamp-none">
                  Complete simple offer tasks. Coins credited upon advertiser confirmation.
                </p>
              </div>
            </div>
            
            <div class="flex items-center justify-between sm:justify-start gap-2 text-[10px] sm:text-xs font-mono text-slate-300 bg-slate-950/70 px-3 py-1.5 sm:px-3.5 sm:py-2 rounded-xl sm:rounded-2xl border border-slate-800 shrink-0">
              <div class="flex items-center gap-1.5">
                <span class="text-slate-500">Region:</span>
                <span class="text-cyan-400 font-bold uppercase">{{ countryCode }}</span>
              </div>
              <span class="text-slate-700">|</span>
              <span class="text-emerald-400 font-bold">{{ filteredOffers.length }} Live Offers</span>
            </div>
          </div>

          <!-- Controls Bar (Search + Platform Filters) -->
          <div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 sm:gap-3">
              <!-- Search Input -->
              <div class="relative flex-1 w-full sm:max-w-md">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 text-xs sm:text-sm">🔍</span>
                <input 
                  v-model="searchQuery" 
                  type="text" 
                  placeholder="Search offers by name, or keyword..." 
                  autocomplete="off"
                  spellcheck="false"
                  class="w-full pl-9 pr-8 py-2 sm:py-2.5 bg-slate-900/90 border border-slate-800 focus:border-cyan-500/50 rounded-xl sm:rounded-2xl text-xs text-white placeholder-slate-500 outline-none transition shadow-inner"
                />
                <button 
                  v-if="searchQuery" 
                  @click="searchQuery = ''" 
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white text-xs cursor-pointer"
                >
                  ✕
                </button>
              </div>

              <!-- Device / Platform Switcher (3-Col Grid on Mobile, Flex on Desktop) -->
              <div class="w-full sm:w-auto grid grid-cols-3 sm:flex items-center gap-1 p-1 bg-slate-900 border border-slate-800 rounded-xl sm:rounded-2xl shrink-0 text-xs">
                <button 
                  v-for="p in platforms" 
                  :key="p.id"
                  @click="selectedPlatform = p.id"
                  :class="[
                    p.hideOnMobile ? 'hidden sm:flex' : 'flex',
                    selectedPlatform === p.id ? 'bg-cyan-500 text-slate-950 shadow-md shadow-cyan-500/20' : 'text-slate-400 hover:text-white',
                    'px-2.5 py-1.5 sm:px-3 rounded-lg sm:rounded-xl font-bold transition items-center justify-center gap-1 cursor-pointer text-[11px] sm:text-xs text-center'
                  ]"
                >
                  <span class="shrink-0">{{ p.icon }}</span>
                  <span class="whitespace-nowrap">{{ p.label }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- Loading Skeleton State -->
          <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 pt-1">
            <div 
              v-for="n in 8" 
              :key="n"
              class="p-4 sm:p-5 rounded-2xl sm:rounded-3xl bg-slate-900/50 border border-slate-800/80 animate-pulse space-y-3 sm:space-y-4"
            >
              <div class="flex items-center gap-3">
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-slate-800 shrink-0"></div>
                <div class="flex-1 space-y-2">
                  <div class="h-4 bg-slate-800 rounded-lg w-3/4"></div>
                  <div class="h-3 bg-slate-800 rounded-lg w-1/2"></div>
                </div>
              </div>
              <div class="h-8 bg-slate-800/60 rounded-xl"></div>
              <div class="h-8 bg-slate-800 rounded-xl"></div>
            </div>
          </div>

          <!-- Error / Unconfigured State (Compact on Mobile) -->
          <div 
            v-else-if="!isConfigured" 
            class="p-5 sm:p-10 text-center rounded-2xl sm:rounded-3xl bg-slate-900/70 border border-amber-500/30 max-w-lg mx-auto space-y-3 sm:space-y-4 shadow-2xl my-2"
          >
            <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-2xl sm:rounded-3xl bg-amber-500/20 text-amber-400 flex items-center justify-center text-2xl sm:text-3xl mx-auto border border-amber-500/30">
              ⚙️
            </div>
            <h3 class="text-base sm:text-lg font-bold text-white">Notik API Setup Required</h3>
            <p class="text-[11px] sm:text-xs text-slate-400 leading-relaxed max-w-sm mx-auto">
              {{ errorMessage || 'Notik API credentials (api_key, pub_id, app_id) are not yet configured in the Admin Offerwall Manager.' }}
            </p>
            <div class="pt-2 flex flex-col sm:flex-row items-center justify-center gap-2.5 sm:gap-3">
              <button 
                @click="fetchOffers(true)"
                class="w-full sm:w-auto px-4 py-2.5 sm:px-5 sm:py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition cursor-pointer active:scale-95"
              >
                🔄 Retry Connection
              </button>
            </div>
          </div>

          <!-- Empty Offers State -->
          <div 
            v-else-if="filteredOffers.length === 0" 
            class="p-8 sm:p-12 text-center rounded-2xl sm:rounded-3xl bg-slate-900/40 border border-slate-800 max-w-md mx-auto space-y-3 my-2"
          >
            <div class="text-3xl sm:text-4xl mb-1">🎯</div>
            <h3 class="text-sm sm:text-base font-bold text-white">No Offers Found</h3>
            <p class="text-[11px] sm:text-xs text-slate-400">
              {{ searchQuery ? 'No offers match your search criteria. Try a different keyword.' : 'There are currently no offers matching your active filters. Try switching platform.' }}
            </p>
            <button 
              v-if="searchQuery || selectedPlatform !== 'all'"
              @click="resetFilters"
              class="mt-1 px-3.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-cyan-400 transition cursor-pointer"
            >
              Reset Filters
            </button>
          </div>

          <!-- Live Offers Grid (Responsive 1-col on mobile, 2 on tablet, 3-4 on desktop) -->
          <div 
            v-else 
            class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4 pt-1"
          >
            <div 
              v-for="offer in filteredOffers" 
              :key="offer.id"
              class="group relative rounded-2xl sm:rounded-3xl p-3.5 sm:p-5 bg-slate-900/80 hover:bg-slate-900 border border-slate-800 hover:border-cyan-500/40 transition-all duration-200 flex flex-col justify-between space-y-3 sm:space-y-4 shadow-xl hover:shadow-cyan-950/20 overflow-hidden"
            >
              <!-- Card Top (Icon + Name + Payout) -->
              <div class="space-y-2.5 sm:space-y-3">
                <div class="flex items-start gap-2.5 sm:gap-3">
                  <!-- Offer Icon -->
                  <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-slate-950 border border-slate-800 overflow-hidden shrink-0 flex items-center justify-center p-1 group-hover:scale-105 transition-transform shadow-md">
                    <img 
                      v-if="offer.image_url" 
                      :src="offer.image_url" 
                      :alt="offer.name" 
                      class="w-full h-full object-cover rounded-lg sm:rounded-xl"
                      loading="lazy"
                      @error="handleImgError($event)"
                    />
                    <span v-else class="text-xl sm:text-2xl">🎮</span>
                  </div>

                  <!-- Offer Title & Meta -->
                  <div class="min-w-0 flex-1">
                    <h4 class="text-xs sm:text-sm font-black text-white truncate leading-snug" :title="offer.name">
                      {{ offer.name }}
                    </h4>

                    <!-- Category & Platform Tags -->
                    <div class="flex items-center gap-1 sm:gap-1.5 flex-wrap mt-1">
                      <span 
                        v-if="offer.categories && offer.categories[0]" 
                        class="px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] font-bold uppercase tracking-wider bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 truncate max-w-[120px]"
                      >
                        {{ offer.categories[0] }}
                      </span>
                      <span 
                        v-if="offer.device_os && offer.device_os !== 'all'" 
                        class="px-1.5 py-0.5 rounded-md text-[8px] sm:text-[9px] font-mono font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700"
                      >
                        {{ offer.device_os }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Short Instructions / Requirement -->
                <p 
                  class="text-[10px] sm:text-[11px] text-slate-400 line-clamp-2 leading-relaxed bg-slate-950/60 p-2 sm:p-2.5 rounded-xl border border-slate-800/80" 
                  :title="offer.short_desc"
                >
                  {{ offer.short_desc }}
                </p>
              </div>

              <!-- Card Bottom (Reward Badge + CTA Button) -->
              <div class="pt-2.5 sm:pt-3 border-t border-slate-800/80 flex items-center justify-between gap-2 sm:gap-3">
                <!-- Coin Reward Display -->
                <div>
                  <div class="text-[8px] sm:text-[9px] text-slate-500 font-bold uppercase tracking-wider">Reward</div>
                  <div class="text-xs sm:text-base font-black text-emerald-400 font-mono tracking-tight flex items-center gap-1">
                    <span>+{{ formatNumber(offer.reward_coins) }}</span>
                    <span class="text-[9px] sm:text-[10px] text-emerald-300/80 font-normal">Coins</span>
                  </div>
                </div>

                <!-- Start Action Button -->
                <button 
                  @click="startOffer(offer)"
                  class="px-3 py-2 sm:px-4 sm:py-2.5 rounded-xl sm:rounded-2xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-slate-950 hover:text-white font-black text-[11px] sm:text-xs transition-all shadow-lg shadow-cyan-900/20 flex items-center gap-1 cursor-pointer active:scale-95 shrink-0"
                >
                  <span>Earn</span>
                  <span>🚀</span>
                </button>
              </div>
            </div>
          </div>

        </main>

        <!-- Footer / Instructions (Super Compact on Mobile) -->
        <footer class="px-3 py-2 sm:px-4 sm:py-2.5 bg-slate-900/95 border-t border-slate-800/90 text-center text-[10px] sm:text-[11px] text-slate-400 shrink-0 leading-tight">
          🔒 Secure Postback Verification active. Rewards unlock automatically after verification.
        </footer>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  offerwall: {
    type: Object,
    default: () => null
  }
});

const emit = defineEmits(['close']);

// State
const loading = ref(false);
const offers = ref([]);
const countryCode = ref('BD');
const isConfigured = ref(true);
const errorMessage = ref('');

// Filters
const searchQuery = ref('');
const selectedPlatform = ref('all');

const platforms = [
  { id: 'all', label: 'All', icon: '🌐', hideOnMobile: false },
  { id: 'android', label: 'Android', icon: '🤖', hideOnMobile: false },
  { id: 'ios', label: 'iOS', icon: '🍏', hideOnMobile: false },
  { id: 'desktop', label: 'Desktop', icon: '💻', hideOnMobile: true },
];

// Fetch Offers from Backend
const fetchOffers = async (forceRefresh = false) => {
  loading.value = true;
  errorMessage.value = '';

  try {
    const res = await axios.get('/offerwall/notik/offers', {
      params: { refresh: forceRefresh ? 1 : 0 }
    });

    if (res.data.success) {
      offers.value = res.data.offers || [];
      countryCode.value = res.data.country || 'BD';
      isConfigured.value = res.data.is_configured ?? true;
    } else {
      isConfigured.value = res.data.is_configured ?? false;
      errorMessage.value = res.data.message || 'Failed to load Notik offers.';
    }
  } catch (err) {
    console.error('Failed to fetch Notik offers', err);
    errorMessage.value = err.response?.data?.message || 'Error connecting to Notik API.';
  } finally {
    loading.value = false;
  }
};

// Open offer tracking link in new tab
const startOffer = (offer) => {
  if (offer && offer.click_url) {
    window.open(offer.click_url, '_blank');
  }
};

// Filtered Offers Computed
const filteredOffers = computed(() => {
  let list = [...offers.value];

  // Search filter
  if (searchQuery.value.trim() !== '') {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter(o => 
      (o.name && o.name.toLowerCase().includes(q)) ||
      (o.short_desc && o.short_desc.toLowerCase().includes(q)) ||
      (o.categories && o.categories.some(c => c.toLowerCase().includes(q)))
    );
  }

  // Platform filter
  if (selectedPlatform.value !== 'all') {
    list = list.filter(o => {
      const os = (o.device_os || 'all').toLowerCase();
      if (os === 'all') return true;
      if (selectedPlatform.value === 'android') return os.includes('android');
      if (selectedPlatform.value === 'ios') return os.includes('ios') || os.includes('iphone') || os.includes('ipad');
      if (selectedPlatform.value === 'desktop') return os.includes('windows') || os.includes('mac') || os.includes('desktop');
      return true;
    });
  }

  return list;
});

const formatNumber = (num) => {
  return Number(num || 0).toLocaleString();
};

const handleImgError = (event) => {
  event.target.style.display = 'none';
};

const resetFilters = () => {
  searchQuery.value = '';
  selectedPlatform.value = 'all';
};

const close = () => {
  emit('close');
};


// Watch for modal open
watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    searchQuery.value = '';
    selectedPlatform.value = 'all';
    if (offers.value.length === 0) {
      fetchOffers();
    }
  }
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.scrollbar-none::-webkit-scrollbar {
  display: none;
}
.scrollbar-none {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
