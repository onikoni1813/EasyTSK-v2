<template>
  <AppLayout>
    <div class="space-y-6 animate-slide-in-up">

      <!-- Header -->
      <div class="glass-card p-6 rounded-3xl border border-pink-500/15 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-40 h-40 bg-pink-500/8 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div class="badge badge-violet mb-2">📢 Ad Engine</div>
            <h1 class="text-2xl font-black text-white">Micro-Campaign Hub</h1>
            <p class="text-xs text-slate-400 mt-1">Spend points to promote your link. Workers complete your task and submit verified proof.</p>
          </div>
          <div class="flex items-center gap-3 shrink-0">
            <div class="text-left sm:text-right">
              <div class="text-xs text-slate-500 mb-0.5">Available Balance</div>
              <div class="text-2xl font-black text-emerald-300"><AnimatedNumber :value="user.main_balance" :decimals="0" /> <span class="text-xs font-normal text-slate-500">Pts</span></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Create Campaign -->
      <div class="glass-card p-6 rounded-3xl border border-violet-500/15">
        <div class="section-header mb-5">
          <span class="section-title">✨ Create Campaign</span>
          <div class="section-header-line"></div>
        </div>

        <form @submit.prevent="createCampaign" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Campaign Title</label>
              <input v-model="form.title" type="text" placeholder="e.g. Join My Official Telegram Channel" class="input-dark" maxlength="100" required />
            </div>
            <div>
              <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Select Platform / Service</label>
              <select v-model="form.campaign_service_id" class="input-dark" required>
                <option value="" disabled>Choose a service</option>
                <option v-for="service in services" :key="service.id" :value="service.id">
                  {{ campaignIcon(service.platform.toLowerCase()) }} {{ service.platform }} - {{ service.action }} (Cost: {{ service.creator_cost }} pts/click | Min: {{ service.min_clicks ?? 1 }})
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 block">Target URL (Link to promote)</label>
            <input v-model="form.target_url" type="url" placeholder="https://t.me/yourchannel or https://youtube.com/..." class="input-dark" required />
          </div>

          <!-- Interactive Proof Requirement Selector (Multi-Select Cards) -->
          <div class="p-4 sm:p-5 rounded-2xl bg-slate-900/80 border border-indigo-500/20 space-y-3.5 shadow-inner">
            <div class="flex items-center justify-between">
              <label class="text-xs font-bold text-indigo-300 flex items-center gap-1.5">
                <span>🛡️</span>
                <span>Select Proof Requirement(s)</span>
              </label>
              <span class="text-[10px] text-slate-400 font-medium">Click to toggle (Select one or multiple)</span>
            </div>

            <!-- 3 Toggle Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
              <!-- Option 1: Screenshot -->
              <div
                @click="toggleProofReq('screenshot')"
                class="p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer flex items-center justify-between group select-none active:scale-[0.98]"
                :class="selectedProofReqs.has('screenshot') 
                  ? 'bg-emerald-500/15 border-emerald-500/50 shadow-[0_0_15px_rgba(16,185,129,0.15)] ring-1 ring-emerald-500/30' 
                  : 'bg-slate-950/70 border-slate-800 hover:border-slate-700 hover:bg-slate-900/50'"
              >
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base" :class="selectedProofReqs.has('screenshot') ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400'">
                    📸
                  </div>
                  <div>
                    <div class="text-xs font-bold" :class="selectedProofReqs.has('screenshot') ? 'text-white' : 'text-slate-300'">Screenshot</div>
                    <div class="text-[10px]" :class="selectedProofReqs.has('screenshot') ? 'text-emerald-300 font-medium' : 'text-slate-500'">Image upload</div>
                  </div>
                </div>
                <div
                  class="w-5 h-5 rounded-lg flex items-center justify-center border transition-all"
                  :class="selectedProofReqs.has('screenshot') ? 'bg-emerald-500 border-emerald-500 text-white shadow-sm' : 'border-slate-700 group-hover:border-slate-600 bg-slate-900'"
                >
                  <svg v-if="selectedProofReqs.has('screenshot')" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
              </div>

              <!-- Option 2: Username / Profile Link -->
              <div
                @click="toggleProofReq('username_link')"
                class="p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer flex items-center justify-between group select-none active:scale-[0.98]"
                :class="selectedProofReqs.has('username_link') 
                  ? 'bg-cyan-500/15 border-cyan-500/50 shadow-[0_0_15px_rgba(6,182,212,0.15)] ring-1 ring-cyan-500/30' 
                  : 'bg-slate-950/70 border-slate-800 hover:border-slate-700 hover:bg-slate-900/50'"
              >
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base" :class="selectedProofReqs.has('username_link') ? 'bg-cyan-500/20 text-cyan-300' : 'bg-slate-800 text-slate-400'">
                    🔗
                  </div>
                  <div>
                    <div class="text-xs font-bold" :class="selectedProofReqs.has('username_link') ? 'text-white' : 'text-slate-300'">Username / Link</div>
                    <div class="text-[10px]" :class="selectedProofReqs.has('username_link') ? 'text-cyan-300 font-medium' : 'text-slate-500'">@user or link</div>
                  </div>
                </div>
                <div
                  class="w-5 h-5 rounded-lg flex items-center justify-center border transition-all"
                  :class="selectedProofReqs.has('username_link') ? 'bg-cyan-500 border-cyan-500 text-white shadow-sm' : 'border-slate-700 group-hover:border-slate-600 bg-slate-900'"
                >
                  <svg v-if="selectedProofReqs.has('username_link')" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
              </div>

              <!-- Option 3: Secret Code -->
              <div
                @click="toggleProofReq('secret_code')"
                class="p-3.5 rounded-2xl border transition-all duration-200 cursor-pointer flex items-center justify-between group select-none active:scale-[0.98]"
                :class="selectedProofReqs.has('secret_code') 
                  ? 'bg-amber-500/15 border-amber-500/50 shadow-[0_0_15px_rgba(245,158,11,0.15)] ring-1 ring-amber-500/30' 
                  : 'bg-slate-950/70 border-slate-800 hover:border-slate-700 hover:bg-slate-900/50'"
              >
                <div class="flex items-center gap-2.5">
                  <div class="w-8 h-8 rounded-xl flex items-center justify-center text-base" :class="selectedProofReqs.has('secret_code') ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-400'">
                    🔑
                  </div>
                  <div>
                    <div class="text-xs font-bold" :class="selectedProofReqs.has('secret_code') ? 'text-white' : 'text-slate-300'">Secret Code</div>
                    <div class="text-[10px]" :class="selectedProofReqs.has('secret_code') ? 'text-amber-300 font-medium' : 'text-slate-500'">Code verification</div>
                  </div>
                </div>
                <div
                  class="w-5 h-5 rounded-lg flex items-center justify-center border transition-all"
                  :class="selectedProofReqs.has('secret_code') ? 'bg-amber-500 border-amber-500 text-white shadow-sm' : 'border-slate-700 group-hover:border-slate-600 bg-slate-900'"
                >
                  <svg v-if="selectedProofReqs.has('secret_code')" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                </div>
              </div>
            </div>

            <!-- Dynamic Sub-Inputs (Secret Code when enabled) -->
            <div v-if="selectedProofReqs.has('secret_code')" class="pt-1 animate-slide-in-up">
              <div class="flex items-center justify-between mb-1.5">
                <label class="text-xs font-bold text-amber-300 flex items-center gap-1">
                  <span>🔑</span>
                  <span>Expected Secret Code</span>
                </label>
                <span class="text-[9px] font-bold text-rose-400 bg-rose-500/10 px-2 py-0.5 rounded-full border border-rose-500/20">REQUIRED</span>
              </div>
              <input
                v-model="form.secret_code"
                type="text"
                placeholder="e.g. JOIN2026 (Workers must enter this code to complete)"
                class="input-dark font-mono text-amber-200"
                maxlength="255"
                required
              />
              <p class="text-[10px] text-amber-300/80 mt-1">⚡ Correct code submissions will be automatically approved and credited instantly!</p>
            </div>
          </div>

          <!-- Single Unified Task Instructions -->
          <div>
            <label class="text-xs font-semibold text-slate-300 mb-1.5 flex items-center justify-between">
              <span class="flex items-center gap-1.5">
                <span>📝</span>
                <span>Task Instructions (Optional)</span>
              </span>
              <span class="text-[10px] text-slate-500">Steps for workers to complete the task</span>
            </label>
            <textarea
              v-model="form.proof_instruction"
              placeholder="Describe the steps workers need to follow (e.g. 1. Click link and join channel. 2. Take a screenshot of the joined screen and submit)."
              class="input-dark resize-none"
              rows="3"
              maxlength="1000"
            ></textarea>
          </div>

          <div>
            <label class="text-xs font-semibold text-slate-400 mb-1.5 flex items-center justify-between">
              <span>Target Clicks / Submissions: <span class="text-indigo-400 font-black">{{ form.target_clicks }}</span></span>
              <span class="text-emerald-400 font-bold">Total Cost: {{ totalCost }} points</span>
            </label>
            <input 
              v-model.number="form.target_clicks" 
              type="range" 
              :min="minClicks" 
              :max="maxClicks" 
              :step="stepClicks" 
              class="w-full accent-indigo-500 cursor-pointer" 
            />
            <div class="flex justify-between text-[10px] text-slate-600 mt-1 font-medium">
              <span>{{ Number(minClicks).toLocaleString() }} clicks</span>
              <span>{{ Number(maxClicks).toLocaleString() }} clicks</span>
            </div>
          </div>

          <!-- Calculation & Balance Breakdown Card -->
          <div v-if="selectedService" class="p-3.5 rounded-2xl bg-slate-900/60 border border-indigo-500/20 text-xs space-y-1.5">
            <div class="flex justify-between text-slate-300">
              <span>Rate per Verified Action:</span>
              <span class="font-bold text-amber-300">{{ selectedService.creator_cost }} Pts</span>
            </div>
            <div class="flex justify-between text-slate-300">
              <span>Required Proof:</span>
              <span class="font-bold text-indigo-300 capitalize">{{ formatProofType(computedProofType) }}</span>
            </div>
            <div class="flex justify-between text-slate-300">
              <span>Total Campaign Budget:</span>
              <span class="font-bold text-emerald-400">{{ Number(totalCost).toLocaleString() }} Pts</span>
            </div>
            <div class="flex justify-between text-slate-400 pt-1 border-t border-slate-800">
              <span>Remaining Balance After Launch:</span>
              <span class="font-bold" :class="remainingBalance >= 0 ? 'text-cyan-400' : 'text-rose-400'">
                {{ remainingBalance >= 0 ? formatBal(remainingBalance) + ' Pts' : 'Insufficient Points!' }}
              </span>
            </div>
          </div>

          <div v-if="errors.target_clicks" class="text-rose-400 text-xs p-3 bg-rose-500/10 rounded-xl border border-rose-500/25">
            ❌ {{ errors.target_clicks }}
          </div>
          <div v-if="errors.campaign_service_id" class="text-rose-400 text-xs p-3 bg-rose-500/10 rounded-xl border border-rose-500/25">
            ❌ {{ errors.campaign_service_id }}
          </div>
          <div v-if="errors.budget" class="text-rose-400 text-xs p-3 bg-rose-500/10 rounded-xl border border-rose-500/25">
            ❌ {{ errors.budget }}
          </div>

          <button
            type="submit"
            :disabled="submitting || totalCost > user.main_balance || !form.campaign_service_id"
            class="btn-neon w-full py-3.5 rounded-2xl text-sm font-black text-white"
            :class="(totalCost <= user.main_balance && form.campaign_service_id) ? 'btn-primary' : 'bg-slate-800 text-slate-500 cursor-not-allowed'"
          >
            <span v-if="submitting" class="flex items-center justify-center gap-2">
              <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
              Submitting...
            </span>
            <span v-else-if="!form.campaign_service_id">⚠️ Please Select a Service</span>
            <span v-else-if="totalCost > user.main_balance">⚠️ Insufficient Balance</span>
            <span v-else>🚀 Launch Campaign ({{ Number(totalCost).toLocaleString() }} pts)</span>
          </button>
        </form>
      </div>

      <!-- My Campaigns -->
      <div class="glass-card rounded-3xl border border-violet-500/15 overflow-hidden">
        <!-- Section Header -->
        <div class="flex items-center gap-3 px-4 pt-4 sm:px-6 sm:pt-5 pb-3">
          <span class="section-title">📊 My Campaigns</span>
          <div class="section-header-line"></div>
          <span class="badge badge-indigo shrink-0">Last 10</span>
        </div>

        <div class="px-4 pb-4 sm:px-6 sm:pb-5">
          <div v-if="myCampaigns.length === 0" class="text-center py-10 text-slate-500 text-sm">
            <div class="text-4xl mb-3">📢</div>
            <p class="text-sm font-bold text-white mb-1">No campaigns created yet</p>
            <p class="text-xs text-slate-500">Create your first campaign above to promote your link.</p>
          </div>

          <div v-else class="space-y-4">
            <div v-for="c in myCampaigns" :key="c.id"
              class="glass-pill p-5 rounded-2xl border border-white/5 space-y-3">
              <div class="flex items-start justify-between gap-3">
                <div>
                  <div class="text-sm font-bold text-white flex items-center gap-2 flex-wrap">
                    <span>{{ campaignIcon(c.type) }} {{ c.title }}</span>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 capitalize">
                      🛡️ {{ formatProofType(c.proof_type) }}
                    </span>
                  </div>
                  <div class="text-[10px] text-slate-400 mt-0.5" v-if="c.action">Action: <span class="text-white">{{ c.action }}</span></div>
                  <div class="text-[10px] text-slate-500 mt-0.5">{{ c.created_at }}</div>
                </div>
                <span class="badge shrink-0" :class="statusBadge(c.status)">{{ c.status }}</span>
              </div>

              <!-- Progress -->
              <div class="space-y-1.5">
                <div class="flex justify-between text-[10px] text-slate-400">
                  <span>{{ c.total_clicks }} / {{ c.target_clicks }} verified completions</span>
                  <span class="font-bold text-indigo-400">{{ c.progress }}%</span>
                </div>
                <div class="progress-track">
                  <div class="progress-fill bg-gradient-to-r from-indigo-500 to-violet-400" :style="{ width: c.progress + '%' }"></div>
                </div>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-800/80 text-[11px]">
                <div class="flex items-center gap-3">
                  <span class="text-slate-500">Budget: <strong class="text-amber-400">{{ c.budget_points }} pts</strong></span>
                  <a :href="c.target_url" target="_blank" rel="noopener noreferrer" class="text-cyan-400 hover:text-cyan-300 transition-colors">
                    🔗 Target URL →
                  </a>
                </div>

                <!-- View Proofs Button -->
                <button
                  type="button"
                  @click="openSubmissionsModal(c)"
                  class="px-3.5 py-1.5 bg-indigo-500/15 hover:bg-indigo-500/25 border border-indigo-500/30 text-indigo-300 hover:text-white rounded-xl text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer shadow-sm active:scale-95"
                >
                  <span>👁️</span>
                  <span>View Proofs</span>
                  <span v-if="c.submissions_count > 0" class="px-1.5 py-0.5 bg-indigo-500 text-white rounded-md text-[10px] font-mono font-bold">{{ c.submissions_count }}</span>
                </button>
              </div>

              <div v-if="c.admin_note" class="mt-2 text-[10px] bg-rose-500/10 border border-rose-500/20 rounded-lg p-2 text-rose-400">
                ⚠️ Admin Note: {{ c.admin_note }}
              </div>
            </div>
          </div>
            
          <div class="pt-4" v-if="myCampaigns && myCampaigns.length > 0">
            <Link href="/campaigns-history" class="w-full py-3 rounded-xl text-xs font-bold text-white flex items-center justify-center gap-2 border border-indigo-500/30 bg-indigo-500/10 hover:bg-indigo-500/20 transition-all card-hover">
              View All Campaigns ➔
            </Link>
          </div>
        </div>
      </div>

    </div>

    <!-- Submissions & Proofs Inspection Modal -->
    <Transition name="modal">
      <div v-if="selectedCampaignForProof" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
        <div class="bg-slate-900 border border-indigo-500/30 rounded-3xl p-5 sm:p-6 w-full max-w-2xl shadow-[0_0_50px_-12px_rgba(99,102,241,0.3)] max-h-[90vh] flex flex-col space-y-4">
          <div class="flex justify-between items-center border-b border-slate-800 pb-3">
            <div>
              <h3 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                <span>📋 Submissions:</span>
                <span class="text-indigo-400 truncate max-w-xs sm:max-w-md">{{ selectedCampaignForProof.title }}</span>
              </h3>
              <p class="text-xs text-slate-400 mt-0.5">
                Target: {{ selectedCampaignForProof.total_clicks }}/{{ selectedCampaignForProof.target_clicks }} verified completions
              </p>
            </div>
            <button @click="selectedCampaignForProof = null" class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 transition-colors">✕</button>
          </div>

          <!-- Loading state -->
          <div v-if="loadingSubmissions" class="py-12 flex flex-col items-center justify-center space-y-2 text-indigo-400">
            <svg class="animate-spin h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            <span class="text-xs text-slate-400">Loading submitted proofs...</span>
          </div>

          <!-- Empty state -->
          <div v-else-if="campaignSubmissions.length === 0" class="py-12 text-center text-slate-500 text-xs">
            <div class="text-3xl mb-2">📭</div>
            <p>No worker submissions received yet.</p>
            <p class="text-[11px] text-slate-600 mt-1">Once approved by Admin, workers' proofs will appear here.</p>
          </div>

          <!-- Submissions list -->
          <div v-else class="space-y-3 overflow-y-auto pr-1 flex-grow">
            <div v-for="sub in campaignSubmissions" :key="sub.id" class="bg-slate-950/70 border border-slate-800 rounded-2xl p-3.5 space-y-2.5 text-xs">
              <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-white">{{ sub.user_name }}</span>
                  <span class="text-slate-500 font-mono text-[10px]">({{ sub.user_phone }})</span>
                </div>
                <div class="flex items-center gap-2">
                  <span v-if="sub.status === 'approved'" class="px-2 py-0.5 bg-emerald-500/20 text-emerald-400 rounded-md text-[10px] font-bold">Approved ✅</span>
                  <span v-else-if="sub.status === 'pending'" class="px-2 py-0.5 bg-amber-500/20 text-amber-400 rounded-md text-[10px] font-bold">Admin Reviewing ⏳</span>
                  <span v-else class="px-2 py-0.5 bg-rose-500/20 text-rose-400 rounded-md text-[10px] font-bold">Rejected ❌</span>
                  <span class="text-slate-500 text-[10px]">{{ sub.submitted_at }}</span>
                </div>
              </div>

              <!-- Submitted Username / Link -->
              <div v-if="sub.submitted_data?.username_link" class="p-2.5 bg-slate-900 rounded-xl border border-slate-800/80 flex items-center justify-between gap-2">
                <span class="text-cyan-400 font-medium shrink-0">🔗 Submitted Profile:</span>
                <span class="font-mono text-white select-all break-all text-right">{{ sub.submitted_data.username_link }}</span>
              </div>

              <!-- Submitted Secret Code -->
              <div v-if="sub.submitted_data?.secret_code" class="p-2.5 bg-slate-900 rounded-xl border border-slate-800/80 flex items-center justify-between gap-2">
                <span class="text-amber-400 font-medium shrink-0">🔑 Secret Code:</span>
                <span class="font-mono text-amber-200 select-all">{{ sub.submitted_data.secret_code }}</span>
              </div>

              <!-- Submitted Screenshot Thumbnail -->
              <div v-if="sub.screenshot_path" class="flex items-center gap-2 pt-1">
                <span class="text-slate-400 shrink-0">📸 Screenshot:</span>
                <div
                  @click="previewImage = '/storage/' + sub.screenshot_path"
                  class="relative group cursor-pointer w-20 h-16 rounded-xl overflow-hidden border border-slate-700 hover:border-indigo-500 transition-colors shrink-0 shadow-md"
                >
                  <img :src="'/storage/' + sub.screenshot_path" class="w-full h-full object-cover group-hover:scale-110 transition-transform" alt="Proof" />
                  <div class="absolute inset-0 bg-indigo-900/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-xs text-white">🔍 View</div>
                </div>
              </div>
            </div>
          </div>

          <div class="pt-2 border-t border-slate-800 flex justify-end">
            <button type="button" @click="selectedCampaignForProof = null" class="px-5 py-2 bg-slate-800 hover:bg-slate-700 text-white font-bold rounded-xl text-xs transition-colors">
              Close
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Image Zoom Modal -->
    <Transition name="modal">
      <div v-if="previewImage" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/90 backdrop-blur-md" @click="previewImage = null">
        <div class="relative max-w-3xl max-h-[90vh] bg-slate-900 rounded-2xl overflow-hidden border border-slate-800 p-2" @click.stop>
          <img :src="previewImage" class="w-full h-auto max-h-[80vh] object-contain rounded-xl" alt="Proof Preview" />
          <button @click="previewImage = null" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-950/80 text-white flex items-center justify-center hover:bg-rose-600 transition-colors">✕</button>
        </div>
      </div>
    </Transition>

  </AppLayout>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import AnimatedNumber from '@/Components/AnimatedNumber.vue';

