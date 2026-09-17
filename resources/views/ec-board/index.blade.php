@extends('layouts.app')

@section('title', 'EC Board')
@section('nav-ecboard', 'active')

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-semibold mb-1">EC Board</h1>
        <p class="text-sm text-gray-500">Fast path to a center's board -- pick a barangay, then a center, to add evacuees or check live headcount.</p>
    </div>

    {{-- Barangay -> center drill-down, mirroring the Evacuees page's own
        pattern, but two levels only and name-only rows -- this page's only
        job is getting staff to a specific center's board as fast as
        possible, not showing stats (those live on the Evacuation Centers
        page and the board itself). --}}
    <nav id="drill-breadcrumb" class="flex items-center gap-1.5 text-sm text-gray-500 mb-4"></nav>

    {{-- Level 1 (landing view): one row per barangay that has at least one
        evacuation center. --}}
    <div id="barangay-list-view" class="max-w-2xl">
        <div id="barangay-empty-state" class="hidden flex-col items-center text-center py-20 bg-white border border-gray-200 rounded-xl">
            <i class="ti ti-building text-gray-300 mb-3" style="font-size: 40px;" aria-hidden="true"></i>
            <p class="text-sm font-medium text-gray-600 mb-1">No evacuation centers yet</p>
            <p class="text-sm text-gray-400">Centers will appear here once barangays register them.</p>
        </div>
        <div id="barangay-table-wrap" class="hidden bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Barangay</th>
                            <th class="text-left px-4 py-3">Centers</th>
                            <th class="text-left px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="barangay-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Level 2: that barangay's evacuation centers, name only. --}}
    <div id="center-list-view" class="hidden max-w-2xl">
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Evacuation center</th>
                            <th class="text-left px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="center-tbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mt-4 max-w-2xl"></div>
@endsection

@section('scripts')
<script>
    let allCenters = [];
    let barangayNameById = {};
    let currentBarangayId = null;
    let currentBarangayName = '';

    function showLevel(level) {
        document.getElementById('barangay-list-view').classList.toggle('hidden', level !== 'barangay');
        document.getElementById('center-list-view').classList.toggle('hidden', level !== 'center');
    }

    function renderBreadcrumb() {
        const atBarangayLevel = currentBarangayId === null;
        const parts = [atBarangayLevel
            ? '<span class="text-gray-700 font-medium">All barangays</span>'
            : '<a href="#" data-goto="barangay" class="hover:text-brand hover:underline">All barangays</a>'];

        if (! atBarangayLevel) {
            parts.push('<i class="ti ti-chevron-right" style="font-size:12px" aria-hidden="true"></i>');
            parts.push(`<span class="text-gray-700 font-medium">${currentBarangayName}</span>`);
        }

        document.getElementById('drill-breadcrumb').innerHTML = parts.join(' ');
    }

    document.getElementById('drill-breadcrumb').addEventListener('click', (e) => {
        const link = e.target.closest('[data-goto="barangay"]');
        if (! link) return;
        e.preventDefault();
        goToBarangayLevel();
    });

    function goToBarangayLevel() {
        currentBarangayId = null;
        currentBarangayName = '';
        renderBreadcrumb();
        showLevel('barangay');
    }

    function renderBarangayTable() {
        // Count centers per barangay, then keep only barangays with >= 1 --
        // this page exists purely to route staff to a center's board, so a
        // barangay with none is a dead end and just adds noise.
        const countByBarangay = {};
        allCenters.forEach((c) => {
            if (! c.barangay_id) return;
            countByBarangay[c.barangay_id] = (countByBarangay[c.barangay_id] ?? 0) + 1;
        });

        const rows = Object.keys(countByBarangay)
            .map((id) => ({
                barangay_id: Number(id),
                barangay_name: barangayNameById[id] ?? 'Unknown barangay',
                center_count: countByBarangay[id],
            }))
            .sort((a, b) => a.barangay_name.localeCompare(b.barangay_name));

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

        document.getElementById('barangay-tbody').innerHTML = rows.map((r) => `
            <tr class="border-t border-gray-100 hover:bg-gray-50 cursor-pointer" data-barangay-id="${r.barangay_id}" data-barangay-name="${r.barangay_name}">
                <td class="px-4 py-3 font-medium">${r.barangay_name}</td>
                <td class="px-4 py-3">${r.center_count}</td>
                <td class="px-4 py-3 text-right"><i class="ti ti-chevron-right text-gray-400" aria-hidden="true"></i></td>
            </tr>`).join('');
    }

    document.getElementById('barangay-tbody').addEventListener('click', (e) => {
        const row = e.target.closest('tr[data-barangay-id]');
        if (! row) return;
        drillIntoBarangay(Number(row.dataset.barangayId), row.dataset.barangayName);
    });

    function drillIntoBarangay(barangayId, barangayName) {
        currentBarangayId = barangayId;
        currentBarangayName = barangayName;

        renderBreadcrumb();
        showLevel('center');

        const centers = allCenters
            .filter((c) => c.barangay_id === barangayId)
            .sort((a, b) => a.name.localeCompare(b.name));

        document.getElementById('center-tbody').innerHTML = centers.map((c) => `
            <tr class="border-t border-gray-100">
                <td class="px-4 py-3 font-medium">${c.name}</td>
                <td class="px-4 py-3 text-right">
                    <a href="/evacuation-centers/${c.id}/ec-board" class="text-brand hover:underline text-sm font-medium">Go to EC Board &rarr;</a>
                </td>
            </tr>`).join('');
    }

    (async () => {
        try {
            const [centersResult, barangaysResult] = await Promise.all([
                Api.get('/evacuation-centers'),
                Api.get('/barangays'),
            ]);

            allCenters = centersResult.data;
            barangayNameById = Object.fromEntries(barangaysResult.data.map((b) => [b.id, b.name]));

            renderBreadcrumb();
            renderBarangayTable();
        } catch (error) {
            showFormErrors(error);
        }
    })();
</script>
@endsection
