<template>
  <AdminLayout>
    <div class="space-y-6 max-w-4xl mx-auto">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-extrabold text-white">Site Types Catalog</h1>
          <p class="text-xs text-slate-400 mt-1">Manage dynamic categories of external properties (Tools, Guides, Education, etc.).</p>
        </div>
        <Link :href="route('admin.sites.index')" class="btn btn-secondary text-xs px-4 py-2 rounded-xl font-bold">
          ← Back to Site Registry
        </Link>
      </div>

      <!-- Add New Site Type Form -->
      <form @submit.prevent="submit" class="glass-card p-6 rounded-3xl border border-slate-700/50 space-y-4">
        <h2 class="text-sm font-bold text-white border-b border-slate-700 pb-2">Add New Site Type</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Name *</label>
            <input v-model="form.name" type="text" placeholder="e.g. Utilities" required class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Icon (Emoji or class)</label>
            <input v-model="form.icon" type="text" placeholder="🛠️" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Description</label>
            <input v-model="form.description" type="text" placeholder="General utility tools" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
          </div>
        </div>
        <div class="flex justify-end">
          <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs px-5 py-2 rounded-xl font-bold">
            Create Site Type
          </button>
        </div>
      </form>

      <!-- Site Types List -->
      <div class="glass-card p-6 rounded-3xl border border-slate-700/50">
        <h2 class="text-sm font-bold text-white mb-4">Existing Site Types</h2>
        <div class="space-y-3">
          <div v-for="st in siteTypes" :key="st.id" class="flex items-center justify-between p-4 rounded-2xl bg-slate-800/40 border border-slate-700/40">
            <div class="flex items-center gap-3">
              <span class="text-2xl">{{ st.icon || '🌐' }}</span>
              <div>
                <div class="font-bold text-white text-sm">{{ st.name }} <span class="text-xs text-slate-400 font-mono">({{ st.slug }})</span></div>
                <div class="text-xs text-slate-400">{{ st.description || 'No description provided.' }}</div>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <span class="badge badge-indigo text-xs">{{ st.sites_count }} site(s)</span>
              <button @click="destroy(st)" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors">
                Delete
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
  siteTypes: Array,
});

const form = useForm({
  name: '',
  icon: '',
  description: '',
});

const submit = () => {
  form.post(route('admin.site-types.store'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
  });
};

const destroy = (siteType) => {
  if (siteType.sites_count > 0) {
    alert('Cannot delete a site type that has associated sites.');
    return;
  }
  if (!confirm(`Delete site type ${siteType.name}?`)) return;
  router.delete(route('admin.site-types.destroy', siteType.id), { preserveScroll: true });
};
</script>
