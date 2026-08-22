<script setup>
import { ref } from 'vue'

const length = ref(16)
const includeUppercase = ref(true)
const includeLowercase = ref(true)
const includeNumbers = ref(true)
const includeSymbols = ref(true)
const password = ref('')
const copied = ref(false)

const generatePassword = () => {
  let chars = ''
  if (includeUppercase.value) chars += 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
  if (includeLowercase.value) chars += 'abcdefghijklmnopqrstuvwxyz'
  if (includeNumbers.value) chars += '0123456789'
  if (includeSymbols.value) chars += '!@#$%^&*()_+-=[]{}|;:,.<>?'

  if (!chars) {
    password.value = 'Select at least one character set'
    return
  }

  let res = ''
  for (let i = 0; i < length.value; i++) {
    res += chars.charAt(Math.floor(Math.random() * chars.length))
  }
  password.value = res
}

const copy = () => {
  if (password.value) {
    navigator.clipboard.writeText(password.value)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }
}

generatePassword()
</script>

<template>
  <div class="space-y-6 max-w-xl mx-auto">
    <div class="bg-gray-900 text-white rounded-xl p-6 flex justify-between items-center shadow-inner">
      <span class="font-mono text-xl tracking-wider select-all break-all">{{ password }}</span>
      <button @click="copy" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition">
        {{ copied ? 'Copied!' : 'Copy' }}
      </button>
    </div>

    <div class="space-y-4 bg-gray-50 p-6 rounded-xl border border-gray-200">
      <div>
        <label class="block text-sm font-semibold text-gray-700 mb-1">Password Length: {{ length }}</label>
        <input type="range" v-model="length" min="8" max="64" @input="generatePassword" class="w-full accent-emerald-600" />
      </div>

      <div class="grid grid-cols-2 gap-4 text-sm font-medium text-gray-700">
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="includeUppercase" @change="generatePassword" class="rounded text-emerald-600 focus:ring-emerald-500" />
          Uppercase (A-Z)
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="includeLowercase" @change="generatePassword" class="rounded text-emerald-600 focus:ring-emerald-500" />
          Lowercase (a-z)
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="includeNumbers" @change="generatePassword" class="rounded text-emerald-600 focus:ring-emerald-500" />
          Numbers (0-9)
        </label>
        <label class="flex items-center gap-2">
          <input type="checkbox" v-model="includeSymbols" @change="generatePassword" class="rounded text-emerald-600 focus:ring-emerald-500" />
          Symbols (!@#$)
        </label>
      </div>

      <button @click="generatePassword" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow transition">
        🔄 Regenerate Password
      </button>
    </div>
  </div>
</template>
