<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h1 class="text-2xl font-extrabold text-white">🧩 Task Manager</h1>
          <p class="text-xs text-slate-400">Create, edit, and manage all earning tasks</p>
        </div>
        <button @click="openCreate" class="btn-neon btn-primary px-4 py-2.5 rounded-xl text-xs font-bold text-white">
          ➕ New Task
        </button>
      </div>

      <!-- Tasks Table -->
      <div class="glass-card p-5 rounded-3xl border border-slate-800">
        <div class="overflow-x-auto">
          <table class="w-full text-xs text-left text-slate-300">
            <thead class="text-[11px] uppercase bg-slate-900 text-slate-400">
              <tr>
                <th class="px-4 py-3">Title</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3">Reward</th>
                <th class="px-4 py-3">Cooldown</th>
                <th class="px-4 py-3">Submissions</th>
                <th class="px-4 py-3">Status</th>
                <th class="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="t in tasks" :key="t.id" class="border-b border-slate-800/60">
                <td class="px-4 py-3">
                  <div class="font-semibold text-white">{{ t.title }}</div>
                  <div class="text-[10px] text-slate-500">{{ t.created_at }}</div>
                </td>
                <td class="px-4 py-3"><span class="badge" :class="typeBadge(t.type)">{{ t.type }}</span></td>
                <td class="px-4 py-3">
                  <div class="font-bold text-emerald-400">{{ t.reward_coins }} pts</div>
                  <div class="text-[10px] text-violet-400">+{{ t.reward_xp }} XP</div>
                </td>
                <td class="px-4 py-3">{{ t.cooldown_hours === 0 ? 'One-time' : t.cooldown_hours + ' hrs' }}</td>
                <td class="px-4 py-3 font-bold text-indigo-400">{{ t.submissions_count }}</td>
                <td class="px-4 py-3">
                  <button @click="toggleStatus(t)" class="badge cursor-pointer" :class="t.status === 'active' ? 'badge-active' : 'badge-rejected'">
                    {{ t.status === 'active' ? '🟢 Active' : '🔴 Inactive' }}
                  </button>
                </td>
                <td class="px-4 py-3 space-x-2 whitespace-nowrap">
                  <button @click="openEdit(t)" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-[11px]">Edit</button>
                  <button @click="destroy(t)" class="px-3 py-1 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-lg text-[11px]">Delete</button>
                </td>
              </tr>
              <tr v-if="tasks.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-slate-500">No tasks created yet.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Create / Edit Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showForm" class="fixed inset-0 z-50 p-4 overflow-y-auto" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
          <div class="flex min-h-full items-center justify-center py-10">
            <div class="glass-card max-w-lg w-full p-6 rounded-3xl border border-indigo-500/30 animate-slide-in-up relative">
            <h3 class="text-base font-black text-white mb-4">{{ editingId ? '✏️ Edit Task' : '➕ New Task' }}</h3>

            <form @submit.prevent="submit" class="space-y-3">
              <div>
                <label class="text-xs font-semibold text-slate-400 mb-1 block">Title</label>
                <input v-model="form.title" type="text" class="input-dark" required maxlength="255" />
                <div v-if="form.errors.title" class="text-[10px] text-rose-500 mt-1">{{ form.errors.title }}</div>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-400 mb-1 block">Description</label>
                <textarea v-model="form.description" class="input-dark resize-none" rows="2"></textarea>
                <div v-if="form.errors.description" class="text-[10px] text-rose-500 mt-1">{{ form.errors.description }}</div>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-400 mb-1 block">Task Image <span class="text-slate-600">(Optional)</span></label>
                <div v-if="editingId && form.current_image_url && !form.remove_image" class="mb-2 flex items-start gap-3 p-2 rounded-xl border border-white/10 bg-white/5">
                  <img :src="form.current_image_url" class="h-12 w-12 object-cover rounded-lg" alt="Task Image" />
                  <div class="flex-1">
                    <p class="text-xs text-slate-300">Current Image</p>
                    <button type="button" @click="form.remove_image = true" class="text-[10px] text-rose-400 font-bold hover:underline">Remove</button>
                  </div>
                </div>
                <input type="file" @change="e => form.image = e.target.files[0]" accept="image/*" class="input-dark text-xs p-1.5" />
                <div v-if="form.errors.image" class="text-[10px] text-rose-500 mt-1">{{ form.errors.image }}</div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Type</label>
                  <select v-model="form.type" class="input-dark">
                    <option value="shortlink">Shortlink</option>
                    <option value="secret_code">Secret Code</option>
                    <option value="social">Social Proof</option>
                    <option value="user_ad">User Ad</option>
                  </select>
                </div>
                <div v-if="['shortlink', 'user_ad'].includes(form.type)">
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Provider Name</label>
                  <input v-model="form.provider_name" type="text" class="input-dark" placeholder="e.g. ShrinkMe" />
                  <div v-if="form.errors.provider_name" class="text-[10px] text-rose-500 mt-1">{{ form.errors.provider_name }}</div>
                </div>
              </div>

              <div>
                <label class="text-xs font-semibold text-slate-400 mb-1 block">Target URL</label>
                <input v-model="form.target_url" type="url" class="input-dark" placeholder="https://..." required />
                <div v-if="form.errors.target_url" class="text-[10px] text-rose-500 mt-1">{{ form.errors.target_url }}</div>
              </div>

              <div v-if="form.type === 'secret_code'">
                <label class="text-xs font-semibold text-slate-400 mb-1 block">Secret Code(s) <span class="text-slate-600">(comma-separated if multiple)</span></label>
                <input v-model="form.secret_code" type="text" class="input-dark font-mono" placeholder="ABC123, XYZ789" />
                <div v-if="form.errors.secret_code" class="text-[10px] text-rose-500 mt-1">{{ form.errors.secret_code }}</div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Reward Coins</label>
                  <input v-model.number="form.reward_coins" type="number" min="1" step="0.01" class="input-dark" required />
                  <div v-if="form.errors.reward_coins" class="text-[10px] text-rose-500 mt-1">{{ form.errors.reward_coins }}</div>
                </div>
                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Reward XP</label>
                  <input v-model.number="form.reward_xp" type="number" min="1" class="input-dark" required />
                  <div v-if="form.errors.reward_xp" class="text-[10px] text-rose-500 mt-1">{{ form.errors.reward_xp }}</div>
                </div>
                <div>
                  <label class="text-xs font-semibold text-slate-400 mb-1 block">Cooldown (Hours) <span class="text-slate-600">(0=One-time)</span></label>
                  <input v-model.number="form.cooldown_hours" type="number" min="0" class="input-dark" required />
                  <div v-if="form.errors.cooldown_hours" class="text-[10px] text-rose-500 mt-1">{{ form.errors.cooldown_hours }}</div>
                </div>
              </div>

              <!-- Dynamic Proof Requirements Builder -->
              <div class="border-t border-white/5 pt-3">
                <div class="flex items-center justify-between mb-2">
                  <label class="text-xs font-semibold text-slate-400">Proof Requirements</label>
                  <button type="button" @click="addProofRequirement" class="text-[11px] text-indigo-400 hover:text-indigo-300 font-bold">+ Add Field</button>
                </div>
                <div v-for="(req, idx) in form.proof_requirements" :key="req.id" class="glass-pill p-4 rounded-xl border border-white/10 mb-3 relative">
                  <button type="button" @click="removeProofRequirement(idx)" class="absolute top-2 right-2 text-rose-400 hover:bg-rose-500/20 p-1 rounded-md transition-colors" title="Remove Field">✕</button>
                  
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1 pr-6">
                    <div>
                      <label class="text-[10px] uppercase tracking-wider font-bold text-slate-500 mb-1 block">Input Type</label>
                      <select v-model="req.type" class="input-dark text-xs py-2 w-full">
                        <option value="text">📝 Text Input</option>
                        <option value="image">📸 Image Upload</option>
                      </select>
                    </div>
                    
                    <div>
                      <label class="text-[10px] uppercase tracking-wider font-bold text-slate-500 mb-1 block">Question / Label shown to user</label>
                      <input v-model="req.label" type="text" class="input-dark text-xs py-2 w-full" :placeholder="req.type === 'text' ? 'e.g. Enter your registered email' : 'e.g. Upload a screenshot of your profile'" required />
                      <div v-if="form.errors[`proof_requirements.${idx}.label`]" class="text-[10px] text-rose-500 w-full mt-1">{{ form.errors[`proof_requirements.${idx}.label`] }}</div>
                    </div>
                  </div>

                  <div class="mt-3 flex items-center gap-2 border-t border-white/5 pt-3">
                    <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                      <input v-model="req.is_required" type="checkbox" class="accent-indigo-500 w-4 h-4 rounded" /> 
                      <span class="font-semibold">Mandatory</span> — <span class="text-slate-500">Users must provide this to complete the task</span>
                    </label>
                  </div>
                </div>
                <p v-if="form.proof_requirements.length === 0" class="text-[11px] text-slate-600">No custom proof fields — legacy screenshot/text submission will be used.</p>
              </div>

              <div class="flex gap-2 pt-2">
                <button type="button" @click="closeForm" class="flex-1 py-2.5 glass-pill text-xs text-slate-400 rounded-xl border border-white/8">Cancel</button>
                <button type="submit" :disabled="form.processing" class="flex-1 btn-neon btn-primary py-2.5 text-xs font-bold text-white rounded-xl">
                  {{ form.processing ? 'Saving...' : (editingId ? 'Update Task' : 'Create Task') }}
                </button>
              </div>
            </form>
          </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </AdminLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  tasks: Array,
});

