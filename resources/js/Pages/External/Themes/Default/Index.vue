<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

defineProps({
  site: Object,
  tools: Array,
  posts: Array,
})
</script>

<template>
  <Head>
    <title>{{ site?.meta_title || site?.name }}</title>
    <meta v-if="site?.meta_description" name="description" :content="site.meta_description" />
  </Head>

  <ExternalLayout>
    <div class="space-y-10">
      <!-- Hero Section -->
      <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-2xl p-8 md:p-12 text-white shadow-md space-y-4">
        <span class="bg-white/10 text-emerald-100 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider">
          Fast & Secure Utilities
        </span>
        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight">{{ site?.name }}</h1>
        <p class="text-emerald-100 text-lg max-w-2xl leading-relaxed">
          {{ site?.meta_description || 'Explore our suite of privacy-first, high-performance web applications and tools.' }}
        </p>
      </div>

      <!-- Featured Tools Grid -->
      <div class="space-y-4">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold text-gray-900">Featured Online Tools</h2>
          <Link href="/tools" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">
            View All Tools →
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div v-for="t in tools" :key="t.id" class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm flex flex-col justify-between hover:border-emerald-400 hover:shadow-md transition">
            <div class="space-y-3">
              <span class="bg-emerald-50 text-emerald-700 text-xs font-semibold px-2.5 py-1 rounded">
                {{ t.category?.name || 'Utility' }}
              </span>
              <h3 class="text-lg font-bold text-gray-900 hover:text-emerald-600">
                <Link :href="`/tools/${t.slug}`">{{ t.name }}</Link>
              </h3>
              <p class="text-gray-600 text-xs leading-relaxed line-clamp-2">
                {{ t.summary || 'Fast online web utility tool.' }}
              </p>
            </div>

            <div class="pt-4 mt-4 border-t border-gray-100 flex items-center justify-between text-xs text-emerald-600 font-semibold">
              <span>Launch Tool</span>
              <span>→</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Guides & Content Section -->
      <div v-if="posts?.length" class="space-y-4 pt-6 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <h2 class="text-2xl font-bold text-gray-900">Productivity Guides & Articles</h2>
          <Link href="/articles" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700">
            Read All Articles →
          </Link>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div v-for="post in posts" :key="post.id" class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm flex flex-col justify-between hover:border-emerald-300 transition">
            <div class="space-y-2">
              <div class="flex items-center gap-2 text-xs text-gray-500">
                <span v-if="post.category" class="bg-emerald-50 text-emerald-700 font-medium px-2 py-0.5 rounded">
                  {{ post.category.name }}
                </span>
                <span>⏱️ {{ post.reading_time_minutes }} min read</span>
              </div>
              <h3 class="text-lg font-bold text-gray-900 hover:text-emerald-600">
                <Link :href="`/p/${post.slug}`">{{ post.title }}</Link>
              </h3>
              <p v-if="post.summary" class="text-gray-600 text-xs line-clamp-2 leading-relaxed">
                {{ post.summary }}
              </p>
            </div>

            <div class="pt-4 mt-4 border-t border-gray-100 text-xs text-emerald-600 font-semibold">
              Read Guide →
            </div>
          </div>
        </div>
      </div>
    </div>
  </ExternalLayout>
</template>
