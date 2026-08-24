<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Page Header & Stats -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-xl shadow-lg shadow-indigo-500/10">
              💳
            </div>
            <div>
              <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight">Payment Methods & Gateways</h1>
              <p class="text-xs text-slate-400">Configure withdrawal options, customize min limits and service charges</p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="openCreateModal"
            class="px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-500/25 transition-all flex items-center gap-2 cursor-pointer active:scale-95"
          >
            <span>➕</span>
            <span>Add Payment Method</span>
          </button>
        </div>
      </div>

      <!-- Flash Notifications / Error Alerts -->
      <div v-if="$page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs font-bold flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span>✅</span>
          <span>{{ $page.props.flash.success }}</span>
        </div>
      </div>
      <div v-if="$page.props.errors?.message" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs font-bold flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span>⚠️</span>
          <span>{{ $page.props.errors.message }}</span>
        </div>
      </div>

      <!-- Quick Stats Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-card p-4 rounded-2xl border border-slate-800 flex items-center justify-between">
          <div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Gateways</div>
            <div class="text-2xl font-black text-white mt-1">{{ stats.total }}</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-slate-800/80 border border-slate-700/50 flex items-center justify-center text-lg">
            🏦
          </div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/[0.02] flex items-center justify-between">
          <div>
            <div class="text-[11px] font-bold text-emerald-400 uppercase tracking-wider">Active & Visible</div>
            <div class="text-2xl font-black text-emerald-300 mt-1">{{ stats.active }}</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center text-lg text-emerald-400">
            🟢
          </div>
        </div>

        <div class="glass-card p-4 rounded-2xl border border-rose-500/20 bg-rose-500/[0.02] flex items-center justify-between">
          <div>
            <div class="text-[11px] font-bold text-rose-400 uppercase tracking-wider">Disabled / Inactive</div>
            <div class="text-2xl font-black text-rose-300 mt-1">{{ stats.inactive }}</div>
          </div>
          <div class="w-10 h-10 rounded-xl bg-rose-500/15 border border-rose-500/30 flex items-center justify-center text-lg text-rose-400">
            🔴
          </div>
        </div>
      </div>

      <!-- Methods Table / Cards -->
      <div class="glass-card rounded-3xl border border-slate-800 overflow-hidden">
        <div class="p-5 border-b border-slate-800/80 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-sm font-bold text-white">Configured Gateways</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
              {{ methods.length }} Gateways
            </span>
          </div>
          <p class="text-xs text-slate-500 hidden sm:block">Toggle switch to instantly enable or disable any method on the user /withdraw page</p>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-900/60 text-slate-400 border-b border-slate-800 uppercase text-[10px] font-bold tracking-wider">
              <tr>
                <th class="px-5 py-3.5">Method / Gateway</th>
                <th class="px-4 py-3.5">Type & Currency</th>
                <th class="px-4 py-3.5">Conversion Rate</th>
                <th class="px-4 py-3.5">Min Points</th>
                <th class="px-4 py-3.5">Service Fee</th>
                <th class="px-4 py-3.5">Usage</th>
                <th class="px-4 py-3.5 text-center">Status</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="m in methods" :key="m.id" class="hover:bg-white/[0.02] transition-colors">
                <!-- Gateway Details -->
                <td class="px-5 py-4">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-900 border border-slate-800 flex items-center justify-center text-lg shrink-0">
                      {{ m.icon || '💳' }}
                    </div>
                    <div>
                      <div class="font-bold text-white flex items-center gap-2">
                        <span>{{ m.name }}</span>
                        <span class="text-[9px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-slate-400">
                          code: {{ m.code }}
                        </span>
                      </div>
                      <div class="text-[10px] text-slate-500 truncate max-w-xs mt-0.5">
                        {{ m.instructions || 'Placeholder: ' + m.account_placeholder }}
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Type & Currency -->
                <td class="px-4 py-4">
                  <div class="space-y-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase inline-block" :class="getTypeBadge(m.type)">
                      {{ formatType(m.type) }}
                    </span>
                    <div class="text-[11px] font-extrabold text-indigo-300 font-mono flex items-center gap-1">
                      <span>{{ m.currency_symbol || '৳' }}</span>
                      <span>{{ m.currency || 'BDT' }}</span>
                    </div>
                  </div>
                </td>

                <!-- Conversion Rate -->
                <td class="px-4 py-4 font-mono">
                  <span v-if="m.conversion_rate" class="font-bold text-cyan-400">
                    1 {{ m.currency }} = {{ m.conversion_rate }} Pts
                  </span>
                  <span v-else class="text-slate-500 text-[11px]">
                    Global Rate
                  </span>
                </td>

                <!-- Min Limit -->
                <td class="px-4 py-4 font-mono">
                  <span v-if="m.min_points" class="font-bold text-emerald-400">
                    {{ m.min_points }} Pts
                  </span>
                  <span v-else class="text-slate-500 italic">
                    Global Rule
                  </span>
                </td>

                <!-- Service Fee -->
                <td class="px-4 py-4 font-mono">
                  <div v-if="m.fixed_charge > 0" class="text-rose-400 font-bold">
                    +{{ m.fixed_charge }} Pts fixed
                  </div>
                  <div v-else-if="m.charge_percent > 0" class="text-amber-400 font-bold">
                    {{ m.charge_percent }}%
                  </div>
                  <div v-else class="text-slate-500">
                    Free (0%)
                  </div>
                </td>

                <!-- Usage / Withdrawals Count -->
                <td class="px-4 py-4">
                  <span class="text-slate-300 font-bold">{{ m.withdrawals_count }}</span>
                  <span class="text-slate-500 text-[10px] ml-1">requests</span>
                </td>

                <!-- Status Toggle -->
                <td class="px-4 py-4 text-center">
                  <button
                    type="button"
                    @click="toggleMethod(m)"
                    class="relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none"
                    :class="m.is_active ? 'bg-emerald-500' : 'bg-slate-700'"
                  >
                    <span
                      class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                      :class="m.is_active ? 'translate-x-5' : 'translate-x-0'"
                    />
                  </button>
                </td>

                <!-- Actions -->
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button
                      @click="openEditModal(m)"
                      class="px-2.5 py-1.5 rounded-lg bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/20 text-indigo-300 hover:text-white font-bold transition-all flex items-center gap-1 cursor-pointer active:scale-95"
                    >
                      <span>✏️</span>
                      <span>Edit</span>
                    </button>
                    <button
                      @click="openDeleteModal(m)"
                      class="px-2.5 py-1.5 rounded-lg bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 text-rose-300 hover:text-white font-bold transition-all flex items-center gap-1 cursor-pointer active:scale-95"
                    >
                      <span>🗑️</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Add / Edit Modal -->
      <Transition name="modal">
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
          <div class="bg-slate-900 border border-indigo-500/30 rounded-3xl p-6 w-full max-w-lg shadow-[0_0_50px_-12px_rgba(99,102,241,0.3)] max-h-[90vh] overflow-y-auto space-y-4">
            <div class="flex justify-between items-center border-b border-slate-800 pb-3">
              <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                <span>{{ isEditing ? '✏️ Edit Payment Method' : '➕ Add New Payment Method' }}</span>
              </h3>
              <button @click="closeModal" class="text-slate-400 hover:text-white text-lg">✕</button>
            </div>

            <form @submit.prevent="submitForm" class="space-y-4">
              <!-- Global Form Error Banner -->
              <div v-if="Object.keys(form.errors).length > 0" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
                <div class="font-bold flex items-center gap-1.5">
                  <span>⚠️</span>
                  <span>Please correct the errors below:</span>
                </div>
                <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-300/90 pl-1">
                  <li v-for="(err, key) in form.errors" :key="key">{{ err }}</li>
                </ul>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Method Name -->
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Display Name *</label>
                  <input
                    v-model="form.name"
                    @input="handleNameInput"
                    type="text"
                    required
                    placeholder="e.g. Binance Pay (USDT)"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500"
                    :class="{'border-rose-500': form.errors.name}"
                  />
                  <span v-if="form.errors.name" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.name }}</span>
                </div>

                <!-- Unique Code -->
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Unique Code *</label>
                  <input
                    v-model="form.code"
                    type="text"
                    required
                    placeholder="e.g. usdt or binance_pay"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs font-mono focus:outline-none focus:border-indigo-500"
                    :class="{'border-rose-500': form.errors.code}"
                  />
                  <span v-if="form.errors.code" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.code }}</span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Gateway Type -->
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Category Type *</label>
                  <select
                    v-model="form.type"
                    @change="handleTypeChange"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500"
                  >
                    <option value="mobile_banking">Mobile Banking</option>
                    <option value="recharge">Mobile Recharge</option>
                    <option value="crypto">Cryptocurrency (USDT/Crypto)</option>
                    <option value="bank">Bank Wire</option>
                    <option value="other">Other Gateway</option>
                  </select>
                  <span v-if="form.errors.type" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.type }}</span>
                </div>

                <!-- Currency -->
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Currency Code *</label>
                  <input
                    v-model="form.currency"
                    type="text"
                    required
                    placeholder="BDT, USDT, USD"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs font-mono uppercase focus:outline-none focus:border-indigo-500"
                    :class="{'border-rose-500': form.errors.currency}"
                  />
                  <span v-if="form.errors.currency" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.currency }}</span>
                </div>

                <!-- Currency Symbol -->
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Currency Symbol *</label>
                  <input
                    v-model="form.currency_symbol"
                    type="text"
                    required
                    placeholder="৳, $, €, etc."
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs text-center font-bold focus:outline-none focus:border-indigo-500"
                    :class="{'border-rose-500': form.errors.currency_symbol}"
                  />
                  <span v-if="form.errors.currency_symbol" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.currency_symbol }}</span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Icon / Emoji -->
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Emoji / Logo Icon</label>
                  <input
                    v-model="form.icon"
                    type="text"
                    placeholder="e.g. 🌸, 🟠, 🚀, 🟡, 📱, 🏦"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500"
                  />
                  <span v-if="form.errors.icon" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.icon }}</span>
                </div>

                <!-- Custom Conversion Rate -->
                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">
                    Custom Rate (Pts / 1 {{ form.currency || 'Unit' }})
                  </label>
                  <input
                    v-model="form.conversion_rate"
                    type="number"
                    step="0.01"
                    min="0"
                    placeholder="Empty = Global (100)"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs font-mono focus:outline-none focus:border-indigo-500"
                  />
                  <span v-if="form.errors.conversion_rate" class="text-[10px] text-rose-400 mt-0.5 block">{{ form.errors.conversion_rate }}</span>
                  <span v-else class="text-[10px] text-slate-500 mt-0.5 block">e.g. 12000 for 1 USDT</span>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Min Points Override -->
                <div>
                  <label class="block text-[11px] font-semibold text-slate-300 mb-1">Min Limit (Pts)</label>
                  <input
                    v-model="form.min_points"
                    type="number"
                    min="0"
                    placeholder="e.g. 1000 (Empty=Global)"
                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs font-mono focus:outline-none focus:border-indigo-500"
                  />
                  <span v-if="form.errors.min_points" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.min_points }}</span>
                </div>

                <!-- Fixed Fee -->
                <div>
                  <label class="block text-[11px] font-semibold text-slate-300 mb-1">Fixed Fee (Pts)</label>
                  <input
                    v-model="form.fixed_charge"
                    type="number"
                    min="0"
                    step="0.1"
                    placeholder="0"
                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs font-mono focus:outline-none focus:border-indigo-500"
                  />
                  <span v-if="form.errors.fixed_charge" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.fixed_charge }}</span>
                </div>

                <!-- Charge Percent -->
                <div>
                  <label class="block text-[11px] font-semibold text-slate-300 mb-1">Charge (%)</label>
                  <input
                    v-model="form.charge_percent"
                    type="number"
                    min="0"
                    max="100"
                    step="0.1"
                    placeholder="0"
                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs font-mono focus:outline-none focus:border-indigo-500"
                  />
                  <span v-if="form.errors.charge_percent" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.charge_percent }}</span>
                </div>
              </div>

              <!-- Placeholder -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Account Field Placeholder *</label>
                <input
                  v-model="form.account_placeholder"
                  type="text"
                  required
                  placeholder="e.g. 017XXXXXXXX or USDT TRC20 Address"
                  class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500"
                  :class="{'border-rose-500': form.errors.account_placeholder}"
                />
                <span v-if="form.errors.account_placeholder" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.account_placeholder }}</span>
              </div>

              <!-- User Instructions -->
              <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">User Instructions (Optional)</label>
                <textarea
                  v-model="form.instructions"
                  rows="2"
                  placeholder="Instructions displayed to users when selecting this method..."
                  class="w-full px-3.5 py-2 bg-slate-950 border border-slate-700/60 rounded-xl text-white text-xs focus:outline-none focus:border-indigo-500 resize-none"
                ></textarea>
                <span v-if="form.errors.instructions" class="text-[10px] text-rose-400 mt-1 block">{{ form.errors.instructions }}</span>
              </div>

              <div class="flex items-center justify-between pt-2 border-t border-slate-800">
                <label class="flex items-center gap-2 cursor-pointer text-xs font-bold text-white">
                  <input v-model="form.is_active" type="checkbox" class="w-4 h-4 rounded text-indigo-600 bg-slate-900 border-slate-700" />
                  <span>Active & Enabled</span>
                </label>

                <div class="flex items-center gap-2">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition"
                  >
                    Cancel
                  </button>
                  <button
                    type="submit"
                    :disabled="form.processing"
                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-bold transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                  >
                    <span>{{ form.processing ? 'Saving...' : (isEditing ? 'Update Gateway' : 'Create Gateway') }}</span>
                  </button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </Transition>

      <!-- Delete Confirmation Modal -->
      <Transition name="modal">
        <div v-if="methodToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
          <div class="bg-slate-900 border border-rose-500/30 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-4">
            <div class="flex items-center gap-3 text-rose-400">
              <span class="text-2xl">⚠️</span>
              <h3 class="text-base font-extrabold text-white">Delete Payment Method?</h3>
            </div>

            <p class="text-xs text-slate-300 leading-relaxed">
              Are you sure you want to delete <strong class="text-white">{{ methodToDelete.name }}</strong>? If you only want to temporarily hide it from users, you can toggle it off instead.
            </p>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800">
              <button
                @click="methodToDelete = null"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition"
              >
                Cancel
              </button>
              <button
                @click="confirmDelete"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-rose-600/30 cursor-pointer"
              >
                Delete Method
              </button>
            </div>
          </div>
        </div>
      </Transition>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  methods: Array,
  stats: Object,
});

