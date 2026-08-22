<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  site: Object,
  pages: Array,
  posts: Array,
  categories: Array,
})

const activeTab = ref('pages') // 'pages' | 'posts' | 'categories'

// Page Form
const pageForm = useForm({
  id: null,
  title: '',
  slug: '',
  content: '',
  meta_title: '',
  meta_description: '',
  meta_keywords: '',
  is_published: true,
})

// Post Form
const postForm = useForm({
  id: null,
  title: '',
  slug: '',
  category_id: '',
  summary: '',
  content: '',
  featured_image: '',
  meta_title: '',
  meta_description: '',
  reading_time_minutes: 3,
  is_published: true,
})

// Category Form
const categoryForm = useForm({
  id: null,
  name: '',
  slug: '',
  description: '',
  is_active: true,
  sort_order: 0,
})

const showPageModal = ref(false)
const showPostModal = ref(false)
const showCategoryModal = ref(false)

const openPageModal = (page = null) => {
  if (page) {
    pageForm.id = page.id
    pageForm.title = page.title
    pageForm.slug = page.slug
    pageForm.content = page.content || ''
    pageForm.meta_title = page.meta_title || ''
    pageForm.meta_description = page.meta_description || ''
    pageForm.meta_keywords = page.meta_keywords || ''
    pageForm.is_published = Boolean(page.is_published)
  } else {
    pageForm.reset()
    pageForm.id = null
  }
  showPageModal.value = true
}

const submitPage = () => {
  if (pageForm.id) {
    pageForm.put(route('admin.sites.pages.update', [props.site.id, pageForm.id]), {
      onSuccess: () => { showPageModal.value = false; pageForm.reset(); }
    })
  } else {
    pageForm.post(route('admin.sites.pages.store', props.site.id), {
      onSuccess: () => { showPageModal.value = false; pageForm.reset(); }
    })
  }
}

const deletePage = (page) => {
  if (confirm(`Delete page "${page.title}"?`)) {
    useForm({}).delete(route('admin.sites.pages.destroy', [props.site.id, page.id]))
  }
}

const openPostModal = (post = null) => {
  if (post) {
    postForm.id = post.id
    postForm.title = post.title
    postForm.slug = post.slug
    postForm.category_id = post.category_id || ''
    postForm.summary = post.summary || ''
    postForm.content = post.content || ''
    postForm.featured_image = post.featured_image || ''
    postForm.meta_title = post.meta_title || ''
    postForm.meta_description = post.meta_description || ''
    postForm.reading_time_minutes = post.reading_time_minutes || 3
    postForm.is_published = Boolean(post.is_published)
  } else {
    postForm.reset()
    postForm.id = null
  }
  showPostModal.value = true
}

const submitPost = () => {
  if (postForm.id) {
    postForm.put(route('admin.sites.posts.update', [props.site.id, postForm.id]), {
      onSuccess: () => { showPostModal.value = false; postForm.reset(); }
    })
  } else {
    postForm.post(route('admin.sites.posts.store', props.site.id), {
      onSuccess: () => { showPostModal.value = false; postForm.reset(); }
    })
  }
}

const deletePost = (post) => {
  if (confirm(`Delete article "${post.title}"?`)) {
    useForm({}).delete(route('admin.sites.posts.destroy', [props.site.id, post.id]))
  }
}

const openCategoryModal = (cat = null) => {
  if (cat) {
    categoryForm.id = cat.id
    categoryForm.name = cat.name
    categoryForm.slug = cat.slug
    categoryForm.description = cat.description || ''
    categoryForm.is_active = Boolean(cat.is_active)
    categoryForm.sort_order = cat.sort_order || 0
  } else {
    categoryForm.reset()
    categoryForm.id = null
  }
  showCategoryModal.value = true
}

const submitCategory = () => {
  if (categoryForm.id) {
    categoryForm.put(route('admin.sites.categories.update', [props.site.id, categoryForm.id]), {
      onSuccess: () => { showCategoryModal.value = false; categoryForm.reset(); }
    })
  } else {
    categoryForm.post(route('admin.sites.categories.store', props.site.id), {
      onSuccess: () => { showCategoryModal.value = false; categoryForm.reset(); }
    })
  }
}

