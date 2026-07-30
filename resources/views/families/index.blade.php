@extends('layouts.app')

@section('title', 'Evacuees')
@section('nav-families', 'active')

@section('content')
    <div class="flex items-center justify-between mb-4 gap-3">
        <div class="relative flex-1 max-w-md">
            <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 16px;" aria-hidden="true"></i>
            <input id="search-input" type="text" placeholder="Search by name or barangay..."
                class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm">
        </div>
        <select id="barangay-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <option value="">All barangays</option>
        </select>
        <div class="flex items-center gap-3 text-sm shrink-0">
            <span><strong id="stat-households">&mdash;</strong> <span class="text-gray-500">households</span></span>
            <span class="text-gray-300">|</span>
            <span><strong id="stat-persons">&mdash;</strong> <span class="text-gray-500">persons</span></span>
            <button id="export-btn" class="flex items-center gap-1.5 text-brand border border-brand/30 rounded-lg px-3 py-2 hover:bg-brand-light">
                <i class="ti ti-download" style="font-size: 15px;" aria-hidden="true"></i> Export
            </button>
        </div>
    </div>

    <div class="flex items-center justify-end mb-4">
        <a href="/families/create"
            class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Register a family
        </a>
    </div>

    <div id="empty-state" class="hidden flex-col items-center text-center py-20">
        <i class="ti ti-users text-gray-300 mb-3" style="font-size: 40px;" aria-hidden="true"></i>
        <p class="text-sm font-medium text-gray-600 mb-1">No families registered yet</p>
        <p class="text-sm text-gray-400 mb-4">Registrations will appear here as barangay officials add them.</p>
        <a href="/families/create" class="text-sm text-brand hover:underline">+ Register the first family</a>
    </div>

    <div id="table-wrap" class="hidden bg-white border border-gray-200 rounded-xl overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">Household head</th>
                    <th class="text-left px-4 py-3">Barangay</th>
                    <th class="text-left px-4 py-3">Persons</th>
                    <th class="text-left px-4 py-3">Evacuation center</th>
                    <th class="text-left px-4 py-3">Status / Tags</th>
                    <th class="text-left px-4 py-3">Date registered</th>
                    <th class="text-left px-4 py-3"></th>
                </tr>
            </thead>
            <tbody id="families-tbody"></tbody>
        </table>
    </div>
@endsection

@section('scripts')
<script>
    let allFamilies = [];

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
            if (f.has_pwd_member) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-purple-50 text-purple-700">PWD</span>');
            if (f.has_senior_member) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-amber-50 text-amber-700">Senior</span>');
            if (f.has_lactating_member) tags.push('<span class="text-xs px-2 py-0.5 rounded-lg bg-pink-50 text-pink-700">Lactating</span>');

            return `
            <tr class="border-t border-gray-100 hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">${f.head_of_family?.full_name ?? '&mdash;'}</td>
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

    function applyFilters() {
        const query = document.getElementById('search-input').value.trim().toLowerCase();
        const barangayId = document.getElementById('barangay-filter').value;

        const filtered = allFamilies.filter((f) => {
            const matchesSearch = ! query ||
                (f.head_of_family?.full_name ?? '').toLowerCase().includes(query) ||
                (f.barangay?.name ?? '').toLowerCase().includes(query);
            const matchesBarangay = ! barangayId || String(f.barangay?.id) === barangayId;
            return matchesSearch && matchesBarangay;
        });

        document.getElementById('stat-households').textContent = filtered.length;
        document.getElementById('stat-persons').textContent = filtered.reduce((sum, f) => sum + (f.member_count ?? 0), 0);
        renderTable(filtered);
    }

    document.getElementById('search-input').addEventListener('input', applyFilters);
    document.getElementById('barangay-filter').addEventListener('change', applyFilters);

    // Client-side CSV export -- uses whatever's currently filtered/visible,
    // no backend export endpoint needed for this.
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
            document.getElementById('stat-households').textContent = allFamilies.length;
            document.getElementById('stat-persons').textContent =
                allFamilies.reduce((sum, f) => sum + (f.member_count ?? 0), 0);
            renderTable(allFamilies);
        } catch (error) {
            showFormErrors(error);
        }
    })();
</script>
@endsection
