<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  slotName: {
    type: String,
    required: true,
  },
})

const page = usePage()

const adPlacement = computed(() => {
  const placements = page.props.currentSite?.ad_placements
  return placements ? placements[props.slotName] : null
})

const adContainer = ref(null)

const injectAdCode = () => {
  if (!adContainer.value || !adPlacement.value?.ad_code) return
  adContainer.value.innerHTML = ''

  const container = document.createElement('div')
  container.innerHTML = adPlacement.value.ad_code

  const scripts = container.querySelectorAll('script')
  scripts.forEach((s) => {
    const newScript = document.createElement('script')
    Array.from(s.attributes).forEach((attr) => newScript.setAttribute(attr.name, attr.value))
    newScript.innerHTML = s.innerHTML
    s.parentNode.replaceChild(newScript, s)
  })

  adContainer.value.appendChild(container)
}

onMounted(injectAdCode)
watch(adPlacement, injectAdCode)
</script>

<template>
  <div v-if="adPlacement?.ad_code" ref="adContainer" class="ad-slot-active my-4 text-center overflow-hidden"></div>
  <div v-else class="border border-dashed border-gray-300 rounded-xl p-4 text-center text-xs text-gray-400 bg-gray-50/50 my-4">
    📢 Structural Ad Container Placeholder ({{ slotName }})
  </div>
</template>
