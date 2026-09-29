<template>
  <Transition name="slide-up">
    <div 
      v-if="showPrompt" 
      class="fixed bottom-4 right-4 left-4 sm:left-auto sm:max-w-md z-50 p-4 rounded-2xl bg-slate-900/95 border border-cyan-500/50 shadow-2xl backdrop-blur-xl text-white flex items-start gap-3.5 ring-1 ring-white/10"
    >
      <!-- Icon with animated pulse if bonus is available -->
      <div 
        class="w-12 h-12 rounded-2xl flex items-center justify-center text-2xl shrink-0 shadow-lg relative"
        :class="showBonusBadge ? 'bg-gradient-to-tr from-amber-500 via-orange-500 to-rose-500 shadow-orange-500/30 animate-bounce-subtle' : 'bg-gradient-to-tr from-cyan-500 to-indigo-600 shadow-cyan-500/20'"
      >
        <span>{{ showBonusBadge ? '🎁' : '🔔' }}</span>
        <span v-if="showBonusBadge" class="absolute -top-1 -right-1 flex h-3 w-3">
          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
          <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-500"></span>
        </span>
      </div>

      <!-- Content -->
      <div class="flex-1 min-w-0">
        <!-- Success State -->
        <div v-if="successMessage" class="py-1">
          <div class="text-sm font-black text-emerald-400 flex items-center gap-1.5">
            <span>🎉</span>
            <span>সফল হয়েছে!</span>
          </div>
          <p class="text-xs text-slate-200 mt-1 font-medium leading-relaxed">
            {{ successMessage }}
          </p>
        </div>

        <!-- Normal / Bonus State -->
        <div v-else>
          <div class="flex items-center gap-1.5 flex-wrap">
            <h4 class="text-xs sm:text-sm font-black text-white">
              <span v-if="showBonusBadge">নোটিফিকেশন অন করলেই {{ bonusAmount }} পয়েন্ট বোনাস!</span>
              <span v-else>ইনস্ট্যান্ট কাজ ও পেমেন্ট অ্যালার্ট</span>
            </h4>
            <span 
              v-if="showBonusBadge"
              class="px-2 py-0.5 rounded-full text-[10px] font-black bg-gradient-to-r from-amber-500 to-orange-500 text-slate-950 uppercase tracking-wider shadow-sm"
            >
              +{{ bonusAmount }} Pts Free
            </span>
          </div>

          <p class="text-[11px] text-slate-300 mt-1 leading-relaxed">
            <span v-if="showBonusBadge">
              ব্রাউজারে নোটিফিকেশন চালু করুন এবং পেয়ে যান ইনস্ট্যান্ট <strong>{{ bonusAmount }} পয়েন্ট</strong> ফ্রি বোনাস! নতুন কাজ ও উইথড্র পেমেন্টের খবর পাবেন সরাসরি।
            </span>
            <span v-else>
              নতুন হাই-পেয়িং কাজ, প্রমোকোড ও উইথড্র পেমেন্টের তাৎক্ষণিক আপডেট পেতে নোটিফিকেশন অন করে রাখুন।
            </span>
          </p>

          <div class="flex items-center gap-2 mt-3">
            <button 
              @click="enableNotifications" 
              :disabled="isSubscribing"
              class="px-3.5 py-2 rounded-xl text-slate-950 font-black text-xs transition cursor-pointer flex items-center gap-1.5 shadow-lg disabled:opacity-50"
              :class="showBonusBadge ? 'bg-gradient-to-r from-amber-400 via-orange-400 to-amber-300 hover:brightness-110 shadow-orange-500/25 ring-1 ring-amber-300/40' : 'bg-cyan-500 hover:bg-cyan-400 shadow-cyan-500/20'"
            >
              <span v-if="isSubscribing" class="animate-spin inline-block text-sm">🔄</span>
              <span v-else>{{ showBonusBadge ? '🎁' : '🔔' }}</span>
              <span>{{ isSubscribing ? 'চালু হচ্ছে...' : (showBonusBadge ? `বোনাস নিন (+${bonusAmount} Pts)` : 'নোটিফিকেশন অন করুন') }}</span>
            </button>
            <button 
              @click="dismissPrompt" 
              class="px-3 py-2 rounded-xl bg-slate-800/90 hover:bg-slate-700 text-slate-400 hover:text-white font-bold text-xs transition cursor-pointer"
            >
              পরে করব
            </button>
          </div>
        </div>
      </div>

      <!-- Close Button -->
      <button 
        v-if="!successMessage"
        @click="dismissPrompt" 
        class="text-slate-500 hover:text-white text-xs cursor-pointer p-1 transition-colors"
        title="বন্ধ করুন"
      >
        ✕
      </button>
    </div>
  </Transition>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { isPushSupported, getNotificationPermission, subscribeToWebPush } from '@/Utils/webPush';

const page = usePage();
const showPrompt = ref(false);
const isSubscribing = ref(false);
const successMessage = ref('');

const user = computed(() => page.props.auth?.user);
const siteSettings = computed(() => page.props.siteSettings || {});

const bonusEnabled = computed(() => {
  return siteSettings.value.push_bonus_enabled !== 'false';
});

const bonusAmount = computed(() => {
  const amt = parseFloat(siteSettings.value.push_bonus_amount ?? 20);
  return isNaN(amt) ? 20 : amt;
});

const hasClaimed = computed(() => {
  return Boolean(user.value?.has_claimed_push_bonus);
});

// Show bonus badge only if user is logged in, bonus is enabled, and not claimed yet
const showBonusBadge = computed(() => {
  return !!user.value && bonusEnabled.value && !hasClaimed.value && bonusAmount.value > 0;
});

const checkPermission = () => {
  if (!isPushSupported()) return;

  // Don't show if already granted or blocked
  const perm = getNotificationPermission();
  if (perm !== 'default') return;

  const dismissedTime = localStorage.getItem('easytsk_push_dismissed');
  if (dismissedTime && Date.now() - parseInt(dismissedTime) < 86400000 * 2) {
    // Dismissed within last 2 days
    return;
  }

  // Show after slight delay for smoother page load UX
  setTimeout(() => {
    showPrompt.value = true;
  }, 2200);
};

const enableNotifications = async () => {
  isSubscribing.value = true;
  try {
    const res = await subscribeToWebPush();
    
    if (res && res.data) {
      if (res.data.bonus_awarded) {
        successMessage.value = `অভিনন্দন! আপনার একাউন্টে +${res.data.bonus_amount} পয়েন্ট বোনাস যোগ করা হয়েছে!`;
        if (res.data.new_balance !== null && user.value) {
          user.value.main_balance = res.data.new_balance;
          user.value.has_claimed_push_bonus = true;
        }
      } else {
        successMessage.value = res.data.message || 'নোটিফিকেশন সফলভাবে চালু করা হয়েছে!';
      }

      // Hide smoothly after celebration
      setTimeout(() => {
        showPrompt.value = false;
        successMessage.value = '';
      }, 4000);
    } else {
      showPrompt.value = false;
    }
  } catch (err) {
    console.error('Failed to subscribe to web push:', err);
    // If permission was denied or dismissed, close prompt
    showPrompt.value = false;
  } finally {
    isSubscribing.value = false;
  }
};

const dismissPrompt = () => {
  showPrompt.value = false;
  localStorage.setItem('easytsk_push_dismissed', Date.now().toString());
};

onMounted(() => {
  checkPermission();
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(20px);
}

@keyframes bounceSubtle {
  0%, 100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-3px);
  }
}

.animate-bounce-subtle {
  animation: bounceSubtle 2s ease-in-out infinite;
}
</style>
