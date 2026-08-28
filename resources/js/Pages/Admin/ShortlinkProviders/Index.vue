<template>
  <AdminLayout>
    <div class="space-y-6 max-w-7xl mx-auto">
      
      <!-- Top Banner Header -->
      <div class="p-6 rounded-3xl bg-gradient-to-r from-cyan-500/10 via-indigo-500/10 to-slate-900 border border-cyan-500/30 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xl">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-cyan-500 text-slate-950">Auto-Monetization Engine</span>
            <span class="text-xs text-cyan-400 font-mono">{{ activeProvidersCount }} Active Providers</span>
          </div>
          <h1 class="text-xl font-black text-white">Shortlink Providers & API Keys</h1>
          <p class="text-xs text-slate-400 mt-1">Save your shortener API keys once. When creating tasks, just select the provider and set your coin rewards.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <button 
            @click="openCreateModal"
            class="px-4 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-lg shadow-cyan-500/20 transition flex items-center gap-1.5 transform hover:-translate-y-0.5"
          >
            <span>➕</span> Add Shortlink Provider
          </button>
        </div>
      </div>

      <!-- Provider Cards Grid -->
      <div v-if="providers.length === 0" class="glass-card p-12 rounded-3xl border border-slate-800 text-center space-y-3">
        <div class="text-5xl">🔗</div>
        <h3 class="text-base font-bold text-white">No Shortlink Providers Added Yet</h3>
        <p class="text-xs text-slate-400 max-w-md mx-auto">Add your first shortener provider (like ShrinkMe.io, Exe.io, or GPLinks) by saving your API key once.</p>
        <button 
          @click="openCreateModal" 
          class="mt-2 px-5 py-2.5 bg-cyan-500 hover:bg-cyan-400 text-slate-950 font-extrabold text-xs rounded-xl transition"
        >
          ➕ Add First Provider
        </button>
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <div 
          v-for="provider in providers" 
          :key="provider.id"
          class="glass-card rounded-2xl border p-5 space-y-4 flex flex-col justify-between transition-all relative group"
          :class="provider.is_active ? 'border-cyan-500/30 bg-slate-900/60 shadow-lg shadow-cyan-500/5' : 'border-slate-800/80 bg-slate-950/40 opacity-75'"
        >
          <div class="space-y-3">
            <!-- Header Row -->
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-lg shadow-inner">
                  {{ provider.icon || '🔗' }}
                </div>
                <div>
                  <h3 class="text-sm font-black text-white flex items-center gap-1.5">
                    {{ provider.name }}
                  </h3>
                  <span class="text-[10px] font-mono text-slate-400">Slug: {{ provider.slug }}</span>
                </div>
              </div>

              <!-- Status Toggle -->
              <button 
                @click="toggleStatus(provider)"
                class="px-2.5 py-1 rounded-lg text-[11px] font-bold transition flex items-center gap-1"
                :class="provider.is_active ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-500 border border-slate-700'"
              >
                <span class="w-1.5 h-1.5 rounded-full" :class="provider.is_active ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500'"></span>
                {{ provider.is_active ? 'Active' : 'Disabled' }}
              </button>
            </div>

            <!-- API URL Info -->
            <div class="p-3 bg-slate-950/80 rounded-xl border border-slate-800/80 space-y-2">
              <div>
                <span class="text-[10px] text-slate-500 uppercase tracking-wider font-bold block">Base API Endpoint</span>
                <span class="text-xs font-mono text-cyan-300 truncate block">{{ provider.api_url }}</span>
              </div>

              <!-- API Key with Mask Toggle -->
              <div>
                <div class="flex items-center justify-between">
                  <span class="text-[10px] text-slate-500 uppercase tracking-wider font-bold">API Key / Token</span>
                  <button 
                    @click="toggleKeyVisibility(provider.id)" 
                    class="text-[10px] text-slate-400 hover:text-cyan-300 transition underline"
                  >
                    {{ showKeys[provider.id] ? 'Hide' : 'Show' }}
                  </button>
                </div>
                <div class="text-xs font-mono text-amber-300 truncate mt-0.5">
                  <span v-if="showKeys[provider.id]">{{ provider.api_key }}</span>
                  <span v-else>••••••••••••••••••••••••••••••••</span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-400">
              <span>Daily Limit: <strong class="text-white">{{ provider.daily_limit }} view/day</strong></span>
              <span class="text-[10px] text-emerald-400 font-medium">⚡ 100% Auto Link Gen</span>
            </div>
          </div>

          <!-- Actions Footer -->
          <div class="flex items-center justify-between pt-3 border-t border-slate-800/80 gap-2">
            <button 
              @click="openEditModal(provider)"
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-lg transition flex items-center gap-1"
            >
              <span>✏️</span> Edit
            </button>

            <button 
              @click="confirmDelete(provider)"
              class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 border border-rose-500/20 text-xs font-bold rounded-lg transition flex items-center gap-1"
            >
              <span>🗑️</span> Delete
            </button>
          </div>
        </div>
      </div>

    </div>

    <!-- Modal: Add / Edit Provider -->
    <Teleport to="body">
      <div v-if="isModalOpen" class="fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-slate-900 border-2 border-cyan-500/30 rounded-3xl p-6 sm:p-7 space-y-4 shadow-2xl animate-scale-in">
          
          <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2.5">
              <span class="w-8 h-8 rounded-xl bg-cyan-500/15 border border-cyan-500/30 flex items-center justify-center text-cyan-400 font-bold text-sm">🔗</span>
              <div>
                <h3 class="text-base font-bold text-white">{{ isEditing ? 'Edit Shortlink Provider' : 'Add Shortlink Provider' }}</h3>
                <p class="text-[11px] text-slate-400">Configure provider endpoint & API token</p>
              </div>
            </div>
            <button @click="closeModal" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-xs font-bold transition">✕</button>
          </div>

          <form @submit.prevent="submitForm" class="space-y-4">
            
            <!-- Quick Preset Dropdown (When creating) -->
            <div v-if="!isEditing">
              <label class="block text-xs font-semibold text-cyan-400 mb-1">Select Preset (Auto-Fills Details)</label>
              <select @change="e => applyPreset(e.target.value)" class="w-full px-3.5 py-2 bg-slate-950 border border-cyan-500/40 rounded-xl text-xs text-white focus:border-cyan-400 focus:outline-none font-bold">
                <option value="">-- Choose a Preset --</option>
                <option v-for="(preset, key) in presets" :key="key" :value="key">
                  {{ preset.icon }} {{ preset.name }}
                </option>
              </select>
            </div>

            <!-- Provider Name -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Provider Name *</label>
              <input 
                v-model="form.name" 
                type="text" 
                required 
                placeholder="e.g. ShrinkMe.io" 
                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:border-cyan-500 focus:outline-none"
              />
              <div v-if="form.errors.name" class="text-[10px] text-rose-500 mt-1">{{ form.errors.name }}</div>
            </div>

            <!-- Base API URL -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">Base API URL *</label>
              <input 
                v-model="form.api_url" 
                type="url" 
                required 
                placeholder="e.g. https://shrinkme.io/api" 
                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs font-mono text-cyan-300 focus:border-cyan-500 focus:outline-none"
              />
              <div v-if="form.errors.api_url" class="text-[10px] text-rose-500 mt-1">{{ form.errors.api_url }}</div>
            </div>

            <!-- API Key -->
            <div>
              <label class="block text-xs font-semibold text-slate-300 mb-1">API Key / Token *</label>
              <input 
                v-model="form.api_key" 
                type="text" 
                required 
                placeholder="Paste API token from provider dashboard..." 
                class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs font-mono text-amber-300 focus:border-cyan-500 focus:outline-none"
              />
              <div v-if="form.errors.api_key" class="text-[10px] text-rose-500 mt-1">{{ form.errors.api_key }}</div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <!-- Icon -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Icon Emoji</label>
                <input 
                  v-model="form.icon" 
                  type="text" 
                  maxlength="4" 
                  placeholder="🔗" 
                  class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:border-cyan-500 focus:outline-none text-center"
                />
              </div>

              <!-- Daily Limit -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Daily Limit / User</label>
                <input 
                  v-model.number="form.daily_limit" 
                  type="number" 
                  min="1" 
                  max="100" 
                  class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:border-cyan-500 focus:outline-none"
                />
              </div>
            </div>

            <!-- Active Checkbox -->
            <div class="flex items-center gap-2 pt-1">
              <input 
                type="checkbox" 
                v-model="form.is_active" 
                id="providerIsActive" 
                class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-cyan-500 focus:ring-cyan-500"
              />
              <label for="providerIsActive" class="text-xs font-bold text-slate-200 cursor-pointer">Enable this Provider immediately</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
              <button 
                type="button" 
                @click="closeModal" 
                class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl transition"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="px-5 py-2.5 bg-gradient-to-r from-cyan-500 to-indigo-600 hover:from-cyan-400 hover:to-indigo-500 text-white text-xs font-extrabold rounded-xl shadow-lg shadow-cyan-500/20 transition disabled:opacity-50"
              >
                {{ isEditing ? 'Update Provider' : 'Save Provider' }}
              </button>
            </div>

          </form>
        </div>
      </div>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  providers: {
    type: Array,
    default: () => [],
  },
  presets: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const activeProvidersCount = computed(() => props.providers.filter(p => p.is_active).length);