const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);
const methodToDelete = ref(null);
const prevAutoCode = ref('');

const form = useForm({
  name: '',
  code: '',
  type: 'mobile_banking',
  currency: 'BDT',
  currency_symbol: '৳',
  min_points: null,
  conversion_rate: null,
  fixed_charge: 0,
  charge_percent: 0,
  account_placeholder: '017XXXXXXXX',
  instructions: '',
  icon: '💳',
  is_active: true,
  order: 0,
});

const handleNameInput = () => {
  if (!isEditing.value && (!form.code || form.code === prevAutoCode.value)) {
    const slug = (form.name || '')
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9]+/g, '_')
      .replace(/^_+|_+$/g, '');
    form.code = slug;
    prevAutoCode.value = slug;
  }
};

const handleTypeChange = () => {
  if (form.type === 'crypto') {
    form.currency = 'USDT';
    form.currency_symbol = '$';
    form.icon = form.icon === '💳' ? '🟡' : form.icon;
    form.account_placeholder = 'Binance Pay ID / USDT TRC20 Address';
    if (!form.conversion_rate) form.conversion_rate = 12000;
  } else if (form.type === 'mobile_banking' || form.type === 'recharge') {
    form.currency = 'BDT';
    form.currency_symbol = '৳';
  }
};

const openCreateModal = () => {
  isEditing.value = false;
  editingId.value = null;
  prevAutoCode.value = '';
  form.reset();
  form.clearErrors();
  form.is_active = true;
  form.currency = 'BDT';
  form.currency_symbol = '৳';
  form.account_placeholder = '017XXXXXXXX';
  form.icon = '💳';
  form.type = 'mobile_banking';
  showModal.value = true;
};

