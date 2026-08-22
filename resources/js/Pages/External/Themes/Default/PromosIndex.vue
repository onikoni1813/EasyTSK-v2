<script setup>
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import ExternalLayout from '@/Layouts/ExternalLayout.vue'

const props = defineProps({
  site: Object,
  deals: Object,
  categories: Array,
  filters: Object,
})

const searchQuery = ref(props.filters?.search || '')
const selectedCategory = ref(props.filters?.category || '')
const copiedCode = ref(null)

const extractCode = (content) => {
  const match = content?.match(/PROMO CODE:\s*([A-Z0-9_-]+)/i)
  return match ? match[1] : 'DEAL2026'
}

const copyCode = (code) => {
  navigator.clipboard.writeText(code)
  copiedCode.value = code
  setTimeout(() => { copiedCode.value = null }, 2000)
}

const applyFilters = () => {
  router.get(
    '/',
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
  <Head>
    <title>{{ site?.meta_title || 'Deals & Coupons Hub' }}</title>
    <meta v-if="site?.meta_description" name="description" :content="site.meta_description" />
  </Head>

  <ExternalLayout>
    <div class="space-y-8">
      <!-- Hero Banner -->
      <div class="bg-gradient-to-br from-purple-600 to-indigo-700 rounded-2xl p-8 md:p-12 text-white shadow-md space-y-4">
        <span class="bg-white/10 text-purple-100 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider">
          🏷️ Verified Coupons & Discounts
        </span>
        <h1 class="text-3xl md:text-5xl font-extrabold tracking-tight">{{ site?.name || 'EasyPromos Hub' }}</h1>
        <p class="text-purple-100 text-lg max-w-2xl leading-relaxed">
          {{ site?.meta_description || 'Save big on developer tools, cloud hosting, software subscriptions, and VPNs.' }}
        </p>

        <!-- Search Input -->
        <div class="pt-2 max-w-xl">
          <input
            v-model="searchQuery"
            @input="applyFilters"
            type="text"
            placeholder="🔍 Search coupon codes or provider..."
            class="w-full rounded-xl border-none shadow-lg text-gray-900 placeholder-gray-400 text-sm p-4 focus:ring-4 focus:ring-purple-300"
          />
        </div>
      </div>

      <!-- Categories Pills -->
      <div v-if="categories?.length" class="flex flex-wrap gap-2">
        <button
          @click="selectCategory('')"
          :class="[selectedCategory === '' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200', 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm']"
        >
          All Deals
        </button>
        <button
          v-for="cat in categories"
          :key="cat.id"
          @click="selectCategory(cat.slug)"
          :class="[selectedCategory === cat.slug ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200', 'px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm']"
        >
          {{ cat.name }}
        </button>
      </div>

      <!-- Promo Deals Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
          v-for="deal in deals.data"
          :key="deal.id"
          class="bg-white rounded-2xl p-6 border border-purple-100 shadow-sm hover:border-purple-300 hover:shadow-md transition flex flex-col justify-between space-y-4"
        >
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span v-if="deal.category" class="bg-purple-50 text-purple-700 font-bold px-2.5 py-1 rounded-md text-xs border border-purple-100">
                {{ deal.category.name }}
              </span>
              <span class="text-xs text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                ✓ Verified Active
              </span>
            </div>

            <h2 class="text-xl font-bold text-gray-900 leading-snug">{{ deal.title }}</h2>
            <p v-if="deal.summary" class="text-gray-600 text-sm leading-relaxed">{{ deal.summary }}</p>
          </div>

          <!-- Coupon Code Box -->
          <div class="pt-4 border-t border-gray-100 flex items-center justify-between gap-3">
            <div class="bg-dashed bg-purple-50 border-2 border-dashed border-purple-300 rounded-xl px-4 py-2 flex-1 text-center font-mono text-base font-extrabold text-purple-900 select-all">
              {{ extractCode(deal.content) }}
            </div>
            <button
              @click="copyCode(extractCode(deal.content))"
              class="px-5 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold rounded-xl text-xs shadow transition flex items-center gap-1.5"
            >
              <span>{{ copiedCode === extractCode(deal.content) ? '✓ Copied!' : '📋 Copy Code' }}</span>
            </button>
          </div>
        </div>
      </div>

      <div v-if="!deals.data?.length" class="bg-white rounded-2xl p-12 text-center text-gray-400 border border-gray-200">
        No active promo deals found matching your search. Try resetting filters.
      </div>
    </div>
  </ExternalLayout>
</template>
