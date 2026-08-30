@extends('layouts.app')

@section('title', 'Evacuees')
@section('nav-families', 'active')

@section('content')
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <h1 class="text-xl font-semibold mb-1">Evacuees</h1>
            <p class="text-sm text-gray-500">List of registered evacuee households and their members.</p>
        </div>
        {{-- Opens the modal below instead of navigating to /families/create --
            that route/page still exists untouched as a fallback, following
            the same pattern established for the alerts page. --}}
        <button type="button" id="register-family-btn"
            class="shrink-0 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Register a family
        </button>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Households</p>
                <p id="stat-households" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Currently registered, active event(s)</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-home text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total persons</p>
                <p id="stat-persons" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Currently displaced, active event(s)</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Children (0-17)</p>
                <p id="stat-children" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-children-pct" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-baby-carriage text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #8B5CF6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Seniors (60+)</p>
                <p id="stat-seniors" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-seniors-pct" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg flex items-center justify-center shrink-0" style="background: #F3EEFF;">
                <i class="ti ti-walk text-purple-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #EF4444;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">PWD members</p>
                <p id="stat-pwd" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-pwd-pct" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-wheelchair text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <div class="relative flex-1 min-w-[200px]">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 16px;" aria-hidden="true"></i>
                    <input id="search-input" type="text" placeholder="Search by name or barangay..."
                        class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm">
                </div>
                <select id="barangay-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All barangays</option>
                </select>
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
                <p class="text-sm font-medium text-gray-600 mb-1">No families registered yet</p>
                <p class="text-sm text-gray-400 mb-4">Registrations will appear here as barangay officials add them.</p>
                <button type="button" id="register-family-empty-btn" class="text-sm text-brand hover:underline">+ Register the first family</button>
            </div>

            <div id="table-wrap" class="hidden bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="text-left px-4 py-3">Household head</th>
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
                <div class="px-4 py-3 border-t border-gray-100 text-xs text-gray-400">
                    Showing <span id="showing-count">0</span> of <span id="total-count">0</span> families
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

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Top barangays by evacuees</p>
                <div id="barangay-distribution" class="space-y-2.5 text-xs"></div>
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
                    <p class="text-xs text-gray-500">Register every member of an arriving household in one step.</p>
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
                        <select id="f-displacement_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="inside_center">Inside an evacuation center</option>
                            <option value="outside_center">Outside (evacuated to relatives/other location)</option>
                        </select>
                    </div>
                    <div id="f-center-field">
                        <label class="text-sm text-gray-600 block mb-1">Evacuation center</label>
                        <select id="f-evacuation_center_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-600 col-span-2">
                        <input type="checkbox" id="f-is_4ps_beneficiary"> Household is a 4Ps beneficiary
                    </label>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-medium text-gray-700">Household members</h2>
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
    // household/person counts (and "Showing X of Y") are tracked separately
    // rather than derived from that array's length.
    let totalHouseholds = 0;
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

            const headName = f.head_of_family?.full_name ?? f.members?.[0]?.full_name ?? '';

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

    function renderSidebar(families) {
        const members = allMembers(families);
        const total = members.length || 1;

        // Sex distribution
        const male = members.filter((m) => m.sex === 'male').length;
        const female = members.filter((m) => m.sex === 'female').length;
        document.getElementById('sex-legend').innerHTML = `
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#3B82F6"></span>Male</span>
                <span class="font-medium text-gray-800">${male} <span class="text-gray-400 font-normal">(${Math.round(male / total * 100)}%)</span></span>
            </div>
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#EC4899"></span>Female</span>
                <span class="font-medium text-gray-800">${female} <span class="text-gray-400 font-normal">(${Math.round(female / total * 100)}%)</span></span>
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

        // Barangay distribution (top 5 by member count)
        const byBarangay = {};
        families.forEach((f) => {
            const name = f.barangay?.name ?? 'Unassigned';
            byBarangay[name] = (byBarangay[name] ?? 0) + (f.member_count ?? 0);
        });
        const topBarangays = Object.entries(byBarangay).sort((a, b) => b[1] - a[1]).slice(0, 5);
        const maxBrgy = Math.max(...topBarangays.map(([, c]) => c), 1);
        document.getElementById('barangay-distribution').innerHTML = topBarangays.length
            ? topBarangays.map(([name, count]) => `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">${name}</span>
                        <span class="font-medium text-gray-800">${count}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width:${count / maxBrgy * 100}%"></div>
                    </div>
                </div>`).join('')
            : '<p class="text-gray-400">No data yet.</p>';

        // Sectoral summary
        const sectoral = [
            ['is_4ps_beneficiary', '4Ps beneficiary', 'ti-gift', 'text-blue-500', 'bg-blue-50'],
            ['is_pwd', 'PWD', 'ti-wheelchair', 'text-red-500', 'bg-red-50'],
            ['is_pregnant', 'Pregnant', 'ti-baby-carriage', 'text-amber-500', 'bg-amber-50'],
            ['is_lactating', 'Lactating', 'ti-droplet', 'text-pink-500', 'bg-pink-50'],
            ['is_solo_parent', 'Solo parent', 'ti-user-check', 'text-purple-500', 'bg-purple-50'],
            ['is_indigenous_person', 'Indigenous', 'ti-leaf', 'text-green-500', 'bg-green-50'],
        ];
        document.getElementById('sectoral-summary').innerHTML = sectoral.map(([key, label, icon, color, bg]) => {
            const count = members.filter((m) => m.sectoral?.[key]).length;
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
        }).join('');
    }

    function renderStatCards(families) {
        const members = allMembers(families);
        const children = members.filter((m) => m.age < 18).length;
        const seniors = members.filter((m) => m.age_bracket === 'senior_citizen').length;
        const pwd = members.filter((m) => m.sectoral?.is_pwd).length;

        // Households/Total persons use the real counts from /families/stats
        // (totalHouseholds/totalPersonsCount, set in loadFamilies()) rather
        // than families.length/members.length -- this page's fetched
        // families array is capped at per_page=200, so those would silently
        // undercount past 200 with no indication anything was truncated.
        document.getElementById('stat-households').textContent = totalHouseholds;
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

    function applyFilters() {
        const query = document.getElementById('search-input').value.trim().toLowerCase();
        const barangayId = document.getElementById('barangay-filter').value;
        const sectoral = document.getElementById('sectoral-filter').value;

        const filtered = allFamilies.filter((f) => {
            const headName = f.head_of_family?.full_name ?? '';
            const matchesSearch = ! query ||
                headName.toLowerCase().includes(query) ||
                (f.barangay?.name ?? '').toLowerCase().includes(query);
            const matchesBarangay = ! barangayId || String(f.barangay?.id) === barangayId;

            let matchesSectoral = true;
            if (sectoral === 'is_4ps_beneficiary') {
                matchesSectoral = f.is_4ps_beneficiary || (f.members ?? []).some((m) => m.sectoral?.is_4ps_beneficiary);
            } else if (sectoral === 'senior') {
                matchesSectoral = f.has_senior_member || (f.members ?? []).some((m) => m.age_bracket === 'senior_citizen');
            } else if (sectoral) {
                matchesSectoral = (f.members ?? []).some((m) => m.sectoral?.[sectoral]);
            }

            return matchesSearch && matchesBarangay && matchesSectoral;
        });

        document.getElementById('showing-count').textContent = filtered.length;
        document.getElementById('total-count').textContent = totalHouseholds;
        renderTable(filtered);
    }

    document.getElementById('search-input').addEventListener('input', applyFilters);
    document.getElementById('barangay-filter').addEventListener('change', applyFilters);
    document.getElementById('sectoral-filter').addEventListener('change', applyFilters);

    // Client-side CSV export -- uses whatever's currently registered, no
    // backend export endpoint needed for this.
    document.getElementById('export-btn').addEventListener('click', () => {
        const rows = [['Household Head', 'Barangay', 'Persons', 'Evacuation Center', '4Ps', 'PWD', 'Senior', 'Lactating', 'Date Registered']];
        allFamilies.forEach((f) => {
            rows.push([
                f.head_of_family?.full_name ?? '', f.barangay?.name ?? '', f.member_count ?? 0,
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
    // successful registration from the modal, refreshing the list in place
    // instead of a full page reload.
    async function loadFamilies() {
        try {
            // /families/stats returns real, non-paginated counts (and
            // defaults to CURRENT state only -- non-closed events) --
            // decoupled from /families?per_page=200's own page size, so the
            // stat cards below are never silently truncated once total
            // households pass 200.
            const [familiesResult, barangaysResult, statsResult] = await Promise.all([
                Api.get('/families?per_page=200'),
                Api.get('/barangays'),
                Api.get('/families/stats'),
            ]);

            // Rebuilt (not appended) each call -- otherwise a re-run after
            // registering a family from the modal would duplicate every
            // option in the filter dropdown.
            document.getElementById('barangay-filter').innerHTML = '<option value="">All barangays</option>' +
                barangaysResult.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');

            allFamilies = familiesResult.data.data;
            totalHouseholds = statsResult.data.households;
            totalPersonsCount = statsResult.data.total_persons;
            renderStatCards(allFamilies);
            renderSidebar(allFamilies);
            document.getElementById('showing-count').textContent = allFamilies.length;
            document.getElementById('total-count').textContent = totalHouseholds;
            renderTable(allFamilies);
        } catch (error) {
            showFormErrors(error);
        }
    }

    loadFamilies();

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
            <p class="text-xs text-gray-400 mt-2">Contact number is required for every member -- if someone doesn't have their own phone (e.g. a child or elderly member), use "Same as head of family" to reuse the household's number.</p>
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

    document.getElementById('f-barangay_id').addEventListener('change', async (e) => {
        const select = document.getElementById('f-evacuation_center_id');
        if (! e.target.value) {
            select.innerHTML = '<option value="">Select barangay first</option>';
            return;
        }
        const centers = await Api.get(`/evacuation-centers?barangay_id=${e.target.value}`);
        select.innerHTML = '<option value="">Select center</option>' +
            centers.data.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
    });

    async function openFamilyModal() {
        document.getElementById('family-modal-errors').classList.add('hidden');
        document.getElementById('register-form').reset();
        document.getElementById('f-members-container').innerHTML = '';
        document.getElementById('f-center-field').style.display = 'block';
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
        } catch (error) {
            // Dropdowns just stay at their default single option if this
            // fails -- the rest of the form is still usable.
        }
    }

    function closeFamilyModal() {
        document.getElementById('family-modal').classList.add('hidden');
        document.getElementById('family-modal').classList.remove('flex');
    }

    document.getElementById('register-family-btn').addEventListener('click', openFamilyModal);
    document.getElementById('register-family-empty-btn').addEventListener('click', openFamilyModal);
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

        const members = Array.from(document.querySelectorAll('#f-members-container .member-row')).map((row) => ({
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
        }));

        const payload = {
            barangay_id: Number(document.getElementById('f-barangay_id').value),
            evacuation_event_id: Number(document.getElementById('f-evacuation_event_id').value),
            displacement_type: document.getElementById('f-displacement_type').value,
            evacuation_center_id: document.getElementById('f-evacuation_center_id').value
                ? Number(document.getElementById('f-evacuation_center_id').value)
                : null,
            is_4ps_beneficiary: document.getElementById('f-is_4ps_beneficiary').checked,
            members,
        };

        const button = document.getElementById('f-submit-btn');
        button.disabled = true;
        button.textContent = 'Registering...';

        try {
            await Api.post('/families/register', payload);
            closeFamilyModal();
            await loadFamilies(); // refresh in place, no full page reload
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
