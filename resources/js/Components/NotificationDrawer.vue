<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-hidden">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

        <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
          <Transition name="slide">
            <div v-if="isOpen" class="w-screen max-w-md bg-[#090d1a] border-l border-indigo-500/20 shadow-2xl flex flex-col">
              
              <!-- Drawer Header -->
              <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between bg-[#040612]/80 backdrop-blur-md">
                <div class="flex items-center gap-2.5">
                  <div class="relative">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-rose-500 rounded-full animate-ping"></span>
                  </div>
                  <h2 class="text-base font-bold text-white tracking-wide">Notifications</h2>
                  <span v-if="unreadCount > 0" class="px-2 py-0.5 text-[10px] font-black bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full">
                    {{ unreadCount }} new
                  </span>
                </div>

                <div class="flex items-center gap-2">
                  <button 
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    class="text-[11px] font-semibold text-indigo-400 hover:text-indigo-300 hover:underline px-2 py-1 rounded transition-colors"
                  >
                    Mark all read
                  </button>
                  <button 
                    @click="$emit('close')" 
                    class="w-8 h-8 rounded-xl bg-white/5 hover:bg-white/10 text-slate-400 hover:text-white flex items-center justify-center transition-all"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <!-- Notifications List -->
              <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scrollbar">
                <template v-if="notificationsList.length > 0">
                  <div
                    v-for="item in notificationsList"
                    :key="item.id"
                    @click="handleClick(item)"
                    class="p-3.5 rounded-2xl border transition-all cursor-pointer relative group"
                    :class="[
                      item.read_at ? 'bg-white/[0.02] border-white/5 opacity-75 hover:opacity-100 hover:border-white/15' : 'bg-gradient-to-r from-indigo-950/40 via-purple-950/20 to-indigo-950/40 border-indigo-500/30 shadow-[0_0_12px_rgba(99,102,241,0.15)]'
                    ]"
                  >
                    <!-- Unread Indicator Dot -->
                    <span v-if="!item.read_at" class="absolute top-3.5 right-3.5 w-2 h-2 rounded-full bg-indigo-400 shadow-[0_0_8px_rgba(99,102,241,0.8)] animate-pulse"></span>

                    <div class="flex items-start gap-3">
                      <!-- Icon Badge -->
                      <div class="shrink-0 w-9 h-9 rounded-xl flex items-center justify-center text-base" :class="typeBadgeClass(item.type)">
                        {{ typeIcon(item.type, item.title) }}
                      </div>

                      <div class="flex-1 min-w-0 pr-4">
                        <h4 class="text-xs font-bold text-white group-hover:text-indigo-300 transition-colors truncate">
                          {{ item.title }}
                        </h4>
                        <p class="text-[11px] text-slate-300 mt-0.5 leading-relaxed break-words">
                          {{ item.message }}
                        </p>
                        <span class="text-[10px] text-slate-500 mt-2 block font-medium">
                          {{ formatDate(item.created_at) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </template>

                <!-- Empty State -->
                <div v-else class="h-64 flex flex-col items-center justify-center text-center p-6">
                  <div class="w-16 h-16 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-3xl mb-3 text-indigo-400">
                    🔕
                  </div>
                  <h3 class="text-sm font-bold text-white">No notifications yet</h3>
                  <p class="text-xs text-slate-400 mt-1 max-w-xs">
                    When you earn rewards, level up, or withdraw payouts, your updates will appear here!
                  </p>
                </div>
              </div>

            </div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  isOpen: Boolean,
  notifications: {
    type: Array,
    default: () => []
  },
  unreadCount: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['close', 'updated']);

const notificationsList = computed(() => props.notifications || []);

const typeBadgeClass = (type) => {
  switch (type) {
    case 'success':
      return 'bg-emerald-500/15 border border-emerald-500/30 text-emerald-400';
    case 'warning':
      return 'bg-amber-500/15 border border-amber-500/30 text-amber-400';
    case 'danger':
      return 'bg-rose-500/15 border border-rose-500/30 text-rose-400';
    default:
      return 'bg-indigo-500/15 border border-indigo-500/30 text-indigo-400';
  }
};

const typeIcon = (type, title = '') => {
  if (title.includes('Level')) return '⚡';
  if (title.includes('Referral') || title.includes('Refer')) return '🎁';
  if (title.includes('Contest') || title.includes('Champion')) return '🏆';
  if (title.includes('Withdrawal Paid') || title.includes('Paid')) return '💸';
  if (title.includes('Rejected')) return '❌';
  if (title.includes('Welcome')) return '🚀';
  
  switch (type) {
    case 'success': return '🎉';
    case 'warning': return '⚠️';
    case 'danger': return '❌';
    default: return '🔔';
  }
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const handleClick = (item) => {
  if (!item.read_at) {
    router.post(`/api/notifications/${item.id}/read`, {}, {
      preserveScroll: true,
      onFinish: () => {
        if (item.action_url) {
          emit('close');
          router.visit(item.action_url);
        }
      }
    });
  } else if (item.action_url) {
    emit('close');
    router.visit(item.action_url);
  }
};

const markAllAsRead = () => {
  router.post('/api/notifications/read-all', {}, {
    preserveScroll: true
  });
};
</script>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}

.slide-enter-active, .slide-leave-active {
  transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-enter-from, .slide-leave-to {
  transform: translateX(100%);
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.2);
  border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.4);
}
</style>
