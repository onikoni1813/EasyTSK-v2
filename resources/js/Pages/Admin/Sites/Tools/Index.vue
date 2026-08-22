<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  site: Object,
  allTools: Array,
  attachedToolIds: Array,
})

const isAttached = (toolId) => props.attachedToolIds.includes(toolId)

const toggleTool = (tool) => {
  useForm({}).post(route('admin.sites.tools.toggle', [props.site.id, tool.id]))
}
</script>

<template>
  <Head :title="`Site Tools — ${site.name}`" />

  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <Link :href="route('admin.sites.index')" class="hover:text-gray-700">Site Registry</Link>
            <span>/</span>
            <span>{{ site.name }}</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">Site Tool Manager</h1>
          <p class="text-sm text-gray-500">Enable or disable interactive tools for this property.</p>
        </div>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Tool Name</th>
              <th class="px-6 py-3">Component</th>
              <th class="px-6 py-3">Category</th>
              <th class="px-6 py-3">Status on Site</th>
              <th class="px-6 py-3 text-right">Toggle Attachment</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="t in allTools" :key="t.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-semibold text-gray-900">
                {{ t.name }}
                <div class="text-xs text-gray-400 font-mono">/tools/{{ t.slug }}</div>
              </td>
              <td class="px-6 py-4 font-mono text-xs text-emerald-700">{{ t.component_name }}</td>
              <td class="px-6 py-4 text-xs text-gray-500">{{ t.category?.name || 'General' }}</td>
              <td class="px-6 py-4">
                <span :class="[isAttached(t.id) ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600', 'px-2.5 py-1 rounded text-xs font-semibold']">
                  {{ isAttached(t.id) ? 'Enabled' : 'Disabled' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right">
                <button
                  @click="toggleTool(t)"
                  :class="[isAttached(t.id) ? 'bg-rose-50 text-rose-700 hover:bg-rose-100 border-rose-200' : 'bg-emerald-600 text-white hover:bg-emerald-700 border-transparent', 'px-3 py-1.5 rounded text-xs font-medium border transition']"
                >
                  {{ isAttached(t.id) ? 'Detach Tool' : 'Attach Tool' }}
                </button>
              </td>
            </tr>
            <tr v-if="!allTools.length">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">No master tools available in registry. Create tools first.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
