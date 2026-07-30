<template>
  <div class="min-h-screen flex items-center justify-center p-4 relative" style="background-color: #02040a;">
    <!-- Floating Toast Notification -->
    <Transition
      enter-active-class="transition duration-300 ease-out"
      enter-from-class="transform -translate-y-4 opacity-0 scale-95"
      enter-to-class="transform translate-y-0 opacity-100 scale-100"
      leave-active-class="transition duration-200 ease-in"
      leave-from-class="transform translate-y-0 opacity-100 scale-100"
      leave-to-class="transform -translate-y-4 opacity-0 scale-95"
    >
      <div v-if="successToastMessage" class="fixed top-6 left-1/2 -translate-x-1/2 z-50 max-w-md w-11/12 p-4 rounded-2xl bg-emerald-950/95 border border-emerald-500/50 text-white shadow-2xl backdrop-blur-xl flex items-center gap-3">
        <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center justify-center font-bold text-lg shrink-0">
          ✓
        </div>
        <div class="flex-1 text-xs font-semibold text-emerald-100">
          {{ successToastMessage }}
        </div>
        <button @click="successToastMessage = null" class="text-slate-400 hover:text-white text-xs p-1">✕</button>
      </div>
    </Transition>

    <!-- Background glows -->
    <div class="fixed -top-20 -right-20 w-80 h-80 bg-violet-600/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed -bottom-20 -left-20 w-96 h-96 bg-indigo-600/8 rounded-full blur-3xl pointer-events-none"></div>

    <div class="glass-card max-w-md w-full p-7 rounded-3xl border border-indigo-500/20 shadow-2xl neon-glow-indigo animate-slide-in-up relative overflow-hidden">
      <div class="absolute inset-0 cyber-grid opacity-30 pointer-events-none"></div>

      <div class="relative z-10 space-y-5">
        <!-- Logo -->
        <div class="text-center space-y-2">
          <template v-if="$page.props.siteSettings?.site_logo">
            <img :src="$page.props.siteSettings.site_logo" alt="Site Logo" class="h-12 w-auto max-h-12 mx-auto object-contain drop-shadow-[0_0_15px_rgba(99,102,241,0.5)]" />
          </template>
          <template v-else>
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-black text-white text-xl shadow-lg neon-glow-indigo mx-auto">
              E
            </div>
          </template>
          <h1 class="text-xl font-black text-white">Welcome Back</h1>
          <p class="text-xs text-slate-500">Sign in to your Easytsk V2 account</p>
        </div>

        <!-- Google OAuth -->
        <button type="button" @click="handleGoogleLogin"
          class="w-full py-3 px-4 glass-pill hover:border-white/20 text-white text-xs font-semibold rounded-2xl border border-white/8 flex items-center justify-center gap-3 transition-all card-hover"
        >
          <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24">
            <path fill="#EA4335" d="M12 5c1.6 0 3 .6 4.1 1.6l3.1-3.1C17.3 1.7 14.8 1 12 1 7.5 1 3.7 3.6 1.9 7.3l3.7 2.9C6.5 7.4 9 5 12 5z"/>
            <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5c-.3 1.5-1.1 2.8-2.4 3.7l3.7 2.9c2.2-2 3.7-5 3.7-8.8z"/>
            <path fill="#FBBC05" d="M5.6 14.8c-.3-.8-.4-1.8-.4-2.8s.1-2 .4-2.8L1.9 6.3C.7 8.7 0 10.3 0 12s.7 3.3 1.9 5.7l3.7-2.9z"/>
            <path fill="#34A853" d="M12 23c3.2 0 6-1.1 8-3l-3.7-2.9c-1.1.7-2.5 1.2-4.3 1.2-3 0-5.5-2.4-6.4-5.2L1.9 16C3.7 19.7 7.5 23 12 23z"/>
          </svg>
          <span>Continue with Google</span>
        </button>

        <!-- Divider -->
        <div class="flex items-center gap-3">
          <div class="flex-1 h-px bg-white/5"></div>
          <span class="text-[10px] uppercase font-bold text-slate-600 tracking-wider">or email</span>
          <div class="flex-1 h-px bg-white/5"></div>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Phone Number or Email</label>
            <input v-model="form.phone" type="text" required class="input-dark" placeholder="e.g. 01700000000 or user@example.com" />
            <span v-if="form.errors.phone" class="text-xs text-rose-400 mt-1 block">{{ form.errors.phone }}</span>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Password</label>
            <input v-model="form.password" type="password" required class="input-dark" placeholder="••••••••" />
            <span v-if="form.errors.password" class="text-xs text-rose-400 mt-1 block">{{ form.errors.password }}</span>
          </div>

          <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 cursor-pointer">
              <input v-model="form.remember" type="checkbox" class="w-3.5 h-3.5 accent-indigo-500" />
              <span class="text-xs text-slate-500">Remember me</span>
            </label>
            <button type="button" @click="showRecoverModal = true" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
              Forgot password?
            </button>
          </div>

          <button
            type="submit"
            :disabled="form.processing || !deviceHashReady"
            class="btn-neon btn-primary w-full py-3.5 rounded-2xl text-sm font-black text-white disabled:opacity-50"
          >
            <span v-if="!deviceHashReady">🔒 Verifying Device...</span>
            <span v-else-if="form.processing">⏳ Signing In...</span>
            <span v-else>🔑 Sign In</span>
          </button>
        </form>

        <p class="text-center text-xs text-slate-500">
          Don't have an account?
          <Link href="/register" class="text-indigo-400 font-bold hover:text-indigo-300 transition-colors">Create Account</Link>
        </p>
      </div>
    </div>

    <!-- Recovery / Support Ticket Modal -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showRecoverModal" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(0,0,0,0.85); backdrop-filter: blur(8px);">
          <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-indigo-500/30 neon-glow-indigo animate-slide-in-up relative overflow-hidden">
            <!-- Modal Header -->
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-black text-white">🔐 Account Recovery</h3>
                <p class="text-xs text-slate-400 mt-0.5">Reset via 4-digit PIN or Admin Support Ticket</p>
              </div>
              <button @click="closeRecoveryModal" class="w-8 h-8 rounded-xl glass-pill flex items-center justify-center text-slate-400 hover:text-white transition-colors text-sm">✕</button>
            </div>

            <!-- Mode Tabs -->
            <div class="grid grid-cols-3 gap-1 bg-slate-900/90 p-1 rounded-2xl border border-slate-800 mb-4">
              <button
                @click="recoveryMode = 'pin'"
                :class="[
                  'py-2 text-[11px] font-bold rounded-xl transition-all',
                  recoveryMode === 'pin' ? 'btn-neon btn-primary text-white shadow-md' : 'text-slate-400 hover:text-white'
                ]"
              >
                Use PIN
              </button>
              <button
                @click="recoveryMode = 'open_ticket'"
                :class="[
                  'py-2 text-[11px] font-bold rounded-xl transition-all',
                  recoveryMode === 'open_ticket' ? 'btn-neon btn-primary text-white shadow-md' : 'text-slate-400 hover:text-white'
                ]"
              >
                Open Ticket
              </button>
              <button
                @click="recoveryMode = 'check_ticket'"
                :class="[
                  'py-2 text-[11px] font-bold rounded-xl transition-all',
                  recoveryMode === 'check_ticket' ? 'btn-neon btn-primary text-white shadow-md' : 'text-slate-400 hover:text-white'
                ]"
              >
                Check Ticket
              </button>
            </div>

            <!-- TAB 1: PIN Recovery Form -->
            <div v-if="recoveryMode === 'pin'" class="space-y-3">
              <form @submit.prevent="submitRecovery" class="space-y-3">
                <input v-model="recoverForm.phone" type="tel" placeholder="Registered phone number" required class="input-dark text-xs" />
                <input v-model="recoverForm.recovery_pin" type="text" maxlength="4" pattern="\d{4}" placeholder="4-digit PIN" required
                  class="input-dark text-xs font-mono tracking-[0.4em] text-center text-indigo-300 border-indigo-500/30" />
                <input v-model="recoverForm.new_password" type="password" placeholder="New password" required class="input-dark text-xs" />
                <input v-model="recoverForm.new_password_confirmation" type="password" placeholder="Confirm new password" required class="input-dark text-xs" />

                <div v-if="recoverForm.errors.recovery_pin" class="text-xs text-rose-400">{{ recoverForm.errors.recovery_pin }}</div>
                <div v-if="recoverForm.errors.phone" class="text-xs text-rose-400">{{ recoverForm.errors.phone }}</div>

                <div class="flex gap-2 pt-1">
                  <button type="button" @click="closeRecoveryModal" class="flex-1 py-2.5 glass-pill text-xs text-slate-400 rounded-xl border border-white/8 hover:text-white transition-colors">Cancel</button>
                  <button type="submit" class="flex-1 btn-neon btn-primary py-2.5 text-xs font-bold text-white rounded-xl">Reset Password</button>
                </div>
              </form>

              <div class="text-center pt-2 border-t border-slate-800">
                <button type="button" @click="recoveryMode = 'open_ticket'" class="text-xs text-indigo-400 hover:text-indigo-300 font-semibold">
                  Forgot your PIN? Open a Support Ticket &rarr;
                </button>
              </div>
            </div>

            <!-- TAB 2: Open Reset Ticket Form -->
            <div v-else-if="recoveryMode === 'open_ticket'" class="space-y-3">
              <div v-if="createdTicketCode" class="p-4 bg-indigo-950/60 border border-indigo-500/40 rounded-2xl text-center space-y-2">
                <p class="text-xs font-bold text-indigo-300">🎉 Password Reset Ticket Created!</p>
                <p class="text-xs text-slate-300">Your Ticket Code is:</p>
                <p class="text-xl font-mono font-black text-indigo-300 tracking-wider bg-slate-900 py-2 rounded-xl border border-indigo-500/30">
                  {{ createdTicketCode }}
                </p>
                <p class="text-[11px] text-slate-400">
                  Please save this code. Admin will review your request. Once approved, use the "Check Ticket" tab to reset your password.
                </p>
                <button @click="switchToCheckTicket" class="w-full py-2.5 btn-neon btn-primary text-white font-bold text-xs rounded-xl transition-colors">
                  Go to Check Ticket Status
                </button>
              </div>

              <form v-else @submit.prevent="submitOpenTicket" class="space-y-3">
                <div>
                  <label class="text-[11px] font-semibold text-slate-400 block mb-1">Registered Phone or Email</label>
                  <input v-model="ticketForm.phone" type="text" placeholder="01700000000 or user@example.com" required class="input-dark text-xs" />
                </div>
                <div>
                  <label class="text-[11px] font-semibold text-slate-400 block mb-1">Reason / Note for Admin</label>
                  <textarea v-model="ticketForm.message" rows="2" placeholder="e.g. Forgot PIN and password..." class="input-dark text-xs py-2"></textarea>
                </div>

                <div v-if="ticketError" class="text-xs text-rose-400 bg-rose-950/40 p-2 rounded-xl border border-rose-500/20">{{ ticketError }}</div>

                <div class="flex gap-2 pt-1">
                  <button type="button" @click="closeRecoveryModal" class="flex-1 py-2.5 glass-pill text-xs text-slate-400 rounded-xl border border-white/8 hover:text-white transition-colors">Cancel</button>
                  <button type="submit" :disabled="submittingTicket" class="flex-1 btn-neon btn-primary py-2.5 text-xs font-bold text-white rounded-xl disabled:opacity-50">
                    <span v-if="submittingTicket">⏳ Submitting...</span>
                    <span v-else>📩 Submit Ticket</span>
                  </button>
                </div>
              </form>
            </div>

            <!-- TAB 3: Check Ticket & Complete Password Reset -->
            <div v-else-if="recoveryMode === 'check_ticket'" class="space-y-3">
              <!-- SUCCESS SCREEN WHEN PASSWORD IS RESET -->
              <div v-if="resetSuccess" class="p-6 rounded-3xl bg-gradient-to-b from-emerald-950/90 via-slate-900 to-slate-950 border border-emerald-500/40 text-center space-y-4 shadow-2xl shadow-emerald-950/60 animate-in fade-in zoom-in duration-300">
                <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center mx-auto text-3xl text-emerald-400 shadow-lg shadow-emerald-500/20 animate-bounce">
                  🎉
                </div>

                <div class="space-y-1">
                  <h4 class="text-base font-black text-white">Password Reset Successful!</h4>
                  <p class="text-xs text-slate-300">Your account password has been updated. You can now sign in with your new password.</p>
                </div>

                <div v-if="checkTicketForm.phone" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900/90 border border-emerald-500/30 text-xs font-mono text-emerald-400">
                  <span>📱</span>
                  <span>{{ checkTicketForm.phone }}</span>
                </div>

                <button
                  @click="finishAndGoToLogin"
                  class="w-full py-3 bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-600 hover:from-emerald-500 hover:to-teal-500 text-white font-black text-xs rounded-xl shadow-lg shadow-emerald-900/50 transition-all flex items-center justify-center gap-2 transform active:scale-98"
                >
                  <span>🔑 Sign In Now</span>
                </button>
              </div>

              <!-- NORMAL CHECK TICKET FORM -->
              <template v-else>
                <form @submit.prevent="checkTicketStatus" class="space-y-3">
                  <div>
                    <label class="text-[11px] font-semibold text-slate-400 block mb-1">Registered Phone Number</label>
                    <input v-model="checkTicketForm.phone" type="text" placeholder="01700000000" required class="input-dark text-xs" />
                  </div>
                  <div>
                    <label class="text-[11px] font-semibold text-slate-400 block mb-1">Ticket Code</label>
                    <input v-model="checkTicketForm.ticket_code" type="text" placeholder="e.g. PR-8X92K4" required class="input-dark text-xs font-mono uppercase tracking-wider text-indigo-300 border-indigo-500/30" />
                  </div>

                  <button type="submit" :disabled="checkingTicket" class="w-full py-2.5 btn-neon btn-primary text-white font-bold text-xs rounded-xl transition-colors disabled:opacity-50">
                    <span v-if="checkingTicket">⏳ Checking Status...</span>
                    <span v-else>🔍 Check Status</span>
                  </button>
                </form>

                <!-- Ticket Info & Reset Password Form when Approved -->
                <div v-if="ticketStatusResult" class="mt-3 p-3 bg-slate-900 border border-slate-800 rounded-2xl space-y-3">
                  <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Status:</span>
                    <span
                      :class="[
                        'px-2 py-0.5 rounded-md font-bold uppercase text-[10px]',
                        ticketStatusResult.status === 'pending' ? 'bg-indigo-500/20 text-indigo-300' : '',
                        ticketStatusResult.status === 'approved' ? 'bg-emerald-500/20 text-emerald-300' : '',
                        ticketStatusResult.status === 'completed' ? 'bg-indigo-500/20 text-indigo-300' : '',
                        ticketStatusResult.status === 'rejected' ? 'bg-rose-500/20 text-rose-300' : ''
                      ]"
                    >
                      {{ ticketStatusResult.status }}
                    </span>
                  </div>

                  <p v-if="ticketStatusResult.admin_note" class="text-xs text-indigo-300 italic">
                    Note: {{ ticketStatusResult.admin_note }}
                  </p>

                  <!-- When Approved: Show Password Reset Form -->
                  <div v-if="ticketStatusResult.status === 'approved'" class="pt-2 border-t border-slate-800 space-y-3">
                    <p class="text-xs font-bold text-emerald-400">✅ Approved by Admin (Valid for 24h)! Set your new password below:</p>
                    
                    <form @submit.prevent="submitFinalPasswordReset" class="space-y-2">
                      <input v-model="resetForm.reset_code" type="text" placeholder="6-digit Reset Code" required class="input-dark text-xs font-mono text-center tracking-widest text-indigo-300 border-indigo-500/30" />
                      <input v-model="resetForm.new_password" type="password" placeholder="New Password" required class="input-dark text-xs" />
                      <input v-model="resetForm.new_password_confirmation" type="password" placeholder="Confirm New Password" required class="input-dark text-xs" />

                      <div v-if="resetError" class="text-xs text-rose-400 bg-rose-950/40 p-2 rounded-xl border border-rose-500/20">{{ resetError }}</div>

                      <button type="submit" :disabled="resettingPassword" class="w-full btn-neon btn-primary py-2.5 text-xs font-bold text-white rounded-xl disabled:opacity-50">
                        <span v-if="resettingPassword">⏳ Resetting Password...</span>
                        <span v-else>🔑 Set New Password & Sign In</span>
                      </button>
                    </form>
                  </div>

                  <div v-else-if="ticketStatusResult.status === 'pending'" class="text-xs text-indigo-300/80">
                    ⏳ Your ticket is under review by Admin. Please check back shortly.
                  </div>
                  <div v-else-if="ticketStatusResult.status === 'rejected'" class="text-xs text-rose-400">
                    ❌ Request rejected. Reason: {{ ticketStatusResult.admin_note }}
                  </div>
                  <div v-else-if="ticketStatusResult.status === 'completed'" class="p-3 bg-emerald-950/40 border border-emerald-500/30 rounded-xl text-center space-y-1">
                    <p class="text-xs font-bold text-emerald-300">🎉 Password reset completed for this ticket!</p>
                    <p class="text-[11px] text-slate-400">You can now log in with your updated password.</p>
                  </div>
                </div>
              </template>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import FingerprintJS from '@fingerprintjs/fingerprintjs';