const props = defineProps({
  user:        Object,
  myCampaigns: Array,
  settings:    Object,
  services:    Array,
});

const page = usePage();
const pageProps = computed(() => page.props);

const form = reactive({
  title:               '',
  description:         '',
  target_url:          '',
  campaign_service_id: '',
  proof_type:          'screenshot',
  proof_instruction:   '',
  secret_code:         '',
  target_clicks:       100,
});

onMounted(() => {
  if (props.services && props.services.length > 0 && !form.campaign_service_id) {
    form.campaign_service_id = props.services[0].id;
  }
});

const selectedService = computed(() => {
  return props.services ? props.services.find(s => s.id === form.campaign_service_id) : null;
});

const minClicks = computed(() => {
  return selectedService.value?.min_clicks ? Number(selectedService.value.min_clicks) : 1;
});

const maxClicks = computed(() => {
  return selectedService.value?.max_clicks ? Number(selectedService.value.max_clicks) : 5000;
});

const stepClicks = computed(() => 1);

watch(selectedService, (newService) => {
  if (newService) {
    const min = Number(newService.min_clicks || 1);
    const max = Number(newService.max_clicks || 5000);
    if (!form.target_clicks || form.target_clicks < min) {
      form.target_clicks = min;
    } else if (form.target_clicks > max) {
      form.target_clicks = max;
    }
  }
});

