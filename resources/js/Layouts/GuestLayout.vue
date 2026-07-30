<template>
  <div class="min-h-screen flex flex-col overflow-x-hidden" style="background-color: #02040a;">
    <!-- Cookie Banner -->
    <CookieBanner />

    <!-- ── Navigation Header ─────────────────────────────────────── -->
    <header class="sticky top-0 z-50 border-b border-indigo-500/15 shadow-[0_4px_30px_rgba(0,0,0,0.6)]" style="background: rgba(4, 6, 18, 0.85); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
      <div class="max-w-6xl w-full mx-auto px-4 sm:px-6 py-3 flex items-center justify-between gap-4">

        <!-- Brand Logo -->
        <a href="/" class="flex items-center gap-2.5 group shrink-0">
          <template v-if="$page.props.siteSettings?.site_logo">
            <img :src="$page.props.siteSettings.site_logo" alt="Site Logo" class="h-9 w-auto max-h-9 object-contain drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]" />
          </template>
          <template v-else>
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-600 via-violet-600 to-cyan-400 flex items-center justify-center font-black text-white text-base shadow-[0_0_15px_rgba(99,102,241,0.5)] group-hover:scale-105 transition-transform duration-300">
              E
            </div>
            <div class="flex items-baseline gap-1.5">
              <span class="font-black text-2xl tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-indigo-300 via-purple-300 to-cyan-300 bg-[length:200%_auto] animate-gradient-x drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]">Easytsk</span>
              <span class="text-[9px] px-1.5 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold uppercase tracking-wider shadow-[0_0_8px_rgba(99,102,241,0.2)]">V2</span>
            </div>
          </template>
        </a>

        <!-- Right Action Buttons -->
        <div class="flex items-center gap-2.5">
          <!-- Logged In User Button -->
          <template v-if="$page.props.auth?.user">
            <a href="/dashboard" class="btn-neon btn-emerald px-4 py-2 text-xs font-bold text-white rounded-xl flex items-center gap-2">
              <span>⚡ Dashboard</span>
            </a>
          </template>

          <!-- Guest Buttons -->
          <template v-else>
            <a href="/login" class="px-4 py-2 text-xs font-bold text-slate-300 hover:text-white rounded-xl border border-white/10 hover:border-indigo-500/40 bg-white/5 hover:bg-white/10 transition-all duration-200">
              Sign In
            </a>
            <a href="/register" class="btn-neon btn-primary px-4 py-2 text-xs font-bold text-white rounded-xl shadow-[0_0_20px_rgba(99,102,241,0.4)]">
              Get Started
            </a>
          </template>

          <!-- Mobile Menu Hamburger Button -->
          <button @click="isMobileMenuOpen = !isMobileMenuOpen" aria-label="Toggle Menu" class="sm:hidden p-2 rounded-xl border border-white/10 text-slate-300 hover:text-white hover:bg-white/5 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path v-if="!isMobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Mobile Dropdown Navigation -->
      <Transition name="mobile-menu">
        <div v-show="isMobileMenuOpen" class="sm:hidden border-t border-white/10 bg-slate-950/95 backdrop-blur-2xl px-4 py-4 space-y-3 shadow-2xl">
          <div class="grid grid-cols-2 gap-2 text-center text-xs font-semibold text-slate-300">
            <a href="/about" @click="isMobileMenuOpen = false" class="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-200">About Us</a>
            <a href="/contact" @click="isMobileMenuOpen = false" class="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-200">Contact Us</a>
            <a href="/terms" @click="isMobileMenuOpen = false" class="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-200">Terms of Service</a>
            <a href="/privacy" @click="isMobileMenuOpen = false" class="p-2.5 rounded-xl bg-white/5 hover:bg-white/10 text-slate-200">Privacy Policy</a>
          </div>
        </div>
      </Transition>
    </header>

    <!-- ── Main Content ──────────────────────────────────────────── -->
    <main class="flex-grow">
      <slot />
    </main>

    <!-- ── Footer ────────────────────────────────────────────────── -->
    <footer class="border-t border-white/5 mt-8" style="background: rgba(4,6,18,0.95);">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-10">

          <!-- Brand & Address -->
          <div class="md:col-span-2 space-y-3">
            <template v-if="$page.props.siteSettings?.site_logo">
              <img :src="$page.props.siteSettings.site_logo" alt="Site Logo" class="h-8 w-auto max-h-8 object-contain drop-shadow-[0_0_10px_rgba(99,102,241,0.4)]" />
            </template>
            <template v-else>
              <div class="flex items-baseline gap-1.5">
                <span class="font-black text-xl tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">Easytsk</span>
                <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold uppercase">V2</span>
              </div>
            </template>
            <p class="text-xs text-slate-400 leading-relaxed max-w-sm">
              Easytsk V2 is a premier micro-task earning platform. Complete simple tasks, earn rewards, and withdraw directly to your mobile wallet.
            </p>
            <div class="text-xs text-slate-500 space-y-1">
              <p>📍 Location: {{ $page.props.siteSettings?.company_address || 'Dhaka, Bangladesh' }}</p>
              <p>📧 Official Support: <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')" class="text-indigo-400 hover:text-indigo-300 transition-colors">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a></p>
            </div>
            <div class="flex items-center gap-2 pt-1">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-neon"></span>
              <span class="text-xs text-emerald-400 font-semibold">Platform Online & Operational</span>
            </div>
          </div>

          <!-- Platform Links -->
          <div>
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest mb-4">Platform</h3>
            <ul class="space-y-2.5">
              <li><a href="/register" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">Create Account</a></li>
              <li><a href="/login" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">Sign In</a></li>
              <li><a href="/about" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">About Us</a></li>
              <li><a href="/contact" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">Contact Us</a></li>
            </ul>
          </div>

          <!-- Legal & Compliance Links -->
          <div>
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-widest mb-4">Legal & Compliance</h3>
            <ul class="space-y-2.5">
              <li><a href="/terms" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">Terms of Service</a></li>
              <li><a href="/privacy" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">Privacy Policy</a></li>
              <li><a href="/cookie-policy" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">Cookie Policy</a></li>
              <li><a href="/contact" class="text-sm text-slate-400 hover:text-indigo-400 transition-colors duration-200">Support</a></li>
            </ul>
          </div>
        </div>

        <!-- Ad Network Earning Disclaimer Box -->
        <div class="p-4 rounded-2xl border border-white/5 bg-white/2 text-[11px] text-slate-500 leading-relaxed mb-8">
          <strong>Disclaimer:</strong> Easytsk V2 is a digital rewards and micro-tasking network. User earnings depend on individual task completions, offer approvals, and advertiser verification. We do not guarantee fixed income. Third-party advertisers operate under their own terms and privacy guidelines.
        </div>

        <!-- Bottom Bar -->
        <div class="border-t border-white/5 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p class="text-xs text-slate-500">
            &copy; {{ new Date().getFullYear() }} Easytsk V2. All rights reserved.
          </p>
          <div class="flex items-center gap-4">
            <a href="/terms" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Terms</a>
            <span class="text-slate-700">·</span>
            <a href="/privacy" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Privacy</a>
            <span class="text-slate-700">·</span>
            <a href="/cookie-policy" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Cookies</a>
            <span class="text-slate-700">·</span>
            <a href="/about" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">About</a>
            <span class="text-slate-700">·</span>
            <a href="/contact" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Contact</a>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import CookieBanner from '@/Components/CookieBanner.vue';

const isMobileMenuOpen = ref(false);
</script>

<style scoped>
@keyframes gradient-x {
  0%, 100% { background-position: 0% 50%; }
  50%       { background-position: 100% 50%; }
}
.animate-gradient-x {
  animation: gradient-x 3s ease infinite;
}

.mobile-menu-enter-active,
.mobile-menu-leave-active {
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}
.mobile-menu-enter-from,
.mobile-menu-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
