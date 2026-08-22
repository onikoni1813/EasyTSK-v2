<template>
  <AppLayout>
    <div class="space-y-5 animate-slide-in-up">

      <!-- Health Gate Warning Banner -->
      <div v-if="health_gate_active" class="glass-card p-4 rounded-2xl border border-rose-500/40 bg-rose-500/10 flex items-center gap-3">
        <span class="text-2xl">💔</span>
        <div class="flex-1">
          <div class="text-xs font-bold text-rose-300">Health Depleted — Proof Submissions Locked</div>
          <div class="text-[11px] text-rose-400/80">Complete Shortlink/Secret Code tasks to regain Health, or wait for it to unlock in <span class="font-mono font-bold">{{ gateCountdown }}</span>.</div>
        </div>
      </div>

      <!-- ── Task Engine Section ── -->
      <template v-if="tasks.length > 0">
        <!-- Header -->
      <div class="glass-card p-4 sm:p-6 rounded-3xl border border-indigo-500/15 relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-36 h-36 bg-indigo-500/8 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col gap-3">
          <!-- Title row -->
          <div class="flex items-start justify-between gap-3">
            <div>
              <div class="badge badge-indigo mb-1.5">⚡ Task Engine</div>
              <h1 class="text-xl sm:text-2xl font-black text-white">Earn Center</h1>
              <p class="text-[11px] text-slate-400 mt-0.5">Shortlinks · Secret Codes · Social Proofs · Offerwalls</p>
            </div>
            <!-- Health indicator (top-right on mobile) -->
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl border shrink-0" :class="health_gate_active ? 'bg-rose-500/20 border-rose-500/40 animate-pulse-neon' : 'bg-rose-500/10 border-rose-500/20'">
              <span class="text-rose-400 text-base">❤️</span>
              <div>
                <div class="text-[9px] text-slate-400 font-bold uppercase tracking-wider">HP</div>
                <div class="text-sm font-black text-white leading-none">{{ $page.props.auth.user.health }}</div>
              </div>
            </div>
          </div>
          <!-- Filter badges row -->
          <div class="flex gap-1.5 flex-wrap">
            <div v-for="cat in categories" :key="cat.key"
              @click="activeFilter = cat.key"
              class="badge cursor-pointer transition-all"
              :class="activeFilter === cat.key ? cat.activeCls : 'badge-indigo opacity-50 hover:opacity-80'"
            >
              {{ cat.icon }} {{ cat.label }}
            </div>
          </div>
        </div>
      </div>

      <!-- No tasks -->
      <div v-if="filteredTasks.length === 0" class="glass-card p-10 rounded-3xl border border-slate-800 text-center">
        <div class="text-4xl mb-3">📭</div>
        <p class="text-sm text-slate-400">No {{ activeFilter !== 'all' ? activeFilter.replace('_', ' ') : '' }} tasks available right now.</p>
      </div>

      <!-- Task Cards Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-for="task in filteredTasks" :key="task.id"
          class="glass-card rounded-3xl border card-hover relative overflow-hidden"
          :class="taskBorderClass(task.type)"
        >
          <!-- Glow accent -->
          <div class="absolute top-0 right-0 w-20 h-20 rounded-full blur-2xl opacity-30 pointer-events-none"
            :class="taskGlowClass(task.type)"></div>

          <div class="p-5 relative z-10 space-y-4">
            <!-- Task Header -->
            <div class="flex justify-between items-start gap-3">
              <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                  <span class="badge" :class="taskBadgeClass(task.type)">
                    {{ taskIcon(task.type) }} {{ task.type.replace('_', ' ') }}
                  </span>
                  <span v-if="task.is_limited" class="badge badge-rose animate-pulse-neon">⏰ Limited</span>
                </div>
                <h3 class="text-sm font-bold text-white">{{ task.title }}</h3>
                <p class="text-[11px] text-slate-500 mt-0.5 line-clamp-2">{{ task.description || 'Complete the task to earn your reward.' }}</p>
              </div>
              <div class="text-right shrink-0">
                <div class="text-lg font-black text-emerald-400 stat-number neon-text-emerald">+{{ task.reward_coins }}</div>
                <div class="text-[10px] text-violet-400 font-bold">+{{ task.reward_xp }} XP</div>
              </div>
            </div>

            <!-- Rejected Task Notice -->
            <div v-if="task.user_status === 'rejected'" class="p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl mt-3 flex items-start gap-2">
              <span class="text-rose-400 text-sm">⚠️</span>
              <div>
                <h4 class="text-xs font-bold text-rose-400 uppercase tracking-wider mb-0.5">Task Rejected</h4>
                <p class="text-[11px] text-rose-200">{{ task.admin_note || 'No specific reason provided.' }}</p>
                <p class="text-[10px] text-rose-500 font-bold mt-1">Health Decreased.</p>
              </div>
            </div>

            <!-- Pending Task Notice -->
            <div v-if="task.user_status === 'pending'" class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl mt-3 text-center">
              <span class="text-amber-400 text-xs font-bold">⏳ Awaiting Admin Review</span>
            </div>

            <!-- Shortlink / Custom / Social Proof / Secret Code -->
            <div v-if="['shortlink', 'social', 'secret_code'].includes(task.type) && task.user_status !== 'pending'" class="space-y-2 mt-3">
              <button
                @click="openCustomTaskModal(task)"
                class="btn-neon btn-emerald w-full py-2.5 rounded-xl text-xs font-bold text-white flex items-center justify-center gap-2"
              >
                <span>📝</span> View Instructions & Submit
              </button>
            </div>
          </div>
        </div>
      </div>

      </template>

      <!-- ── Offerwall Hub ──────────────────────────────────────────────── -->
      <div id="offerwall" class="ow-hub-wrapper glass-card rounded-3xl border border-cyan-500/15 overflow-hidden">

        <!-- Section header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 px-4 pt-4 sm:px-6 sm:pt-5 pb-3">
          <div class="flex items-center gap-3">
            <span class="section-title">🏆 Offerwall Hub</span>
            <span class="badge badge-cyan shrink-0">{{ offerwalls.length }} Networks</span>
            <span v-if="is_locked" class="badge badge-rose shrink-0 animate-pulse-neon">🔒 Locked</span>
          </div>

          <!-- Tab Switcher (Networks / My History) -->
          <div class="flex items-center gap-1.5 p-1 bg-slate-900/60 border border-white/10 rounded-2xl shrink-0 self-start sm:self-auto">
            <button 
              @click="activeOwTab = 'networks'" 
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
              :class="activeOwTab === 'networks' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'text-slate-400 hover:text-white'"
            >
              🌐 Networks
            </button>
            <button 
              @click="activeOwTab = 'history'" 
              class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
              :class="activeOwTab === 'history' ? 'bg-cyan-500 text-white shadow-lg shadow-cyan-500/25' : 'text-slate-400 hover:text-white'"
            >
              📊 History & Stats
              <span v-if="offerwallStats?.pending_amount > 0" class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
            </button>
          </div>
        </div>

        <!-- ── TAB 1: NETWORKS ── -->
        <div v-if="activeOwTab === 'networks'" class="ow-cards-area px-4 pb-4 sm:px-6 sm:pb-5">
          <!-- Offerwall grid (always rendered) -->
          <div class="ow-grid">
            <div
              v-for="ow in offerwalls" :key="ow.id"
              @click="openOfferwall(ow)"
              class="glass-pill rounded-2xl border ow-card group"
              :class="is_locked ? 'border-white/5 cursor-not-allowed' : 'border-white/5 card-hover cursor-pointer'"
            >
              <div class="ow-icon" :class="!is_locked && 'group-hover:scale-110'">
                <img v-if="ow.image_url && !ow.image_error" :src="ow.image_url" :alt="ow.name" style="width:100%;height:100%;object-fit:contain;" @error="ow.image_error = true">
                <span v-else class="ow-initial">{{ ow.name.charAt(0) }}</span>
              </div>
              <div class="ow-name">{{ ow.name }}</div>
              <div class="ow-ratio">x{{ ow.reward_ratio }}</div>
            </div>
          </div>

          <!-- Locked overlay — sits on top of the grid -->
          <Transition name="lock-fade">
            <div v-if="is_locked" class="ow-locked-overlay">
              <div class="ow-locked-card">
                <div class="ow-lock-ring">
                  <div class="ow-lock-ring-inner">
                    <span class="ow-lock-icon">🔒</span>
                  </div>
                </div>
                <h3 class="ow-locked-title">Hub Locked</h3>
                <p class="ow-locked-desc">
                  Complete all tasks first.
                  <span class="ow-locked-count">{{ pending_tasks_count }} task(s)</span> remaining.
                </p>
              </div>
            </div>
          </Transition>
        </div>

        <!-- ── TAB 2: HISTORY & STATS ── -->
        <div v-else class="px-4 pb-5 sm:px-6 space-y-4">
          <!-- User Offerwall Stats Cards Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-3.5 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-lg">💰</div>
              <div>
                <div class="text-[10px] font-bold text-cyan-400/80 uppercase tracking-wider">Total Earned</div>
                <div class="text-base font-black text-white">+{{ offerwallStats?.total_earned || 0 }} <span class="text-[10px] text-cyan-300 font-normal">Coins</span></div>
              </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/20 flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-amber-500/20 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg">⏳</div>
              <div>
                <div class="text-[10px] font-bold text-amber-400/80 uppercase tracking-wider">Pending Hold</div>
                <div class="text-base font-black text-white">{{ offerwallStats?.pending_amount || 0 }} <span class="text-[10px] text-amber-300 font-normal">Coins</span></div>
              </div>
            </div>

            <div class="p-3.5 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-lg">✅</div>
              <div>
                <div class="text-[10px] font-bold text-emerald-400/80 uppercase tracking-wider">Offers Completed</div>
                <div class="text-base font-black text-white">{{ offerwallStats?.completed_count || 0 }} <span class="text-[10px] text-emerald-300 font-normal">Offers</span></div>
              </div>
            </div>
          </div>

          <!-- Offerwall Logs Table -->
          <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/40">
            <div v-if="!offerwallLogs || offerwallLogs.length === 0" class="p-8 text-center text-slate-500 text-xs">
              <span class="text-2xl block mb-1.5">📜</span>
              No offerwall conversions recorded yet. Complete offers from any network to see your rewards here!
            </div>
            <div v-else class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-white/5 text-slate-400 uppercase text-[10px] font-bold tracking-wider border-b border-white/10">
                  <tr>
                    <th class="px-4 py-2.5">Provider</th>
                    <th class="px-4 py-2.5">Transaction ID</th>
                    <th class="px-4 py-2.5">Coins</th>
                    <th class="px-4 py-2.5">Status</th>
                    <th class="px-4 py-2.5">Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-slate-300">
                  <tr v-for="log in offerwallLogs" :key="log.id" class="hover:bg-white/[0.02]">
                    <td class="px-4 py-3 font-bold text-white flex items-center gap-2">
                      <span class="w-2 h-2 rounded-full" :class="log.status === 'approved' ? 'bg-emerald-400' : log.status === 'pending' ? 'bg-amber-400' : 'bg-rose-400'"></span>
                      {{ log.provider }}
                    </td>
                    <td class="px-4 py-3 font-mono text-[11px] text-slate-400 truncate max-w-[140px]">{{ log.transaction_id }}</td>
                    <td class="px-4 py-3 font-black text-emerald-400">+{{ log.amount }}</td>
                    <td class="px-4 py-3">
                      <span v-if="log.status === 'approved'" class="badge badge-emerald">Approved</span>
                      <span v-else-if="log.status === 'pending'" class="badge badge-amber" title="In hold period">Pending (Hold)</span>
                      <span v-else class="badge badge-rose">Reversed</span>
                    </td>
                    <td class="px-4 py-3 text-[11px] text-slate-400 whitespace-nowrap">{{ log.created_at }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <p class="text-[10px] text-slate-600 px-4 pb-3 sm:px-6 sm:pb-4 text-center leading-relaxed">
          Points from offerwalls are held in Pending Balance for {{ offerwallPendingHours }} hours before release into Main Balance.
        </p>
      </div>

      <!-- ═══════════ Premium Task Modal ═══════════ -->
      <Teleport to="body">
        <Transition name="modal">
          <div v-if="activeCustomTask" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center" @click.self="closeCustomTaskModal">
            <!-- Backdrop -->
            <div class="absolute inset-0 modal-backdrop"></div>

            <!-- Modal Card -->
            <div class="modal-card relative z-10 w-full max-w-[440px] mx-4 mb-0 sm:mb-0 max-h-[90vh] flex flex-col">
              <!-- Gradient border wrapper -->
              <div class="modal-border-glow rounded-t-[28px] sm:rounded-[28px] p-[1px]">
                <div class="bg-[#0a0e1a] rounded-t-[28px] sm:rounded-[28px] overflow-hidden flex flex-col max-h-[90vh]">

                  <!-- ── Header with gradient ── -->
                  <div class="relative px-6 pt-6 pb-5 overflow-hidden shrink-0">
                    <div class="absolute inset-0 modal-header-gradient"></div>
                    <div class="absolute -right-12 -top-12 w-32 h-32 bg-emerald-400/15 rounded-full blur-3xl"></div>
                    <div class="absolute -left-8 -bottom-8 w-24 h-24 bg-cyan-400/10 rounded-full blur-2xl"></div>

                    <div class="relative z-10 flex justify-between items-start">
                      <div class="flex-1 pr-4">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/15 border border-emerald-500/25 mb-3">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                          <span class="text-[10px] font-bold text-emerald-400 uppercase tracking-wider">{{ taskIcon(activeCustomTask.type) }} {{ activeCustomTask.type.replace('_', ' ') }}</span>
                        </div>
                        <h3 class="text-[17px] font-black text-white leading-tight">{{ activeCustomTask.title }}</h3>
                      </div>

                      <!-- Close + Reward -->
                      <div class="flex flex-col items-end gap-2">
                        <button @click="closeCustomTaskModal" class="w-7 h-7 flex items-center justify-center rounded-full bg-white/5 hover:bg-white/15 text-slate-400 hover:text-white transition-all text-xs">
                          ✕
                        </button>
                        <div class="text-right">
                          <div class="text-base font-black text-emerald-400 leading-none">+{{ activeCustomTask.reward_coins }}</div>
                          <div class="text-[9px] text-violet-400 font-bold mt-0.5">+{{ activeCustomTask.reward_xp }} XP</div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- ── Scrollable body ── -->
                  <div class="overflow-y-auto overscroll-contain px-6 pb-6 space-y-4 modal-scroll">

                    <!-- Step 1: Instructions -->
                    <div class="relative">
                      <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-[10px] font-black text-white shrink-0 shadow-lg shadow-cyan-500/25">1</div>
                        <span class="text-xs font-bold text-white uppercase tracking-wider">Read Instructions</span>
                      </div>
                      <div class="ml-8 p-4 rounded-2xl bg-white/[0.03] border border-white/[0.06] hover:border-white/10 transition-colors">
                        <div v-if="activeCustomTask.description" class="text-[13px] text-slate-300/90 instruction-content leading-relaxed" v-html="activeCustomTask.description"></div>
                        <div v-else class="text-[13px] text-slate-500 italic">No specific instructions provided.</div>

                        <div v-if="activeCustomTask.image_url" class="mt-4">
                          <img :src="activeCustomTask.image_url" alt="Task Image" class="w-full max-h-64 object-contain rounded-xl border border-white/10 bg-black/20" />
                        </div>

                        <a v-if="activeCustomTask.target_url"
                          :href="activeCustomTask.target_url" target="_blank" rel="noopener noreferrer"
                          class="mt-3 inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-xs font-bold text-cyan-400 hover:bg-cyan-500/20 hover:border-cyan-500/40 transition-all group"
                        >
                          <svg class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                          </svg>
                          Open Task Link
                        </a>
                      </div>
                    </div>

                    <!-- Divider -->
                    <div class="flex items-center gap-3 mx-8">
                      <div class="flex-1 h-px bg-gradient-to-r from-transparent via-white/10 to-transparent"></div>
                    </div>

                    <!-- Step 2: Submit Proof -->
                    <div class="relative">
                      <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center text-[10px] font-black text-white shrink-0 shadow-lg shadow-emerald-500/25">2</div>
                        <span class="text-xs font-bold text-white uppercase tracking-wider">Submit Proof</span>
                      </div>

                      <div class="ml-8 space-y-3">
                        <!-- Dynamic Proofs -->
                        <template v-if="activeCustomTask.proof_requirements && activeCustomTask.proof_requirements.length > 0">
                          <div v-for="(req, idx) in activeCustomTask.proof_requirements" :key="req.id" class="proof-field-card p-3.5 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                            <!-- Text Proof -->
                            <div v-if="req.type === 'text'">
                              <label class="flex items-center gap-2 text-xs font-bold text-slate-300 mb-2">
                                <span class="w-5 h-5 rounded-md bg-amber-500/15 flex items-center justify-center text-[10px]">✏️</span>
                                {{ req.label }}
                                <span v-if="req.is_required" class="text-[9px] text-rose-400 font-bold bg-rose-500/10 px-1.5 py-0.5 rounded">REQUIRED</span>
                                <span v-else class="text-[9px] text-slate-600 font-medium">Optional</span>
                              </label>
                              <textarea
                                v-model="customTaskProofs[req.id]"
                                rows="2"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] focus:border-emerald-500/40 focus:bg-white/[0.06] text-xs text-white placeholder-slate-600 outline-none resize-none transition-all"
                                :placeholder="'Enter ' + req.label + '...'"
                              ></textarea>
                            </div>

                            <!-- Image Proof -->
                            <div v-if="req.type === 'image'">
                              <label class="flex items-center gap-2 text-xs font-bold text-slate-300 mb-2">
                                <span class="w-5 h-5 rounded-md bg-violet-500/15 flex items-center justify-center text-[10px]">📷</span>
                                {{ req.label }}
                                <span v-if="req.is_required" class="text-[9px] text-rose-400 font-bold bg-rose-500/10 px-1.5 py-0.5 rounded">REQUIRED</span>
                                <span v-else class="text-[9px] text-slate-600 font-medium">Optional</span>
                              </label>
                              <label
                                :for="'file-' + req.id"
                                class="flex items-center gap-3 cursor-pointer rounded-xl border-2 border-dashed border-white/[0.08] hover:border-emerald-500/30 px-4 py-3 transition-all group"
                                :class="customTaskProofs[req.id] ? 'border-emerald-500/30 bg-emerald-500/[0.04]' : ''"
                              >
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-colors">
                                  <span v-if="!customTaskProofs[req.id]" class="text-emerald-400 text-sm">⬆</span>
                                  <span v-else class="text-emerald-400 text-sm">✓</span>
                                </div>
                                <span class="text-xs flex-1 truncate" :class="customTaskProofs[req.id] ? 'text-emerald-300 font-semibold' : 'text-slate-500'">
                                  {{ customTaskProofs[req.id] ? customTaskProofs[req.id].name : 'Tap to upload screenshot...' }}
                                </span>
                              </label>
                              <input :id="'file-' + req.id" type="file" accept="image/*" @change="(e) => handleDynamicFileSelect(e, req.id)" class="hidden" />
                            </div>
                          </div>
                        </template>

                        <!-- Fallback Legacy Proofs -->
                        <template v-else>
                          <!-- Secret Code Multiple Inputs -->
                          <div v-if="activeCustomTask.type === 'secret_code'" class="space-y-3">
                            <div v-for="idx in (activeCustomTask.secret_code_count || 1)" :key="idx" class="proof-field-card p-3.5 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                              <label class="flex items-center gap-2 text-xs font-bold text-slate-300 mb-2">
                                <span class="w-5 h-5 rounded-md bg-amber-500/15 flex items-center justify-center text-[10px]">🔑</span>
                                Secret Code #{{ idx }}
                              </label>
                              <input
                                v-model="customSecretCodes[idx - 1]"
                                type="text"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] focus:border-emerald-500/40 focus:bg-white/[0.06] text-xs text-white placeholder-slate-600 outline-none transition-all"
                                placeholder="Enter secret code..."
                              />
                            </div>
                          </div>

                          <!-- Legacy Text Proof for non-secret-code -->
                          <div v-else class="proof-field-card p-3.5 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-300 mb-2">
                              <span class="w-5 h-5 rounded-md bg-amber-500/15 flex items-center justify-center text-[10px]">✏️</span>
                              Text Proof
                            </label>
                            <textarea
                              v-model="customTaskText"
                              rows="3"
                              class="w-full px-3.5 py-2.5 rounded-xl bg-white/[0.04] border border-white/[0.08] focus:border-emerald-500/40 focus:bg-white/[0.06] text-xs text-white placeholder-slate-600 outline-none resize-none transition-all"
                              placeholder="Enter completion code, your username, or required text proof..."
                            ></textarea>
                          </div>

                          <div v-if="activeCustomTask.type !== 'secret_code'" class="proof-field-card p-3.5 rounded-2xl bg-white/[0.02] border border-white/[0.06]">
                            <label class="flex items-center gap-2 text-xs font-bold text-slate-300 mb-2">
                              <span class="w-5 h-5 rounded-md bg-violet-500/15 flex items-center justify-center text-[10px]">📷</span>
                              Screenshot <span class="text-[9px] text-slate-600 font-medium">Optional</span>
                            </label>
                            <label
                              for="custom-file"
                              class="flex items-center gap-3 cursor-pointer rounded-xl border-2 border-dashed border-white/[0.08] hover:border-emerald-500/30 px-4 py-3 transition-all group"
                              :class="customTaskFile ? 'border-emerald-500/30 bg-emerald-500/[0.04]' : ''"
                            >
                              <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center group-hover:bg-emerald-500/20 transition-colors">
                                <span v-if="!customTaskFile" class="text-emerald-400 text-sm">⬆</span>
                                <span v-else class="text-emerald-400 text-sm">✓</span>
                              </div>
                              <span class="text-xs flex-1 truncate" :class="customTaskFile ? 'text-emerald-300 font-semibold' : 'text-slate-500'">
                                {{ customTaskFile ? customTaskFile.name : 'Tap to upload screenshot...' }}
                              </span>
                            </label>
                            <input id="custom-file" type="file" accept="image/*" @change="handleCustomFileSelect" class="hidden" />
                          </div>
                        </template>
                      </div>
                    </div>

                    <!-- Error message -->
                    <div v-if="customTaskError" class="ml-8 flex items-start gap-2 p-3 rounded-xl bg-rose-500/10 border border-rose-500/20">
                      <span class="text-rose-400 text-xs shrink-0 mt-0.5">⚠</span>
                      <p class="text-xs text-rose-300 font-medium">{{ customTaskError }}</p>
                    </div>

                    <!-- Submit Button -->
                    <div class="ml-8 pt-1">
                      <button
                        @click="submitCustomProof"
                        :disabled="isSubmittingProof"
                        class="submit-btn w-full py-3.5 rounded-2xl text-sm font-black text-white disabled:opacity-40 disabled:cursor-not-allowed transition-all"
                      >
                        <span class="relative z-10 flex items-center justify-center gap-2">
                          <svg v-if="isSubmittingProof" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                          </svg>
                          {{ isSubmittingProof ? 'Submitting...' : '🚀 Submit & Claim Reward' }}
                        </span>
                      </button>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>

      <!-- ── Task History ──────────────────────────────────────────────── -->
      <div class="glass-card rounded-3xl border border-violet-500/15 overflow-hidden">

        <!-- Section Header -->
        <div class="flex items-center gap-3 px-4 pt-4 sm:px-6 sm:pt-5 pb-3">
          <span class="section-title">📋 Task History</span>
          <div class="section-header-line"></div>
          <span class="badge badge-indigo shrink-0">Last 5</span>
        </div>

        <div class="px-4 pb-4 sm:px-6 sm:pb-5">

          <!-- Empty State -->
          <div v-if="!taskHistory || taskHistory.length === 0" class="text-center py-10">
            <div class="text-4xl mb-3">📭</div>
            <p class="text-sm font-bold text-white mb-1">No task history yet</p>
            <p class="text-xs text-slate-500">Complete some tasks above to see your history here.</p>
          </div>

          <!-- History List -->
          <div v-else class="space-y-2">
            <div v-for="item in taskHistory" :key="item.id"
              class="flex items-center gap-3 p-3 rounded-2xl bg-white/[0.02] border border-white/[0.05] hover:border-violet-500/20 transition-all"
            >
              <!-- Type Icon -->
              <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base shrink-0"
                :class="{
                  'bg-indigo-500/15 border border-indigo-500/20': item.task_type === 'shortlink',
                  'bg-amber-500/15 border border-amber-500/20': item.task_type === 'secret_code',
                  'bg-emerald-500/15 border border-emerald-500/20': item.task_type === 'social',
                }"
              >
                {{ taskIcon(item.task_type) }}
              </div>

              <!-- Task Info -->
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                  <span class="text-xs font-bold text-white truncate">{{ item.task_title }}</span>
                  <!-- Status Badge -->
                  <span class="shrink-0 px-1.5 py-0.5 rounded text-[9px] font-bold uppercase"
                    :class="{
                      'bg-amber-500/20 text-amber-400 border border-amber-500/25': item.status === 'pending',
                      'bg-emerald-500/20 text-emerald-400 border border-emerald-500/25': item.status === 'approved',
                      'bg-rose-500/20 text-rose-400 border border-rose-500/25': item.status === 'rejected',
                    }"
                  >
                    {{ item.status === 'pending' ? '⏳ Review' : item.status === 'approved' ? '✅ Approved' : '❌ Rejected' }}
                  </span>
                </div>
                <div class="text-[10px] text-slate-500">{{ item.submitted_at }}</div>
                <!-- Admin Note for Rejected -->
                <div v-if="item.status === 'rejected' && item.admin_note"
                  class="mt-1 text-[10px] text-rose-400 bg-rose-500/10 px-2 py-1 rounded-lg border border-rose-500/15"
                >
                  ⚠️ {{ item.admin_note }}
                </div>
              </div>

              <!-- Reward -->
              <div class="text-right shrink-0">
                <div class="text-xs font-black"
                  :class="item.status === 'approved' ? 'text-emerald-400' : 'text-slate-500'"
                >+{{ item.reward_coins }}</div>
                <div class="text-[9px] text-slate-600">pts</div>
              </div>
            </div>
          </div>
          
          <div class="pt-4" v-if="taskHistory && taskHistory.length > 0">
            <Link href="/tasks-history" class="w-full py-3 rounded-xl text-xs font-bold text-white flex items-center justify-center gap-2 border border-indigo-500/30 bg-indigo-500/10 hover:bg-indigo-500/20 transition-all card-hover">
              View All Task ➔
            </Link>
          </div>

        </div>
      </div>



    </div><!-- END space-y-5 wrapper -->

    <!-- Fullscreen / Responsive Iframe Modal for Offerwalls -->
    <Teleport to="body">
      <div v-if="isIframeOpen" class="fixed inset-0 z-[100] bg-slate-950/90 backdrop-blur-md flex flex-col overflow-hidden">
        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 bg-slate-900 border-b border-cyan-500/20 shrink-0">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center text-cyan-400 font-bold overflow-hidden">
              <img v-if="activeOfferwall?.image_url" :src="activeOfferwall.image_url" :alt="activeOfferwall?.name" class="w-full h-full object-contain p-1" />
              <span v-else>{{ activeOfferwall?.name?.charAt(0) || '🏆' }}</span>
            </div>
            <div>
              <h3 class="text-sm font-bold text-white leading-none flex items-center gap-2">
                {{ activeOfferwall?.name || 'Offerwall Network' }}
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
              </h3>
              <p class="text-[10px] text-cyan-400 font-medium mt-0.5">Ratio: x{{ activeOfferwall?.reward_ratio || 1.0 }} · Complete tasks to earn points</p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button 
              @click="closeIframe" 
              class="bg-rose-500/20 text-rose-400 hover:bg-rose-500 hover:text-white px-3 py-1.5 rounded-xl flex items-center justify-center transition-all text-xs font-bold gap-1 border border-rose-500/30"
            >
              ✕ Close
            </button>
          </div>
        </div>

        <!-- Body / Iframe -->
        <div class="flex-1 bg-slate-950 relative w-full h-full overflow-hidden">
          <div v-if="!iframeLoaded" class="absolute inset-0 p-6 flex flex-col items-center justify-center gap-3 bg-slate-950/90 z-20">
            <div class="w-10 h-10 border-3 border-cyan-500/30 border-t-cyan-400 rounded-full animate-spin"></div>
            <p class="text-xs text-cyan-300 font-bold animate-pulse">Loading {{ activeOfferwall?.name || 'Offerwall' }}...</p>
          </div>
          <iframe 
            v-if="activeIframeUrl" 
            :src="activeIframeUrl" 
            class="absolute top-0 left-0 w-full h-full border-none z-10 bg-white" 
            allow="camera; microphone; clipboard-write; encrypted-media" 
            @load="iframeLoaded = true"
          ></iframe>
        </div>
      </div>
    </Teleport>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SkeletonBlock from '@/Components/SkeletonBlock.vue';

