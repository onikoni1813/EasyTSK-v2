<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  accounts: Array,
})

const accForm = useForm({
  id: null,
  network: 'adsterra',
  account_name: '',
  account_id_or_email: '',
  payout_method: 'usdt',
  min_payout_amount: 50.00,
  status: 'active',
})

const showModal = ref(false)

const openModal = (acc = null) => {
  if (acc) {
    accForm.id = acc.id
    accForm.network = acc.network
    accForm.account_name = acc.account_name
    accForm.account_id_or_email = acc.account_id_or_email
    accForm.payout_method = acc.payout_method
    accForm.min_payout_amount = acc.min_payout_amount
    accForm.status = acc.status
  } else {
    accForm.reset()
    accForm.id = null
  }
  showModal.value = true
}

const submitAccount = () => {
  if (accForm.id) {
    accForm.put(route('admin.revenue.publishers.update', accForm.id), {
      onSuccess: () => { showModal.value = false; accForm.reset(); }
    })
  } else {
    accForm.post(route('admin.revenue.publishers.store'), {
      onSuccess: () => { showModal.value = false; accForm.reset(); }
    })
  }
}

const deleteAccount = (acc) => {
  if (confirm(`Delete publisher account "${acc.account_name}"?`)) {
    useForm({}).delete(route('admin.revenue.publishers.destroy', acc.id))
  }
}
</script>

<template>
  <Head title="Publisher Accounts" />

  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
            <Link :href="route('admin.revenue.index')" class="hover:text-gray-700">Revenue Engine</Link>
            <span>/</span>
            <span>Publisher Accounts</span>
          </div>
          <h1 class="text-2xl font-bold text-gray-900">Ad Publisher Accounts</h1>
          <p class="text-sm text-gray-500">Manage registered Adsterra, Monetag, AdMaven, HilltopAds, and MyBid publisher accounts.</p>
        </div>

        <button @click="openModal()" class="px-4 py-2 bg-emerald-600 text-white font-medium rounded-lg text-sm hover:bg-emerald-700">
          + Register Publisher Account
        </button>
      </div>

      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
        <table class="w-full text-left text-sm text-gray-600">
          <thead class="bg-gray-50 text-gray-700 text-xs font-semibold uppercase border-b border-gray-200">
            <tr>
              <th class="px-6 py-3">Network</th>
              <th class="px-6 py-3">Account Name</th>
              <th class="px-6 py-3">ID / Email</th>
              <th class="px-6 py-3">Payout Method</th>
              <th class="px-6 py-3">Min Payout</th>
              <th class="px-6 py-3">Status</th>
              <th class="px-6 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="acc in accounts" :key="acc.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 uppercase font-bold text-xs text-purple-700">{{ acc.network }}</td>
              <td class="px-6 py-4 font-semibold text-gray-900">{{ acc.account_name }}</td>
              <td class="px-6 py-4 text-xs font-mono text-gray-500">{{ acc.account_id_or_email }}</td>
              <td class="px-6 py-4 text-xs font-medium uppercase text-blue-700">{{ acc.payout_method }}</td>
              <td class="px-6 py-4 font-mono text-xs">${{ parseFloat(acc.min_payout_amount).toFixed(2) }}</td>
              <td class="px-6 py-4">
                <span class="px-2.5 py-1 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 uppercase">
                  {{ acc.status }}
                </span>
              </td>
              <td class="px-6 py-4 text-right space-x-2">
                <button @click="openModal(acc)" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</button>
                <button @click="deleteAccount(acc)" class="text-rose-600 hover:text-rose-900 font-medium">Delete</button>
              </td>
            </tr>
            <tr v-if="!accounts.length">
              <td colspan="7" class="px-6 py-8 text-center text-gray-400">No publisher accounts registered yet.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AdminLayout>
</template>
