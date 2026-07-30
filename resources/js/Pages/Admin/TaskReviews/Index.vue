<template>
  <AdminLayout>
    <div class="space-y-6 sm:space-y-8">
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400">
            Fast Review Hub
          </h1>
          <p class="text-xs sm:text-sm text-slate-400 mt-1">1-click proof approval & auto-delete image files</p>
        </div>
        <button
          v-if="selectedIds.length > 0"
          @click="bulkApprove"
          :disabled="bulkApproving"
          class="w-full sm:w-auto bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-400 hover:to-emerald-500 px-5 py-2.5 rounded-xl text-sm font-bold text-white shadow-lg shadow-emerald-500/30 transition-all duration-300 transform hover:-translate-y-0.5 flex items-center justify-center gap-2"
        >
          <span v-if="bulkApproving" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
          {{ bulkApproving ? 'Approving...' : `✓ Bulk Approve (${selectedIds.length})` }}
        </button>
      </div>

      <!-- Empty State -->
      <div v-if="!pendingReviews.data || pendingReviews.data.length === 0" class="flex flex-col items-center justify-center p-8 sm:p-12 bg-slate-900/50 rounded-3xl border border-slate-800 border-dashed">
        <div class="w-16 h-16 sm:w-20 sm:h-20 bg-slate-800 rounded-full flex items-center justify-center mb-4">
          <svg class="w-8 h-8 sm:w-10 sm:h-10 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
        </div>
        <h3 class="text-lg sm:text-xl font-bold text-white mb-2">No pending reviews</h3>
        <p class="text-xs sm:text-sm text-slate-400 text-center max-w-md">You're all caught up! There are no tasks waiting for verification at the moment.</p>
      </div>

      <!-- Cards Grid -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
        <div v-for="review in pendingReviews.data" :key="review.id" class="bg-slate-900/40 backdrop-blur-sm p-4 sm:p-6 rounded-3xl border border-slate-800 hover:border-indigo-500/50 hover:shadow-[0_0_20px_rgba(99,102,241,0.1)] transition-all duration-300 flex flex-col space-y-4">
          <div class="flex flex-col sm:flex-row justify-between items-start gap-3 sm:gap-4">
            <label class="flex items-start gap-3 cursor-pointer group w-full sm:w-auto">
              <div class="relative flex items-center justify-center mt-0.5 sm:mt-1">
                <input type="checkbox" :value="review.id" v-model="selectedIds" class="peer appearance-none w-5 h-5 border-2 border-slate-600 rounded-md checked:bg-indigo-500 checked:border-indigo-500 cursor-pointer transition-colors" />
                <svg class="absolute w-3 h-3 text-white opacity-0 peer-checked:opacity-100 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
              </div>
              <div class="flex-grow">
                <span class="font-bold text-white text-sm block group-hover:text-indigo-400 transition-colors truncate">{{ review.user ? review.user.name : 'Unknown User' }}</span>
                <span class="text-xs text-slate-500">ID: {{ review.user_id }}</span>
              </div>
            </label>
            <div class="bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 px-3 py-1.5 rounded-lg text-xs font-semibold sm:shrink-0 text-left sm:text-right w-full sm:w-auto truncate">
              {{ review.task ? review.task.title : 'Deleted Task' }}
            </div>
          </div>

          <!-- Dynamic Proofs -->
          <div class="space-y-3 flex-grow">
            <template v-if="isDynamicProof(review.submitted_data)">
              <div v-for="(entry, reqId) in review.submitted_data" :key="reqId" class="bg-slate-950 border border-slate-800/50 p-4 rounded-2xl relative overflow-hidden group/proof">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-700 group-hover/proof:bg-indigo-500 transition-colors"></div>
                <div class="text-[10px] text-slate-400 font-bold mb-2 uppercase tracking-wider pl-1">{{ entry.label || reqId }}</div>
                <div v-if="entry.type === 'text'" class="text-sm text-slate-200 whitespace-pre-wrap pl-1 font-medium">{{ entry.value || '—' }}</div>
                <div v-else-if="entry.type === 'image'" class="pl-1">
                  <template v-if="review.screenshot_hashes && review.screenshot_hashes.length > 0">
                    <div class="flex flex-wrap gap-3">
                      <div v-for="sh in review.screenshot_hashes" :key="sh.id" 
                           class="relative group/thumb w-24 h-24 rounded-xl overflow-hidden border-2 border-slate-700 hover:border-indigo-500 cursor-pointer transition-colors shadow-lg"
                           @click="openImage('/storage/' + sh.file_path)">
                        <img :src="'/storage/' + sh.file_path" class="w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-110" alt="Proof Thumbnail" />
                        <div class="absolute inset-0 bg-indigo-900/40 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[1px]">
                          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </div>
                      </div>
                    </div>
                  </template>
                  <span v-else class="text-xs text-slate-500 italic bg-slate-900 px-3 py-1.5 rounded-lg">No image attached</span>
                </div>
              </div>
            </template>

            <!-- Legacy Proofs -->
            <template v-else>
              <div v-if="review.submitted_data?.text_proof" class="bg-slate-950 border border-slate-800/50 p-4 rounded-2xl relative overflow-hidden group/proof">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-700 group-hover/proof:bg-indigo-500 transition-colors"></div>
                <div class="text-[10px] text-slate-400 font-bold mb-2 uppercase tracking-wider pl-1">Text Proof</div>
                <div class="text-sm text-slate-200 whitespace-pre-wrap pl-1 font-medium">{{ review.submitted_data.text_proof }}</div>
              </div>

              <div v-if="review.screenshot_hashes && review.screenshot_hashes.length > 0" class="bg-slate-950 border border-slate-800/50 p-4 rounded-2xl relative overflow-hidden group/proof">
                <div class="absolute left-0 top-0 bottom-0 w-1 bg-slate-700 group-hover/proof:bg-indigo-500 transition-colors"></div>
                <div class="text-[10px] text-slate-400 font-bold mb-3 uppercase tracking-wider pl-1">Screenshot Proof</div>
                <div class="pl-1">
                  <div class="relative group/thumb w-24 h-24 rounded-xl overflow-hidden border-2 border-slate-700 hover:border-indigo-500 cursor-pointer transition-colors shadow-lg inline-block"
                       @click="openImage('/storage/' + review.screenshot_hashes[0].file_path)">
                    <img :src="'/storage/' + review.screenshot_hashes[0].file_path" class="w-full h-full object-cover transition-transform duration-300 group-hover/thumb:scale-110" alt="Legacy Proof Thumbnail" />
                    <div class="absolute inset-0 bg-indigo-900/40 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-[1px]">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </div>
                  </div>
                </div>
              </div>
            </template>
          </div>

          <div class="grid grid-cols-2 gap-3 pt-2">
            <button @click="approve(review)" class="py-2.5 bg-emerald-500/10 hover:bg-emerald-500 hover:text-white text-emerald-400 border border-emerald-500/20 font-bold rounded-xl text-sm transition-colors flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
              Approve
            </button>
            <button @click="reject(review)" class="py-2.5 bg-rose-500/10 hover:bg-rose-500 hover:text-white text-rose-400 border border-rose-500/20 font-bold rounded-xl text-sm transition-colors flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
              Reject
            </button>
          </div>
        </div>
      </div>
      
      <!-- Image Viewer Modal -->
      <Teleport to="body">
        <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div v-if="selectedImage" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 backdrop-blur-md p-4 sm:p-8" @click="selectedImage = null">
            <button class="absolute top-4 right-4 sm:top-6 sm:right-6 text-slate-300 hover:text-white bg-slate-800/50 hover:bg-rose-500/80 p-2 sm:px-4 sm:py-2 rounded-xl backdrop-blur-sm transition-colors flex items-center gap-2" @click="selectedImage = null">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              <span class="font-bold hidden sm:inline">Close</span>
            </button>
            <img :src="selectedImage" class="max-w-full max-h-full object-contain rounded-2xl shadow-[0_0_50px_rgba(0,0,0,0.5)] transform scale-100 transition-transform duration-300" @click.stop />
          </div>
        </transition>
      </Teleport>
      
      <!-- Rejection Modal -->
      <Teleport to="body">
        <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" enter-to-class="opacity-100 translate-y-0 sm:scale-100" leave-active-class="transition ease-in duration-200" leave-from-class="opacity-100 translate-y-0 sm:scale-100" leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
          <div v-if="rejectingReview" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-0">
            <div class="fixed inset-0 bg-black/80 backdrop-blur-sm" @click="closeRejectModal"></div>
            
            <div class="relative bg-slate-900 border border-slate-800 rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col">
              <div class="px-6 py-5 border-b border-slate-800/80 flex justify-between items-center bg-slate-900/50">
                <h3 class="text-xl font-bold text-rose-500 flex items-center gap-2">
                  <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                  Reject Task Proof
                </h3>
                <button @click="closeRejectModal" class="text-slate-400 hover:text-white transition-colors p-1 rounded-lg hover:bg-slate-800">
                  <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
              </div>
              
              <div class="p-6 space-y-4">
                <p class="text-slate-300 text-sm">
                  You are about to reject a task submitted by <span class="font-bold text-white">{{ rejectingReview.user?.name || 'Unknown User' }}</span>. This will deduct <span class="text-rose-400 font-bold">10 Health</span> points from their account.
                </p>
                <div>
                  <label class="block text-sm font-bold text-slate-400 mb-2">Reason for Rejection <span class="text-rose-500">*</span></label>
                  <textarea 
                    v-model="rejectReason" 
                    rows="3" 
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl p-3 text-sm text-white placeholder-slate-600 focus:border-rose-500 focus:ring-1 focus:ring-rose-500 transition-all resize-none"
                    placeholder="e.g., Invalid screenshot proof, does not meet requirements..."
                  ></textarea>
                </div>
              </div>
              
              <div class="px-6 py-4 border-t border-slate-800/80 bg-slate-900/50 flex justify-end gap-3">
                <button @click="closeRejectModal" class="px-4 py-2.5 rounded-xl font-bold text-sm text-slate-300 hover:text-white hover:bg-slate-800 transition-colors">
                  Cancel
                </button>
                <button 
                  @click="confirmReject" 
                  :disabled="!rejectReason.trim() || isRejecting"
                  class="px-5 py-2.5 rounded-xl font-bold text-sm text-white bg-rose-600 hover:bg-rose-500 transition-all shadow-lg shadow-rose-600/20 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                  <span v-if="isRejecting" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></span>
                  Confirm Reject
                </button>
              </div>
            </div>
          </div>
        </transition>
      </Teleport>

    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

