<!-- Anti-Cheat 60s Dwell Timer Widget -->
@php
    $site = $currentSite ?? app(\App\Services\SiteContext::class)->get();
    $taskEnabled = $site?->task_reward_enabled ?? true;
    $requiredSeconds = $site?->task_timer_seconds ?: 60;
@endphp

@if($taskEnabled)
    <!-- Sticky Floating Task Reward Widget -->
    <div id="taskRewardBar" class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:w-96 bg-slate-950/95 backdrop-blur-md border border-emerald-500/40 rounded-2xl p-4 shadow-2xl z-40 transition-all duration-300 transform translate-y-0">
        <div class="flex items-center justify-between gap-3 mb-2">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-pulse"></span>
                <span class="text-xs font-bold text-white tracking-wide">EasyTSK Reward Task</span>
            </div>
            <div id="timerCountdown" class="text-xs font-mono font-bold text-emerald-400 bg-emerald-500/10 border border-emerald-500/30 px-2.5 py-0.5 rounded-lg">
                {{ $requiredSeconds }}s
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-slate-800 rounded-full h-1.5 overflow-hidden mb-2">
            <div id="timerProgressBar" class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 transition-all duration-1000 ease-linear" style="width: 0%"></div>
        </div>

        <!-- Status Message & Code Box -->
        <div id="timerStatusText" class="text-[11px] text-slate-300 leading-snug">
            ⏱️ Read for <span class="font-bold text-emerald-400">{{ $requiredSeconds }} seconds</span> with this tab active to unlock your reward code.
        </div>

        <!-- Generated Reward Box (Hidden Initially) -->
        <div id="rewardCodeBox" class="hidden mt-3 pt-3 border-t border-slate-800/80 space-y-2">
            <div class="text-[11px] font-bold text-emerald-300 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Task Complete! Your Secret Code:</span>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" id="secretCodeInput" readonly value="" class="flex-1 bg-slate-900 border border-emerald-500/50 rounded-xl px-3 py-1.5 text-xs font-mono font-black text-emerald-300 text-center tracking-widest select-all">
                <button type="button" id="copyCodeBtn" onclick="copySecretCode()" class="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-600 text-slate-950 font-extrabold text-xs rounded-xl shadow transition flex-shrink-0">
                    Copy
                </button>
            </div>
            <p class="text-[10px] text-slate-400 leading-tight">Paste this code on EasyTSK to get your task approved immediately.</p>
        </div>
    </div>

    <script>
    (function() {
        const totalSeconds = {{ $requiredSeconds }};
        const siteId = {{ $site?->id ?? 1 }};
        const postId = {{ isset($post) ? $post->id : 'null' }};

        let remainingSeconds = totalSeconds;
        let sessionToken = null;
        let timerInterval = null;
        let isTabActive = true;
        let isCodeGenerated = false;

        // Anti-Cheat Visibility Check (Tab switch detection)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                isTabActive = false;
                pauseTimer('⚠️ Timer Paused! Please keep this article tab active.');
            } else {
                isTabActive = true;
                if (!isCodeGenerated) resumeTimer();
            }
        });

        window.addEventListener('blur', function() {
            isTabActive = false;
            pauseTimer('⚠️ Timer Paused! Please keep this article tab active.');
        });

        window.addEventListener('focus', function() {
            isTabActive = true;
            if (!isCodeGenerated) resumeTimer();
        });

        async function initTaskSession() {
            try {
                const res = await fetch('{{ url("/api/task/start-session") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        site_id: siteId,
                        post_id: postId
                    })
                });

                const data = await res.json();
                if (data.enabled && data.session_token) {
                    sessionToken = data.session_token;
                    startTimer();
                }
            } catch (e) {
                console.error('Task session initialization failed:', e);
            }
        }

        function startTimer() {
            if (timerInterval) clearInterval(timerInterval);

            timerInterval = setInterval(function() {
                // If AdBlocker modal is open or tab is inactive, do not decrement
                const adblockOverlay = document.getElementById('globalAdblockOverlay');
                if ((adblockOverlay && !adblockOverlay.classList.contains('hidden')) || !isTabActive || isCodeGenerated) {
                    return;
                }

                remainingSeconds--;
                updateTimerDisplay();

                if (remainingSeconds <= 0) {
                    clearInterval(timerInterval);
                    claimSecretCode();
                }
            }, 1000);
        }

        function pauseTimer(msg) {
            const status = document.getElementById('timerStatusText');
            if (status && !isCodeGenerated) {
                status.innerHTML = '<span class="text-amber-400 font-semibold">' + msg + '</span>';
            }
        }

        function resumeTimer() {
            const status = document.getElementById('timerStatusText');
            if (status && !isCodeGenerated) {
                status.innerHTML = '⏱️ Reading in progress... Keep tab active to claim code.';
            }
        }

        function updateTimerDisplay() {
            const countdown = document.getElementById('timerCountdown');
            const progressBar = document.getElementById('timerProgressBar');

            if (countdown) {
                countdown.innerText = remainingSeconds + 's';
            }
            if (progressBar) {
                const pct = Math.min(100, Math.round(((totalSeconds - remainingSeconds) / totalSeconds) * 100));
                progressBar.style.width = pct + '%';
            }
        }

        async function claimSecretCode() {
            if (!sessionToken || isCodeGenerated) return;

            const status = document.getElementById('timerStatusText');
            if (status) status.innerHTML = '⏳ Validating reading dwell time with server...';

            try {
                const res = await fetch('{{ url("/api/task/claim-code") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        session_token: sessionToken,
                        post_id: postId
                    })
                });

                const data = await res.json();
                if (data.success && data.code) {
                    isCodeGenerated = true;
                    showSecretCode(data.code);
                } else {
                    if (status) status.innerHTML = '<span class="text-rose-400 font-bold">' + (data.message || 'Verification failed.') + '</span>';
                }
            } catch (e) {
                if (status) status.innerHTML = '<span class="text-rose-400">Network error claiming code.</span>';
            }
        }

        function showSecretCode(code) {
            const status = document.getElementById('timerStatusText');
            const box = document.getElementById('rewardCodeBox');
            const input = document.getElementById('secretCodeInput');
            const countdown = document.getElementById('timerCountdown');

            if (status) status.classList.add('hidden');
            if (countdown) {
                countdown.innerText = 'DONE';
                countdown.className = 'text-xs font-mono font-black text-emerald-300 bg-emerald-500/20 border border-emerald-500 px-2 py-0.5 rounded-lg';
            }
            if (input) input.value = code;
            if (box) box.classList.remove('hidden');
        }

        window.copySecretCode = function() {
            const input = document.getElementById('secretCodeInput');
            const btn = document.getElementById('copyCodeBtn');
            if (input && input.value) {
                navigator.clipboard.writeText(input.value);
                if (btn) {
                    btn.innerText = 'Copied!';
                    btn.classList.add('bg-teal-400');
                    setTimeout(() => {
                        btn.innerText = 'Copy';
                        btn.classList.remove('bg-teal-400');
                    }, 2000);
                }
            }
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTaskSession);
        } else {
            initTaskSession();
        }
    })();
    </script>
@endif