const deleteCategory = (cat) => {
  if (confirm(`Delete category "${cat.name}"?`)) {
    useForm({}).delete(route('admin.sites.categories.destroy', [props.site.id, cat.id]))
  }
}
</script>

<template>
  <Head :title="`Content Engine — ${site.name}`" />

  <AdminLayout>
    <div class="space-y-6">
      <!-- Breadcrumb & Header -->
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <Link :href="route('admin.sites.index')" class="hover:text-gray-700">Site Registry</Link>
            <span>/</span>
            <span>{{ site.name }}</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">Content Engine</h1>
          <p class="text-sm text-gray-500">Manage pages, guides, articles, and content categories for this property.</p>
        </div>

        <div class="flex gap-3">
          <button v-if="activeTab === 'pages'" @click="openPageModal()" class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg text-sm hover:bg-emerald-700">
            + New Page
          </button>
          <button v-if="activeTab === 'posts'" @click="openPostModal()" class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg text-sm hover:bg-emerald-700">
            + New Article
          </button>
          <button v-if="activeTab === 'categories'" @click="openCategoryModal()" class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg text-sm hover:bg-emerald-700">
            + New Category
          </button>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="border-b border-gray-200 flex gap-6 text-sm font-medium">
        <button
          @click="activeTab = 'pages'"
          :class="[activeTab === 'pages' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'pb-3 border-b-2 transition']"
        >
          Pages ({{ pages.length }})
        </button>
        <button
          @click="activeTab = 'posts'"
          :class="[activeTab === 'posts' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'pb-3 border-b-2 transition']"
        >
          Articles / Posts ({{ posts.length }})
        </button>
        <button
          @click="activeTab = 'categories'"
          :class="[activeTab === 'categories' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700', 'pb-3 border-b-2 transition']"
        >
          Categories ({{ categories.length }})
        </button>
      </div>

      <!-- Pages Tab -->
      <div v-if="activeTab === 'pages'" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Title</th>
              <th class="px-6 py-3">Slug</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3">Published</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="p in pages" :key="p.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-semibold text-gray-900">{{ p.title }}</td>
              <td class="px-6 py-4 font-mono text-xs text-gray-500">/{{ p.slug }}</td>
              <td class="px-6 py-4">
                <span :class="[p.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800', 'px-2 py-0.5 rounded text-xs font-medium']">
                  {{ p.is_published ? 'Published' : 'Draft' }}
                </span>
              </td>
              <td class="px-6 py-4 text-xs text-gray-500">{{ p.published_at ? new Date(p.published_at).toLocaleDateString() : '—' }}</td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openPageModal(p)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                <button @click="deletePage(p)" class="text-rose-600 hover:text-rose-900 font-medium">Delete</button>
              </td>
            </tr>
            <tr v-if="!pages.length">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">No custom pages created yet.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Articles Tab -->
      <div v-if="activeTab === 'posts'" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Title</th>
              <th class="px-6 py-3">Category</th>
              <th class="px-6 py-3">Reading Time</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="post in posts" :key="post.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-semibold text-gray-900">{{ post.title }}</td>
              <td class="px-6 py-4 text-xs text-gray-500">{{ post.category?.name || 'Uncategorized' }}</td>
              <td class="px-6 py-4 text-xs text-gray-500">{{ post.reading_time_minutes }} min</td>
              <td class="px-6 py-4">
                <span :class="[post.is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-800', 'px-2 py-0.5 rounded text-xs font-medium']">
                  {{ post.is_published ? 'Published' : 'Draft' }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openPostModal(post)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                <button @click="deletePost(post)" class="text-rose-600 hover:text-rose-900 font-medium">Delete</button>
              </td>
            </tr>
            <tr v-if="!posts.length">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">No articles created yet.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Categories Tab -->
      <div v-if="activeTab === 'categories'" class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Category Name</th>
              <th class="px-6 py-3">Slug</th>
              <th class="px-6 py-3">Sort Order</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="cat in categories" :key="cat.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-semibold text-gray-900">{{ cat.name }}</td>
              <td class="px-6 py-4 font-mono text-xs text-gray-500">{{ cat.slug }}</td>
              <td class="px-6 py-4 text-xs text-gray-500">{{ cat.sort_order }}</td>
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
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">No categories created yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
