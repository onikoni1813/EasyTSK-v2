<template>
  <AdminLayout>
    <div class="space-y-6 max-w-4xl mx-auto">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-extrabold text-white">Edit {{ site.name }}</h1>
          <p class="text-xs text-slate-400 mt-1">Manage configuration, domain aliases, and custom key-value settings.</p>
        </div>
        <Link :href="route('admin.sites.index')" class="btn btn-secondary text-xs px-4 py-2 rounded-xl font-bold">
          ← Back to Registry
        </Link>
      </div>

      <!-- Main Edit Form -->
      <form @submit.prevent="submitUpdate" class="glass-card p-6 rounded-3xl border border-slate-700/50 space-y-5">
        <h2 class="text-sm font-bold text-white border-b border-slate-700 pb-2">General Settings</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Site Name *</label>
            <input v-model="form.name" type="text" required class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Slug *</label>
            <input v-model="form.slug" type="text" required class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Site Type *</label>
            <select v-model="form.site_type_id" required class="form-select text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white">
              <option v-for="st in siteTypes" :key="st.id" :value="st.id">{{ st.name }}</option>
            </select>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Status *</label>
            <select v-model="form.status" required class="form-select text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="maintenance">Maintenance</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Primary Domain (FQDN)</label>
            <input v-model="form.primary_domain" type="text" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white font-mono" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Subdomain Prefix</label>
            <input v-model="form.subdomain" type="text" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white font-mono" />
          </div>
        </div>

        <div class="pt-2 flex justify-end">
          <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs px-6 py-2 rounded-xl font-bold">
            Update Site
          </button>
        </div>
      </form>

      <!-- Domain Mapping Manager -->
      <div class="glass-card p-6 rounded-3xl border border-slate-700/50 space-y-4">
        <h2 class="text-sm font-bold text-white border-b border-slate-700 pb-2">Domain Aliases & Routing Mapping</h2>
        
        <form @submit.prevent="addDomain" class="flex gap-3">
          <input v-model="domainForm.domain_name" type="text" placeholder="Add domain alias (e.g. easytools.net)" required class="form-input text-xs flex-1 rounded-xl bg-slate-800/80 border-slate-700 text-white font-mono" />
          <button type="submit" :disabled="domainForm.processing" class="btn btn-secondary text-xs px-4 py-2 rounded-xl font-bold">
            Add Domain Alias
          </button>
        </form>

        <div class="space-y-2 pt-2">
          <div v-for="d in site.domains" :key="d.id" class="flex items-center justify-between p-3 rounded-xl bg-slate-800/50 border border-slate-700/40 text-xs">
            <div class="flex items-center gap-3">
              <span class="font-mono text-emerald-400 font-bold">{{ d.domain_name }}</span>
              <span v-if="d.is_primary" class="badge badge-emerald text-[10px]">Primary</span>
              <span v-else class="badge badge-secondary text-[10px]">Alias</span>
            </div>
            <button v-if="!d.is_primary" @click="removeDomain(d)" class="text-rose-400 hover:text-rose-300 font-bold text-[11px]">
              Remove
            </button>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  site: Object,
  siteTypes: Array,
});

const form = useForm({
  site_type_id: props.site.site_type_id,
  name: props.site.name,
  slug: props.site.slug,
  subdomain: props.site.subdomain || '',
  primary_domain: props.site.primary_domain || '',
  status: props.site.status,
  theme: props.site.theme || 'default',
  default_language: props.site.default_language || 'en',
  analytics_id: props.site.analytics_id || '',
  meta_title: props.site.meta_title || '',
  meta_description: props.site.meta_description || '',
});

const domainForm = useForm({
  domain_name: '',
  is_primary: false,
});

const submitUpdate = () => {
  form.put(route('admin.sites.update', props.site.id));
};

const addDomain = () => {
  domainForm.post(route('admin.sites.domains.store', props.site.id), {
    preserveScroll: true,
    onSuccess: () => domainForm.reset(),
  });
};

const removeDomain = (domain) => {
  if (!confirm(`Remove domain ${domain.domain_name}?`)) return;
  router.delete(route('admin.sites.domains.destroy', domain.id), { preserveScroll: true });
};
</script>