const showForm  = ref(false);
const editingId = ref(null);

const emptyForm = () => ({
  title: '',
  description: '',
  type: 'shortlink',
  provider_name: '',
  target_url: '',
  secret_code: '',
  reward_coins: 10,
  reward_xp: 10,
  cooldown_hours: 0,
  image: null,
  remove_image: false,
  current_image_url: null,
  proof_requirements: [],
});

const form = useForm(emptyForm());

const typeBadge = (type) => ({
  shortlink: 'badge-cyan',
  secret_code: 'badge-amber',
  social: 'badge-emerald',
  user_ad: 'badge-violet',
})[type] || 'badge-indigo';

const openCreate = () => {
  editingId.value = null;
  form.defaults(emptyForm()).reset();
  form.clearErrors();
  showForm.value = true;
};

const openEdit = (task) => {
  editingId.value = task.id;
  form.defaults({
    title: task.title,
    description: task.description || '',
    type: task.type,
    provider_name: task.provider_name || '',
    target_url: task.target_url || '',
    secret_code: task.secret_code || '',
    reward_coins: task.reward_coins,
    reward_xp: task.reward_xp,
    cooldown_hours: task.cooldown_hours,
    image: null,
    remove_image: false,
    current_image_url: task.image_url,
    proof_requirements: (task.proof_requirements || []).map(r => ({ ...r })),
  }).reset();
  form.clearErrors();
  showForm.value = true;
};

