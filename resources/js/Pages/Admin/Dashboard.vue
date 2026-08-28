<template>
  <AdminLayout>
    <div class="space-y-6">

      <div class="flex items-center justify-between">
        <h1 class="text-2xl font-extrabold text-white">📊 Admin Overview</h1>
        <div class="flex items-center gap-2 text-xs font-semibold text-slate-400">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <span>System Online & Synced</span>
        </div>
      </div>

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

      <!-- Quick Links & Operation Hub -->
      <div class="space-y-3">
        <div class="text-xs font-extrabold uppercase tracking-wider text-slate-400">⚡ Quick Operations Hub</div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
          <Link :href="`${adminPath}/tasks/reviews`" class="glass-card p-4 rounded-2xl border border-amber-500/15 card-hover text-center block transition-transform hover:scale-105">
            <div class="text-2xl mb-1">📋</div>
            <div class="text-xs font-bold text-white">Task Reviews</div>
            <div class="badge badge-amber mt-1 text-[10px]">{{ stats.pendingReviewsCount }} pending</div>
          </Link>
          <Link :href="`${adminPath}/withdrawals`" class="glass-card p-4 rounded-2xl border border-emerald-500/15 card-hover text-center block transition-transform hover:scale-105">
            <div class="text-2xl mb-1">💸</div>
            <div class="text-xs font-bold text-white">Withdrawals</div>
            <div class="badge badge-emerald mt-1 text-[10px]">{{ stats.pendingWithdrawalsCount }} pending</div>
          </Link>
          <Link :href="`${adminPath}/campaigns`" class="glass-card p-4 rounded-2xl border border-violet-500/15 card-hover text-center block transition-transform hover:scale-105">
            <div class="text-2xl mb-1">📢</div>
            <div class="text-xs font-bold text-white">Campaigns</div>
            <div class="badge badge-violet mt-1 text-[10px]">{{ stats.pendingCampaigns }} pending</div>
          </Link>
          <Link :href="`${adminPath}/password-tickets`" class="glass-card p-4 rounded-2xl border border-rose-500/15 card-hover text-center block transition-transform hover:scale-105">
            <div class="text-2xl mb-1">🔐</div>
            <div class="text-xs font-bold text-white">Pass Tickets</div>
            <div class="badge badge-rose mt-1 text-[10px]">{{ stats.pendingPasswordTicketsCount }} pending</div>
          </Link>
          <Link :href="`${adminPath}/support-tickets`" class="glass-card p-4 rounded-2xl border border-indigo-500/15 card-hover text-center block transition-transform hover:scale-105">
            <div class="text-2xl mb-1">💬</div>
            <div class="text-xs font-bold text-white">Support Tickets</div>
            <div class="badge badge-indigo mt-1 text-[10px]">{{ stats.openSupportTicketsCount }} open</div>
          </Link>
          <Link :href="`${adminPath}/referral-contests`" class="glass-card p-4 rounded-2xl border border-cyan-500/15 card-hover text-center block transition-transform hover:scale-105">
            <div class="text-2xl mb-1">🏆</div>
            <div class="text-xs font-bold text-white">Contests</div>
            <div class="badge badge-cyan mt-1 text-[10px]">{{ stats.activeContestsCount }} active</div>
          </Link>
          <Link :href="`${adminPath}/shortlink-providers`" class="glass-card p-4 rounded-2xl border border-cyan-500/20 card-hover text-center block transition-transform hover:scale-105">
            <div class="text-2xl mb-1">🔗</div>
            <div class="text-xs font-bold text-white">Shortlinks</div>
            <div class="badge badge-cyan mt-1 text-[10px]">API Engine</div>
          </Link>
        </div>
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
const adminPath = computed(() => '/' + (page.props.admin_path || 'secret-panel'));

const statCards = computed(() => [
  { label: 'Total Users',        value: Number(props.stats.totalUsers || 0).toLocaleString(),       sub: `+${props.stats.newUsersThisWeek} this week`, border: 'border-indigo-500/15', labelCls: 'text-indigo-400', glow: 'bg-indigo-500', href: `${adminPath.value}/users` },
  { label: 'Banned Users',       value: Number(props.stats.bannedUsersCount || 0).toLocaleString(), sub: 'Restricted accounts', border: 'border-rose-500/15',   labelCls: 'text-rose-400',   glow: 'bg-rose-500',   href: `${adminPath.value}/users` },
  { label: 'Main Liability',     value: Number(props.stats.totalMainLiability || 0).toLocaleString() + ' pts', sub: 'Withdrawable balance', border: 'border-rose-500/15', labelCls: 'text-rose-400', glow: 'bg-rose-500', href: `${adminPath.value}/users` },
  { label: 'Pending Liability',  value: Number(props.stats.totalPendingLiability || 0).toLocaleString() + ' pts', sub: 'Hold / unlock balance', border: 'border-amber-500/15', labelCls: 'text-amber-400', glow: 'bg-amber-500', href: `${adminPath.value}/offerwalls` },
  { label: 'Total Paid Out',     value: '৳' + Number(props.stats.totalPaidOut || 0).toLocaleString(), sub: 'Approved withdrawals', border: 'border-emerald-500/15', labelCls: 'text-emerald-400', glow: 'bg-emerald-500', href: `${adminPath.value}/withdrawals` },
  { label: 'Flagged Devices',    value: Number(props.stats.flaggedDevicesCount || 0).toLocaleString(), sub: 'Multi-account detected', border: 'border-purple-500/15', labelCls: 'text-purple-400', glow: 'bg-purple-500', href: `${adminPath.value}/users` },
  { label: 'Task Reviews',       value: Number(props.stats.pendingReviewsCount || 0).toLocaleString(), sub: 'Awaiting review', border: 'border-violet-500/15', labelCls: 'text-violet-400', glow: 'bg-violet-500', href: `${adminPath.value}/tasks/reviews` },
  { label: 'Withdrawals',        value: Number(props.stats.pendingWithdrawalsCount || 0).toLocaleString(), sub: 'Pending payout', border: 'border-emerald-500/15', labelCls: 'text-emerald-400', glow: 'bg-emerald-500', href: `${adminPath.value}/withdrawals` },
  { label: 'Campaigns',          value: Number(props.stats.pendingCampaigns || 0).toLocaleString(), sub: 'Awaiting approval', border: 'border-pink-500/15',   labelCls: 'text-pink-400',   glow: 'bg-pink-500',   href: `${adminPath.value}/campaigns` },
  { label: 'Password Tickets',   value: Number(props.stats.pendingPasswordTicketsCount || 0).toLocaleString(), sub: 'Pending reset tickets', border: 'border-amber-500/15', labelCls: 'text-amber-400', glow: 'bg-amber-500', href: `${adminPath.value}/password-tickets` },
  { label: 'Support Tickets',    value: Number(props.stats.openSupportTicketsCount || 0).toLocaleString(), sub: 'Open user tickets', border: 'border-blue-500/15', labelCls: 'text-blue-400', glow: 'bg-blue-500', href: `${adminPath.value}/support-tickets` },
  { label: 'Active Promo Codes', value: Number(props.stats.activeCodes || 0).toLocaleString(), sub: 'Live promo codes', border: 'border-cyan-500/15',   labelCls: 'text-cyan-400',   glow: 'bg-cyan-500',   href: `${adminPath.value}/promo-codes` },
]);
</script>
