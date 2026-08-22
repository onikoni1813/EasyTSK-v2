<template>
  <AdminLayout>
    <div class="space-y-6">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-xl font-black text-white flex items-center gap-2">
            <span>🔐 Password Reset Tickets</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
              {{ stats.pending }} Pending
            </span>
          </h1>
          <p class="text-xs text-slate-400 mt-1">Manage user account recovery, security inspection, and password reset requests</p>
        </div>
      </div>

      <!-- Stats Summary Grid -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="glass-card p-4 rounded-2xl border border-slate-800 bg-slate-900/60">
          <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Total Tickets</p>
          <p class="text-xl font-black text-white mt-1">{{ stats.total }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-amber-500/30 bg-amber-500/5">
          <p class="text-[11px] font-semibold text-amber-400 uppercase tracking-wider">Pending</p>
          <p class="text-xl font-black text-amber-300 mt-1">{{ stats.pending }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-emerald-500/30 bg-emerald-500/5">
          <p class="text-[11px] font-semibold text-emerald-400 uppercase tracking-wider">Approved</p>
          <p class="text-xl font-black text-emerald-300 mt-1">{{ stats.approved }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-indigo-500/30 bg-indigo-500/5">
          <p class="text-[11px] font-semibold text-indigo-400 uppercase tracking-wider">Completed</p>
          <p class="text-xl font-black text-indigo-300 mt-1">{{ stats.completed }}</p>
        </div>
        <div class="glass-card p-4 rounded-2xl border border-rose-500/30 bg-rose-500/5 col-span-2 sm:col-span-1">
          <p class="text-[11px] font-semibold text-rose-400 uppercase tracking-wider">Rejected</p>
          <p class="text-xl font-black text-rose-300 mt-1">{{ stats.rejected }}</p>
        </div>
      </div>

      <!-- Filter Controls & Search -->
      <div class="glass-card p-4 rounded-2xl border border-slate-800 flex flex-col md:flex-row gap-3 items-center justify-between">
        <div class="flex items-center gap-2 overflow-x-auto w-full md:w-auto pb-1 md:pb-0">
          <button
            v-for="st in ['all', 'pending', 'approved', 'completed', 'rejected']"
            :key="st"
            @click="filterStatus(st)"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-semibold capitalize whitespace-nowrap transition-all',
              currentStatus === st ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-900/80 text-slate-400 hover:text-white border border-slate-800'
            ]"
          >
            {{ st }}
          </button>
        </div>

        <div class="w-full md:w-72 flex items-center gap-2">
          <input
            v-model="searchQuery"
            @keyup.enter="handleSearch"
            type="text"
            placeholder="Search phone, ticket code, user..."
            class="w-full bg-slate-900/90 border border-slate-700/80 rounded-xl px-3.5 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
          />
          <button
            @click="handleSearch"
            class="px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all"
          >
            Search
          </button>
        </div>
      </div>

      <!-- Copy Toast Notification -->
      <div
        v-if="copyToast"
        class="fixed bottom-6 right-6 z-50 px-4 py-2.5 bg-emerald-600 text-white font-semibold text-xs rounded-xl shadow-2xl flex items-center gap-2 animate-bounce"
      >
        <span>📋</span> {{ copyToast }}
      </div>

      <!-- Tickets Table -->
      <div class="glass-card rounded-2xl border border-slate-800 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-900/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                <th class="py-3.5 px-4">Ticket Code & IP</th>
                <th class="py-3.5 px-4">User Dynamic Info</th>
                <th class="py-3.5 px-4">Message / Reason</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4">Reset OTP Code</th>
                <th class="py-3.5 px-4">Submitted Date</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 text-xs">
              <tr v-if="tickets.data.length === 0">
                <td colspan="7" class="py-8 text-center text-slate-500 font-medium">
                  No password reset tickets found.
                </td>
              </tr>

              <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-900/40 transition-colors">
                <!-- Ticket Code & IP -->
                <td class="py-3.5 px-4 whitespace-nowrap space-y-1">
                  <div class="flex items-center gap-1.5">
                    <span class="font-mono font-black text-amber-400 text-sm tracking-wide">{{ ticket.ticket_code }}</span>
                    <button
                      @click="copyText(ticket.ticket_code, 'Ticket Code copied!')"
                      title="Copy Ticket Code"
                      class="text-slate-500 hover:text-amber-300 transition-colors p-0.5"
                    >
                      📋
                    </button>
                  </div>

                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span v-if="ticket.ip_address" class="text-[10px] text-slate-400 font-mono bg-slate-900 px-1.5 py-0.5 rounded border border-slate-800">
                      IP: {{ ticket.ip_address }}
                    </span>
                    <span
                      v-if="ticket.user && ticket.device_hash"
                      :class="[
                        'text-[10px] font-bold px-1.5 py-0.5 rounded border',
                        isDeviceMatched(ticket) ? 'bg-emerald-950/60 text-emerald-400 border-emerald-500/30' : 'bg-rose-950/60 text-rose-400 border-rose-500/30'
                      ]"
                    >
                      {{ isDeviceMatched(ticket) ? 'Device Match ✅' : 'Device Mismatch ⚠️' }}
                    </span>
                  </div>
                </td>

                <!-- User Dynamic Info -->
                <td class="py-3.5 px-4">
                  <div v-if="ticket.user" class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-white">{{ ticket.user.name }}</span>
                      <span class="text-[10px] font-mono px-1.5 py-0.2 bg-slate-800 text-slate-300 rounded">#{{ ticket.user.id }}</span>
                      <span v-if="ticket.user.is_banned" class="px-1.5 py-0.2 text-[9px] font-extrabold bg-rose-600/30 text-rose-300 border border-rose-500/40 rounded uppercase">
                        Banned
                      </span>
                    </div>

                    <div class="text-[11px] text-slate-400 flex items-center gap-2">
                      <span>📱 {{ ticket.phone }}</span>
                      <span v-if="ticket.user.email" class="text-indigo-300">✉️ {{ ticket.user.email }}</span>
                    </div>

                    <div class="flex items-center gap-2 text-[10px] text-slate-400 flex-wrap">
                      <span class="px-1.5 py-0.5 bg-indigo-950/60 text-indigo-300 border border-indigo-500/30 rounded font-semibold">
                        Lvl {{ ticket.user.level || 1 }}
                      </span>
                      <span class="px-1.5 py-0.5 bg-slate-900 text-emerald-400 border border-slate-800 rounded font-bold">
                        ৳{{ ticket.user.main_balance || '0.00' }}
                      </span>
                      <span
                        :class="[
                          'px-1.5 py-0.5 rounded border font-semibold',
                          parseFloat(ticket.user.risk_score || 0) > 50 ? 'bg-rose-950/60 text-rose-300 border-rose-500/30' : 'bg-slate-900 text-slate-400 border-slate-800'
                        ]"
                      >
                        Risk: {{ ticket.user.risk_score || 0 }}%
                      </span>

                      <Link
                        :href="`${adminPath}/users?search=${encodeURIComponent(ticket.phone)}`"
                        class="text-indigo-400 hover:text-indigo-300 font-bold underline text-[10px] ml-1"
                      >
                        View Profile →
                      </Link>
                    </div>
                  </div>

                  <div v-else class="text-slate-400 space-y-0.5">
                    <p class="font-bold text-white">{{ ticket.phone }}</p>
                    <span class="inline-block px-1.5 py-0.5 text-[10px] font-bold bg-rose-950/60 text-rose-400 border border-rose-500/30 rounded">
                      Unmatched User Account
                    </span>
                  </div>
                </td>

                <!-- Message / Reason -->
                <td class="py-3.5 px-4 max-w-xs">
                  <p class="text-slate-300 truncate font-medium" :title="ticket.message">{{ ticket.message || 'No note provided' }}</p>
                  <p v-if="ticket.admin_note" class="text-[10px] text-amber-300 mt-1 italic">
                    Admin Note: {{ ticket.admin_note }}
                  </p>
                </td>

                <!-- Status -->
                <td class="py-3.5 px-4 whitespace-nowrap">
                  <span
                    :class="[
                      'px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border shadow-sm',
                      ticket.status === 'pending' ? 'bg-amber-500/20 text-amber-300 border-amber-500/40 animate-pulse' : '',
                      ticket.status === 'approved' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40' : '',
                      ticket.status === 'completed' ? 'bg-indigo-500/20 text-indigo-300 border-indigo-500/40' : '',
                      ticket.status === 'rejected' ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : ''
                    ]"
                  >
                    {{ ticket.status }}
                  </span>
                </td>

                <!-- Reset OTP Code -->
                <td class="py-3.5 px-4 font-mono text-sm font-bold whitespace-nowrap">
                  <div v-if="ticket.reset_code" class="flex items-center gap-1.5">
                    <span class="bg-emerald-950/80 border border-emerald-500/40 text-emerald-300 px-2.5 py-1 rounded-lg tracking-widest text-xs font-mono shadow-inner">
                      {{ ticket.reset_code }}
                    </span>
                    <button
                      @click="copyText(ticket.reset_code, 'Reset Code copied!')"
                      title="Copy Reset Code"
                      class="text-slate-400 hover:text-emerald-300 transition-colors"
                    >
                      📋
                    </button>
                  </div>
                  <span v-else class="text-slate-600 text-xs font-sans">-</span>
                </td>

                <!-- Submitted Date -->
                <td class="py-3.5 px-4 text-[11px] text-slate-400 whitespace-nowrap">
                  {{ formatDate(ticket.created_at) }}
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-right whitespace-nowrap space-x-1.5">
                  <button
                    @click="openInspectModal(ticket)"
                    class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-indigo-300 font-bold text-xs border border-slate-700 transition-colors"
                    title="Inspect Full Details & Security"
                  >
                    👁️ Details
                  </button>

                  <template v-if="ticket.status === 'pending'">
                    <button
                      @click="openApproveModal(ticket)"
                      class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-md transition-colors"
                    >
                      Approve
                    </button>
                    <button
                      @click="openRejectModal(ticket)"
                      class="px-3 py-1.5 rounded-xl bg-rose-600/30 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 font-bold text-xs transition-colors"
                    >
                      Reject
                    </button>
                  </template>

                  <button
                    @click="openDeleteModal(ticket)"
                    class="px-2 py-1.5 rounded-xl bg-rose-950/60 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/30 font-bold text-xs transition-colors"
                    title="Delete Ticket"
                  >
                    🗑️
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="tickets.links.length > 3" class="p-4 border-t border-slate-800 flex justify-end gap-1">
          <Link
            v-for="link in tickets.links"
            :key="link.label"
            :href="link.url || '#'"
            v-html="link.label"
            :class="[
              'px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors',
              link.active ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-slate-900 border border-slate-800',
              !link.url ? 'opacity-40 cursor-not-allowed' : ''
            ]"
          />
        </div>
      </div>

      <!-- Comprehensive Inspection Modal (User & Ticket Security Inspector) -->
      <Teleport to="body">
        <div v-if="selectedTicketForInspect" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto">
          <div class="glass-card max-w-2xl w-full p-6 rounded-3xl border border-indigo-500/30 bg-slate-950 space-y-5 my-8 shadow-2xl">
            <!-- Modal Header -->
            <div class="flex justify-between items-center pb-3 border-b border-slate-800">
              <div>
                <h3 class="text-base font-black text-white flex items-center gap-2">
                  <span>🔍 Password Ticket & User Inspector</span>
                  <span class="font-mono text-amber-400 text-xs px-2 py-0.5 bg-amber-500/10 border border-amber-500/30 rounded-lg">
                    {{ selectedTicketForInspect.ticket_code }}
                  </span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">Deep inspection of user account, security tokens, and request metadata</p>
              </div>
              <button @click="selectedTicketForInspect = null" class="w-8 h-8 rounded-xl bg-slate-900 text-slate-400 hover:text-white flex items-center justify-center">✕</button>
            </div>

            <!-- Modal Content Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Section 1: User Dynamic Account Profile -->
              <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider flex items-center gap-1.5">
                  <span>👤 User Account Info</span>
                </h4>

                <div v-if="selectedTicketForInspect.user" class="space-y-2 text-xs">
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Full Name:</span>
                    <span class="font-bold text-white">{{ selectedTicketForInspect.user.name }}</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">User ID (UID):</span>
                    <span class="font-mono font-bold text-indigo-300">#{{ selectedTicketForInspect.user.id }}</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Registered Phone:</span>
                    <span class="font-mono text-white">{{ selectedTicketForInspect.phone }}</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Email Address:</span>
                    <span class="text-slate-300">{{ selectedTicketForInspect.user.email || 'N/A' }}</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Main Balance:</span>
                    <span class="font-bold text-emerald-400">৳{{ selectedTicketForInspect.user.main_balance || '0.00' }}</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Level / XP:</span>
                    <span class="text-indigo-300 font-bold">Level {{ selectedTicketForInspect.user.level || 1 }}</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Risk Score:</span>
                    <span
                      :class="[
                        'font-bold px-1.5 py-0.5 rounded text-[10px]',
                        parseFloat(selectedTicketForInspect.user.risk_score || 0) > 50 ? 'bg-rose-950 text-rose-300 border border-rose-500/30' : 'bg-emerald-950 text-emerald-300 border border-emerald-500/30'
                      ]"
                    >
                      {{ selectedTicketForInspect.user.risk_score || 0 }}%
                    </span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Account Status:</span>
                    <span
                      :class="[
                        'font-extrabold px-2 py-0.5 rounded text-[10px] uppercase',
                        selectedTicketForInspect.user.is_banned ? 'bg-rose-600 text-white' : 'bg-emerald-600/30 text-emerald-300 border border-emerald-500/30'
                      ]"
                    >
                      {{ selectedTicketForInspect.user.is_banned ? 'Banned' : 'Active' }}
                    </span>
                  </div>
                  <div class="flex justify-between py-1">
                    <span class="text-slate-400">Member Since:</span>
                    <span class="text-slate-300">{{ formatDate(selectedTicketForInspect.user.created_at) }}</span>
                  </div>
                </div>

                <div v-else class="text-xs text-rose-400 p-3 bg-rose-950/30 border border-rose-500/20 rounded-xl">
                  ⚠️ No linked user account found for phone number <strong>{{ selectedTicketForInspect.phone }}</strong>.
                </div>
              </div>

              <!-- Section 2: Ticket Security & Fingerprint -->
              <div class="p-4 rounded-2xl bg-slate-900/80 border border-slate-800 space-y-3">
                <h4 class="text-xs font-bold text-amber-400 uppercase tracking-wider flex items-center gap-1.5">
                  <span>🛡️ Ticket Security & Metadata</span>
                </h4>

                <div class="space-y-2 text-xs">
                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Current Status:</span>
                    <span
                      :class="[
                        'font-bold px-2 py-0.5 rounded text-[10px] uppercase',
                        selectedTicketForInspect.status === 'pending' ? 'bg-amber-500/20 text-amber-300' : '',
                        selectedTicketForInspect.status === 'approved' ? 'bg-emerald-500/20 text-emerald-300' : '',
                        selectedTicketForInspect.status === 'completed' ? 'bg-indigo-500/20 text-indigo-300' : '',
                        selectedTicketForInspect.status === 'rejected' ? 'bg-rose-500/20 text-rose-300' : ''
                      ]"
                    >
                      {{ selectedTicketForInspect.status }}
                    </span>
                  </div>

                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">6-Digit Reset Code:</span>
                    <span v-if="selectedTicketForInspect.reset_code" class="font-mono font-bold text-emerald-300 text-sm bg-emerald-950 px-2 py-0.5 rounded border border-emerald-500/30">
                      {{ selectedTicketForInspect.reset_code }}
                    </span>
                    <span v-else class="text-slate-500">Not generated yet</span>
                  </div>

                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Request IP Address:</span>
                    <span class="font-mono text-slate-300">{{ selectedTicketForInspect.ip_address || 'N/A' }}</span>
                  </div>

                  <div class="py-1 border-b border-slate-800/60">
                    <span class="text-slate-400 block mb-1">Ticket Device Fingerprint:</span>
                    <span class="font-mono text-[10px] text-slate-300 bg-slate-950 p-1.5 rounded block truncate" :title="selectedTicketForInspect.device_hash">
                      {{ selectedTicketForInspect.device_hash || 'No hash recorded' }}
                    </span>
                  </div>

                  <div v-if="selectedTicketForInspect.user" class="py-1 border-b border-slate-800/60">
                    <span class="text-slate-400 block mb-1">Account Registered Device:</span>
                    <span class="font-mono text-[10px] text-slate-300 bg-slate-950 p-1.5 rounded block truncate" :title="selectedTicketForInspect.user.device_hash">
                      {{ selectedTicketForInspect.user.device_hash || 'No account hash' }}
                    </span>
                  </div>

                  <div class="flex justify-between py-1 border-b border-slate-800/60">
                    <span class="text-slate-400">Fingerprint Verification:</span>
                    <span
                      v-if="selectedTicketForInspect.user && selectedTicketForInspect.device_hash"
                      :class="[
                        'font-bold px-2 py-0.5 rounded text-[10px]',
                        isDeviceMatched(selectedTicketForInspect) ? 'bg-emerald-950 text-emerald-400 border border-emerald-500/30' : 'bg-rose-950 text-rose-400 border border-rose-500/30'
                      ]"
                    >
                      {{ isDeviceMatched(selectedTicketForInspect) ? 'Device Verified ✅' : 'Different Device ⚠️' }}
                    </span>
                    <span v-else class="text-slate-500 text-[10px]">Unverified</span>
                  </div>

                  <div class="flex justify-between py-1">
                    <span class="text-slate-400">Submitted At:</span>
                    <span class="text-slate-300">{{ formatDate(selectedTicketForInspect.created_at) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Notes Section -->
            <div class="space-y-2 text-xs">
              <div class="p-3 bg-slate-900 rounded-2xl border border-slate-800">
                <span class="text-slate-400 font-bold block mb-1">User Reason / Message:</span>
                <p class="text-slate-200">{{ selectedTicketForInspect.message || 'No user note provided.' }}</p>
              </div>

              <div v-if="selectedTicketForInspect.admin_note" class="p-3 bg-amber-950/30 border border-amber-500/30 rounded-2xl">
                <span class="text-amber-400 font-bold block mb-1">Admin Resolution Note:</span>
                <p class="text-amber-200">{{ selectedTicketForInspect.admin_note }}</p>
              </div>
            </div>

            <!-- Modal Footer Actions -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-800">
              <Link
                v-if="selectedTicketForInspect.user"
                :href="`${adminPath}/users?search=${encodeURIComponent(selectedTicketForInspect.phone)}`"
                class="px-4 py-2 bg-indigo-600/30 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/40 rounded-xl text-xs font-bold transition-all"
              >
                Go to User Profile →
              </Link>

              <div class="flex items-center gap-2 ml-auto">
                <button
                  v-if="selectedTicketForInspect.status === 'pending'"
                  @click="openApproveModalFromInspect"
                  class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors"
                >
                  Approve Reset Request
                </button>
                <button
                  v-if="selectedTicketForInspect.status === 'pending'"
                  @click="openRejectModalFromInspect"
                  class="px-4 py-2 bg-rose-600/30 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-500/30 font-bold text-xs transition-colors"
                >
                  Reject Request
                </button>
                <button @click="selectedTicketForInspect = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                  Close
                </button>
              </div>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Approve Modal -->
      <Teleport to="body">
        <div v-if="selectedTicketForApprove" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-emerald-500/30 bg-slate-950 space-y-4 shadow-2xl">
            <div class="flex justify-between items-center">
              <h3 class="text-base font-black text-white flex items-center gap-2">
                <span>✅ Approve Password Reset</span>
              </h3>
              <button @click="selectedTicketForApprove = null" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-300">
              Approving ticket <strong class="text-amber-400 font-mono">{{ selectedTicketForApprove.ticket_code }}</strong> will generate a 6-digit Reset Code for <strong class="text-white">{{ selectedTicketForApprove.phone }}</strong>.
            </p>

            <div>
              <label class="text-xs font-semibold text-slate-400 block mb-1">Admin Note (Optional)</label>
              <textarea
                v-model="approveForm.admin_note"
                rows="3"
                placeholder="e.g. Identity verified via support call"
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500"
              ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button @click="selectedTicketForApprove = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                Cancel
              </button>
              <button
                @click="submitApprove"
                :disabled="approveForm.processing"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors disabled:opacity-50"
              >
                Confirm Approval & Generate Code
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Reject Modal -->
      <Teleport to="body">
        <div v-if="selectedTicketForReject" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-rose-500/30 bg-slate-950 space-y-4 shadow-2xl">
            <div class="flex justify-between items-center">
              <h3 class="text-base font-black text-rose-400 flex items-center gap-2">
                <span>🚫 Reject Reset Request</span>
              </h3>
              <button @click="selectedTicketForReject = null" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <div>
              <label class="text-xs font-semibold text-slate-400 block mb-1">Reason for Rejection (Required)</label>
              <textarea
                v-model="rejectForm.admin_note"
                rows="3"
                placeholder="e.g. Phone number does not match account owner"
                required
                class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white placeholder-slate-600 focus:outline-none focus:border-rose-500"
              ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button @click="selectedTicketForReject = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                Cancel
              </button>
              <button
                @click="submitReject"
                :disabled="rejectForm.processing || !rejectForm.admin_note"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors disabled:opacity-50"
              >
                Confirm Rejection
              </button>
            </div>
          </div>
        </div>
      </Teleport>

      <!-- Delete Confirmation Modal -->
      <Teleport to="body">
        <div v-if="selectedTicketForDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm">
          <div class="glass-card max-w-md w-full p-6 rounded-3xl border border-rose-500/40 bg-slate-950 space-y-4 shadow-2xl">
            <div class="flex justify-between items-center">
              <h3 class="text-base font-black text-rose-400 flex items-center gap-2">
                <span>🗑️ Delete Ticket</span>
              </h3>
              <button @click="selectedTicketForDelete = null" class="text-slate-400 hover:text-white">✕</button>
            </div>

            <p class="text-xs text-slate-300">
              Are you sure you want to permanently delete password ticket <strong class="text-amber-400 font-mono">{{ selectedTicketForDelete.ticket_code }}</strong> for user <strong class="text-white">{{ selectedTicketForDelete.phone }}</strong>?
            </p>

            <div class="flex justify-end gap-2 pt-2">
              <button @click="selectedTicketForDelete = null" class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white">
                Cancel
              </button>
              <button
                @click="submitDelete"
                :disabled="deleteForm.processing"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs rounded-xl shadow-lg transition-colors disabled:opacity-50"
              >
                Confirm Delete
              </button>
            </div>
          </div>
        </div>
      </Teleport>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
  tickets: Object,
  filters: Object,
  stats: Object,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const currentStatus = ref(props.filters.status || 'all');
const searchQuery   = ref(props.filters.search || '');

const selectedTicketForInspect = ref(null);
const selectedTicketForApprove = ref(null);
const selectedTicketForReject  = ref(null);
const selectedTicketForDelete  = ref(null);

const copyToast = ref('');

const approveForm = useForm({
  admin_note: 'Identity verified. Reset approved.',
});

const rejectForm = useForm({
  admin_note: '',
});

const deleteForm = useForm({});

const filterStatus = (st) => {
  currentStatus.value = st;
  router.get(`${adminPath.value}/password-tickets`, {
    status: st,
    search: searchQuery.value,
  }, { preserveState: true, replace: true });
};

const handleSearch = () => {
  router.get(`${adminPath.value}/password-tickets`, {
    status: currentStatus.value,
    search: searchQuery.value,
  }, { preserveState: true, replace: true });
};

const openInspectModal = (ticket) => {
  selectedTicketForInspect.value = ticket;
};

const openApproveModal = (ticket) => {
  selectedTicketForApprove.value = ticket;
  approveForm.admin_note = 'Identity verified. Reset approved.';
};

const openRejectModal = (ticket) => {
  selectedTicketForReject.value = ticket;
  rejectForm.admin_note = '';
};

const openDeleteModal = (ticket) => {
  selectedTicketForDelete.value = ticket;
};

const openApproveModalFromInspect = () => {
  if (selectedTicketForInspect.value) {
    openApproveModal(selectedTicketForInspect.value);
    selectedTicketForInspect.value = null;
  }
};

const openRejectModalFromInspect = () => {
  if (selectedTicketForInspect.value) {
    openRejectModal(selectedTicketForInspect.value);
    selectedTicketForInspect.value = null;
  }
};

const submitApprove = () => {
  if (!selectedTicketForApprove.value) return;
  approveForm.post(`${adminPath.value}/password-tickets/${selectedTicketForApprove.value.id}/approve`, {
    onSuccess: () => {
      selectedTicketForApprove.value = null;
    },
  });
};

const submitReject = () => {
  if (!selectedTicketForReject.value) return;
  rejectForm.post(`${adminPath.value}/password-tickets/${selectedTicketForReject.value.id}/reject`, {
    onSuccess: () => {
      selectedTicketForReject.value = null;
    },
  });
};

const submitDelete = () => {
  if (!selectedTicketForDelete.value) return;
  deleteForm.delete(`${adminPath.value}/password-tickets/${selectedTicketForDelete.value.id}`, {
    onSuccess: () => {
      selectedTicketForDelete.value = null;
    },
  });
};

const isDeviceMatched = (ticket) => {
  if (!ticket || !ticket.user || !ticket.device_hash || !ticket.user.device_hash) return false;
  return ticket.device_hash === ticket.user.device_hash;
};

const copyText = (text, msg) => {
  if (!text) return;
  navigator.clipboard.writeText(text);
  copyToast.value = msg || 'Copied to clipboard!';
  setTimeout(() => {
    copyToast.value = '';
  }, 2500);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>