const props = defineProps({
  tasks:     Array,
  userLevel: Number,
  offerwalls: Array,
  is_locked: Boolean,
  pending_tasks_count: Number,
  health_gate_active: Boolean,
  health_gate_expires_at: String,
  taskHistory: Array,
  offerwallPendingHours: Number,
  offerwallLogs: { type: Array, default: () => [] },
  offerwallStats: { type: Object, default: () => ({ total_earned: 0, pending_amount: 0, completed_count: 0 }) },
});

const gateCountdown = ref('--:--:--');
let gateTimerInterval = null;

const updateGateCountdown = () => {
  if (!props.health_gate_expires_at) return;
  const remainingMs = new Date(props.health_gate_expires_at).getTime() - Date.now();
  if (remainingMs <= 0) {
    gateCountdown.value = '00:00:00';
    clearInterval(gateTimerInterval);
    return;
  }
  const totalSeconds = Math.floor(remainingMs / 1000);
  const h = Math.floor(totalSeconds / 3600);
  const m = Math.floor((totalSeconds % 3600) / 60);
  const s = totalSeconds % 60;
  gateCountdown.value = `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
};

onMounted(() => {
  if (props.health_gate_active) {
    updateGateCountdown();
    gateTimerInterval = setInterval(updateGateCountdown, 1000);
  }
  if (window.location.hash === '#offerwall') {
    setTimeout(() => {
      const el = document.getElementById('offerwall');
      if (el) {
        el.scrollIntoView({ behavior: 'smooth' });
      }
    }, 150);
  }
});

onUnmounted(() => {
  clearInterval(gateTimerInterval);
});

const activeOwTab = ref('networks');
const activeFilter = ref('all');
const activeCustomTask = ref(null);
const customTaskText = ref('');
const customSecretCodes = ref([]);
const customTaskFile = ref(null);
const customTaskProofs = ref({});
const customTaskError = ref('');
const isSubmittingProof = ref(false);

const categories = [
  { key: 'all',         label: 'All',       icon: '⚡', activeCls: 'badge-indigo' },
  { key: 'shortlink',   label: 'Shortlink', icon: '🔗', activeCls: 'badge-cyan'   },
  { key: 'secret_code', label: 'Code',      icon: '🔑', activeCls: 'badge-amber'  },
  { key: 'social',      label: 'Custom',    icon: '📝', activeCls: 'badge-emerald'},
];

const filteredTasks = computed(() =>
  activeFilter.value === 'all'
    ? props.tasks
    : props.tasks.filter(t => t.type === activeFilter.value)
);

const activeOfferwall = ref(null);
const activeIframeUrl = ref(null);
const isIframeOpen = ref(false);
const iframeLoaded = ref(false);

const openOfferwall = (ow) => {
  if (props.is_locked) return;
  const user = usePage().props.auth.user;
  activeOfferwall.value = ow;
  let url = ow.iframe_url_pattern || '';
  if (user) {
    url = url
      .replace(/{user_id}/gi, user.id)
      .replace(/{uid}/gi, user.id)
      .replace(/{sub_id}/gi, user.id)
      .replace(/{sub_id1}/gi, user.id)
      .replace(/{userID}/gi, user.id)
      .replace(/{id}/gi, user.id)
      .replace(/{username}/gi, encodeURIComponent(user.name || ''))
      .replace(/{email}/gi, encodeURIComponent(user.email || ''));
  }
  iframeLoaded.value = false;
  activeIframeUrl.value = url;
  isIframeOpen.value = true;
};

const closeIframe = () => {
  isIframeOpen.value = false;
  activeIframeUrl.value = null;
  activeOfferwall.value = null;
  iframeLoaded.value = false;
};

const taskIcon = (type) => {
  const icons = { shortlink: '🔗', secret_code: '🔑', social: '📝' };
  return icons[type] || '⚡';
};
const taskBadgeClass = (type) => {
  const map = { shortlink: 'badge-indigo', secret_code: 'badge-amber', social: 'badge-emerald' };
  return map[type] || 'badge-indigo';
};
const taskBorderClass = (type) => {
  const map = { shortlink: 'border-indigo-500/15', secret_code: 'border-amber-500/15', social: 'border-emerald-500/15' };
  return map[type] || 'border-slate-800/50';
};
const taskGlowClass = (type) => {
  const map = { shortlink: 'bg-indigo-500', secret_code: 'bg-amber-500', social: 'bg-emerald-500' };
  return map[type] || 'bg-slate-500';
};

const openCustomTaskModal = (task) => {
  activeCustomTask.value = task;
  customTaskText.value   = '';
  customSecretCodes.value = [];
  customTaskFile.value   = null;
  customTaskProofs.value = {};
  customTaskError.value  = '';
};

const closeCustomTaskModal = () => {
  activeCustomTask.value = null;
};

const handleCustomFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) customTaskFile.value = file;
};

const handleDynamicFileSelect = (event, reqId) => {
  const file = event.target.files[0];
  if (file) customTaskProofs.value[reqId] = file;
};

const submitCustomProof = () => {
  const task = activeCustomTask.value;
  const reqs = task.proof_requirements;

  isSubmittingProof.value = true;
  customTaskError.value   = '';

  const fd = new FormData();

  // ── Dynamic proof requirements path ──
  if (reqs && reqs.length > 0) {
    // Validate required fields
    for (const req of reqs) {
      if (!req.is_required) continue;
      const val = customTaskProofs.value[req.id];
      if (req.type === 'text' && (!val || !val.trim())) {
        customTaskError.value = `"${req.label}" is required.`;
        isSubmittingProof.value = false;
        return;
      }
      if (req.type === 'image' && !val) {
        customTaskError.value = `"${req.label}" screenshot is required.`;
        isSubmittingProof.value = false;
        return;
      }
    }

    // Build FormData from dynamic proofs
    for (const req of reqs) {
      const val = customTaskProofs.value[req.id];
      if (!val) continue;
      if (req.type === 'text') {
        fd.append(`proofs[${req.id}][text]`, val.trim());
      } else if (req.type === 'image') {
        const file = customTaskProofs.value[req.id];
        if (file) {
          fd.append(`proofs[${req.id}][image]`, file);
        }
      }
    }
    fd.append('is_dynamic', '1');
  } else {
    // ── Legacy fallback path ──
    if (activeCustomTask.value.type === 'secret_code') {
      const requiredCount = activeCustomTask.value.secret_code_count || 1;
      let missing = false;
      for (let i = 0; i < requiredCount; i++) {
        if (!customSecretCodes.value[i] || !customSecretCodes.value[i].trim()) {
          missing = true;
          break;
        }
      }
      if (missing) {
        customTaskError.value = 'Please provide all required secret codes.';
        isSubmittingProof.value = false;
        return;
      }
      for (let i = 0; i < requiredCount; i++) {
        fd.append('secret_codes[]', customSecretCodes.value[i].trim());
      }
    } else {
      if (!customTaskText.value.trim() && !customTaskFile.value) {
        customTaskError.value = 'Please provide either text proof or a screenshot.';
        isSubmittingProof.value = false;
        return;
      }
      if (customTaskText.value.trim()) {
        fd.append('text_proof', customTaskText.value.trim());
      }
      if (customTaskFile.value) {
        fd.append('screenshot', customTaskFile.value);
      }
    }
  }

  router.post(`/tasks/${task.id}/social-proof`, fd, {
    preserveScroll: true,
    onSuccess: () => {
      closeCustomTaskModal();
    },
    onError: (errors) => {
      customTaskError.value = errors.screenshot || errors.message || Object.values(errors)[0] || 'An error occurred while submitting.';
    },
    onFinish: () => {
      isSubmittingProof.value = false;
    }
  });
};
</script>

<style scoped>
/* ── Modal Transitions ── */
.modal-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-leave-active { transition: all 0.2s ease-in; }
.modal-enter-from   { opacity: 0; }
.modal-leave-to     { opacity: 0; }
.modal-enter-from .modal-card { transform: translateY(40px) scale(0.97); }
.modal-leave-to   .modal-card { transform: translateY(20px) scale(0.98); }

/* ── Backdrop ── */
.modal-backdrop {
  background: rgba(2, 5, 14, 0.90);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

/* ── Modal card base ── */
.modal-card {
  transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

/* ── Animated gradient border ── */
.modal-border-glow {
  background: linear-gradient(135deg,
    rgba(16, 185, 129, 0.6) 0%,
    rgba(6, 182, 212, 0.2) 40%,
    rgba(139, 92, 246, 0.3) 70%,
    rgba(16, 185, 129, 0.5) 100%
  );
  box-shadow:
    0 0 0 1px rgba(16, 185, 129, 0.08),
    0 32px 64px rgba(0, 0, 0, 0.6),
    0 0 80px rgba(16, 185, 129, 0.06);
  animation: borderPulse 4s ease-in-out infinite;
}

@keyframes borderPulse {
  0%, 100% { box-shadow: 0 0 0 1px rgba(16,185,129,0.08), 0 32px 64px rgba(0,0,0,0.6), 0 0 80px rgba(16,185,129,0.06); }
  50%       { box-shadow: 0 0 0 1px rgba(16,185,129,0.15), 0 32px 64px rgba(0,0,0,0.6), 0 0 120px rgba(16,185,129,0.12); }
}

/* ── Header gradient ── */
.modal-header-gradient {
  background: linear-gradient(
    160deg,
    rgba(16, 185, 129, 0.08) 0%,
    rgba(6, 182, 212, 0.04) 50%,
    transparent 100%
  );
}

/* ── Custom scrollbar ── */
.modal-scroll {
  scrollbar-width: thin;
  scrollbar-color: rgba(16,185,129,0.2) transparent;
}
.modal-scroll::-webkit-scrollbar { width: 4px; }
.modal-scroll::-webkit-scrollbar-track { background: transparent; }
.modal-scroll::-webkit-scrollbar-thumb { background: rgba(16,185,129,0.2); border-radius: 2px; }
.modal-scroll::-webkit-scrollbar-thumb:hover { background: rgba(16,185,129,0.35); }

/* ── Submit button ── */
.submit-btn {
  position: relative;
  background: linear-gradient(135deg, #059669 0%, #10b981 40%, #06b6d4 100%);
  box-shadow: 0 4px 24px rgba(16,185,129,0.3), 0 1px 0 rgba(255,255,255,0.1) inset;
  letter-spacing: 0.02em;
}
.submit-btn:hover:not(:disabled) {
  box-shadow: 0 6px 32px rgba(16,185,129,0.45), 0 1px 0 rgba(255,255,255,0.15) inset;
  transform: translateY(-1px);
}
.submit-btn:active:not(:disabled) {
  transform: translateY(0);
}

/* ── Instruction HTML content ── */
:deep(.instruction-content) {
  white-space: pre-wrap;
  line-height: 1.65;
  color: rgba(203, 213, 225, 0.9);
}
:deep(.instruction-content img) {
  max-width: 100%;
  height: auto;
  border-radius: 0.75rem;
  border: 1px solid rgba(255,255,255,0.08);
  margin: 0.5rem 0;
}
:deep(.instruction-content a) {
  color: #38bdf8;
  text-decoration: underline;
}
:deep(.instruction-content b),
:deep(.instruction-content strong) {
  color: #fff;
  font-weight: 700;
}

/* ── Offerwall Hub Responsive Grid ── */
.ow-hub-wrapper {
  overflow: hidden;
}

.ow-cards-area {
  position: relative;
  min-height: 220px;
  overflow: hidden;
  border-radius: 12px;
}

.ow-grid {
  display: grid;
  gap: 8px;
  grid-template-columns: repeat(2, 1fr);
}
@media (min-width: 480px) {
  .ow-grid { grid-template-columns: repeat(3, 1fr); gap: 10px; }
}
@media (min-width: 768px) {
  .ow-grid { grid-template-columns: repeat(4, 1fr); gap: 12px; }
}

.ow-card {
  padding: 12px 8px;
  text-align: center;
  transition: transform 0.2s ease, opacity 0.2s ease;
}
.ow-icon {
  width: 40px;
  height: 40px;
  margin: 0 auto 6px;
  border-radius: 10px;
  background: rgba(255,255,255,0.05);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 6px;
  transition: transform 0.2s ease;
}
.ow-initial {
  font-size: 1.1rem;
  font-weight: 800;
  color: #67e8f9;
}
.ow-name {
  font-size: 11px;
  font-weight: 700;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  padding: 0 2px;
}
.ow-ratio {
  font-size: 9px;
  color: #6ee7b7;
  font-weight: 600;
  margin-top: 2px;
}

/* ── Locked State Overlay ── */
.ow-locked-overlay {
  position: absolute;
  inset: 0;
  z-index: 10;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 16px;
  background: linear-gradient(
    135deg,
    rgba(2, 5, 14, 0.82) 0%,
    rgba(10, 5, 30, 0.78) 50%,
    rgba(2, 5, 14, 0.82) 100%
  );
  backdrop-filter: blur(8px);
  -webkit-backdrop-filter: blur(8px);
}

.ow-locked-card {
  text-align: center;
  padding: 16px 24px;
  max-width: 260px;
}

.ow-lock-ring {
  width: 64px;
  height: 64px;
  margin: 0 auto 12px;
  border-radius: 50%;
  background: conic-gradient(from 0deg, rgba(244,63,94,0.6), rgba(244,63,94,0.1), rgba(244,63,94,0.6));
  animation: lock-spin 3s linear infinite;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 24px rgba(244,63,94,0.3), 0 0 48px rgba(244,63,94,0.12);
}

.ow-lock-ring-inner {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  background: rgba(10, 6, 26, 0.95);
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(244,63,94,0.25);
}

.ow-lock-icon {
  font-size: 1.6rem;
  line-height: 1;
  filter: drop-shadow(0 0 8px rgba(244,63,94,0.6));
}

.ow-locked-title {
  font-size: 15px;
  font-weight: 900;
  color: #fff;
  letter-spacing: -0.01em;
  margin-bottom: 6px;
}

.ow-locked-desc {
  font-size: 11px;
  color: #94a3b8;
  line-height: 1.55;
}

.ow-locked-count {
  font-weight: 700;
  color: #fb7185;
}

/* ── Lock overlay animation ── */
@keyframes lock-spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

.lock-fade-enter-active { transition: opacity 0.3s ease; }
.lock-fade-leave-active { transition: opacity 0.2s ease; }
.lock-fade-enter-from,
.lock-fade-leave-to     { opacity: 0; }
</style>

