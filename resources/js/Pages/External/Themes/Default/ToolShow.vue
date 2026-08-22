<script setup>
import { computed } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

import WordCounter from '@/Components/Tools/WordCounter.vue'
import JsonFormatter from '@/Components/Tools/JsonFormatter.vue'
import Base64Tool from '@/Components/Tools/Base64Tool.vue'
import PasswordGenerator from '@/Components/Tools/PasswordGenerator.vue'
import QrCodeGenerator from '@/Components/Tools/QrCodeGenerator.vue'
import UrlEncoder from '@/Components/Tools/UrlEncoder.vue'
import CaseConverter from '@/Components/Tools/CaseConverter.vue'

const props = defineProps({
  tool: Object,
})

const toolMap = {
  WordCounter,
  JsonFormatter,
  Base64Tool,
  PasswordGenerator,
  QrCodeGenerator,
  UrlEncoder,
  CaseConverter,
}

const ActiveComponent = computed(() => toolMap[props.tool?.component_name] || null)
</script>

<template>
  <Head>
    <title>{{ tool?.meta_title || tool?.name }}</title>
    <meta v-if="tool?.meta_description" name="description" :content="tool.meta_description" />
    <meta v-if="tool?.meta_keywords" name="keywords" :content="tool.meta_keywords" />
  </Head>

  <ExternalLayout>
    <div class="space-y-6">
      <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm space-y-4">
        <div class="flex items-center gap-2 text-xs font-semibold text-emerald-700 bg-emerald-50 w-max px-3 py-1 rounded-full border border-emerald-100">
          <span>⚡ {{ tool?.category || 'Utility Tool' }}</span>
          <span>•</span>
          <span class="capitalize">{{ tool?.execution_type?.replace('_', ' ') }}</span>
        </div>
        <h1 class="text-3xl font-extrabold text-gray-900">{{ tool?.name }}</h1>
        <p v-if="tool?.summary" class="text-gray-600 text-base leading-relaxed">{{ tool.summary }}</p>
      </div>

      <!-- Active Tool Interactive Component Container -->
      <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm">
        <component v-if="ActiveComponent" :is="ActiveComponent" />
        <div v-else class="py-12 text-center text-gray-400">
          Tool runner component ({{ tool?.component_name }}) is ready.
        </div>
      </div>

      <div v-if="tool?.description" class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm space-y-4">
        <h2 class="text-xl font-bold text-gray-900">About {{ tool?.name }}</h2>
        <div class="prose max-w-none text-gray-600 text-sm leading-relaxed">
          {{ tool.description }}
        </div>
      </div>
    </div>
  </ExternalLayout>
</template>
