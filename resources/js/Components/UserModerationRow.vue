<template>
  <div class="glass-pill p-4 rounded-2xl border border-white/5 flex flex-col sm:flex-row sm:items-center gap-3">
    <div class="flex-1 min-w-0">
      <div class="text-sm font-bold text-white">{{ user.name }}</div>
      <div class="text-[11px] text-slate-500">{{ user.email }}</div>
    </div>

    <!-- Risk Score -->
    <div class="flex items-center gap-2 shrink-0">
      <div class="w-20 progress-track">
        <div class="progress-fill"
          :class="user.risk_score > 80 ? 'bg-rose-500' : user.risk_score > 60 ? 'bg-amber-500' : 'bg-indigo-500'"
          :style="{ width: user.risk_score + '%' }"
        ></div>
      </div>
      <input
        v-model.number="riskInput"
        type="number" min="0" max="100"
        class="input-dark text-[11px] py-1 px-2 w-14 shrink-0"
      />
      <button @click="saveRisk" :disabled="savingRisk" class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 shrink-0">
        {{ savingRisk ? '...' : 'Set' }}
      </button>
    </div>

    <!-- Health -->
    <div class="flex items-center gap-2 shrink-0">
      <div class="w-20 progress-track">
        <div class="progress-fill"
          :class="user.health <= 20 ? 'bg-rose-500' : user.health <= 60 ? 'bg-amber-500' : 'bg-emerald-500'"
          :style="{ width: user.health + '%' }"
        ></div>
      </div>
      <input
        v-model.number="healthInput"
        type="number" min="0" max="100"
        class="input-dark text-[11px] py-1 px-2 w-14 shrink-0"
      />
      <button @click="saveHealth" :disabled="savingHealth" class="text-[10px] font-bold text-emerald-400 hover:text-emerald-300 shrink-0">
        {{ savingHealth ? '...' : 'Set' }}
      </button>
    </div>

    <button
      @click="toggleBan"
      :disabled="banning"
      class="badge shrink-0 cursor-pointer hover:opacity-80 transition-opacity"
      :class="user.is_banned ? 'badge-emerald' : 'badge-rose'"
    >
      {{ user.is_banned ? '🔓 Unban' : '🔨 Ban' }}
    </button>
  </div>
</template>

<script setup>
/**
 * Shared moderation row for admin user lists (High Risk Users, Low Health Users, etc.).
 * Wires ban toggle, risk score, and health adjustments to their backend endpoints.
 */
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const props = defineProps({
  user: { type: Object, required: true },
});

const riskInput   = ref(props.user.risk_score);
const healthInput = ref(props.user.health);
const savingRisk  = ref(false);
const savingHealth = ref(false);
const banning     = ref(false);

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
