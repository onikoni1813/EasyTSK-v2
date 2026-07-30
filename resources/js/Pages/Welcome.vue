<template>
  <GuestLayout>
    <Head title="Earn Money Online — Easytsk V2" />

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- HERO SECTION                                                -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- HERO SECTION                                                -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="relative overflow-hidden">

      <!-- Animated Background Orbs -->
      <div class="absolute inset-0 pointer-events-none">
        <div class="hero-orb hero-orb-1"></div>
        <div class="hero-orb hero-orb-2"></div>
        <div class="hero-orb hero-orb-3"></div>
        <!-- Cyber Grid overlay -->
        <div class="absolute inset-0 cyber-grid opacity-40"></div>
        <!-- Radial vignette -->
        <div class="absolute inset-0" style="background: radial-gradient(ellipse at center, transparent 20%, #02040a 80%);"></div>
      </div>

      <!-- Floating particles -->
      <div class="absolute inset-0 pointer-events-none overflow-hidden">
        <div v-for="i in 20" :key="i" class="particle" :style="particleStyle(i)"></div>
      </div>

      <div class="relative max-w-6xl mx-auto px-4 sm:px-6 pt-16 pb-20 sm:pt-20 sm:pb-24 text-center w-full">

        <!-- Badge -->
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-card border border-indigo-500/30 mb-6 animate-slide-in-up">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse-neon"></span>
          <span class="text-xs font-bold text-indigo-300 uppercase tracking-widest">Platform Live · Trusted by Thousands</span>
        </div>

        <!-- Headline -->
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tighter text-white leading-[1.08] mb-6" style="animation: slide-in-up 0.6s ease-out 0.1s both;">
          Monetize Your Time<br>
          <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 via-violet-400 to-cyan-400 bg-[length:200%_auto] animate-gradient-x">
            With EasyTSK
          </span>
        </h1>

        <!-- Subheadline -->
        <p class="text-base sm:text-lg text-slate-400 max-w-2xl mx-auto mb-10 leading-relaxed" style="animation: slide-in-up 0.6s ease-out 0.2s both;">
          Complete simple online tasks, earn real rewards, and withdraw directly to your mobile banking or payment wallet — no investment required.
        </p>

        <!-- CTA Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4" style="animation: slide-in-up 0.6s ease-out 0.3s both;">
          <a
            href="/register"
            id="hero-get-started-btn"
            class="btn-neon btn-primary px-8 py-4 text-base font-bold text-white rounded-2xl shadow-[0_0_30px_rgba(99,102,241,0.4)] hover:shadow-[0_0_50px_rgba(99,102,241,0.6)] transition-all duration-300"
          >
            🚀 Get Started — It's Free
          </a>
          <a
            href="#how-it-works"
            class="px-8 py-4 text-base font-semibold text-slate-300 border border-white/10 rounded-2xl hover:border-indigo-500/40 hover:text-white transition-all duration-300 hover:bg-white/5"
          >
            How It Works ↓
          </a>
        </div>

      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- HOW IT WORKS                                               -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section id="how-it-works" class="py-16 sm:py-20 relative border-t border-white/5">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-12">
          <div class="section-header justify-center">
            <div class="section-header-line" style="max-width:80px"></div>
            <span class="section-title">How It Works</span>
            <div class="section-header-line" style="max-width:80px"></div>
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white mt-3">Start Earning in 3 Simple Steps</h2>
          <p class="text-slate-400 mt-3 max-w-xl mx-auto text-sm sm:text-base">No experience needed. No investment required. Just your time and effort.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative">
          <!-- Connector line (desktop) -->
          <div class="absolute top-16 left-1/4 right-1/4 h-px hidden md:block" style="background: linear-gradient(90deg, transparent, rgba(99,102,241,0.4), transparent);"></div>

          <div
            v-for="(step, i) in steps"
            :key="i"
            class="step-card glass-card rounded-3xl p-8 text-center card-hover border border-white/5 relative flex flex-col items-center"
            :class="step.borderColor"
          >
            <!-- Step Badge -->
            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold mb-5" :class="step.badgeBg">
              <span class="w-1.5 h-1.5 rounded-full bg-current animate-pulse"></span>
              <span>Step {{ i + 1 }}</span>
            </div>

            <!-- Icon -->
            <div class="w-20 h-20 rounded-2xl mx-auto mb-5 flex items-center justify-center text-4xl" :class="step.iconBg">
              {{ step.icon }}
            </div>
            <h3 class="text-xl font-bold text-white mb-3">{{ step.title }}</h3>
            <p class="text-sm text-slate-400 leading-relaxed">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- TRUST STATS (Live Counters)                                -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section id="stats" class="py-16 sm:py-20 relative overflow-hidden border-t border-white/5">
      <div class="absolute inset-0" style="background: linear-gradient(135deg, rgba(99,102,241,0.06) 0%, rgba(139,92,246,0.04) 50%, rgba(6,182,212,0.06) 100%);"></div>
      <div class="absolute inset-0 cyber-grid opacity-20"></div>

      <div class="relative max-w-6xl mx-auto px-4 sm:px-6">
        <div class="text-center mb-12">
          <div class="section-header justify-center">
            <div class="section-header-line" style="max-width:80px"></div>
            <span class="section-title flex items-center gap-2">
              <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              Platform Stats
            </span>
            <div class="section-header-line" style="max-width:80px"></div>
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white mt-3">Real Numbers, Real Payments</h2>
          <p class="text-slate-400 mt-3 text-sm sm:text-base">Our platform stats update in real-time as members earn and withdraw.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
          <div
            v-for="stat in liveStats"
            :key="stat.label"
            class="glass-card rounded-3xl p-8 text-center card-hover border relative group overflow-hidden transition-all duration-300 hover:-translate-y-1.5"
            :class="stat.borderClass"
          >
            <!-- Hover ambient glow behind card -->
            <div class="absolute -inset-px rounded-3xl bg-gradient-to-r from-indigo-500/10 via-purple-500/10 to-pink-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-md pointer-events-none"></div>

            <div class="w-16 h-16 rounded-2xl mx-auto mb-5 flex items-center justify-center text-3xl transition-transform duration-300 group-hover:scale-110" :class="stat.iconBg">
              {{ stat.icon }}
            </div>
            <div class="text-4xl sm:text-5xl font-black mb-2 tracking-tight">
              <AnimatedCounter :target="stat.value" :prefix="stat.prefix" :suffix="stat.suffix" :duration="2200" :class="stat.textClass" />
            </div>
            <div class="text-sm font-bold text-slate-300 mb-1">{{ stat.label }}</div>
            <div class="text-xs text-slate-500">{{ stat.sublabel }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- FEATURES GRID                                              -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section id="why-us" class="py-16 sm:py-20 border-t border-white/5">
      <div class="max-w-6xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-12">
          <div class="section-header justify-center">
            <div class="section-header-line" style="max-width:80px"></div>
            <span class="section-title">Why Easytsk</span>
            <div class="section-header-line" style="max-width:80px"></div>
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white mt-3">Everything You Need to Earn</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="f in features"
            :key="f.title"
            class="glass-card rounded-2xl p-7 card-hover border border-white/5 hover:border-indigo-500/20 transition-colors duration-300 group"
          >
            <div class="w-12 h-12 rounded-xl mb-4 flex items-center justify-center text-2xl" :class="f.iconBg">{{ f.icon }}</div>
            <h3 class="font-bold text-white mb-2 group-hover:text-indigo-300 transition-colors duration-200 text-base">{{ f.title }}</h3>
            <p class="text-sm text-slate-500 leading-relaxed">{{ f.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- FAQ                                                        -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="py-16 sm:py-20 border-t border-white/5" id="faq">
      <div class="max-w-3xl mx-auto px-4 sm:px-6">

        <div class="text-center mb-12">
          <div class="section-header justify-center">
            <div class="section-header-line" style="max-width:80px"></div>
            <span class="section-title">FAQ</span>
            <div class="section-header-line" style="max-width:80px"></div>
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-white mt-3">Frequently Asked Questions</h2>
          <p class="text-slate-400 mt-3 text-sm sm:text-base">Everything you need to know before you start.</p>
        </div>

        <div class="space-y-4">
          <div
            v-for="(faq, i) in faqs"
            :key="i"
            class="glass-card rounded-2xl border border-white/5 overflow-hidden transition-all duration-300"
            :class="openFaq === i ? 'border-indigo-500/25' : ''"
          >
            <button
              @click="openFaq = openFaq === i ? null : i"
              class="w-full flex items-center justify-between px-6 py-5 text-left hover:bg-white/3 transition-colors duration-200"
            >
              <span class="font-semibold text-slate-200 pr-4 text-sm sm:text-base">{{ faq.q }}</span>
              <span class="shrink-0 w-7 h-7 rounded-full border border-white/10 flex items-center justify-center text-slate-400 transition-transform duration-300" :class="openFaq === i ? 'rotate-45 border-indigo-500/40 text-indigo-400' : ''">+</span>
            </button>
            <div v-show="openFaq === i" class="px-6 pb-5">
              <div class="h-px bg-white/5 mb-4"></div>
              <p class="text-sm text-slate-400 leading-relaxed">{{ faq.a }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- AD NETWORK COMPLIANCE & PLATFORM GUIDE                     -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="py-16 sm:py-20 border-t border-white/5 bg-slate-900/20">
      <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <div class="glass-card rounded-3xl p-8 sm:p-12 border border-white/5 space-y-8">
          <div class="text-center max-w-2xl mx-auto space-y-3">
            <span class="text-xs font-bold text-indigo-400 uppercase tracking-widest">Platform Transparency & Trust</span>
            <h2 class="text-2xl sm:text-3xl font-black text-white">How Easytsk V2 Ensures Safe & Fair Micro-Tasking</h2>
            <p class="text-sm text-slate-400 leading-relaxed">
              We connect digital advertisers with engaged real users globally. Our automated verification engine ensures authentic engagement while providing members with transparent earnings.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 text-xs sm:text-sm text-slate-300 leading-relaxed">
            <div class="space-y-3 p-5 rounded-2xl bg-white/2 border border-white/5">
              <h3 class="font-bold text-white text-base flex items-center gap-2">
                <span>🛡️</span> Multi-Layer Fraud Prevention
              </h3>
              <p class="text-slate-400">
                To protect advertisers and maintain network integrity, Easytsk V2 employs IP threat intelligence, device fingerprinting, and strict single-account verification. Automated bots and VPN traffic are blocked instantly.
              </p>
            </div>

            <div class="space-y-3 p-5 rounded-2xl bg-white/2 border border-white/5">
              <h3 class="font-bold text-white text-base flex items-center gap-2">
                <span>💸</span> Verified Instant Payouts
              </h3>
              <p class="text-slate-400">
                Once task completions are approved by advertisers or our automated validation system, earnings are instantly credited to your wallet balance. Members can request withdrawals directly to bKash, Nagad, or wallet accounts.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════ -->
    <!-- FINAL CTA                                                  -->
    <!-- ═══════════════════════════════════════════════════════════ -->
    <section class="py-16 sm:py-20 border-t border-white/5">
      <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <div class="glass-card rounded-3xl p-8 sm:p-14 text-center relative overflow-hidden border border-indigo-500/20">
          <!-- BG glow -->
          <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse at center, rgba(99,102,241,0.12) 0%, transparent 70%);"></div>
          <div class="absolute inset-0 cyber-grid opacity-20 pointer-events-none"></div>

          <div class="relative">
            <div class="text-5xl mb-6">💎</div>
            <h2 class="text-3xl sm:text-5xl font-black text-white mb-4 tracking-tight">
              Start Earning <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400">Today</span>
            </h2>
            <p class="text-slate-400 max-w-xl mx-auto mb-8 text-base sm:text-lg leading-relaxed">
              Join thousands of members who are already earning from home. Registration takes less than 60 seconds.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
              <a
                href="/register"
                id="footer-cta-btn"
                class="btn-neon btn-primary px-10 py-4 text-base font-bold text-white rounded-2xl shadow-[0_0_40px_rgba(99,102,241,0.35)]"
              >
                🚀 Claim Your Welcome Bonus
              </a>
              <a href="/login" class="text-sm text-slate-400 hover:text-white transition-colors duration-200">
                Already a member? Sign in →
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>

  </GuestLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import AnimatedCounter from '@/Components/AnimatedCounter.vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({ total_users: 0, tasks_completed: 0, total_payouts: 0 }),
  },
});

