<script setup>
import { ref, computed } from 'vue'

const text = ref('')

const wordCount = computed(() => {
  const trimmed = text.value.trim()
  return trimmed ? trimmed.split(/\s+/).length : 0
})

const charCount = computed(() => text.value.length)
const charNoSpaceCount = computed(() => text.value.replace(/\s+/g, '').length)
const sentenceCount = computed(() => {
  const trimmed = text.value.trim()
  return trimmed ? trimmed.split(/[.!?]+/).filter(Boolean).length : 0
})
const paragraphCount = computed(() => {
  const trimmed = text.value.trim()
  return trimmed ? trimmed.split(/\n+/).filter(Boolean).length : 0
})
const readingTime = computed(() => Math.ceil(wordCount.value / 200))
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
      <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-center">
        <div class="text-2xl font-extrabold text-emerald-700">{{ wordCount }}</div>
        <div class="text-xs font-semibold text-emerald-900 mt-1 uppercase">Words</div>
      </div>
      <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 text-center">
        <div class="text-2xl font-extrabold text-blue-700">{{ charCount }}</div>
        <div class="text-xs font-semibold text-blue-900 mt-1 uppercase">Characters</div>
      </div>
      <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-center">
        <div class="text-2xl font-extrabold text-indigo-700">{{ charNoSpaceCount }}</div>
        <div class="text-xs font-semibold text-indigo-900 mt-1 uppercase">No Spaces</div>
      </div>
      <div class="bg-purple-50 border border-purple-100 rounded-xl p-4 text-center">
        <div class="text-2xl font-extrabold text-purple-700">{{ sentenceCount }}</div>
        <div class="text-xs font-semibold text-purple-900 mt-1 uppercase">Sentences</div>
      </div>
      <div class="bg-amber-50 border border-amber-100 rounded-xl p-4 text-center">
        <div class="text-2xl font-extrabold text-amber-700">{{ readingTime }}m</div>
        <div class="text-xs font-semibold text-amber-900 mt-1 uppercase">Reading Time</div>
      </div>
    </div>

    <div>
      <textarea
        v-model="text"
        placeholder="Type or paste your text here to count words and analyze composition..."
        rows="10"
        class="w-full rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-gray-800 text-base p-4"
      ></textarea>
    </div>
  </div>
</template>
