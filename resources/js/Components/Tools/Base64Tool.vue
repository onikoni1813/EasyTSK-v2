<script setup>
import { ref } from 'vue'

const inputText = ref('')
const outputText = ref('')
const mode = ref('encode')
const errorMsg = ref('')

const processText = () => {
  errorMsg.value = ''
  try {
    if (mode.value === 'encode') {
      outputText.value = btoa(inputText.value)
    } else {
      outputText.value = atob(inputText.value)
    }
  } catch (err) {
    errorMsg.value = 'Base64 conversion error: ' + err.message
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex gap-4 border-b border-gray-200 pb-3">
      <button @click="mode = 'encode'; processText()" :class="[mode === 'encode' ? 'text-emerald-600 border-emerald-600 font-bold' : 'text-gray-500', 'pb-2 border-b-2']">
        Encode to Base64
      </button>
      <button @click="mode = 'decode'; processText()" :class="[mode === 'decode' ? 'text-emerald-600 border-emerald-600 font-bold' : 'text-gray-500', 'pb-2 border-b-2']">
        Decode from Base64
      </button>
    </div>

    <div class="space-y-4">
      <textarea
        v-model="inputText"
        @input="processText"
        :placeholder="mode === 'encode' ? 'Type text to encode...' : 'Paste Base64 string to decode...'"
        rows="6"
        class="w-full font-mono text-sm rounded-xl border-gray-300 shadow-sm p-4"
      ></textarea>

      <div v-if="errorMsg" class="p-3 bg-rose-50 text-rose-700 text-xs font-semibold rounded-lg">
        {{ errorMsg }}
      </div>

      <textarea
        v-model="outputText"
        readonly
        placeholder="Result..."
        rows="6"
        class="w-full font-mono text-sm bg-gray-50 rounded-xl border-gray-300 shadow-sm p-4 text-gray-800"
      ></textarea>
    </div>
  </div>
</template>
