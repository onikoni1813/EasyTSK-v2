<script setup>
import { Head, Link } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

defineProps({
  post: Object,
  relatedPosts: Array,
})
</script>

<template>
  <Head>
    <title>{{ post?.meta_title || post?.title }}</title>
    <meta v-if="post?.meta_description" name="description" :content="post.meta_description" />
    <meta v-if="post?.meta_keywords" name="keywords" :content="post.meta_keywords" />
  </Head>

  <ExternalLayout>
    <div class="space-y-10">
      <article class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm space-y-6">
        <div class="space-y-3">
          <div class="flex items-center gap-3 text-xs text-gray-500">
            <span v-if="post?.category" class="bg-blue-50 text-blue-700 font-semibold px-2.5 py-1 rounded-md border border-blue-100">
              {{ post.category.name }}
            </span>
            <span v-if="post?.reading_time_minutes">⏱️ {{ post.reading_time_minutes }} min read</span>
            <span v-if="post?.published_at">📅 {{ post.published_at }}</span>
            <span v-if="post?.author_name">✍️ {{ post.author_name }}</span>
          </div>
          <h1 class="text-3xl font-extrabold text-gray-900 leading-tight">{{ post?.title }}</h1>
          <p v-if="post?.summary" class="text-gray-600 text-lg italic border-l-4 border-blue-500 pl-4 py-1">
            {{ post.summary }}
          </p>
        </div>

        <img v-if="post?.featured_image" :src="post.featured_image" :alt="post.title" class="w-full h-64 object-cover rounded-lg border border-gray-100" />

        <div class="prose max-w-none text-gray-700 leading-relaxed text-base space-y-4">
          {{ post?.content }}
        </div>

        <div class="pt-6 border-t border-gray-100">
          <Link href="/articles" class="text-blue-600 font-medium hover:underline text-sm flex items-center gap-1">
            ← Back to all articles
          </Link>
        </div>
      </article>

      <!-- Related Articles Section -->
      <div v-if="relatedPosts?.length" class="space-y-4">
        <h2 class="text-xl font-bold text-gray-900">Related Tutorials & Guides</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div v-for="rel in relatedPosts" :key="rel.id" class="bg-white rounded-xl p-5 border border-gray-200 shadow-sm flex flex-col justify-between hover:border-blue-300 transition">
            <div class="space-y-2">
              <span v-if="rel.category" class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded">
                {{ rel.category.name }}
              </span>
              <h3 class="text-base font-bold text-gray-900 hover:text-blue-600 leading-snug">
                <Link :href="`/p/${rel.slug}`">{{ rel.title }}</Link>
              </h3>
            </div>
            <div class="pt-3 mt-3 border-t border-gray-100 text-xs text-blue-600 font-semibold">
              Read Guide →
            </div>
          </div>
        </div>
      </div>
    </div>
  </ExternalLayout>
</template>
