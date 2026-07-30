<template>
  <div class="min-h-screen flex flex-col pb-24 md:pb-0 overflow-x-hidden" style="background-color: #02040a;">
    <AntiAdblock />

    <!-- ── Top Header ─────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-40 glass-card border-b border-white/5">
      <div class="max-w-5xl w-full mx-auto px-2.5 sm:px-4 py-2.5 sm:py-3 flex items-center justify-between gap-1.5 sm:gap-3">

        <!-- Logo -->
        <Link href="/dashboard" class="flex items-center shrink-0">
          <template v-if="$page.props.siteSettings?.site_logo">
            <img :src="$page.props.siteSettings.site_logo" alt="Site Logo" class="h-7 sm:h-9 w-auto max-h-9 object-contain drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]" />
          </template>
          <template v-else>
            <div class="flex flex-col justify-center">
              <div class="flex items-baseline gap-1 sm:gap-1.5">
                <span class="font-black text-xl sm:text-3xl tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-purple-400 to-indigo-400 bg-[length:200%_auto] animate-gradient-x drop-shadow-[0_0_8px_rgba(99,102,241,0.4)]">Easytsk</span>
                <span class="text-[9px] sm:text-xs px-1.5 sm:px-2 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold uppercase tracking-wider shadow-[0_0_8px_rgba(99,102,241,0.2)]">
                  V2
                </span>
              </div>
            </div>
          </template>
        </Link>

        <!-- Balance Strip (when logged in) -->
        <div v-if="user" class="flex items-center gap-1 sm:gap-2 shrink-0">
          <!-- Main -->
          <div class="glass-pill px-2 py-1 sm:px-3 sm:py-1.5 rounded-lg sm:rounded-xl border border-emerald-500/25 flex items-center gap-1 sm:gap-1.5 card-hover cursor-default shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse-neon"></span>
            <AnimatedNumber :value="user.main_balance" :decimals="0" class="text-[10px] sm:text-[11px] font-bold text-emerald-300" />
            <span class="text-[10px] text-emerald-500 hidden sm:block">Pts</span>
          </div>
          <!-- Pending -->
          <div class="glass-pill px-3 py-1.5 rounded-xl border border-amber-500/25 hidden sm:flex items-center gap-1.5 shrink-0">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
            <AnimatedNumber :value="user.pending_balance" :decimals="0" class="text-[11px] font-bold text-amber-300" />
          </div>
          <!-- Level Badge Button -->
          <Link
            href="/dashboard"
            class="px-1.5 py-1 sm:px-2.5 sm:py-1.5 rounded-lg sm:rounded-xl bg-gradient-to-r from-violet-950/80 via-purple-900/60 to-indigo-950/80 border border-violet-500/40 hover:border-violet-400/80 shadow-[0_0_10px_rgba(139,92,246,0.25)] hover:shadow-[0_0_16px_rgba(139,92,246,0.4)] transition-all flex items-center gap-1 sm:gap-1.5 shrink-0 whitespace-nowrap active:scale-95 group cursor-pointer"
            title="Your Level"
          >
            <!-- Glowing Lightning SVG Icon -->
            <svg class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-amber-400 fill-amber-400/30 group-hover:scale-110 transition-transform drop-shadow-[0_0_6px_rgba(251,191,36,0.8)] shrink-0" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <div class="flex items-center gap-0.5 sm:gap-1 shrink-0 whitespace-nowrap">
              <span class="text-[9px] sm:text-[10px] font-black uppercase text-violet-300 tracking-wider">Lv</span>
              <span class="text-[10px] sm:text-[11px] font-black text-white bg-violet-500/40 border border-violet-400/50 px-1 sm:px-1.5 py-0.5 rounded-md shadow-inner leading-none">
                {{ user.level }}
              </span>
            </div>
          </Link>
          <!-- Notification Bell Button -->
          <button
            @click="isNotificationDrawerOpen = true"
            class="relative glass-pill p-1.5 sm:px-2.5 sm:py-1.5 rounded-lg sm:rounded-xl border border-indigo-500/30 flex items-center gap-1.5 hover:border-indigo-400 text-indigo-300 hover:text-white transition-all card-hover shrink-0 cursor-pointer"
            title="Notifications"
          >
            <svg class="w-4 h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[9px] font-black text-white shadow-[0_0_8px_rgba(244,63,94,0.8)] animate-pulse">
              {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
          </button>

          <!-- Support Link -->
          <Link href="/support" class="glass-pill px-2 py-1 sm:px-2.5 sm:py-1.5 rounded-lg sm:rounded-xl border border-indigo-500/30 flex items-center gap-1.5 hover:border-indigo-400 text-indigo-300 hover:text-white transition-all card-hover shrink-0" title="Help & Support">
            <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-indigo-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
            <span class="text-[11px] font-bold hidden sm:inline">Support</span>
          </Link>
        </div>

        <div v-else class="flex items-center gap-2">
          <Link href="/login" class="px-3.5 py-1.5 text-xs font-semibold text-slate-400 hover:text-white transition-colors">Login</Link>
          <Link href="/register" class="btn-neon btn-primary px-3.5 py-1.5 text-xs text-white rounded-xl">Register</Link>
        </div>
      </div>
    </header>

    <!-- Notification Drawer Component -->
    <NotificationDrawer
      :is-open="isNotificationDrawerOpen"
      :notifications="notifications"
      :unread-count="unreadCount"
      @close="isNotificationDrawerOpen = false"
    />

    <!-- Confetti Achievement Modal -->
    <AchievementModal
      :notifications="notifications"
    />

    <!-- ── Flash Toasts ────────────────────────────────────────────────── -->
    <Transition name="toast">
      <div v-if="$page.props.flash?.success" class="max-w-md mx-auto mt-3 mx-4 px-4 py-2.5 toast-success rounded-xl text-xs font-semibold text-center shadow-xl animate-slide-in-up">
        ✅ {{ $page.props.flash.success }}
      </div>
    </Transition>
    <Transition name="toast">
      <div v-if="$page.props.flash?.error" class="max-w-md mx-auto mt-3 mx-4 px-4 py-2.5 toast-error rounded-xl text-xs font-semibold text-center shadow-xl animate-slide-in-up">
        ❌ {{ $page.props.flash.error }}
      </div>
    </Transition>

    <!-- ── Main Content ────────────────────────────────────────────────── -->
    <main class="flex-grow max-w-5xl w-full mx-auto px-4 py-6">
      <!-- Skeleton loader shown during Inertia page transitions, replacing spinners per Global Rules -->
      <div v-if="isNavigating" class="space-y-4">
        <SkeletonBlock height="120px" rounded="rounded-3xl" />
        <div class="grid grid-cols-3 gap-3">
          <SkeletonBlock height="80px" rounded="rounded-2xl" />
          <SkeletonBlock height="80px" rounded="rounded-2xl" />
          <SkeletonBlock height="80px" rounded="rounded-2xl" />
        </div>
        <SkeletonBlock height="200px" rounded="rounded-3xl" />
      </div>
      <slot v-else />
    </main>

    <!-- ── Bottom Mobile Nav ───────────────────────────────────────────── -->
    <nav v-if="user" class="fixed bottom-0 left-0 right-0 z-40 md:hidden" style="background: rgba(4,6,18,0.95); backdrop-filter: blur(20px); border-top: 1px solid rgba(99,102,241,0.15);">
      <div class="flex items-center justify-around px-2 py-2">

        <Link href="/dashboard" :class="navClass('/dashboard')" class="nav-btn">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          <span>Home</span>
        </Link>

        <Link href="/tasks" :class="navClass('/tasks')" class="nav-btn">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          <span>Tasks</span>
        </Link>

        <Link href="/campaigns" :class="navClass('/campaigns')" class="nav-btn">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
          </svg>
          <span>Promote</span>
        </Link>

        <Link href="/withdraw" :class="navClass('/withdraw')" class="nav-btn">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Payout</span>
        </Link>

        <button @click="logout" class="nav-btn text-rose-400">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
          </svg>
          <span>Exit</span>
        </button>

      </div>
    </nav>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import AntiAdblock from '@/Components/AntiAdblock.vue';
import AnimatedNumber from '@/Components/AnimatedNumber.vue';
import SkeletonBlock from '@/Components/SkeletonBlock.vue';
import NotificationDrawer from '@/Components/NotificationDrawer.vue';
import AchievementModal from '@/Components/AchievementModal.vue';

const page   = usePage();
const user   = computed(() => page.props.auth?.user);
const notifications = computed(() => page.props.auth?.notifications || []);
const unreadCount = computed(() => page.props.auth?.unreadNotificationsCount || 0);

const isNotificationDrawerOpen = ref(false);
const isNavigating = ref(false);
let removeStartListener = null;
let removeFinishListener = null;

onMounted(() => {
  removeStartListener  = router.on('start',  () => { isNavigating.value = true; });
  removeFinishListener = router.on('finish', () => { isNavigating.value = false; });
});

onUnmounted(() => {
  removeStartListener?.();
  removeFinishListener?.();
});

const navClass = (path) => {
  const active = path === '/'
    ? page.url === '/'
    : page.url.startsWith(path);
  return active ? 'nav-item-active text-indigo-400' : 'text-slate-400 hover:text-slate-200';
};

const logout = () => router.post('/logout');
</script>

<style scoped>
.nav-btn {
  display: flex;
  flex-direction: column;
  align-items: center;
  font-size: 10px;
  font-weight: 600;
  gap: 3px;
  padding: 6px 10px;
  border-radius: 12px;
  transition: color 0.2s ease;
  min-width: 52px;
  cursor: pointer;
  background: none;
  border: none;
}

.toast-enter-active, .toast-leave-active { transition: all 0.35s ease; }
.toast-enter-from  { transform: translateY(-10px); opacity: 0; }
.toast-leave-to    { transform: translateY(-10px); opacity: 0; }

@keyframes gradient-x {
  0%, 100% {
    background-position: 0% 50%;
  }
  50% {
    background-position: 100% 50%;
  }
}
.animate-gradient-x {
  animation: gradient-x 3s ease infinite;
}
</style>
