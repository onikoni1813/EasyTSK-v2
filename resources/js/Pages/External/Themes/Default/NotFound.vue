<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

defineProps({
  slug: String,
})
</script>

<template>
  <Head title="404 - Page Not Found" />

  <ExternalLayout>
    <div class="bg-white rounded-xl p-12 border border-gray-200 shadow-sm text-center space-y-4">
      <h1 class="text-6xl font-extrabold text-gray-900">404</h1>
      <h2 class="text-2xl font-bold text-gray-800">Page Not Found</h2>
      <p class="text-gray-600 max-w-md mx-auto">The requested page does not exist or has been removed.</p>
      <div class="pt-4">
        <Link href="/" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-emerald-600 text-white font-medium hover:bg-emerald-700 transition">
          Back to Homepage
        </Link>
      </div>
    </div>
  </ExternalLayout>
</template>
