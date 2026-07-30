<template>
  <GuestLayout>
    <Head :title="title" />

    <div class="max-w-4xl mx-auto px-4 sm:px-6 py-16 sm:py-24">

      <!-- Page Header -->
      <div class="text-center mb-12">
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass-card border border-white/10 mb-6">
          <span class="text-lg">{{ icon }}</span>
          <span class="text-xs font-bold text-slate-400 uppercase tracking-widest">Legal & Compliance</span>
        </div>
        <h1 class="text-4xl sm:text-5xl font-black text-white mb-4">{{ title }}</h1>
        <p class="text-slate-500 text-sm">Last updated: {{ lastUpdated }}</p>
        <div class="h-px bg-gradient-to-r from-transparent via-indigo-500/30 to-transparent mt-8"></div>
      </div>

      <!-- Content -->
      <article class="legal-content glass-card rounded-3xl p-8 sm:p-12 border border-white/5">
        <slot />
      </article>

      <!-- Back link -->
      <div class="text-center mt-10">
        <a href="/" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-indigo-400 transition-colors duration-200">
          ← Back to Homepage
        </a>
      </div>
    </div>
  </GuestLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';

defineProps({
  title:       { type: String, required: true },
  icon:        { type: String, default: '📄' },
  lastUpdated: { type: String, default: 'July 2025' },
});
</script>

<style>
/* Legal content typography */
.legal-content h2 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #e2e8f0;
  margin-top: 2.5rem;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(99,102,241,0.15);
}
.legal-content h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #a5b4fc;
  margin-top: 1.5rem;
  margin-bottom: 0.5rem;
}
.legal-content p {
  color: #94a3b8;
  line-height: 1.8;
  margin-bottom: 1rem;
  font-size: 0.9rem;
}
.legal-content ul {
  list-style: none;
  padding: 0;
  margin-bottom: 1rem;
}
.legal-content ul li {
  color: #94a3b8;
  font-size: 0.9rem;
  line-height: 1.7;
  padding: 0.25rem 0 0.25rem 1.5rem;
  position: relative;
}
.legal-content ul li::before {
  content: '›';
  position: absolute;
  left: 0;
  color: #6366f1;
  font-weight: 700;
}
.legal-content a {
  color: #818cf8;
  text-decoration: underline;
  text-underline-offset: 3px;
}
.legal-content .highlight-box {
  background: rgba(99,102,241,0.08);
  border: 1px solid rgba(99,102,241,0.2);
  border-radius: 12px;
  padding: 16px 20px;
  margin: 1.5rem 0;
  font-size: 0.875rem;
  color: #a5b4fc;
  line-height: 1.7;
}
.legal-content .warning-box {
  background: rgba(244,63,94,0.08);
  border: 1px solid rgba(244,63,94,0.2);
  border-radius: 12px;
  padding: 16px 20px;
  margin: 1.5rem 0;
  font-size: 0.875rem;
  color: #fda4af;
  line-height: 1.7;
}
</style>
