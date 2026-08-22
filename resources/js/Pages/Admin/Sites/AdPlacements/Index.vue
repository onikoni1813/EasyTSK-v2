<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  site: Object,
  adPlacements: Array,
  slots: Object,
  networks: Object,
})

const adForm = useForm({
  placement_slot: 'header_top',
  network: 'adsterra',
  ad_code: '',
  device_target: 'all',
  is_active: true,
})

const submitAdPlacement = () => {
  adForm.post(route('admin.sites.ads.store', props.site.id), {
    onSuccess: () => { adForm.reset('ad_code'); }
  })
}

const toggleAd = (adPlacement) => {
  useForm({}).post(route('admin.sites.ads.toggle', [props.site.id, adPlacement.id]))
}

const deleteAd = (adPlacement) => {
  if (confirm('Delete this ad placement snippet?')) {
    useForm({}).delete(route('admin.sites.ads.destroy', [props.site.id, adPlacement.id]))
  }
}
</script>

<template>
  <Head :title="`Ad Placements — ${site.name}`" />

  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <Link :href="route('admin.sites.index')" class="hover:text-gray-700">Site Registry</Link>
            <span>/</span>
            <span>{{ site.name }}</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">Ad Placement Engine</h1>
          <p class="text-sm text-gray-500">Configure Adsterra, Monetag, AdMaven, HilltopAds, or MyBid ad scripts per placement slot.</p>
        </div>
      </div>

      <!-- Add / Edit Ad Placement Form -->
      <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm space-y-4">
        <h2 class="text-lg font-bold text-gray-900">Configure Ad Unit Script</h2>

        <form @submit.prevent="submitAdPlacement" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Placement Slot</label>
              <select v-model="adForm.placement_slot" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500">
                <option v-for="(label, key) in slots" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Ad Network</label>
              <select v-model="adForm.network" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500">
                <option v-for="(label, key) in networks" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Device Target</label>
              <select v-model="adForm.device_target" class="w-full rounded-lg border-gray-300 text-sm focus:ring-emerald-500">
                <option value="all">All Devices (Desktop & Mobile)</option>
                <option value="desktop_only">Desktop Only</option>
                <option value="mobile_only">Mobile Only</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Ad Code Snippet (HTML / JS Script)</label>
            <textarea
              v-model="adForm.ad_code"
              rows="5"
              placeholder="<script type='text/javascript'>...</script>"
              class="w-full font-mono text-xs rounded-lg border-gray-300 focus:ring-emerald-500 p-3"
            ></textarea>
          </div>

          <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg text-sm transition">
            Save Ad Placement Snippet
          </button>
        </form>
      </div>

      <!-- Existing Ad Placements Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Slot</th>
              <th class="px-6 py-3">Network</th>
              <th class="px-6 py-3">Target</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="ad in adPlacements" :key="ad.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-semibold text-gray-900 uppercase text-xs">{{ ad.placement_slot.replace('_', ' ') }}</td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded text-xs font-bold bg-purple-50 text-purple-700 border border-purple-100 uppercase">
                  {{ ad.network }}
                </span>
              </td>
              <td class="px-6 py-4 text-xs text-gray-500 capitalize">{{ ad.device_target.replace('_', ' ') }}</td>
              <td class="px-6 py-4">
                <button
                  @click="toggleAd(ad)"
                  :class="[ad.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-600', 'px-2.5 py-1 rounded text-xs font-semibold']"
                >
                  {{ ad.is_active ? 'Active' : 'Disabled' }}
                </button>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="deleteAd(ad)" class="text-rose-600 hover:text-rose-900 font-medium">Delete</button>
              </td>
            </tr>
            <tr v-if="!adPlacements.length">
              <td colspan="5" class="px-6 py-8 text-center text-gray-400">No ad placements configured for this site yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