import axios from 'axios';

const deviceHashReady  = ref(false);
const showRecoverModal = ref(false);
const recoveryMode     = ref('pin'); // 'pin' | 'open_ticket' | 'check_ticket'

const form = useForm({
  phone:       '',
  password:    '',
  remember:    false,
  device_hash: '',
});

const recoverForm = useForm({
  phone:                     '',
  recovery_pin:              '',
  new_password:              '',
  new_password_confirmation: '',
});

// Ticket State
const ticketForm = ref({
  phone: '',
  message: '',
});
const submittingTicket = ref(false);
const createdTicketCode = ref(null);
const ticketError = ref(null);

const checkTicketForm = ref({
  phone: '',
  ticket_code: '',
});
const checkingTicket = ref(false);
const ticketStatusResult = ref(null);

const resetForm = ref({
  reset_code: '',
  new_password: '',
  new_password_confirmation: '',
});
const resettingPassword = ref(false);
const resetError = ref(null);
const resetSuccess = ref(false);
const successToastMessage = ref(null);

onMounted(async () => {
  try {
    const fp     = await FingerprintJS.load();
    const result = await fp.get();
    form.device_hash    = result.visitorId;
    deviceHashReady.value = true;
    document.cookie = `device_hash=${result.visitorId}; path=/; max-age=31536000; SameSite=Lax`;
  } catch {
    deviceHashReady.value = true;
  }
});

