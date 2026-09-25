@extends('layouts.app')

@section('title', 'Evacuees')
@section('nav-families', 'active')

@section('content')
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <h1 class="text-xl font-semibold mb-1">Evacuees</h1>
            <p class="text-sm text-gray-500">List of registered evacuee families and their members.</p>
        </div>
        {{-- Hidden per CSWDO: outside_center registration has no real
            operational use for them (people not physically at a center
            aren't covered by relief distribution, so this was never
            actually used in practice) -- EC Board's "Add Evacuee" already
            fully covers inside_center registration. UI visibility only:
            the modal below, /families/register, and /families/create all
            stay fully intact and reachable directly, in case this is
            needed again later. --}}
        <div class="hidden shrink-0 text-right">
            <button type="button" id="register-family-btn"
                class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                + Register a family
            </button>
            <p class="text-xs text-gray-500 mt-1 max-w-[220px]">For families outside a center, or to enter full details directly</p>
        </div>
    </div>

    {{-- Global search: independent of the barangay -> center -> family
        drill-down below -- finds a specific evacuee by name no matter
        which barangay/center they're actually in, so family-reunification
        lookups never get slower because of the drill-down reorganization. --}}
    <div class="relative mb-6">
        <div class="relative">
            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 16px;" aria-hidden="true"></i>
            <input id="global-search-input" type="text" autocomplete="off"
                placeholder="Search any evacuee by name -- jumps straight to their family, regardless of barangay or center..."
                class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2.5 text-sm bg-white">
        </div>
        <div id="global-search-results" class="hidden absolute z-40 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-lg max-h-80 overflow-y-auto"></div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Families</p>
                <p id="stat-families" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-500 italic mt-1">Currently registered, active event(s)</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-home text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Total persons</p>
                <p id="stat-persons" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-500 italic mt-1">Currently displaced, active event(s)</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Children (0-17)</p>
                <p id="stat-children" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-children-pct" class="text-xs text-gray-500 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-baby-carriage text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #8B5CF6;">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">Seniors (60+)</p>
                <p id="stat-seniors" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-seniors-pct" class="text-xs text-gray-500 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background: #F3EEFF;">
                <i class="ti ti-walk text-purple-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #EF4444;">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-1">PWD members</p>
                <p id="stat-pwd" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-pwd-pct" class="text-xs text-gray-500 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-wheelchair text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            {{-- Barangay -> center -> family drill-down. "All barangays" is
                always clickable to jump back to the landing view; the
                current level's own label is plain text, not a link. --}}
            <nav id="drill-breadcrumb" class="flex items-center gap-1.5 text-sm text-gray-500 mb-4"></nav>

            {{-- Level 1 (default/landing view): one row per barangay. --}}
            <div id="barangay-summary-view">
                <div id="barangay-empty-state" class="hidden flex-col items-center text-center py-20 bg-white border border-gray-200 rounded-xl">
                    <i class="ti ti-users text-gray-300 mb-3" style="font-size: 40px;" aria-hidden="true"></i>
                    <p class="text-sm font-medium text-gray-600 mb-1">No families registered yet</p>
                    <p class="text-sm text-gray-500 mb-4">Registrations will appear here as barangay officials add them.</p>
                    {{-- Hidden per CSWDO -- see the header button's own comment above. --}}
                    <button type="button" id="register-family-empty-btn" class="hidden text-sm text-brand hover:underline">+ Register the first family</button>
                </div>
                <div id="barangay-table-wrap" class="hidden bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="text-left px-4 py-3">Barangay</th>
                                    <th class="text-left px-4 py-3">Families</th>
                                    <th class="text-left px-4 py-3">Persons</th>
                                    <th class="text-left px-4 py-3">Pending details</th>
                                    <th class="text-left px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody id="barangay-summary-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Level 2: evacuation centers within the selected barangay. --}}
            <div id="center-summary-view" class="hidden">
                {{-- Hidden per CSWDO -- see the header button's own comment above. --}}
                <button type="button" id="register-in-barangay-btn" class="hidden text-sm text-brand hover:underline mb-3"></button>
                <div id="center-table-wrap" class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="text-left px-4 py-3">Evacuation center</th>
                                    <th class="text-left px-4 py-3">Families</th>
                                    <th class="text-left px-4 py-3">Persons</th>
                                    <th class="text-left px-4 py-3">Pending details</th>
                                    <th class="text-left px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody id="center-summary-tbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Level 3: the actual family list -- unchanged from before,
                just reached via drill-down instead of being the landing view. --}}
            <div id="family-list-view" class="hidden">
                <div class="flex flex-wrap items-center justify-end gap-3 mb-4">
                    {{-- Hidden per CSWDO -- see the header button's own comment above. --}}
                    <button type="button" id="register-at-center-btn" class="hidden text-sm text-brand hover:underline"></button>
                    <select id="sectoral-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All sectoral groups</option>
                        <option value="is_4ps_beneficiary">4Ps beneficiary</option>
                        <option value="is_pwd">PWD</option>
                        <option value="senior">Senior citizen</option>
                        <option value="is_pregnant">Pregnant</option>
                        <option value="is_lactating">Lactating</option>
                        <option value="is_solo_parent">Solo parent</option>
                        <option value="is_indigenous_person">Indigenous person</option>
                    </select>
                    <button id="export-btn" class="flex items-center gap-1.5 text-brand border border-brand/30 rounded-lg px-3 py-2 text-sm hover:bg-brand-light">
                        <i class="ti ti-download" style="font-size: 15px;" aria-hidden="true"></i> Export
                    </button>
                </div>

                <div id="empty-state" class="hidden flex-col items-center text-center py-20 bg-white border border-gray-200 rounded-xl">
                    <i class="ti ti-users text-gray-300 mb-3" style="font-size: 40px;" aria-hidden="true"></i>
                    <p class="text-sm font-medium text-gray-600 mb-1">No families here yet</p>
                    <p class="text-sm text-gray-500 mb-4">Registrations will appear here as barangay officials add them.</p>
                </div>

                <div id="table-wrap" class="hidden bg-white border border-gray-200 rounded-xl overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                                <tr>
                                    <th class="text-left px-4 py-3">Family head</th>
                                    <th class="text-left px-4 py-3">Barangay</th>
                                    <th class="text-left px-4 py-3">Persons</th>
                                    <th class="text-left px-4 py-3">Evacuation center</th>
                                    <th class="text-left px-4 py-3">Sectoral tags</th>
                                    <th class="text-left px-4 py-3">Date registered</th>
                                    <th class="text-left px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody id="families-tbody"></tbody>
                        </table>
                    </div>
                    <div class="px-4 py-3 border-t border-gray-100 text-xs text-gray-500">
                        Showing <span id="showing-count">0</span> of <span id="total-count">0</span> families
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Sex distribution</p>
                <div class="flex items-center gap-4">
                    <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                        <canvas id="sexChart" role="img" aria-label="Doughnut chart of male vs female evacuees"></canvas>
                    </div>
                    <div id="sex-legend" class="flex-1 space-y-2 text-sm"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Age distribution</p>
                <div id="age-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            {{-- Swaps between "Top barangays" (city-wide), "Top evacuation
                centers" (drilled into one barangay -- ranking barangays
                when there's only one in scope is meaningless), and hidden
                entirely (drilled into one center -- nothing left to rank).
                See renderRankingCard(). --}}
            <div id="ranking-card" class="bg-white border border-gray-200 rounded-xl p-4">
                <p id="ranking-card-title" class="text-sm font-semibold text-gray-700 mb-3">Top barangays by evacuees</p>
                <div id="ranking-card-body" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Sectoral summary</p>
                <div id="sectoral-summary" class="grid grid-cols-2 gap-3"></div>
            </div>
        </div>
    </div>

    <div id="family-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800">Register a family</p>
                    <p class="text-xs text-gray-500">For families staying outside an evacuation center (with relatives, etc.), or to register full details directly. For someone physically at a center right now, that center's EC Board "Add Evacuee" is faster.</p>
                </div>
                <button type="button" id="family-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="family-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="register-form" class="flex flex-col gap-6 p-5">
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Barangay</label>
                        <select id="f-barangay_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Disaster event</label>
                        <select id="f-evacuation_event_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Displacement type</label>
                        {{-- Outside-center listed (and defaulted to) first:
                            this form's primary real use case now that
                            inside-center registration normally happens
                            through the faster EC Board "Add Evacuee" --
                            see openFamilyModal()'s prefill handling for
                            when this still switches to inside_center
                            automatically (opened from a center's own
                            drill-down context). --}}
                        <select id="f-displacement_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="outside_center">Outside (evacuated to relatives/other location)</option>
                            <option value="inside_center">Inside an evacuation center</option>
                        </select>
                    </div>
                    <div id="f-center-field">
                        <label class="text-sm text-gray-600 block mb-1">Evacuation center</label>
                        <select id="f-evacuation_center_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600 col-span-2">
                        <input type="checkbox" id="f-is_4ps_beneficiary"> Family is a 4Ps beneficiary
                    </label>
                </div>

                {{-- Quick headcount registration was removed from here -- the
                    EC Information Board (evacuation center detail page) now
                    serves that purpose: its age/sex breakdown generates real
                    placeholder evacuees directly, so staff enter a fast
                    headcount there instead of a second, separate place.
                    Full-detail registration is the only path here. --}}
                <div id="f-full-mode-section">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-medium text-gray-700">Family members</h2>
                        <button type="button" id="f-add-member-btn" class="text-sm text-brand hover:underline">+ Add another member</button>
                    </div>
                    <div id="f-members-container" class="flex flex-col gap-4"></div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="family-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="f-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                        Register family
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    let allFamilies = [];
    let sexChartInstance = null;

    // Real, non-truncated totals from /families/stats -- allFamilies (below)
    // is capped at per_page=200 for the table/breakdown cards, so the true
    // family/person counts (and "Showing X of Y") are tracked separately
    // rather than derived from that array's length.
    let totalFamilies = 0;
    let totalPersonsCount = 0;

    const AVATAR_COLORS = ['#2563EB', '#16A34A', '#D97706', '#DB2777', '#7C3AED', '#0891B2'];

    function avatarFor(name) {
        const label = name || '?';
        const initials = label.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase() || '?';
        let hash = 0;
        for (let i = 0; i < label.length; i++) hash = label.charCodeAt(i) + ((hash << 5) - hash);
        const color = AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
        return `<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold shrink-0" style="background:${color}">${initials}</div>`;
    }

    const AGE_BRACKETS = [
        ['infant', 'Infant (0-6 mo)'],
        ['toddler', 'Toddler (7-24 mo)'],
        ['preschooler', 'Preschooler (2-5 yrs)'],
        ['school_age', 'School age (6-12 yrs)'],
        ['teenage', 'Teenage (13-17 yrs)'],
        ['adult', 'Adult (18-59 yrs)'],
        ['senior_citizen', 'Senior citizen (60+ yrs)'],
    ];

    function allMembers(families) {
        return families.flatMap((f) => f.members ?? []);
    }

    function renderTable(families) {
        if (families.length === 0) {
            document.getElementById('table-wrap').classList.add('hidden');
            document.getElementById('empty-state').classList.remove('hidden');
            document.getElementById('empty-state').classList.add('flex');
            return;
        }

        document.getElementById('empty-state').classList.add('hidden');
        document.getElementById('table-wrap').classList.remove('hidden');

        const tbody = document.getElementById('families-tbody');
        tbody.innerHTML = families.map((f) => {
            const tags = [];
            if (f.is_4ps_beneficiary) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700">4Ps</span>');
            if (f.has_pwd_member) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-red-50 text-red-700">PWD</span>');
            if (f.has_senior_member) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-purple-50 text-purple-700">Senior</span>');
            if (f.has_lactating_member) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-pink-50 text-pink-700">Lactating</span>');
            if ((f.members ?? []).some((m) => m.sectoral?.is_pregnant)) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700">Pregnant</span>');

            const pendingCount = (f.members ?? []).filter((m) => m.is_placeholder).length;
            if (pendingCount > 0) tags.push(`<span class="text-xs px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800">${pendingCount} pending</span>`);

            // f.name is set when this household was created via the EC
            // Board's "Add Evacuee -> New household" path (see
            // FamilyResource) -- a normally-registered family has no name
            // column at all and is identified by its head of family instead.
            const headName = f.name || f.head_of_family?.full_name || f.members?.[0]?.full_name || '';

            return `
            <tr class="border-t border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        ${avatarFor(headName)}
                        <span class="font-medium">${headName || '&mdash;'}</span>
                    </div>
                </td>
                <td class="px-4 py-3">${f.barangay?.name ?? '&mdash;'}</td>
                <td class="px-4 py-3">
                    <span class="inline-flex items-center gap-1.5">
                        <i class="ti ti-users text-gray-400" style="font-size: 14px;" aria-hidden="true"></i>
                        ${f.member_count ?? '&mdash;'}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600">${f.evacuation_center?.name ?? '&mdash;'}</td>
                <td class="px-4 py-3"><div class="flex flex-wrap gap-1">${tags.join('') || '<span class="text-gray-300 text-xs">&mdash;</span>'}</div></td>
                <td class="px-4 py-3 text-gray-500">${new Date(f.created_at).toLocaleDateString()}</td>
                <td class="px-4 py-3"><a href="/families/${f.id}" class="text-brand hover:underline">View</a></td>
            </tr>`;
        }).join('');
    }

    // sectoralQuickCount: { child_headed_family: {male, female}, single_headed_family: {male, female} }
    // -- see fetchSectoralQuickCountSummary() and the endpoint's own
    // docblock for why these two specifically come from a different source
    // than the other six.
    function renderSidebar(families, sectoralQuickCount) {
        const members = allMembers(families);
        const total = members.length || 1;

        // Sex distribution
        const male = members.filter((m) => m.sex === 'male').length;
        const female = members.filter((m) => m.sex === 'female').length;
        document.getElementById('sex-legend').innerHTML = `
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#3B82F6"></span>Male</span>
                <span class="font-medium text-gray-800">${male} <span class="text-gray-500 font-normal">(${Math.round(male / total * 100)}%)</span></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#EC4899"></span>Female</span>
                <span class="font-medium text-gray-800">${female} <span class="text-gray-500 font-normal">(${Math.round(female / total * 100)}%)</span></span>
            </div>`;

        if (sexChartInstance) sexChartInstance.destroy();
        sexChartInstance = new Chart(document.getElementById('sexChart'), {
            type: 'doughnut',
            data: {
                labels: ['Male', 'Female'],
                datasets: [{ data: [male, female], backgroundColor: ['#3B82F6', '#EC4899'], borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
        });

        // Age distribution
        const ageCounts = AGE_BRACKETS.map(([key]) => members.filter((m) => m.age_bracket === key).length);
        const maxAge = Math.max(...ageCounts, 1);
        document.getElementById('age-distribution').innerHTML = AGE_BRACKETS.map(([key, label], i) => `
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-gray-600">${label}</span>
                    <span class="font-medium text-gray-800">${ageCounts[i]}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width:${ageCounts[i] / maxAge * 100}%"></div>
                </div>
            </div>`).join('');

        // Sectoral summary -- the first six are live, per-evacuee flags
        // (scoped automatically since they're computed from `members`,
        // whatever set that is). The last two (Child/Single-Headed Family)
        // have no such flag anywhere on Family/Evacuee at all -- they only
        // exist as an EC-Board-reported aggregate (see
        // sectoralQuickCountSummary()'s own docblock), so they're summed
        // from sectoralQuickCount instead of filtered from `members`.
        const sectoral = [
            ['is_4ps_beneficiary', '4Ps beneficiary', 'ti-gift', 'text-blue-500', 'bg-blue-50'],
            ['is_pwd', 'PWD', 'ti-wheelchair', 'text-red-500', 'bg-red-50'],
            ['is_pregnant', 'Pregnant', 'ti-baby-carriage', 'text-amber-500', 'bg-amber-50'],
            ['is_lactating', 'Lactating', 'ti-droplet', 'text-pink-500', 'bg-pink-50'],
            ['is_solo_parent', 'Solo parent', 'ti-user-check', 'text-purple-500', 'bg-purple-50'],
            ['is_indigenous_person', 'Indigenous', 'ti-leaf', 'text-green-500', 'bg-green-50'],
        ];
        const sectoralCards = sectoral.map(([key, label, icon, color, bg]) =>
            sectoralCardHtml(members.filter((m) => m.sectoral?.[key]).length, label, icon, color, bg));

        const childHeaded = sectoralQuickCount?.child_headed_family ?? { male: 0, female: 0 };
        const singleHeaded = sectoralQuickCount?.single_headed_family ?? { male: 0, female: 0 };
        sectoralCards.push(sectoralCardHtml(childHeaded.male + childHeaded.female, 'Child-headed family', 'ti-baby', 'text-cyan-500', 'bg-cyan-50'));
        sectoralCards.push(sectoralCardHtml(singleHeaded.male + singleHeaded.female, 'Single-headed family', 'ti-user', 'text-teal-500', 'bg-teal-50'));

        document.getElementById('sectoral-summary').innerHTML = sectoralCards.join('');
    }

    function sectoralCardHtml(count, label, icon, color, bg) {
        return `
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-md ${bg} flex items-center justify-center shrink-0">
                    <i class="ti ${icon} ${color}" style="font-size:14px;" aria-hidden="true"></i>
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-semibold text-gray-800">${count}</p>
                    <p class="text-[11px] text-gray-500">${label}</p>
                </div>
            </div>`;
    }

    // Swaps the sidebar's 4th card between "Top barangays" (city-wide --
    // ranking barangays only makes sense when several are in view),
    // "Top evacuation centers" (drilled into one barangay -- ranking ITS
    // centers is the equivalent question one level down), and hidden
    // entirely (drilled into one center -- nothing left to rank).
    // mode: 'barangays' | 'centers' | 'hidden'.
    function renderRankingCard(mode, families, centerRows) {
        const card = document.getElementById('ranking-card');

        if (mode === 'hidden') {
            card.classList.add('hidden');
            return;
        }
        card.classList.remove('hidden');

        let entries; // [label, count][]
        if (mode === 'barangays') {
            document.getElementById('ranking-card-title').textContent = 'Top barangays by evacuees';
            const byBarangay = {};
            families.forEach((f) => {
                const name = f.barangay?.name ?? 'Unassigned';
                byBarangay[name] = (byBarangay[name] ?? 0) + (f.member_count ?? 0);
            });
            entries = Object.entries(byBarangay).sort((a, b) => b[1] - a[1]).slice(0, 5);
        } else {
            document.getElementById('ranking-card-title').textContent = 'Top evacuation centers';
            entries = (centerRows ?? [])
                .filter((r) => r.evacuation_center_id) // excludes the "Outside center / unassigned" bucket from a ranking of CENTERS
                .map((r) => [r.evacuation_center_name, r.person_count])
                .sort((a, b) => b[1] - a[1])
                .slice(0, 5);
        }

        const max = Math.max(...entries.map(([, c]) => c), 1);
        document.getElementById('ranking-card-body').innerHTML = entries.length
            ? entries.map(([name, count]) => `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">${name}</span>
                        <span class="font-medium text-gray-800">${count}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width:${count / max * 100}%"></div>
                    </div>
                </div>`).join('')
            : '<p class="text-gray-500">No data yet.</p>';
    }

    // Fire-and-forget-safe: falls back to zeros on any error, same
    // defensive convention as every other summary fetch on this page, so a
    // failure here never blocks the rest of the sidebar from rendering.
    async function fetchSectoralQuickCountSummary(params = {}) {
        const fallback = { child_headed_family: { male: 0, female: 0 }, single_headed_family: { male: 0, female: 0 } };
        try {
            const query = new URLSearchParams(
                Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ''))
            ).toString();
            const result = await Api.get(`/families/sectoral-quick-count-summary${query ? `?${query}` : ''}`);
            return result.data;
        } catch (error) {
            return fallback;
        }
    }

    function renderStatCards(families) {
        const members = allMembers(families);
        const children = members.filter((m) => m.age < 18).length;
        const seniors = members.filter((m) => m.age_bracket === 'senior_citizen').length;
        const pwd = members.filter((m) => m.sectoral?.is_pwd).length;

        // Families/Total persons use the real counts already resolved for
        // the current scope (totalFamilies/totalPersonsCount -- see
        // refreshScopedSummary()) rather than families.length/members.length
        // -- this page's fetched families arrays are capped at per_page=200,
        // so those would silently undercount past 200 with no indication
        // anything was truncated.
        document.getElementById('stat-families').textContent = totalFamilies;
        document.getElementById('stat-persons').textContent = totalPersonsCount;
        document.getElementById('stat-children').textContent = children;
        document.getElementById('stat-seniors').textContent = seniors;
        document.getElementById('stat-pwd').textContent = pwd;

        // Percentages are still computed against this page's own fetched
        // members (not totalPersonsCount) -- children/seniors/pwd are only
        // known for the families actually fetched, so the percentage has to
        // be relative to that same set to stay internally consistent.
        const totalPersons = members.length;
        const pct = (n) => totalPersons ? `${Math.round(n / totalPersons * 100)}% of total persons` : '&mdash;';
        document.getElementById('stat-children-pct').innerHTML = pct(children);
        document.getElementById('stat-seniors-pct').innerHTML = pct(seniors);
        document.getElementById('stat-pwd-pct').innerHTML = pct(pwd);
    }

    // The family-list-view's own filter (sectoral group only -- name
    // search is handled globally now, see global-search-input further
    // below) applies on top of whichever barangay/center the user has
    // already drilled into, not the whole allFamilies array.
    function applyFilters() {
        const sectoral = document.getElementById('sectoral-filter').value;

        const filtered = familiesInCurrentDrill.filter((f) => {
            if (sectoral === 'is_4ps_beneficiary') {
                return f.is_4ps_beneficiary || (f.members ?? []).some((m) => m.sectoral?.is_4ps_beneficiary);
            }
            if (sectoral === 'senior') {
                return f.has_senior_member || (f.members ?? []).some((m) => m.age_bracket === 'senior_citizen');
            }
            if (sectoral) {
                return (f.members ?? []).some((m) => m.sectoral?.[sectoral]);
            }
            return true;
        });

        document.getElementById('showing-count').textContent = filtered.length;
        document.getElementById('total-count').textContent = familiesInCurrentDrill.length;
        renderTable(filtered);
    }

    document.getElementById('sectoral-filter').addEventListener('change', applyFilters);

    // Client-side CSV export -- exports whatever the family-list-view is
    // currently showing (i.e. respects the barangay/center drill-down and
    // the sectoral filter), not every family in the system.
    document.getElementById('export-btn').addEventListener('click', () => {
        const rows = [['Family Head', 'Barangay', 'Persons', 'Evacuation Center', '4Ps', 'PWD', 'Senior', 'Lactating', 'Date Registered']];
        familiesInCurrentDrill.forEach((f) => {
            rows.push([
                f.name || f.head_of_family?.full_name || '', f.barangay?.name ?? '', f.member_count ?? 0,
                f.evacuation_center?.name ?? '', f.is_4ps_beneficiary ? 'Yes' : 'No',
                f.has_pwd_member ? 'Yes' : 'No', f.has_senior_member ? 'Yes' : 'No',
                f.has_lactating_member ? 'Yes' : 'No', new Date(f.created_at).toLocaleDateString(),
            ]);
        });
        const csv = rows.map((r) => r.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `evacuees-${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
    });

    // Named (not an inline IIFE) so it can be called again after a
    // successful registration from the modal. Only fetches the city-wide
    // data + totals and caches them -- doesn't render anything itself
    // anymore; refreshScopedSummary() does that, since which set of
    // families/totals actually needs rendering depends on the current
    // drill level, not just the city-wide one.
    async function loadFamilies() {
        try {
            // /families/stats returns real, non-paginated counts (and
            // defaults to CURRENT state only -- non-closed events) --
            // decoupled from /families?per_page=200's own page size, so the
            // stat cards below are never silently truncated once total
            // families pass 200.
            const [familiesResult, statsResult] = await Promise.all([
                Api.get('/families?per_page=200'),
                Api.get('/families/stats'),
            ]);

            allFamilies = familiesResult.data.data;
            cityWideTotals = statsResult.data;
        } catch (error) {
            showFormErrors(error);
        }
    }

    // --- Barangay -> center -> family drill-down ----------------------------
    // Three mutually-exclusive view levels. currentBarangayId/currentCenterId
    // track where the user currently is; null means "not drilled into yet".
    // currentCenterId is the string 'none' specifically for the "Outside
    // center / unassigned" bucket (a real center's id is always a number).

    let currentBarangayId = null;
    let currentBarangayName = '';
    let currentCenterId = null;
    let currentCenterName = '';
    let familiesInCurrentDrill = [];
    // Barangay-scoped source familiesInCurrentDrill filters from once
    // drilled in -- fetched fresh per barangay (not sliced from allFamilies,
    // which is capped at per_page=200 city-wide) so a single barangay's own
    // families/summary cards are never silently truncated either.
    let familiesInCurrentBarangay = [];
    // Cached so the barangay-level ranking card ("Top evacuation centers")
    // and the center-level stat totals can both reuse it without a second
    // fetch -- see refreshScopedSummary().
    let centerSummaryRowsCache = [];
    let cityWideTotals = { households: 0, total_persons: 0 };

    function showDrillLevel(level) {
        document.getElementById('barangay-summary-view').classList.toggle('hidden', level !== 'barangay');
        document.getElementById('center-summary-view').classList.toggle('hidden', level !== 'center');
        document.getElementById('family-list-view').classList.toggle('hidden', level !== 'family');
    }

    function renderBreadcrumb() {
        const parts = [];
        const atBarangayLevel = currentBarangayId === null;
        const atCenterLevel = currentBarangayId !== null && currentCenterId === null;

        parts.push(atBarangayLevel
            ? '<span class="text-gray-700 font-medium">All barangays</span>'
            : '<a href="#" data-goto="barangay" class="hover:text-brand hover:underline">All barangays</a>');

        if (currentBarangayId !== null) {
            parts.push('<i class="ti ti-chevron-right" style="font-size:12px" aria-hidden="true"></i>');
            parts.push(atCenterLevel
                ? `<span class="text-gray-700 font-medium">${currentBarangayName}</span>`
                : `<a href="#" data-goto="center" class="hover:text-brand hover:underline">${currentBarangayName}</a>`);
        }

        if (currentCenterId !== null) {
            parts.push('<i class="ti ti-chevron-right" style="font-size:12px" aria-hidden="true"></i>');
            parts.push(`<span class="text-gray-700 font-medium">${currentCenterName}</span>`);
        }

        document.getElementById('drill-breadcrumb').innerHTML = parts.join(' ');
    }

    document.getElementById('drill-breadcrumb').addEventListener('click', (e) => {
        const link = e.target.closest('[data-goto]');
        if (! link) return;
        e.preventDefault();

        if (link.dataset.goto === 'barangay') {
            goToBarangayLevel();
        } else if (link.dataset.goto === 'center') {
            goToCenterLevel();
        }
    });

    function renderBarangaySummaryTable(rows) {
        const emptyState = document.getElementById('barangay-empty-state');
        const tableWrap = document.getElementById('barangay-table-wrap');

        if (rows.length === 0) {
            tableWrap.classList.add('hidden');
            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
            return;
        }

        emptyState.classList.add('hidden');
        tableWrap.classList.remove('hidden');

        document.getElementById('barangay-summary-tbody').innerHTML = rows.map((r) => `
            <tr class="border-t border-gray-100 hover:bg-gray-50 cursor-pointer" data-barangay-id="${r.barangay_id}" data-barangay-name="${r.barangay_name}">
                <td class="px-4 py-3 font-medium">${r.barangay_name}</td>
                <td class="px-4 py-3">${r.family_count}</td>
                <td class="px-4 py-3">${r.person_count}</td>
                <td class="px-4 py-3">${r.pending_count > 0
                    ? `<span class="text-xs px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800">${r.pending_count} pending</span>`
                    : '<span class="text-gray-300">&mdash;</span>'}</td>
                <td class="px-4 py-3 text-right"><i class="ti ti-chevron-right text-gray-400" aria-hidden="true"></i></td>
            </tr>`).join('');
    }

    async function loadBarangaySummary() {
        try {
            const result = await Api.get('/families/barangay-summary');
            renderBarangaySummaryTable(result.data);
        } catch (error) {
            showFormErrors(error);
        }
    }

    document.getElementById('barangay-summary-tbody').addEventListener('click', (e) => {
        const row = e.target.closest('tr[data-barangay-id]');
        if (! row) return;
        drillIntoBarangay(Number(row.dataset.barangayId), row.dataset.barangayName);
    });

    function renderCenterSummaryTable(rows) {
        document.getElementById('center-summary-tbody').innerHTML = rows.length
            ? rows.map((r) => `
                <tr class="border-t border-gray-100 hover:bg-gray-50 cursor-pointer"
                    data-center-id="${r.evacuation_center_id ?? 'none'}" data-center-name="${r.evacuation_center_name}">
                    <td class="px-4 py-3 font-medium">${r.evacuation_center_name}</td>
                    <td class="px-4 py-3">${r.family_count}</td>
                    <td class="px-4 py-3">${r.person_count}</td>
                    <td class="px-4 py-3">${r.pending_count > 0
                        ? `<span class="text-xs px-2 py-0.5 rounded-lg bg-amber-100 text-amber-800">${r.pending_count} pending</span>`
                        : '<span class="text-gray-300">&mdash;</span>'}</td>
                    <td class="px-4 py-3 text-right"><i class="ti ti-chevron-right text-gray-400" aria-hidden="true"></i></td>
                </tr>`).join('')
            : `<tr><td colspan="5" class="px-4 py-10 text-center text-gray-500 text-sm">No families registered in this barangay yet.</td></tr>`;
    }

    async function drillIntoBarangay(barangayId, barangayName) {
        currentBarangayId = barangayId;
        currentBarangayName = barangayName;
        currentCenterId = null;
        currentCenterName = '';

        renderBreadcrumb();
        showDrillLevel('center');
        // register-in-barangay-btn is hidden (see CSWDO note above), but its
        // text is still kept in sync in case that's ever reversed.
        document.getElementById('register-in-barangay-btn').textContent = `+ Register a family in ${barangayName}`;

        try {
            const [familiesResult, centerSummaryResult] = await Promise.all([
                Api.get(`/families?barangay_id=${barangayId}&per_page=200`),
                Api.get(`/families/center-summary?barangay_id=${barangayId}`),
            ]);
            familiesInCurrentBarangay = familiesResult.data.data;
            centerSummaryRowsCache = centerSummaryResult.data;
            renderCenterSummaryTable(centerSummaryRowsCache);
        } catch (error) {
            familiesInCurrentBarangay = [];
            centerSummaryRowsCache = [];
            showFormErrors(error);
        }

        await refreshScopedSummary();
    }

    document.getElementById('center-summary-tbody').addEventListener('click', (e) => {
        const row = e.target.closest('tr[data-center-id]');
        if (! row) return;
        drillIntoCenter(row.dataset.centerId === 'none' ? 'none' : Number(row.dataset.centerId), row.dataset.centerName);
    });

    async function drillIntoCenter(centerId, centerName) {
        currentCenterId = centerId;
        currentCenterName = centerName;

        renderBreadcrumb();
        showDrillLevel('family');

        // register-at-center-btn is hidden (see CSWDO note above), but its
        // text is still kept in sync in case that's ever reversed. No real
        // center to prefill for the "unassigned" bucket -- offer the
        // barangay-level prefill instead.
        const registerBtn = document.getElementById('register-at-center-btn');
        registerBtn.textContent = centerId !== 'none'
            ? `+ Register a family at ${centerName}`
            : `+ Register a family in ${currentBarangayName}`;

        // Filters from familiesInCurrentBarangay (fetched fresh, scoped to
        // exactly this barangay -- see drillIntoBarangay()), not the
        // city-wide allFamilies -- no barangay check needed here anymore,
        // that scoping already happened server-side.
        familiesInCurrentDrill = familiesInCurrentBarangay.filter((f) => (
            centerId === 'none' ? ! f.evacuation_center : f.evacuation_center?.id === centerId
        ));

        document.getElementById('sectoral-filter').value = '';
        applyFilters();

        await refreshScopedSummary();
    }

    async function goToBarangayLevel() {
        currentBarangayId = null;
        currentCenterId = null;
        renderBreadcrumb();
        showDrillLevel('barangay');
        await refreshScopedSummary();
    }

    async function goToCenterLevel() {
        currentCenterId = null;
        currentCenterName = '';
        renderBreadcrumb();
        showDrillLevel('center');
        await refreshScopedSummary();
    }

    // Refreshes whichever drill level is currently visible (plus the
    // barangay summary underneath it, so its counts are correct if the
    // user navigates back up) -- called after a family is registered from
    // the modal, instead of just reloading the flat family list.
    async function refreshCurrentDrillView() {
        await loadBarangaySummary();

        if (currentBarangayId !== null) {
            try {
                const [familiesResult, centerSummaryResult] = await Promise.all([
                    Api.get(`/families?barangay_id=${currentBarangayId}&per_page=200`),
                    Api.get(`/families/center-summary?barangay_id=${currentBarangayId}`),
                ]);
                familiesInCurrentBarangay = familiesResult.data.data;
                centerSummaryRowsCache = centerSummaryResult.data;
                renderCenterSummaryTable(centerSummaryRowsCache);
            } catch (error) {
                // Leaves the previous (now slightly stale) data in place --
                // the rest of the page is still usable.
            }
        }

        if (currentCenterId !== null) {
            await drillIntoCenter(currentCenterId, currentCenterName);
        } else {
            await refreshScopedSummary();
        }
    }

    // The single place that decides WHICH families/totals/sectoral-quick-
    // count figures belong to the current drill level, and renders the top
    // stat row + every sidebar card against exactly that scope -- city-wide
    // at the top level (unchanged from before this fix), one barangay's
    // worth once drilled in, or one center's worth at the deepest level.
    // Called after every drill-level change (including the initial load).
    async function refreshScopedSummary() {
        let scopedFamilies;
        let totals;
        let sectoralParams;
        let rankingMode;

        if (currentBarangayId === null) {
            scopedFamilies = allFamilies;
            totals = cityWideTotals;
            sectoralParams = {};
            rankingMode = 'barangays';
        } else if (currentCenterId === null) {
            scopedFamilies = familiesInCurrentBarangay;
            sectoralParams = { barangay_id: currentBarangayId };
            rankingMode = 'centers';

            try {
                const statsResult = await Api.get(`/families/stats?barangay_id=${currentBarangayId}`);
                totals = statsResult.data;
            } catch (error) {
                totals = { households: scopedFamilies.length, total_persons: allMembers(scopedFamilies).length };
            }
        } else {
            scopedFamilies = familiesInCurrentDrill;
            sectoralParams = { barangay_id: currentBarangayId };
            if (currentCenterId !== 'none') sectoralParams.evacuation_center_id = currentCenterId;
            rankingMode = 'hidden';

            // Authoritative counts from the already-fetched center-summary
            // row -- exact, not re-derived from familiesInCurrentDrill,
            // which is safe here anyway but this avoids a second source of
            // truth for the same two numbers.
            const row = centerSummaryRowsCache.find((r) => String(r.evacuation_center_id ?? 'none') === String(currentCenterId));
            totals = row
                ? { households: row.family_count, total_persons: row.person_count }
                : { households: scopedFamilies.length, total_persons: allMembers(scopedFamilies).length };
        }

        totalFamilies = totals.households;
        totalPersonsCount = totals.total_persons;

        const sectoralQuickCount = await fetchSectoralQuickCountSummary(sectoralParams);

        renderStatCards(scopedFamilies);
        renderSidebar(scopedFamilies, sectoralQuickCount);
        renderRankingCard(rankingMode, scopedFamilies, centerSummaryRowsCache);
    }

    document.getElementById('register-in-barangay-btn').addEventListener('click', () => {
        openFamilyModal({ barangayId: currentBarangayId, barangayName: currentBarangayName });
    });

    document.getElementById('register-at-center-btn').addEventListener('click', () => {
        openFamilyModal(currentCenterId !== 'none'
            ? { barangayId: currentBarangayId, centerId: currentCenterId, centerName: currentCenterName }
            : { barangayId: currentBarangayId, barangayName: currentBarangayName });
    });

    // --- Global search -------------------------------------------------------
    // Independent of the drill-down above -- searches every evacuee by name
    // (not just the currently-drilled-into barangay/center) via the same
    // backend search EvacueeController::index() already provides, so a
    // family-reunification lookup is never limited by, or slowed down by,
    // which drill-down level the page happens to be showing.

    let globalSearchDebounce = null;

    async function runGlobalSearch(query) {
        const resultsBox = document.getElementById('global-search-results');

        try {
            const result = await Api.get(`/evacuees?search=${encodeURIComponent(query)}&per_page=15`);
            const evacuees = result.data.data;

            resultsBox.innerHTML = evacuees.length
                ? evacuees.map((ev) => `
                    <a href="/families/${ev.family_id}" class="flex items-center justify-between gap-3 px-4 py-2.5 hover:bg-gray-50 border-b border-gray-50 last:border-0">
                        <span class="min-w-0">
                            <span class="text-sm font-medium text-gray-800">
                                ${ev.is_placeholder ? '<span class="text-amber-600">Member — details pending</span>' : ev.full_name}
                            </span>
                            ${ev.barangay_name ? `<span class="block text-xs text-gray-500">${ev.barangay_name}</span>` : ''}
                        </span>
                        <i class="ti ti-chevron-right text-gray-300 shrink-0" aria-hidden="true"></i>
                    </a>`).join('')
                : '<p class="px-4 py-3 text-sm text-gray-500">No evacuees match that name.</p>';

            resultsBox.classList.remove('hidden');
        } catch (error) {
            resultsBox.classList.add('hidden');
        }
    }

    document.getElementById('global-search-input').addEventListener('input', (e) => {
        const query = e.target.value.trim();
        clearTimeout(globalSearchDebounce);

        if (query.length < 2) {
            document.getElementById('global-search-results').classList.add('hidden');
            return;
        }

        globalSearchDebounce = setTimeout(() => runGlobalSearch(query), 250);
    });

    document.addEventListener('click', (e) => {
        const resultsBox = document.getElementById('global-search-results');
        if (! resultsBox.contains(e.target) && e.target.id !== 'global-search-input') {
            resultsBox.classList.add('hidden');
        }
    });

    renderBreadcrumb();

    (async () => {
        await loadFamilies();
        await loadBarangaySummary();

        await refreshScopedSummary();
    })();

    // --- Register-family modal --------------------------------------------
    // Same pattern established on the alerts page: /families/create still
    // exists untouched as a fallback page.

    let fMemberCount = 0;

    function fMemberRowHtml(index) {
        return `
        <div class="member-row bg-white border border-gray-200 rounded-xl p-4" data-index="${index}">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-600">Member ${index + 1}</p>
                ${index > 0 ? `<button type="button" class="remove-member text-xs text-red-500 hover:underline">Remove</button>` : ''}
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" placeholder="First name" class="m-first_name border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <input type="text" placeholder="Middle name" class="m-middle_name border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <input type="text" placeholder="Last name" class="m-last_name border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <select class="m-sex border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Sex</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                <input type="date" class="m-date_of_birth border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <div>
                    <input type="text" placeholder="09XXXXXXXXX" class="m-contact_number w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    <button type="button" class="same-as-head-btn text-xs text-brand hover:underline mt-1">Same as head of family</button>
                </div>
            </div>
            <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-600 items-center">
                <label class="flex items-center gap-1.5"><input type="radio" name="f-head-${index}" class="m-is_head_of_family"> Head of family</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_pwd"> PWD</label>
                <input type="text" placeholder="PWD type (e.g. visual, mobility)" class="m-pwd_type hidden border border-gray-300 rounded-lg px-2 py-1 text-xs">
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_pregnant"> Pregnant</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_lactating"> Lactating</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_solo_parent"> Solo parent</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_indigenous_person"> Indigenous person</label>
            </div>
            <p class="text-xs text-gray-500 mt-2">Contact number is required for every member -- if someone doesn't have their own phone (e.g. a child or elderly member), use "Same as head of family" to reuse the family's number.</p>
        </div>`;
    }

    function fAddMemberRow() {
        document.getElementById('f-members-container').insertAdjacentHTML('beforeend', fMemberRowHtml(fMemberCount));
        fMemberCount++;
    }

    document.getElementById('f-add-member-btn').addEventListener('click', fAddMemberRow);

    document.getElementById('f-members-container').addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-member')) {
            e.target.closest('.member-row').remove();
        }

        // Copies the head of family's own contact number into whichever
        // row's button was clicked -- contact_number is required for every
        // member now, and not every member (a child, an elderly relative)
        // realistically has their own phone.
        if (e.target.classList.contains('same-as-head-btn')) {
            const headRow = document.querySelector('#f-members-container .m-is_head_of_family:checked')?.closest('.member-row');
            if (! headRow) return;
            const headNumber = headRow.querySelector('.m-contact_number').value;
            e.target.closest('.member-row').querySelector('.m-contact_number').value = headNumber;
        }
    });

    document.getElementById('f-members-container').addEventListener('change', (e) => {
        if (e.target.classList.contains('m-is_head_of_family')) {
            document.querySelectorAll('#f-members-container .same-as-head-btn').forEach((btn) => {
                btn.classList.remove('hidden');
            });
            e.target.closest('.member-row').querySelector('.same-as-head-btn').classList.add('hidden');
        }

        if (e.target.classList.contains('m-is_pwd')) {
            const pwdTypeInput = e.target.closest('.member-row').querySelector('.m-pwd_type');
            pwdTypeInput.classList.toggle('hidden', ! e.target.checked);
            pwdTypeInput.required = e.target.checked;
        }
    });

    document.getElementById('f-displacement_type').addEventListener('change', (e) => {
        document.getElementById('f-center-field').style.display = e.target.value === 'inside_center' ? 'block' : 'none';
    });

    // Factored out (not just the inline 'change' listener below) so
    // openFamilyModal() can also call it directly when contextually
    // pre-filling a barangay from the drill-down, without faking a change
    // event.
    async function fLoadCentersForBarangay(barangayId) {
        const select = document.getElementById('f-evacuation_center_id');
        if (! barangayId) {
            select.innerHTML = '<option value="">Select barangay first</option>';
            return;
        }
        const centers = await Api.get(`/evacuation-centers?barangay_id=${barangayId}`);
        select.innerHTML = '<option value="">Select center</option>' +
            centers.data.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
    }

    document.getElementById('f-barangay_id').addEventListener('change', (e) => fLoadCentersForBarangay(e.target.value));

    // prefill (optional): { barangayId, centerId } -- set when opened from
    // within the barangay/center drill-down, so staff registering a family
    // they just found "missing" from a specific center's list don't have
    // to re-pick a barangay/center the page already knows they're looking
    // at. The general "Register a family" buttons (header, empty state)
    // call this with no prefill at all, same as before.
    async function openFamilyModal(prefill = {}) {
        document.getElementById('family-modal-errors').classList.add('hidden');
        document.getElementById('register-form').reset();
        document.getElementById('f-members-container').innerHTML = '';
        // Matches whatever displacement_type the reset above landed on
        // (outside_center, its first/default option) -- not hardcoded to
        // 'block', so the center field doesn't show while that default is
        // selected. Overridden below when opened with a center prefill.
        document.getElementById('f-center-field').style.display =
            document.getElementById('f-displacement_type').value === 'inside_center' ? 'block' : 'none';
        fMemberCount = 0;
        fAddMemberRow(); // start with one member row (the head of family)

        document.getElementById('family-modal').classList.remove('hidden');
        document.getElementById('family-modal').classList.add('flex');

        try {
            const [barangays, events] = await Promise.all([
                Api.get('/barangays'),
                Api.get('/evacuation-events'),
            ]);

            // Barangay stays a full, free choice for every role including
            // barangay officials -- an evacuee's home barangay can
            // genuinely differ from whichever barangay's staff happens to
            // be registering them.
            document.getElementById('f-barangay_id').innerHTML =
                '<option value="">Select barangay</option>' +
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');
            document.getElementById('f-evacuation_center_id').innerHTML = '<option value="">Select barangay first</option>';

            // Only open (non-closed) disaster events -- registering a new
            // evacuee into an already-closed one isn't a valid action.
            const openEvents = events.data.filter((ev) => ev.status !== 'closed');
            document.getElementById('f-evacuation_event_id').innerHTML =
                '<option value="">Select event</option>' +
                openEvents.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join('');

            if (prefill.barangayId) {
                document.getElementById('f-barangay_id').value = prefill.barangayId;
                await fLoadCentersForBarangay(prefill.barangayId);

                if (prefill.centerId) {
                    document.getElementById('f-evacuation_center_id').value = prefill.centerId;

                    // A specific center was prefilled (opened from that
                    // center's own drill-down context) -- switch off the
                    // general outside_center default, since staff clicking
                    // "+ Register a family at {center}" clearly mean
                    // someone physically there.
                    document.getElementById('f-displacement_type').value = 'inside_center';
                    document.getElementById('f-center-field').style.display = 'block';
                }
            }
        } catch (error) {
            // Dropdowns just stay at their default single option if this
            // fails -- the rest of the form is still usable.
        }
    }

    function closeFamilyModal() {
        document.getElementById('family-modal').classList.add('hidden');
        document.getElementById('family-modal').classList.remove('flex');
    }

    // Wrapped in arrow functions (not passed directly) -- addEventListener
    // would otherwise call openFamilyModal(clickEvent), and the click
    // Event object is not a valid prefill.
    document.getElementById('register-family-btn').addEventListener('click', () => openFamilyModal());
    document.getElementById('register-family-empty-btn').addEventListener('click', () => openFamilyModal());
    document.getElementById('family-modal-close').addEventListener('click', closeFamilyModal);
    document.getElementById('family-modal-cancel').addEventListener('click', closeFamilyModal);

    document.getElementById('family-modal').addEventListener('click', (e) => {
        if (e.target.id === 'family-modal') closeFamilyModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('family-modal').classList.contains('hidden')) {
            closeFamilyModal();
        }
    });

    document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            barangay_id: Number(document.getElementById('f-barangay_id').value),
            evacuation_event_id: Number(document.getElementById('f-evacuation_event_id').value),
            displacement_type: document.getElementById('f-displacement_type').value,
            evacuation_center_id: document.getElementById('f-evacuation_center_id').value
                ? Number(document.getElementById('f-evacuation_center_id').value)
                : null,
            is_4ps_beneficiary: document.getElementById('f-is_4ps_beneficiary').checked,
            members: Array.from(document.querySelectorAll('#f-members-container .member-row')).map((row) => ({
                first_name: row.querySelector('.m-first_name').value,
                middle_name: row.querySelector('.m-middle_name').value || null,
                last_name: row.querySelector('.m-last_name').value,
                sex: row.querySelector('.m-sex').value,
                date_of_birth: row.querySelector('.m-date_of_birth').value,
                contact_number: row.querySelector('.m-contact_number').value || null,
                is_head_of_family: row.querySelector('.m-is_head_of_family').checked,
                is_pwd: row.querySelector('.m-is_pwd').checked,
                pwd_type: row.querySelector('.m-is_pwd').checked ? row.querySelector('.m-pwd_type').value : null,
                is_pregnant: row.querySelector('.m-is_pregnant').checked,
                is_lactating: row.querySelector('.m-is_lactating').checked,
                is_solo_parent: row.querySelector('.m-is_solo_parent').checked,
                is_indigenous_person: row.querySelector('.m-is_indigenous_person').checked,
            })),
        };

        const button = document.getElementById('f-submit-btn');
        button.disabled = true;
        button.textContent = 'Registering...';

        try {
            await Api.post('/families/register', payload);
            closeFamilyModal();
            // Refresh in place, no full page reload: stat cards/sidebar via
            // loadFamilies(), plus whichever drill-down level is currently
            // visible (and the barangay summary underneath it) via
            // refreshCurrentDrillView() -- a plain loadFamilies() alone
            // wouldn't touch the barangay/center summary tables at all.
            await Promise.all([loadFamilies(), refreshCurrentDrillView()]);
        } catch (error) {
            // Shown inside the modal itself (not the page's #form-errors
            // box, which sits behind the modal and wouldn't be visible).
            const box = document.getElementById('family-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Register family';
        }
    });
</script>
@endsection
