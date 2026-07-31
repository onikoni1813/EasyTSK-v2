<template>
  <AdminLayout>
    <div class="space-y-8">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 class="text-3xl font-extrabold text-white tracking-tight">System Settings</h1>
          <p class="text-sm text-slate-400 mt-1">Configure global parameters, withdrawal limits, and rewards.</p>
        </div>
        <button @click="updateSettings" 
                :disabled="settingsForm.processing"
                class="inline-flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold rounded-2xl transition-all duration-200 shadow-lg shadow-indigo-900/20 disabled:opacity-50 disabled:cursor-not-allowed">
          <SaveIcon class="w-5 h-5 mr-2" />
          {{ settingsForm.processing ? 'Saving...' : 'Save All Settings' }}
        </button>
      </div>

      <!-- Success Alert Banner -->
      <div v-if="$page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center gap-3 text-emerald-400 text-sm font-semibold">
        <CheckCircleIcon class="w-5 h-5 shrink-0 text-emerald-400" />
        <span>{{ $page.props.flash.success }}</span>
      </div>

      <!-- Validation Error Alert Banner -->
      <div v-if="Object.keys(settingsForm.errors).length > 0" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-400 text-sm space-y-1">
        <div class="flex items-center gap-2 font-bold mb-1">
          <AlertTriangleIcon class="w-5 h-5 shrink-0 text-rose-400" />
          <span>Please fix the following issues before saving:</span>
        </div>
        <ul class="list-disc list-inside text-xs space-y-0.5 pl-2">
          <li v-for="(error, field) in settingsForm.errors" :key="field">{{ error }}</li>
        </ul>
      </div>

      <form @submit.prevent="updateSettings">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          
          <!-- Platform Maintenance Mode -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group lg:col-span-2 transition-all duration-300"
               :class="settingsForm.maintenance_mode ? 'border-amber-500/40 bg-amber-500/5' : ''">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-amber-500/20"></div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 relative">
              <div class="flex items-center gap-3">
                <div class="p-2.5 bg-amber-500/20 rounded-xl text-amber-400 shrink-0">
                  <WrenchIcon class="w-5 h-5" />
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <h2 class="text-lg font-bold text-white">Platform Maintenance Mode</h2>
                    <span v-if="settingsForm.maintenance_mode" class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-500/20 border border-amber-500/40 text-amber-400 animate-pulse">
                      MAINTENANCE ACTIVE
                    </span>
                    <span v-else class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-500/20 border border-emerald-500/40 text-emerald-400">
                      LIVE (OFFLINE)
                    </span>
                  </div>
                  <p class="text-xs text-slate-400 mt-0.5">Restrict regular users and show maintenance notice during system upgrades.</p>
                </div>
              </div>

              <!-- Toggle Switch -->
              <label class="relative inline-flex items-center cursor-pointer shrink-0">
                <input type="checkbox" v-model="settingsForm.maintenance_mode" class="sr-only peer">
                <div class="w-14 h-7 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-amber-500"></div>
              </label>
            </div>

            <div class="space-y-4 relative">
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Custom Maintenance Notice Message</label>
                <textarea v-model="settingsForm.maintenance_message" rows="2" placeholder="Write custom maintenance announcement message..." class="w-full px-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-amber-500 focus:ring-1 focus:ring-amber-500 rounded-xl text-sm text-white transition-all placeholder-slate-600"></textarea>
              </div>

              <div class="p-3.5 rounded-xl bg-slate-950/50 border border-slate-800 text-xs text-slate-400 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                  <ShieldAlertIcon class="w-4 h-4 text-amber-400 shrink-0" />
                  <span>Admins retain full access to site & panel. Non-admin users see the Maintenance Page.</span>
                </div>
                <a href="/" target="_blank" class="text-xs font-semibold text-amber-400 hover:underline shrink-0 flex items-center gap-1">
                  Preview Site &rarr;
                </a>
              </div>
            </div>
          </div>

          <!-- Core Configuration -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-blue-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-blue-500/20 rounded-xl text-blue-400">
                <SettingsIcon class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-bold text-white">Core Configuration</h2>
            </div>
            
            <div class="space-y-5 relative">
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Conversion Rate (Points = 1 BDT)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <RefreshCwIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.conversion_rate" type="number" step="0.1" required class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all placeholder-slate-600" />
                </div>
                <p class="text-[11px] text-slate-400 mt-1.5">Default 100 means 1000 Points = 10 BDT</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Welcome Bonus Amount</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <GiftIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.welcome_bonus" type="number" step="0.1" required class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
              </div>

              <div class="pt-2">
                <label class="flex items-center p-4 rounded-xl border border-amber-500/20 bg-amber-500/5 cursor-pointer hover:bg-amber-500/10 transition-colors">
                  <input v-model="settingsForm.happy_hour" type="checkbox" class="w-5 h-5 rounded text-amber-500 focus:ring-amber-500 bg-slate-900 border-slate-700" />
                  <div class="ml-3">
                    <span class="block text-sm font-bold text-amber-400">Activate Happy Hour</span>
                    <span class="block text-xs text-amber-400/70 mt-0.5">Applies 2x Reward Bonus to tasks</span>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Withdrawal Rules -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-emerald-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-emerald-500/20 rounded-xl text-emerald-400">
                <CreditCardIcon class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-bold text-white">Withdrawal Rules</h2>
            </div>

            <div class="space-y-5 relative">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-300 mb-1.5">First Limit (Pts)</label>
                  <input v-model="settingsForm.first_withdraw_limit" type="number" required class="w-full px-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-300 mb-1.5">Next Limit (Pts)</label>
                  <input v-model="settingsForm.next_withdraw_limit" type="number" required class="w-full px-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
              </div>
              
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Withdrawal Charge (%)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <PercentIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.withdrawal_charge_percent" type="number" required min="0" max="100" class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Minimum Required Health Score (%)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <ActivityIcon class="w-4 h-4 text-rose-400" />
                  </div>
                  <input v-model="settingsForm.min_withdrawal_health" type="number" required min="0" max="100" class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-400 mt-1.5">Users at or below this health % cannot submit withdrawal requests (Default: 40%)</p>
              </div>
            </div>
          </div>

          <!-- Referral System -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-purple-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-purple-500/20 rounded-xl text-purple-400">
                <UsersIcon class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-bold text-white">Referral System</h2>
            </div>

            <div class="space-y-5 relative">
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Referral Bonus (Locked Pts)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <AwardIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.referral_bonus" type="number" required class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Referral Target (Pts to Unlock)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <TargetIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.referral_target" type="number" required class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-400 mt-1.5">Amount referred user must earn to unlock bonus</p>
              </div>
            </div>
          </div>

          <!-- Additional Settings -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-32 h-32 bg-rose-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-rose-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-rose-500/20 rounded-xl text-rose-400">
                <SmartphoneIcon class="w-5 h-5" />
              </div>
              <h2 class="text-lg font-bold text-white">Services & Limits</h2>
            </div>

            <div class="space-y-5 relative">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-semibold text-slate-300 mb-1.5">Recharge Min Limit</label>
                  <input v-model="settingsForm.mobile_recharge_min_limit" type="number" required min="1" class="w-full px-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <div>
                  <label class="block text-sm font-semibold text-slate-300 mb-1.5">Recharge Fixed Charge</label>
                  <input v-model="settingsForm.mobile_recharge_fixed_charge" type="number" required min="0" class="w-full px-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Offerwall Pending Duration</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <ClockIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.offerwall_pending_hours" type="number" required min="0" class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                  <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none">
                    <span class="text-xs text-slate-500">Hours</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Homepage Platform Stats (Demo Boost Data) -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group lg:col-span-2">
            <div class="absolute top-0 right-0 w-32 h-32 bg-indigo-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-indigo-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-indigo-500/20 rounded-xl text-indigo-400">
                <TrendingUpIcon class="w-5 h-5" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">Homepage Platform Stats (Demo Boost Data)</h2>
                <p class="text-xs text-slate-400">These values are automatically added to real database counts on the public homepage to present a strong brand image.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative">
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Demo Registered Users</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <UsersIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.demo_users" type="number" required min="0" class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Total shown = Real Users + Demo Users</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Demo Tasks Completed</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <AwardIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.demo_tasks" type="number" required min="0" class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Total shown = Real Tasks + Demo Tasks</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Demo Total Payouts (BDT)</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <CreditCardIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.demo_payouts" type="number" step="0.01" required min="0" class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Total shown = Real Payouts + Demo Payouts</p>
              </div>
            </div>
          </div>

          <!-- Official Contact & Support Details -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group lg:col-span-2">
            <div class="absolute top-0 right-0 w-32 h-32 bg-cyan-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-cyan-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-cyan-500/20 rounded-xl text-cyan-400">
                <MailIcon class="w-5 h-5" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">Official Contact & Support Emails</h2>
                <p class="text-xs text-slate-400">Manage public contact emails and address displayed across legal pages, footer, and support links.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 relative">
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Support Email</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MailIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.support_email" type="email" required class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Used in legal pages & footer</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Contact Email</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MailIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.contact_email" type="email" required class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Used in Contact form & inquiry links</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Company Address</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <MapPinIcon class="w-4 h-4 text-slate-500" />
                  </div>
                  <input v-model="settingsForm.company_address" type="text" required class="w-full pl-10 pr-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all" />
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Location string for compliance footer</p>
              </div>
            </div>
          </div>

          <!-- Website Branding (Logo & Favicon) -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group lg:col-span-2">
            <div class="absolute top-0 right-0 w-32 h-32 bg-violet-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-violet-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-violet-500/20 rounded-xl text-violet-400">
                <ImageIcon class="w-5 h-5" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">Website Branding (Dynamic Logo & Favicon)</h2>
                <p class="text-xs text-slate-400">Upload custom logo image or favicon icon directly from your device, or enter an image URL.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative">
              <!-- Site Logo Field -->
              <div class="space-y-4 p-5 rounded-2xl border border-slate-800/80 bg-slate-950/40">
                <label class="block text-sm font-semibold text-slate-200">Header & Brand Logo Image</label>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                  <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                    <img v-if="logoPreview || settingsForm.site_logo_url" :src="logoPreview || settingsForm.site_logo_url" alt="Logo Preview" class="max-w-full max-h-full object-contain p-1" />
                    <span v-else class="text-2xl font-bold text-indigo-400">E</span>
                  </div>
                  <div class="flex-1 w-full space-y-2">
                    <div class="flex items-center gap-2">
                      <label class="inline-flex items-center px-4 py-2 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 hover:text-indigo-200 border border-indigo-500/30 rounded-xl text-xs font-semibold cursor-pointer transition-all">
                        <UploadIcon class="w-4 h-4 mr-2" />
                        Direct Upload File
                        <input type="file" accept="image/*" class="hidden" @change="handleLogoUpload" />
                      </label>
                      <span v-if="logoFileName" class="text-xs text-emerald-400 truncate max-w-[140px] inline-block">{{ logoFileName }}</span>
                    </div>
                    <div class="relative">
                      <input v-model="settingsForm.site_logo_url" type="text" placeholder="Or paste Image URL (https://...)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/50 rounded-xl text-xs text-white placeholder-slate-600 focus:border-indigo-500" />
                    </div>
                  </div>
                </div>
                <p class="text-[11px] text-slate-500">Supported formats: PNG, JPG, SVG, WebP (Max 5MB). Direct file upload overrides image URL.</p>
              </div>

              <!-- Favicon Field -->
              <div class="space-y-4 p-5 rounded-2xl border border-slate-800/80 bg-slate-950/40">
                <label class="block text-sm font-semibold text-slate-200">Browser Favicon Icon</label>
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                  <div class="w-16 h-16 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center overflow-hidden shrink-0 shadow-inner">
                    <img v-if="faviconPreview || settingsForm.site_favicon_url" :src="faviconPreview || settingsForm.site_favicon_url" alt="Favicon Preview" class="w-8 h-8 object-contain" />
                    <span v-else class="text-2xl">🌐</span>
                  </div>
                  <div class="flex-1 w-full space-y-2">
                    <div class="flex items-center gap-2">
                      <label class="inline-flex items-center px-4 py-2 bg-purple-600/20 hover:bg-purple-600/30 text-purple-300 hover:text-purple-200 border border-purple-500/30 rounded-xl text-xs font-semibold cursor-pointer transition-all">
                        <UploadIcon class="w-4 h-4 mr-2" />
                        Direct Upload File
                        <input type="file" accept="image/*,.ico" class="hidden" @change="handleFaviconUpload" />
                      </label>
                      <span v-if="faviconFileName" class="text-xs text-emerald-400 truncate max-w-[140px] inline-block">{{ faviconFileName }}</span>
                    </div>
                    <div class="relative">
                      <input v-model="settingsForm.site_favicon_url" type="text" placeholder="Or paste Favicon URL (/favicon.ico)" class="w-full px-3 py-2 bg-slate-900 border border-slate-700/50 rounded-xl text-xs text-white placeholder-slate-600 focus:border-indigo-500" />
                    </div>
                  </div>
                </div>
                <p class="text-[11px] text-slate-500">Supported formats: .ico, PNG, SVG (Max 2MB). Direct file upload overrides icon URL.</p>
              </div>
            </div>
          </div>

          <!-- Google OAuth Authentication -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group lg:col-span-2">
            <div class="absolute top-0 right-0 w-32 h-32 bg-rose-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-rose-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-rose-500/20 rounded-xl text-rose-400">
                <LockIcon class="w-5 h-5" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">Google OAuth Authentication Settings</h2>
                <p class="text-xs text-slate-400">Configure Google Client ID & Secret dynamically for 1-click social login.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative">
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Google Client ID</label>
                <input v-model="settingsForm.google_client_id" type="text" placeholder="xxxxxx.apps.googleusercontent.com" class="w-full px-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all placeholder-slate-600" />
              </div>
              <div>
                <label class="block text-sm font-semibold text-slate-300 mb-1.5">Google Client Secret</label>
                <input v-model="settingsForm.google_client_secret" type="password" placeholder="GOCSPX-xxxxxxxxxxxxxx" class="w-full px-4 py-3 bg-slate-950/50 border border-slate-700/50 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-sm text-white transition-all placeholder-slate-600" />
              </div>
            </div>

            <div class="mt-4 p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400 flex flex-col md:flex-row md:items-center justify-between gap-2">
              <div>
                <span class="font-bold text-slate-300">Authorized Redirect URI for Google Console:</span>
                <code class="ml-2 px-2 py-1 bg-slate-900 border border-slate-700 rounded text-amber-400 font-mono select-all">/auth/google/callback</code>
              </div>
              <span class="text-slate-500 text-[11px]">System automatically detects domain & port!</span>
            </div>
          </div>

          <!-- Telegram Bot Notifications -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group lg:col-span-2">
            <div class="absolute top-0 right-0 w-32 h-32 bg-sky-500/10 rounded-full blur-3xl -mr-10 -mt-10 transition-all duration-500 group-hover:bg-sky-500/20"></div>
            <div class="flex items-center gap-3 mb-6 relative">
              <div class="p-2.5 bg-sky-500/20 rounded-xl text-sky-400">
                <SendIcon class="w-5 h-5" />
              </div>
              <div>
                <h2 class="text-lg font-bold text-white">Telegram Bot Notifications</h2>
                <p class="text-xs text-slate-400">Configure instant alerts for admin withdrawal requests & public payout success proof channel.</p>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 relative">
              
              <!-- Admin Withdrawal Alert Bot -->
              <div class="p-5 rounded-2xl bg-slate-950/40 border border-slate-800/80 space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-sm font-bold text-slate-200">1. Admin Withdrawal Alert Bot</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Sends instant alert when user requests a payout (with single-tap copyable payment number).</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="settingsForm.telegram_admin_bot_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-600"></div>
                  </label>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Bot Token</label>
                  <input v-model="settingsForm.telegram_admin_bot_token" type="password" placeholder="123456789:ABCdefGhIJKlmNoPQRsTUVwxyZ" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/60 focus:border-sky-500 rounded-xl text-xs text-white placeholder-slate-600" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Chat ID / Group ID</label>
                  <input v-model="settingsForm.telegram_admin_chat_id" type="text" placeholder="-100123456789 or 987654321" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/60 focus:border-sky-500 rounded-xl text-xs text-white placeholder-slate-600" />
                </div>

                <button type="button" @click="testTelegram('admin')" :disabled="testingAdmin" class="w-full py-2.5 bg-sky-600/20 hover:bg-sky-600/30 text-sky-400 border border-sky-500/30 font-semibold rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50">
                  <SendIcon class="w-3.5 h-3.5" />
                  {{ testingAdmin ? 'Sending Test Message...' : 'Test Admin Bot Connection' }}
                </button>
              </div>

              <!-- Withdrawal Success Public Bot -->
              <div class="p-5 rounded-2xl bg-slate-950/40 border border-slate-800/80 space-y-4">
                <div class="flex items-center justify-between">
                  <div>
                    <h3 class="text-sm font-bold text-slate-200">2. Withdrawal Success Public Bot</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Automatically posts payment proof message to your public Telegram channel when payout is marked Paid.</p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" v-model="settingsForm.telegram_success_bot_enabled" class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                  </label>
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Bot Token</label>
                  <input v-model="settingsForm.telegram_success_bot_token" type="password" placeholder="987654321:XYZabcDefGhiJklMnoPqrSTUvw" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/60 focus:border-emerald-500 rounded-xl text-xs text-white placeholder-slate-600" />
                </div>

                <div>
                  <label class="block text-xs font-semibold text-slate-300 mb-1">Public Channel ID / Username</label>
                  <input v-model="settingsForm.telegram_success_chat_id" type="text" placeholder="@MyPayoutChannel or -100987654321" class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/60 focus:border-emerald-500 rounded-xl text-xs text-white placeholder-slate-600" />
                </div>

                <button type="button" @click="testTelegram('success')" :disabled="testingSuccess" class="w-full py-2.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 font-semibold rounded-xl text-xs transition-colors flex items-center justify-center gap-1.5 disabled:opacity-50">
                  <SendIcon class="w-3.5 h-3.5" />
                  {{ testingSuccess ? 'Sending Test Message...' : 'Test Success Bot Connection' }}
                </button>
              </div>

            </div>
          </div>

        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import {
  SaveIcon,
  SettingsIcon,
  RefreshCwIcon,
  GiftIcon,
  CreditCardIcon,
  PercentIcon,
  UsersIcon,
  AwardIcon,
  TargetIcon,
  SmartphoneIcon,
  ClockIcon,
  TrendingUpIcon,
  MailIcon,
  MapPinIcon,
  ImageIcon,
  LockIcon,
  UploadIcon,
  CheckCircleIcon,
  AlertTriangleIcon,
  SendIcon,
  WrenchIcon,
  ShieldAlertIcon,
  ActivityIcon
} from 'lucide-vue-next';