const handleGoogleLogin = () => {
  const url = form.device_hash 
    ? `/auth/google?device_hash=${encodeURIComponent(form.device_hash)}`
    : '/auth/google';
  window.location.href = url;
};

const submit = () => form.post('/login');

const finishAndGoToLogin = () => {
  if (checkTicketForm.value.phone) {
    form.phone = checkTicketForm.value.phone;
  } else if (recoverForm.phone) {
    form.phone = recoverForm.phone;
  }

  successToastMessage.value = 'Password reset successfully! Please sign in with your new password.';
  closeRecoveryModal();

  setTimeout(() => {
    if (successToastMessage.value) {
      successToastMessage.value = null;
    }
  }, 7000);
};

const submitRecovery = () => {
  recoverForm.post('/recover-account', {
    onSuccess: () => {
      finishAndGoToLogin();
    },
  });
};

const submitOpenTicket = async () => {
  submittingTicket.value = true;
  ticketError.value = null;

  try {
    const response = await axios.post('/password-tickets/submit', {
      phone: ticketForm.value.phone,
      message: ticketForm.value.message,
      device_hash: form.device_hash,
    });

    if (response.data.success) {
      createdTicketCode.value = response.data.ticket_code;
      checkTicketForm.value.phone = ticketForm.value.phone;
      checkTicketForm.value.ticket_code = response.data.ticket_code;
    }
  } catch (error) {
    if (error.response?.data?.errors?.phone) {
      ticketError.value = error.response.data.errors.phone[0];
    } else {
      ticketError.value = error.response?.data?.message || 'Failed to submit ticket. Please check phone number.';
    }
  } finally {
    submittingTicket.value = false;
  }
};