const closeForm = () => {
  showForm.value = false;
};

const addProofRequirement = () => {
  form.proof_requirements.push({
    id: 'req_' + Date.now(),
    type: 'text',
    label: '',
    is_required: true,
  });
};

const removeProofRequirement = (idx) => {
  form.proof_requirements.splice(idx, 1);
};

const submit = () => {
  if (editingId.value) {
    form.transform((data) => ({
      ...data,
      _method: 'put',
    })).post(`/admin/tasks/${editingId.value}`, {
      preserveScroll: true,
      onSuccess: () => closeForm(),
    });
  } else {
    form.transform((data) => data).post('/admin/tasks', {
      preserveScroll: true,
      onSuccess: () => closeForm(),
    });
  }
};

const toggleStatus = (task) => {
  router.post(`/admin/tasks/${task.id}/toggle`, {}, { preserveScroll: true });
};

const destroy = (task) => {
  if (!confirm(`আপনি কি নিশ্চিত? টাস্কটি ডিলিট করলে এর সাথে থাকা সকল ডেটা এবং স্ক্রিনশট চিরতরে মুছে যাবে।`)) return;
  router.delete(`/admin/tasks/${task.id}`, { preserveScroll: true });
};
</script>

<style scoped>
.modal-enter-active { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.modal-leave-active { transition: all 0.2s ease-in; }
.modal-enter-from   { transform: scale(0.85); opacity: 0; }
.modal-leave-to     { transform: scale(1.05); opacity: 0; }
</style>
   
 