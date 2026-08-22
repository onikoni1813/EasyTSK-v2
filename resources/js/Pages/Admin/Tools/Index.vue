<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  tools: Array,
  categories: Array,
})

const activeTab = ref('tools')

const toolForm = useForm({
  id: null,
  name: '',
  slug: '',
  category_id: '',
  component_name: '',
  summary: '',
  description: '',
  execution_type: 'client_side',
  is_active: true,
})

const categoryForm = useForm({
  id: null,
  name: '',
  slug: '',
  description: '',
  is_active: true,
})

const showToolModal = ref(false)
const showCategoryModal = ref(false)

const openToolModal = (tool = null) => {
  if (tool) {
    toolForm.id = tool.id
    toolForm.name = tool.name
    toolForm.slug = tool.slug
    toolForm.category_id = tool.category_id || ''
    toolForm.component_name = tool.component_name
    toolForm.summary = tool.summary || ''
    toolForm.description = tool.description || ''
    toolForm.execution_type = tool.execution_type || 'client_side'
    toolForm.is_active = Boolean(tool.is_active)
  } else {
    toolForm.reset()
    toolForm.id = null
  }
  showToolModal.value = true
}

const submitTool = () => {
  if (toolForm.id) {
    toolForm.put(route('admin.tools.update', toolForm.id), {
      onSuccess: () => { showToolModal.value = false; toolForm.reset(); }
    })
  } else {
    toolForm.post(route('admin.tools.store'), {
      onSuccess: () => { showToolModal.value = false; toolForm.reset(); }
    })
  }
}

const deleteTool = (tool) => {
  if (confirm(`Delete tool "${tool.name}"?`)) {
    useForm({}).delete(route('admin.tools.destroy', tool.id))
  }
}

const openCategoryModal = (cat = null) => {
  if (cat) {
    categoryForm.id = cat.id
    categoryForm.name = cat.name
    categoryForm.slug = cat.slug
    categoryForm.description = cat.description || ''
    categoryForm.is_active = Boolean(cat.is_active)
  } else {
    categoryForm.reset()
    categoryForm.id = null
  }
  showCategoryModal.value = true
}

const submitCategory = () => {
  if (categoryForm.id) {
    categoryForm.put(route('admin.tool-categories.update', categoryForm.id), {
      onSuccess: () => { showCategoryModal.value = false; categoryForm.reset(); }
    })
  } else {
    categoryForm.post(route('admin.tool-categories.store'), {
      onSuccess: () => { showCategoryModal.value = false; categoryForm.reset(); }
    })
  }
}

const deleteCategory = (cat) => {
  if (confirm(`Delete category "${cat.name}"?`)) {
    useForm({}).delete(route('admin.tool-categories.destroy', cat.id))
  }
}
</script>

<template>
  <Head title="Master Tool Registry" />

  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Master Tool Registry</h1>
          <p class="text-sm text-gray-500">Configure global interactive tools and tool categories across the ecosystem.</p>
        </div>

        <div class="flex gap-3">
          <button v-if="activeTab === 'tools'" @click="openToolModal()" class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg text-sm hover:bg-emerald-700">
            + Add New Tool
          </button>
          <button v-if="activeTab === 'categories'" @click="openCategoryModal()" class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg text-sm hover:bg-emerald-700">
            + Add Tool Category
          </button>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="border-b border-gray-200 flex gap-6 text-sm font-medium">
        <button
          @click="activeTab = 'tools'"
          :class="[activeTab === 'tools' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'pb-3 border-b-2 transition']"
        >
          Tools Registry ({{ tools.length }})
        </button>
        <button
          @click="activeTab = 'categories'"
          :class="[activeTab === 'categories' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'pb-3 border-b-2 transition']"
        >
          Tool Categories ({{ categories.length }})
        </button>
      </div>

      <!-- Tools List -->
      <div v-if="activeTab === 'tools'" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Tool Name</th>
              <th class="px-6 py-3">Component</th>
              <th class="px-6 py-3">Category</th>
              <th class="px-6 py-3">Execution</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="t in tools" :key="t.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-semibold text-gray-900">
                {{ t.name }}
                <div class="text-xs text-gray-400 font-mono">/tools/{{ t.slug }}</div>
              </td>
              <td class="px-6 py-4 font-mono text-xs text-emerald-700">{{ t.component_name }}</td>
              <td class="px-6 py-4 text-xs text-gray-500">{{ t.category?.name || 'Uncategorized' }}</td>
              <td class="px-6 py-4">
                <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                  {{ t.execution_type }}
                </span>
              </td>
              <td class="px-6 py-4">
                <span :class="[t.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800', 'px-2 py-0.5 rounded text-xs font-medium']">
                  {{ t.is_active ? 'Active' : 'Disabled' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openToolModal(t)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                <button @click="deleteTool(t)" class="text-rose-600 hover:text-rose-900 font-medium">Delete</button>
              </td>
            </tr>
            <tr v-if="!tools.length">
              <td colspan="6" class="px-6 py-8 text-center text-gray-400">No tools registered yet.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Categories List -->
      <div v-if="activeTab === 'categories'" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Category Name</th>
              <th class="px-6 py-3">Slug</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-semibold text-gray-900">{{ cat.name }}</td>
              <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ cat.slug }}</td>
              <td class="px-6 py-4">
                <span :class="[cat.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800', 'px-2 py-0.5 rounded text-xs font-medium']">
                  {{ cat.is_active ? 'Active' : 'Disabled' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openCategoryModal(cat)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                <button @click="deleteCategory(cat)" class="text-rose-600 hover:text-rose-900 font-medium">Delete</button>
              </td>
            </tr>
            <tr v-if="!categories.length">
              <td colspan="4" class="px-6 py-8 text-center text-gray-400">No tool categories created yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