const switchToCheckTicket = () => {
  recoveryMode.value = 'check_ticket';
  if (createdTicketCode.value) {
    checkTicketStatus();
  }
};

const checkTicketStatus = async () => {
  checkingTicket.value = true;
  ticketStatusResult.value = null;

  try {
    const response = await axios.post('/password-tickets/check', {
      phone: checkTicketForm.value.phone,
      ticket_code: checkTicketForm.value.ticket_code,
    });

    if (response.data.success) {
      ticketStatusResult.value = response.data;
      if (response.data.reset_code) {
        resetForm.value.reset_code = response.data.reset_code;
      }
    }
  } catch (error) {
    ticketStatusResult.value = {
      status: 'error',
      admin_note: error.response?.data?.message || 'No matching ticket found.',
    };
  } finally {
    checkingTicket.value = false;
  }
};

const submitFinalPasswordReset = async () => {
  resettingPassword.value = true;
  resetError.value = null;

  try {
    const response = await axios.post('/password-tickets/reset', {
      phone: checkTicketForm.value.phone,
      ticket_code: checkTicketForm.value.ticket_code,
      reset_code: resetForm.value.reset_code,
      new_password: resetForm.value.new_password,
      new_password_confirmation: resetForm.value.new_password_confirmation,
    });

    if (response.data.success) {
      resetSuccess.value = true;
    }
  } catch (error) {
    if (error.response?.data?.errors) {
      const errs = error.response.data.errors;
      resetError.value = Object.values(errs).flat().join(' ');
    } else {
      resetError.value = error.response?.data?.message || 'Failed to reset password.';
    }
  } finally {
    resettingPassword.value = false;
  }
};

const closeRecoveryModal = () => {
  showRecoverModal.value   = false;
  recoveryMode.value       = 'pin';
  createdTicketCode.value  = null;
  ticketError.value        = null;
  ticketStatusResult.value = null;
  resetError.value         = null;
  resetSuccess.value       = false;
};
</script>

<style scoped>
.modal-enter-active { transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
.modal-leave-active { transition: all 0.2s ease-in; }
.modal-enter-from   { transform: scale(0.85); opacity: 0; }
.modal-leave-to     { transform: scale(1.05); opacity: 0; }
</style>