// Stats dynamically combining Real DB counts + Admin Demo Boost values
const displayStats = computed(() => ({
  total_users:     props.stats?.total_users || 0,
  tasks_completed: props.stats?.tasks_completed || 0,
  total_payouts:   props.stats?.total_payouts || 0,
}));

function formatNumber(n) {
  if (n >= 1_000_000) return (n / 1_000_000).toFixed(1) + 'M';
  if (n >= 1_000)     return (n / 1_000).toFixed(1) + 'K';
  return n.toLocaleString();
}

// Particle style helper
function particleStyle(i) {
  const size  = 2 + (i % 4);
  const left  = ((i * 37 + 13) % 100);
  const top   = ((i * 53 + 7) % 100);
  const delay = (i * 0.3).toFixed(1);
  const dur   = 4 + (i % 6);
  const colors = ['rgba(99,102,241,0.6)','rgba(139,92,246,0.5)','rgba(6,182,212,0.5)','rgba(16,185,129,0.4)'];
  return {
    width:  `${size}px`,
    height: `${size}px`,
    left:   `${left}%`,
    top:    `${top}%`,
    background: colors[i % colors.length],
    animationDelay:    `${delay}s`,
    animationDuration: `${dur}s`,
  };
}

const openFaq = ref(null);

const steps = [
  {
    icon: '📝', title: 'Create Free Account',
    desc: 'Sign up in under 60 seconds using your email or Google account. No credit card, no deposit — completely free.',
    iconBg: 'bg-indigo-500/15 border border-indigo-500/25',
    borderColor: 'hover:border-indigo-500/30',
    badgeBg: 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30',
  },
  {
    icon: '⚡', title: 'Complete Offers & Tasks',
    desc: 'Browse tasks like watching videos, completing surveys, testing apps, or visiting websites. Earn points for every approved task.',
    iconBg: 'bg-violet-500/15 border border-violet-500/25',
    borderColor: 'hover:border-violet-500/30',
    badgeBg: 'bg-violet-500/20 text-violet-300 border border-violet-500/30',
  },
  {
    icon: '💸', title: 'Withdraw & Get Paid',
    desc: 'Convert your points to cash and withdraw via bKash, Nagad, or other supported methods. Payments processed within 24–72 hours.',
    iconBg: 'bg-emerald-500/15 border border-emerald-500/25',
    borderColor: 'hover:border-emerald-500/30',
    badgeBg: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
  },
];

