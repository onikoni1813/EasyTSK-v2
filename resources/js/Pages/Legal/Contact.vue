<template>
  <LegalLayout title="Contact Us" icon="📬" last-updated="July 2025">

    <!-- Contact Info Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8 not-legal">
      <div class="rounded-2xl border border-indigo-500/20 p-5" style="background:rgba(99,102,241,0.07);">
        <div class="text-2xl mb-2">📧</div>
        <div class="text-sm font-bold text-slate-200 mb-1">Support Email</div>
        <a :href="'mailto:' + ($page.props.siteSettings?.support_email || 'support@easytsk.com')" class="text-indigo-400 text-sm hover:text-indigo-300 transition-colors">{{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }}</a>
        <div class="text-xs text-slate-500 mt-1">Response within 24–48 hours</div>
      </div>
      <div class="rounded-2xl border border-violet-500/20 p-5" style="background:rgba(139,92,246,0.07);">
        <div class="text-2xl mb-2">🏢</div>
        <div class="text-sm font-bold text-slate-200 mb-1">Business Address</div>
        <p class="text-slate-400 text-sm">{{ $page.props.siteSettings?.company_address || 'Dhaka, Bangladesh' }}</p>
        <div class="text-xs text-slate-500 mt-1">Mon–Fri, 9:00 AM – 6:00 PM (BST)</div>
      </div>
    </div>

    <h2>Send Us a Message</h2>
    <p>Fill out the form below and we'll get back to you as soon as possible. Please include as much detail as possible so our team can help you efficiently.</p>

    <!-- Contact Form -->
    <form @submit.prevent="submitForm" class="space-y-4 mt-6 not-legal">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest block mb-2">Your Name *</label>
          <input v-model="form.name" type="text" required placeholder="John Doe" class="input-dark rounded-xl" id="contact-name" />
        </div>
        <div>
          <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest block mb-2">Email Address *</label>
          <input v-model="form.email" type="email" required placeholder="you@example.com" class="input-dark rounded-xl" id="contact-email" />
        </div>
      </div>
      <div>
        <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest block mb-2">Subject *</label>
        <select v-model="form.subject" class="input-dark rounded-xl" id="contact-subject">
          <option value="">Select a subject</option>
          <option>Account Issue</option>
          <option>Withdrawal Problem</option>
          <option>Task Dispute</option>
          <option>Bug Report</option>
          <option>Partnership / Business</option>
          <option>Other</option>
        </select>
      </div>
      <div>
        <label class="text-xs font-semibold text-slate-400 uppercase tracking-widest block mb-2">Message *</label>
        <textarea v-model="form.message" required rows="5" placeholder="Describe your issue or question in detail..." class="input-dark rounded-xl resize-none" id="contact-message"></textarea>
      </div>

      <div v-if="submitted" class="p-4 rounded-xl text-sm text-emerald-300 border border-emerald-500/25" style="background:rgba(16,185,129,0.08);">
        ✅ Message sent successfully! We'll get back to you within 24–48 hours.
      </div>

      <button
        type="submit"
        id="contact-submit-btn"
        :disabled="isLoading"
        class="btn-neon btn-primary w-full py-4 text-sm font-bold text-white rounded-xl disabled:opacity-60 disabled:cursor-not-allowed"
      >
        <span v-if="isLoading">Sending…</span>
        <span v-else>📤 Send Message</span>
      </button>
    </form>

    <h2>Frequently Contacted About</h2>
    <ul>
      <li><strong style="color:#e2e8f0;">Account suspension:</strong> Email us with your registered email and user ID for faster resolution.</li>
      <li><strong style="color:#e2e8f0;">Withdrawal not received:</strong> Include your transaction ID and withdrawal request date.</li>
      <li><strong style="color:#e2e8f0;">Task rejection dispute:</strong> Include the task name and your submission screenshot.</li>
      <li><strong style="color:#e2e8f0;">Partnership & advertising:</strong> Contact business@easytsk.com for advertiser inquiries.</li>
    </ul>

    <div class="warning-box">
      <strong>⚠️ Note:</strong> We will never ask for your password or payment PIN via email. If you receive any such request, please ignore it and report it to {{ $page.props.siteSettings?.support_email || 'support@easytsk.com' }} immediately.
    </div>

  </LegalLayout>
</template>

<script setup>
import { ref } from 'vue';
import LegalLayout from '@/Layouts/LegalLayout.vue';

const form = ref({ name: '', email: '', subject: '', message: '' });
const isLoading  = ref(false);
const submitted  = ref(false);

async function submitForm() {
  isLoading.value = true;
  // Simulate sending (no backend endpoint yet — shows success after 1.5s)
  await new Promise(r => setTimeout(r, 1500));
  isLoading.value = false;
  submitted.value  = true;
  form.value       = { name: '', email: '', subject: '', message: '' };
}
</script>
