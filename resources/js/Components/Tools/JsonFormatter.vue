<script setup>
import { ref } from 'vue'

const inputJson = ref('')
const outputJson = ref('')
const errorMessage = ref('')
const copied = ref(false)

const formatJson = () => {
  errorMessage.value = ''
  try {
    const parsed = JSON.parse(inputJson.value)
    outputJson.value = JSON.stringify(parsed, null, 2)
  } catch (err) {
    errorMessage.value = 'Invalid JSON: ' + err.message
  }
}

const minifyJson = () => {
  errorMessage.value = ''
  try {
    const parsed = JSON.parse(inputJson.value)
    outputJson.value = JSON.stringify(parsed)
  } catch (err) {
    errorMessage.value = 'Invalid JSON: ' + err.message
  }
}

const copyOutput = () => {
  if (outputJson.value) {
    navigator.clipboard.writeText(outputJson.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Input JSON</label>
        <textarea
          v-model="inputJson"
          placeholder="Paste raw JSON here..."
          rows="12"
          class="w-full font-mono text-sm rounded-xl border-gray-300 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 p-4"
        ></textarea>
      </div>

      <div>
        <div class="flex justify-between items-center mb-2">
          <label class="block text-sm font-medium text-gray-700">Formatted Output</label>
          <button v-if="outputJson" @click="copyOutput" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">
            {{ copied ? 'Copied!' : '📋 Copy Output' }}
          </button>
        </div>
        <textarea
          v-model="outputJson"
          readonly
          placeholder="Formatted JSON result will appear here..."
          rows="12"
          class="w-full font-mono text-sm bg-gray-50 rounded-xl border-gray-300 shadow-sm p-4 text-gray-800"
        ></textarea>
      </div>
    </div>

    <div v-if="errorMessage" class="p-4 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl text-sm font-medium">
      {{ errorMessage }}
    </div>

    <div class="flex gap-4">
      <button @click="formatJson" class="px-6 py-2.5 bg-emerald-600 text-white font-medium rounded-xl hover:bg-emerald-700 shadow-sm">
        Beautify JSON
      </button>
      <button @click="minifyJson" class="px-6 py-2.5 bg-gray-800 text-white font-medium rounded-xl hover:bg-gray-900 shadow-sm">
        Minify JSON
      </button>
    </div>
  </div>
</template>
