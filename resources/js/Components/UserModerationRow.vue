<template>
  <div class="glass-pill p-4 rounded-2xl border border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <!-- User Information -->
    <div class="flex-1 min-w-0 flex items-center justify-between sm:justify-start gap-4">
      <div>
        <div class="flex items-center gap-2">
          <Link :href="`${adminPath}/users?search=${encodeURIComponent(user.email || user.name)}`" class="text-sm font-bold text-white hover:text-indigo-400 hover:underline transition-colors">
            {{ user.name }}
          </Link>
          <span v-if="user.is_banned" class="px-1.5 py-0.5 bg-rose-500/20 text-rose-400 text-[10px] font-bold rounded uppercase">Banned</span>
        </div>
        <div class="text-[11px] text-slate-400 flex flex-wrap items-center gap-x-2 gap-y-0.5 mt-0.5">
          <span>{{ user.email }}</span>
          <span v-if="user.phone" class="text-slate-500">• {{ user.phone }}</span>
        </div>
        <div class="text-[10px] text-slate-500 flex items-center gap-3 mt-1">
          <span class="text-emerald-400 font-semibold">Main: {{ user.main_balance ?? 0 }} pts</span>
          <span class="text-amber-400 font-semibold">Hold: {{ user.pending_balance ?? 0 }} pts</span>
        </div>
      </div>
    </div>

    <!-- Moderation Controls -->
    <div class="flex flex-wrap items-center gap-3 shrink-0">
      <!-- Risk Score -->
      <div class="flex items-center gap-1.5 bg-slate-900/60 p-1.5 rounded-xl border border-slate-800">
        <span class="text-[10px] text-slate-400 font-semibold uppercase px-1">Risk</span>
        <div class="w-16 progress-track hidden sm:block">
          <div class="progress-fill"
            :class="riskInput > 80 ? 'bg-rose-500' : riskInput > 60 ? 'bg-amber-500' : 'bg-indigo-500'"
            :style="{ width: riskInput + '%' }"
          ></div>
        </div>
        <input
          v-model.number="riskInput"
          type="number" min="0" max="100"
          class="input-dark text-[11px] py-1 px-2 w-14 text-center shrink-0"
        />
        <button @click="saveRisk" :disabled="savingRisk" class="px-2 py-1 bg-indigo-500/20 hover:bg-indigo-500/30 text-indigo-300 rounded-lg text-[10px] font-bold transition-all shrink-0">
          {{ savingRisk ? '...' : 'Set' }}
        </button>
      </div>

      <!-- Health -->
      <div class="flex items-center gap-1.5 bg-slate-900/60 p-1.5 rounded-xl border border-slate-800">
        <span class="text-[10px] text-slate-400 font-semibold uppercase px-1">Health</span>
        <div class="w-16 progress-track hidden sm:block">
          <div class="progress-fill"
            :class="healthInput <= 20 ? 'bg-rose-500' : healthInput <= 60 ? 'bg-amber-500' : 'bg-emerald-500'"
            :style="{ width: healthInput + '%' }"
          ></div>
        </div>
        <input
          v-model.number="healthInput"
          type="number" min="0" max="100"
          class="input-dark text-[11px] py-1 px-2 w-14 text-center shrink-0"
        />
        <button @click="saveHealth" :disabled="savingHealth" class="px-2 py-1 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 rounded-lg text-[10px] font-bold transition-all shrink-0">
          {{ savingHealth ? '...' : 'Set' }}
        </button>
      </div>

      <!-- Ban / Unban Toggle -->
      <button
        @click="toggleBan"
        :disabled="banning"
        class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer shrink-0"
        :class="user.is_banned ? 'bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40' : 'bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40'"
      >
        {{ banning ? '...' : (user.is_banned ? '🔓 Unban' : '🔨 Ban') }}
      </button>
    </div>
  </div>
</template>

<script setup>
/**
 * Shared moderation row for admin user lists (High Risk Users, Low Health Users, etc.).
 * Wires ban toggle, risk score, and health adjustments to their backend endpoints.
 */
import { ref, computed, watch } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'secret-panel'));

const props = defineProps({
  user: { type: Object, required: true },
});

const riskInput   = ref(props.user.risk_score);
const healthInput = ref(props.user.health);
const savingRisk  = ref(false);
const savingHealth = ref(false);
const banning     = ref(false);

watch(() => props.user, (newUser) => {
  riskInput.value = newUser.risk_score;
  healthInput.value = newUser.health;
}, { deep: true });

const toggleBan = () => {
  banning.value = true;
  router.post(`${adminPath.value}/users/${props.user.id}/ban`, {}, {
    preserveScroll: true,
    onFinish: () => { banning.value = false; },
  });
};

const saveRisk = () => {
  savingRisk.value = true;
  router.post(`${adminPath.value}/users/${props.user.id}/risk-score`, { risk_score: riskInput.value }, {
    preserveScroll: true,
    onFinish: () => { savingRisk.value = false; },
  });
};

const saveHealth = () => {
  savingHealth.value = true;
  router.post(`${adminPath.value}/users/${props.user.id}/health`, { health: healthInput.value }, {
    preserveScroll: true,
    onFinish: () => { savingHealth.value = false; },
  });
};
</script>
