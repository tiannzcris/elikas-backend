@extends('layouts.app')

@section('title', 'Family details')
@section('nav-families', 'active')

@section('content')
    <a href="/families" class="text-sm text-gray-500 hover:text-brand">&larr; Back to families</a>

    <div id="content-wrap" class="hidden mt-4">
        <h1 class="text-xl font-semibold mb-1" id="family-title">Family</h1>
        <p class="text-sm text-gray-500 mb-6" id="family-subtitle"></p>

        <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100" id="members-list"></div>
    </div>
@endsection

@section('scripts')
<script>
    const familyId = window.location.pathname.split('/').pop();

    (async () => {
        try {
            const result = await Api.get(`/families/${familyId}`);
            const family = result.data;

            document.getElementById('family-title').textContent =
                `${family.barangay?.name ?? 'Unknown barangay'} — ${family.head_of_family?.full_name ?? 'Family'}`;
            document.getElementById('family-subtitle').textContent =
                `${family.evacuation_event?.name ?? ''} · Registered ${new Date(family.created_at).toLocaleString()}`;

            document.getElementById('members-list').innerHTML = family.members.map((m) => {
                const activeRecord = m.evacuation_records.find(r => ! r.date_out);
                const sectoral = Object.entries(m.sectoral)
                    .filter(([key, val]) => val === true)
                    .map(([key]) => key.replace('is_', '').replace(/_/g, ' '))
                    .join(', ');

                return `
                <div class="p-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium">${m.full_name} <span class="text-gray-400 font-normal">(${m.age} yrs, ${m.age_bracket.replace('_', ' ')})</span></p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            ${activeRecord ? `Checked in at ${activeRecord.evacuation_center?.name ?? 'unspecified location'}` : 'Checked out'}
                            ${sectoral ? ' · ' + sectoral : ''}
                        </p>
                    </div>
                    <span class="text-xs px-2 py-1 rounded-lg ${m.status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'}">
                        ${m.status.replace('_', ' ')}
                    </span>
                </div>`;
            }).join('');

            document.getElementById('content-wrap').classList.remove('hidden');
        } catch (error) {
            showFormErrors(error);
        }
    })();
</script>
@endsection
