/**
 * Universal Anti-AdBlock Master Suite
 * Detects:
 *  - uBlock Origin Lite (Basic, Optimal, Complete)
 *  - uBlock Origin / AdBlock Plus / AdGuard (All filtering levels)
 *  - Brave Shields & Opera AdBlocker
 *  - Network & DNS AdBlockers (Pi-hole, AdGuard DNS)
 */
(function() {
    let adblockDetected = false;

    function lockScreenWithAdblock() {
        if (adblockDetected) return;
        adblockDetected = true;

        const overlay = document.getElementById('globalAdblockOverlay');
        if (overlay) {
            overlay.classList.remove('hidden');
            overlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        const timerStatus = document.getElementById('timerStatusText');
        if (timerStatus) {
            timerStatus.innerHTML = '<span class="text-rose-400 font-bold">⚠️ AdBlocker detected! Disable to continue.</span>';
        }
    }

    // -------------------------------------------------------------
    // TEST 1: DOM Cosmetic Filtering Trap (Complete / Optimal Mode)
    // -------------------------------------------------------------
    function testDomBait() {
        const bait = document.getElementById('ad-bait-element');
        if (bait) {
            const computed = window.getComputedStyle(bait);
            if (
                computed.display === 'none' ||
                computed.visibility === 'hidden' ||
                bait.offsetHeight === 0 ||
                bait.clientHeight === 0
            ) {
                lockScreenWithAdblock();
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------
    // TEST 2: Real Ad Network Script Injection & Integrity Check
    // (Catches Basic Mode: uBlock Origin Lite / MV3 declarativeNetRequest)
    // -------------------------------------------------------------
    function testAdNetworkScript() {
        const script = document.createElement('script');
        script.type = 'text/javascript';
        script.async = true;
        script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456';
        script.crossOrigin = 'anonymous';

        // Case A: Basic mode blocks the script -> onerror fires
        script.onerror = function() {
            lockScreenWithAdblock();
        };

        // Case B: Basic mode redirects script to empty noop -> onload fires but window.adsbygoogle is NOT functional
        script.onload = function() {
            setTimeout(function() {
                // Real adsbygoogle script attaches functions or arrays to window
                if (typeof window.adsbygoogle === 'undefined' && typeof window.google_ad_client === 'undefined') {
                    // Check if it was stubbed/emptied by uBOL
                    // We verify DoubleClick gpt as secondary check
                    testDoubleClickGpt();
                }
            }, 300);
        };

        document.head.appendChild(script);
    }

    function testDoubleClickGpt() {
        const gpt = document.createElement('script');
        gpt.type = 'text/javascript';
        gpt.async = true;
        gpt.src = 'https://securepubads.g.doubleclick.net/tag/js/gpt.js';

        gpt.onerror = function() {
            lockScreenWithAdblock();
        };

        gpt.onload = function() {
            setTimeout(function() {
                if (typeof window.googletag === 'undefined') {
                    lockScreenWithAdblock();
                }
            }, 300);
        };

        document.head.appendChild(gpt);
    }

    // -------------------------------------------------------------
    // TEST 3: Network Fetch Probes to Ad Endpoints
    // (Catches DNS blockers & Basic declarativeNetRequest)
    // -------------------------------------------------------------
    function testNetworkFetchProbes() {
        const blockedTargets = [
            'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js',
            'https://securepubads.g.doubleclick.net/tag/js/gpt.js',
            'https://adservice.google.com/adsid/integrator.js',
            'https://static.monetag.com/tag.min.js'
        ];

        blockedTargets.forEach(function(target) {
            fetch(new Request(target, { method: 'GET', mode: 'no-cors', cache: 'no-store' }))
                .then(function(res) {
                    // If response is blocked, type might be error
                })
                .catch(function(err) {
                    // Network error thrown by client extension
                    lockScreenWithAdblock();
                });
        });
    }

    // -------------------------------------------------------------
    // TEST 4: Fake Google Ad Ins Unit Simulation
    // -------------------------------------------------------------
    function testAdUnitInsertion() {
        try {
            const ins = document.createElement('ins');
            ins.className = 'adsbygoogle';
            ins.style.cssText = 'display:block !important; position:absolute !important; top:-999px !important; left:-999px !important; width:300px !important; height:250px !important;';
            ins.setAttribute('data-ad-client', 'ca-pub-1234567890123456');
            ins.setAttribute('data-ad-slot', '1234567890');
            document.body.appendChild(ins);

            setTimeout(function() {
                const style = window.getComputedStyle(ins);
                if (
                    style.display === 'none' ||
                    style.visibility === 'hidden' ||
                    ins.offsetHeight === 0
                ) {
                    lockScreenWithAdblock();
                }
                ins.remove();
            }, 400);
        } catch (e) {}
    }

    // -------------------------------------------------------------
    // TEST 5: EasyList Bait Script
    // -------------------------------------------------------------
    function testBaitScript() {
        if (typeof window.__adblock_passed === 'undefined') {
            lockScreenWithAdblock();
            return true;
        }
        return false;
    }

    // Run entire detection sequence
    function runAllDetectionTiers() {
        testDomBait();
        testBaitScript();
        testAdNetworkScript();
        testNetworkFetchProbes();
        testAdUnitInsertion();
    }

    // Lifecycle triggers
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(runAllDetectionTiers, 150);
        });
    } else {
        setTimeout(runAllDetectionTiers, 150);
    }

    window.addEventListener('load', function() {
        setTimeout(runAllDetectionTiers, 300);

        // Continuous heartbeat check
        setInterval(function() {
            testDomBait();
            if (typeof window.__adblock_passed === 'undefined') {
                lockScreenWithAdblock();
            }
        }, 1500);
    });
})();
