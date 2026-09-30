<template>
  <AdminLayout>
    <div class="space-y-6">

      <!-- Header & Channel Switcher Tabs -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
          <div class="flex items-center gap-2.5">
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
              <span>📢</span> Marketing & Broadcast Center
            </h1>
            <span 
              class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-cyan-500/20 text-cyan-300 border border-cyan-500/30"
            >
              Omnichannel
            </span>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            ওয়েব পুশ নোটিফিকেশন (১০০% ফ্রি) এবং বাল্ক এসএমএস দিয়ে ব্যবহারকারীদের সাথে সরাসরি যোগাযোগ ও ক্যাম্পেইন পরিচালনা করুন।
          </p>
        </div>

        <!-- Channel Switcher Tabs -->
        <div class="flex items-center gap-1.5 p-1 bg-slate-900 border border-slate-800 rounded-2xl shrink-0 shadow-lg">
          <button 
            type="button"
            @click="activeChannel = 'push'"
            :class="activeChannel === 'push' ? 'bg-cyan-500 text-slate-950 font-black shadow-md shadow-cyan-500/20' : 'text-slate-400 hover:text-white font-bold'"
            class="px-3.5 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-2"
          >
            <span>🔔</span> Web Push
            <span class="px-1.5 py-0.5 rounded text-[9px] bg-slate-950/40 text-cyan-950 font-mono font-black" :class="activeChannel === 'push' ? 'bg-slate-950/20 text-slate-950' : 'bg-cyan-500/20 text-cyan-300'">
              Free
            </span>
          </button>
          <button 
            type="button"
            @click="activeChannel = 'sms'"
            :class="activeChannel === 'sms' ? 'bg-indigo-600 text-white font-black shadow-md shadow-indigo-600/20' : 'text-slate-400 hover:text-white font-bold'"
            class="px-3.5 py-2 rounded-xl text-xs transition cursor-pointer flex items-center gap-2"
          >
            <span>📱</span> SMS Campaign
          </button>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- TAB 1: WEB PUSH NOTIFICATIONS                                             -->
      <!-- ========================================================================= -->
      <div v-if="activeChannel === 'push'" class="space-y-6">

        <!-- Push Stats Row -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xl">
            <div class="flex items-center justify-between text-slate-400">
              <span class="text-[10px] font-semibold uppercase">Total Subscribers</span>
              <span class="text-xs">🔔</span>
            </div>
            <div class="text-2xl font-black text-cyan-400 mt-1 font-mono">{{ pushStats.total }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Active web push devices</div>
          </div>

          <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xl">
            <div class="flex items-center justify-between text-slate-400">
              <span class="text-[10px] font-semibold uppercase">Mobile Devices</span>
              <span class="text-xs">📱</span>
            </div>
            <div class="text-2xl font-black text-emerald-400 mt-1 font-mono">{{ pushStats.mobile }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Android & mobile browsers</div>
          </div>

          <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xl">
            <div class="flex items-center justify-between text-slate-400">
              <span class="text-[10px] font-semibold uppercase">Desktop Computers</span>
              <span class="text-xs">💻</span>
            </div>
            <div class="text-2xl font-black text-indigo-400 mt-1 font-mono">{{ pushStats.desktop }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Windows, Mac & Linux PCs</div>
          </div>

          <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60 shadow-xl">
            <div class="flex items-center justify-between text-slate-400">
              <span class="text-[10px] font-semibold uppercase">Push Cost</span>
              <span class="text-xs">⚡</span>
            </div>
            <div class="text-2xl font-black text-emerald-400 mt-1 font-mono">0.00 ৳</div>
            <div class="text-[10px] text-emerald-400/80 mt-0.5 font-bold">100% Free & Unlimited</div>
          </div>
        </div>

        <!-- Push Campaign Composer & Live Smartphone/PC Preview -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

          <!-- Left 2 Cols: Push Form -->
          <div class="lg:col-span-2 glass-card p-6 sm:p-7 rounded-3xl border border-slate-800 space-y-6 shadow-2xl">
            <div class="flex items-center justify-between border-b border-slate-800/80 pb-4">
              <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>✍️</span> Compose Web Push Broadcast
              </h2>

              <!-- Admin Test Device Status Badge -->
              <div class="flex items-center gap-2">
                <span 
                  class="px-2.5 py-1 rounded-xl text-[10px] font-bold border flex items-center gap-1.5"
                  :class="isBrowserSubscribed ? 'bg-emerald-500/10 text-emerald-300 border-emerald-500/30' : 'bg-slate-800 text-slate-400 border-slate-700'"
                >
                  <span class="w-1.5 h-1.5 rounded-full" :class="isBrowserSubscribed ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                  <span>{{ isBrowserSubscribed ? 'Admin Browser Subscribed' : 'Admin Not Subscribed' }}</span>
                </span>

                <button 
                  v-if="!isBrowserSubscribed"
                  type="button"
                  @click="subscribeAdminBrowser"
                  :disabled="isSubscribingAdmin"
                  class="px-2.5 py-1 rounded-xl bg-cyan-500/20 text-cyan-300 hover:bg-cyan-500 hover:text-slate-950 border border-cyan-500/30 text-[10px] font-bold transition cursor-pointer"
                >
                  {{ isSubscribingAdmin ? 'Subscribing...' : '🔔 Subscribe My Browser' }}
                </button>
              </div>
            </div>

            <form @submit.prevent="openPushConfirmModal" class="space-y-5">
              
              <!-- 1. Audience Filter -->
              <div class="space-y-2">
                <label class="block text-xs font-bold text-slate-200">
                  1️⃣ Target Audience (টার্গেট অডিয়েন্স নির্বাচন করুন)
                </label>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">
                  <button 
                    type="button"
                    @click="pushForm.audience_filter = 'all'"
                    class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                    :class="pushForm.audience_filter === 'all' ? 'bg-cyan-500/10 border-cyan-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <div class="text-[11px] font-bold">🌐 All Devices</div>
                    <div class="text-[10px] text-cyan-400 font-mono mt-0.5">{{ pushStats.total }} subscribers</div>
                  </button>

                  <button 
                    type="button"
                    @click="pushForm.audience_filter = 'mobile_only'"
                    class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                    :class="pushForm.audience_filter === 'mobile_only' ? 'bg-cyan-500/10 border-cyan-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <div class="text-[11px] font-bold">📱 Mobile Only</div>
                    <div class="text-[10px] text-emerald-400 font-mono mt-0.5">{{ pushStats.mobile }} devices</div>
                  </button>

                  <button 
                    type="button"
                    @click="pushForm.audience_filter = 'desktop_only'"
                    class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                    :class="pushForm.audience_filter === 'desktop_only' ? 'bg-cyan-500/10 border-cyan-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <div class="text-[11px] font-bold">💻 Desktop Only</div>
                    <div class="text-[10px] text-indigo-400 font-mono mt-0.5">{{ pushStats.desktop }} devices</div>
                  </button>

                  <button 
                    type="button"
                    @click="pushForm.audience_filter = 'today_active'"
                    class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                    :class="pushForm.audience_filter === 'today_active' ? 'bg-cyan-500/10 border-cyan-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <div class="text-[11px] font-bold">⚡ Active Today</div>
                    <div class="text-[10px] text-amber-400 font-mono mt-0.5">{{ pushStats.today_active }} devices</div>
                  </button>

                  <button 
                    type="button"
                    @click="pushForm.audience_filter = 'inactive_3d'"
                    class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                    :class="pushForm.audience_filter === 'inactive_3d' ? 'bg-cyan-500/10 border-cyan-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <div class="text-[11px] font-bold">⏳ Inactive 3+ Days</div>
                    <div class="text-[10px] text-slate-400 font-mono mt-0.5">{{ pushStats.inactive_3d }} devices</div>
                  </button>

                  <button 
                    type="button"
                    @click="pushForm.audience_filter = 'inactive_7d'"
                    class="p-2.5 rounded-xl border text-left transition cursor-pointer"
                    :class="pushForm.audience_filter === 'inactive_7d' ? 'bg-cyan-500/10 border-cyan-500 text-white' : 'bg-slate-900 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <div class="text-[11px] font-bold">💤 Inactive 7+ Days</div>
                    <div class="text-[10px] text-rose-400 font-mono mt-0.5">{{ pushStats.inactive_7d }} devices</div>
                  </button>
                </div>
              </div>

              <!-- Preset Templates -->
              <div class="space-y-1.5">
                <label class="block text-[11px] font-bold text-slate-400">Quick Templates (রেডিমেড টেমপ্লেট):</label>
                <div class="flex flex-wrap gap-1.5">
                  <button 
                    v-for="t in pushTemplates" 
                    :key="t.title"
                    type="button"
                    @click="applyPushTemplate(t)"
                    class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-slate-800 border border-slate-800 text-slate-300 text-[11px] font-semibold transition cursor-pointer"
                  >
                    {{ t.label }}
                  </button>
                </div>
              </div>

              <!-- 2. Notification Title -->
              <div class="space-y-1.5">
                <div class="flex justify-between items-center text-xs font-bold text-slate-200">
                  <label>2️⃣ Notification Title (শিরোনাম)</label>
                  <span class="text-[10px] font-mono text-slate-400">{{ pushForm.title.length }}/100</span>
                </div>
                <input 
                  v-model="pushForm.title"
                  type="text"
                  maxlength="100"
                  placeholder="e.g. 🎁 নতুন অফার যুক্ত হয়েছে! এখনই টাস্ক কমপ্লিট করুন"
                  class="w-full px-3.5 py-2.5 bg-slate-900/90 border border-slate-800 focus:border-cyan-500/50 rounded-xl text-xs text-white placeholder-slate-500 outline-none transition"
                  required
                />
              </div>

              <!-- 3. Notification Message Body -->
              <div class="space-y-1.5">
                <div class="flex justify-between items-center text-xs font-bold text-slate-200">
                  <label>3️⃣ Notification Body (মেসেজ বিস্তারিত)</label>
                  <span class="text-[10px] font-mono text-slate-400">{{ pushForm.body.length }}/250</span>
                </div>
                <textarea 
                  v-model="pushForm.body"
                  rows="3"
                  maxlength="250"
                  placeholder="e.g. Notik ও Timewall এ নতুন হাই-পেয়িং কাজ পাওয়া যাচ্ছে। সহজ কিছু টাস্ক শেষ করে কয়েন লুফে নিন!"
                  class="w-full px-3.5 py-2.5 bg-slate-900/90 border border-slate-800 focus:border-cyan-500/50 rounded-xl text-xs text-white placeholder-slate-500 outline-none transition"
                  required
                ></textarea>
              </div>

              <!-- 4. Target Click URL -->
              <div class="space-y-1.5">
                <label class="block text-xs font-bold text-slate-200">
                  4️⃣ Click Target URL (ক্লিক করলে কোন পেজ ওপেন হবে)
                </label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-500 text-xs">🔗</span>
                  <input 
                    v-model="pushForm.target_url"
                    type="text"
                    placeholder="/tasks or /dashboard"
                    class="w-full pl-8 pr-3.5 py-2.5 bg-slate-900/90 border border-slate-800 focus:border-cyan-500/50 rounded-xl text-xs text-white placeholder-slate-500 outline-none font-mono transition"
                  />
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="pt-3 border-t border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-3">
                <button 
                  type="button"
                  @click="sendTestPush"
                  :disabled="isSendingTestPush"
                  class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white font-bold text-xs transition cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                >
                  <span v-if="isSendingTestPush" class="animate-spin inline-block">⏳</span>
                  <span>{{ isSendingTestPush ? 'Sending Test...' : '🚀 Test on My Screen' }}</span>
                </button>

                <button 
                  type="submit"
                  :disabled="pushStats.total === 0 || pushForm.processing"
                  class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-slate-950 font-black text-xs transition cursor-pointer shadow-lg shadow-cyan-900/20 active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2"
                >
                  <span>📢</span>
                  <span>Broadcast Web Push Notification</span>
                </button>
              </div>

            </form>
          </div>

          <!-- Right 1 Col: Live Device Preview Widget -->
          <div class="space-y-4">
            <div class="glass-card p-5 rounded-3xl border border-slate-800 space-y-4 bg-slate-900/40">
              <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-xs font-bold text-slate-300 flex items-center gap-1.5">
                  <span>📱</span> Live Preview (ইউজার যেভাবে দেখবে)
                </h3>
                <span class="text-[9px] font-mono text-cyan-400">Android / Windows</span>
              </div>

              <!-- Smartphone Notification Preview Box -->
              <div class="p-3.5 rounded-2xl bg-slate-950 border border-slate-800/90 shadow-2xl space-y-2.5">
                <div class="flex items-center justify-between text-[10px] text-slate-400">
                  <div class="flex items-center gap-1.5">
                    <img src="/icon-192.png" alt="Icon" class="w-4 h-4 rounded-md object-contain" />
                    <span class="font-bold text-slate-200">EasyTSK</span>
                    <span>•</span>
                    <span>Just now</span>
                  </div>
                  <span class="text-slate-600">Chrome</span>
                </div>

                <div>
                  <h4 class="text-xs font-black text-white leading-tight">
                    {{ pushForm.title || '🎁 নতুন টাস্ক এলার্ট!' }}
                  </h4>
                  <p class="text-[11px] text-slate-300 mt-1 leading-relaxed">
                    {{ pushForm.body || 'এখনই টাস্ক কমপ্লিট করে ফ্রেশ পয়েন্ট আয় করে নিন।' }}
                  </p>
                </div>

                <div class="pt-2 border-t border-slate-800/60 flex items-center justify-between text-[10px]">
                  <span class="text-slate-500 font-mono">{{ pushForm.target_url || '/tasks' }}</span>
                  <span class="text-cyan-400 font-bold">Open 🚀</span>
                </div>
              </div>

              <div class="p-3 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-[11px] text-cyan-300 leading-relaxed">
                💡 <strong>কেন পুশ নোটিফিকেশন সেরা?</strong><br/>
                ব্যবহারকারী সাইট বন্ধ রাখলেও তার মোবাইল ফোনে ইন্টারনেট অন থাকা মাত্রই সাউন্ড ও ভাইব্রেশন সহ এই নোটিফিকেশনটি ভেসে উঠবে।
              </div>
            </div>

            <!-- Subscription Incentive / Bonus Reward Settings Card -->
            <div class="glass-card p-5 rounded-3xl border border-amber-500/30 bg-slate-900/60 shadow-xl space-y-4">
              <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-xs font-bold text-amber-300 flex items-center gap-1.5">
                  <span>🎁</span> Push Subscriber Bonus Reward
                </h3>
                <span class="px-2 py-0.5 rounded-full text-[9px] font-black bg-amber-500/20 text-amber-300 uppercase">
                  Growth Booster
                </span>
              </div>

              <p class="text-[11px] text-slate-300 leading-relaxed">
                ইউজারদের দিয়ে নোটিফিকেশন Allow করানোর জন্য ইনস্ট্যান্ট ফ্রি পয়েন্ট বোনাস অফার করুন। ব্যবহারকারী একবারই এই বোনাস পাবে।
              </p>

              <form @submit.prevent="saveBonusSettings" class="space-y-3 pt-1">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800">
                  <span class="text-xs font-bold text-slate-200">সাবস্ক্রিপশন বোনাস সক্রিয় রাখুন</span>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                      type="checkbox" 
                      v-model="bonusForm.push_bonus_enabled" 
                      class="sr-only peer"
                    />
                    <div class="w-9 h-5 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-amber-500"></div>
                  </label>
                </div>

                <div class="space-y-1">
                  <label class="block text-[11px] font-bold text-slate-300">বোনাস পয়েন্টের পরিমাণ (Pts):</label>
                  <div class="relative">
                    <input 
                      type="number" 
                      v-model="bonusForm.push_bonus_amount" 
                      min="0" 
                      max="100000" 
                      step="1"
                      class="w-full px-3 py-2 bg-slate-950 border border-slate-800 focus:border-amber-500/50 rounded-xl text-xs text-amber-300 font-mono font-bold outline-none"
                    />
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[10px] text-slate-500">Points</span>
                  </div>
                  <span v-if="bonusForm.errors.push_bonus_amount" class="text-[10px] text-rose-400 block font-semibold mt-1">
                    {{ bonusForm.errors.push_bonus_amount }}
                  </span>
                </div>

                <button 
                  type="submit" 
                  :disabled="bonusForm.processing"
                  class="w-full py-2 rounded-xl bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-bold transition cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-50"
                >
                  <span>{{ bonusForm.processing ? 'Saving...' : 'Save Bonus Settings 💾' }}</span>
                </button>
              </form>
            </div>
          </div>

        </div>

        <!-- Push Campaigns History Table -->
        <div class="glass-card rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
          <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
              <span>📋</span> Web Push Broadcast History
            </h3>
            <span class="text-xs font-mono text-slate-400">Total: {{ pushCampaigns.total || pushCampaigns.data?.length || 0 }}</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono text-[10px] border-b border-slate-800">
                <tr>
                  <th class="py-3 px-4">Title & Message</th>
                  <th class="py-3 px-4">Target Audience</th>
                  <th class="py-3 px-4 text-center">Delivered</th>
                  <th class="py-3 px-4 text-center">Failed</th>
                  <th class="py-3 px-4 text-center">Status</th>
                  <th class="py-3 px-4 text-right">Sent Time</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <tr v-if="!pushCampaigns.data || pushCampaigns.data.length === 0">
                  <td colspan="6" class="text-center py-8 text-slate-500 font-mono">
                    No web push campaigns sent yet. Send your first broadcast above!
                  </td>
                </tr>
                <tr v-for="c in pushCampaigns.data" :key="c.id" class="hover:bg-slate-900/40 transition">
                  <td class="py-3 px-4 max-w-xs">
                    <div class="font-bold text-white truncate">{{ c.title }}</div>
                    <div class="text-[10px] text-slate-400 truncate">{{ c.body }}</div>
                  </td>
                  <td class="py-3 px-4 font-mono text-cyan-300 text-[11px] uppercase">
                    {{ c.audience_filter }}
                  </td>
                  <td class="py-3 px-4 text-center font-mono font-bold text-emerald-400">
                    {{ c.total_sent }}
                  </td>
                  <td class="py-3 px-4 text-center font-mono text-rose-400">
                    {{ c.total_failed }}
                  </td>
                  <td class="py-3 px-4 text-center">
                    <span 
                      class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase"
                      :class="{
                        'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': c.status === 'completed',
                        'bg-amber-500/20 text-amber-300 border border-amber-500/30': c.status === 'partial',
                        'bg-rose-500/20 text-rose-300 border border-rose-500/30': c.status === 'failed',
                      }"
                    >
                      {{ c.status }}
                    </span>
                  </td>
                  <td class="py-3 px-4 text-right text-slate-500 text-[10px] font-mono whitespace-nowrap">
                    {{ formatDate(c.created_at) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- ========================================================================= -->
      <!-- TAB 2: SMS CAMPAIGN (BULK SMS DHAKA)                                      -->
      <!-- ========================================================================= -->
      <div v-else class="space-y-6">

        <!-- Header & Gateway Balance Banner -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 p-4 rounded-2xl border border-slate-800">
          <div>
            <div class="flex items-center gap-2">
              <span class="text-sm font-bold text-white">Bulk SMS Dhaka Gateway:</span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                :class="isEnabled ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'"
              >
                {{ isEnabled ? 'Gateway Live' : 'Gateway Disabled' }}
              </span>
            </div>
            <div class="text-xs text-slate-400 mt-1">Sender ID: <strong class="text-indigo-400 font-mono">{{ senderId }}</strong></div>
          </div>

          <div class="flex items-center gap-3">
            <div class="text-right">
              <div class="text-[10px] text-slate-400 font-semibold uppercase">SMS Balance</div>
              <div class="text-base font-black text-emerald-400 font-mono">
                {{ balance !== null ? `${balance} BDT` : 'N/A' }}
              </div>
            </div>
            <Link 
              :href="`${adminPath}/sms-campaign`" 
              class="p-2 bg-slate-800 hover:bg-slate-700 rounded-xl text-slate-300 transition"
              title="Refresh"
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
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative overflow-hidden"
                    :class="form.filter_type === 'all' ? 'bg-indigo-600/10 border-indigo-500 shadow-lg shadow-indigo-600/10' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700 text-slate-300'"
                  >
                    <div class="text-xs font-bold text-white">🌐 All Valid Phones</div>
                    <div class="text-[11px] text-indigo-400 font-mono font-bold mt-1">{{ audienceCounts.all }} Contacts</div>
                  </button>

                  <button
                    type="button"
                    @click="form.filter_type = 'verified_only'"
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative overflow-hidden"
                    :class="form.filter_type === 'verified_only' ? 'bg-emerald-600/10 border-emerald-500 shadow-lg shadow-emerald-600/10' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700 text-slate-300'"
                  >
                    <div class="text-xs font-bold text-white">✅ Phone Verified</div>
                    <div class="text-[11px] text-emerald-400 font-mono font-bold mt-1">{{ audienceCounts.verified_only }} Contacts</div>
                  </button>

                  <button
                    type="button"
                    @click="form.filter_type = 'inactive_3d'"
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative overflow-hidden"
                    :class="form.filter_type === 'inactive_3d' ? 'bg-amber-600/10 border-amber-500 shadow-lg shadow-amber-600/10' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700 text-slate-300'"
                  >
                    <div class="text-xs font-bold text-white">⏳ Inactive 3+ Days</div>
                    <div class="text-[11px] text-amber-400 font-mono font-bold mt-1">{{ audienceCounts.inactive_3d }} Contacts</div>
                  </button>

                  <button
                    type="button"
                    @click="form.filter_type = 'inactive_7d'"
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative overflow-hidden"
                    :class="form.filter_type === 'inactive_7d' ? 'bg-rose-600/10 border-rose-500 shadow-lg shadow-rose-600/10' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700 text-slate-300'"
                  >
                    <div class="text-xs font-bold text-white">💤 Inactive 7+ Days</div>
                    <div class="text-[11px] text-rose-400 font-mono font-bold mt-1">{{ audienceCounts.inactive_7d }} Contacts</div>
                  </button>

                  <button
                    type="button"
                    @click="selectBalanceFilter"
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative overflow-hidden"
                    :class="form.filter_type === 'balance_min' ? 'bg-purple-600/10 border-purple-500 shadow-lg shadow-purple-600/10' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700 text-slate-300'"
                  >
                    <div class="text-xs font-bold text-white">💰 Min Balance</div>
                    <div class="text-[11px] text-purple-400 font-mono font-bold mt-1">{{ dynamicBalanceCount }} Contacts</div>
                  </button>

                  <button
                    type="button"
                    @click="form.filter_type = 'today_new'"
                    class="p-3 rounded-2xl border text-left transition-all cursor-pointer relative overflow-hidden"
                    :class="form.filter_type === 'today_new' ? 'bg-cyan-600/10 border-cyan-500 shadow-lg shadow-cyan-600/10' : 'bg-slate-900/60 border-slate-800 hover:border-slate-700 text-slate-300'"
                  >
                    <div class="text-xs font-bold text-white">✨ Registered Today</div>
                    <div class="text-[11px] text-cyan-400 font-mono font-bold mt-1">{{ audienceCounts.today_new }} Contacts</div>
                  </button>
                </div>
              </div>

              <!-- Message Textarea -->
              <div class="space-y-2">
                <div class="flex items-center justify-between">
                  <label class="block text-xs font-bold text-slate-200">
                    2️⃣ Message Content (এসএমএস বার্তা লিখুন)
                  </label>
                  <span class="text-[11px] font-mono text-slate-400">
                    {{ charCount }} chars · {{ partsPerRecipient }} SMS ({{ isUnicode ? 'Unicode' : 'GSM-7' }})
                  </span>
                </div>

                <textarea
                  v-model="form.message"
                  rows="4"
                  class="w-full p-4 rounded-2xl bg-slate-950/80 border border-slate-800 text-white text-xs placeholder-slate-500 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition shadow-inner font-sans leading-relaxed"
                  placeholder="EasyTsk: প্রিয় ইউজার, আপনার একাউন্টে নতুন টাস্ক যোগ হয়েছে! এখনই ভিজিট করুন..."
                ></textarea>
              </div>

              <!-- Cost Summary & Dispatch Button -->
              <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800/80 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                  <div class="text-xs text-slate-400 font-semibold">আনুমানিক খরচ (Estimated Cost):</div>
                  <div class="text-lg font-black text-emerald-400 font-mono mt-0.5">
                    ~{{ totalEstimatedCost }} BDT
                    <span class="text-xs font-normal text-slate-500">({{ selectedAudienceCount }} × {{ partsPerRecipient }} × 0.35 ৳)</span>
                  </div>
                </div>

                <button
                  type="submit"
                  :disabled="selectedAudienceCount === 0 || !form.message || form.processing"
                  class="w-full sm:w-auto px-6 py-3 rounded-2xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/20 active:scale-95 transition cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                >
                  <span>🚀</span>
                  <span>{{ form.processing ? 'Processing...' : 'Review & Blast Campaign' }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Right 1 Col: Test SMS Tool & Instructions -->
          <div class="space-y-6">
            <div class="glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
              <h3 class="text-xs font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                <span>📱</span> Send Single Test SMS
              </h3>
              <p class="text-[11px] text-slate-400 leading-relaxed">
                পুরো ক্যাম্পেইন ছাড়ার আগে নিজের নাম্বারে একটি পরীক্ষামূলক এসএমএস পাঠিয়ে ফরম্যাট ও ডেলিভারি যাচাই করুন।
              </p>

              <form @submit.prevent="sendTestSms" class="space-y-3">
                <div>
                  <label class="block text-[10px] font-bold text-slate-300 uppercase mb-1">Your Mobile Number</label>
                  <input
                    v-model="testForm.phone"
                    type="text"
                    placeholder="017XXXXXXXX"
                    class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white font-mono outline-none"
                  />
                </div>

                <button
                  type="submit"
                  :disabled="!testForm.phone || !form.message || testForm.processing"
                  class="w-full py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition cursor-pointer disabled:opacity-50"
                >
                  {{ testForm.processing ? 'Sending Test...' : 'Send Test SMS 📤' }}
                </button>
              </form>
            </div>
          </div>
        </div>

        <!-- SMS Campaign History Table -->
        <div class="glass-card rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
          <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
              <span>📋</span> SMS Campaign Blast History
            </h3>
            <span class="text-xs font-mono text-slate-400">Total: {{ campaigns.total || campaigns.data?.length || 0 }}</span>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-900/80 text-slate-400 uppercase font-mono text-[10px] border-b border-slate-800">
                <tr>
                  <th class="py-3 px-4">Title & Message</th>
                  <th class="py-3 px-4">Audience</th>
                  <th class="py-3 px-4 text-center">Recipients</th>
                  <th class="py-3 px-4 text-center">Delivered</th>
                  <th class="py-3 px-4 text-right">Cost</th>
                  <th class="py-3 px-4 text-center">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60 text-slate-300">
                <tr v-if="!campaigns.data || campaigns.data.length === 0">
                  <td colspan="6" class="text-center py-8 text-slate-500 font-mono">
                    No SMS campaigns executed yet.
                  </td>
                </tr>
                <tr v-for="c in campaigns.data" :key="c.id" class="hover:bg-slate-900/40 transition">
                  <td class="py-3 px-4 max-w-xs">
                    <div class="font-bold text-white truncate">{{ c.title }}</div>
                    <div class="text-[10px] text-slate-400 truncate">{{ c.message }}</div>
                  </td>
                  <td class="py-3 px-4 font-mono text-indigo-300 text-[11px]">
                    {{ c.filter_type }}
                  </td>
                  <td class="py-3 px-4 text-center font-mono">
                    {{ c.recipient_count }}
                  </td>
                  <td class="py-3 px-4 text-center font-mono font-bold text-emerald-400">
                    {{ c.sent_count }}
                  </td>
                  <td class="py-3 px-4 text-right font-mono text-emerald-400">
                    {{ c.cost_estimate }} ৳
                  </td>
                  <td class="py-3 px-4 text-center">
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

      </div>

      <!-- SMS Confirmation Modal -->
      <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-indigo-500/40 bg-slate-900 shadow-2xl space-y-5">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-2xl shrink-0">
              ⚠️
            </div>
            <div>
              <h3 class="text-base font-bold text-white">Confirm SMS Campaign Blast</h3>
              <p class="text-xs text-slate-400 mt-0.5">অনুগ্রহ করে তথ্যগুলো যাচাই করে নিশ্চিত করুন।</p>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
              <span>Audience Target:</span>
              <span class="font-bold text-white uppercase">{{ form.filter_type }}</span>
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

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="showConfirmModal = false"
              class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-xs font-bold transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="submitCampaign"
              :disabled="form.processing"
              class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold transition cursor-pointer shadow-lg shadow-indigo-600/20"
            >
              Confirm & Send Now 🚀
            </button>
          </div>
        </div>
      </div>

      <!-- Web Push Confirmation Modal -->
      <div v-if="showPushConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-cyan-500/40 bg-slate-900 shadow-2xl space-y-5">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center text-2xl shrink-0">
              🔔
            </div>
            <div>
              <h3 class="text-base font-bold text-white">Confirm Web Push Broadcast</h3>
              <p class="text-xs text-slate-400 mt-0.5">সবগুলো ডিভাইস ব্রাউজারে নোটিফিকেশন পাঠানো হবে।</p>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 space-y-2 text-xs">
            <div class="flex justify-between text-slate-400">
              <span>Audience Target:</span>
              <span class="font-bold text-cyan-400 uppercase">{{ pushForm.audience_filter }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Target URL:</span>
              <span class="font-bold text-white font-mono">{{ pushForm.target_url }}</span>
            </div>
            <div class="flex justify-between text-slate-400">
              <span>Dispatch Cost:</span>
              <span class="font-bold text-emerald-400 font-mono">0.00 BDT (Free)</span>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="showPushConfirmModal = false"
              class="px-4 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-xs font-bold transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="submitPushCampaign"
              :disabled="pushForm.processing"
              class="px-5 py-2.5 rounded-xl bg-cyan-500 hover:bg-cyan-400 text-slate-950 text-xs font-black transition cursor-pointer shadow-lg shadow-cyan-500/20"
            >
              Confirm & Broadcast 📢
            </button>
          </div>
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';
import { isPushSupported, subscribeToWebPush, getCurrentSubscription } from '@/Utils/webPush';

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
  pushStats: {
    type: Object,
    default: () => ({ total: 0, mobile: 0, desktop: 0, today_active: 0, inactive_3d: 0, inactive_7d: 0, total_campaigns: 0 }),
  },
  pushCampaigns: {
    type: Object,
    default: () => ({ data: [] }),
  },
  vapidPublicKey: String,
  isPushEnabled: Boolean,
  pushBonusEnabled: {
    type: Boolean,
    default: true,
  },
  pushBonusAmount: {
    type: [Number, String],
    default: 20,
  },
});

// Active Channel Tab: 'push' (default) or 'sms'
const activeChannel = ref('push');

// Web Push Bonus Settings Form
const bonusForm = useForm({
  push_bonus_enabled: props.pushBonusEnabled,
  push_bonus_amount: props.pushBonusAmount,
});

const saveBonusSettings = () => {
  bonusForm.post(`${adminPath.value}/sms-campaign/push-settings`, {
    preserveScroll: true,
  });
};

// Web Push Form & States
const pushForm = useForm({
  title: '',
  body: '',
  target_url: '/tasks',
  audience_filter: 'all',
  image_url: '',
});

const showPushConfirmModal = ref(false);
const isBrowserSubscribed = ref(false);
const adminSubscriptionEndpoint = ref('');
const isSubscribingAdmin = ref(false);
const isSendingTestPush = ref(false);

const pushTemplates = [
  { label: '🎁 New Offers Alert', title: '🎁 নতুন অফার যুক্ত হয়েছে!', body: 'Notik এবং Timewall এ নতুন কাজ চলে এসেছে। এখনই কমপ্লিট করে কয়েন আয় করুন!', url: '/tasks' },
  { label: '⚡ Flash Promo Drop', title: '⚡ Flash Promo Code Drop!', body: 'সীমিত সময়ের জন্য ফ্রি বোনাস কোড! এখনই সাইটে লগইন করে কোড ক্লেইম করুন।', url: '/tasks' },
  { label: '💰 Payout Threshold', title: '💰 আপনার উইথড্র ব্যালেন্স প্রস্তুত!', body: 'আপনার কয়েন রিডিম করে বিকাশ/নগদে ক্যাশআউট করুন আজই।', url: '/withdraw' },
  { label: '🔥 Daily Streak Bonus', title: '🔥 আপনার ডেইলি স্ট্রিক মিস করবেন না!', body: 'আজকের ফ্রি ডেইলি রিওয়ার্ড ক্লেইম করতে এখনি লগইন করুন।', url: '/dashboard' },
];

const applyPushTemplate = (t) => {
  pushForm.title = t.title;
  pushForm.body = t.body;
  pushForm.target_url = t.url;
};

const checkAdminSubscription = async () => {
  if (!isPushSupported()) return;
  const sub = await getCurrentSubscription();
  if (sub) {
    isBrowserSubscribed.value = true;
    adminSubscriptionEndpoint.value = sub.endpoint;
  }
};

const subscribeAdminBrowser = async () => {
  isSubscribingAdmin.value = true;
  try {
    const res = await subscribeToWebPush(props.vapidPublicKey);
    isBrowserSubscribed.value = true;
    adminSubscriptionEndpoint.value = res.subscription?.endpoint || '';
    alert('✅ আপনার ব্রাউজার সফলভাবে পুশ নোটিফিকেশনের জন্য সাবস্ক্রাইব হয়েছে!');
  } catch (err) {
    alert('❌ পুশ নোটিফিকেশন সক্রিয় করা যায়নি: ' + err.message);
  } finally {
    isSubscribingAdmin.value = false;
  }
};

const sendTestPush = async () => {
  if (!pushForm.title.trim() || !pushForm.body.trim()) {
    alert('অনুগ্রহ করে আগে নোটিফিকেশনের Title ও Body লিখুন।');
    return;
  }

  isSendingTestPush.value = true;
  try {
    const res = await axios.post(`${adminPath.value}/sms-campaign/push-test`, {
      title: pushForm.title,
      body: pushForm.body,
      target_url: pushForm.target_url,
      endpoint: adminSubscriptionEndpoint.value,
    });
    alert(res.data.message || 'টেস্ট নোটিফিকেশন পাঠানো হয়েছে!');
  } catch (err) {
    alert(err.response?.data?.message || 'টেস্ট নোটিফিকেশন পাঠানো ব্যর্থ হয়েছে। আগে ব্রাউজার সাবস্ক্রাইব করুন।');
  } finally {
    isSendingTestPush.value = false;
  }
};

const openPushConfirmModal = () => {
  if (!pushForm.title.trim() || !pushForm.body.trim()) return;
  showPushConfirmModal.value = true;
};

const submitPushCampaign = () => {
  showPushConfirmModal.value = false;
  pushForm.post(`${adminPath.value}/sms-campaign/push-send`, {
    preserveScroll: true,
    onSuccess: () => {
      pushForm.reset('title', 'body', 'image_url');
    },
  });
};

// SMS Form & States
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

const selectedAudienceCount = computed(() => {
  if (form.filter_type === 'balance_min') {
    return dynamicBalanceCount.value;
  }
  return props.audienceCounts[form.filter_type] || 0;
});

const isUnicode = computed(() => {
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

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  return new Date(dateStr).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

onMounted(() => {
  checkAdminSubscription();
});
</script>
