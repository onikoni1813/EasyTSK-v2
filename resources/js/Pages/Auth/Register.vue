<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative" style="background-color: #02040a;">
    <!-- Background glows -->
    <div class="fixed -top-20 -left-20 w-80 h-80 bg-indigo-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed -bottom-20 -right-20 w-96 h-96 bg-violet-600/8 rounded-full blur-3xl pointer-events-none"></div>

    <div class="glass-card max-w-md w-full p-7 rounded-3xl border border-indigo-500/20 shadow-2xl neon-glow-indigo animate-slide-in-up relative overflow-hidden">
      <div class="absolute inset-0 cyber-grid opacity-30 pointer-events-none"></div>

      <div class="relative z-10 space-y-5">
        <!-- Logo + Title -->
        <div class="text-center space-y-2">
          <template v-if="$page.props.siteSettings?.site_logo">
            <img :src="$page.props.siteSettings.site_logo" alt="Site Logo" class="h-12 w-auto max-h-12 mx-auto object-contain drop-shadow-[0_0_15px_rgba(99,102,241,0.5)]" />
          </template>
          <template v-else>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-black text-white text-xl shadow-lg neon-glow-indigo mx-auto">
              E
            </div>
          </template>
          <h1 class="text-xl font-black text-white">Join Easytsk V2</h1>
          <p class="text-xs text-slate-500">Start earning today — free forever</p>
        </div>



        <form @submit.prevent="submit" class="space-y-3.5">
          <!-- Name -->
          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Full Name</label>
            <input v-model="form.name" type="text" required class="input-dark" placeholder="e.g. Rahul Islam" />
            <span v-if="form.errors.name" class="text-xs text-rose-400 mt-1 block">{{ form.errors.name }}</span>
          </div>

          <!-- Phone -->
          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Phone Number</label>
            <input v-model="form.phone" type="tel" required class="input-dark" placeholder="e.g. 01700000000" />
            <span v-if="form.errors.phone" class="text-xs text-rose-400 mt-1 block">{{ form.errors.phone }}</span>
          </div>

          <!-- Email (Optional) -->
          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block flex items-center gap-1.5">
              Email Address
              <span class="badge badge-indigo opacity-60">Optional</span>
            </label>
            <input v-model="form.email" type="email" class="input-dark" placeholder="your@email.com" />
            <span v-if="form.errors.email" class="text-xs text-rose-400 mt-1 block">{{ form.errors.email }}</span>
          </div>

          <!-- Password Row -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Password</label>
              <input v-model="form.password" type="password" required class="input-dark" placeholder="••••••••" />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Confirm</label>
              <input v-model="form.password_confirmation" type="password" required class="input-dark" placeholder="••••••••" />
            </div>
          </div>
          <span v-if="form.errors.password" class="text-xs text-rose-400 block">{{ form.errors.password }}</span>

          <!-- Recovery PIN -->
          <div>
            <label class="text-xs font-semibold text-amber-400 mb-1.5 block flex items-center gap-1.5">
              🔐 4-Digit Recovery PIN
              <span class="badge badge-amber">Required</span>
            </label>
            <input
              v-model="form.recovery_pin"
              type="text"
              maxlength="4"
              pattern="\d{4}"
              required
              class="input-dark border-amber-500/30 font-mono text-amber-300 tracking-[0.4em] text-center text-lg"
              placeholder="• • • •"
            />
            <p class="text-[10px] text-amber-600 mt-1">Remember this PIN — it's your only password recovery method.</p>
            <span v-if="form.errors.recovery_pin" class="text-xs text-rose-400 mt-0.5 block">{{ form.errors.recovery_pin }}</span>
          </div>

          <!-- Referral Code -->
          <div>
            <label class="text-xs font-semibold text-slate-500 mb-1.5 block flex items-center gap-1.5">
              👥 Referral Code
              <span v-if="form.ref_code" class="badge badge-emerald">Auto-filled ✓</span>
              <span v-else class="badge badge-indigo opacity-60">Optional</span>
            </label>
            <input v-model="form.ref_code" type="text" class="input-dark font-mono text-indigo-300" placeholder="e.g. ABC12345" maxlength="10" />
          </div>

          <!-- Device hash error -->
          <div v-if="form.errors.device_hash" class="p-3 bg-rose-500/15 border border-rose-500/30 text-rose-300 text-xs rounded-xl text-center font-medium">
            ⛔ {{ form.errors.device_hash }}
          </div>

          <!-- Submit -->
          <button
            type="submit"
            :disabled="form.processing || !deviceHashReady"
            class="btn-neon btn-primary w-full py-3.5 rounded-2xl text-sm font-black text-white disabled:opacity-50"
          >
            <span v-if="!deviceHashReady">🔒 Verifying Device...</span>
            <span v-else-if="form.processing">⏳ Creating Account...</span>
            <span v-else>🚀 Create Account</span>
          </button>
        </form>

        <p class="text-center text-xs text-slate-500">
          Already have an account?
          <Link href="/login" class="text-indigo-400 font-bold hover:text-indigo-300 transition-colors">Sign In</Link>
        </p>

        <!-- Trust badges -->
        <div class="flex items-center justify-center gap-4 pt-2 border-t border-white/5">
          <span class="text-[10px] text-slate-600 flex items-center gap-1">🔒 1 Device Policy</span>
          <span class="text-[10px] text-slate-600 flex items-center gap-1">🔑 PIN Recovery</span>
          <span class="text-[10px] text-slate-600 flex items-center gap-1">💸 Free Forever</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import FingerprintJS from '@fingerprintjs/fingerprintjs';

const props = defineProps({
  ref: String,
  ref_code: String,
});

const getInitialRefCode = () => {
  if (props.ref_code) return props.ref_code;
  if (props.ref) return props.ref;
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    return params.get('ref') || params.get('ref_code') || '';
  }
  return '';
};

const deviceHashReady = ref(false);

const form = useForm({
  name:                  '',
  phone:                 '',
  email:                 '',
  password:              '',
  password_confirmation: '',
  recovery_pin:          '',
  ref_code:              getInitialRefCode(),
  device_hash:           '',
});

onMounted(async () => {
  try {
    const fp     = await FingerprintJS.load();
    const result = await fp.get();
    form.device_hash    = result.visitorId;
    deviceHashReady.value = true;
    document.cookie = `device_hash=${result.visitorId}; path=/; max-age=31536000; SameSite=Lax`;
  } catch {
    deviceHashReady.value = true; // fallback
  }
});


const submit = () => form.post('/register');
</script>
