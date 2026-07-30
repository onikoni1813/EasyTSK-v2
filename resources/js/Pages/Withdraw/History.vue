<template>
  <AppLayout>
    <div class="space-y-6">
      <div class="flex items-center gap-4">
        <Link href="/withdraw" class="w-10 h-10 bg-slate-800 rounded-xl flex items-center justify-center text-slate-300 hover:text-white hover:bg-slate-700 transition">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
          </svg>
        </Link>
        <div>
          <h1 class="text-2xl font-extrabold text-white">Full Withdrawal History</h1>
          <p class="text-xs text-slate-400">View all your previous payout requests</p>
        </div>
      </div>

      <div class="glass-card p-6 rounded-3xl border border-slate-800 space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-base font-bold text-white">All Records</h2>
          </div>
          <span class="badge badge-indigo">{{ withdrawals.length }} total</span>
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
              <div class="text-[10px] text-slate-500">{{ new Date(w.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</div>
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
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  withdrawals: Array,
});
</script>
