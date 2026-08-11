@extends('layouts.app')

@section('title', 'Evacuation centers')
@section('nav-centers', 'active')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold mb-1">Evacuation centers</h1>
            <p class="text-sm text-gray-500">Capacity and live occupancy across Ligao City.</p>
        </div>
        {{-- Opens the modal below instead of navigating to /evacuation-centers/create --
            that route/page still exists untouched as a fallback, following
            the same pattern established for the alerts page. --}}
        <button type="button" id="add-center-btn"
            class="hidden shrink-0 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Add evacuation center
        </button>
    </div>

    <div id="stats-row" class="hidden grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total centers</p>
                <p id="stat-total" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-building text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Active now</p>
                <p id="stat-active" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-check text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #A855F7;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total capacity</p>
                <p id="stat-capacity" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users-group text-purple-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Current occupancy</p>
                <p id="stat-occupancy" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-door-enter text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #EF4444;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">At risk / Full</p>
                <p id="stat-at-risk" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">&ge;90% occupied</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div id="filter-tabs" class="flex items-center gap-2 flex-wrap"></div>
                <div class="flex items-center gap-3 w-full sm:w-auto">
                    <div class="relative flex-1 sm:flex-none">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 15px;" aria-hidden="true"></i>
                        <input id="search-input" type="text" placeholder="Search by name or barangay..."
                            class="border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm w-full sm:w-56">
                    </div>
                    <button id="export-btn" class="flex items-center gap-1.5 text-brand border border-brand/30 rounded-lg px-3 py-2 text-sm hover:bg-brand-light shrink-0">
                        <i class="ti ti-download" style="font-size: 15px;" aria-hidden="true"></i> Export
                    </button>
                </div>
            </div>

            <div id="cards" class="grid sm:grid-cols-2 gap-4"></div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Occupancy overview</p>
                <div class="flex items-center gap-4">
                    <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                        <canvas id="occupancyChart" role="img" aria-label="Doughnut chart of center occupancy buckets"></canvas>
                    </div>
                    <div id="occupancy-legend" class="flex-1 space-y-2 text-sm"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Centers by type</p>
                <div id="type-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Centers by barangay</p>
                <div id="barangay-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Facility coverage</p>
                <p class="text-xs text-gray-400 mb-3">Share of centers reporting each facility as available.</p>
                <div id="facility-coverage" class="space-y-2.5 text-xs"></div>
            </div>

            <a href="/gis-map" class="bg-white border border-gray-200 rounded-xl p-4 flex items-center justify-between hover:border-brand group">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                        <i class="ti ti-map text-blue-500" style="font-size: 18px;" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">View on GIS map</p>
                        <p class="text-xs text-gray-400">See centers and hazard zones geographically</p>
                    </div>
                </div>
                <i class="ti ti-chevron-right text-gray-300 group-hover:text-brand" style="font-size: 18px;" aria-hidden="true"></i>
            </a>
        </div>
    </div>

    <div id="add-center-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800">Add evacuation center</p>
                    <p class="text-xs text-gray-500">Click the map to set the exact location.</p>
                </div>
                <button type="button" id="center-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="center-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="center-form" class="flex flex-col gap-4 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="text-sm text-gray-600 block mb-1">Name</label>
                        <input type="text" id="center-name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Barangay</label>
                        <select id="center-barangay_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Type</label>
                        <select id="center-type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="school">School</option>
                            <option value="covered_court">Covered court</option>
                            <option value="church">Church</option>
                            <option value="barangay_hall">Barangay hall</option>
                            <option value="gymnasium">Gymnasium</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm text-gray-600 block mb-1">Address</label>
                        <input type="text" id="center-address" required placeholder="e.g. Purok 3, Barangay Bacong, Ligao City"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Capacity (families)</label>
                        <input type="number" id="center-capacity_families" min="0" placeholder="e.g. 50" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <p class="text-xs text-gray-400 mt-1">Leave blank if not yet known.</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Capacity (persons)</label>
                        <input type="number" id="center-capacity_persons" min="0" placeholder="e.g. 250" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <p class="text-xs text-gray-400 mt-1">Leave blank if not yet known.</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Camp manager name</label>
                        <input type="text" id="center-camp_manager_name" placeholder="e.g. Juan Dela Cruz" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Camp manager contact</label>
                        <input type="text" id="center-camp_manager_contact" placeholder="09XXXXXXXXX" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Status</label>
                        <select id="center-status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="on_standby">On standby</option>
                            <option value="active">Active</option>
                            <option value="full">Full</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <p class="text-sm text-gray-600 mb-2">
                        Location <span id="center-coords-display" class="text-gray-400">(click the map to set)</span>
                    </p>
                    <div id="center-picker-map" style="height: 300px; border-radius: 0.5rem;"></div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="center-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="center-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                        Save evacuation center
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    // Only administrators/CSWD personnel manage centers -- barangay
    // officials can view but the "Add" button isn't relevant to them.
    const user = Api.getUser();
    if (user && user.role !== 'barangay_official') {
        document.getElementById('add-center-btn').classList.remove('hidden');
    }

    const statusColors = {
        active: 'bg-green-50 text-green-700',
        on_standby: 'bg-gray-100 text-gray-600',
        full: 'bg-amber-50 text-amber-700',
        closed: 'bg-red-50 text-red-700',
    };

    const TYPE_LABELS = {
        school: 'School', covered_court: 'Covered court', church: 'Church',
        barangay_hall: 'Barangay hall', gymnasium: 'Gymnasium', other: 'Other',
    };

    // The 6 facilities most tied to health/dignity/safety in the DROMIC EC
    // Information Board checklist -- a curated subset rather than all 19
    // tracked types, so the sidebar card stays scannable.
    const KEY_FACILITIES = [
        ['handwashing_facility', 'Handwashing facility'],
        ['toilet_common', 'Common toilet'],
        ['bathing_area_common', 'Common bathing area'],
        ['women_friendly_space', 'Women-friendly space'],
        ['child_friendly_space', 'Child-friendly space'],
        ['health_facility', 'Health facility'],
        ['community_kitchen', 'Community kitchen'],
    ];

    let allCenters = [];
    let activeFilter = 'all';
    let occupancyChartInstance = null;

    function occupancyBucket(c) {
        if (c.capacity_persons === null || c.capacity_persons === undefined) return 'no_data';
        const pct = c.occupancy_percent ?? 0;
        if (pct >= 90) return 'at_risk';
        if (pct >= 75) return 'near_full';
        return 'available';
    }

    function renderFilterTabs() {
        const counts = {
            all: allCenters.length,
            active: allCenters.filter((c) => c.status === 'active').length,
            on_standby: allCenters.filter((c) => c.status === 'on_standby').length,
            full: allCenters.filter((c) => c.status === 'full').length,
            closed: allCenters.filter((c) => c.status === 'closed').length,
        };
        const labels = { all: 'All', active: 'Active', on_standby: 'On standby', full: 'Full', closed: 'Closed' };

        document.getElementById('filter-tabs').innerHTML = Object.keys(labels).map((key) => `
            <button data-filter="${key}"
                class="filter-tab text-sm px-3 py-1.5 rounded-lg border ${activeFilter === key ? 'border-brand bg-brand-light text-brand font-medium' : 'border-gray-300 text-gray-600 hover:bg-gray-50'}">
                ${labels[key]} (${counts[key]})
            </button>
        `).join('');

        document.querySelectorAll('.filter-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                activeFilter = btn.dataset.filter;
                renderFilterTabs();
                renderCards();
            });
        });
    }

    function renderCards() {
        const query = document.getElementById('search-input').value.trim().toLowerCase();

        const filtered = allCenters.filter((c) => {
            const matchesFilter = activeFilter === 'all' || c.status === activeFilter;
            const matchesSearch = ! query ||
                c.name.toLowerCase().includes(query) ||
                (c.barangay?.name ?? '').toLowerCase().includes(query);
            return matchesFilter && matchesSearch;
        });

        document.getElementById('cards').innerHTML = filtered.length === 0
            ? '<p class="text-gray-400 text-sm sm:col-span-2 text-center py-16">No centers match this filter.</p>'
            : filtered.map((c) => {
                const pct = c.occupancy_percent ?? 0;
                const barColor = pct >= 100 ? 'bg-red-500' : pct >= 75 ? 'bg-amber-500' : 'bg-green-500';
                const facilities = c.facilities ?? [];
                const facilitiesAvailable = facilities.filter((f) => f.is_available).length;

                return `
                <a href="/evacuation-centers/${c.id}" class="bg-white border border-gray-200 rounded-xl p-4 block hover:border-brand">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-start gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                <i class="ti ti-building text-blue-500" style="font-size: 18px;" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="font-medium text-sm">${c.name}</p>
                                <p class="text-xs text-gray-500">${c.barangay?.name ?? '—'} · ${TYPE_LABELS[c.type] ?? c.type}</p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-lg shrink-0 ${statusColors[c.status] ?? ''}">${c.status.replace('_', ' ')}</span>
                    </div>
                    ${c.capacity_persons ? `
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Occupancy</span><span>${c.current_occupancy} / ${c.capacity_persons}</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full mb-3">
                            <div class="h-1.5 ${barColor} rounded-full" style="width: ${Math.min(pct, 100)}%"></div>
                        </div>
                    ` : '<p class="text-xs text-gray-400 mb-3">No capacity set</p>'}
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 pt-2 border-t border-gray-100">
                        <i class="ti ti-clipboard-check" style="font-size: 13px;" aria-hidden="true"></i>
                        ${facilities.length ? `${facilitiesAvailable}/${facilities.length} facilities available` : 'No facility checklist recorded'}
                    </div>
                </a>`;
            }).join('');
    }

    function renderSidebar() {
        // Occupancy overview donut
        const buckets = { available: 0, near_full: 0, at_risk: 0, no_data: 0 };
        allCenters.forEach((c) => buckets[occupancyBucket(c)]++);

        const bucketMeta = [
            ['available', 'Available (<75%)', '#22C55E'],
            ['near_full', 'Near full (75-89%)', '#F59E0B'],
            ['at_risk', 'At risk (≥90%)', '#EF4444'],
            ['no_data', 'No capacity set', '#9CA3AF'],
        ];

        document.getElementById('occupancy-legend').innerHTML = bucketMeta.map(([key, label, color]) => `
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${color}"></span>${label}</span>
                <span class="font-medium text-gray-800">${buckets[key]}</span>
            </div>`).join('');

        if (occupancyChartInstance) occupancyChartInstance.destroy();
        occupancyChartInstance = new Chart(document.getElementById('occupancyChart'), {
            type: 'doughnut',
            data: {
                labels: bucketMeta.map(([, label]) => label),
                datasets: [{ data: bucketMeta.map(([key]) => buckets[key]), backgroundColor: bucketMeta.map(([, , c]) => c), borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
        });

        // Centers by type
        const byType = {};
        allCenters.forEach((c) => { byType[c.type] = (byType[c.type] ?? 0) + 1; });
        const maxType = Math.max(...Object.values(byType), 1);
        document.getElementById('type-distribution').innerHTML = Object.entries(byType)
            .sort((a, b) => b[1] - a[1])
            .map(([type, count]) => `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">${TYPE_LABELS[type] ?? type}</span>
                        <span class="font-medium text-gray-800">${count}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width:${count / maxType * 100}%"></div>
                    </div>
                </div>`).join('') || '<p class="text-gray-400">No data yet.</p>';

        // Centers by barangay (top 5)
        const byBarangay = {};
        allCenters.forEach((c) => {
            const name = c.barangay?.name ?? 'Unassigned';
            byBarangay[name] = (byBarangay[name] ?? 0) + 1;
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
                        <div class="bg-purple-500 h-1.5 rounded-full" style="width:${count / maxBrgy * 100}%"></div>
                    </div>
                </div>`).join('')
            : '<p class="text-gray-400">No data yet.</p>';

        // Facility coverage (share of centers with each key facility available)
        const totalCenters = allCenters.length || 1;
        document.getElementById('facility-coverage').innerHTML = KEY_FACILITIES.map(([key, label]) => {
            const count = allCenters.filter((c) => (c.facilities ?? []).some((f) => f.facility_type === key && f.is_available)).length;
            const pct = Math.round(count / totalCenters * 100);
            return `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">${label}</span>
                        <span class="font-medium text-gray-800">${count}/${totalCenters}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-teal-500 h-1.5 rounded-full" style="width:${pct}%"></div>
                    </div>
                </div>`;
        }).join('');
    }

    document.getElementById('search-input').addEventListener('input', renderCards);

    document.getElementById('export-btn').addEventListener('click', () => {
        const rows = [['Name', 'Barangay', 'Type', 'Status', 'Available', 'Capacity', 'Occupancy %']];
        allCenters.forEach((c) => {
            rows.push([
                c.name, c.barangay?.name ?? '', c.type, c.status,
                c.capacity_persons ? (c.capacity_persons - c.current_occupancy) : '',
                c.capacity_persons ?? '', c.occupancy_percent ?? '',
            ]);
        });
        const csv = rows.map((r) => r.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n');
        const blob = new Blob([csv], { type: 'text/csv' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = `evacuation-centers-${new Date().toISOString().slice(0, 10)}.csv`;
        link.click();
    });

    // Named (not an inline IIFE) so it can be called again after a
    // successful save from the modal, refreshing the list in place instead
    // of a full page reload.
    async function loadCenters() {
        try {
            const result = await Api.get('/evacuation-centers');
            const centers = result.data;

            if (centers.length === 0) {
                document.getElementById('cards').innerHTML =
                    '<p class="text-gray-400 text-sm sm:col-span-2 text-center py-16">No evacuation centers yet.</p>';
                document.getElementById('stats-row').classList.add('hidden');
                return;
            }

            // The lightweight index endpoint only returns id/name/barangay_id/status,
            // so fetch full details (with occupancy + facilities) for each card.
            const details = await Promise.all(centers.map((c) => Api.get(`/evacuation-centers/${c.id}`)));
            allCenters = details.map(({ data }) => data);

            document.getElementById('stats-row').classList.remove('hidden');
            document.getElementById('stat-total').textContent = allCenters.length;
            document.getElementById('stat-active').textContent = allCenters.filter((c) => c.status === 'active').length;
            document.getElementById('stat-capacity').textContent =
                allCenters.reduce((sum, c) => sum + (c.capacity_persons ?? 0), 0).toLocaleString();
            document.getElementById('stat-occupancy').textContent =
                allCenters.reduce((sum, c) => sum + (c.current_occupancy ?? 0), 0).toLocaleString();
            document.getElementById('stat-at-risk').textContent =
                allCenters.filter((c) => (c.occupancy_percent ?? 0) >= 90).length;

            renderFilterTabs();
            renderCards();
            renderSidebar();
        } catch (error) {
            showFormErrors(error);
        }
    }

    loadCenters();

    // --- Add-center modal -----------------------------------------------
    // /evacuation-centers/create still exists untouched as a fallback.
    //
    // Leaflet gotcha: the map is created inside a modal that starts
    // display:none. Leaflet measures its container's size at L.map() time,
    // so initializing it while hidden (or before the hidden->flex class
    // swap has actually painted) produces a broken/blank map with tiles
    // positioned for a 0-size container. Fixed two ways: (1) the map is
    // only ever constructed the first time the modal opens -- never on
    // page load, while it's still hidden -- and (2) every open still calls
    // invalidateSize() after a short delay, since the container's size can
    // also go stale from a window resize while the modal was closed.
    const CENTER_MAP_DEFAULT_VIEW = [13.1391, 123.5321];
    const CENTER_MAP_DEFAULT_ZOOM = 13;

    let centerPickerMap = null;
    let centerMarker = null;
    let selectedLat = null;
    let selectedLng = null;

    function initCenterPickerMap() {
        if (centerPickerMap) return;

        centerPickerMap = L.map('center-picker-map').setView(CENTER_MAP_DEFAULT_VIEW, CENTER_MAP_DEFAULT_ZOOM);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(centerPickerMap);

        centerPickerMap.on('click', (e) => {
            selectedLat = e.latlng.lat;
            selectedLng = e.latlng.lng;

            if (centerMarker) {
                centerMarker.setLatLng(e.latlng);
            } else {
                centerMarker = L.marker(e.latlng).addTo(centerPickerMap);
            }

            document.getElementById('center-coords-display').textContent =
                `(${selectedLat.toFixed(6)}, ${selectedLng.toFixed(6)})`;
            document.getElementById('center-coords-display').classList.remove('text-gray-400');
        });
    }

    async function openCenterModal() {
        document.getElementById('center-modal-errors').classList.add('hidden');
        document.getElementById('center-form').reset();

        selectedLat = null;
        selectedLng = null;
        document.getElementById('center-coords-display').textContent = '(click the map to set)';
        document.getElementById('center-coords-display').classList.add('text-gray-400');

        document.getElementById('add-center-modal').classList.remove('hidden');
        document.getElementById('add-center-modal').classList.add('flex');

        initCenterPickerMap();
        if (centerMarker) {
            centerPickerMap.removeLayer(centerMarker);
            centerMarker = null;
        }

        // 100ms: long enough for the hidden->flex class swap to actually
        // paint before Leaflet remeasures the container. setView() resets
        // any panning left over from a previous open.
        setTimeout(() => {
            centerPickerMap.invalidateSize();
            centerPickerMap.setView(CENTER_MAP_DEFAULT_VIEW, CENTER_MAP_DEFAULT_ZOOM);
        }, 100);

        try {
            const barangays = await Api.get('/barangays');
            document.getElementById('center-barangay_id').innerHTML =
                '<option value="">Select barangay</option>' +
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');
        } catch (error) {
            // Dropdown just stays at its default single option if this fails.
        }
    }

    function closeCenterModal() {
        document.getElementById('add-center-modal').classList.add('hidden');
        document.getElementById('add-center-modal').classList.remove('flex');
    }

    document.getElementById('add-center-btn').addEventListener('click', openCenterModal);
    document.getElementById('center-modal-close').addEventListener('click', closeCenterModal);
    document.getElementById('center-modal-cancel').addEventListener('click', closeCenterModal);

    document.getElementById('add-center-modal').addEventListener('click', (e) => {
        if (e.target.id === 'add-center-modal') closeCenterModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('add-center-modal').classList.contains('hidden')) {
            closeCenterModal();
        }
    });

    document.getElementById('center-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (selectedLat === null) {
            const box = document.getElementById('center-modal-errors');
            box.innerHTML = '<p>Click the map to set the evacuation center\'s location before saving.</p>';
            box.classList.remove('hidden');
            return;
        }

        const payload = {
            barangay_id: Number(document.getElementById('center-barangay_id').value),
            name: document.getElementById('center-name').value,
            type: document.getElementById('center-type').value,
            address: document.getElementById('center-address').value,
            latitude: selectedLat,
            longitude: selectedLng,
            capacity_families: document.getElementById('center-capacity_families').value || null,
            capacity_persons: document.getElementById('center-capacity_persons').value || null,
            camp_manager_name: document.getElementById('center-camp_manager_name').value || null,
            camp_manager_contact: document.getElementById('center-camp_manager_contact').value || null,
            status: document.getElementById('center-status').value,
        };

        const button = document.getElementById('center-submit-btn');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            await Api.post('/evacuation-centers', payload);
            closeCenterModal();
            await loadCenters(); // refresh in place, no full page reload
        } catch (error) {
            const box = document.getElementById('center-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Save evacuation center';
        }
    });
</script>
@endsection