const openEditModal = (m) => {
  isEditing.value = true;
  editingId.value = m.id;
  form.clearErrors();
  form.name = m.name;
  form.code = m.code;
  form.type = m.type;
  form.currency = m.currency || 'BDT';
  form.currency_symbol = m.currency_symbol || '৳';
  form.min_points = m.min_points;
  form.conversion_rate = m.conversion_rate;
  form.fixed_charge = m.fixed_charge;
  form.charge_percent = m.charge_percent;
  form.account_placeholder = m.account_placeholder;
  form.instructions = m.instructions || '';
  form.icon = m.icon || '💳';
  form.is_active = m.is_active;
  form.order = m.order || 0;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  form.reset();
  form.clearErrors();
};

const submitForm = () => {
  if (isEditing.value) {
    form.put(`/secret-panel/payment-methods/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  } else {
    form.post('/secret-panel/payment-methods', {
      preserveScroll: true,
      onSuccess: () => closeModal(),
    });
  }
};

const toggleMethod = (m) => {
  router.post(`/secret-panel/payment-methods/${m.id}/toggle`, {}, {
    preserveScroll: true,
  });
};

const openDeleteModal = (m) => {
  methodToDelete.value = m;
};

const confirmDelete = () => {
  if (!methodToDelete.value) return;
  router.delete(`/secret-panel/payment-methods/${methodToDelete.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      methodToDelete.value = null;
    },
    onError: () => {
      methodToDelete.value = null;
    },
  });
};

const getTypeBadge = (type) => {
  switch (type) {
    case 'mobile_banking':
      return 'bg-pink-500/15 text-pink-300 border border-pink-500/25';
    case 'recharge':
      return 'bg-cyan-500/15 text-cyan-300 border border-cyan-500/25';
    case 'crypto':
      return 'bg-amber-500/15 text-amber-300 border border-amber-500/25';
    case 'bank':
      return 'bg-indigo-500/15 text-indigo-300 border border-indigo-500/25';
    default:
      return 'bg-slate-700/50 text-slate-300 border border-slate-600';
  }
};

const formatType = (type) => {
  return type.replace('_', ' ');
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}
</style>
