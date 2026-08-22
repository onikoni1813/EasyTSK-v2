<script setup>
import { computed } from 'vue'
import { usePage, Link } from '@inertiajs/vue3'
import AdSlot from '@/Components/External/AdSlot.vue'

const page = usePage()
const currentSite = computed(() => page.props.currentSite || {})
</script>

<template>
  <div class="min-h-screen bg-gray-50 text-gray-900 font-sans flex flex-col justify-between antialiased">
    <!-- Header / Nav -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-40 shadow-sm">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Site Logo / Branding -->
        <div class="flex items-center gap-3">
          <Link href="/" class="flex items-center gap-2 font-black text-xl text-gray-900 tracking-tight">
            <span class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow">
              {{ currentSite?.name ? currentSite.name.charAt(0) : 'S' }}
            </span>
            <span>{{ currentSite?.name || 'External Property' }}</span>
          </Link>
        </div>

        <!-- Header Nav Links -->
        <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-gray-600">
          <template v-if="currentSite?.header_nav?.length">
            <Link
              v-for="(item, idx) in currentSite.header_nav"
              :key="idx"
              :href="item.url"
              class="hover:text-emerald-600 transition"
            >
              {{ item.label }}
            </Link>
          </template>
          <template v-else>
            <Link href="/" class="hover:text-emerald-600 transition">Home</Link>
            <Link href="/about" class="hover:text-emerald-600 transition">About</Link>
          </template>
        </nav>
      </div>
    </header>

    <!-- Structural Ad Slot: Header Top Banner -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full pt-4">
      <AdSlot slotName="header_top" />
    </div>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full py-6 flex-1 grid grid-cols-1 lg:grid-cols-4 gap-8">
      <!-- Primary Main Content Column -->
      <div class="lg:col-span-3 space-y-6">
        <!-- Structural Ad Slot: Content Top -->
        <AdSlot slotName="content_top" />

        <slot />

        <!-- Structural Ad Slot: Content Bottom -->
        <AdSlot slotName="content_bottom" />
      </div>

      <!-- Sidebar Column -->
      <aside class="space-y-6">
        <!-- Structural Ad Slot: Sidebar -->
        <AdSlot slotName="sidebar" />

        <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm space-y-3">
          <h3 class="font-bold text-gray-900 text-sm">About {{ currentSite?.name }}</h3>
          <p class="text-xs text-gray-500 leading-relaxed">
            Independent online web property powered by EasyTSK Ecosystem. Fast, secure, and privacy-first.
          </p>
        </div>
      </aside>
    </main>

    <!-- Structural Ad Slot: Footer Top Banner -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full">
      <AdSlot slotName="footer_bottom" />
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-gray-200 py-8 text-xs text-gray-500 mt-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
        <p>© {{ new Date().getFullYear() }} {{ currentSite?.name || 'External Property' }}. All rights reserved.</p>

        <div class="flex items-center gap-6">
          <template v-if="currentSite?.footer_links?.length">
            <Link
              v-for="(link, idx) in currentSite.footer_links"
              :key="idx"
              :href="link.url"
              class="hover:text-gray-900 transition"
            >
              {{ link.label }}
            </Link>
          </template>
          <template v-else>
            <Link href="/privacy" class="hover:text-gray-900 transition">Privacy Policy</Link>
            <Link href="/terms" class="hover:text-gray-900 transition">Terms of Service</Link>
            <Link href="/contact" class="hover:text-gray-900 transition">Contact Us</Link>
          </template>
        </div>
      </div>
    </footer>
  </div>
</template>
