<template>
  <AdminLayout>
    <div class="space-y-6 max-w-3xl mx-auto">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-extrabold text-white">Register External Site</h1>
          <p class="text-xs text-slate-400 mt-1">Configure site details, domain bindings, and default theme for domain routing.</p>
        </div>
        <Link :href="route('admin.sites.index')" class="btn btn-secondary text-xs px-4 py-2 rounded-xl font-bold">
          ← Back to Registry
        </Link>
      </div>

      <form @submit.prevent="submit" class="glass-card p-6 rounded-3xl border border-slate-700/50 space-y-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Site Name *</label>
            <input v-model="form.name" type="text" placeholder="e.g. EasyTSK Image Tools" required class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
            <div v-if="form.errors.name" class="text-rose-400 text-[10px] mt-1">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Slug *</label>
            <input v-model="form.slug" type="text" placeholder="e.g. tools-site" required class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
            <div v-if="form.errors.slug" class="text-rose-400 text-[10px] mt-1">{{ form.errors.slug }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Site Type *</label>
            <select v-model="form.site_type_id" required class="form-select text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white">
              <option value="" disabled>Select Site Type</option>
              <option v-for="st in siteTypes" :key="st.id" :value="st.id">{{ st.name }}</option>
            </select>
            <div v-if="form.errors.site_type_id" class="text-rose-400 text-[10px] mt-1">{{ form.errors.site_type_id }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Initial Status *</label>
            <select v-model="form.status" required class="form-select text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="maintenance">Maintenance</option>
            </select>
            <div v-if="form.errors.status" class="text-rose-400 text-[10px] mt-1">{{ form.errors.status }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Primary Domain (Full FQDN)</label>
            <input v-model="form.primary_domain" type="text" placeholder="e.g. tools.easytsk.com" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white font-mono" />
            <div v-if="form.errors.primary_domain" class="text-rose-400 text-[10px] mt-1">{{ form.errors.primary_domain }}</div>
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Subdomain Prefix</label>
            <input v-model="form.subdomain" type="text" placeholder="e.g. tools" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white font-mono" />
            <div v-if="form.errors.subdomain" class="text-rose-400 text-[10px] mt-1">{{ form.errors.subdomain }}</div>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Theme</label>
            <input v-model="form.theme" type="text" placeholder="default" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
          </div>

          <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1">Analytics ID (GA4 / Tag Manager)</label>
            <input v-model="form.analytics_id" type="text" placeholder="G-XXXXXXXXXX" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Meta Title (SEO Defaults)</label>
          <input v-model="form.meta_title" type="text" placeholder="Free Online Image & Text Tools | EasyTSK" class="form-input text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white" />
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-300 mb-1">Meta Description</label>
          <textarea v-model="form.meta_description" rows="3" placeholder="Free browser-based tools for image compression, format conversion, and text processing." class="form-textarea text-xs w-full rounded-xl bg-slate-800/80 border-slate-700 text-white"></textarea>
        </div>

        <div class="pt-3 flex justify-end">
          <button type="submit" :disabled="form.processing" class="btn btn-primary text-xs px-6 py-2.5 rounded-xl font-bold">
            {{ form.processing ? 'Registering...' : 'Register Site' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
  siteTypes: Array,
});

const form = useForm({
  site_type_id: '',
  name: '',
  slug: '',
  subdomain: '',
  primary_domain: '',
  status: 'active',
  theme: 'default',
  default_language: 'en',
  analytics_id: '',
  meta_title: '',
  meta_description: '',
});

const submit = () => {
  form.post(route('admin.sites.store'));
};
</script>
