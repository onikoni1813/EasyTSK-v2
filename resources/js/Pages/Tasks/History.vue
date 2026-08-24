<template>
  <AppLayout>
    <div class="space-y-5 animate-slide-in-up max-w-4xl mx-auto">
      
      <!-- Header -->
      <div class="glass-card p-4 sm:p-6 rounded-3xl border border-indigo-500/15 relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-36 h-36 bg-indigo-500/8 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex items-center justify-between">
          <div>
            <div class="flex items-center gap-3">
              <Link href="/tasks" class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 flex items-center justify-center text-slate-300 transition-colors">
                ←
              </Link>
              <h1 class="text-xl sm:text-2xl font-black text-white">Task History</h1>
            </div>
            <p class="text-xs text-slate-400 mt-1 ml-11">All your completed, pending, and rejected tasks.</p>
          </div>
        </div>
      </div>

      <!-- History List -->
      <div class="glass-card rounded-3xl border border-violet-500/15 overflow-hidden p-4 sm:p-6">
        
        <div v-if="!taskHistory.data || taskHistory.data.length === 0" class="text-center py-10">
          <div class="text-4xl mb-3">📭</div>
          <p class="text-sm font-bold text-white mb-1">No task history yet</p>
          <p class="text-xs text-slate-500">Complete some tasks to see your history here.</p>
        </div>

        <div v-else class="space-y-3">
          <div v-for="item in taskHistory.data" :key="item.id"
            class="flex items-center gap-3 p-3.5 rounded-2xl bg-white/[0.02] border border-white/[0.05] hover:border-violet-500/20 transition-all"
          >
            <!-- Type Icon -->
            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg shrink-0"
              :class="{
                'bg-violet-500/15 border border-violet-500/20 text-violet-300': item.task_type === 'community',
                'bg-indigo-500/15 border border-indigo-500/20': item.task_type === 'shortlink',
                'bg-amber-500/15 border border-amber-500/20': item.task_type === 'secret_code',
                'bg-emerald-500/15 border border-emerald-500/20': item.task_type === 'social',
              }"
            >
              {{ taskIcon(item.task_type) }}
            </div>

            <!-- Task Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                <span v-if="item.task_type === 'community'" class="px-2 py-0.5 rounded bg-violet-500/20 text-violet-300 border border-violet-500/30 text-[10px] font-bold">📢 Community</span>
                <span class="text-sm font-bold text-white truncate">{{ item.task_title }}</span>
                <!-- Status Badge -->
                <span class="shrink-0 px-2 py-0.5 rounded text-[10px] font-bold uppercase"
                  :class="{
                    'bg-amber-500/20 text-amber-400 border border-amber-500/25': item.status === 'pending',
                    'bg-emerald-500/20 text-emerald-400 border border-emerald-500/25': item.status === 'approved',
                    'bg-rose-500/20 text-rose-400 border border-rose-500/25': item.status === 'rejected',
                  }"
                >
                  {{ item.status === 'pending' ? '⏳ Review' : item.status === 'approved' ? '✅ Approved' : '❌ Rejected' }}
                </span>
              </div>
              <div class="text-xs text-slate-500">{{ item.created_at || item.submitted_at }}</div>
              <!-- Admin Note for Rejected -->
              <div v-if="item.status === 'rejected' && item.admin_note"
                class="mt-1.5 text-[11px] text-rose-400 bg-rose-500/10 px-2.5 py-1.5 rounded-lg border border-rose-500/15 inline-block"
              >
                ⚠️ {{ item.admin_note }}
              </div>
            </div>

            <!-- Reward -->
            <div class="text-right shrink-0">
              <div class="text-sm font-black"
                :class="item.status === 'approved' ? 'text-emerald-400' : 'text-slate-500'"
              >+{{ item.reward_coins }}</div>
              <div class="text-[10px] text-slate-600">pts</div>
            </div>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="taskHistory.links && taskHistory.links.length > 3" class="mt-6 flex flex-wrap justify-center gap-1.5">
          <Link v-for="(link, i) in taskHistory.links" :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1.5 rounded-lg text-xs font-medium border transition-colors"
            :class="[
              link.active 
                ? 'bg-indigo-500/20 border-indigo-500/40 text-indigo-300' 
                : 'bg-white/5 border-white/10 text-slate-400 hover:bg-white/10 hover:text-white',
              !link.url ? 'opacity-50 cursor-not-allowed' : ''
            ]"
            preserve-scroll
          />
        </div>
        
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  taskHistory: Object,
});

const taskIcon = (type) => {
  switch (type) {
    case 'community': return '📢';
    case 'shortlink': return '🔗';
    case 'secret_code': return '🔑';
    case 'social': return '📱';
    default: return '📝';
  }
};
</script>
