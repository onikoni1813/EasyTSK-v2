<script setup>
import { ref, watch, onMounted } from 'vue'

const text = ref('https://easytsk.com')
const qrCanvas = ref(null)

const drawQr = () => {
  if (!qrCanvas.value) return
  const ctx = qrCanvas.value.getContext('2d')
  ctx.clearRect(0, 0, 200, 200)

  ctx.fillStyle = '#FFFFFF'
  ctx.fillRect(0, 0, 200, 200)

  ctx.fillStyle = '#000000'
  ctx.fillRect(10, 10, 50, 50)
  ctx.fillRect(140, 10, 50, 50)
  ctx.fillRect(10, 140, 50, 50)
  ctx.fillStyle = '#FFFFFF'
  ctx.fillRect(20, 20, 30, 30)
  ctx.fillRect(150, 20, 30, 30)
  ctx.fillRect(20, 150, 30, 30)
  ctx.fillStyle = '#000000'
  ctx.fillRect(30, 30, 10, 10)
  ctx.fillRect(160, 30, 10, 10)
  ctx.fillRect(30, 160, 10, 10)

  const val = text.value || ' '
  for (let i = 0; i < val.length; i++) {
    const code = val.charCodeAt(i)
    const x = (code * 17) % 180 + 10
    const y = (code * 23) % 180 + 10
    ctx.fillRect(x, y, 8, 8)
  }
}

watch(text, drawQr)
onMounted(drawQr)
</script>

<template>
  <div class="space-y-6 max-w-md mx-auto text-center">
    <div>
      <label class="block text-sm font-semibold text-gray-700 mb-2">Target URL or Text</label>
      <input
        v-model="text"
        type="text"
        placeholder="Enter URL or text..."
        class="w-full rounded-xl border-gray-300 shadow-sm p-3 text-center text-base"
      />
    </div>

    <div class="p-6 bg-white border border-gray-200 rounded-2xl shadow-sm inline-block mx-auto">
      <canvas ref="qrCanvas" width="200" height="200" class="mx-auto rounded-lg"></canvas>
    </div>
  </div>
</template>