const liveStats = computed(() => [
  {
    icon: '👥', label: 'Registered Members', sublabel: 'Active earners worldwide',
    value: displayStats.value.total_users, prefix: '', suffix: '+',
    iconBg: 'bg-indigo-500/15 border border-indigo-500/20',
    textClass: 'text-indigo-400 bg-clip-text bg-gradient-to-r from-indigo-300 via-indigo-400 to-purple-400 text-transparent',
    borderClass: 'border-indigo-500/15 hover:border-indigo-500/30',
  },
  {
    icon: '✅', label: 'Tasks Completed', sublabel: 'Successfully approved tasks',
    value: displayStats.value.tasks_completed, prefix: '', suffix: '+',
    iconBg: 'bg-cyan-500/15 border border-cyan-500/20',
    textClass: 'text-cyan-400 bg-clip-text bg-gradient-to-r from-cyan-300 via-cyan-400 to-blue-400 text-transparent',
    borderClass: 'border-cyan-500/15 hover:border-cyan-500/30',
  },
  {
    icon: '💰', label: 'Total Paid Out', sublabel: 'Real money earned by members',
    value: displayStats.value.total_payouts, prefix: '৳', suffix: '+',
    iconBg: 'bg-emerald-500/15 border border-emerald-500/20',
    textClass: 'text-emerald-400 bg-clip-text bg-gradient-to-r from-emerald-300 via-emerald-400 to-teal-400 text-transparent',
    borderClass: 'border-emerald-500/15 hover:border-emerald-500/30',
  },
]);

