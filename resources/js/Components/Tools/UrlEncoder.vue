<script setup>
import { ref } from 'vue'

const input = ref('')
const output = ref('')
const mode = ref('encode')

const process = () => {
  try {
    if (mode.value === 'encode') {
      output.value = encodeURIComponent(input.value)
    } else {
      output.value = decodeURIComponent(input.value)
    }
  } catch (e) {
    output.value = 'URL decoding error: ' + e.message
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex gap-4 border-b border-gray-200 pb-3">
      <button @click="mode = 'encode'; process()" :class="[mode === 'encode' ? 'text-emerald-600 border-emerald-600 font-bold' : 'text-gray-500', 'pb-2 border-b-2']">
        URL Encode
      </button>
      <button @click="mode = 'decode'; process()" :class="[mode === 'decode' ? 'text-emerald-600 border-emerald-600 font-bold' : 'text-gray-500', 'pb-2 border-b-2']">
        URL Decode
      </button>
    </div>

    <textarea
      v-model="input"
      @input="process"
      placeholder="Type URL string..."
      rows="6"
      class="w-full font-mono text-sm rounded-xl border-gray-300 shadow-sm p-4"
    ></textarea>

    <textarea
      v-model="output"
      readonly
      placeholder="Output result..."
      rows="6"
      class="w-full font-mono text-sm bg-gray-50 rounded-xl border-gray-300 shadow-sm p-4 text-gray-800"
    ></textarea>
  </div>
</template>
