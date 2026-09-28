<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- Header & Gateway Balance Banner -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
              <span>📱</span> SMS Campaign & Marketing
            </h1>
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
              :class="isEnabled ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'"
            >
              {{ isEnabled ? 'Gateway Live' : 'Gateway Disabled' }}
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            ফিল্টার করে নির্দিষ্ট অডিয়েন্সকে টাস্ক আপডেট, উইথড্র রিমাইন্ডার ও রি-এনগেজমেন্ট এসএমএস ক্যাম্পেইন পরিচালনা করুন।
          </p>
        </div>

        <!-- Live Balance Widget -->
        <div class="flex items-center gap-3 bg-slate-900/90 border border-slate-800 rounded-2xl p-3 px-4 shadow-xl shrink-0">
          <div class="w-10 h-10 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-lg shrink-0">
            💳
          </div>
          <div>
            <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider">SMS Gateway Balance</div>
            <div class="text-base font-black text-emerald-400 font-mono">
              {{ balance !== null ? `${balance} BDT` : 'N/A' }}
            </div>
          </div>
          <Link 
            :href="`${adminPath}/sms-campaign`" 
            class="ml-2 p-2 hover:bg-slate-800 rounded-xl text-slate-400 hover:text-white transition cursor-pointer"
            title="Refresh Balance"
          >
            🔄
          </Link>
        </div>
      </div>

      <!-- Quick Stats Counter Row -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60">
          <div class="text-[10px] text-slate-400 font-semibold uppercase">Total Contacts</div>
          <div class="text-xl font-black text-white mt-1 font-mono">{{ audienceCounts.all }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">Valid BD phone numbers</div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60">
          <div class="text-[10px] text-slate-400 font-semibold uppercase">Verified Accounts</div>
          <div class="text-xl font-black text-emerald-400 mt-1 font-mono">{{ audienceCounts.verified_only }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">Passed OTP qualification</div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60">
          <div class="text-[10px] text-slate-400 font-semibold uppercase">Inactive (3+ Days)</div>
          <div class="text-xl font-black text-amber-400 mt-1 font-mono">{{ audienceCounts.inactive_3d }}</div>
          <div class="text-[10px] text-slate-500 mt-0.5">Prime for re-engagement</div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60">
          <div class="text-[10px] text-slate-400 font-semibold uppercase truncate">
            {{ form.filter_type === 'balance_min' ? `Balance (≥ ${form.min_balance || 0} Pts)` : 'Balance (≥ 500 Pts)' }}
          </div>
          <div class="text-xl font-black text-indigo-400 mt-1 font-mono flex items-center gap-2">
            <span>{{ form.filter_type === 'balance_min' ? dynamicBalanceCount : audienceCounts.balance_gt_500 }}</span>
            <span v-if="isLoadingCount && form.filter_type === 'balance_min'" class="w-3 h-3 border-2 border-indigo-400 border-t-transparent rounded-full animate-spin"></span>
          </div>
          <div class="text-[10px] text-slate-500 mt-0.5 truncate">
            {{ form.filter_type === 'balance_min' ? 'Live dynamic audience' : 'Near payout threshold' }}
          </div>
        </div>
      </div>

      <!-- Main Campaign Builder Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Campaign Setup & Composer -->
        <div class="lg:col-span-2 glass-card p-6 sm:p-7 rounded-3xl border border-slate-800 space-y-6">
          <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span>✍️</span> New SMS Campaign
            </h2>
            <span class="text-xs text-slate-400 font-mono">Sender ID: <strong class="text-indigo-400">{{ senderId }}</strong></span>
          </div>

          <form @submit.prevent="openConfirmModal" class="space-y-6">
            <!-- 1. Select Target Audience Filter -->
            <div class="space-y-2.5">
              <label class="block text-xs font-bold text-slate-200">
                1️⃣ Target Audience (প্রাপক নির্বাচন করুন)
              </label>

              <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <button
                  type="button"
                  @click="form.filter_type = 'all'"
                  class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="form.filter_type === 'all' ? 'bg-indigo-600/20 border-indigo-500 text-white shadow-lg shadow-indigo-500/10' : 'bg-slate-950/70 border-slate-800 text-slate-400 hover:border-slate-700'"
                >
                  <div class="text-xs font-bold">👥 All Users</div>
                  <div class="text-lg font-black text-white font-mono mt-1">{{ audienceCounts.all }}</div>
                  <div class="text-[10px] text-slate-500 mt-1">সব ইউজার</div>
                </button>

                <button
                  type="button"
                  @click="form.filter_type = 'inactive_3d'"
                  class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="form.filter_type === 'inactive_3d' ? 'bg-amber-600/20 border-amber-500 text-white shadow-lg shadow-amber-500/10' : 'bg-slate-950/70 border-slate-800 text-slate-400 hover:border-slate-700'"
                >
                  <div class="text-xs font-bold">⏳ Inactive 3+ Days</div>
                  <div class="text-lg font-black text-white font-mono mt-1">{{ audienceCounts.inactive_3d }}</div>
                  <div class="text-[10px] text-slate-500 mt-1">৩ দিন অনুপস্থিত</div>
                </button>

                <button
                  type="button"
                  @click="form.filter_type = 'inactive_7d'"
                  class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="form.filter_type === 'inactive_7d' ? 'bg-rose-600/20 border-rose-500 text-white shadow-lg shadow-rose-500/10' : 'bg-slate-950/70 border-slate-800 text-slate-400 hover:border-slate-700'"
                >
                  <div class="text-xs font-bold">💤 Inactive 7+ Days</div>
                  <div class="text-lg font-black text-white font-mono mt-1">{{ audienceCounts.inactive_7d }}</div>
                  <div class="text-[10px] text-slate-500 mt-1">৭ দিন অনুপস্থিত</div>
                </button>

                <button
                  type="button"
                  @click="selectBalanceFilter"
                  class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer relative overflow-hidden group"
                  :class="form.filter_type === 'balance_min' ? 'bg-emerald-600/20 border-emerald-500 text-white shadow-lg shadow-emerald-500/10 ring-1 ring-emerald-500/30' : 'bg-slate-950/70 border-slate-800 text-slate-400 hover:border-slate-700'"
                >
                  <div class="flex items-center justify-between w-full">
                    <div class="text-xs font-bold truncate">💰 Balance ≥ {{ form.min_balance }} Pts</div>
                    <span v-if="form.filter_type === 'balance_min'" class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse shrink-0"></span>
                  </div>
                  <div class="text-lg font-black text-white font-mono mt-1 flex items-center gap-2">
                    <span>{{ dynamicBalanceCount }}</span>
                    <span v-if="isLoadingCount" class="inline-block w-3.5 h-3.5 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></span>
                  </div>
                  <div class="text-[10px] text-slate-500 mt-1 flex items-center justify-between">
                    <span>কাস্টম ব্যালেন্স ফিল্টার</span>
                    <span v-if="form.filter_type === 'balance_min'" class="text-emerald-400 text-[9px] font-bold uppercase">Active</span>
                  </div>
                </button>

                <button
                  type="button"
                  @click="form.filter_type = 'today_new'"
                  class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="form.filter_type === 'today_new' ? 'bg-cyan-600/20 border-cyan-500 text-white shadow-lg shadow-cyan-500/10' : 'bg-slate-950/70 border-slate-800 text-slate-400 hover:border-slate-700'"
                >
                  <div class="text-xs font-bold">🆕 Today's New</div>
                  <div class="text-lg font-black text-white font-mono mt-1">{{ audienceCounts.today_new }}</div>
                  <div class="text-[10px] text-slate-500 mt-1">আজকের নতুন একাউন্ট</div>
                </button>

                <button
                  type="button"
                  @click="form.filter_type = 'verified_only'"
                  class="p-3.5 rounded-2xl border text-left transition flex flex-col justify-between cursor-pointer"
                  :class="form.filter_type === 'verified_only' ? 'bg-purple-600/20 border-purple-500 text-white shadow-lg shadow-purple-500/10' : 'bg-slate-950/70 border-slate-800 text-slate-400 hover:border-slate-700'"
                >
                  <div class="text-xs font-bold">⭐ Verified Only</div>
                  <div class="text-lg font-black text-white font-mono mt-1">{{ audienceCounts.verified_only }}</div>
                  <div class="text-[10px] text-slate-500 mt-1">ওটিপি ভেরিফাইড ইউজার</div>
                </button>
              </div>

              <!-- Dynamic Min Balance Control Box (Responsive) -->
              <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform opacity-0 -translate-y-2 scale-98"
                enter-to-class="transform opacity-100 translate-y-0 scale-100"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="transform opacity-100 translate-y-0 scale-100"
                leave-to-class="transform opacity-0 -translate-y-2 scale-98"
              >
                <div 
                  v-if="form.filter_type === 'balance_min'" 
                  class="mt-3 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-900/90 to-teal-950/30 border border-emerald-500/40 shadow-xl space-y-3 relative overflow-hidden"
                >
                  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-emerald-500/20 pb-3">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-sm shrink-0">
                        ⚙️
                      </div>
                      <div>
                        <label class="text-xs font-bold text-white flex items-center gap-1.5">
                          Dynamic Point Threshold (ব্যালেন্স পয়েন্ট নির্ধারণ)
                        </label>
                        <p class="text-[11px] text-emerald-200/80">এডমিন তার প্রয়োজন মতো যেকোনো পয়েন্ট সীমা বসাতে পারেন</p>
                      </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                      <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">টার্গেট প্রাপক:</span>
                      <span class="text-xs font-black font-mono px-2.5 py-1 rounded-lg bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 flex items-center gap-1.5">
                        <span v-if="isLoadingCount" class="w-2.5 h-2.5 border-2 border-emerald-400 border-t-transparent rounded-full animate-spin"></span>
                        {{ dynamicBalanceCount }} Users
                      </span>
                    </div>
                  </div>

                  <!-- Responsive Input & Quick Preset Buttons -->
                  <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
                    <!-- Numeric Input with Pts badge -->
                    <div class="sm:col-span-5 relative">
                      <input 
                        v-model.number="form.min_balance" 
                        type="number" 
                        min="0" 
                        step="50" 
                        placeholder="যেমন: 500" 
                        class="w-full pl-3.5 pr-14 py-2.5 bg-slate-950/90 border border-emerald-500/40 focus:border-emerald-400 rounded-xl text-sm font-mono font-bold text-white placeholder-slate-600 focus:outline-none focus:ring-1 focus:ring-emerald-400"
                      />
                      <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-emerald-400 font-mono pointer-events-none">
                        Pts
                      </span>
                    </div>

                    <!-- Quick Preset Buttons (wrap nicely on mobile) -->
                    <div class="sm:col-span-7 flex items-center gap-1.5 flex-wrap">
                      <span class="text-[10px] text-slate-400 font-medium mr-1 hidden lg:inline">দ্রুত বাটন:</span>
                      <button 
                        v-for="preset in [100, 250, 500, 1000, 2000, 5000]" 
                        :key="preset"
                        type="button" 
                        @click="setBalancePreset(preset)"
                        class="px-2.5 py-1.5 rounded-xl text-xs font-mono font-bold transition cursor-pointer flex-1 sm:flex-initial text-center"
                        :class="form.min_balance === preset ? 'bg-emerald-500 text-slate-950 font-black shadow-md shadow-emerald-500/20 ring-2 ring-emerald-300' : 'bg-slate-950/80 text-slate-300 hover:text-white border border-slate-800 hover:border-emerald-500/50 hover:bg-emerald-950/30'"
                      >
                        {{ preset >= 1000 ? (preset / 1000) + 'k' : preset }} Pts
                      </button>
                    </div>
                  </div>

                  <div class="flex items-center gap-2 text-[11px] text-slate-400 pt-1">
                    <span class="text-emerald-400 shrink-0">💡</span>
                    <span>
                      যাদের মেইন ব্যালেন্স 
                      <strong class="text-emerald-300 font-mono">≥ {{ form.min_balance || 0 }} Pts</strong> 
                      এবং ভ্যালিড ফোন নাম্বার আছে, কেবল তারাই এই ক্যাম্পেইনে মেসেজ পাবে।
                    </span>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Campaign Title (Optional) -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">
                Campaign Title (ঐচ্ছিক রেফারেন্স ট্যাগ)
              </label>
              <input 
                v-model="form.title" 
                type="text" 
                placeholder="যেমন: Weekly Task Blast, Payout Reminder" 
                class="w-full px-4 py-2.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-600 focus:border-indigo-500"
              />
            </div>

            <!-- 2. Message Composer & Quick Templates -->
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <label class="block text-xs font-bold text-slate-200">
                  2️⃣ SMS Content (মেসেজ লিখুন)
                </label>
                <span class="text-[11px] font-mono" :class="isUnicode ? 'text-amber-400' : 'text-slate-400'">
                  {{ isUnicode ? '🌐 Unicode/বাংলা' : '🔤 English/ASCII' }}
                </span>
              </div>

              <!-- Quick Template Insertion Chips -->
              <div class="flex items-center gap-1.5 flex-wrap pb-1">
                <span class="text-[10px] text-slate-500 mr-1">Quick Templates:</span>
                <button 
                  v-for="tpl in quickTemplates" 
                  :key="tpl.label"
                  type="button" 
                  @click="form.message = tpl.text"
                  class="px-2.5 py-1 bg-slate-950/80 hover:bg-indigo-900/30 border border-slate-800 hover:border-indigo-500/40 rounded-lg text-[10px] font-medium text-slate-300 hover:text-indigo-200 transition cursor-pointer"
                >
                  {{ tpl.label }}
                </button>
              </div>

              <textarea 
                v-model="form.message" 
                rows="4" 
                required
                placeholder="EasyTsk: New high-paying tasks available! Complete now: easytsk.com"
                class="w-full px-4 py-3 bg-slate-950/90 border border-slate-800 focus:border-indigo-500 rounded-2xl text-xs text-white placeholder-slate-600 leading-relaxed font-sans"
              ></textarea>

              <!-- Real-time Spam Word Detection Warning -->
              <div v-if="detectedSpamWords.length > 0" class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs flex items-start gap-2">
                <span class="text-base shrink-0">⚠️</span>
                <div>
                  <div class="font-bold">স্প্যাম ফিল্টার সতর্কতা:</div>
                  <p class="text-[11px] text-amber-200/90 mt-0.5">
                    মেসেজে <strong>"{{ detectedSpamWords.join('", "') }}"</strong> শব্দটি রয়েছে। বিটিআরসি ও বাল্ক এসএমএস গেটওয়ে এটি ব্লক করতে পারে। নিরাপদ ডেলিভারির জন্য বিকল্প শব্দ বা স্ট্যান্ডার্ড ইংরেজি ব্যবহার করুন।
                  </p>
                </div>
              </div>

              <!-- Character & Part Counter -->
              <div class="flex items-center justify-between text-[11px] text-slate-400 px-1 pt-1 font-mono">
                <div>
                  <span>Characters: <strong>{{ charCount }}</strong></span>
                  <span class="mx-1.5 text-slate-700">|</span>
                  <span>Parts per recipient: <strong class="text-white">{{ partsPerRecipient }} SMS</strong> ({{ maxCharsPerPart }} chars/part)</span>
                </div>
                <div class="text-right">
                  <span>Total SMS Cost: <strong class="text-emerald-400">~{{ totalEstimatedCost }} BDT</strong></span>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
              <button 
                type="submit" 
                :disabled="selectedAudienceCount === 0 || !form.message || form.processing || !isEnabled"
                class="w-full py-4 px-6 bg-gradient-to-r from-emerald-600 via-teal-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-black text-sm rounded-2xl shadow-xl shadow-emerald-950/40 transition transform active:scale-98 flex items-center justify-center gap-2 cursor-pointer"
              >
                <span>🚀 Launch SMS Campaign ({{ selectedAudienceCount }} Recipients)</span>
              </button>
            </div>
          </form>
        </div>

        <!-- Right 1 Col: Live Preview & Admin Test SMS -->
        <div class="space-y-6">

          <!-- Live Phone SMS Preview Card -->
          <div class="glass-card p-5 rounded-3xl border border-slate-800 space-y-4">
            <h3 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-2">
              <span>📱</span> Mobile Screen Preview
            </h3>

            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800/80 relative space-y-3">
              <div class="flex items-center justify-between border-b border-slate-800/60 pb-2">
                <span class="text-[10px] font-bold text-indigo-400 font-mono">{{ senderId }}</span>
                <span class="text-[9px] text-slate-500 font-mono">Now • SMS</span>
              </div>
              <p class="text-xs text-slate-200 leading-relaxed break-words whitespace-pre-wrap font-sans min-h-[48px]">
                {{ form.message || 'মেসেজ লিখলে এখানে প্রিভিউ দেখতে পাবেন...' }}
              </p>
            </div>

            <!-- Cost Summary Breakdown -->
            <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800 space-y-1.5 text-[11px]">
              <div class="flex justify-between text-slate-400">
                <span>Selected Audience:</span>
                <span class="font-mono text-white font-bold">{{ selectedAudienceCount }} Users</span>
              </div>
              <div class="flex justify-between text-slate-400">
                <span>SMS Parts:</span>
                <span class="font-mono text-white">{{ partsPerRecipient }} Part(s)</span>
              </div>
              <div class="flex justify-between text-slate-400">
                <span>Rate per SMS:</span>
                <span class="font-mono text-white">~0.35 BDT</span>
              </div>
              <div class="flex justify-between items-center pt-2 border-t border-slate-800 text-xs">
                <span class="font-bold text-slate-200">Total Approx Cost:</span>
                <span class="font-mono font-black text-emerald-400">~{{ totalEstimatedCost }} BDT</span>
              </div>
            </div>
          </div>

          <!-- Admin Test Single SMS Box -->
          <div class="glass-card p-5 rounded-3xl border border-indigo-500/30 bg-indigo-950/20 space-y-3">
            <div class="flex items-center justify-between">
              <h3 class="text-xs font-bold text-indigo-300 flex items-center gap-1.5">
                <span>📲</span> Test Before Blasting
              </h3>
              <span class="text-[9px] px-2 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold uppercase">1 SMS</span>
            </div>
            <p class="text-[11px] text-indigo-200/80">
              সবাইকে পাঠানোর পূর্বে নিজের ফোনে টেস্ট এসএমএস পাঠিয়ে যাচাই করে নিন।
            </p>

            <form @submit.prevent="sendTestSms" class="space-y-2.5">
              <input 
                v-model="testForm.phone" 
                type="text" 
                required 
                placeholder="017XXXXXXXX" 
                class="w-full px-3.5 py-2.5 bg-slate-950 border border-indigo-500/30 rounded-xl text-xs font-mono text-white placeholder-slate-600 focus:border-indigo-400"
              />
              <button 
                type="submit" 
                :disabled="testForm.processing || !testForm.phone || !form.message"
                class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white text-xs font-bold rounded-xl transition cursor-pointer flex items-center justify-center gap-1.5"
              >
                <span>{{ testForm.processing ? 'Sending Test...' : 'Send Test SMS to My Number' }}</span>
              </button>
            </form>
          </div>

        </div>

      </div>

      <!-- Campaign History Table -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span>📜</span> Campaign History & Logs
            </h2>
            <p class="text-xs text-slate-500 mt-0.5">পূর্বের সকল বাল্ক ক্যাম্পেইনের হিস্টরি ও ডেলিভারি স্ট্যাটাস</p>
          </div>
          <span class="badge badge-indigo">{{ campaigns.total || 0 }} Campaigns</span>
        </div>

        <div v-if="!campaigns.data || campaigns.data.length === 0" class="text-center py-10">
          <div class="text-3xl mb-2">📨</div>
          <p class="text-sm font-bold text-white mb-1">কোনো পূর্বের ক্যাম্পেইন পাওয়া যায়নি</p>
          <p class="text-xs text-slate-500">আপনার প্রথম এসএমএস ক্যাম্পেইন পরিচালনা করলে এখানে রেকর্ড জমা হবে।</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="text-[10px] text-slate-400 uppercase border-b border-slate-800 font-mono">
                <th class="py-3 px-3">Date</th>
                <th class="py-3 px-3">Filter</th>
                <th class="py-3 px-3">Message Preview</th>
                <th class="py-3 px-3 text-center">Recipients</th>
                <th class="py-3 px-3 text-center">Sent / Failed</th>
                <th class="py-3 px-3 text-right">Cost</th>
                <th class="py-3 px-3 text-center">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="c in campaigns.data" :key="c.id" class="hover:bg-slate-900/50 transition">
                <td class="py-3 px-3 text-slate-400 font-mono whitespace-nowrap">
                  {{ new Date(c.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                </td>
                <td class="py-3 px-3">
                  <span class="px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase"
                    :class="{
                      'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30': c.filter_type === 'all',
                      'bg-amber-500/20 text-amber-300 border border-amber-500/30': c.filter_type === 'inactive_3d' || c.filter_type === 'inactive_7d',
                      'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': c.filter_type && c.filter_type.includes('balance'),
                      'bg-cyan-500/20 text-cyan-300 border border-cyan-500/30': c.filter_type === 'today_new',
                      'bg-purple-500/20 text-purple-300 border border-purple-500/30': c.filter_type === 'verified_only',
                    }"
                  >
                    {{ c.filter_type }}
                  </span>
                </td>
                <td class="py-3 px-3 max-w-xs truncate text-slate-300 font-sans" :title="c.message">
                  {{ c.message }}
                </td>
                <td class="py-3 px-3 text-center font-mono font-bold text-white">
                  {{ c.recipient_count }}
                </td>
                <td class="py-3 px-3 text-center font-mono">
                  <span class="text-emerald-400 font-bold">{{ c.sent_count }}</span>
                  <span class="text-slate-600 mx-1">/</span>
                  <span :class="c.failed_count > 0 ? 'text-rose-400 font-bold' : 'text-slate-500'">{{ c.failed_count }}</span>
                </td>
                <td class="py-3 px-3 text-right font-mono text-emerald-400">
                  {{ c.cost_estimate }} ৳
                </td>
                <td class="py-3 px-3 text-center">
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                    :class="{
                      'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': c.status === 'completed',
                      'bg-amber-500/20 text-amber-300 border border-amber-500/30': c.status === 'partial',
                      'bg-rose-500/20 text-rose-300 border border-rose-500/30': c.status === 'failed',
                    }"
                  >
                    {{ c.status }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Confirmation Modal Before Blasting -->
      <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-indigo-500/40 bg-slate-900 shadow-2xl space-y-5">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-2xl shrink-0">
              ⚠️
            </div>
            <div>
              <h3 class="text-base font-bold text-white">Confirm Campaign Dispatch</h3>
              <p class="text-xs text-slate-400 mt-0.5">অনুগ্রহ করে তথ্যগুলো যাচাই করে নিশ্চিত করুন।</p>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
              <span>Audience Target:</span>
              <span class="font-bold text-white uppercase">
                {{ form.filter_type === 'balance_min' ? `Balance ≥ ${form.min_balance || 0} Pts` : form.filter_type }}
              </span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Total Recipients:</span>
              <span class="font-bold text-indigo-400 font-mono">{{ selectedAudienceCount }} Contacts</span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Estimated Cost:</span>
              <span class="font-bold text-emerald-400 font-mono">~{{ totalEstimatedCost }} BDT</span>
            </div>
          </div>

          <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800 text-[11px] text-slate-300 leading-relaxed font-sans max-h-24 overflow-y-auto">
            "{{ form.message }}"
          </div>

          <div class="flex items-center gap-3 pt-2">
            <button 
              type="button" 
              @click="showConfirmModal = false"
              class="w-1/2 py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl transition cursor-pointer"
            >
              Cancel (বাতিল)
            </button>
            <button 
              type="button" 
              @click="submitCampaign"
              :disabled="form.processing"
              class="w-1/2 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs rounded-xl shadow-lg shadow-emerald-600/20 transition cursor-pointer"
            >
              <span>{{ form.processing ? 'Dispatching...' : 'Yes, Send Now 🚀' }}</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'secret-panel'));

const props = defineProps({
  balance: [Number, String],
  balanceMessage: String,
  isConfigured: Boolean,
  isEnabled: Boolean,
  senderId: String,
  audienceCounts: {
    type: Object,
    default: () => ({ all: 0, inactive_3d: 0, inactive_7d: 0, balance_gt_500: 0, today_new: 0, verified_only: 0 }),
  },
  sampleContacts: {
    type: Array,
    default: () => [],
  },
  campaigns: {
    type: Object,
    default: () => ({ data: [] }),
  },
  adminPhone: String,
});

const showConfirmModal = ref(false);

const form = useForm({
  filter_type: 'all',
  min_balance: 500,
  title: '',
  message: '',
});

const testForm = useForm({
  phone: props.adminPhone || '',
  message: '',
});

const dynamicBalanceCount = ref(props.audienceCounts?.balance_gt_500 ?? 0);
const isLoadingCount = ref(false);
let debounceTimer = null;

const fetchBalanceAudienceCount = (minVal) => {
  clearTimeout(debounceTimer);
  isLoadingCount.value = true;
  debounceTimer = setTimeout(async () => {
    try {
      const val = minVal !== undefined && minVal !== null && minVal !== '' ? minVal : 0;
      const res = await fetch(`${adminPath.value}/sms-campaign/count?filter_type=balance_min&min_balance=${encodeURIComponent(val)}`);
      if (res.ok) {
        const data = await res.json();
        dynamicBalanceCount.value = data.count ?? 0;
      }
    } catch (e) {
      console.error('Failed to fetch count', e);
    } finally {
      isLoadingCount.value = false;
    }
  }, 300);
};

const selectBalanceFilter = () => {
  form.filter_type = 'balance_min';
  fetchBalanceAudienceCount(form.min_balance);
};

const setBalancePreset = (preset) => {
  form.min_balance = preset;
};

watch(() => form.min_balance, (newVal) => {
  if (form.filter_type === 'balance_min') {
    fetchBalanceAudienceCount(newVal);
  }
});

const quickTemplates = [
  { label: '🚀 New Tasks', text: 'EasyTsk: New high-paying tasks are available! Complete now to earn extra points: easytsk.com/tasks' },
  { label: '💰 Payout Near', text: 'EasyTsk: You are close to your payout threshold! Earn remaining points & cash out today: easytsk.com/withdraw' },
  { label: '⏳ Miss You', text: 'EasyTsk: We miss you! Login today to claim your daily bonus & boost your streak: easytsk.com/dashboard' },
  { label: '🎁 Promo Drop', text: 'EasyTsk: Flash Promo! Use code EASY50 today to claim 50 free bonus points: easytsk.com' },
];

const selectedAudienceCount = computed(() => {
  if (form.filter_type === 'balance_min') {
    return dynamicBalanceCount.value;
  }
  return props.audienceCounts[form.filter_type] || 0;
});

const isUnicode = computed(() => {
  // If message contains non-ASCII characters (e.g. Bangla)
  return /[^\u0000-\u007f]/.test(form.message);
});

const charCount = computed(() => {
  return (form.message || '').length;
});

const maxCharsPerPart = computed(() => {
  return isUnicode.value ? 70 : 160;
});

const partsPerRecipient = computed(() => {
  if (charCount.value === 0) return 1;
  return Math.max(1, Math.ceil(charCount.value / maxCharsPerPart.value));
});

const totalEstimatedCost = computed(() => {
  const recipients = selectedAudienceCount.value;
  const parts = partsPerRecipient.value;
  return (recipients * parts * 0.35).toFixed(2);
});

// Spam keywords to watch out for
const spamKeywords = ['একাউন্ট', 'অ্যাকাউন্ট', 'লটারি', 'জুয়া'];
const detectedSpamWords = computed(() => {
  const msg = form.message || '';
  return spamKeywords.filter(word => msg.includes(word));
});

const openConfirmModal = () => {
  if (selectedAudienceCount.value === 0 || !form.message) return;
  showConfirmModal.value = true;
};

const submitCampaign = () => {
  form.post(`${adminPath.value}/sms-campaign/send`, {
    preserveScroll: true,
    onSuccess: () => {
      showConfirmModal.value = false;
      form.reset('message', 'title');
    },
    onError: () => {
      showConfirmModal.value = false;
    },
  });
};

const sendTestSms = () => {
  testForm.message = form.message;
  testForm.post(`${adminPath.value}/sms-campaign/test`, {
    preserveScroll: true,
  });
};
</script>
