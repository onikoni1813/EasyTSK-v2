<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-extrabold text-white">🏆 Levels Manager</h1>
          <p class="text-xs text-slate-400">Configure XP requirements and bonuses for each level</p>
        </div>
        <button @click="openCreate" class="btn-neon btn-primary px-4 py-2.5 rounded-xl text-xs font-bold text-white">
          ➕ New Level
        </button>
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
              <p class="text-xs text-slate-400 mb-6">This action cannot be undone. Are you absolutely sure?</p>
              
              <div class="flex gap-3">
                <button @click="levelToDelete = null" class="flex-1 py-2.5 glass-pill text-xs font-bold text-slate-300 hover:text-white rounded-xl border border-white/10 transition-colors">
                  Cancel
                </button>
                <button @click="confirmDelete" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white rounded-xl transition-colors shadow-[0_0_15px_rgba(225,29,72,0.4)]">
                  Yes, Delete it
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <div class="glass-card p-5 rounded-3xl border border-slate-800">
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left text-slate-300">
            <thead class="text-[11px] uppercase bg-slate-900 text-slate-400">
              <tr>
                <th class="px-4 py-3">Level Number</th>
                <th class="px-4 py-3">XP Required</th>
                <th class="px-4 py-3">Bonus Reward (Pts)</th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="level in levels" :key="level.id" class="border-b border-slate-800/60 hover:bg-white/[0.02]">
                <td class="px-4 py-3 font-bold text-white">Level {{ level.level_number }}</td>
                <td class="px-4 py-3 text-indigo-400 font-bold">{{ level.xp_required }} XP</td>
                <td class="px-4 py-3 text-emerald-400 font-bold">+{{ level.bonus_reward }} Pts</td>
                <td class="px-4 py-3 text-right space-x-2">
                  <button @click="openEdit(level)" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-[11px]">Edit</button>
                  <button v-if="level.level_number !== 1" @click="requestDelete(level)" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-lg text-[11px]">Delete</button>
                </td>
              </tr>
              <tr v-if="levels.length === 0">
                <td colspan="4" class="px-4 py-8 text-center text-slate-500">No levels created yet.</td>
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
            <div class="glass-card max-w-sm w-full p-6 rounded-3xl border border-indigo-500/30 animate-slide-in-up relative">
              <h3 class="text-base font-black text-white mb-4">{{ editingId ? '✏️ Edit Level' : '➕ New Level' }}</h3>

              <form @submit.prevent="submit" class="space-y-4">
                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Level Number</label>
                  <input v-model.number="form.level_number" type="number" min="1" class="input-dark" required :disabled="editingId && form.level_number === 1" />
                  <div v-if="form.errors.level_number" class="text-[10px] text-rose-500 mt-1">{{ form.errors.level_number }}</div>
                </div>

                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">XP Required to Unlock</label>
                  <input v-model.number="form.xp_required" type="number" min="0" class="input-dark" required />
                  <div v-if="form.errors.xp_required" class="text-[10px] text-rose-500 mt-1">{{ form.errors.xp_required }}</div>
                </div>

                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Bonus Reward (Points)</label>
                  <input v-model.number="form.bonus_reward" type="number" min="0" step="0.01" class="input-dark" required />
                  <div v-if="form.errors.bonus_reward" class="text-[10px] text-rose-500 mt-1">{{ form.errors.bonus_reward }}</div>
                </div>

                <div class="flex gap-2 pt-2">
                  <button type="button" @click="closeForm" class="flex-1 py-2.5 glass-pill text-xs text-slate-400 rounded-xl border border-white/8">Cancel</button>
                  <button type="submit" :disabled="form.processing" class="flex-1 btn-neon btn-primary py-2.5 text-xs font-bold text-white rounded-xl">
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
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  levels: Array,
});

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
    level_number: props.levels.length > 0 ? Math.max(...props.levels.map(l => l.level_number)) + 1 : 1,
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
    form.put(`/admin/levels/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeForm(),
    });
  } else {
    form.post('/admin/levels', {
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
  router.delete(`/admin/levels/${levelToDelete.value.id}`, { 
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
