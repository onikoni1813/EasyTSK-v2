<template>
  <button
    @click="$emit('run')"
    :disabled="disabled || loading"
    class="relative flex items-center gap-2.5 px-4 py-3 rounded-2xl border font-semibold text-sm transition-all duration-200 w-full text-left group"
    :class="buttonClasses"
  >
    <!-- Spinner or Icon -->
    <span class="text-base flex-shrink-0">
      <LoaderIcon v-if="loading" class="w-4 h-4 animate-spin" />
      <span v-else>{{ cmd.icon }}</span>
    </span>

    <!-- Label -->
    <span class="font-mono text-xs leading-snug flex-1 min-w-0 truncate">{{ cmd.label }}</span>

    <!-- Danger badge -->
    <span v-if="cmd.danger && !disabled && !loading" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-500/20 text-rose-400 flex-shrink-0">
      ⚠
    </span>

    <!-- Running glow -->
    <div v-if="loading" class="absolute inset-0 rounded-2xl animate-pulse bg-white/5"></div>
  </button>
</template>

<script setup>
import { computed } from 'vue';
import { LoaderIcon } from 'lucide-vue-next';

const props = defineProps({
  cmd:      { type: Object, required: true },
  loading:  { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
});

defineEmits(['run']);

const colorMap = {
  orange:  { active: 'border-orange-500/40 bg-orange-500/10 text-orange-300 hover:bg-orange-500/20 hover:border-orange-500/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
  indigo:  { active: 'border-indigo-500/40 bg-indigo-500/10 text-indigo-300 hover:bg-indigo-500/20 hover:border-indigo-500/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
  emerald: { active: 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20 hover:border-emerald-500/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
  amber:   { active: 'border-amber-500/40 bg-amber-500/10 text-amber-300 hover:bg-amber-500/20 hover:border-amber-500/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
  rose:    { active: 'border-rose-500/40 bg-rose-500/10 text-rose-300 hover:bg-rose-500/20 hover:border-rose-500/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
  sky:     { active: 'border-sky-500/40 bg-sky-500/10 text-sky-300 hover:bg-sky-500/20 hover:border-sky-500/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
  violet:  { active: 'border-violet-500/40 bg-violet-500/10 text-violet-300 hover:bg-violet-500/20 hover:border-violet-500/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
  slate:   { active: 'border-slate-600/40 bg-slate-700/20 text-slate-300 hover:bg-slate-700/40 hover:border-slate-600/60', disabled: 'border-slate-700/40 bg-slate-800/30 text-slate-500' },
};

const buttonClasses = computed(() => {
  const color = colorMap[props.cmd.color] || colorMap.slate;
  if (props.disabled || props.loading) return color.disabled + ' cursor-not-allowed';
  return color.active + ' cursor-pointer';
});
</script>
