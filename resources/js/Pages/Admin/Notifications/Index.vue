<template>
  <AdminLayout>
    <div class="space-y-8">
      
      <!-- Page Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-800 pb-5">
        <div>
          <div class="flex items-center gap-2">
            <span class="p-2 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xl">🔔</span>
            <h1 class="text-2xl font-black text-white tracking-tight">Notifications Broadcast Center</h1>
          </div>
          <p class="text-xs text-slate-400 mt-1">
            Send instant in-app notification alerts directly to all registered users or targeted user groups.
          </p>
        </div>

        <div class="flex items-center gap-3">
          <div class="px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
            <span class="text-xs font-bold text-slate-300">Total Users: <strong class="text-white">{{ totalUsers }}</strong></span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        
        <!-- Left: Broadcast Form -->
        <div class="lg:col-span-7 space-y-6">
          <div class="glass-card p-6 rounded-2xl border border-slate-800 shadow-2xl relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/5 rounded-full blur-2xl pointer-events-none"></div>

            <h2 class="text-sm font-bold text-white uppercase tracking-wider mb-5 flex items-center gap-2">
              <span>✍️ Compose Broadcast Message</span>
            </h2>

            <form @submit.prevent="submitBroadcast" class="space-y-5">

              <!-- Target Selection -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Target Audience</label>
                <div class="grid grid-cols-3 gap-2.5">
                  <button
                    type="button"
                    @click="form.target_type = 'all'"
                    class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all flex flex-col items-center gap-1"
                    :class="form.target_type === 'all' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-900/30' : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <span>🌐 All Users</span>
                    <span class="text-[10px] text-slate-500 font-normal">({{ totalUsers }} users)</span>
                  </button>

                  <button
                    type="button"
                    @click="form.target_type = 'level'"
                    class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all flex flex-col items-center gap-1"
                    :class="form.target_type === 'level' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-900/30' : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <span>⚡ By Level</span>
                    <span class="text-[10px] text-slate-500 font-normal">Target rank</span>
                  </button>

                  <button
                    type="button"
                    @click="form.target_type = 'user'"
                    class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all flex flex-col items-center gap-1"
                    :class="form.target_type === 'user' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 shadow-md shadow-indigo-900/30' : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <span>👤 Single User</span>
                    <span class="text-[10px] text-slate-500 font-normal">ID / Email</span>
                  </button>
                </div>
              </div>

              <!-- Conditional: Target Level -->
              <div v-if="form.target_type === 'level'" class="p-3.5 rounded-xl bg-slate-900/80 border border-indigo-500/30 space-y-2">
                <label class="block text-xs font-semibold text-indigo-300">Select Target Level</label>
                <select
                  v-model="form.target_level"
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-indigo-500"
                >
                  <option :value="null" disabled>Select Level...</option>
                  <option v-for="l in levels" :key="l.id" :value="l.level_number">
                    Level {{ l.level_number }} (Required XP: {{ l.xp_required }})
                  </option>
                </select>
              </div>

              <!-- Conditional: Target Single User -->
              <div v-if="form.target_type === 'user'" class="p-3.5 rounded-xl bg-slate-900/80 border border-indigo-500/30 space-y-2">
                <label class="block text-xs font-semibold text-indigo-300">Target User ID, Email, or Name</label>
                <input
                  v-model="form.user_query"
                  type="text"
                  placeholder="e.g. 1025 or user@example.com"
                  class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3.5 py-2 text-xs text-white focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-600"
                />
              </div>

              <!-- Delivery Mode Selection -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Delivery Channel / Presentation</label>
                <div class="grid grid-cols-2 gap-3">
                  <button
                    type="button"
                    @click="form.delivery_mode = 'drawer'"
                    class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all flex items-center justify-center gap-2"
                    :class="form.delivery_mode === 'drawer' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 shadow-md' : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <span>📥 Bell Drawer Only</span>
                  </button>

                  <button
                    type="button"
                    @click="form.delivery_mode = 'popup'"
                    class="py-2.5 px-3 rounded-xl border text-xs font-bold transition-all flex items-center justify-center gap-2"
                    :class="form.delivery_mode === 'popup' ? 'bg-amber-600/20 border-amber-500 text-amber-300 shadow-md' : 'bg-slate-900/60 border-slate-800 text-slate-400 hover:border-slate-700'"
                  >
                    <span>📣 Direct Screen Popup</span>
                  </button>
                </div>
                <span class="text-[10px] text-slate-500 mt-1 block">
                  {{ form.delivery_mode === 'popup' ? 'Message will pop up in a modal overlay on user screen when they enter the dashboard.' : 'Message will sit quietly in the top header bell drawer.' }}
                </span>
              </div>

              <!-- Notification Type -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-2">Notification Type & Styling</label>
                <div class="grid grid-cols-4 gap-2">
                  <button
                    type="button"
                    @click="form.type = 'info'"
                    class="py-2 px-2.5 rounded-xl border text-xs font-bold transition-all text-center"
                    :class="form.type === 'info' ? 'bg-indigo-500/20 border-indigo-400 text-indigo-300' : 'bg-slate-900 border-slate-800 text-slate-400'"
                  >
                    🔵 Info
                  </button>
                  <button
                    type="button"
                    @click="form.type = 'success'"
                    class="py-2 px-2.5 rounded-xl border text-xs font-bold transition-all text-center"
                    :class="form.type === 'success' ? 'bg-emerald-500/20 border-emerald-400 text-emerald-300' : 'bg-slate-900 border-slate-800 text-slate-400'"
                  >
                    🎉 Success
                  </button>
                  <button
                    type="button"
                    @click="form.type = 'warning'"
                    class="py-2 px-2.5 rounded-xl border text-xs font-bold transition-all text-center"
                    :class="form.type === 'warning' ? 'bg-amber-500/20 border-amber-400 text-amber-300' : 'bg-slate-900 border-slate-800 text-slate-400'"
                  >
                    ⚠️ Warning
                  </button>
                  <button
                    type="button"
                    @click="form.type = 'danger'"
                    class="py-2 px-2.5 rounded-xl border text-xs font-bold transition-all text-center"
                    :class="form.type === 'danger' ? 'bg-rose-500/20 border-rose-400 text-rose-300' : 'bg-slate-900 border-slate-800 text-slate-400'"
                  >
                    🚨 Danger
                  </button>
                </div>
              </div>

              <!-- Title -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Notification Title</label>
                <input
                  v-model="form.title"
                  type="text"
                  placeholder="e.g. Special Weekend Bonus Offer! 🚀"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-600"
                  required
                />
              </div>

              <!-- Message -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Message Body</label>
                <textarea
                  v-model="form.message"
                  rows="3"
                  placeholder="Write update message details for users here..."
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-600 leading-relaxed"
                  required
                ></textarea>
              </div>

              <!-- Action URL -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Action URL / Redirect Link (Optional)</label>
                <input
                  v-model="form.action_url"
                  type="text"
                  placeholder="e.g. /withdraw or /tasks or https://t.me/yourgroup"
                  class="w-full bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-600"
                />
                <span class="text-[10px] text-slate-500 mt-1 block">
                  When user clicks this notification in their drawer, they will be redirected to this link.
                </span>
              </div>

              <!-- Submit Button -->
              <div class="pt-2">
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                >
                  <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                  <span>🚀 Send Notification Broadcast</span>
                </button>
              </div>

            </form>
          </div>
        </div>

        <!-- Right: Live Preview Panel -->
        <div class="lg:col-span-5 space-y-6">
          <div class="glass-card p-5 rounded-2xl border border-indigo-500/20 bg-[#090d1a] relative">
            <div class="flex items-center justify-between border-b border-white/10 pb-3 mb-4">
              <div class="flex items-center gap-2">
                <span class="text-xs uppercase font-extrabold text-indigo-400 tracking-wider">📱 Live Drawer Preview</span>
              </div>
              <span class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 font-bold">User View</span>
            </div>

            <!-- Drawer Item Preview Card -->
            <div
              class="p-4 rounded-2xl border transition-all relative group bg-gradient-to-r from-indigo-950/40 via-purple-950/20 to-indigo-950/40 border-indigo-500/30 shadow-[0_0_15px_rgba(99,102,241,0.15)]"
            >
              <!-- Unread Indicator Dot -->
              <span class="absolute top-4 right-4 w-2.5 h-2.5 rounded-full bg-indigo-400 shadow-[0_0_8px_rgba(99,102,241,0.8)] animate-pulse"></span>

              <div class="flex items-start gap-3">
                <!-- Icon Badge -->
                <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center text-lg" :class="previewBadgeClass">
                  {{ previewIcon }}
                </div>

                <div class="flex-1 min-w-0 pr-4">
                  <h4 class="text-xs font-bold text-white leading-tight">
                    {{ form.title || 'Notification Title Sample' }}
                  </h4>
                  <p class="text-[11px] text-slate-300 mt-1 leading-relaxed break-words">
                    {{ form.message || 'This is how your update message will appear inside the user notification drawer when delivered.' }}
                  </p>
                  <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-white/5">
                    <span class="text-[10px] text-slate-500 font-medium">Just now</span>
                    <span v-if="form.action_url" class="text-[10px] font-bold text-indigo-400 hover:underline">
                      Link: {{ form.action_url }} &rarr;
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4 p-3 rounded-xl bg-slate-900/60 border border-slate-800 text-[11px] text-slate-400 space-y-1">
              <p class="font-bold text-slate-300">💡 Target Summary:</p>
              <p v-if="form.target_type === 'all'">Broadcast will be delivered to <strong>ALL {{ totalUsers }} users</strong> instantly.</p>
              <p v-else-if="form.target_type === 'level'">Broadcast will be sent to users in <strong>Level {{ form.target_level || 'Select' }}</strong>.</p>
              <p v-else-if="form.target_type === 'user'">Message will be sent directly to user matching <strong>"{{ form.user_query || 'Query' }}"</strong>.</p>
            </div>
          </div>
        </div>

      </div>

      <!-- History Table -->
      <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between bg-slate-900/50">
          <h3 class="text-sm font-bold text-white flex items-center gap-2">
            <span>📋 Sent Notifications Log</span>
          </h3>
          <span class="text-xs text-slate-400">Total Records: {{ notifications.total }}</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-800 bg-slate-950/60 text-[11px] uppercase tracking-wider text-slate-400 font-bold">
                <th class="py-3.5 px-4">Recipient</th>
                <th class="py-3.5 px-4">Type</th>
                <th class="py-3.5 px-4">Title & Message</th>
                <th class="py-3.5 px-4">Action Link</th>
                <th class="py-3.5 px-4">Sent At</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr v-for="n in notifications.data" :key="n.id" class="hover:bg-slate-900/40 transition-colors">
                <td class="py-3.5 px-4">
                  <div v-if="n.user" class="font-bold text-slate-200">
                    {{ n.user.name }}
                    <span class="text-[10px] text-slate-500 block font-normal">{{ n.user.email }}</span>
                  </div>
                  <div v-else class="text-slate-500 font-italic">
                    User #{{ n.user_id }}
                  </div>
                </td>

                <td class="py-3.5 px-4">
                  <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider" :class="typeBadgeClass(n.type)">
                    {{ n.type }}
                  </span>
                </td>

                <td class="py-3.5 px-4 max-w-xs">
                  <h4 class="font-bold text-white text-xs truncate">{{ n.title }}</h4>
                  <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ n.message }}</p>
                </td>

                <td class="py-3.5 px-4">
                  <span v-if="n.action_url" class="text-[11px] text-indigo-400 font-mono bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">
                    {{ n.action_url }}
                  </span>
                  <span v-else class="text-slate-600 text-[11px]">None</span>
                </td>

                <td class="py-3.5 px-4 text-[11px] text-slate-400 whitespace-nowrap">
                  {{ formatDate(n.created_at) }}
                </td>

                <td class="py-3.5 px-4 text-right">
                  <button
                    @click="deleteNotification(n.id)"
                    class="p-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 hover:text-rose-300 transition-colors"
                    title="Delete Notification Log"
                  >
                    🗑️
                  </button>
                </td>
              </tr>

              <tr v-if="!notifications.data || notifications.data.length === 0">
                <td colspan="6" class="py-8 text-center text-slate-500 text-xs">
                  No notifications sent yet. Use the form above to broadcast updates to users!
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="notifications.links && notifications.links.length > 3" class="p-4 border-t border-slate-800 flex justify-end gap-1">
          <Link
            v-for="(link, i) in notifications.links"
            :key="i"
            :href="link.url || '#'"
            v-html="link.label"
            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
            :class="[
              link.active ? 'bg-indigo-600 text-white' : 'bg-slate-900 text-slate-400 hover:bg-slate-800',
              !link.url ? 'opacity-40 pointer-events-none' : ''
            ]"
          />
        </div>
      </div>

    </div>
  </AdminLayout>
</template>

<script setup>
import { computed } from 'vue';
import { useForm, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const props = defineProps({
  notifications: Object,
  levels: Array,
  totalUsers: Number,
});

const form = useForm({
  target_type: 'all',
  target_level: null,
  user_query: '',
  delivery_mode: 'drawer',
  title: '',
  message: '',
  type: 'info',
  action_url: '',
});

const submitBroadcast = () => {
  form.post(`${adminPath.value}/notifications/send`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('title', 'message', 'action_url');
    }
  });
};

const deleteNotification = (id) => {
  if (confirm('Are you sure you want to delete this notification record?')) {
    router.delete(`${adminPath.value}/notifications/${id}`, {
      preserveScroll: true
    });
  }
};

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

const previewBadgeClass = computed(() => typeBadgeClass(form.type));

const previewIcon = computed(() => {
  switch (form.type) {
    case 'success': return '🎉';
    case 'warning': return '⚠️';
    case 'danger': return '🚨';
    default: return '🔔';
  }
});

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