const features = [
  { icon: '🛡️', title: 'Verified & Secure',      desc: 'All tasks are vetted by our team. Your account is protected with IP monitoring and fraud detection.', iconBg: 'bg-indigo-500/15 border border-indigo-500/20' },
  { icon: '⚡', title: 'Instant Earnings',         desc: 'Points are credited to your account as soon as a task is reviewed and approved — no waiting weeks.', iconBg: 'bg-violet-500/15 border border-violet-500/20' },
  { icon: '📱', title: 'Mobile Friendly',          desc: 'Fully responsive platform. Earn on your phone, tablet, or desktop — anywhere, anytime.', iconBg: 'bg-cyan-500/15 border border-cyan-500/20' },
  { icon: '🎁', title: 'Referral Rewards',         desc: 'Invite friends and earn bonus points for every active referral. The more you share, the more you earn.', iconBg: 'bg-emerald-500/15 border border-emerald-500/20' },
  { icon: '🎰', title: 'Daily Bonus Wheel',        desc: 'Spin the reward wheel every 24 hours for a chance to win bonus points. Extra rewards for streaks.', iconBg: 'bg-amber-500/15 border border-amber-500/20' },
  { icon: '💳', title: 'Multiple Payout Methods', desc: 'Withdraw via bKash, Nagad, or other supported payment gateways. Low minimum withdrawal threshold.', iconBg: 'bg-rose-500/15 border border-rose-500/20' },
];

