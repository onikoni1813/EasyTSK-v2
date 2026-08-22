<template>
  <AdminLayout>
    <div class="space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl font-extrabold text-white">External Site Registry</h1>
          <p class="text-xs text-slate-400 mt-1">Manage multi-site domain routing, themes, and status controls for external AD-SITE properties.</p>
        </div>
        <div class="flex items-center gap-3">
          <Link :href="route('admin.site-types.index')" class="btn btn-secondary text-xs px-4 py-2.5 rounded-xl font-bold flex items-center gap-2">
            <span>⚙️ Site Types</span>
          </Link>
          <Link :href="route('admin.sites.create')" class="btn btn-primary text-xs px-4 py-2.5 rounded-xl font-bold flex items-center gap-2">
            <span>➕ Register Site</span>
          </Link>
        </div>
      </div>

      <!-- Sites Grid / Table -->
      <div class="glass-card p-6 rounded-3xl border border-slate-700/50">
        <div v-if="!sites.data.length" class="text-center py-12">
          <div class="text-4xl mb-3">🌐</div>
          <h3 class="text-lg font-bold text-white mb-1">No External Sites Registered</h3>
          <p class="text-xs text-slate-400 max-w-md mx-auto mb-6">Register your first external website (e.g. tools.easytsk.com) to begin multi-site ad monetization.</p>
          <Link :href="route('admin.sites.create')" class="btn btn-primary text-xs px-5 py-2.5 rounded-xl font-bold">
            Register First Site
          </Link>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead>
              <tr class="border-b border-slate-700/50 text-slate-400 font-semibold uppercase tracking-wider">
                <th class="pb-3 px-3">Site Details</th>
                <th class="pb-3 px-3">Site Type</th>
                <th class="pb-3 px-3">Primary Domain / Subdomain</th>
                <th class="pb-3 px-3">Theme</th>
                <th class="pb-3 px-3">Status</th>
                <th class="pb-3 px-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/50 text-slate-300">
              <tr v-for="site in sites.data" :key="site.id" class="hover:bg-slate-800/30 transition-colors">
                <td class="py-4 px-3">
                  <div class="font-bold text-white text-sm">{{ site.name }}</div>
                  <div class="text-[11px] text-slate-400">{{ site.slug }}</div>
                </td>
                <td class="py-4 px-3">
                  <span class="badge badge-indigo text-[11px]">
                    {{ site.site_type ? site.site_type.name : 'Unassigned' }}
                  </span>
                </td>
                <td class="py-4 px-3 font-mono text-[11px] text-emerald-400">
                  <div>{{ site.primary_domain || site.subdomain + '.easytsk.com' || 'None' }}</div>
                  <div v-if="site.domains_count > 1" class="text-[10px] text-slate-400">+{{ site.domains_count - 1 }} alias domains</div>
                </td>
                <td class="py-4 px-3 capitalize text-slate-300">
                  {{ site.theme }}
                </td>
                <td class="py-4 px-3">
                  <span :class="{
                    'badge badge-emerald': site.status === 'active',
                    'badge badge-rose': site.status === 'inactive',
                    'badge badge-amber': site.status === 'maintenance'
                  }">
                    {{ site.status }}
                  </span>
                </td>
                <td class="py-4 px-3 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="toggleStatus(site)" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 transition-colors">
                      Toggle
                    </button>
                    <Link :href="route('admin.sites.edit', site.id)" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 transition-colors">
                      Edit
                    </Link>
                    <button @click="destroySite(site)" class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 transition-colors">
                      Delete
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
  sites: Object,
});

const toggleStatus = (site) => {
  router.post(route('admin.sites.toggle', site.id), {}, { preserveScroll: true });
};

const destroySite = (site) => {
  if (!confirm(`Are you sure you want to delete ${site.name} from the registry?`)) return;
  router.delete(route('admin.sites.destroy', site.id), { preserveScroll: true });
};
</script>
