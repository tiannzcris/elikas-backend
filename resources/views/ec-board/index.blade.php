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
        page and the board itself). Rows are styled as clickable cards
        (icon + hover lift + chevron), matching the Evacuation Centers list
        page's own card pattern, rather than plain table rows. --}}
    <nav id="drill-breadcrumb" class="flex items-center gap-1.5 text-sm text-gray-500 mb-4"></nav>

    {{-- Level 1 (landing view): one card per barangay that has at least one
        evacuation center. --}}
    <div id="barangay-list-view" class="max-w-3xl">
        <div id="barangay-empty-state" class="hidden flex-col items-center text-center py-20 bg-white border border-gray-200 rounded-xl">
            <i class="ti ti-building text-gray-300 mb-3" style="font-size: 40px;" aria-hidden="true"></i>
            <p class="text-sm font-medium text-gray-600 mb-1">No evacuation centers yet</p>
            <p class="text-sm text-gray-500">Centers will appear here once barangays register them.</p>
        </div>
        {{-- Only for staff assigned to a barangay (barangay officials),
            and only while their barangay is actually in the list below. --}}
        <p id="own-barangay-note" class="hidden text-xs text-gray-500 mb-3">Your barangay is shown first. Other barangays are included so you can help register displaced residents temporarily staying in your area, or view city-wide activity.</p>
        <div id="barangay-list" class="hidden flex flex-col gap-2.5"></div>
    </div>

    {{-- Level 2: that barangay's evacuation centers, name only. --}}
    <div id="center-list-view" class="hidden max-w-3xl">
        <div id="center-list" class="flex flex-col gap-2.5"></div>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mt-4 max-w-3xl"></div>
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

    // Card row shared by both levels -- consistent padding, hover
    // border/shadow lift, icon chip, and a trailing chevron/CTA so the row
    // reads as clickable at a glance instead of relying on cursor:pointer
    // alone (matches the Evacuation Centers list page's own card pattern).
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

        // The logged-in staff member's own barangay (barangay officials
        // only -- administrators/CSWD personnel have none) goes first,
        // everything else stays alphabetical.
        const ownBarangayId = Api.getUser()?.barangay?.id ?? null;
        const ownIndex = rows.findIndex((r) => r.barangay_id === ownBarangayId);
        if (ownIndex > 0) rows.unshift(...rows.splice(ownIndex, 1));
        document.getElementById('own-barangay-note').classList.toggle('hidden', ownIndex === -1);

        const emptyState = document.getElementById('barangay-empty-state');
        const listEl = document.getElementById('barangay-list');

        if (rows.length === 0) {
            listEl.classList.add('hidden');
            emptyState.classList.remove('hidden');
            emptyState.classList.add('flex');
            return;
        }

        emptyState.classList.add('hidden');
        listEl.classList.remove('hidden');

        listEl.innerHTML = rows.map((r) => `
            <button type="button"
                class="w-full flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3.5 text-left hover:border-brand hover:shadow-sm transition-shadow"
                data-barangay-id="${r.barangay_id}" data-barangay-name="${r.barangay_name}">
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-map-pin text-blue-500" style="font-size: 18px;" aria-hidden="true"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-sm text-gray-800">${r.barangay_name}${r.barangay_id === ownBarangayId ? ' <span class="ml-1 text-xs font-medium px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700">Your barangay</span>' : ''}</p>
                    <p class="text-xs text-gray-500">${r.center_count} evacuation center${r.center_count === 1 ? '' : 's'}</p>
                </div>
                <i class="ti ti-chevron-right text-gray-300 shrink-0" style="font-size: 18px;" aria-hidden="true"></i>
            </button>`).join('');
    }

    document.getElementById('barangay-list').addEventListener('click', (e) => {
        const row = e.target.closest('[data-barangay-id]');
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

        document.getElementById('center-list').innerHTML = centers.map((c) => `
            <div class="flex items-center gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3.5 hover:border-brand hover:shadow-sm transition-shadow">
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-building text-green-500" style="font-size: 18px;" aria-hidden="true"></i>
                </div>
                <p class="flex-1 min-w-0 font-medium text-sm text-gray-800">${c.name}</p>
                <a href="/ec-board/${c.id}?from=ec-board&barangay=${currentBarangayId}"
                    class="shrink-0 flex items-center gap-1 bg-brand hover:bg-brand-dark text-white text-xs font-medium rounded-lg px-3 py-2">
                    Go to EC Board <i class="ti ti-arrow-right" style="font-size: 13px;" aria-hidden="true"></i>
                </a>
            </div>`).join('');
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

            // Deep link support: /ec-board?barangay=X lands straight on that
            // barangay's centers list instead of the top-level landing view
            // -- used by a board page's "Back" link (see ec-board/show.blade.php)
            // so it returns to the SAME barangay list the user drilled into,
            // not just the section's default landing view.
            const barangayParam = Number(new URLSearchParams(window.location.search).get('barangay'));
            if (barangayParam && barangayNameById[barangayParam]) {
                drillIntoBarangay(barangayParam, barangayNameById[barangayParam]);
            }
        } catch (error) {
            showFormErrors(error);
        }
    })();
</script>
@endsection
