<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-white">👥 User Management</h1>
          <p class="text-xs text-slate-400">Search, view, and edit user details</p>
        </div>

        <div class="flex items-center space-x-2">
          <input 
            v-model="searchQuery" 
            @keyup.enter="search"
            type="text" 
            placeholder="Search name, phone, email..." 
            class="px-4 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 w-64"
          >
          <button @click="search" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-xs">
            Search
          </button>
          <button v-if="filters.search" @click="clearSearch" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white font-bold rounded-xl text-xs">
            Clear
          </button>
        </div>
      </div>

      <div class="glass-card p-4 sm:p-5 rounded-3xl border border-slate-800">
        <!-- Desktop Table View -->
        <div class="hidden sm:block overflow-x-auto">
          <table class="w-full text-xs text-left text-slate-300">
            <thead class="text-[11px] uppercase bg-slate-900 text-slate-400">
              <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">User Info</th>
                <th class="px-4 py-3">Balances</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3 text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in users.data" :key="user.id" @click="openEditModal(user)" class="border-b border-slate-800/60 hover:bg-slate-800/30 cursor-pointer">
                <td class="px-4 py-3 whitespace-nowrap text-slate-500 font-mono">#{{ user.id }}</td>
                <td class="px-4 py-3 min-w-[150px]">
                  <div class="font-bold text-white">{{ user.name }}</div>
                  <div class="text-slate-400">{{ user.phone }}</div>
                  <div class="text-slate-500 text-[10px]">{{ user.email }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="text-emerald-400 font-bold">M: ৳{{ user.main_balance }}</div>
                  <div class="text-amber-400">P: ৳{{ user.pending_balance }}</div>
                </td>
                <td class="px-4 py-3">
                  <div class="flex flex-col gap-1">
                    <span :class="user.role === 'admin' ? 'text-indigo-400 font-bold' : 'text-slate-400'">{{ user.role }}</span>
                    <span v-if="user.is_banned" class="px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded text-[10px] uppercase font-bold w-max">Banned</span>
                    <span v-else class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] uppercase font-bold w-max">Active</span>
                  </div>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end space-x-2">
                    <button @click.stop="openHistoryModal(user)" class="px-3 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-400 font-bold rounded-lg text-[11px] transition-all flex items-center space-x-1">
                      <span>📜</span>
                      <span>History</span>
                    </button>
                    <button @click.stop="openEditModal(user)" class="px-3 py-1.5 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 font-bold rounded-lg text-[11px] transition-all">
                      Edit
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="users.data.length === 0">
                <td colspan="5" class="px-4 py-8 text-center text-slate-500">No users found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile Card View -->
        <div class="sm:hidden space-y-3">
          <div 
            v-for="user in users.data" 
            :key="'mobile-' + user.id" 
            class="bg-slate-900/90 border border-slate-800 rounded-2xl p-4 space-y-3"
          >
            <div class="flex justify-between items-start">
              <div>
                <div class="flex items-center space-x-2">
                  <span class="font-bold text-white text-sm">{{ user.name }}</span>
                  <span class="text-[10px] font-mono text-slate-500">#{{ user.id }}</span>
                </div>
                <div class="text-xs text-slate-400 mt-0.5">{{ user.phone }}</div>
                <div class="text-[11px] text-slate-500">{{ user.email || 'No Email' }}</div>
              </div>
              <div class="flex flex-col items-end gap-1">
                <span :class="user.role === 'admin' ? 'text-indigo-400 font-bold text-xs' : 'text-slate-400 text-xs'">{{ user.role }}</span>
                <span v-if="user.is_banned" class="px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded text-[10px] uppercase font-bold">Banned</span>
                <span v-else class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded text-[10px] uppercase font-bold">Active</span>
              </div>
            </div>

            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-800/80">
              <div class="flex items-center space-x-3">
                <span class="text-emerald-400 font-bold">Main: ৳{{ user.main_balance }}</span>
                <span class="text-amber-400 font-bold">Pending: ৳{{ user.pending_balance }}</span>
              </div>
            </div>

            <div class="flex items-center space-x-2 pt-2 border-t border-slate-800/80">
              <button 
                @click.stop="openHistoryModal(user)" 
                class="flex-1 py-2 bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/30 text-amber-400 font-bold rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all"
              >
                <span>📜</span>
                <span>History</span>
              </button>
              <button 
                @click.stop="openEditModal(user)" 
                class="flex-1 py-2 bg-indigo-500/10 hover:bg-indigo-500/20 border border-indigo-500/30 text-indigo-400 font-bold rounded-xl text-xs flex items-center justify-center space-x-1.5 transition-all"
              >
                <span>✏️</span>
                <span>Edit User</span>
              </button>
            </div>
          </div>

          <div v-if="users.data.length === 0" class="py-8 text-center text-slate-500 text-xs">
            No users found.
          </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-between items-center text-xs text-slate-400">
          <div>Showing {{ users.from || 0 }} to {{ users.to || 0 }} of {{ users.total }} users</div>
          <div class="flex space-x-2">
            <Link v-if="users.prev_page_url" :href="users.prev_page_url" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg">Prev</Link>
            <span v-else class="px-3 py-1.5 bg-slate-900 rounded-lg opacity-50 cursor-not-allowed">Prev</span>
            
            <Link v-if="users.next_page_url" :href="users.next_page_url" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded-lg">Next</Link>
            <span v-else class="px-3 py-1.5 bg-slate-900 rounded-lg opacity-50 cursor-not-allowed">Next</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit User Modal -->
    <Transition name="modal">
      <div v-if="editingUser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-slate-700/50 rounded-3xl p-6 w-full max-w-lg shadow-[0_0_50px_-12px_rgba(79,70,229,0.3)] max-h-[90vh] overflow-y-auto transform transition-all">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-extrabold text-white">Edit User: <span class="text-indigo-400">{{ form.name }}</span></h2>
            <button @click="editingUser = null" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">✕</button>
          </div>
          
          <form @submit.prevent="updateUser" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Name</label>
              <input v-model="form.name" type="text" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Phone</label>
              <input v-model="form.phone" type="text" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-400 mb-1">Email</label>
            <input v-model="form.email" type="email" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Main Balance (Final)</label>
              <input v-model="form.main_balance" type="number" step="0.01" min="0" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
              
              <div class="flex items-center space-x-2 mt-2">
                <input v-model="quickAmount" type="number" step="0.01" min="0" placeholder="Amount" class="w-full px-2 py-1 bg-slate-900 border border-slate-700 rounded text-xs text-white">
                <button type="button" @click="form.main_balance = Math.max(0, parseFloat(form.main_balance || 0) + parseFloat(quickAmount || 0)); quickAmount = ''" class="px-3 py-1 bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-400 font-bold rounded border border-emerald-500/30 text-[10px] transition-colors">Add</button>
                <button type="button" @click="form.main_balance = Math.max(0, parseFloat(form.main_balance || 0) - parseFloat(quickAmount || 0)); quickAmount = ''" class="px-3 py-1 bg-rose-500/20 hover:bg-rose-500/30 text-rose-400 font-bold rounded border border-rose-500/30 text-[10px] transition-colors">Deduct</button>
              </div>
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Pending Balance</label>
              <input v-model="form.pending_balance" type="number" step="0.01" min="0" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Risk Score (0-100)</label>
              <input v-model="form.risk_score" type="number" step="0.01" min="0" max="100" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-1">Health (0-100)</label>
              <input v-model="form.health" type="number" min="0" max="100" required class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-800">
            <div>
              <label class="block text-xs font-semibold text-slate-400 mb-2">Role</label>
              <select v-model="form.role" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-lg text-sm text-white focus:outline-none focus:border-indigo-500">
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div class="flex items-center mt-6">
              <label class="flex items-center cursor-pointer">
                <input v-model="form.is_banned" type="checkbox" class="sr-only peer">
                <div class="w-11 h-6 bg-slate-700 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-rose-600 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all relative"></div>
                <span class="ml-3 text-sm font-bold" :class="form.is_banned ? 'text-rose-500' : 'text-slate-300'">Banned Account</span>
              </label>
            </div>
          </div>

          <div class="flex justify-end space-x-3 pt-6">
            <button type="button" @click="editingUser = null" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-sm transition-colors">
              Cancel
            </button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 text-white font-bold rounded-xl text-sm transition-colors shadow-lg shadow-indigo-500/30">
              {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>
    </Transition>

    <!-- User History Modal -->
    <UserHistoryModal 
      :show="showHistoryModal" 
      :user="historyUser" 
      @close="showHistoryModal = false" 
    />

  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import UserHistoryModal from '@/Components/UserHistoryModal.vue';

const props = defineProps({
  users: Object,
  filters: Object,
});

const searchQuery = ref(props.filters.search || '');
const editingUser = ref(null);
const quickAmount = ref('');

const showHistoryModal = ref(false);
const historyUser = ref(null);

const openHistoryModal = (user) => {
  historyUser.value = user;
  showHistoryModal.value = true;
};

const form = useForm({
  name: '',
  phone: '',
  email: '',
  main_balance: 0,
  pending_balance: 0,
  role: 'user',
  is_banned: false,
  risk_score: 0,
  health: 100,
});

const search = () => {
  router.get('/admin/users', { search: searchQuery.value }, { preserveState: true });
};

const clearSearch = () => {
  searchQuery.value = '';
  router.get('/admin/users');
};

const openEditModal = (user) => {
  editingUser.value = user;
  form.name = user.name;
  form.phone = user.phone;
  form.email = user.email || '';
  form.main_balance = user.main_balance;
  form.pending_balance = user.pending_balance;
  form.role = user.role;
  form.is_banned = user.is_banned ? true : false;
  form.risk_score = user.risk_score;
  form.health = user.health;
};

const updateUser = () => {
  form.put(`/admin/users/${editingUser.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      editingUser.value = null;
    },
  });
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active > div, .modal-leave-active > div {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from > div {
  transform: scale(0.95) translateY(10px);
}
.modal-leave-to > div {
  transform: scale(0.95) translateY(10px);
}
</style>
