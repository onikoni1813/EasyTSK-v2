<template>
  <div class="shimmer" :class="rounded" :style="{ width, height }"></div>
</template>

<script setup>
/**
 * Generic shimmer skeleton block, per Global Project Rules §5:
 * "Use Skeleton Loaders instead of loading spinners for better UX."
 * Compose several of these to build a skeleton placeholder matching real content shape.
 */
defineProps({
  width: { type: String, default: '100%' },
  height: { type: String, default: '14px' },
  rounded: { type: String, default: 'rounded-lg' },
});
</script>