const faqs = [
  {
    q: 'How do I get paid on Easytsk?',
    a: 'Once your tasks are reviewed and approved, your earnings are credited to your main balance. You can then request a withdrawal via bKash, Nagad, or other supported payment methods. Withdrawals are processed within 24–72 hours, subject to our minimum withdrawal amount policy.',
  },
  {
    q: 'Is Easytsk completely free to join?',
    a: 'Yes, signing up and using Easytsk is 100% free. We do not require any deposit or investment. You earn by completing tasks and offers provided by our platform and third-party advertisers.',
  },
  {
    q: 'Why was my account suspended or banned?',
    a: 'Accounts are suspended for violations including: creating multiple accounts from the same IP or device, submitting fraudulent task completions, using VPNs or proxy servers, botting, or violating our Terms of Service. If you believe your ban was in error, please contact support@easytsk.com.',
  },
  {
    q: 'How long does task approval take?',
    a: 'Most tasks are reviewed and approved within 24 hours. Some tasks that require manual verification by our review team may take up to 72 hours. Offerwall completions may take longer depending on the advertiser\'s verification process.',
  },
  {
    q: 'Can I have more than one account?',
    a: 'No. Each user is allowed exactly one (1) account per household/IP address. Creating multiple accounts is a serious violation of our Terms of Service and will result in a permanent ban of all associated accounts and forfeiture of all balances.',
  },
  {
    q: 'What types of tasks are available?',
    a: 'Tasks include survey completions, app installations, video watching, website visits, social media engagements, offer completions, and micro-campaigns. The availability of tasks varies based on your region and account level.',
  },
];
</script>

<style scoped>
/* Hero Orbs */
.hero-orb {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  pointer-events: none;
}
.hero-orb-1 {
  width: 600px; height: 600px;
  top: -200px; left: -150px;
  background: radial-gradient(circle, rgba(99,102,241,0.18) 0%, transparent 70%);
  animation: float-orb 12s ease-in-out infinite;
}
.hero-orb-2 {
  width: 500px; height: 500px;
  top: 100px; right: -100px;
  background: radial-gradient(circle, rgba(139,92,246,0.14) 0%, transparent 70%);
  animation: float-orb 15s ease-in-out infinite reverse;
}
.hero-orb-3 {
  width: 400px; height: 400px;
  bottom: -100px; left: 40%;
  background: radial-gradient(circle, rgba(6,182,212,0.10) 0%, transparent 70%);
  animation: float-orb 10s ease-in-out infinite 2s;
}
@keyframes float-orb {
  0%,100% { transform: translate(0,0) scale(1); }
  50%      { transform: translate(20px,-20px) scale(1.05); }
}

/* Particles */
.particle {
  position: absolute;
  border-radius: 50%;
  animation: particle-float linear infinite;
  opacity: 0.6;
}
@keyframes particle-float {
  0%   { transform: translateY(0) scale(1); opacity: 0.6; }
  50%  { transform: translateY(-30px) scale(1.2); opacity: 0.3; }
  100% { transform: translateY(0) scale(1); opacity: 0.6; }
}

/* Gradient x animation */
@keyframes gradient-x {
  0%,100% { background-position: 0% 50%; }
  50%      { background-position: 100% 50%; }
}
.animate-gradient-x { animation: gradient-x 4s ease infinite; }

/* FAQ icon rotate */
.rotate-45 { transform: rotate(45deg); }

/* Slide in animation */
@keyframes slide-in-up {
  from { transform: translateY(24px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}
</style>
