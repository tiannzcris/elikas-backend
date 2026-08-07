@extends('layouts.app')

@section('title', 'Evacuees')
@section('nav-families', 'active')

@section('content')
    <div class="flex items-start justify-between mb-6 gap-4">
        <div>
            <h1 class="text-xl font-semibold mb-1">Evacuees</h1>
            <p class="text-sm text-gray-500">List of registered evacuee households and their members.</p>
        </div>
        <a href="/families/create"
            class="shrink-0 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Register a family
        </a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Households</p>
                <p id="stat-households" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Families registered</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-home text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total persons</p>
                <p id="stat-persons" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">All registered members</p>
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
                <a href="/families/create" class="text-sm text-brand hover:underline">+ Register the first family</a>
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
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    let allFamilies = [];
    let sexChartInstance = null;

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
        const totalPersons = members.length;
        const children = members.filter((m) => m.age < 18).length;
        const seniors = members.filter((m) => m.age_bracket === 'senior_citizen').length;
        const pwd = members.filter((m) => m.sectoral?.is_pwd).length;

        document.getElementById('stat-households').textContent = families.length;
        document.getElementById('stat-persons').textContent = totalPersons;
        document.getElementById('stat-children').textContent = children;
        document.getElementById('stat-seniors').textContent = seniors;
        document.getElementById('stat-pwd').textContent = pwd;

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
        document.getElementById('total-count').textContent = allFamilies.length;
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

    (async () => {
        try {
            const [familiesResult, barangaysResult] = await Promise.all([
                Api.get('/families?per_page=200'),
                Api.get('/barangays'),
            ]);

            document.getElementById('barangay-filter').insertAdjacentHTML('beforeend',
                barangaysResult.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join(''));

            allFamilies = familiesResult.data.data;
            renderStatCards(allFamilies);
            renderSidebar(allFamilies);
            document.getElementById('showing-count').textContent = allFamilies.length;
            document.getElementById('total-count').textContent = allFamilies.length;
            renderTable(allFamilies);
        } catch (error) {
            showFormErrors(error);
        }
    })();
</script>
@endsection
