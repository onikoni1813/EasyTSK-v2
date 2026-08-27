<template>
  <AppLayout>
    <SpinWheelModal
      v-if="showSpinWheel"
      :can-spin="canSpinLocal"
      @close="showSpinWheel = false"
      @spin-complete="onSpinComplete"
    />

    <div class="space-y-5 animate-slide-in-up">

      <!-- ── Hero Banner (User Profile & Dynamic Level Overview) ────────── -->
      <div class="glass-card rounded-3xl border border-indigo-500/15 relative overflow-hidden cyber-grid">
        <!-- Glow orbs -->
        <div class="absolute -right-16 -top-16 w-56 h-56 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-40 h-40 bg-violet-600/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 p-6">
          <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-5">
            <!-- User Info Section -->
            <div class="flex items-center gap-4">
              <!-- Premium Avatar -->
              <div class="relative shrink-0 group">
                <!-- Outer glow -->
                <div class="absolute inset-0 bg-gradient-to-tr from-indigo-500 via-purple-500 to-pink-500 rounded-2xl blur opacity-60 group-hover:opacity-100 transition-opacity duration-300 animate-pulse-neon"></div>
                <!-- Inner box -->
                <div class="relative w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-[#0a0d17] border border-white/10 flex items-center justify-center overflow-hidden">
                  <div class="absolute inset-0 bg-gradient-to-tr from-indigo-500/20 to-purple-500/20"></div>
                  <span class="relative z-10 text-3xl sm:text-4xl font-black text-transparent bg-clip-text bg-gradient-to-br from-indigo-300 to-purple-300 drop-shadow-[0_0_10px_rgba(167,139,250,0.5)]">
                    {{ user.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <!-- Online Status Indicator -->
                <div class="absolute -bottom-1 -right-1 w-5 h-5 sm:w-6 sm:h-6 bg-[#040612] rounded-full flex items-center justify-center">
                  <div class="w-3 h-3 sm:w-3.5 sm:h-3.5 bg-emerald-400 rounded-full shadow-[0_0_10px_rgba(52,211,153,0.8)] animate-pulse"></div>
                </div>
              </div>

              <!-- Info -->
              <div>
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                  <span class="badge badge-indigo shadow-[0_0_8px_rgba(99,102,241,0.3)]">Level {{ user.level }}</span>
                  <span v-if="user.joined_at" class="text-[10px] text-slate-400 bg-slate-800/60 px-2 py-0.5 rounded-full border border-slate-700/50">Member since {{ user.joined_at }}</span>
                  <span v-if="user.is_banned" class="badge badge-rose">⛔ Banned</span>
                  <span v-if="user.risk_score > 50" class="badge badge-amber">⚠️ High Risk</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-white leading-tight drop-shadow-md">
                  {{ user.name }}
                </h1>
                <p class="text-[11px] sm:text-xs text-slate-400 mt-1 flex items-center gap-1.5 flex-wrap">
                  <span v-if="user.email" class="text-slate-300">{{ user.email }}</span>
                  <span v-if="user.email && user.phone" class="text-slate-600">•</span>
                  <span v-if="user.phone" class="text-slate-400 font-mono">{{ user.phone }}</span>
                  <span class="text-slate-600">•</span>
                  <span class="text-violet-400 font-bold drop-shadow-[0_0_4px_rgba(167,139,250,0.5)]">{{ user.xp_points }} XP</span>
                  <span class="text-slate-600">•</span>
                  <span class="font-mono text-indigo-400 text-[9px] sm:text-[10px] bg-indigo-500/10 px-1.5 py-0.5 rounded">REF: {{ user.referral_code || '—' }}</span>
                </p>
                
                <!-- Health Bar Widget -->
                <div class="mt-2.5 flex items-center gap-2">
                  <div class="flex items-center gap-1.5 shrink-0">
                    <span class="text-xs" :class="{'animate-pulse drop-shadow-[0_0_5px_rgba(244,63,94,0.8)]': user.health <= 20}">❤️</span>
                    <span class="text-[10px] font-bold" 
                          :class="user.health > 50 ? 'text-emerald-400' : (user.health > 20 ? 'text-amber-400' : 'text-rose-400')">
                      {{ user.health ?? 100 }}/100 Health
                    </span>
                  </div>
                  <div class="w-24 sm:w-32 h-1.5 bg-[#040612] rounded-full overflow-hidden border border-white/5 shadow-inner">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="user.health > 50 ? 'bg-gradient-to-r from-emerald-500 to-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.5)]' : (user.health > 20 ? 'bg-gradient-to-r from-amber-500 to-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]' : 'bg-gradient-to-r from-rose-500 to-rose-400 shadow-[0_0_8px_rgba(244,63,94,0.5)] animate-pulse')"
                         :style="{ width: (user.health ?? 100) + '%' }">
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Quick CTA Buttons -->
            <div class="flex gap-2 shrink-0 flex-wrap mt-4 sm:mt-0">
              <Link href="/tasks" class="btn-neon btn-primary px-4 py-2.5 text-xs text-white rounded-2xl gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Earn Now
              </Link>
              <button
                @click="showSpinWheel = true"
                class="btn-neon btn-amber px-4 py-2.5 text-xs text-white rounded-2xl gap-1.5"
                :class="{ 'opacity-50': !canSpinLocal }"
              >
                🎰 Spin Wheel
                <span v-if="canSpinLocal" class="w-1.5 h-1.5 rounded-full bg-amber-300 animate-pulse-neon"></span>
              </button>
            </div>
          </div>

          <!-- Dynamic XP Progress Bar -->
          <div class="mt-5 space-y-1.5">
            <div class="flex items-center justify-between text-[11px] font-semibold">
              <span class="text-slate-400">Level {{ user.level }} Progress ({{ user.xp_points }} / {{ user.next_level_xp }} XP)</span>
              <Link href="/levels" class="text-indigo-400 hover:text-indigo-300 hover:underline flex items-center gap-1 font-bold">
                <span>{{ xpPercent }}% → Level {{ user.next_level_number }}</span>
                <span class="text-xs">➔</span>
              </Link>
            </div>
            <Link href="/levels" class="block group cursor-pointer" title="View Level Roadmap & Perks">
              <div class="progress-track group-hover:ring-1 group-hover:ring-indigo-500/50 transition-all">
                <div
                  class="progress-fill bg-gradient-to-r from-indigo-500 via-violet-500 to-cyan-400"
                  :style="{ width: xpPercent + '%' }"
                ></div>
              </div>
            </Link>
          </div>
        </div>
      </div>

      <!-- ── 3-Tier Balance Grid ──────────────────────────────────────── -->
      <div class="grid grid-cols-3 gap-3">
        <!-- Main Balance -->
        <div class="glass-card p-4 rounded-2xl border border-emerald-500/20 card-hover relative overflow-hidden">
          <div class="absolute top-0 right-0 w-20 h-20 bg-emerald-500/5 rounded-full blur-2xl"></div>
          <div class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider mb-1 flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse-neon"></span>MAIN
          </div>
          <AnimatedNumber :value="user.main_balance" :decimals="0" class="text-2xl font-black text-white" />
          <div class="text-[10px] text-slate-400 mt-0.5">Withdrawable</div>
        </div>

        <!-- Pending Balance -->
        <div class="glass-card p-4 rounded-2xl border border-amber-500/20 card-hover relative overflow-hidden">
          <div class="absolute top-0 right-0 w-20 h-20 bg-amber-500/5 rounded-full blur-2xl"></div>
          <div class="text-[10px] font-bold text-amber-400 uppercase tracking-wider mb-1 flex items-center gap-1">
            ⏳ PENDING
          </div>
          <AnimatedNumber :value="user.pending_balance" :decimals="0" class="text-2xl font-black text-white" />
          <div class="text-[10px] text-slate-400 mt-0.5">24h hold</div>
        </div>

        <!-- Locked Balance -->
        <div class="glass-card p-4 rounded-2xl border border-violet-500/20 card-hover relative overflow-hidden">
          <div class="absolute top-0 right-0 w-20 h-20 bg-violet-500/5 rounded-full blur-2xl"></div>
          <div class="text-[10px] font-bold text-violet-400 uppercase tracking-wider mb-1 flex items-center gap-1">
            🔒 LOCKED
          </div>
          <AnimatedNumber :value="user.locked_balance" :decimals="0" class="text-2xl font-black text-white" />
          <div class="text-[10px] text-slate-400 mt-0.5">Referral bonus</div>
        </div>
      </div>

      <!-- ── Task Statistics Overview Grid ───────────────────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- Approved Tasks -->
        <div class="glass-card p-3.5 rounded-2xl border border-emerald-500/15 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/15 flex items-center justify-center text-emerald-400 text-lg font-bold shrink-0">
            ✓
          </div>
          <div>
            <div class="text-lg font-black text-white">{{ completedTasksCount }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Approved Tasks</div>
          </div>
        </div>

        <!-- Pending Tasks -->
        <div class="glass-card p-3.5 rounded-2xl border border-amber-500/15 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-500/15 flex items-center justify-center text-amber-400 text-lg font-bold shrink-0">
            ⏳
          </div>
          <div>
            <div class="text-lg font-black text-white">{{ pendingTasksCount }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Under Review</div>
          </div>
        </div>

        <!-- Rejected Tasks -->
        <div class="glass-card p-3.5 rounded-2xl border border-rose-500/15 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-rose-500/15 flex items-center justify-center text-rose-400 text-lg font-bold shrink-0">
            ✕
          </div>
          <div>
            <div class="text-lg font-black text-white">{{ rejectedTasksCount }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Rejected Tasks</div>
          </div>
        </div>

        <!-- Total Available Active Tasks -->
        <div class="glass-card p-3.5 rounded-2xl border border-indigo-500/15 flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-indigo-500/15 flex items-center justify-center text-indigo-400 text-lg font-bold shrink-0">
            ⚡
          </div>
          <div>
            <div class="text-lg font-black text-white">{{ totalActiveTasks }}</div>
            <div class="text-[10px] text-slate-400 font-medium">Available Microtasks</div>
          </div>
        </div>
      </div>

      <!-- ── Welcome Bonus Progress ────────────────────────────────────── -->
      <div v-if="!user.has_claimed_welcome_bonus" class="glass-card p-5 rounded-2xl border border-amber-500/30 bg-amber-500/5 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex items-center gap-3 mb-2">
          <span class="text-2xl animate-bounce">🎁</span>
          <div>
            <h2 class="text-sm font-bold text-white">Unlock Your Welcome Bonus!</h2>
            <p class="text-[11px] text-slate-300">Complete all active tasks to unlock your <span class="text-amber-400 font-bold">{{ user.welcome_bonus_amount }}</span> pts bonus.</p>
          </div>
        </div>
        <div class="mt-3">
          <div class="flex justify-between text-[10px] font-semibold mb-1">
            <span class="text-slate-300">Progress</span>
            <span class="text-amber-400">{{ completedTasksCount }} / {{ totalActiveTasks }} Tasks Completed</span>
          </div>
          <div class="progress-track w-full bg-black/40 h-2">
            <div
              class="progress-fill bg-gradient-to-r from-amber-500 to-yellow-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]"
              :style="{ width: Math.min(100, (totalActiveTasks > 0 ? (completedTasksCount / totalActiveTasks * 100) : 0)) + '%' }"
            ></div>
          </div>
        </div>
      </div>

      <!-- ── Quick Actions Row (Balanced 6-Card Grid) ───────────────── -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <Link href="/tasks" class="glass-card p-4 rounded-2xl border border-indigo-500/15 card-hover text-center group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">⚡</div>
          <div class="text-xs font-bold text-white">Micro Tasks</div>
          <div class="text-[10px] text-slate-400">Earn points</div>
        </Link>
        <Link href="/tasks#offerwall" class="glass-card p-4 rounded-2xl border border-cyan-500/15 card-hover text-center group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🏆</div>
          <div class="text-xs font-bold text-white">Offerwalls</div>
          <div class="text-[10px] text-slate-400">Big rewards</div>
        </Link>
        <Link href="/referral-contest" class="glass-card p-4 rounded-2xl border border-amber-500/30 bg-amber-500/10 card-hover text-center group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">🥇</div>
          <div class="text-xs font-bold text-amber-300">Top Referrer</div>
          <div class="text-[10px] text-amber-400 font-bold">Win Contest</div>
        </Link>
        <Link href="/campaigns" class="glass-card p-4 rounded-2xl border border-pink-500/15 card-hover text-center group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">📢</div>
          <div class="text-xs font-bold text-white">Advertise</div>
          <div class="text-[10px] text-slate-400">Promote link</div>
        </Link>
        <Link href="/withdraw" class="glass-card p-4 rounded-2xl border border-emerald-500/15 card-hover text-center group">
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">💸</div>
          <div class="text-xs font-bold text-white">Withdraw</div>
          <div class="text-[10px] text-slate-400">Get paid</div>
        </Link>
        <!-- 6th Slot: How to Work (YouTube Video Guide) -->
        <a 
          :href="tutorialVideoUrl || $page.props.siteSettings?.tutorial_video_url || 'https://www.youtube.com'" 
          target="_blank" 
          rel="noopener noreferrer" 
          class="glass-card p-4 rounded-2xl border border-red-500/30 bg-red-500/10 hover:border-red-500/50 hover:bg-red-500/15 card-hover text-center group cursor-pointer shadow-[0_0_15px_rgba(239,68,68,0.1)] hover:shadow-[0_0_20px_rgba(239,68,68,0.25)] transition-all"
        >
          <div class="text-2xl mb-2 group-hover:scale-110 transition-transform">▶️</div>
          <div class="text-xs font-bold text-red-400">How to Work</div>
          <div class="text-[10px] text-slate-400">Watch tutorial</div>
        </a>
      </div>

      <!-- ── Streak + Promo Row ───────────────────────────────────────── -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

        <!-- Streak Widget -->
        <div class="glass-card p-5 rounded-2xl border border-amber-500/15 flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-amber-500/15 border border-amber-500/25 flex items-center justify-center text-2xl shrink-0 animate-float">
            🔥
          </div>
          <div class="flex-1">
            <h2 class="text-sm font-bold text-white">Daily Streak</h2>
            <div class="text-xs text-slate-300 mt-0.5">{{ tasksCompletedToday }}/3 tasks today · <span class="text-amber-400 font-bold">{{ streakCount }} day streak</span></div>
            <div class="mt-2 progress-track">
              <div
                class="progress-fill bg-gradient-to-r from-amber-500 to-orange-400"
                :style="{ width: Math.min(100, Math.max(0, (tasksCompletedToday / 3 * 100))) + '%' }"
              ></div>
            </div>
          </div>
          <div v-if="streakCount >= 7" class="shrink-0">
            <button v-if="canSpinLocal" @click="showSpinWheel = true" class="badge badge-amber animate-pulse-neon">🎰 Spin!</button>
            <span v-else class="badge bg-slate-800 text-slate-400">✅ Spin Used</span>
          </div>
        </div>

        <!-- Promo Code Widget -->
        <div class="glass-card p-5 rounded-2xl border border-violet-500/15">
          <div class="flex items-center gap-2 mb-3">
            <span class="text-lg">🎟️</span>
            <h2 class="text-sm font-bold text-white">Promo Code</h2>
          </div>
          <form @submit.prevent="redeemPromo" class="flex gap-2">
            <input
              v-model="promoCode"
              type="text"
              placeholder="Enter code..."
              class="input-dark text-xs flex-1 py-2.5 px-3"
              :class="{ 'border-rose-500/50': promoError }"
              maxlength="20"
            />
            <button
              type="submit"
              class="btn-neon btn-primary text-white text-xs px-4 rounded-xl shrink-0"
              :disabled="promoLoading"
            >
              {{ promoLoading ? '...' : 'Claim' }}
            </button>
          </form>
          <p v-if="promoError" class="text-rose-400 text-[10px] mt-1.5">{{ promoError }}</p>
        </div>
      </div>

      <!-- ── Referral Card ────────────────────────────────────────────── -->
      <div class="glass-card p-5 rounded-2xl border border-indigo-500/15 relative overflow-hidden">
        <div class="absolute right-0 top-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute -left-8 -bottom-8 w-28 h-28 bg-violet-500/5 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10">
          <!-- Header -->
          <div class="flex items-center gap-2 mb-3 flex-wrap">
            <span class="text-lg">👥</span>
            <h2 class="text-sm font-bold text-white">Refer & Earn</h2>
            <Link href="/referral-contest" class="px-3 py-1 rounded-full bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/30 text-[11px] font-bold transition-all flex items-center gap-1">
              <span>🏆 Top Referrer Contest</span>
            </Link>
            <span class="badge badge-indigo ml-auto">+500 pts/referral</span>
          </div>
          <p class="text-xs text-slate-300 mb-4">Share your unique link. Earn 500 locked points per referral — unlocked when they earn 1000 pts.</p>

          <!-- Full Link Display + Copy Button -->
          <div class="glass-pill rounded-xl border border-indigo-500/25 p-1 flex items-center gap-1 mb-4">
            <div class="flex-1 min-w-0 px-3 py-2">
              <div class="text-[10px] text-slate-400 mb-0.5 font-semibold">YOUR REFERRAL LINK</div>
              <div class="text-xs font-mono text-indigo-300 truncate select-all">{{ referralUrl }}</div>
            </div>
            <button
              @click="copyReferral"
              class="shrink-0 px-4 py-2.5 rounded-lg text-xs font-bold transition-all duration-300"
              :class="copied
                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40'
                : 'bg-indigo-500/20 text-indigo-300 border border-indigo-500/40 hover:bg-indigo-500/30'"
            >
              <span v-if="copied" class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Copied!
              </span>
              <span v-else class="flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                Copy Link
              </span>
            </button>
          </div>

          <!-- Share Buttons -->
          <div class="space-y-2">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Share via</div>
            <div class="grid grid-cols-4 gap-2">
              <!-- WhatsApp -->
              <a :href="shareWhatsApp" target="_blank" rel="noopener noreferrer"
                class="flex flex-col items-center gap-1.5 glass-pill py-3 rounded-xl border border-emerald-500/20 hover:border-emerald-500/50 hover:bg-emerald-500/10 transition-all group cursor-pointer"
              >
                <svg class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                </svg>
                <span class="text-[10px] font-semibold text-emerald-400">WhatsApp</span>
              </a>

              <!-- Telegram -->
              <a :href="shareTelegram" target="_blank" rel="noopener noreferrer"
                class="flex flex-col items-center gap-1.5 glass-pill py-3 rounded-xl border border-sky-500/20 hover:border-sky-500/50 hover:bg-sky-500/10 transition-all group cursor-pointer"
              >
                <svg class="w-5 h-5 text-sky-400 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                </svg>
                <span class="text-[10px] font-semibold text-sky-400">Telegram</span>
              </a>

              <!-- Facebook -->
              <a :href="shareFacebook" target="_blank" rel="noopener noreferrer"
                class="flex flex-col items-center gap-1.5 glass-pill py-3 rounded-xl border border-blue-500/20 hover:border-blue-500/50 hover:bg-blue-500/10 transition-all group cursor-pointer"
              >
                <svg class="w-5 h-5 text-blue-400 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                <span class="text-[10px] font-semibold text-blue-400">Facebook</span>
              </a>

              <!-- X (Twitter) -->
              <a :href="shareX" target="_blank" rel="noopener noreferrer"
                class="flex flex-col items-center gap-1.5 glass-pill py-3 rounded-xl border border-slate-500/20 hover:border-slate-400/50 hover:bg-slate-500/10 transition-all group cursor-pointer"
              >
                <svg class="w-5 h-5 text-slate-300 group-hover:scale-110 transition-transform" viewBox="0 0 24 24" fill="currentColor">
                  <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                </svg>
                <span class="text-[10px] font-semibold text-slate-300">X</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Referral History -->
        <div class="mt-5 pt-4 border-t border-white/5 space-y-3 relative z-10">
          <div class="section-header mb-3">
            <h3 class="section-title">👥 Referral History</h3>
            <div class="section-header-line"></div>
            <span class="badge badge-indigo shrink-0">{{ totalReferrals }} referred</span>
          </div>

          <!-- Empty State -->
          <div v-if="referralsLoading && referrals.length === 0" class="text-center py-6">
            <p class="text-xs text-slate-400 animate-pulse">Loading history...</p>
          </div>
          <div v-else-if="referrals.length === 0" class="text-center py-6">
            <div class="text-3xl mb-2">🔗</div>
            <p class="text-xs text-slate-400">No referrals yet. Share your link to start earning!</p>
          </div>

          <!-- Referral Items -->
          <div v-else class="space-y-2">
            <div v-for="ref in referrals" :key="ref.id"
              class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.02] border border-white/[0.05] hover:border-indigo-500/20 transition-all"
            >
              <!-- Avatar -->
              <div class="w-9 h-9 rounded-xl bg-indigo-500/20 flex items-center justify-center text-sm font-black text-indigo-400 shrink-0">
                {{ (ref.referred_user?.name || '?')[0].toUpperCase() }}
              </div>

              <!-- Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5">
                  <span class="text-xs font-bold text-white truncate">{{ ref.referred_user?.name || 'User' }}</span>
                  <!-- Status Badge -->
                  <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                    :class="{
                      'bg-emerald-500/20 text-emerald-400': ref.status === 'unlocked' || ref.status === 'claimed',
                      'bg-amber-500/20 text-amber-400': ref.status === 'locked',
                    }"
                  >{{ ref.status === 'locked' ? '🔒 Locked' : ref.status === 'unlocked' ? '✅ Unlocked' : '💰 Claimed' }}</span>
                </div>
                <div class="text-[10px] text-slate-400 flex items-center gap-1.5">
                  <span>{{ ref.tasks_completed }}/5 tasks</span>
                  <span class="text-slate-600">·</span>
                  <span>Joined {{ ref.joined_at }}</span>
                </div>
                <!-- Earn progress bar -->
                <div class="mt-1.5 progress-track w-full">
                  <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-400"
                    :style="{ width: Math.min(100, (ref.earned_so_far / ref.target_amount) * 100) + '%' }"
                  ></div>
                </div>
                <div class="text-[9px] text-slate-400 mt-0.5">{{ ref.earned_so_far }} / {{ ref.target_amount }} pts completed</div>
              </div>

              <!-- Reward Badge -->
              <div class="shrink-0 text-right">
                <div class="text-xs font-black text-violet-300">+{{ ref.locked_reward }}</div>
                <div class="text-[9px] text-slate-400">pts bonus</div>
              </div>
            </div>

            <!-- View All / Load More Link -->
            <div v-if="totalReferrals > 0" class="mt-4 text-center">
              <Link 
                href="/reffer" 
                class="btn-neon btn-primary px-4 py-2 text-xs text-white rounded-xl inline-block"
              >
                View All Referrals ({{ totalReferrals }})
              </Link>
            </div>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SpinWheelModal from '@/Components/SpinWheelModal.vue';
import AnimatedNumber from '@/Components/AnimatedNumber.vue';
import axios from 'axios';

const page = usePage();

const props = defineProps({
  user:                Object,
  streakCount:         Number,
  tasksCompletedToday: Number,
  completedTasksCount: Number,
  pendingTasksCount:   Number,
  rejectedTasksCount:  Number,
  totalActiveTasks:    Number,
  canSpin:             Boolean,
  tutorialVideoUrl:    String,
});

const referrals = ref([]);
const referralsLoading = ref(true);
const referralCurrentPage = ref(1);
const referralLastPage = ref(1);
const totalReferrals = ref(0);

const loadReferrals = async (page = 1) => {
  referralsLoading.value = true;
  try {
    const res = await axios.get(`/referrals/history?page=${page}`);
    if (page === 1) {
      referrals.value = res.data.data;
    } else {
      referrals.value = [...referrals.value, ...res.data.data];
    }
    referralCurrentPage.value = res.data.current_page;
    referralLastPage.value = res.data.last_page;
    totalReferrals.value = res.data.total;
  } catch (err) {
    console.error('Failed to load referrals', err);
  } finally {
    referralsLoading.value = false;
  }
};

onMounted(() => {
  loadReferrals(1);
});

const showSpinWheel = ref(false);
const promoCode     = ref('');
const promoError    = ref('');
const promoSuccess  = ref('');
const promoLoading  = ref(false);
const copied        = ref(false);

const xpPercent = computed(() => {
  const currentXp = props.user?.xp_points || 0;
  const minXp     = props.user?.current_level_xp || 0;
  const maxXp     = props.user?.next_level_xp || 500;
  
  if (maxXp <= minXp) return 100;
  const percent = Math.floor(((currentXp - minXp) / (maxXp - minXp)) * 100);
  return Math.min(100, Math.max(0, percent));
});

const canSpinLocal = ref(props.canSpin || false);

watch(() => props.canSpin, (newVal) => {
  canSpinLocal.value = newVal;
});

const redeemPromo = async () => {
  if (!promoCode.value.trim()) return;
  promoError.value   = '';
  promoLoading.value = true;

  router.post('/promo/redeem', { code: promoCode.value }, {
    preserveScroll: true,
    onSuccess: () => {
      promoCode.value = '';
    },
    onError: (errors) => {
      promoError.value = errors.promo_code || 'Invalid or expired code.';
    },
    onFinish: () => { promoLoading.value = false; },
  });
};

const referralUrl = computed(() =>
  `${window.location.origin}/register?ref=${props.user?.referral_code || ''}`
);

const shareText = computed(() =>
  `🚀 Join Easytsk V2 and start earning! Use my referral link to get bonus points: ${referralUrl.value}`
);

const shareWhatsApp = computed(() =>
  `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText.value)}`
);

const shareTelegram = computed(() =>
  `https://t.me/share/url?url=${encodeURIComponent(referralUrl.value)}&text=${encodeURIComponent('🚀 Join Easytsk V2 and earn rewards!')}`
);

const shareFacebook = computed(() =>
  `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(referralUrl.value)}`
);

const shareX = computed(() =>
  `https://twitter.com/intent/tweet?text=${encodeURIComponent(shareText.value)}`
);

const copyReferral = () => {
  navigator.clipboard.writeText(referralUrl.value).then(() => {
    copied.value = true;
    setTimeout(() => { copied.value = false; }, 2500);
  });
};

const onSpinComplete = (data) => {
  canSpinLocal.value = false;
  if (data && data.new_balance !== undefined) {
    props.user.main_balance = data.new_balance;
    if (page.props.auth?.user) {
      page.props.auth.user.main_balance = data.new_balance;
    }
  }
  setTimeout(() => {
    showSpinWheel.value = false;
    router.reload({ preserveScroll: true });
  }, 2500);
};
</script>
