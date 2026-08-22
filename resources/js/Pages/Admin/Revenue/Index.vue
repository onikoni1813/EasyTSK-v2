<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  totalRevenue: String,
  totalImpressions: Number,
  totalClicks: Number,
  avgCpm: String,
  revenueLogs: Array,
  sites: Array,
  publisherAccounts: Array,
})

const logForm = useForm({
  site_id: props.sites?.[0]?.id || '',
  publisher_account_id: props.publisherAccounts?.[0]?.id || '',
  network: 'adsterra',
  log_date: new Date().toISOString().split('T')[0],
  impressions: 1000,
  clicks: 15,
  revenue_usd: 2.50,
  payment_status: 'unpaid',
})

const showLogModal = ref(false)

const submitLog = () => {
  logForm.post(route('admin.revenue.logs.store'), {
    onSuccess: () => { showLogModal.value = false; }
  })
}
</script>

<template>
  <Head title="Revenue & Cashflow Analytics" />

  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Ecosystem Revenue & Cashflow</h1>
          <p class="text-sm text-gray-500">Track ad network earnings, impressions, eCPM performance, and publisher accounts.</p>
        </div>

        <div class="flex gap-3">
          <Link :href="route('admin.revenue.publishers.index')" class="px-4 py-2 bg-gray-800 text-white font-medium rounded-lg text-sm hover:bg-gray-900">
            Publisher Accounts
          </Link>
          <button @click="showLogModal = true" class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg text-sm hover:bg-emerald-700">
            + Log Daily Revenue
          </button>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
          <div class="text-xs font-semibold uppercase text-gray-400">Total Ecosystem Revenue</div>
          <div class="text-3xl font-black text-emerald-600 mt-2">${{ totalRevenue }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
          <div class="text-xs font-semibold uppercase text-gray-400">Average eCPM</div>
          <div class="text-3xl font-black text-purple-600 mt-2">${{ avgCpm }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
          <div class="text-xs font-semibold uppercase text-gray-400">Total Impressions</div>
          <div class="text-3xl font-black text-blue-600 mt-2">{{ totalImpressions.toLocaleString() }}</div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
          <div class="text-xs font-semibold uppercase text-gray-400">Total Clicks</div>
          <div class="text-3xl font-black text-indigo-600 mt-2">{{ totalClicks.toLocaleString() }}</div>
        </div>
      </div>

      <!-- Log Daily Earnings Modal -->
      <div v-if="showLogModal" class="bg-white rounded-xl p-6 border border-gray-200 shadow-lg space-y-4">
        <h2 class="text-lg font-bold text-gray-900">Log Daily Ad Earnings</h2>
        <form @submit.prevent="submitLog" class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold uppercase text-gray-700 mb-1">Target Property Site</label>
            <select v-model="logForm.site_id" class="w-full rounded-lg border-gray-300 text-sm">
              <option v-for="s in sites" :key="s.id" :value="s.id">{{ s.name }} ({{ s.subdomain }})</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase text-gray-700 mb-1">Ad Network</label>
            <select v-model="logForm.network" class="w-full rounded-lg border-gray-300 text-sm">
              <option value="adsterra">Adsterra</option>
              <option value="monetag">Monetag</option>
              <option value="admaven">AdMaven</option>
              <option value="hilltopads">HilltopAds</option>
              <option value="mybid">MyBid</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase text-gray-700 mb-1">Log Date</label>
            <input type="date" v-model="logForm.log_date" class="w-full rounded-lg border-gray-300 text-sm" />
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase text-gray-700 mb-1">Impressions</label>
            <input type="number" v-model="logForm.impressions" class="w-full rounded-lg border-gray-300 text-sm" />
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase text-gray-700 mb-1">Clicks</label>
            <input type="number" v-model="logForm.clicks" class="w-full rounded-lg border-gray-300 text-sm" />
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase text-gray-700 mb-1">Revenue ($ USD)</label>
            <input type="number" step="0.0001" v-model="logForm.revenue_usd" class="w-full rounded-lg border-gray-300 text-sm" />
          </div>

          <div class="md:col-span-3 flex justify-end gap-3 pt-2">
            <button type="button" @click="showLogModal = false" class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-emerald-600 text-white text-sm font-bold rounded-lg hover:bg-emerald-700">Submit Log</button>
          </div>
        </form>
      </div>

      <!-- Recent Revenue Logs Table -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Date</th>
              <th class="px-6 py-3">Property Site</th>
              <th class="px-6 py-3">Network</th>
              <th class="px-6 py-3">Impressions</th>
              <th class="px-6 py-3">eCPM</th>
              <th class="px-6 py-3">Revenue ($)</th>
              <th class="px-6 py-3">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="log in revenueLogs" :key="log.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 font-mono text-xs text-gray-900">{{ new Date(log.log_date).toLocaleDateString() }}</td>
              <td class="px-6 py-4 font-semibold text-gray-900">{{ log.site?.name }}</td>
              <td class="px-6 py-4 uppercase text-xs font-bold text-purple-700">{{ log.network }}</td>
              <td class="px-6 py-4 text-xs font-mono">{{ log.impressions.toLocaleString() }}</td>
              <td class="px-6 py-4 text-xs font-mono text-emerald-700">${{ parseFloat(log.cpm_rate).toFixed(2) }}</td>
              <td class="px-6 py-4 font-bold text-gray-900">${{ parseFloat(log.revenue_usd).toFixed(2) }}</td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded text-xs font-semibold bg-amber-50 text-amber-800 uppercase border border-amber-100">
                  {{ log.payment_status }}
                </span>
              </td>
            </tr>
            <tr v-if="!revenueLogs.length">
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">No revenue logs recorded yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
