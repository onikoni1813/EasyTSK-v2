<template>
  <AppLayout>
    <div class="space-y-6">
      <!-- Page Header & User Info Overview -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900/90 to-indigo-950/40 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -top-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="relative z-10 space-y-5">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
            <div class="flex items-center gap-3">
              <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/20 shrink-0">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center text-xl">
                  💸
                </div>
              </div>
              <div>
                <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Withdrawal & Payout Hub</h1>
                <p class="text-xs text-slate-400">Convert your earned points to real BDT cash ({{ conversionRate || 100 }} Pts = 1 BDT)</p>
              </div>
            </div>

            <!-- User Quick Badge -->
            <div class="flex items-center gap-2 bg-slate-950/60 p-2 rounded-2xl border border-slate-800/80 self-start md:self-auto">
              <div class="w-8 h-8 rounded-xl bg-indigo-600/30 border border-indigo-500/30 flex items-center justify-center font-bold text-xs text-indigo-300">
                {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
              </div>
              <div class="pr-2">
                <div class="text-xs font-bold text-white flex items-center gap-1.5">
                  {{ user.name }}
                  <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">Lvl {{ user.level || 1 }}</span>
                </div>
                <div class="text-[10px] text-slate-400 font-mono">{{ user.phone || user.email }}</div>
              </div>
            </div>
          </div>

          <!-- Payout Stats Cards Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <!-- Available Balance -->
            <div class="bg-slate-950/70 p-3.5 rounded-2xl border border-emerald-500/20 space-y-1">
              <div class="text-[11px] font-semibold text-emerald-400/90 flex items-center justify-between">
                <span>Available Balance</span>
                <span class="text-xs">💰</span>
              </div>
              <div class="text-lg sm:text-xl font-black text-emerald-300">
                <AnimatedNumber :value="mainBalance" :decimals="0" /> <span class="text-xs font-normal text-slate-400">Pts</span>
              </div>
              <div class="text-[10px] text-slate-500">
                ≈ ৳{{ (mainBalance / (conversionRate || 100)).toFixed(2) }} BDT
              </div>
            </div>

            <!-- Total Paid Out -->
            <div class="bg-slate-950/70 p-3.5 rounded-2xl border border-slate-800 space-y-1">
              <div class="text-[11px] font-semibold text-slate-400 flex items-center justify-between">
                <span>Total Paid Out</span>
                <span class="text-xs">✅</span>
              </div>
              <div class="text-lg sm:text-xl font-black text-white">
                ৳{{ userStats?.totalWithdrawnBdt || 0 }}
              </div>
              <div class="text-[10px] text-slate-500">
                Completed requests
              </div>
            </div>

            <!-- Pending Payout -->
            <div class="bg-slate-950/70 p-3.5 rounded-2xl border border-slate-800 space-y-1">
              <div class="text-[11px] font-semibold text-amber-400 flex items-center justify-between">
                <span>Pending Payout</span>
                <span class="text-xs">⏳</span>
              </div>
              <div class="text-lg sm:text-xl font-black text-amber-300">
                ৳{{ userStats?.pendingWithdrawnBdt || 0 }}
              </div>
              <div class="text-[10px] text-slate-500">
                Under review
              </div>
            </div>

            <!-- Health Score Bar -->
            <div class="bg-slate-950/70 p-3.5 rounded-2xl border border-slate-800 space-y-1">
              <div class="text-[11px] font-semibold text-slate-400 flex items-center justify-between">
                <span>Account Health</span>
                <span class="text-xs">❤️</span>
              </div>
              <div class="flex items-center gap-2">
                <div class="text-lg sm:text-xl font-black" :class="userHealth > minWithdrawHealth ? 'text-rose-400' : 'text-rose-500'">
                  {{ userHealth }}%
                </div>
                <span class="text-[10px] px-1.5 py-0.5 rounded font-bold uppercase"
                  :class="userHealth > minWithdrawHealth ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300'"
                >
                  {{ userHealth > minWithdrawHealth ? 'Good' : 'Low' }}
                </span>
              </div>
              <div class="w-full bg-slate-900 rounded-full h-1.5 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" 
                  :style="{ width: `${Math.min(100, Math.max(0, userHealth))}%` }"
                  :class="userHealth > minWithdrawHealth ? 'bg-gradient-to-r from-emerald-500 to-teal-400' : 'bg-gradient-to-r from-rose-600 to-rose-400'"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Banners (Low Health, Cooldown, or Pending) -->
      <div v-if="isHealthTooLow" class="glass-card p-5 rounded-3xl border border-rose-500/40 bg-rose-500/10 space-y-3">
        <div class="flex items-start space-x-3 text-rose-300">
          <span class="text-2xl shrink-0">❤️‍🩹</span>
          <div class="flex-1">
            <h3 class="text-sm font-bold text-rose-200">Withdrawal Restricted — Health Score Too Low</h3>
            <p class="text-xs text-rose-300/80 mt-1 leading-relaxed">
              Your account health is currently at <span class="font-bold text-rose-200 font-mono">{{ userHealth }}%</span>. To submit withdrawal requests, your Health Score must be greater than <span class="font-bold text-rose-200 font-mono">{{ minWithdrawHealth || 40 }}%</span>.
            </p>
            <p class="text-xs text-rose-300/70 mt-1">
              Complete Shortlink or Secret-Code tasks to earn points and restore your health score back to 100%.
            </p>
          </div>
        </div>
        <div class="pt-1 flex justify-end">
          <Link href="/tasks" class="inline-flex items-center gap-1.5 px-4 py-2 bg-rose-500/20 hover:bg-rose-500/30 text-rose-200 text-xs font-bold rounded-xl border border-rose-500/40 transition shadow-lg shadow-rose-500/10">
            ⚡ Complete Tasks to Restore Health
          </Link>
        </div>
      </div>

      <div v-else-if="!canWithdraw" class="glass-card p-5 rounded-3xl border border-amber-500/40 bg-amber-500/10 space-y-2">
        <div class="flex items-center space-x-3 text-amber-300">
          <span class="text-xl">⏳</span>
          <div v-if="hasPending">
            <h3 class="text-sm font-bold">Withdrawal Pending</h3>
            <p class="text-xs text-amber-400/80">You already have a pending withdrawal request. Please wait until it is processed by our finance team.</p>
          </div>
          <div v-else>
            <h3 class="text-sm font-bold">24-Hour Withdrawal Cooldown Active</h3>
            <p class="text-xs text-amber-400/80">You can submit your next payout request after the 24-hour cooldown timer expires.</p>
          </div>
        </div>
        <div v-if="!hasPending && cooldownSeconds > 0" class="text-center py-2 bg-slate-950/60 rounded-2xl border border-amber-500/20">
          <span class="text-2xl font-black text-amber-400 font-mono">{{ formatTimer(cooldownSeconds) }}</span>
        </div>
      </div>

      <!-- Main Layout Grid: Wallet Settings & Withdrawal Form -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Saved Wallet & Payment Details Manager Card (Left / 1 Col) -->
        <div class="lg:col-span-1 glass-card p-6 rounded-3xl border border-slate-800 space-y-4 flex flex-col justify-between">
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-bold text-white flex items-center gap-2">
                <span>💳</span> Saved Wallet Details
              </h2>
              <span class="text-[10px] px-2 py-0.5 rounded font-bold uppercase"
                :class="savedNumber ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30'"
              >
                {{ savedNumber ? 'Configured' : 'Not Set' }}
              </span>
            </div>

            <!-- Saved Info Box -->
            <div v-if="savedNumber" class="p-4 bg-slate-950/80 rounded-2xl border border-slate-800 space-y-2">
              <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Default Payment Gateway</div>
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                  <span v-if="savedMethod === 'bKash'">📱 bKash</span>
                  <span v-else-if="savedMethod === 'Nagad'">🔶 Nagad</span>
                  <span v-else-if="savedMethod === 'Rocket'">🚀 Rocket</span>
                  <span v-else>📲 {{ savedMethod }}</span>
                </span>
                <span class="text-xs font-mono font-bold text-white">{{ savedNumber }}</span>
              </div>
              <p class="text-[10px] text-slate-500">Auto-filled in your payout request forms.</p>
            </div>
            <div v-else class="p-4 bg-slate-950/40 rounded-2xl border border-dashed border-slate-800 text-center space-y-1">
              <p class="text-xs text-slate-400 font-medium">No saved payout wallet</p>
              <p class="text-[10px] text-slate-500">Save your default bKash/Nagad/Rocket details for 1-click withdrawals.</p>
            </div>

            <!-- Toggle Form Button -->
            <button 
              @click="showWalletModal = !showWalletModal"
              type="button" 
              class="w-full py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl border border-slate-700 transition flex items-center justify-center gap-2"
            >
              <span>{{ showWalletModal ? '✕ Close Wallet Settings' : '⚙️ Manage / Update Saved Wallet' }}</span>
            </button>

            <!-- Wallet Update Form -->
            <transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2">
              <form v-if="showWalletModal" @submit.prevent="updateWallet" class="p-4 bg-slate-950 rounded-2xl border border-indigo-500/30 space-y-3">
                <h3 class="text-xs font-bold text-indigo-300">Update Wallet & Security PIN</h3>
                
                <div>
                  <label class="block text-[11px] font-semibold text-slate-300 mb-1">Preferred Gateway</label>
                  <select v-model="walletForm.payment_method" class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs">
                    <option value="bKash">bKash Personal</option>
                    <option value="Nagad">Nagad Personal</option>
                    <option value="Rocket">Rocket Personal</option>
                    <option value="Mobile Recharge">Mobile Recharge</option>
                  </select>
                </div>

                <div>
                  <label class="block text-[11px] font-semibold text-slate-300 mb-1">Wallet Phone Number</label>
                  <input 
                    v-model="walletForm.payment_number" 
                    type="text" 
                    required 
                    placeholder="017XXXXXXXX"
                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500" 
                  />
                  <span v-if="walletForm.errors.payment_number" class="text-[10px] text-rose-400 mt-0.5 block">{{ walletForm.errors.payment_number }}</span>
                </div>

                <div>
                  <label class="block text-[11px] font-semibold text-slate-300 mb-1">
                    4-Digit Security PIN {{ hasRecoveryPin ? '(Verify existing PIN)' : '(Create new PIN)' }}
                  </label>
                  <input 
                    v-model="walletForm.recovery_pin" 
                    type="password" 
                    maxlength="4" 
                    required 
                    placeholder="****"
                    class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs font-mono tracking-widest focus:outline-none focus:border-indigo-500" 
                  />
                  <span v-if="walletForm.errors.recovery_pin" class="text-[10px] text-rose-400 mt-0.5 block">{{ walletForm.errors.recovery_pin }}</span>
                </div>

                <button 
                  type="submit" 
                  :disabled="walletForm.processing"
                  class="w-full py-2 px-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl shadow-md transition disabled:opacity-50"
                >
                  {{ walletForm.processing ? 'Saving Wallet...' : 'Save Wallet Details' }}
                </button>
              </form>
            </transition>
          </div>

          <div class="pt-3 border-t border-slate-800/80 text-[10px] text-slate-500 leading-relaxed">
            🔒 Payments are protected by 4-digit PIN verification. Contact support if you forgot your PIN.
          </div>
        </div>

        <!-- Withdrawal Request Form (Right / 2 Cols) -->
        <div class="lg:col-span-2 glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
          <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-white flex items-center gap-2">
              <span>⚡</span> Submit Payout Request
            </h2>
            <span class="text-xs text-slate-400 font-mono">Min limit: <strong class="text-emerald-400">{{ activeMinLimit }} Pts</strong></span>
          </div>

          <form @submit.prevent="submitWithdrawal" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Points Amount -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Points to Withdraw</label>
                <input 
                  v-model="form.amount_coins" 
                  type="number" 
                  required 
                  :disabled="!canWithdraw"
                  class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs focus:outline-none focus:border-emerald-500 font-mono" 
                  placeholder="1000"
                />
                <span v-if="form.errors.amount_coins" class="text-xs text-rose-400 mt-1 block">{{ form.errors.amount_coins }}</span>
                
                <!-- Quick Amount Selector Buttons -->
                <div class="flex items-center gap-1.5 mt-2 flex-wrap">
                  <button 
                    v-for="amt in [minWithdrawCoins, 1000, 2000, 5000]" 
                    :key="amt"
                    type="button" 
                    @click="form.amount_coins = amt"
                    :disabled="!canWithdraw"
                    class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 border border-slate-800 rounded-lg text-[10px] font-bold text-slate-300 transition"
                  >
                    {{ amt }} Pts
                  </button>
                  <button 
                    type="button" 
                    @click="form.amount_coins = Math.floor(mainBalance)"
                    :disabled="!canWithdraw || mainBalance < activeMinLimit"
                    class="px-2.5 py-1 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 rounded-lg text-[10px] font-bold text-emerald-300 transition"
                  >
                    Max ({{ Math.floor(mainBalance) }})
                  </button>
                </div>
              </div>

              <!-- Payment Method Selection -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Payment Method</label>
                <select 
                  v-model="form.payment_method" 
                  @change="handleMethodChange"
                  :disabled="!canWithdraw" 
                  class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs focus:outline-none focus:border-emerald-500"
                >
                  <option value="bKash">bKash Personal</option>
                  <option value="Nagad">Nagad Personal</option>
                  <option value="Rocket">Rocket Personal</option>
                  <option value="Mobile Recharge">Mobile Recharge</option>
                </select>
                <span class="text-[10px] text-slate-500 mt-1 block">
                  Select payment gateway for receiving cash.
                </span>
              </div>
            </div>

            <!-- Wallet Number -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-semibold text-slate-300">Account Details / Mobile Number</label>
                <button 
                  v-if="savedNumber && form.account_details !== savedNumber" 
                  type="button" 
                  @click="form.account_details = savedNumber"
                  class="text-[10px] text-indigo-400 hover:underline font-semibold"
                >
                  Use Saved Number ({{ savedNumber }})
                </button>
              </div>
              <input 
                v-model="form.account_details" 
                type="text" 
                required 
                :disabled="!canWithdraw"
                class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs font-mono focus:outline-none focus:border-emerald-500" 
                placeholder="017XXXXXXXX"
              />
              <span v-if="form.errors.account_details" class="text-xs text-rose-400 mt-1 block">{{ form.errors.account_details }}</span>
            </div>

            <!-- Fee & Conversion Calculation Box -->
            <div class="p-4 bg-slate-950/80 rounded-2xl border border-slate-800 space-y-2 text-xs">
              <div class="flex justify-between text-slate-400">
                <span>Requested Points:</span>
                <span class="font-mono font-bold text-white">{{ form.amount_coins || 0 }} Pts</span>
              </div>
              <div class="flex justify-between text-slate-400" v-if="estimatedChargeCoins > 0">
                <span v-if="form.payment_method === 'Mobile Recharge'">Fixed Processing Fee:</span>
                <span v-else>Gateway Service Fee ({{ withdrawalChargePercent }}%):</span>
                <span class="text-rose-400 font-bold font-mono">-{{ estimatedChargeCoins }} Pts</span>
              </div>
              <div class="flex justify-between items-center pt-2 border-t border-slate-800">
                <span class="font-bold text-slate-200">Net Amount Received:</span>
                <span class="text-base font-black text-emerald-400">৳ {{ estimatedBDT }} BDT</span>
              </div>
            </div>

            <button 
              type="submit" 
              :disabled="!canWithdraw || form.processing"
              class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-bold text-xs rounded-2xl shadow-xl shadow-emerald-500/20 transition transform active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <span>{{ form.processing ? 'Submitting Request...' : 'Submit Withdrawal Request' }}</span>
            </button>
          </form>
        </div>

      </div>

      <!-- Recent Withdrawals History Table/Cards -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-white">Recent Payout History</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Your latest withdrawal requests and status updates</p>
          </div>
          <span class="badge badge-indigo">{{ withdrawals.length }} recent records</span>
        </div>

        <!-- Empty State -->
        <div v-if="!withdrawals || withdrawals.length === 0" class="text-center py-10">
          <div class="text-4xl mb-3">💸</div>
          <p class="text-sm font-bold text-white mb-1">No withdrawals yet</p>
          <p class="text-xs text-slate-500">Your payout history will appear here once you request a withdrawal.</p>
        </div>

        <!-- Withdrawal Cards -->
        <div v-else class="space-y-2.5">
          <div v-for="w in withdrawals" :key="w.id"
            class="flex items-center gap-3 p-3.5 rounded-2xl bg-white/[0.02] border border-white/[0.05] hover:border-emerald-500/20 transition-all group"
          >
            <!-- Method Icon -->
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"
              :class="{
                'bg-pink-500/15 border border-pink-500/20': w.payment_method === 'bKash',
                'bg-orange-500/15 border border-orange-500/20': w.payment_method === 'Nagad',
                'bg-violet-500/15 border border-violet-500/20': w.payment_method === 'Rocket',
                'bg-emerald-500/15 border border-emerald-500/20': w.payment_method === 'Mobile Recharge',
              }"
            >
              {{ w.payment_method === 'bKash' ? '📱' : w.payment_method === 'Nagad' ? '🔶' : w.payment_method === 'Rocket' ? '🚀' : '📲' }}
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="text-xs font-bold text-white">{{ w.payment_method }}</span>
                <span class="text-[10px] text-slate-400 truncate font-mono bg-slate-900 px-2 py-0.5 rounded border border-slate-800">{{ w.account_details }}</span>
              </div>
              <div class="text-[10px] text-slate-500">{{ new Date(w.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</div>
              <div v-if="w.status === 'paid' && w.transaction_id" class="text-[10px] text-emerald-400 mt-1 font-mono tracking-wide">Txn ID: {{ w.transaction_id }}</div>
              <div v-if="w.status === 'rejected' && w.rejection_reason" class="text-[10px] text-rose-400 mt-1">Reason: {{ w.rejection_reason }}</div>
            </div>

            <!-- Amount -->
            <div class="text-right shrink-0">
              <div class="text-sm font-black text-emerald-400">৳{{ w.amount_bdt }}</div>
              <div class="text-[10px] text-slate-500 font-mono">{{ w.amount_coins }} Pts</div>
            </div>

            <!-- Status Badge -->
            <div class="shrink-0">
              <span class="px-2.5 py-1 rounded-xl text-[10px] uppercase font-black"
                :class="{
                  'bg-amber-500/20 text-amber-300 border border-amber-500/25': w.status === 'pending',
                  'bg-emerald-500/20 text-emerald-300 border border-emerald-500/25': w.status === 'paid',
                  'bg-rose-500/20 text-rose-300 border border-rose-500/25': w.status === 'rejected',
                }"
              >
                {{ w.status === 'pending' ? '⏳ Pending' : w.status === 'paid' ? '✅ Paid' : '❌ Rejected' }}
              </span>
            </div>
          </div>
          
          <div class="pt-2 text-center">
            <Link href="/withdraw-history" class="inline-block px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition border border-slate-700 hover:border-slate-600">
              View Full History →
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AnimatedNumber from '@/Components/AnimatedNumber.vue';

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const userHealth = computed(() => user.value?.health ?? 100);

const props = defineProps({
  mainBalance: Number,
  canWithdraw: Boolean,
  hasPending: Boolean,
  isHealthTooLow: Boolean,
  minWithdrawHealth: Number,
  remainingSeconds: Number,
  conversionRate: Number,
  minWithdrawCoins: Number,
  savedMethod: String,
  savedNumber: String,
  hasRecoveryPin: Boolean,
  userStats: Object,
  withdrawals: Array,
  withdrawalChargePercent: Number,
  mobileRechargeMinLimit: Number,
  mobileRechargeFixedCharge: Number,
});

const showWalletModal = ref(false);
const cooldownSeconds = ref(props.remainingSeconds || 0);
let timerInterval = null;

// Payout Form State
const form = useForm({
  amount_coins: props.minWithdrawCoins || 1000,
  payment_method: props.savedMethod || 'bKash',
  account_details: props.savedNumber || '',
});

// Wallet Settings Form State
const walletForm = useForm({
  payment_method: props.savedMethod || 'bKash',
  payment_number: props.savedNumber || '',
  recovery_pin: '',
});

const handleMethodChange = () => {
  if (props.savedMethod === form.payment_method && props.savedNumber) {
    form.account_details = props.savedNumber;
  }
};

const activeMinLimit = computed(() => {
  if (form.payment_method === 'Mobile Recharge') {
    return props.mobileRechargeMinLimit || 500;
  }
  return props.minWithdrawCoins || 1000;
});

const estimatedChargeCoins = computed(() => {
  const coins = Number(form.amount_coins) || 0;
  if (form.payment_method === 'Mobile Recharge') {
    return props.mobileRechargeFixedCharge || 10;
  }
  const chargePercent = props.withdrawalChargePercent || 0;
  return ((coins * chargePercent) / 100).toFixed(0);
});

const estimatedBDT = computed(() => {
  const coins = Number(form.amount_coins) || 0;
  let chargeCoins = 0;
  if (form.payment_method === 'Mobile Recharge') {
    chargeCoins = props.mobileRechargeFixedCharge || 10;
  } else {
    const chargePercent = props.withdrawalChargePercent || 0;
    chargeCoins = (coins * chargePercent) / 100;
  }
  const netCoins = coins - chargeCoins;
  const rate = props.conversionRate || 100;
  return Math.max(0, netCoins / rate).toFixed(2);
});

const formatTimer = (seconds) => {
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = seconds % 60;
  return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

onMounted(() => {
  if (cooldownSeconds.value > 0) {
    timerInterval = setInterval(() => {
      if (cooldownSeconds.value > 0) {
        cooldownSeconds.value--;
      } else {
        clearInterval(timerInterval);
      }
    }, 1000);
  }
});

onUnmounted(() => {
  if (timerInterval) clearInterval(timerInterval);
});

const submitWithdrawal = () => {
  form.post('/withdraw');
};

const updateWallet = () => {
  walletForm.post('/withdraw/wallet', {
    onSuccess: () => {
      showWalletModal.value = false;
      walletForm.recovery_pin = '';
      if (form.payment_method === walletForm.payment_method) {
        form.account_details = walletForm.payment_number;
      }
    },
  });
};
</script>
