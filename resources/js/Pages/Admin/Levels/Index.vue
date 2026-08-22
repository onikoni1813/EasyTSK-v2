<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header Section -->
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-extrabold text-white flex items-center gap-2">
            🏆 Levels & Gamification Engine
          </h1>
          <p class="text-xs text-slate-400 mt-1">Configure XP requirements, user distribution, and bonus point rewards for each tier</p>
        </div>
        <button @click="openCreate" class="btn-neon btn-primary px-4 py-2.5 rounded-xl text-xs font-bold text-white shadow-lg shadow-indigo-600/30 flex items-center gap-2">
          <span>➕</span>
          <span>Add New Level</span>
        </button>
      </div>

      <!-- Platform Dynamic Stats Overview -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-card p-4 rounded-2xl border border-slate-800/80 bg-gradient-to-br from-indigo-900/20 to-slate-900/40">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400">Total Tiers</span>
            <span class="p-2 rounded-xl bg-indigo-500/10 text-indigo-400 text-sm">🏆</span>
          </div>
          <div class="text-2xl font-black text-white">{{ stats?.total_levels || levels.length }}</div>
          <div class="text-[11px] text-slate-500 mt-1">Configured level tiers</div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-slate-800/80 bg-gradient-to-br from-emerald-900/20 to-slate-900/40">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400">Active Platform Users</span>
            <span class="p-2 rounded-xl bg-emerald-500/10 text-emerald-400 text-sm">👥</span>
          </div>
          <div class="text-2xl font-black text-white">{{ stats?.total_users || 0 }}</div>
          <div class="text-[11px] text-slate-500 mt-1">Total registered users</div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-slate-800/80 bg-gradient-to-br from-amber-900/20 to-slate-900/40">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400">Highest Level Achieved</span>
            <span class="p-2 rounded-xl bg-amber-500/10 text-amber-400 text-sm">⚡</span>
          </div>
          <div class="text-2xl font-black text-amber-400">Level {{ stats?.max_user_level || 1 }}</div>
          <div class="text-[11px] text-slate-500 mt-1">Top player milestone</div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-slate-800/80 bg-gradient-to-br from-purple-900/20 to-slate-900/40">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-bold text-slate-400">Average User XP</span>
            <span class="p-2 rounded-xl bg-purple-500/10 text-purple-400 text-sm">⭐</span>
          </div>
          <div class="text-2xl font-black text-purple-300">{{ stats?.avg_user_xp || 0 }} <span class="text-xs text-slate-400 font-normal">XP</span></div>
          <div class="text-[11px] text-slate-500 mt-1">Average experience points</div>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div v-if="levelToDelete" class="fixed inset-0 z-[60] p-4 flex items-center justify-center overflow-y-auto" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
            <div class="glass-card max-w-sm w-full p-6 rounded-3xl border border-rose-500/30 animate-slide-in-up text-center relative">
              <div class="w-16 h-16 rounded-full bg-rose-500/20 flex items-center justify-center mx-auto mb-4 border border-rose-500/30">
                <span class="text-2xl">⚠️</span>
              </div>
              <h3 class="text-lg font-black text-white mb-2">Delete Level {{ levelToDelete.level_number }}?</h3>
              
              <div v-if="levelToDelete.users_count > 0" class="p-3 mb-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-semibold text-left">
                ⛔ Cannot delete: <strong>{{ levelToDelete.users_count }} user(s)</strong> are currently at Level {{ levelToDelete.level_number }}. Please reassign or update user levels first.
              </div>
              <p v-else class="text-xs text-slate-400 mb-6">This action cannot be undone. Are you absolutely sure?</p>
              
              <div class="flex gap-3">
                <button @click="levelToDelete = null" class="flex-1 py-2.5 glass-pill text-xs font-bold text-slate-300 hover:text-white rounded-xl border border-white/10 transition-colors">
                  Cancel
                </button>
                <button 
                  v-if="!levelToDelete.users_count || levelToDelete.users_count === 0" 
                  @click="confirmDelete" 
                  class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white rounded-xl transition-colors shadow-[0_0_15px_rgba(225,29,72,0.4)]"
                >
                  Yes, Delete it
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Main Levels Table Card -->
      <div class="glass-card p-5 rounded-3xl border border-slate-800/80">
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left text-slate-300">
            <thead class="text-[11px] uppercase bg-slate-900/90 text-slate-400 border-b border-slate-800">
              <tr>
                <th class="px-4 py-3.5">Level Tier</th>
                <th class="px-4 py-3.5">Required Experience (XP)</th>
                <th class="px-4 py-3.5">Level Unlock Bonus</th>
                <th class="px-4 py-3.5">Assigned Active Users</th>
                <th class="px-4 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="level in levels" :key="level.id" class="hover:bg-white/[0.02] transition-colors">
                <td class="px-4 py-3.5 font-bold text-white">
                  <div class="flex items-center gap-2.5">
                    <span class="w-7 h-7 rounded-lg bg-indigo-600/20 text-indigo-400 border border-indigo-500/30 flex items-center justify-center font-black text-xs">
                      L{{ level.level_number }}
                    </span>
                    <span>Level {{ level.level_number }}</span>
                    <span v-if="level.level_number === 1" class="px-2 py-0.5 rounded-md bg-amber-500/10 text-amber-400 text-[10px] font-bold border border-amber-500/20">
                      Starter Tier
                    </span>
                  </div>
                </td>
                <td class="px-4 py-3.5 text-indigo-400 font-bold">
                  <div class="flex items-center gap-1.5">
                    <span>⚡</span>
                    <span>{{ level.xp_required.toLocaleString() }} XP</span>
                  </div>
                </td>
                <td class="px-4 py-3.5 text-emerald-400 font-bold">
                  <div class="flex items-center gap-1.5">
                    <span>🎁</span>
                    <span>+{{ parseFloat(level.bonus_reward).toFixed(2) }} Points</span>
                  </div>
                </td>
                <td class="px-4 py-3.5">
                  <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" :class="level.users_count > 0 ? 'bg-indigo-500/10 text-indigo-300 border border-indigo-500/20' : 'bg-slate-800/50 text-slate-500 border border-slate-700/30'">
                    <span>👥</span>
                    <span>{{ level.users_count || 0 }} User{{ level.users_count === 1 ? '' : 's' }}</span>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-right space-x-2">
                  <button @click="openEdit(level)" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-[11px] transition-all shadow-md shadow-indigo-900/20">
                    Edit
                  </button>
                  <button 
                    v-if="level.level_number !== 1" 
                    @click="requestDelete(level)" 
                    class="px-3 py-1.5 bg-rose-600/80 hover:bg-rose-600 text-white font-bold rounded-lg text-[11px] transition-all"
                  >
                    Delete
                  </button>
                </td>
              </tr>
              <tr v-if="levels.length === 0">
                <td colspan="5" class="px-4 py-12 text-center text-slate-500">
                  <div class="text-3xl mb-2">🏆</div>
                  <div class="text-sm font-semibold text-slate-400">No levels created yet.</div>
                  <p class="text-xs text-slate-600 mt-1">Click "Add New Level" above to set up level tiers.</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showForm" class="fixed inset-0 z-50 p-4 overflow-y-auto" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
          <div class="flex min-h-full items-center justify-center py-10">
            <div class="glass-card max-w-sm w-full p-6 rounded-3xl border border-indigo-500/30 animate-slide-in-up relative shadow-2xl">
              <h3 class="text-base font-black text-white mb-4 flex items-center gap-2">
                <span>{{ editingId ? '✏️ Edit Level Tier' : '➕ Create New Level Tier' }}</span>
              </h3>

              <form @submit.prevent="submit" class="space-y-4">
                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Level Number</label>
                  <input 
                    v-model.number="form.level_number" 
                    type="number" 
                    min="1" 
                    class="input-dark w-full px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-white text-xs focus:border-indigo-500 focus:outline-none" 
                    required 
                    :disabled="editingId && form.level_number === 1" 
                  />
                  <div v-if="form.errors.level_number" class="text-[10px] text-rose-400 mt-1 font-semibold">{{ form.errors.level_number }}</div>
                </div>

                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">XP Required to Unlock Tier</label>
                  <input 
                    v-model.number="form.xp_required" 
                    type="number" 
                    min="0" 
                    class="input-dark w-full px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-white text-xs focus:border-indigo-500 focus:outline-none" 
                    required 
                  />
                  <div v-if="form.errors.xp_required" class="text-[10px] text-rose-400 mt-1 font-semibold">{{ form.errors.xp_required }}</div>
                </div>

                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Level Reward Bonus (Points)</label>
                  <input 
                    v-model.number="form.bonus_reward" 
                    type="number" 
                    min="0" 
                    step="0.01" 
                    class="input-dark w-full px-3 py-2 rounded-xl bg-slate-900/80 border border-slate-700 text-white text-xs focus:border-indigo-500 focus:outline-none" 
                    required 
                  />
                  <div v-if="form.errors.bonus_reward" class="text-[10px] text-rose-400 mt-1 font-semibold">{{ form.errors.bonus_reward }}</div>
                </div>

                <div class="flex gap-2 pt-2">
                  <button type="button" @click="closeForm" class="flex-1 py-2.5 glass-pill text-xs font-semibold text-slate-400 hover:text-white rounded-xl border border-white/8 transition-colors">
                    Cancel
                  </button>
                  <button type="submit" :disabled="form.processing" class="flex-1 btn-neon btn-primary py-2.5 text-xs font-bold text-white rounded-xl shadow-lg shadow-indigo-600/30">
                    {{ form.processing ? 'Saving...' : (editingId ? 'Update Level' : 'Create Level') }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  levels: Array,
  stats: Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const showForm = ref(false);
const editingId = ref(null);
const levelToDelete = ref(null);

const form = useForm({
  level_number: 1,
  xp_required: 0,
  bonus_reward: 0,
});

const openCreate = () => {
  editingId.value = null;
  form.defaults({
    level_number: props.levels && props.levels.length > 0 ? Math.max(...props.levels.map(l => l.level_number)) + 1 : 1,
    xp_required: 0,
    bonus_reward: 0,
  }).reset();
  form.clearErrors();
  showForm.value = true;
};

const openEdit = (level) => {
  editingId.value = level.id;
  form.defaults({
    level_number: level.level_number,
    xp_required: level.xp_required,
    bonus_reward: level.bonus_reward,
  }).reset();
  form.clearErrors();
  showForm.value = true;
};

const closeForm = () => {
  showForm.value = false;
};

const submit = () => {
  if (editingId.value) {
    form.put(`${adminPath.value}/levels/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeForm(),
    });
  } else {
    form.post(`${adminPath.value}/levels`, {
      preserveScroll: true,
      onSuccess: () => closeForm(),
    });
  }
};

const requestDelete = (level) => {
  levelToDelete.value = level;
};

const confirmDelete = () => {
  if (!levelToDelete.value) return;
  router.delete(`${adminPath.value}/levels/${levelToDelete.value.id}`, { 
    preserveScroll: true,
    onSuccess: () => { levelToDelete.value = null; }
  });
};
</script>

<style scoped>
.modal-enter-active { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.modal-leave-active { transition: all 0.2s ease-in; }
.modal-enter-from   { transform: scale(0.85); opacity: 0; }
.modal-leave-to     { transform: scale(1.05); opacity: 0; }
</style>