const totalCost = computed(() => {
  if (!selectedService.value) return 0;
  return (Number(form.target_clicks) || 0) * selectedService.value.creator_cost;
});

const remainingBalance = computed(() => {
  return (props.user?.main_balance || 0) - totalCost.value;
});

const errors     = ref({});
const submitting = ref(false);

const selectedCampaignForProof = ref(null);
const campaignSubmissions = ref([]);
const loadingSubmissions = ref(false);
const previewImage = ref(null);

const formatBal = (v) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const campaignIcon = (type) => {
  const icons = { website: '🌐', telegram: '✈️', youtube: '▶️', facebook: '📘', other: '📎' };
  return icons[type?.toLowerCase()] || '📎';
};

const formatProofType = (type) => {
  const map = {
    screenshot: 'Screenshot Only',
    username_link: 'Username / Link',
    secret_code: 'Secret Code',
    screenshot_username: 'Screenshot + Username',
    screenshot_code: 'Screenshot + Code',
    username_code: 'Username + Code',
    all: 'Screenshot + Username + Code',
  };
  return map[type] || 'Screenshot';
};

const statusBadge = (status) => {
  const map = {
    pending: 'badge-amber', active: 'badge-emerald',
    paused: 'badge-violet', completed: 'badge-cyan', rejected: 'badge-rose',
  };
  return map[status] || 'badge-indigo';
};

