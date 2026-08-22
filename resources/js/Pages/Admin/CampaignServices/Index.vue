<template>
  <AdminLayout>
    <div class="space-y-6 pb-20">
      
      <!-- Header Section -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-400">
            Campaign Services
          </h1>
          <p class="text-sm text-slate-400 mt-1">Manage dynamic pricing, margins, proof requirements, and platforms for micro-campaigns</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Services List (Left / Main) -->
        <div class="lg:col-span-2 space-y-4">
          <div class="glass-card bg-slate-900/40 backdrop-blur-xl border border-slate-800/60 rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-slate-800/60 flex justify-between items-center bg-slate-800/20">
              <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="text-xl">🚀</span> Active Services
              </h2>
              <div class="bg-indigo-500/20 text-indigo-400 px-3 py-1 rounded-full text-xs font-bold border border-indigo-500/30">
                {{ campaignServices.length }} Total
              </div>
            </div>

            <div class="p-6">
              <div v-if="campaignServices.length === 0" class="text-center py-12 flex flex-col items-center justify-center space-y-3">
                <div class="w-16 h-16 rounded-full bg-slate-800/50 flex items-center justify-center text-3xl mb-2 shadow-inner">📭</div>
                <p class="text-sm font-semibold text-slate-300">No services configured yet</p>
                <p class="text-xs text-slate-500 max-w-xs">Use the form to add a new platform and action to start allowing user campaigns.</p>
              </div>

              <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="service in campaignServices" :key="service.id" 
                     class="group bg-slate-800/30 hover:bg-slate-800/60 transition-all duration-300 p-5 rounded-2xl border border-slate-700/50 relative overflow-hidden flex flex-col justify-between">
                  
                  <div class="absolute top-0 left-0 w-1 h-full bg-gradient-to-b from-indigo-500 to-cyan-500 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                  
                  <div>
                    <div class="flex justify-between items-start mb-3">
                      <div>
                        <div class="flex flex-wrap items-center gap-2">
                          <h3 class="text-base font-bold text-slate-100">
                            {{ service.platform }}
                          </h3>
                          <span v-if="service.is_active" class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Active
                          </span>
                          <span v-else class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full border border-rose-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Disabled
                          </span>
                          <span v-if="service.requires_proof" class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded-full border border-amber-500/20">
                            📸 Proof Required
                          </span>
                        </div>
                        <p class="text-xs font-medium text-cyan-400 mt-1.5 px-2 py-0.5 bg-cyan-900/30 rounded-md inline-block">
                          {{ service.action }}
                        </p>
                      </div>
                      
                      <div class="flex items-center gap-1">
                        <button @click="openEditModal(service)" 
                                class="text-slate-400 hover:text-indigo-400 hover:bg-indigo-500/10 p-2 rounded-xl transition-colors"
                                title="Edit Service">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 210.3H3v-3.572L16.732 3.732z" />
                          </svg>
                        </button>
                        <button @click="confirmDelete(service.id)" 
                                class="text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 p-2 rounded-xl transition-colors"
                                title="Delete Service">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                          </svg>
                        </button>
                      </div>
                    </div>
                  </div>

                  <div>
                    <div class="grid grid-cols-2 gap-2 pt-3 border-t border-slate-700/50">
                      <div class="bg-slate-900/50 p-2 rounded-xl text-center border border-slate-800">
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-0.5">Creator Cost</p>
                        <p class="text-xs font-black text-rose-400">{{ service.creator_cost }} <span class="text-[9px] text-slate-500 font-normal">pts</span></p>
                      </div>
                      <div class="bg-slate-900/50 p-2 rounded-xl text-center border border-slate-800">
                        <p class="text-[10px] text-slate-400 font-semibold uppercase tracking-wider mb-0.5">User Reward</p>
                        <p class="text-xs font-black text-emerald-400">{{ service.clicker_reward }} <span class="text-[9px] text-slate-500 font-normal">pts</span></p>
                      </div>
                    </div>

                    <!-- Profit Margin Indicator -->
                    <div class="mt-2.5 bg-indigo-950/40 border border-indigo-800/40 rounded-xl px-3 py-1.5 flex justify-between items-center">
                      <span class="text-[10px] font-bold text-indigo-300 uppercase tracking-wider">System Margin</span>
                      <span class="text-xs font-extrabold" :class="(parseFloat(service.creator_cost) - parseFloat(service.clicker_reward)) >= 0 ? 'text-cyan-300' : 'text-rose-400'">
                        {{ (parseFloat(service.creator_cost) - parseFloat(service.clicker_reward)) >= 0 ? '+' : '' }}{{ (parseFloat(service.creator_cost) - parseFloat(service.clicker_reward)).toFixed(2) }} <span class="text-[9px] font-normal text-indigo-400">pts/click</span>
                      </span>
                    </div>
                  </div>
                  
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Form (Right / Sidebar) -->
        <div class="lg:col-span-1">
          <div class="glass-card bg-slate-900/40 backdrop-blur-xl border border-slate-800/60 rounded-3xl p-6 shadow-2xl sticky top-6">
            <div class="mb-6 pb-4 border-b border-slate-800/60">
              <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <span class="text-xl">✨</span> Add New Service
              </h2>
              <p class="text-xs text-slate-400 mt-1">Configure pricing for a new platform action</p>
            </div>
            
            <form @submit.prevent="createService" class="space-y-4">
              <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider pl-1">Platform</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-slate-500">📱</span>
                  </div>
                  <input v-model="serviceForm.platform" type="text" placeholder="e.g. Facebook, YouTube, Instagram" required 
                         class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl text-sm text-white placeholder-slate-600 transition-all shadow-inner" />
                </div>
                <p v-if="serviceForm.errors.platform" class="text-xs text-rose-400 mt-1 font-medium">{{ serviceForm.errors.platform }}</p>
              </div>
              
              <div class="space-y-1">
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider pl-1">Action Type</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-slate-500">⚡</span>
                  </div>
                  <input v-model="serviceForm.action" type="text" placeholder="e.g. Like, Subscribe, Follow, Share" required 
                         class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl text-sm text-white placeholder-slate-600 transition-all shadow-inner" />
                </div>
                <p v-if="serviceForm.errors.action" class="text-xs text-rose-400 mt-1 font-medium">{{ serviceForm.errors.action }}</p>
              </div>
              
              <div class="grid grid-cols-2 gap-4 pt-2">
                <div class="space-y-1">
                  <label class="block text-[11px] font-bold text-rose-400 uppercase tracking-wider pl-1" title="Cost charged to the campaign creator">Creator Cost</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span class="text-slate-500 text-xs">🪙</span>
                    </div>
                    <input v-model="serviceForm.creator_cost" type="number" step="0.1" required 
                           class="w-full pl-8 pr-2 py-2.5 bg-slate-950/50 border border-rose-900/30 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 rounded-xl text-sm text-white font-mono transition-all shadow-inner" />
                  </div>
                  <p v-if="serviceForm.errors.creator_cost" class="text-xs text-rose-400 mt-1 font-medium">{{ serviceForm.errors.creator_cost }}</p>
                </div>
                
                <div class="space-y-1">
                  <label class="block text-[11px] font-bold text-emerald-400 uppercase tracking-wider pl-1" title="Reward given to the user who clicks">User Reward</label>
                  <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                      <span class="text-slate-500 text-xs">🎁</span>
                    </div>
                    <input v-model="serviceForm.clicker_reward" type="number" step="0.1" required 
                           class="w-full pl-8 pr-2 py-2.5 bg-slate-950/50 border border-emerald-900/30 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl text-sm text-white font-mono transition-all shadow-inner" />
                  </div>
                  <p v-if="serviceForm.errors.clicker_reward" class="text-xs text-rose-400 mt-1 font-medium">{{ serviceForm.errors.clicker_reward }}</p>
                </div>
              </div>

              <!-- Proof Requirement Toggle -->
              <div class="pt-2 flex items-center justify-between bg-slate-950/40 p-3 rounded-xl border border-slate-800">
                <div>
                  <span class="text-xs font-bold text-slate-300 block">Requires Proof</span>
                  <span class="text-[10px] text-slate-500 block">User must submit action proof</span>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                  <input type="checkbox" v-model="serviceForm.requires_proof" class="sr-only peer">
                  <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
                </label>
              </div>

              <!-- Live Profit Margin Summary -->
              <div class="bg-slate-950/40 p-3 rounded-xl border border-slate-800 text-xs flex justify-between items-center">
                <span class="text-slate-400 font-medium">Estimated Platform Profit:</span>
                <span class="font-bold font-mono" :class="(parseFloat(serviceForm.creator_cost || 0) - parseFloat(serviceForm.clicker_reward || 0)) >= 0 ? 'text-cyan-400' : 'text-rose-400'">
                  {{ (parseFloat(serviceForm.creator_cost || 0) - parseFloat(serviceForm.clicker_reward || 0)) >= 0 ? '+' : '' }}{{ (parseFloat(serviceForm.creator_cost || 0) - parseFloat(serviceForm.clicker_reward || 0)).toFixed(2) }} pts/click
                </span>
              </div>

              <div class="pt-2">
                <button type="submit" :disabled="serviceForm.processing"
                        class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-cyan-600 hover:from-emerald-500 hover:to-cyan-500 text-white font-bold rounded-xl text-sm flex items-center justify-center gap-2 shadow-lg shadow-emerald-900/20 transform transition-all active:scale-95 disabled:opacity-50">
                  <span v-if="serviceForm.processing" class="animate-spin text-xl">⏳</span>
                  <span v-else>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                  </span>
                  Add Campaign Service
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Edit Service Modal -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/70 backdrop-blur-md" @click="closeEditModal"></div>
      
      <div class="relative bg-slate-900 border border-slate-700/80 rounded-3xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all">
        <div class="p-6 border-b border-slate-800 flex justify-between items-center bg-slate-800/40">
          <h3 class="text-lg font-bold text-white flex items-center gap-2">
            <span>✏️</span> Edit Campaign Service
          </h3>
          <button @click="closeEditModal" class="text-slate-400 hover:text-white transition-colors">✕</button>
        </div>

        <form @submit.prevent="updateService" class="p-6 space-y-4">
          <div class="space-y-1">
            <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider pl-1">Platform</label>
            <input v-model="editForm.platform" type="text" required 
                   class="w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/60 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white font-medium" />
            <p v-if="editForm.errors.platform" class="text-xs text-rose-400 mt-1 font-medium">{{ editForm.errors.platform }}</p>
          </div>

          <div class="space-y-1">
            <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider pl-1">Action Type</label>
            <input v-model="editForm.action" type="text" required 
                   class="w-full px-4 py-2.5 bg-slate-950/60 border border-slate-700/60 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white font-medium" />
            <p v-if="editForm.errors.action" class="text-xs text-rose-400 mt-1 font-medium">{{ editForm.errors.action }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-[11px] font-bold text-rose-400 uppercase tracking-wider pl-1">Creator Cost</label>
              <input v-model="editForm.creator_cost" type="number" step="0.1" required 
                     class="w-full px-3 py-2 bg-slate-950/60 border border-rose-900/40 focus:border-rose-500 rounded-xl text-sm text-white font-mono" />
              <p v-if="editForm.errors.creator_cost" class="text-xs text-rose-400 mt-1 font-medium">{{ editForm.errors.creator_cost }}</p>
            </div>

            <div class="space-y-1">
              <label class="block text-[11px] font-bold text-emerald-400 uppercase tracking-wider pl-1">User Reward</label>
              <input v-model="editForm.clicker_reward" type="number" step="0.1" required 
                     class="w-full px-3 py-2 bg-slate-950/60 border border-emerald-900/40 focus:border-emerald-500 rounded-xl text-sm text-white font-mono" />
              <p v-if="editForm.errors.clicker_reward" class="text-xs text-rose-400 mt-1 font-medium">{{ editForm.errors.clicker_reward }}</p>
            </div>
          </div>

          <!-- Status Toggle -->
          <div class="pt-2 flex items-center justify-between bg-slate-950/40 p-3 rounded-xl border border-slate-800">
            <span class="text-xs font-bold text-slate-300">Active Status:</span>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="editForm.is_active" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
            </label>
          </div>

          <!-- Proof Requirement Toggle -->
          <div class="flex items-center justify-between bg-slate-950/40 p-3 rounded-xl border border-slate-800">
            <span class="text-xs font-bold text-slate-300">Requires Proof:</span>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="editForm.requires_proof" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-500"></div>
            </label>
          </div>

          <!-- Live Margin -->
          <div class="bg-indigo-950/40 p-3 rounded-xl border border-indigo-900/40 text-xs flex justify-between items-center">
            <span class="text-indigo-300 font-medium">Calculated System Margin:</span>
            <span class="font-bold font-mono" :class="(parseFloat(editForm.creator_cost || 0) - parseFloat(editForm.clicker_reward || 0)) >= 0 ? 'text-cyan-300' : 'text-rose-400'">
              {{ (parseFloat(editForm.creator_cost || 0) - parseFloat(editForm.clicker_reward || 0)) >= 0 ? '+' : '' }}{{ (parseFloat(editForm.creator_cost || 0) - parseFloat(editForm.clicker_reward || 0)).toFixed(2) }} pts/click
            </span>
          </div>

          <div class="flex gap-3 pt-4 border-t border-slate-800">
            <button type="button" @click="closeEditModal" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-sm transition-colors">
              Cancel
            </button>
            <button type="submit" :disabled="editForm.processing" class="flex-1 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-sm transition-colors shadow-lg shadow-indigo-900/20 disabled:opacity-50 flex items-center justify-center gap-2">
              <span v-if="editForm.processing" class="animate-spin text-sm">⏳</span>
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeDeleteModal"></div>
      
      <div class="relative bg-slate-900 border border-slate-700 rounded-3xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        <div class="p-6">
          <div class="w-12 h-12 rounded-full bg-rose-500/20 text-rose-500 flex items-center justify-center mb-4 mx-auto">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-xl font-bold text-white text-center mb-2">Delete Service?</h3>
          <p class="text-sm text-slate-400 text-center mb-6">
            Are you sure you want to remove this campaign service? This action cannot be undone.
          </p>
          
          <div class="flex gap-3">
            <button @click="closeDeleteModal" class="flex-1 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-sm transition-colors">
              Cancel
            </button>
            <button @click="executeDelete" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-sm transition-colors shadow-lg shadow-rose-900/20">
              Delete
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const props = defineProps({
  campaignServices: Array,
});

const serviceForm = useForm({
  platform: '',
  action: '',
  creator_cost: 5.0,
  clicker_reward: 2.0,
  requires_proof: false,
  is_active: true,
});

const editForm = useForm({
  id: null,
  platform: '',
  action: '',
  creator_cost: 5.0,
  clicker_reward: 2.0,
  requires_proof: false,
  is_active: true,
});

const showDeleteModal = ref(false);
const showEditModal = ref(false);
const serviceToDelete = ref(null);

const createService = () => {
  serviceForm.post(`${adminPath.value}/campaign-services`, {
    onSuccess: () => {
      serviceForm.reset();
    },
    preserveScroll: true
  });
};

const openEditModal = (service) => {
  editForm.id = service.id;
  editForm.platform = service.platform;
  editForm.action = service.action;
  editForm.creator_cost = service.creator_cost;
  editForm.clicker_reward = service.clicker_reward;
  editForm.requires_proof = Boolean(service.requires_proof);
  editForm.is_active = Boolean(service.is_active);
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
  editForm.reset();
};

const updateService = () => {
  if (editForm.id) {
    editForm.put(`${adminPath.value}/campaign-services/${editForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        closeEditModal();
      }
    });
  }
};

const confirmDelete = (id) => {
  serviceToDelete.value = id;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  serviceToDelete.value = null;
};

const executeDelete = () => {
  if (serviceToDelete.value) {
    router.delete(`${adminPath.value}/campaign-services/${serviceToDelete.value}`, {
      preserveScroll: true,
      onSuccess: () => {
        closeDeleteModal();
      }
    });
  }
};
</script>
