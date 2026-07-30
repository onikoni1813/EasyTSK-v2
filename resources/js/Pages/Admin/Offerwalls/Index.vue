<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps({ offerwalls: Array });
const showModal = ref(false);
const showPostbackModal = ref(false);
const selectedOfferwall = ref(null);

const form = ref({
  name: '', iframe_url_pattern: '', reward_ratio: 1.00, secret_key: '', image_url: '', description: '', status: true,
  param_user_id: 'user_id', param_amount: 'amount', param_transaction_id: 'transaction_id', param_status: 'status', param_secret_key: 'secure', status_chargeback_value: 'reversed', allowed_ips: ''
});
const isEditing = ref(false);
const currentId = ref(null);

function openModal(offerwall = null) {
  if (offerwall) {
    isEditing.value = true;
    currentId.value = offerwall.id;
    form.value = { ...offerwall };
  } else {
    isEditing.value = false;
    currentId.value = null;
    form.value = { 
      name: '', iframe_url_pattern: '', reward_ratio: 1.00, secret_key: '', image_url: '', description: '', status: true,
      param_user_id: 'user_id', param_amount: 'amount', param_transaction_id: 'transaction_id', param_status: 'status', param_secret_key: 'secure', status_chargeback_value: 'reversed', allowed_ips: ''
    };
  }
  showModal.value = true;
}

function save() {
  if (isEditing.value) {
    router.put('/admin/offerwalls/' + currentId.value, form.value, { onSuccess: () => showModal.value = false });
  } else {
    router.post('/admin/offerwalls', form.value, { onSuccess: () => showModal.value = false });
  }
}

function deleteOfferwall(id) {
  if (confirm('Are you sure you want to delete this offerwall?')) {
    router.delete('/admin/offerwalls/' + id);
  }
}

function openPostbackGuide(offerwall) {
  selectedOfferwall.value = offerwall;
  showPostbackModal.value = true;
}

const appUrl = computed(() => window.location.origin);

const postbackUrl = computed(() => {
  if (!selectedOfferwall.value) return '';
  const provider = selectedOfferwall.value.name.toLowerCase().replace(/\s+/g, '');
  
  const pUser = selectedOfferwall.value.param_user_id || 'user_id';
  const pAmount = selectedOfferwall.value.param_amount || 'amount';
  const pTx = selectedOfferwall.value.param_transaction_id || 'transaction_id';
  
  let url = `${appUrl.value}/postback/${provider}?${pUser}={user_id}&${pAmount}={reward}&${pTx}={tx_id}`;
  if (selectedOfferwall.value.secret_key) {
    const pSecure = selectedOfferwall.value.param_secret_key || 'secure';
    url += `&${pSecure}=${selectedOfferwall.value.secret_key}`;
  }
  return url;
});

function copyToClipboard(text) {
  navigator.clipboard.writeText(text);
  alert('Copied to clipboard!');
}
</script>