const props = defineProps({
  conversionRate: String,
  welcomeBonus: Number,
  happyHourActive: Boolean,
  campaignCreatorCost: Number,
  campaignClickerReward: Number,
  firstWithdrawLimit: Number,
  nextWithdrawLimit: Number,
  referralBonus: Number,
  referralTarget: Number,
  offerwallPendingHours: Number,
  withdrawalChargePercent: Number,
  mobileRechargeMinLimit: Number,
  mobileRechargeFixedCharge: Number,
  minWithdrawalHealth: Number,
  demoUsers: Number,
  demoTasks: Number,
  demoPayouts: Number,
  supportEmail: String,
  contactEmail: String,
  companyAddress: String,
  siteLogo: String,
  siteFavicon: String,
  googleClientId: String,
  googleClientSecret: String,
  telegramAdminBotEnabled: Boolean,
  telegramAdminBotToken: String,
  telegramAdminChatId: String,
  telegramSuccessBotEnabled: Boolean,
  telegramSuccessBotToken: String,
  telegramSuccessChatId: String,
  maintenanceMode: Boolean,
  maintenanceMessage: String,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

const logoPreview = ref(null);
const logoFileName = ref('');
const faviconPreview = ref(null);
const faviconFileName = ref('');
const testingAdmin = ref(false);
const testingSuccess = ref(false);

const settingsForm = useForm({
  conversion_rate: props.conversionRate || 100,
  welcome_bonus: props.welcomeBonus || 50,
  happy_hour: props.happyHourActive || false,
  maintenance_mode: props.maintenanceMode || false,
  maintenance_message: props.maintenanceMessage || 'We are currently performing scheduled maintenance to upgrade our platform. Please check back shortly!',
  campaign_creator_cost: props.campaignCreatorCost || 2.0,
  campaign_clicker_reward: props.campaignClickerReward || 1.0,
  first_withdraw_limit: props.firstWithdrawLimit || 1000,
  next_withdraw_limit: props.nextWithdrawLimit || 500,
  referral_bonus: props.referralBonus || 500,
  referral_target: props.referralTarget || 1000,
  offerwall_pending_hours: props.offerwallPendingHours || 24,
  withdrawal_charge_percent: props.withdrawalChargePercent || 0,
  mobile_recharge_min_limit: props.mobileRechargeMinLimit || 500,
  mobile_recharge_fixed_charge: props.mobileRechargeFixedCharge || 10,
  min_withdrawal_health: props.minWithdrawalHealth !== undefined ? props.minWithdrawalHealth : 40,
  demo_users: props.demoUsers !== undefined ? props.demoUsers : 1200,
  demo_tasks: props.demoTasks !== undefined ? props.demoTasks : 45000,
  demo_payouts: props.demoPayouts !== undefined ? props.demoPayouts : 280000,
  support_email: props.supportEmail || 'support@easytsk.com',
  contact_email: props.contactEmail || 'contact@easytsk.com',
  company_address: props.companyAddress || 'Dhaka, Bangladesh',
  site_logo_url: props.siteLogo || '',
  site_favicon_url: props.siteFavicon || '/favicon.ico',
  site_logo_file: null,
  site_favicon_file: null,
  google_client_id: props.googleClientId || '',
  google_client_secret: props.googleClientSecret || '',
  telegram_admin_bot_enabled: props.telegramAdminBotEnabled || false,
  telegram_admin_bot_token: props.telegramAdminBotToken || '',
  telegram_admin_chat_id: props.telegramAdminChatId || '',
  telegram_success_bot_enabled: props.telegramSuccessBotEnabled || false,
  telegram_success_bot_token: props.telegramSuccessBotToken || '',
  telegram_success_chat_id: props.telegramSuccessChatId || '',
});

const testTelegram = (type) => {
  const isAdmin = type === 'admin';
  if (isAdmin) testingAdmin.value = true;
  else testingSuccess.value = true;

  const payload = {
    bot_type: type,
    bot_token: isAdmin ? settingsForm.telegram_admin_bot_token : settingsForm.telegram_success_bot_token,
    chat_id: isAdmin ? settingsForm.telegram_admin_chat_id : settingsForm.telegram_success_chat_id,
  };

  useForm(payload).post(`${adminPath.value}/settings/telegram-test`, {
    preserveScroll: true,
    onFinish: () => {
      if (isAdmin) testingAdmin.value = false;
      else testingSuccess.value = false;
    }
  });
};

const handleLogoUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    settingsForm.site_logo_file = file;
    logoFileName.value = file.name;
    logoPreview.value = URL.createObjectURL(file);
  }
};

const handleFaviconUpload = (e) => {
  const file = e.target.files[0];
  if (file) {
    settingsForm.site_favicon_file = file;
    faviconFileName.value = file.name;
    faviconPreview.value = URL.createObjectURL(file);
  }
};

const updateSettings = () => {
  settingsForm.post(`${adminPath.value}/settings`, {
    preserveScroll: true,
  });
};
</script>

