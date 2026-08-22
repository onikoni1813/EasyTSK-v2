<script setup>
import { ref } from 'vue'

const text = ref('')

const toUpper = () => text.value = text.value.toUpperCase()
const toLower = () => text.value = text.value.toLowerCase()
const toTitle = () => {
  text.value = text.value.toLowerCase().replace(/(?:^|\s)\w/g, match => match.toUpperCase())
}
const toSnake = () => {
  text.value = text.value.trim().toLowerCase().replace(/\s+/g, '_')
}
const toKebab = () => {
  text.value = text.value.trim().toLowerCase().replace(/\s+/g, '-')
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap gap-3">
      <button @click="toUpper" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg">UPPERCASE</button>
      <button @click="toLower" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg">lowercase</button>
      <button @click="toTitle" class="px-4 py-2 bg-emerald-600 text-white text-xs font-bold rounded-lg">Title Case</button>
      <button @click="toSnake" class="px-4 py-2 bg-gray-800 text-white text-xs font-bold rounded-lg">snake_case</button>
      <button @click="toKebab" class="px-4 py-2 bg-gray-800 text-white text-xs font-bold rounded-lg">kebab-case</button>
    </div>

    <textarea
      v-model="text"
      placeholder="Type text to convert case..."
      rows="8"
      class="w-full rounded-xl border-gray-300 shadow-sm p-4 text-base"
    ></textarea>
  </div>
</template>
