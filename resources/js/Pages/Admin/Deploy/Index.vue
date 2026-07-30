<template>
  <AdminLayout>
    <div class="space-y-8">

      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-3">
            <div class="p-2.5 bg-emerald-500/20 rounded-xl text-emerald-400">
              <RocketIcon class="w-6 h-6" />
            </div>
            <div>
              <h1 class="text-3xl font-extrabold text-white tracking-tight">Deployment Center</h1>
              <p class="text-sm text-slate-400 mt-0.5">Admin প্যানেল থেকে সরাসরি ডিপ্লয় করুন</p>
            </div>
          </div>
        </div>

        <!-- System Info Badges -->
        <div class="flex flex-wrap gap-2">
          <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-blue-500/15 text-blue-300 border border-blue-500/20">
            PHP {{ phpVersion }}
          </span>
          <span class="px-3 py-1.5 rounded-full text-xs font-semibold bg-purple-500/15 text-purple-300 border border-purple-500/20">
            Laravel {{ laravelVersion }}
          </span>
          <span :class="[
            'px-3 py-1.5 rounded-full text-xs font-semibold border',
            appEnv === 'production'
              ? 'bg-rose-500/15 text-rose-300 border-rose-500/20'
              : 'bg-amber-500/15 text-amber-300 border-amber-500/20'
          ]">
            {{ appEnv?.toUpperCase() }}
          </span>
        </div>
      </div>

      <!-- Warning Banner (production) -->
      <div v-if="appEnv === 'production'" class="flex items-start gap-3 p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30">
        <AlertTriangleIcon class="w-5 h-5 text-rose-400 flex-shrink-0 mt-0.5" />
        <div>
          <p class="text-sm font-bold text-rose-300">Production Environment</p>
          <p class="text-xs text-rose-400/80 mt-0.5">আপনি production environment-এ আছেন। কমান্ড সাবধানে চালান।</p>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        <!-- Left: Command Groups -->
        <div class="xl:col-span-2 space-y-5">

          <!-- Git Commands -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden group">
            <div class="absolute top-0 right-0 w-40 h-40 bg-orange-500/8 rounded-full blur-3xl -mr-16 -mt-16"></div>
            <div class="flex items-center gap-3 mb-5">
              <div class="p-2 bg-orange-500/20 rounded-xl text-orange-400"><GitBranchIcon class="w-4 h-4" /></div>
              <h2 class="text-base font-bold text-white">Git Operations</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <CommandButton
                v-for="cmd in gitCommands" :key="cmd.key"
                :cmd="cmd"
                :loading="runningCommand === cmd.key"
                :disabled="!!runningCommand"
                @run="executeCommand(cmd.key)"
              />
            </div>
          </div>

          <!-- Artisan Commands -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-indigo-500/8 rounded-full blur-3xl -mr-16 -mt-16"></div>
            <div class="flex items-center gap-3 mb-5">
              <div class="p-2 bg-indigo-500/20 rounded-xl text-indigo-400"><TerminalIcon class="w-4 h-4" /></div>
              <h2 class="text-base font-bold text-white">Artisan Commands</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <CommandButton
                v-for="cmd in artisanCommands" :key="cmd.key"
                :cmd="cmd"
                :loading="runningCommand === cmd.key"
                :disabled="!!runningCommand"
                @run="executeCommand(cmd.key)"
              />
            </div>
          </div>

          <!-- Node/Composer -->
          <div class="glass-card p-6 rounded-3xl border border-slate-800/60 bg-slate-900/40 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 bg-emerald-500/8 rounded-full blur-3xl -mr-16 -mt-16"></div>
            <div class="flex items-center gap-3 mb-5">
              <div class="p-2 bg-emerald-500/20 rounded-xl text-emerald-400"><PackageIcon class="w-4 h-4" /></div>
              <h2 class="text-base font-bold text-white">Dependencies</h2>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <CommandButton
                v-for="cmd in depCommands" :key="cmd.key"
                :cmd="cmd"
                :loading="runningCommand === cmd.key"
                :disabled="!!runningCommand"
                @run="executeCommand(cmd.key)"
              />
            </div>
          </div>

          <!-- Quick Deploy Button -->
          <div class="glass-card p-6 rounded-3xl border border-emerald-500/30 bg-emerald-500/5 relative overflow-hidden">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                  <ZapIcon class="w-4 h-4 text-emerald-400" /> Quick Deploy
                </h2>
                <p class="text-xs text-slate-400 mt-1">git pull → composer install → migrate → optimize — সব এক ক্লিকে</p>
              </div>
              <button
                @click="runQuickDeploy"
                :disabled="!!runningCommand || quickDeployRunning"
                class="flex items-center gap-2 px-6 py-3 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-2xl transition-all duration-200 shadow-lg shadow-emerald-900/30 text-sm"
              >
                <RocketIcon v-if="!quickDeployRunning" class="w-4 h-4" />
                <LoaderIcon v-else class="w-4 h-4 animate-spin" />
                {{ quickDeployRunning ? 'Deploying...' : '🚀 Deploy Now' }}
              </button>
            </div>
            <!-- Quick deploy progress -->
            <div v-if="quickDeploySteps.length > 0" class="mt-4 space-y-2">
              <div v-for="step in quickDeploySteps" :key="step.key" class="flex items-center gap-3 text-xs">
                <CheckCircleIcon v-if="step.status === 'done'" class="w-4 h-4 text-emerald-400 flex-shrink-0" />
                <LoaderIcon v-else-if="step.status === 'running'" class="w-4 h-4 text-blue-400 animate-spin flex-shrink-0" />
                <XCircleIcon v-else-if="step.status === 'failed'" class="w-4 h-4 text-rose-400 flex-shrink-0" />
                <div v-else class="w-4 h-4 rounded-full border border-slate-600 flex-shrink-0"></div>
                <span :class="{
                  'text-emerald-300': step.status === 'done',
                  'text-blue-300': step.status === 'running',
                  'text-rose-300': step.status === 'failed',
                  'text-slate-500': step.status === 'pending'
                }">{{ step.label }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Terminal + Git Log -->
        <div class="space-y-5">

          <!-- Terminal Output -->
          <div class="glass-card rounded-3xl border border-slate-800/60 bg-slate-900/40 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 bg-slate-950/60 border-b border-slate-800/60">
              <div class="flex items-center gap-2">
                <div class="flex gap-1.5">
                  <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                  <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                  <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                </div>
                <span class="text-xs font-mono text-slate-400 ml-1">terminal</span>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="lastCommandDuration" class="text-xs text-slate-500">{{ lastCommandDuration }}ms</span>
                <button @click="clearOutput" class="text-xs text-slate-500 hover:text-slate-300 transition-colors">Clear</button>
              </div>
            </div>

            <!-- Terminal Body -->
            <div ref="terminalRef" class="h-80 overflow-y-auto p-4 font-mono text-xs bg-slate-950/30 space-y-1" style="scroll-behavior: smooth;">
              <div v-if="!terminalOutput && !runningCommand" class="text-slate-600 text-center mt-16">
                <TerminalIcon class="w-10 h-10 mx-auto mb-2 opacity-30" />
                <p>কমান্ড চালালে এখানে আউটপুট দেখাবে</p>
              </div>

              <!-- Output lines -->
              <template v-for="(entry, i) in outputHistory" :key="i">
                <div class="text-slate-500 border-t border-slate-800/40 pt-1 mt-2 first:border-0 first:pt-0 first:mt-0">
                  <span class="text-emerald-500">$</span>
                  <span class="text-slate-300 ml-2">{{ entry.command }}</span>
                  <span class="ml-2 text-xs" :class="entry.success ? 'text-emerald-600' : 'text-rose-600'">
                    [{{ entry.success ? 'OK' : 'FAIL' }}]
                  </span>
                </div>
                <pre class="whitespace-pre-wrap break-all leading-relaxed" :class="entry.success ? 'text-slate-300' : 'text-rose-300'">{{ entry.output }}</pre>
              </template>

              <!-- Blinking cursor when running -->
              <div v-if="runningCommand" class="flex items-center gap-2 text-amber-400">
                <LoaderIcon class="w-3 h-3 animate-spin" />
                <span>Running...</span>
              </div>
            </div>
          </div>

          <!-- Git Log -->
          <div class="glass-card p-5 rounded-3xl border border-slate-800/60 bg-slate-900/40">
            <div class="flex items-center gap-2 mb-4">
              <div class="p-1.5 bg-slate-700/50 rounded-lg text-slate-400"><GitCommitIcon class="w-4 h-4" /></div>
              <h3 class="text-sm font-bold text-white">Recent Commits</h3>
            </div>
            <div class="space-y-2">
              <div v-for="(log, i) in gitLog" :key="i" class="flex items-start gap-2.5 text-xs group">
                <div class="w-1.5 h-1.5 rounded-full bg-slate-600 flex-shrink-0 mt-1.5 group-first:bg-indigo-500"></div>
                <span class="text-slate-400 font-mono leading-relaxed break-all">{{ log }}</span>
              </div>
              <div v-if="!gitLog?.length" class="text-xs text-slate-600 text-center py-4">
                Git log unavailable
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import { ref, computed, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import CommandButton from '@/Components/CommandButton.vue';
import axios from 'axios';
import {
  RocketIcon, TerminalIcon, GitBranchIcon, GitCommitIcon,
  PackageIcon, ZapIcon, LoaderIcon, AlertTriangleIcon,
  CheckCircleIcon, XCircleIcon,
} from 'lucide-vue-next';

const props = defineProps({
  gitLog: Array,
  phpVersion: String,
  laravelVersion: String,
  appEnv: String,
  appUrl: String,
});

const page = usePage();
const adminPath = computed(() => '/' + (page.props.admin_path || 'admin'));

// --- State ---
const runningCommand    = ref(null);
const terminalOutput    = ref('');
const outputHistory     = ref([]);
const terminalRef       = ref(null);
const lastCommandDuration = ref(null);
const quickDeployRunning  = ref(false);
const quickDeploySteps    = ref([]);

// --- Command Definitions ---
const gitCommands = [
  { key: 'git_pull',        label: 'git pull (main)',   icon: '⬇️', color: 'orange', danger: false },
  { key: 'git_pull_master', label: 'git pull (master)', icon: '⬇️', color: 'orange', danger: false },
  { key: 'git_status',      label: 'git status',        icon: '🔍', color: 'slate',  danger: false },
];

const artisanCommands = [
  { key: 'migrate',       label: 'migrate',         icon: '🗃️',  color: 'amber',  danger: true  },
  { key: 'cache_clear',   label: 'cache:clear',     icon: '🧹',  color: 'indigo', danger: false },
  { key: 'config_cache',  label: 'config:cache',    icon: '⚙️',  color: 'indigo', danger: false },
  { key: 'route_cache',   label: 'route:cache',     icon: '🛣️',  color: 'indigo', danger: false },
  { key: 'view_cache',    label: 'view:cache',      icon: '👁️',  color: 'indigo', danger: false },
  { key: 'optimize',      label: 'optimize',        icon: '⚡',  color: 'emerald',danger: false },
  { key: 'optimize_clear',label: 'optimize:clear',  icon: '🔄',  color: 'rose',   danger: false },
  { key: 'storage_link',  label: 'storage:link',    icon: '🔗',  color: 'sky',    danger: false },
  { key: 'queue_restart', label: 'queue:restart',   icon: '🔁',  color: 'violet', danger: false },
  { key: 'down',          label: 'artisan down',    icon: '🛑',  color: 'rose',   danger: true  },
  { key: 'up',            label: 'artisan up',      icon: '🟢',  color: 'emerald',danger: false },
];

const depCommands = [
  { key: 'composer_install', label: 'composer install', icon: '🎼', color: 'amber',   danger: false },
  { key: 'npm_install',      label: 'npm install',      icon: '📦', color: 'emerald', danger: false },
  { key: 'npm_build',        label: 'npm run build',    icon: '🔨', color: 'sky',     danger: false },
];

// Quick Deploy steps
const QUICK_DEPLOY_STEPS = [
  { key: 'git_pull',      label: 'git pull origin main' },
  { key: 'composer_install', label: 'composer install' },
  { key: 'migrate',       label: 'php artisan migrate' },
  { key: 'optimize_clear',label: 'optimize:clear' },
  { key: 'optimize',      label: 'optimize' },
];

// --- Methods ---
const scrollTerminal = async () => {
  await nextTick();
  if (terminalRef.value) {
    terminalRef.value.scrollTop = terminalRef.value.scrollHeight;
  }
};

const executeCommand = async (commandKey) => {
  if (runningCommand.value) return;
  runningCommand.value = commandKey;
  lastCommandDuration.value = null;

  try {
    const response = await axios.post(`${adminPath.value}/deploy/run`, {
      command: commandKey,
    }, {
      headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
    });

    const data = response.data;
    outputHistory.value.push({
      command: data.command,
      output:  data.output || '(no output)',
      success: data.success,
    });
    lastCommandDuration.value = data.duration;
  } catch (err) {
    const errData = err.response?.data;
    outputHistory.value.push({
      command: commandKey,
      output:  errData?.output || err.message || 'Unknown error',
      success: false,
    });
  } finally {
    runningCommand.value = null;
    scrollTerminal();
  }
};

const runQuickDeploy = async () => {
  if (quickDeployRunning.value || runningCommand.value) return;
  quickDeployRunning.value = true;

  // Reset steps
  quickDeploySteps.value = QUICK_DEPLOY_STEPS.map(s => ({ ...s, status: 'pending' }));

  for (let i = 0; i < quickDeploySteps.value.length; i++) {
    const step = quickDeploySteps.value[i];
    step.status = 'running';
    runningCommand.value = step.key;

    try {
      const res = await axios.post(`${adminPath.value}/deploy/run`, { command: step.key }, {
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content },
      });

      outputHistory.value.push({
        command: res.data.command,
        output:  res.data.output || '(no output)',
        success: res.data.success,
      });
      scrollTerminal();

      if (!res.data.success) {
        step.status = 'failed';
        runningCommand.value = null;
        quickDeployRunning.value = false;
        return;
      }

      step.status = 'done';
    } catch (err) {
      step.status = 'failed';
      outputHistory.value.push({
        command: step.label,
        output:  err.response?.data?.output || err.message,
        success: false,
      });
      scrollTerminal();
      runningCommand.value = null;
      quickDeployRunning.value = false;
      return;
    }
  }

  runningCommand.value = null;
  quickDeployRunning.value = false;
};

const clearOutput = () => {
  outputHistory.value = [];
  lastCommandDuration.value = null;
  quickDeploySteps.value = [];
};
</script>
