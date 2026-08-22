<script setup>
import { Head } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

defineProps({
  page: Object,
})
</script>

<template>
  <Head :title="page?.title" />

  <ExternalLayout>
    <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm space-y-6">
      <h1 class="text-3xl font-bold text-gray-900">{{ page?.title }}</h1>
      <div class="prose max-w-none text-gray-700 leading-relaxed">
        {{ page?.content }}
      </div>
    </div>
  </ExternalLayout>
</template>