<template>
  <AdminLayout title="Manage Offerwalls">
    <div class="flex justify-between items-center mb-8">
      <div>
        <h2 class="text-2xl font-bold text-white tracking-tight">Offerwalls</h2>
        <p class="text-sm text-slate-400 mt-1">Manage external offerwall integrations and postbacks.</p>
      </div>
      <button @click="openModal()" class="bg-emerald-500 hover:bg-emerald-600 px-5 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-emerald-500/20 transition-all hover:-translate-y-0.5">
        <i class="fas fa-plus mr-2"></i> Add Offerwall
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
      <div v-for="ow in offerwalls" :key="ow.id" class="bg-[#1e293b]/50 backdrop-blur-xl border border-white/5 hover:border-emerald-500/30 transition-colors p-6 rounded-3xl shadow-xl relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-50 group-hover:opacity-100 transition-opacity">
          <div :class="ow.status ? 'bg-emerald-500/20 text-emerald-400' : 'bg-rose-500/20 text-rose-400'" class="px-3 py-1 rounded-full text-xs font-bold tracking-wide uppercase">
            {{ ow.status ? 'Active' : 'Inactive' }}
          </div>
        </div>
        
        <div class="flex items-center gap-4 mb-6">
          <div class="w-16 h-16 rounded-2xl bg-black/40 p-2 flex items-center justify-center border border-white/10">
            <img v-if="ow.image_url && !ow.image_error" :src="ow.image_url" class="max-w-full max-h-full rounded-xl object-contain" :alt="ow.name" @error="ow.image_error = true">
            <span v-else class="text-2xl font-bold text-slate-400">{{ ow.name.charAt(0) }}</span>
          </div>
          <div>
            <h3 class="text-xl font-bold text-white">{{ ow.name }}</h3>
            <p class="text-sm text-slate-400">Ratio: <span class="text-emerald-400 font-semibold">{{ ow.reward_ratio }}x</span></p>
            <p v-if="ow.description" class="text-xs text-slate-500 mt-1.5 leading-relaxed line-clamp-2" :title="ow.description">{{ ow.description }}</p>
          </div>
        </div>

        <div class="space-y-3 mt-4">
          <button @click="openPostbackGuide(ow)" class="w-full bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 py-2.5 rounded-xl text-sm font-semibold transition-colors flex items-center justify-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            Postback Setup
          </button>
          
          <div class="flex gap-3">
            <button @click="openModal(ow)" class="flex-1 bg-white/5 text-white hover:bg-white/10 py-2.5 rounded-xl text-sm font-semibold transition-colors">Edit</button>
            <button @click="deleteOfferwall(ow.id)" class="flex-1 bg-rose-500/10 text-rose-400 hover:bg-rose-500/20 py-2.5 rounded-xl text-sm font-semibold transition-colors">Delete</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit/Add Modal -->
    <div v-if="showModal" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
      <div class="bg-[#0f172a] border border-white/10 rounded-3xl p-8 w-full max-w-2xl shadow-2xl transform transition-all max-h-[90vh] overflow-y-auto" @click.stop>
        <h3 class="text-2xl font-bold text-white mb-6">{{ isEditing ? 'Edit Offerwall' : 'Add New Offerwall' }}</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Provider Name</label>
            <input v-model="form.name" placeholder="e.g. Timewall" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
          </div>
          
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Reward Ratio</label>
            <input v-model="form.reward_ratio" type="number" step="any" placeholder="1.00" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Iframe URL Pattern</label>
            <input v-model="form.iframe_url_pattern" placeholder="e.g. https://timewall.io/offerwall?user={user_id}" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
            <p class="text-xs text-slate-500 mt-1">Use <code class="text-emerald-400 bg-emerald-400/10 px-1 rounded">{user_id}</code> as a macro for the authenticated user's ID.</p>
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Secret Key (For Postback Security)</label>
            <input v-model="form.secret_key" placeholder="Optional but recommended" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Logo URL <span class="text-slate-500 normal-case font-normal">(Optional)</span></label>
            <div class="flex gap-4 items-center">
              <input v-model="form.image_url" placeholder="https://..." class="flex-1 w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all">
              <div v-if="form.image_url" class="w-12 h-12 bg-white/5 rounded-xl border border-white/10 flex items-center justify-center p-1 shrink-0 shadow-inner">
                <img :src="form.image_url" class="max-w-full max-h-full object-contain rounded-lg" alt="Preview">
              </div>
            </div>
          </div>

          <div class="space-y-1 md:col-span-2">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Description</label>
            <textarea v-model="form.description" rows="3" placeholder="Brief description of the offerwall..." class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 focus:ring-1 focus:ring-emerald-500/50 transition-all"></textarea>
          </div>
          
          <div class="md:col-span-2 pt-4 pb-2 border-b border-white/10">
            <h4 class="text-sm font-bold text-white uppercase tracking-wider">Advanced Postback Parameters</h4>
            <p class="text-xs text-slate-400">Map custom parameter names for this provider's postback requests.</p>
          </div>
          
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">User ID Parameter</label>
            <input v-model="form.param_user_id" placeholder="e.g. subId, user_id" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Amount Parameter</label>
            <input v-model="form.param_amount" placeholder="e.g. amount, reward, payout" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Transaction ID Parameter</label>
            <input v-model="form.param_transaction_id" placeholder="e.g. tx_id, transId" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Secret Key Parameter</label>
            <input v-model="form.param_secret_key" placeholder="e.g. secure, hash" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Status Parameter</label>
            <input v-model="form.param_status" placeholder="e.g. status, type" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Chargeback Value</label>
            <input v-model="form.status_chargeback_value" placeholder="e.g. reversed, 2, chargeback" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Secret Key <span class="text-slate-500 normal-case font-normal">(Optional)</span></label>
            <input v-model="form.secret_key" placeholder="Enter provider's secret key" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
            <p class="text-[11px] text-slate-500 mt-1 leading-relaxed">Leave blank if the provider uses complex MD5/SHA1 Signature Hashes. Instead, use <b class="text-slate-400">Allowed IPs</b> below for security.</p>
          </div>
          <div class="space-y-1">
            <label class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Allowed IPs <span class="text-slate-500 normal-case font-normal">(Comma separated, Optional)</span></label>
            <input v-model="form.allowed_ips" placeholder="e.g. 192.168.1.1, 10.0.0.5" class="w-full bg-black/30 border border-white/10 rounded-xl px-4 py-3 text-white outline-none focus:border-emerald-500/50 transition-all">
            <p class="text-xs text-slate-500 mt-1">If set, ONLY postbacks from these IPs will be accepted. Excellent for security if provider sends signature hashes.</p>
          </div>

          <div class="md:col-span-2 flex items-center mt-4">
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.status" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
              <span class="ml-3 text-sm font-medium text-white">Offerwall is Active</span>
            </label>
          </div>
        </div>

        <div class="flex gap-4 mt-8">
          <button @click="showModal = false" class="flex-1 bg-white/5 hover:bg-white/10 py-3 rounded-xl text-sm font-bold text-white transition-colors">Cancel</button>
          <button @click="save()" class="flex-1 bg-emerald-500 hover:bg-emerald-600 shadow-lg shadow-emerald-500/20 py-3 rounded-xl text-sm font-bold text-white transition-all hover:-translate-y-0.5">Save Offerwall</button>
        </div>
      </div>
    </div>

    <!-- Postback Setup Guide Modal -->
    <div v-if="showPostbackModal && selectedOfferwall" class="fixed inset-0 bg-black/80 backdrop-blur-md flex items-center justify-center p-4 z-50">
      <div class="bg-[#0f172a] border border-white/10 rounded-3xl p-8 w-full max-w-3xl shadow-2xl max-h-[90vh] overflow-y-auto" @click.stop>
        <div class="flex justify-between items-start mb-6">
          <div>
            <h3 class="text-2xl font-bold text-white">Postback Setup: {{ selectedOfferwall.name }}</h3>
            <p class="text-slate-400 mt-1">Configure your offerwall provider to send callbacks to this system.</p>
          </div>
          <button @click="showPostbackModal = false" class="text-slate-400 hover:text-white bg-white/5 hover:bg-white/10 rounded-full p-2 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <div class="bg-black/40 border border-white/5 rounded-2xl p-5 mb-6">
          <h4 class="text-sm font-semibold text-emerald-400 uppercase tracking-wider mb-3">Your Global Postback URL</h4>
          <div class="flex items-center gap-3">
            <code class="flex-1 block bg-black/60 border border-white/10 rounded-xl p-4 text-sm text-indigo-300 break-all select-all font-mono">
              {{ postbackUrl }}
            </code>
            <button @click="copyToClipboard(postbackUrl)" class="bg-white/10 hover:bg-white/20 p-4 rounded-xl text-white transition-colors" title="Copy to clipboard">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
            </button>
          </div>
          <p class="text-xs text-slate-500 mt-3">
            <strong>Note:</strong> You must replace <code class="text-slate-300">{user_id}</code>, <code class="text-slate-300">{reward}</code>, and <code class="text-slate-300">{tx_id}</code> with the actual macro tags provided by your offerwall network (e.g., <code class="text-slate-300">{userID}</code> or <code class="text-slate-300">[SUBID]</code>).
          </p>
        </div>

        <div class="space-y-4">
          <h4 class="text-lg font-bold text-white border-b border-white/10 pb-2">Supported Parameter Macros</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">User Identifier <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_user_id || 'user_id' }})</span></p>
              <p class="text-xs text-slate-400">Pass the authenticated user's ID to this parameter.</p>
            </div>
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">Transaction ID <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_transaction_id || 'transaction_id' }})</span></p>
              <p class="text-xs text-slate-400">Pass the unique offerwall transaction hash to this parameter.</p>
            </div>
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">Reward Amount <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_amount || 'amount' }})</span></p>
              <p class="text-xs text-slate-400">Pass the rewarded provider currency to this parameter.</p>
            </div>
            <div class="bg-white/5 p-4 rounded-xl border border-white/5">
              <p class="text-sm text-slate-300 font-semibold mb-1">Status / Chargeback <span class="text-xs font-normal text-emerald-500">(Mapped to: {{ selectedOfferwall.param_status || 'status' }})</span></p>
              <p class="text-xs text-slate-400">Pass <code class="text-rose-400 bg-rose-400/10 px-1 rounded">{{ selectedOfferwall.status_chargeback_value || 'reversed' }}</code> to reverse a transaction.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

