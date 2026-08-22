<template>
  <AdminLayout>
    <div class="space-y-6">

      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-extrabold text-white">🎟️ Promo Code Manager</h1>
          <p class="text-xs text-slate-400">Create, manage, and track promotional reward codes</p>
        </div>
      </div>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <Transition name="modal">
          <div v-if="codeToDelete" class="fixed inset-0 z-[60] p-4 flex items-center justify-center overflow-y-auto" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
            <div class="glass-card max-w-sm w-full p-6 rounded-3xl border border-rose-500/30 text-center relative">
              <div class="w-16 h-16 rounded-full bg-rose-500/20 flex items-center justify-center mx-auto mb-4 border border-rose-500/30">
                <span class="text-2xl">⚠️</span>
              </div>
              <h3 class="text-lg font-black text-white mb-2">Delete Code "{{ codeToDelete.code }}"?</h3>
              <p class="text-xs text-slate-400 mb-6">This action cannot be undone. Users will no longer be able to redeem it.</p>
              
              <div class="flex gap-3">
                <button @click="codeToDelete = null" class="flex-1 py-2.5 glass-pill text-xs font-bold text-slate-300 hover:text-white rounded-xl border border-white/10 transition-colors">
                  Cancel
                </button>
                <button @click="confirmDelete" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-500 text-xs font-bold text-white rounded-xl transition-colors shadow-[0_0_15px_rgba(225,29,72,0.4)]">
                  Yes, Delete
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- Create New Code -->
      <div class="glass-card p-6 rounded-3xl border border-cyan-500/20">
        <div class="section-header mb-5">
          <span class="section-title text-cyan-400">✨ Create Promo Code</span>
          <div class="section-header-line"></div>
        </div>

        <div v-if="Object.keys(form.errors).length > 0" class="mb-5 p-3.5 bg-rose-500/10 border border-rose-500/30 rounded-2xl text-xs text-rose-400 font-semibold flex items-center gap-2">
          <span>⚠️</span>
          <span>Please fix the validation errors below before submitting.</span>
        </div>

        <form @submit.prevent="create" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Code (auto-generated if blank)</label>
            <input
              v-model="form.code"
              type="text"
              class="input-dark font-mono uppercase"
              :class="{ 'border-rose-500/50': form.errors.code }"
              placeholder="e.g. SAVE50"
              maxlength="20"
            />
            <div v-if="form.errors.code" class="text-xs text-rose-400 mt-1 font-semibold">{{ form.errors.code }}</div>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Description</label>
            <input
              v-model="form.description"
              type="text"
              class="input-dark"
              :class="{ 'border-rose-500/50': form.errors.description }"
              placeholder="Campaign name / event"
              maxlength="100"
            />
            <div v-if="form.errors.description" class="text-xs text-rose-400 mt-1 font-semibold">{{ form.errors.description }}</div>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Reward Points</label>
            <input
              v-model.number="form.reward_points"
              type="number"
              class="input-dark"
              :class="{ 'border-rose-500/50': form.errors.reward_points }"
              placeholder="50"
              min="1"
              max="10000"
              required
            />
            <div v-if="form.errors.reward_points" class="text-xs text-rose-400 mt-1 font-semibold">{{ form.errors.reward_points }}</div>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Max Uses</label>
            <input
              v-model.number="form.max_uses"
              type="number"
              class="input-dark"
              :class="{ 'border-rose-500/50': form.errors.max_uses }"
              placeholder="100"
              min="1"
              required
            />
            <div v-if="form.errors.max_uses" class="text-xs text-rose-400 mt-1 font-semibold">{{ form.errors.max_uses }}</div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-semibold text-slate-400">Expires At (optional)</label>
              <button
                v-if="form.expires_at"
                type="button"
                @click="form.expires_at = ''"
                class="text-[11px] text-rose-400 hover:text-rose-300 font-bold transition-colors cursor-pointer"
              >
                ✕ Clear
              </button>
            </div>

            <div class="relative">
              <input
                v-model="form.expires_at"
                type="date"
                :min="minDate"
                class="input-dark w-full border-slate-700 focus:border-cyan-500 text-slate-100 dark-date-picker"
                :class="{ 'border-rose-500/50': form.errors.expires_at }"
              />
            </div>

            <!-- Quick Date Selectors -->
            <div class="flex items-center gap-1.5 mt-2 flex-wrap text-[11px]">
              <span class="text-slate-500 font-medium mr-0.5">Quick add:</span>
              <button
                type="button"
                @click="setQuickDate(7)"
                class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-cyan-600/30 text-slate-300 hover:text-cyan-300 border border-slate-700/60 hover:border-cyan-500/50 transition-all font-semibold"
              >
                +7 Days
              </button>
              <button
                type="button"
                @click="setQuickDate(15)"
                class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-cyan-600/30 text-slate-300 hover:text-cyan-300 border border-slate-700/60 hover:border-cyan-500/50 transition-all font-semibold"
              >
                +15 Days
              </button>
              <button
                type="button"
                @click="setQuickDate(30)"
                class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-cyan-600/30 text-slate-300 hover:text-cyan-300 border border-slate-700/60 hover:border-cyan-500/50 transition-all font-semibold"
              >
                +30 Days
              </button>
              <button
                type="button"
                @click="form.expires_at = ''"
                class="px-2.5 py-1 rounded-lg bg-slate-800/80 hover:bg-rose-900/30 text-slate-400 hover:text-rose-300 border border-slate-700/60 hover:border-rose-500/40 transition-all font-semibold"
              >
                No Expiry
              </button>
            </div>

            <div v-if="form.errors.expires_at" class="text-xs text-rose-400 mt-1 font-semibold">{{ form.errors.expires_at }}</div>
          </div>

          <div class="flex items-end">
            <button
              type="submit"
              :disabled="form.processing"
              class="btn-neon btn-cyan w-full py-3 rounded-2xl text-sm font-black text-white transition-opacity hover:opacity-90 disabled:opacity-50"
            >
              {{ form.processing ? '⏳ Creating...' : '🎟️ Create Code' }}
            </button>
          </div>
        </form>
      </div>

      <!-- Promo Codes List -->
      <div class="glass-card p-6 rounded-3xl border border-slate-800/50">
        <div class="section-header mb-5">
          <span class="section-title">📋 All Promo Codes</span>
          <div class="section-header-line"></div>
          <span class="badge badge-indigo shrink-0">{{ codes.length }}</span>
        </div>

        <div v-if="codes.length === 0" class="text-center py-8 text-slate-500 text-sm">No promo codes created yet.</div>

        <div v-else class="space-y-3">
          <div
            v-for="code in codes"
            :key="code.id"
            class="glass-pill p-4 rounded-2xl border border-white/5 flex flex-col sm:flex-row sm:items-center gap-3"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <span class="font-mono text-sm font-black text-cyan-300">{{ code.code }}</span>
                <span class="badge" :class="code.is_active ? 'badge-emerald' : 'badge-rose'">
                  {{ code.is_active ? 'Active' : 'Disabled' }}
                </span>
              </div>
              <div class="text-[11px] text-slate-400">
                {{ code.description || 'No description' }} ·
                <span class="text-amber-400 font-bold">+{{ code.reward_points }} pts</span> ·
                {{ code.used_count }}/{{ code.max_uses }} uses
                <span v-if="code.expires_at"> · Expires {{ code.expires_at }}</span>
              </div>
            </div>

            <!-- Usage progress -->
            <div class="w-24 shrink-0">
              <div class="flex justify-between text-[10px] text-slate-400 mb-1 font-mono">
                <span>Usage</span>
                <span>{{ Math.round((code.used_count / (code.max_uses || 1)) * 100) }}%</span>
              </div>
              <div class="progress-track">
                <div
                  class="progress-fill bg-gradient-to-r from-cyan-500 to-indigo-500"
                  :style="{ width: Math.min(100, (code.used_count / (code.max_uses || 1)) * 100) + '%' }"
                ></div>
              </div>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button
                @click="toggle(code)"
                class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                :class="code.is_active ? 'bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 border border-rose-500/30' : 'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 border border-emerald-500/30'"
              >
                {{ code.is_active ? '🔴 Disable' : '🟢 Enable' }}
              </button>

              <button
                @click="codeToDelete = code"
                class="px-3 py-1.5 bg-slate-800 hover:bg-rose-600/80 text-slate-300 hover:text-white rounded-xl text-xs font-bold border border-slate-700 transition-colors"
                title="Delete Code"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  codes: Array,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const codeToDelete = ref(null);

const minDate = new Date().toISOString().split('T')[0];

const setQuickDate = (days) => {
  const targetDate = new Date();
  targetDate.setDate(targetDate.getDate() + days);
  form.expires_at = targetDate.toISOString().split('T')[0];
};

const form = useForm({
  code:          '',
  description:   '',
  reward_points: 50,
  max_uses:      100,
  expires_at:    '',
});

const create = () => {
  form.post(`${adminPath.value}/promo-codes`, {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
    },
  });
};

const toggle = (code) => {
  router.post(`${adminPath.value}/promo-codes/${code.id}/toggle`, {}, { preserveScroll: true });
};

const confirmDelete = () => {
  if (!codeToDelete.value) return;
  router.delete(`${adminPath.value}/promo-codes/${codeToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      codeToDelete.value = null;
    },
  });
};
</script>

<style scoped>
.dark-date-picker {
  color-scheme: dark;
}
.dark-date-picker::-webkit-calendar-picker-indicator {
  filter: invert(0.8);
  cursor: pointer;
}
</style>
