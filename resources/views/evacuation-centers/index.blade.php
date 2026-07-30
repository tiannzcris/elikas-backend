@extends('layouts.app')

@section('title', 'Evacuation centers')
@section('nav-centers', 'active')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold mb-1">Evacuation centers</h1>
            <p class="text-sm text-gray-500">Capacity and live occupancy across Ligao City.</p>
        </div>
        <a href="/evacuation-centers/create" id="add-center-btn"
            class="hidden bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Add evacuation center
        </a>
    </div>

    <div id="stats-row" class="hidden grid grid-cols-4 gap-4 mb-6">
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
    </div>

    <div class="flex items-center justify-between mb-4">
        <div id="filter-tabs" class="flex items-center gap-2"></div>
        <button id="export-btn" class="flex items-center gap-1.5 text-brand border border-brand/30 rounded-lg px-3 py-2 text-sm hover:bg-brand-light">
            <i class="ti ti-download" style="font-size: 15px;" aria-hidden="true"></i> Export
        </button>
    </div>

    <div id="cards" class="grid grid-cols-2 gap-4"></div>
@endsection

@section('scripts')
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

    let allCenters = [];
    let activeFilter = 'all';

    function renderFilterTabs() {
        const counts = {
            all: allCenters.length,
            active: allCenters.filter((c) => c.status === 'active').length,
            full: allCenters.filter((c) => c.status === 'full').length,
            closed: allCenters.filter((c) => c.status === 'closed').length,
        };
        const labels = { all: 'All', active: 'Active', full: 'Full', closed: 'Closed' };

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
        const filtered = activeFilter === 'all' ? allCenters : allCenters.filter((c) => c.status === activeFilter);

        document.getElementById('cards').innerHTML = filtered.length === 0
            ? '<p class="text-gray-400 text-sm col-span-2 text-center py-16">No centers match this filter.</p>'
            : filtered.map((c) => {
                const pct = c.occupancy_percent ?? 0;
                const barColor = pct >= 100 ? 'bg-red-500' : pct >= 75 ? 'bg-amber-500' : 'bg-green-500';

                return `
                <a href="/evacuation-centers/${c.id}" class="bg-white border border-gray-200 rounded-xl p-4 block hover:border-brand">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex items-start gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                <i class="ti ti-building text-blue-500" style="font-size: 18px;" aria-hidden="true"></i>
                            </div>
                            <div>
                                <p class="font-medium text-sm">${c.name}</p>
                                <p class="text-xs text-gray-500">${c.barangay?.name ?? '—'} · ${c.type.replace('_', ' ')}</p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-lg ${statusColors[c.status] ?? ''}">${c.status.replace('_', ' ')}</span>
                    </div>
                    ${c.capacity_persons ? `
                        <div class="flex justify-between text-xs text-gray-500 mb-1">
                            <span>Occupancy</span><span>${c.current_occupancy} / ${c.capacity_persons}</span>
                        </div>
                        <div class="h-1.5 bg-gray-100 rounded-full">
                            <div class="h-1.5 ${barColor} rounded-full" style="width: ${Math.min(pct, 100)}%"></div>
                        </div>
                    ` : '<p class="text-xs text-gray-400">No capacity set</p>'}
                </a>`;
            }).join('');
    }

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

    (async () => {
        try {
            const result = await Api.get('/evacuation-centers');
            const centers = result.data;

            if (centers.length === 0) {
                document.getElementById('cards').innerHTML =
                    '<p class="text-gray-400 text-sm col-span-2 text-center py-16">No evacuation centers yet.</p>';
                return;
            }

            // The lightweight index endpoint only returns id/name/barangay_id/status,
            // so fetch full details (with occupancy) for each card.
            const details = await Promise.all(centers.map((c) => Api.get(`/evacuation-centers/${c.id}`)));
            allCenters = details.map(({ data }) => data);

            document.getElementById('stats-row').classList.remove('hidden');
            document.getElementById('stats-row').classList.add('grid');
            document.getElementById('stat-total').textContent = allCenters.length;
            document.getElementById('stat-active').textContent = allCenters.filter((c) => c.status === 'active').length;
            document.getElementById('stat-capacity').textContent =
                allCenters.reduce((sum, c) => sum + (c.capacity_persons ?? 0), 0).toLocaleString();
            document.getElementById('stat-occupancy').textContent =
                allCenters.reduce((sum, c) => sum + (c.current_occupancy ?? 0), 0).toLocaleString();

            renderFilterTabs();
            renderCards();
        } catch (error) {
            showFormErrors(error);
        }
    })();
</script>
@endsection
