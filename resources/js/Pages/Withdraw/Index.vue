<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h1 class="text-2xl font-extrabold text-white">Withdrawal & Payout Hub</h1>
          <p class="text-xs text-slate-400">Convert your earned points to BDT cash (100 Points = 1 BDT)</p>
        </div>
        <div class="text-right shrink-0">
          <div class="text-xs text-slate-500 mb-0.5">Available Balance</div>
          <div class="text-2xl font-black text-emerald-300"><AnimatedNumber :value="mainBalance" :decimals="0" /> <span class="text-xs font-normal text-slate-500">Pts</span></div>
        </div>
      </div>

      <!-- Status Banners (Low Health, Cooldown, or Pending) -->
      <div v-if="isHealthTooLow" class="glass-card p-5 rounded-3xl border border-rose-500/40 bg-rose-500/10 space-y-3">
        <div class="flex items-start space-x-3 text-rose-300">
          <span class="text-2xl shrink-0">❤️‍🩹</span>
          <div class="flex-1">
            <h3 class="text-sm font-bold text-rose-200">Withdrawal Restricted — Health Score Too Low</h3>
            <p class="text-xs text-rose-300/80 mt-1 leading-relaxed">
              Your account health is currently at <span class="font-bold text-rose-200 font-mono">{{ $page.props.auth.user.health }}%</span>. To submit withdrawal requests, your Health Score must be greater than <span class="font-bold text-rose-200 font-mono">{{ minWithdrawHealth || 40 }}%</span>.
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
            <p class="text-xs text-amber-400/80">You already have a pending withdrawal request. Please wait until it is processed.</p>
          </div>
          <div v-else>
            <h3 class="text-sm font-bold">24-Hour Withdrawal Cooldown Active</h3>
            <p class="text-xs text-amber-400/80">You can submit your next payout request after the cooldown timer expires.</p>
          </div>
        </div>
        <div v-if="!hasPending && cooldownSeconds > 0" class="text-center py-2 bg-slate-950/60 rounded-2xl border border-amber-500/20">
          <span class="text-2xl font-black text-amber-400 font-mono">{{ formatTimer(cooldownSeconds) }}</span>
        </div>
      </div>

      <!-- Withdrawal Request Form -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
        <h2 class="text-base font-bold text-white">Request Payout</h2>

        <form @submit.prevent="submitWithdrawal" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Points to Withdraw (Min: {{ activeMinLimit }} Pts)</label>
              <input 
                v-model="form.amount_coins" 
                type="number" 
                required 
                :disabled="!canWithdraw"
                class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500" 
                placeholder="1000"
              />
              <span v-if="form.errors.amount_coins" class="text-xs text-rose-400 mt-1 block">{{ form.errors.amount_coins }}</span>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Payment Method</label>
              <select v-model="form.payment_method" :disabled="!canWithdraw" class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs">
                <option value="bKash">bKash Personal</option>
                <option value="Nagad">Nagad Personal</option>
                <option value="Rocket">Rocket Personal</option>
                <option value="Mobile Recharge">Mobile Recharge</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Mobile Wallet Number</label>
            <input 
              v-model="form.account_details" 
              type="text" 
              required 
              :disabled="!canWithdraw"
              class="w-full px-4 py-3 bg-slate-900 border border-slate-800 rounded-xl text-white text-xs" 
              placeholder="017XXXXXXXX"
            />
          </div>

          <div class="p-3 bg-slate-900/60 rounded-xl border border-slate-800 flex flex-col gap-1 text-xs font-semibold text-slate-300">
            <div class="flex justify-between" v-if="estimatedChargeCoins > 0">
              <span v-if="form.payment_method === 'Mobile Recharge'">Fixed Withdrawal Charge:</span>
              <span v-else>Withdrawal Charge ({{ withdrawalChargePercent }}%):</span>
              <span class="text-rose-400 font-bold">-{{ estimatedChargeCoins }} Pts</span>
            </div>
            <div class="flex justify-between mt-1 pt-1 border-t border-slate-800/60">
              <span>You will receive:</span>
              <span class="text-emerald-400 font-bold text-sm">৳ {{ estimatedBDT }} BDT</span>
            </div>
          </div>

          <button 
            type="submit" 
            :disabled="!canWithdraw || form.processing"
            class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-bold text-xs rounded-2xl shadow-xl shadow-emerald-500/20 transition transform active:scale-95 disabled:opacity-50"
          >
            Submit Withdrawal Request
          </button>
        </form>
      </div>

      <!-- Recent Withdrawals History -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-white">Withdrawal History</h2>
            <p class="text-[11px] text-slate-500 mt-0.5">Your recent payout requests</p>
          </div>
          <span class="badge badge-indigo">{{ withdrawals.length }} records</span>
        </div>

        <!-- Empty State -->
        <div v-if="!withdrawals || withdrawals.length === 0" class="text-center py-10">
          <div class="text-4xl mb-3">💸</div>
          <p class="text-sm font-bold text-white mb-1">No withdrawals yet</p>
          <p class="text-xs text-slate-500">Your payout history will appear here.</p>
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
              }"
            >
              {{ w.payment_method === 'bKash' ? '📱' : w.payment_method === 'Nagad' ? '🔶' : '🚀' }}
            </div>

            <!-- Details -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5">
                <span class="text-xs font-bold text-white">{{ w.payment_method }}</span>
                <span class="text-[10px] text-slate-600 truncate font-mono">{{ w.account_details }}</span>
              </div>
              <div class="text-[10px] text-slate-500">{{ new Date(w.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) }}</div>
              <div v-if="w.status === 'paid' && w.transaction_id" class="text-[10px] text-emerald-400 mt-1 font-mono tracking-wide">Txn: {{ w.transaction_id }}</div>
              <div v-if="w.status === 'rejected' && w.rejection_reason" class="text-[10px] text-rose-400 mt-1">Reason: {{ w.rejection_reason }}</div>
            </div>

            <!-- Amount -->
            <div class="text-right shrink-0">
              <div class="text-sm font-black text-emerald-400">৳{{ w.amount_bdt }}</div>
              <div class="text-[10px] text-slate-600">{{ w.amount_coins }} Pts</div>
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
          
          <div v-if="withdrawals.length >= 5" class="pt-2 text-center">
            <Link href="/withdraw-history" class="inline-block px-6 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl transition border border-slate-700 hover:border-slate-600">
              See More
            </Link>
          </div>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AnimatedNumber from '@/Components/AnimatedNumber.vue';

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
  withdrawals: Array,
  withdrawalChargePercent: Number,
  mobileRechargeMinLimit: Number,
  mobileRechargeFixedCharge: Number,
});

const cooldownSeconds = ref(props.remainingSeconds || 0);
let timerInterval = null;

const form = useForm({
  amount_coins: props.minWithdrawCoins || 1000,
  payment_method: props.savedMethod || 'bKash',
  account_details: props.savedNumber || '',
});

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
</script>
