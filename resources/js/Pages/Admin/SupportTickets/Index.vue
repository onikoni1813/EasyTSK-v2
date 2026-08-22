<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-black text-white flex items-center gap-2">
            <span>💬 User Support Tickets</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
              {{ stats.open }} Open
            </span>
          </h1>
          <p class="text-xs text-slate-400 mt-1">Manage user support inquiries, chat responses, and resolve user issues</p>
        </div>
      </div>

      <!-- Stats Summary Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60">
          <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Tickets</p>
          <p class="text-xl font-black text-white mt-1">{{ stats.total }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-amber-500/30 bg-amber-500/5">
          <p class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Open</p>
          <p class="text-xl font-black text-amber-300 mt-1">{{ stats.open }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-indigo-500/30 bg-indigo-500/5">
          <p class="text-[11px] font-semibold text-indigo-400 uppercase tracking-wider">In Progress</p>
          <p class="text-xl font-black text-indigo-300 mt-1">{{ stats.in_progress }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/5">
          <p class="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider">Resolved</p>
          <p class="text-xl font-black text-emerald-300 mt-1">{{ stats.resolved }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-slate-700 bg-slate-900/40 col-span-2 sm:col-span-1">
          <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Closed</p>
          <p class="text-xl font-black text-slate-300 mt-1">{{ stats.closed }}</p>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="glass-card p-4 rounded-2xl border border-slate-800 space-y-3">
        <div class="flex flex-col md:flex-row gap-3 items-center justify-between">
          <!-- Status Tabs -->
          <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
            <span class="text-xs text-slate-500 font-semibold mr-1">Status:</span>
            <button
              v-for="st in ['all', 'open', 'in_progress', 'resolved', 'closed']"
              :key="st"
              @click="filterStatus(st)"
              :class="[
                'px-3 py-1.5 rounded-xl text-xs font-semibold capitalize whitespace-nowrap transition-all',
                currentStatus === st ? 'bg-indigo-600 text-white shadow-lg' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'
              ]"
            >
              {{ st.replace('_', ' ') }}
            </button>
          </div>

          <!-- Search Query -->
          <div class="w-full md:w-72 flex gap-2">
            <input
              v-model="searchQuery"
              @keyup.enter="applyFilters"
              type="text"
              placeholder="Search ticket #, subject, user..."
              class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
            />
            <button
              @click="applyFilters"
              class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition-colors"
            >
              Search
            </button>
          </div>
        </div>

        <!-- Category Dropdown & Reset -->
        <div class="flex items-center justify-between border-t border-slate-800/80 pt-3 text-xs">
          <div class="flex items-center gap-2">
            <span class="text-slate-400 font-semibold">Category Filter:</span>
            <select
              v-model="currentCategory"
              @change="applyFilters"
              class="bg-slate-900 border border-slate-800 text-white font-bold rounded-xl px-3 py-1 text-xs focus:outline-none focus:border-indigo-500"
            >
              <option value="all">All Categories</option>
              <option value="withdrawal">💸 Withdrawal</option>
              <option value="task">🧩 Task & Offerwall</option>
              <option value="account">🔐 Account & Security</option>
              <option value="general">💬 General</option>
            </select>
          </div>

          <button
            v-if="currentStatus !== 'all' || currentCategory !== 'all' || searchQuery"
            @click="resetFilters"
            class="text-xs text-rose-400 hover:text-rose-300 font-semibold flex items-center gap-1 transition-colors"
          >
            ✕ Reset Filters
          </button>
        </div>
      </div>

      <!-- Tickets Table -->
      <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-900/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                <th class="py-3.5 px-4">Ticket Number</th>
                <th class="py-3.5 px-4">User</th>
                <th class="py-3.5 px-4">Category</th>
                <th class="py-3.5 px-4">Subject</th>
                <th class="py-3.5 px-4">Priority</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4">Last Reply</th>
                <th class="py-3.5 px-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr v-if="tickets.data.length === 0">
                <td colspan="8" class="py-8 text-center text-slate-500 font-medium">
                  No support tickets found.
                </td>
              </tr>

              <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-900/40 transition-colors">
                <td class="py-3.5 px-4 font-mono font-bold text-indigo-400 whitespace-nowrap">
                  {{ ticket.ticket_number }}
                </td>

                <td class="py-3.5 px-4">
                  <div v-if="ticket.user" class="space-y-0.5">
                    <p class="font-bold text-white">{{ ticket.user.name }}</p>
                    <p class="text-[11px] text-slate-400">{{ ticket.user.phone }}</p>
                  </div>
                </td>

                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase bg-slate-900 text-slate-300 border border-slate-800">
                    {{ ticket.category }}
                  </span>
                </td>

                <td class="py-3.5 px-4 max-w-xs truncate font-semibold text-slate-200">
                  {{ ticket.subject }}
                </td>

                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span
                    :class="[
                      'px-2 py-0.5 rounded text-[10px] font-black uppercase border',
                      ticket.priority === 'high' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 animate-pulse' : '',
                      ticket.priority === 'medium' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40' : '',
                      ticket.priority === 'low' ? 'bg-slate-800 text-slate-400 border-slate-700' : ''
                    ]"
                  >
                    {{ ticket.priority || 'medium' }}
                  </span>
                </td>

                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border',
                      ticket.status === 'open' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 animate-pulse' : '',
                      ticket.status === 'in_progress' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : '',
                      ticket.status === 'resolved' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : '',
                      ticket.status === 'closed' ? 'bg-slate-800 text-slate-400 border-slate-700' : ''
                    ]"
                  >
                    {{ ticket.status.replace('_', ' ') }}
                  </span>
                </td>

                <td class="py-3.5 px-4 text-[11px] text-slate-400 whitespace-nowrap">
                  {{ formatDate(ticket.last_reply_at || ticket.updated_at) }}
                </td>

                <td class="py-3.5 px-4 text-right whitespace-nowrap">
                  <Link
                    :href="`${adminPath}/support-tickets/${ticket.id}`"
                    class="px-3.5 py-1.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs transition-colors shadow-sm inline-block"
                  >
                    Open Thread &rarr;
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="tickets.links.length > 3" class="p-4 border-t border-slate-800 flex justify-end gap-1">
          <Link
            v-for="link in tickets.links"
            :key="link.label"
            :href="link.url || '#'"
            v-html="link.label"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors',
              link.active ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900 border border-slate-800',
              !link.url ? 'opacity-40 cursor-not-allowed' : ''
            ]"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  tickets: Object,
  filters: Object,
  stats: Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const currentStatus   = ref(props.filters.status || 'all');
const currentCategory = ref(props.filters.category || 'all');
const searchQuery     = ref(props.filters.search || '');

const filterStatus = (st) => {
  currentStatus.value = st;
  applyFilters();
};

const applyFilters = () => {
  router.get(`${adminPath.value}/support-tickets`, {
    status:   currentStatus.value,
    category: currentCategory.value,
    search:   searchQuery.value,
  }, { preserveState: true, replace: true });
};

const resetFilters = () => {
  currentStatus.value   = 'all';
  currentCategory.value = 'all';
  searchQuery.value     = '';
  applyFilters();
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