const selectedProofReqs = ref(new Set(['screenshot']));

const toggleProofReq = (type) => {
  if (selectedProofReqs.value.has(type)) {
    // Keep at least one requirement selected
    if (selectedProofReqs.value.size > 1) {
      selectedProofReqs.value.delete(type);
      selectedProofReqs.value = new Set(selectedProofReqs.value);
    }
  } else {
    selectedProofReqs.value.add(type);
    selectedProofReqs.value = new Set(selectedProofReqs.value);
  }
};

const computedProofType = computed(() => {
  const hasScreenshot = selectedProofReqs.value.has('screenshot');
  const hasUsername   = selectedProofReqs.value.has('username_link');
  const hasCode       = selectedProofReqs.value.has('secret_code');

  if (hasScreenshot && hasUsername && hasCode) return 'all';
  if (hasScreenshot && hasUsername) return 'screenshot_username';
  if (hasScreenshot && hasCode) return 'screenshot_code';
  if (hasUsername && hasCode) return 'username_code';
  if (hasUsername) return 'username_link';
  if (hasCode) return 'secret_code';
  return 'screenshot';
});

const createCampaign = () => {
  errors.value   = {};
  submitting.value = true;
  form.proof_type = computedProofType.value;

  // Clear secret code if not required, or validate if required
  if (selectedProofReqs.value.has('secret_code')) {
    if (!form.secret_code || !form.secret_code.trim()) {
      errors.value = { secret_code: 'Please enter the expected Secret Code.' };
      submitting.value = false;
      return;
    }
  } else {
    form.secret_code = '';
  }

  router.post('/campaigns', form, {
    preserveScroll: true,
    onError: (e) => { errors.value = e; },
    onFinish: () => { submitting.value = false; },
    onSuccess: () => {
      form.title             = '';
      form.description       = '';
      form.target_url        = '';
      form.proof_instruction = '';
      form.secret_code       = '';
      selectedProofReqs.value = new Set(['screenshot']);
      if (props.services && props.services.length > 0) {
        form.campaign_service_id = props.services[0].id;
      }
      form.target_clicks = minClicks.value;
    },
  });
};

const openSubmissionsModal = async (campaign) => {
  selectedCampaignForProof.value = campaign;
  campaignSubmissions.value = [];
  loadingSubmissions.value = true;
  try {
    const res = await axios.get(`/campaigns/${campaign.id}/submissions`);
    campaignSubmissions.value = res.data.submissions || [];
  } catch (e) {
    console.error('Failed to load campaign submissions', e);
  } finally {
    loadingSubmissions.value = false;
  }
};
</script>

<style scoped>
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.3s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active > div, .modal-leave-active > div {
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.modal-enter-from > div {
  transform: scale(0.95) translateY(10px);
}
.modal-leave-to > div {
  transform: scale(0.95) translateY(10px);
}
</style>

