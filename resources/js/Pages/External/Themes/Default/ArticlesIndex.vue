<script setup>
import { ref, watch } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

const props = defineProps({
  posts: Object,
  categories: Array,
  filters: Object,
})

const searchQuery = ref(props.filters?.search || '')
const selectedCategory = ref(props.filters?.category || '')

const applyFilters = () => {
  router.get(
    '/articles',
    {
      search: searchQuery.value || undefined,
      category: selectedCategory.value || undefined,
    },
    { preserveState: true, replace: true }
  )
}

const selectCategory = (slug) => {
  selectedCategory.value = selectedCategory.value === slug ? '' : slug
  applyFilters()
}
</script>

<template>
  <Head title="Articles & Guides" />

  <ExternalLayout>
    <div class="space-y-8">
      <!-- Page Header -->
      <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm space-y-4">
        <h1 class="text-3xl font-extrabold text-gray-900">Articles & Technical Guides</h1>
        <p class="text-gray-600">Explore technical tutorials, developer best practices, and architecture insights.</p>

        <!-- Search Bar -->
        <div class="flex flex-col md:flex-row gap-4 pt-2">
          <input
            v-model="searchQuery"
            @input="applyFilters"
            type="text"
            placeholder="🔍 Search articles by keyword..."
            class="flex-1 rounded-xl border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm p-3"
          />
        </div>

        <!-- Category Filter Pills -->
        <div v-if="categories?.length" class="flex flex-wrap gap-2 pt-2">
          <button
            @click="selectCategory('')"
            :class="[selectedCategory === '' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200', 'px-3 py-1.5 rounded-lg text-xs font-medium transition']"
          >
            All Categories
          </button>
          <button
            v-for="cat in categories"
            :key="cat.id"
            @click="selectCategory(cat.slug)"
            :class="[selectedCategory === cat.slug ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200', 'px-3 py-1.5 rounded-lg text-xs font-medium transition']"
          >
            {{ cat.name }}
          </button>
        </div>
      </div>

      <!-- Articles Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div v-for="post in posts.data" :key="post.id" class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm flex flex-col justify-between hover:border-blue-300 transition">
          <div class="space-y-3">
            <div class="flex items-center gap-2 text-xs text-gray-500">
              <span v-if="post.category" class="bg-blue-50 text-blue-700 font-medium px-2 py-0.5 rounded">
                {{ post.category.name }}
              </span>
              <span>⏱️ {{ post.reading_time_minutes }} min read</span>
            </div>
            <h2 class="text-xl font-bold text-gray-900 hover:text-blue-600">
              <Link :href="`/p/${post.slug}`">{{ post.title }}</Link>
            </h2>
            <p v-if="post.summary" class="text-gray-600 text-sm line-clamp-3 leading-relaxed">
              {{ post.summary }}
            </p>
          </div>

          <div class="pt-4 mt-4 border-t border-gray-100 flex justify-between items-center text-xs text-blue-600 font-semibold">
            <span>Read full guide →</span>
          </div>
        </div>
      </div>

      <div v-if="!posts.data?.length" class="bg-white rounded-xl p-12 text-center text-gray-400 border border-gray-200">
        No articles found matching your search. Try adjusting keywords or category filters.
      </div>
    </div>
  </ExternalLayout>
</template>
