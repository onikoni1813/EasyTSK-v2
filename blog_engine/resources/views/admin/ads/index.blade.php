@extends('layouts.admin')

@section('title', 'Ad Engine Configurations')
@section('page-title', 'Ad Engine: ' . ($site ? $site->name : 'Blog'))

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <!-- Ad Engine Header Card -->
    <div class="p-6 rounded-3xl bg-gradient-to-r from-amber-500/10 via-slate-900 to-slate-950 border border-amber-500/30 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-xl">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-amber-500 text-slate-950">High-CTR Ad Engine</span>
                <span class="text-xs text-amber-400 font-mono">Active Site: {{ $site->name }}</span>
            </div>
            <h3 class="text-lg sm:text-xl font-black text-white">Adsterra, Monetag & Custom Ad Placements</h3>
            <p class="text-xs text-slate-400 mt-1">Configure banner slots, popunder scripts, custom networks, and automated in-content paragraph injection with strict site isolation.</p>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
            <button type="button" onclick="openNewAdModal()" class="px-4 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-extrabold text-xs rounded-xl shadow-lg shadow-amber-500/25 transition flex items-center gap-1.5 transform hover:-translate-y-0.5">
                <span class="text-sm">➕</span> Add New Ad Unit
            </button>
        </div>
    </div>

    <!-- Main Form for Bulk Saving All Placements -->
    <form action="{{ route('admin.ads.save') }}" method="POST" id="adPlacementsForm" class="space-y-6">
        @csrf

        <!-- Ad Units Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @php
                // Merge standard preset slots with any custom slots already in database
                $displayedSlots = $slots;
                foreach($allPlacements as $p) {
                    if (!isset($displayedSlots[$p->placement_slot])) {
                        $displayedSlots[$p->placement_slot] = $p->title ?: ucfirst(str_replace('_', ' ', $p->placement_slot));
                    }
                }
            @endphp

            @foreach($displayedSlots as $slotKey => $slotLabel)
                @php
                    $placement = $existingPlacements->get($slotKey);
                    $isActive = $placement?->is_active ?? false;
                    $network = $placement?->network ?? 'adsterra';
                    $code = $placement?->ad_code ?? '';
                    $placementId = $placement?->id;
                    $isCustom = !array_key_exists($slotKey, $slots);
                @endphp

                <div id="slot-card-{{ $slotKey }}" class="p-5 rounded-2xl bg-slate-950/90 border {{ $isActive ? 'border-amber-500/50 shadow-lg shadow-amber-500/5' : 'border-slate-800' }} space-y-3.5 transition relative flex flex-col justify-between group">
                    <div>
                        <!-- Slot Header with Switch & Delete Button -->
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3 gap-2 flex-wrap">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2.5 h-2.5 rounded-full {{ $isActive ? 'bg-amber-400 animate-pulse' : 'bg-slate-600' }} flex-shrink-0"></span>
                                <div class="truncate">
                                    <h4 class="text-xs font-bold text-white truncate">{{ $placement?->title ?: $slotLabel }}</h4>
                                    <span class="text-[10px] font-mono text-slate-400">Slot: <span class="text-amber-400/90 font-semibold">{{ $slotKey }}</span></span>
                                </div>
                            </div>

                            <div class="flex items-center gap-2.5 flex-shrink-0">
                                <!-- Enable / Disable Toggle -->
                                <label class="flex items-center gap-1.5 cursor-pointer text-xs bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-800">
                                    <input type="checkbox" id="switch-{{ $slotKey }}" name="ads[{{ $slotKey }}][is_active]" value="1" {{ $isActive ? 'checked' : '' }} class="w-3.5 h-3.5 rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500">
                                    <span class="text-[11px] {{ $isActive ? 'text-amber-400 font-semibold' : 'text-slate-400' }}">{{ $isActive ? 'Active' : 'Off' }}</span>
                                </label>

                                <!-- Prominent Delete / Clear Button -->
                                <button type="button" onclick="confirmDeleteAd('{{ $placementId ?: $slotKey }}', '{{ addslashes($placement?->title ?: $slotLabel) }}', {{ $placementId ? 'true' : 'false' }}, '{{ $slotKey }}')" class="px-2.5 py-1 bg-rose-500/10 hover:bg-rose-500/25 text-rose-400 border border-rose-500/30 rounded-lg text-xs font-bold transition flex items-center gap-1" title="Delete / Clear this ad placement">
                                    <span>🗑️</span> Delete
                                </button>
                            </div>
                        </div>

                        <!-- Ad Title & Network Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                            <div>
                                <label class="text-[11px] text-slate-400 font-semibold block mb-1">Ad Title / Label:</label>
                                <input type="text" name="ads[{{ $slotKey }}][title]" value="{{ $placement?->title ?: $slotLabel }}" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-xs text-white focus:border-amber-500 focus:outline-none">
                            </div>

                            <div>
                                <label class="text-[11px] text-slate-400 font-semibold block mb-1">Ad Network / Category:</label>
                                <select name="ads[{{ $slotKey }}][network]" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-xs text-slate-200 focus:border-amber-500 focus:outline-none font-medium">
                                    @foreach($networks as $netKey => $netName)
                                        <option value="{{ $netKey }}" {{ strtolower($network) === strtolower($netKey) ? 'selected' : '' }}>{{ $netName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Ad Script / HTML Code -->
                        <div class="mt-3">
                            <label class="block text-[11px] text-slate-400 font-semibold mb-1">Ad Code / Script (HTML/JS):</label>
                            <textarea id="code-{{ $slotKey }}" name="ads[{{ $slotKey }}][code]" rows="4" placeholder="Paste your Adsterra, Monetag, AdSense, or Custom banner script here..." class="w-full px-3 py-2 bg-slate-900 border border-slate-800 rounded-xl text-xs font-mono text-amber-300 placeholder-slate-600 focus:border-amber-500 focus:outline-none transition leading-tight">{{ $code }}</textarea>
                        </div>
                    </div>

                    <!-- Slot Hints -->
                    <div class="text-[10px] text-slate-500 font-mono border-t border-slate-800/60 pt-2 flex items-center justify-between">
                        <div>
                            @if($slotKey === 'in_content_p2')
                                <span>&bull; Auto-injected after 2nd paragraph of articles.</span>
                            @elseif($slotKey === 'in_content_p5')
                                <span>&bull; Auto-injected after 5th paragraph for long reads.</span>
                            @elseif($slotKey === 'popunder')
                                <span>&bull; Direct Popunder / OnClick script tag for full page monetization.</span>
                            @elseif($slotKey === 'header')
                                <span>&bull; Top billboard banner (728x90 desktop / 320x50 mobile).</span>
                            @elseif($slotKey === 'sidebar_sticky')
                                <span>&bull; Floats smoothly as readers scroll down.</span>
                            @else
                                <span>&bull; Slot: <span class="text-amber-400">{{ $slotKey }}</span></span>
                            @endif
                        </div>

                        @if($isCustom)
                            <span class="px-1.5 py-0.5 rounded text-[9px] bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 font-bold">Custom Unit</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sticky Save Button Bar -->
        <div class="sticky bottom-4 sm:bottom-6 p-3 sm:p-4 bg-slate-900/95 backdrop-blur-md border border-slate-800 rounded-2xl shadow-2xl flex flex-col sm:flex-row items-center justify-between gap-3 z-20">
            <div class="text-[11px] sm:text-xs text-slate-400 text-center sm:text-left">
                ⚡ Changes are isolated to <strong class="text-white">{{ $site->name }}</strong> and cached automatically.
            </div>
            <button type="submit" class="w-full sm:w-auto px-6 sm:px-8 py-2.5 sm:py-3 bg-amber-500 hover:bg-amber-400 font-extrabold text-slate-950 text-xs rounded-xl shadow-lg shadow-amber-500/25 transition">
                Save & Activate Ad Placements
            </button>
        </div>
    </form>
</div>

<!-- Modal: Add New Ad Unit & Network -->
<div id="newAdModal" class="fixed inset-0 bg-slate-950/85 backdrop-blur-md z-50 flex items-center justify-center p-4 hidden">
    <div class="max-w-lg w-full bg-slate-900 border-2 border-amber-500/30 rounded-3xl p-6 sm:p-7 space-y-4 shadow-2xl animate-scale-in">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center text-amber-400 font-bold text-sm">➕</span>
                <div>
                    <h3 class="text-base font-bold text-white">Create New Ad Unit</h3>
                    <p class="text-[11px] text-slate-400">Add standard or custom ad slots and networks</p>
                </div>
            </div>
            <button type="button" onclick="closeNewAdModal()" class="w-8 h-8 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center text-xs font-bold transition">✕</button>
        </div>

        <form action="{{ route('admin.ads.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Ad Unit Title *</label>
                <input type="text" name="title" required placeholder="e.g. PropellerAds Sticky Banner" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Placement Slot *</label>
                    <select name="placement_slot" id="slotSelect" onchange="handleSlotSelection(this.value)" required class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none font-medium">
                        @foreach($slots as $k => $lbl)
                            <option value="{{ $k }}">{{ $lbl }}</option>
                        @endforeach
                        <option value="__custom__" class="text-amber-400 font-bold">➕ Custom Slot Key...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Ad Network / Category *</label>
                    <select name="network" id="networkSelect" onchange="handleNetworkSelection(this.value)" required class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:border-amber-500 focus:outline-none font-medium">
                        @foreach($networks as $netKey => $netName)
                            <option value="{{ $netKey }}">{{ $netName }}</option>
                        @endforeach
                        <option value="__custom_network__" class="text-amber-400 font-bold">➕ Add New Ad Network...</option>
                    </select>
                </div>
            </div>

            <!-- Custom Slot Key Input (Shown if custom slot selected) -->
            <div id="customSlotInputBox" class="hidden p-3 bg-slate-950 rounded-xl border border-amber-500/40 space-y-1">
                <label class="block text-xs font-bold text-amber-400">Custom Slot Identifier (snake_case) *</label>
                <input type="text" id="customSlotInput" placeholder="e.g. footer_sticky, in_content_p3, popup_banner" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-xs font-mono text-amber-300 focus:border-amber-500 focus:outline-none">
                <p class="text-[10px] text-slate-400">You can render this in your theme via &lt;?php echo AdEngine::render('your_slot_key'); ?&gt;</p>
            </div>

            <!-- Custom Network Input (Shown if custom network selected) -->
            <div id="customNetworkInputBox" class="hidden p-3 bg-slate-950 rounded-xl border border-amber-500/40 space-y-1">
                <label class="block text-xs font-bold text-amber-400">New Ad Network Name *</label>
                <input type="text" name="custom_network" id="customNetworkInput" placeholder="e.g. AdMaven, Galaksion, ClickAdu, Ezoic, Media.net" class="w-full px-3 py-1.5 bg-slate-900 border border-slate-800 rounded-lg text-xs text-emerald-300 focus:border-amber-500 focus:outline-none font-semibold">
                <p class="text-[10px] text-slate-400">This new ad network category will be saved and available in all ad dropdowns.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1">Ad Code / Script (HTML/JS)</label>
                <textarea name="ad_code" rows="4" placeholder="<script src='//...js'></script> or <div>...</div>" class="w-full px-3.5 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs font-mono text-amber-300 placeholder-slate-600 focus:border-amber-500 focus:outline-none leading-tight"></textarea>
            </div>

            <div class="flex items-center gap-2 pt-1">
                <input type="checkbox" name="is_active" id="modalIsActive" value="1" checked class="w-4 h-4 rounded bg-slate-950 border-slate-800 text-amber-500 focus:ring-amber-500">
                <label for="modalIsActive" class="text-xs font-bold text-slate-200 cursor-pointer">Activate this Ad Unit immediately</label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-800">
                <button type="button" onclick="closeNewAdModal()" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2.5 bg-amber-500 hover:bg-amber-400 text-slate-950 text-xs font-extrabold rounded-xl shadow-lg shadow-amber-500/20 transition">
                    Create Ad Unit
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Delete Form Helper -->
<form id="deleteAdForm" method="POST" class="hidden">
    @csrf
    @method('DELETE')
</form>

<script>
function openNewAdModal() {
    document.getElementById('newAdModal').classList.remove('hidden');
}

function closeNewAdModal() {
    document.getElementById('newAdModal').classList.add('hidden');
}

function handleSlotSelection(val) {
    const customBox = document.getElementById('customSlotInputBox');
    const customInput = document.getElementById('customSlotInput');
    const slotSelect = document.getElementById('slotSelect');

    if (val === '__custom__') {
        customBox.classList.remove('hidden');
        customInput.setAttribute('required', 'required');
        customInput.setAttribute('name', 'placement_slot');
        slotSelect.removeAttribute('name');
    } else {
        customBox.classList.add('hidden');
        customInput.removeAttribute('required');
        customInput.removeAttribute('name');
        slotSelect.setAttribute('name', 'placement_slot');
    }
}

function handleNetworkSelection(val) {
    const customBox = document.getElementById('customNetworkInputBox');
    const customInput = document.getElementById('customNetworkInput');

    if (val === '__custom_network__') {
        customBox.classList.remove('hidden');
        customInput.setAttribute('required', 'required');
    } else {
        customBox.classList.add('hidden');
        customInput.removeAttribute('required');
        customInput.value = '';
    }
}

function confirmDeleteAd(idOrSlot, adTitle, isSavedInDb, slotKey) {
    if (confirm('Are you sure you want to delete / remove the Ad Unit "' + adTitle + '"?')) {
        if (isSavedInDb) {
            // Delete from database via DELETE request
            const form = document.getElementById('deleteAdForm');
            form.action = '{{ url("/admin/ads") }}/' + idOrSlot;
            form.submit();
        } else {
            // Clear inputs in DOM
            const codeBox = document.getElementById('code-' + slotKey);
            const switchBox = document.getElementById('switch-' + slotKey);
            if (codeBox) codeBox.value = '';
            if (switchBox) switchBox.checked = false;
            
            // Auto submit form to save cleared state
            document.getElementById('adPlacementsForm').submit();
        }
    }
}
</script>
@endsection
