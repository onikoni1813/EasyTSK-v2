<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
  siteName: String,
  message: String,
})
</script>

<template>
  <Head title="Maintenance Mode" />

  <div class="min-h-screen bg-gray-900 text-white flex flex-col items-center justify-center p-6 text-center font-sans">
    <div class="max-w-lg w-full bg-gray-800 rounded-2xl p-8 border border-gray-700 shadow-xl space-y-6">
      <div class="w-16 h-16 bg-amber-500/10 text-amber-500 rounded-2xl flex items-center justify-center mx-auto text-3xl font-bold">
        🛠️
      </div>
      <h1 class="text-3xl font-bold">{{ siteName || 'Site' }} is Under Maintenance</h1>
      <p class="text-gray-400 text-base leading-relaxed">
        {{ message || 'We are performing scheduled maintenance and updates. Please check back shortly.' }}
      </p>
    </div>
  </div>
</template>
