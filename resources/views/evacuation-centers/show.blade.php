@extends('layouts.app')

@section('title', 'Evacuation center')
@section('nav-centers', 'active')

@section('content')
    <a href="/evacuation-centers" class="text-sm text-gray-500 hover:text-brand">&larr; Back to evacuation centers</a>

    <div id="content-wrap" class="hidden mt-4 max-w-3xl">
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-semibold" id="center-name"></h1>
                    <p class="text-sm text-gray-500" id="center-subtitle"></p>
                </div>
                <span id="center-status" class="text-xs px-2 py-1 rounded-lg"></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 text-sm">
                <div>
                    <p class="text-xs text-gray-500">Occupancy</p>
                    <p id="center-occupancy" class="font-medium"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Camp manager</p>
                    <p id="center-manager" class="font-medium"></p>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Contact</p>
                    <p id="center-contact" class="font-medium"></p>
                </div>
            </div>
        </div>

        <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

        <form id="facilities-form">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-medium text-gray-700 mb-3">Facilities checklist</p>
                <div id="facilities-list" class="flex flex-col divide-y divide-gray-100"></div>
            </div>
            <button type="submit" id="submit-btn"
                class="hidden mt-4 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                Save facilities checklist
            </button>
        </form>
    </div>
@endsection

@section('scripts')
<script>
    const centerId = window.location.pathname.split('/').pop();

    // Matches the exact 19 facility_type values defined in the
    // evacuation_center_facilities migration -- grouped here only for
    // display, the value sent to the API is the same enum string either way.
    const facilityTypes = [
        ['toilet_male', 'Toilet (male)'], ['toilet_female', 'Toilet (female)'], ['toilet_common', 'Toilet (common)'],
        ['latrine_compost_pit', 'Latrine (compost pit)'], ['latrine_sealed', 'Latrine (sealed)'],
        ['bathing_area_male', 'Bathing area (male)'], ['bathing_area_female', 'Bathing area (female)'], ['bathing_area_common', 'Bathing area (common)'],
        ['handwashing_facility', 'Handwashing facility'], ['laundry_space', 'Laundry space'],
        ['women_friendly_space', 'Women-friendly space'], ['child_friendly_space', 'Child-friendly space'],
        ['health_facility', 'Health facility'], ['prayer_room', 'Prayer room'], ['community_kitchen', 'Community kitchen'],
        ['livestock_area', 'Livestock area'], ['camp_management_desk', 'Camp management desk'],
        ['info_board', 'Info board'], ['storage_area', 'Storage area'],
    ];

    const statusColors = {
        active: 'bg-green-50 text-green-700', on_standby: 'bg-gray-100 text-gray-600',
        full: 'bg-amber-50 text-amber-700', closed: 'bg-red-50 text-red-700',
    };

    let existingFacilities = {};

    function renderFacilitiesList() {
        document.getElementById('facilities-list').innerHTML = facilityTypes.map(([type, label]) => {
            const existing = existingFacilities[type] || { quantity: 0, is_available: true, concerns_and_needs: '' };
            return `
            <div class="py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3" data-type="${type}">
                <span class="text-sm sm:flex-1">${label}</span>
                <div class="flex items-center gap-3">
                    <input type="number" min="0" value="${existing.quantity}" aria-label="${label} quantity" class="f-quantity w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm">
                    <label class="flex items-center gap-1.5 text-xs text-gray-500 w-20">
                        <input type="checkbox" class="f-available" ${existing.is_available ? 'checked' : ''}> Available
                    </label>
                </div>
                <input type="text" placeholder="Notes" value="${existing.concerns_and_needs ?? ''}" class="f-notes w-full sm:flex-1 border border-gray-300 rounded-lg px-2 py-1 text-xs">
            </div>`;
        }).join('');
    }

    (async () => {
        try {
            const result = await Api.get(`/evacuation-centers/${centerId}`);
            const c = result.data;

            document.getElementById('center-name').textContent = c.name;
            document.getElementById('center-subtitle').textContent = `${c.barangay?.name ?? '—'} · ${c.address}`;
            document.getElementById('center-status').textContent = c.status.replace('_', ' ');
            document.getElementById('center-status').className = `text-xs px-2 py-1 rounded-lg ${statusColors[c.status] ?? ''}`;
            document.getElementById('center-occupancy').textContent =
                c.capacity_persons ? `${c.current_occupancy} / ${c.capacity_persons} persons` : 'No capacity set';
            document.getElementById('center-manager').textContent = c.camp_manager_name || '—';
            document.getElementById('center-contact').textContent = c.camp_manager_contact || '—';

            (c.facilities || []).forEach((f) => { existingFacilities[f.facility_type] = f; });
            renderFacilitiesList();

            const user = Api.getUser();
            if (user && user.role !== 'barangay_official') {
                document.getElementById('submit-btn').classList.remove('hidden');
            }

            document.getElementById('content-wrap').classList.remove('hidden');
        } catch (error) {
            showFormErrors(error);
        }
    })();

    document.getElementById('facilities-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const facilities = Array.from(document.querySelectorAll('#facilities-list > div')).map((row) => ({
            facility_type: row.dataset.type,
            quantity: Number(row.querySelector('.f-quantity').value) || 0,
            is_available: row.querySelector('.f-available').checked,
            concerns_and_needs: row.querySelector('.f-notes').value || null,
        }));

        const button = document.getElementById('submit-btn');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            await Api.request(`/evacuation-centers/${centerId}/facilities`, {
                method: 'PUT',
                body: JSON.stringify({ facilities }),
            });
            button.textContent = 'Saved!';
            setTimeout(() => { button.disabled = false; button.textContent = 'Save facilities checklist'; }, 1500);
        } catch (error) {
            showFormErrors(error);
            button.disabled = false;
            button.textContent = 'Save facilities checklist';
        }
    });
</script>
@endsection
