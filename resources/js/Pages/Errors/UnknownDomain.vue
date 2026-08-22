<script setup>
import { Head } from '@inertiajs/vue3'

defineProps({
  host: String,
})
</script>

<template>
  <Head title="404 - Unknown Domain" />

  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-6 text-center font-sans">
    <div class="max-w-md w-full bg-white rounded-2xl p-8 border border-gray-200 shadow-sm space-y-4">
      <h1 class="text-5xl font-extrabold text-gray-900">404</h1>
      <h2 class="text-xl font-bold text-gray-800">Domain Not Configured</h2>
      <p class="text-gray-600 text-sm">The domain <code class="bg-gray-100 px-2 py-1 rounded text-gray-900 font-mono">{{ host }}</code> is not registered on this server.</p>
    </div>
  </div>
</template>