const isModalOpen = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const showKeys = ref({});

const toggleKeyVisibility = (id) => {
  showKeys.value[id] = !showKeys.value[id];
};

const form = useForm({
  name: '',
  api_url: '',
  api_key: '',
  daily_limit: 1,
  is_active: true,
  icon: '🔗',
});

const applyPreset = (key) => {
  if (!key || !props.presets[key]) return;
  const p = props.presets[key];
  form.name = p.name;
  form.api_url = p.api_url;
  form.icon = p.icon;
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  form.reset();
  form.clearErrors();
  isModalOpen.value = true;
};

const openEditModal = (provider) => {
  isEditing.value = true;
  editingId.value = provider.id;
  form.name = provider.name;
  form.api_url = provider.api_url;
  form.api_key = provider.api_key;
  form.daily_limit = provider.daily_limit;
  form.is_active = provider.is_active;
  form.icon = provider.icon || '🔗';
  form.clearErrors();
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
};

const submitForm = () => {
  if (isEditing.value) {
    form.put(`${adminPath.value}/shortlink-providers/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post(`${adminPath.value}/shortlink-providers`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
};

const toggleStatus = (provider) => {
  router.post(`${adminPath.value}/shortlink-providers/${provider.id}/toggle`, {}, {
    preserveScroll: true,
  });
};

const confirmDelete = (provider) => {
  if (confirm(`Are you sure you want to delete "${provider.name}"? Existing tasks will not have this API provider available.`)) {
    router.delete(`${adminPath.value}/shortlink-providers/${provider.id}`, {
      preserveScroll: true,
    });
  }
};
</script>
