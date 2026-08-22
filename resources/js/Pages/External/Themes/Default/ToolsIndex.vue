<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

defineProps({
  tools: Array,
})
</script>

<template>
  <Head title="Online Tools & Utilities" />

  <ExternalLayout>
    <div class="space-y-8">
      <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm space-y-2">
        <h1 class="text-3xl font-extrabold text-gray-900">Free Online Tools</h1>
        <p class="text-gray-600">Fast, secure, and privacy-focused online tools running right in your browser.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="t in tools" :key="t.id" class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm hover:border-emerald-300 transition flex flex-col justify-between">
          <div class="space-y-3">
            <span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded">
              {{ t.category?.name || 'Utility' }}
            </span>
            <h2 class="text-lg font-bold text-gray-900 hover:text-emerald-600">
              <Link :href="`/tools/${t.slug}`">{{ t.name }}</Link>
            </h2>
            <p class="text-gray-600 text-xs leading-relaxed line-clamp-2">
              {{ t.summary || 'Fast online processing tool.' }}
            </p>
          </div>

          <div class="pt-4 mt-4 border-t border-gray-100 text-xs text-emerald-600 font-semibold">
            Use Tool →
          </div>
        </div>
      </div>

      <div v-if="!tools?.length" class="bg-white rounded-xl p-12 text-center text-gray-400 border border-gray-200">
        No tools attached to this site yet.
      </div>
    </div>
  </ExternalLayout>
</template>
