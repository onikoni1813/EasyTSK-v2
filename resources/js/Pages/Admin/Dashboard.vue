<template>
  <AdminLayout>
    <div class="space-y-6">

      <h1 class="text-2xl font-extrabold text-white">📊 Admin Overview</h1>

      <!-- Growth Trend Chart -->
      <GrowthChart :chart-data="growthChart" />

      <!-- Stats Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <Link v-for="stat in statCards" :key="stat.label"
          :href="stat.href"
          class="glass-card p-4 rounded-2xl card-hover relative overflow-hidden block transition-all duration-200 hover:scale-[1.02] cursor-pointer"
          :class="stat.border"
        >
          <div class="absolute top-0 right-0 w-16 h-16 rounded-full blur-2xl opacity-20" :class="stat.glow"></div>
          <div class="text-[10px] font-bold uppercase tracking-wider mb-1" :class="stat.labelCls">{{ stat.label }}</div>
          <div class="text-2xl font-black text-white stat-number">{{ stat.value }}</div>
          <div v-if="stat.sub" class="text-[10px] text-slate-500 mt-0.5">{{ stat.sub }}</div>
        </Link>
      </div>

      <!-- High Risk Users -->
      <div v-if="highRiskUsers.length" class="glass-card p-6 rounded-3xl border border-rose-500/20">
        <div class="section-header mb-5">
          <span class="section-title text-rose-400">⚠️ High Risk Users</span>
          <div class="section-header-line"></div>
          <span class="badge badge-rose shrink-0">{{ highRiskUsers.length }}</span>
        </div>

        <div class="space-y-3">
          <UserModerationRow v-for="u in highRiskUsers" :key="u.id" :user="u" />
        </div>
      </div>

      <!-- Low Health Users -->
      <div v-if="lowHealthUsers.length" class="glass-card p-6 rounded-3xl border border-amber-500/20">
        <div class="section-header mb-5">
          <span class="section-title text-amber-400">❤️‍🩹 Low Health Users</span>
          <div class="section-header-line"></div>
          <span class="badge badge-amber shrink-0">{{ lowHealthUsers.length }}</span>
        </div>

        <div class="space-y-3">
          <UserModerationRow v-for="u in lowHealthUsers" :key="u.id" :user="u" />
        </div>
      </div>

      <!-- Quick Links -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <Link :href="`${adminPath}/reviews`" class="glass-card p-5 rounded-2xl border border-amber-500/15 card-hover text-center block">
          <div class="text-3xl mb-2">📋</div>
          <div class="text-sm font-bold text-white">Task Reviews</div>
          <div class="badge badge-amber mt-2">{{ stats.pendingReviewsCount }} pending</div>
        </Link>
        <Link :href="`${adminPath}/withdrawals`" class="glass-card p-5 rounded-2xl border border-emerald-500/15 card-hover text-center block">
          <div class="text-3xl mb-2">💸</div>
          <div class="text-sm font-bold text-white">Withdrawals</div>
          <div class="badge badge-emerald mt-2">{{ stats.pendingWithdrawalsCount }} pending</div>
        </Link>
        <Link :href="`${adminPath}/campaigns`" class="glass-card p-5 rounded-2xl border border-violet-500/15 card-hover text-center block">
          <div class="text-3xl mb-2">📢</div>
          <div class="text-sm font-bold text-white">Campaigns</div>
          <div class="badge badge-violet mt-2">{{ stats.pendingCampaigns }} pending</div>
        </Link>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import GrowthChart from '@/Components/GrowthChart.vue';
import UserModerationRow from '@/Components/UserModerationRow.vue';

const props = defineProps({
  stats:          Object,
  highRiskUsers:  Array,
  lowHealthUsers: Array,
  growthChart:    Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const statCards = computed(() => [
  { label: 'Total Users',       value: props.stats.totalUsers,              sub: `+${props.stats.newUsersThisWeek} this week`, border: 'border-indigo-500/15', labelCls: 'text-indigo-400', glow: 'bg-indigo-500', href: `${adminPath.value}/users` },
  { label: 'Main Liability',    value: props.stats.totalMainLiability.toFixed(0) + ' pts', sub: 'Withdrawable balance', border: 'border-rose-500/15',    labelCls: 'text-rose-400',   glow: 'bg-rose-500',   href: `${adminPath.value}/users` },
  { label: 'Pending Liability', value: props.stats.totalPendingLiability.toFixed(0) + ' pts', sub: '24h hold funds', border: 'border-amber-500/15',   labelCls: 'text-amber-400',  glow: 'bg-amber-500',  href: `${adminPath.value}/offerwalls` },
  { label: 'Flagged Devices',   value: props.stats.flaggedDevicesCount,     sub: 'Multi-account detected', border: 'border-rose-500/15',   labelCls: 'text-rose-400',   glow: 'bg-rose-500',   href: `${adminPath.value}/users` },
  { label: 'Task Reviews',      value: props.stats.pendingReviewsCount,     sub: 'Awaiting review', border: 'border-violet-500/15',  labelCls: 'text-violet-400', glow: 'bg-violet-500', href: `${adminPath.value}/reviews` },
  { label: 'Withdrawals',       value: props.stats.pendingWithdrawalsCount, sub: 'Pending payout', border: 'border-emerald-500/15', labelCls: 'text-emerald-400',glow: 'bg-emerald-500', href: `${adminPath.value}/withdrawals` },
  { label: 'Campaigns',         value: props.stats.pendingCampaigns,        sub: 'Awaiting approval', border: 'border-pink-500/15',    labelCls: 'text-pink-400',   glow: 'bg-pink-500',   href: `${adminPath.value}/campaigns` },
  { label: 'Active Promo Codes',value: props.stats.activeCodes,            sub: 'Live promo codes', border: 'border-cyan-500/15',    labelCls: 'text-cyan-400',   glow: 'bg-cyan-500',   href: `${adminPath.value}/promo-codes` },
]);
</script>