defineProps({
  pendingReviews: Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const selectedIds   = ref([]);
const bulkApproving = ref(false);
const selectedImage = ref(null); // State for the image modal

// Reject Modal State
const rejectingReview = ref(null);
const rejectReason = ref('');
const isRejecting = ref(false);

const openImage = (url) => {
  selectedImage.value = url;
};

const bulkApprove = () => {
  if (selectedIds.value.length === 0) return;
  bulkApproving.value = true;
  router.post(`${adminPath.value}/reviews/bulk-approve`, { ids: selectedIds.value }, {
    preserveScroll: true,
    onSuccess: () => { selectedIds.value = []; },
    onFinish: () => { bulkApproving.value = false; },
  });
};

/**
 * Detect if submitted_data is dynamic (keyed by req_id with {type, label} objects)
 * vs legacy (flat with text_proof / screenshot_hash).
 */
const isDynamicProof = (data) => {
  if (!data || typeof data !== 'object') return false;
  // Legacy keys
  if (data.text_proof !== undefined || data.screenshot_hash !== undefined) return false;
  // Dynamic: first value should have a 'type' key
  const first = Object.values(data)[0];
  return first && typeof first === 'object' && first.type !== undefined;
};

const approve = (review) => {
  router.post(`${adminPath.value}/reviews/${review.id}/approve`, {}, { preserveScroll: true });
};

const reject = (review) => {
  rejectingReview.value = review;
  rejectReason.value = 'Invalid screenshot proof';
};

const closeRejectModal = () => {
  if (isRejecting.value) return;
  rejectingReview.value = null;
  rejectReason.value = '';
};

const confirmReject = () => {
  if (!rejectingReview.value || !rejectReason.value.trim()) return;
  
  isRejecting.value = true;
  router.post(`${adminPath.value}/reviews/${rejectingReview.value.id}/reject`, {
    admin_note: rejectReason.value.trim(),
  }, { 
    preserveScroll: true,
    onSuccess: () => {
      // Force close the modal on success (bypassing the isRejecting check in closeRejectModal)
      rejectingReview.value = null;
      rejectReason.value = '';
    },
    onFinish: () => {
      isRejecting.value = false;
    }
  });
};
</script>
