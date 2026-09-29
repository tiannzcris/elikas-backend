@extends('layouts.app')

@section('title', 'Evacuation center')
@section('nav-centers', 'active')

@section('content')
    <a href="/evacuation-centers" class="btn btn-secondary px-3 py-1.5">
        <i class="ti ti-arrow-left" style="font-size: 15px;" aria-hidden="true"></i> Back to evacuation centers
    </a>

    <div id="content-wrap" class="hidden mt-4 max-w-3xl">
        {{-- No EC Board link here -- reaching a center's board is now
            exclusively through the standalone EC Board sidebar section
            (barangay -> centers -> board, see ec-board/index.blade.php).
            This page stays scoped to the center's own management details. --}}
        <div class="card p-5 mb-6">
            <div class="mb-4">
                <img id="center-photo" src="" alt="" class="hidden w-full h-64 object-cover rounded-lg">
                <div id="center-photo-placeholder" class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300">
                    <i class="ti ti-building" style="font-size: 48px;" aria-hidden="true"></i>
                </div>
            </div>
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="page-title" id="center-name"></h1>
                    <p class="page-subtitle" id="center-subtitle"></p>
                </div>
                <span id="center-status" class="badge badge-neutral shrink-0"></span>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-xs font-medium text-gray-600 mb-1">Occupancy</p>
                <p id="center-occupancy" class="text-base font-semibold text-gray-900"></p>
            </div>
            {{-- Two separate contact people, clearly labeled -- matches the
                real EC Information Board template's own structure (a
                primary Camp Manager plus a separate Assistant Camp
                Manager), not just one name split across two inputs. --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-100">
                <div role="group" aria-labelledby="cm-group-label">
                    <p id="cm-group-label" class="card-title mb-2">Camp manager</p>
                    <div class="flex flex-col gap-2">
                        <input type="text" id="cm-name-input" placeholder="Name" aria-label="Camp manager name" class="input">
                        <input type="text" id="cm-contact-input" placeholder="Contact number" aria-label="Camp manager contact number" class="input">
                    </div>
                </div>
                <div role="group" aria-labelledby="acm-group-label">
                    <p id="acm-group-label" class="card-title mb-2">Assistant camp manager</p>
                    <div class="flex flex-col gap-2">
                        <input type="text" id="acm-name-input" placeholder="Name" aria-label="Assistant camp manager name" class="input">
                        <input type="text" id="acm-contact-input" placeholder="Contact number" aria-label="Assistant camp manager contact number" class="input">
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 mt-4">
                <button type="button" id="cm-save-btn" class="btn btn-secondary">
                    Save camp manager info
                </button>
                <span id="cm-saved-msg" class="hidden text-sm font-medium text-green-700">&check; Saved</span>
            </div>
        </div>

        <div id="form-errors" class="hidden callout callout-danger mb-4"></div>

        <form id="facilities-form">
            <div class="card p-4">
                <h2 class="card-title mb-1">Facilities checklist</h2>
                <div id="facilities-list" class="flex flex-col divide-y divide-gray-100"></div>
            </div>
            <button type="submit" id="submit-btn"
                class="hidden mt-4 btn btn-primary">
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
        active: 'badge-success', on_standby: 'badge-neutral',
        full: 'badge-warning', closed: 'badge-danger',
    };
    const STATUS_LABELS = { active: 'Active', on_standby: 'On standby', full: 'Full', closed: 'Closed' };

    let existingFacilities = {};

    function renderFacilitiesList() {
        document.getElementById('facilities-list').innerHTML = facilityTypes.map(([type, label]) => {
            const existing = existingFacilities[type] || { quantity: 0, is_available: true, concerns_and_needs: '' };
            return `
            <div class="py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3" data-type="${type}">
                <span class="text-sm text-gray-900 sm:flex-1">${label}</span>
                <div class="flex items-center gap-3">
                    <input type="number" min="0" value="${existing.quantity}" aria-label="${label} quantity" class="f-quantity input input-sm w-20 tabular-nums">
                    <label class="flex items-center gap-1.5 text-sm text-gray-700 w-24">
                        <input type="checkbox" class="f-available" ${existing.is_available ? 'checked' : ''}> Available
                    </label>
                </div>
                <input type="text" placeholder="Notes" aria-label="${label} notes" value="${existing.concerns_and_needs ?? ''}" class="f-notes input input-sm sm:flex-1">
            </div>`;
        }).join('');
    }

    (async () => {
        try {
            const result = await Api.get(`/evacuation-centers/${centerId}`);
            const c = result.data;

            document.getElementById('center-name').textContent = c.name;
            document.getElementById('center-subtitle').textContent = `${c.barangay?.name ?? '—'} · ${c.address}`;
            document.getElementById('center-status').textContent = STATUS_LABELS[c.status] ?? c.status.replace('_', ' ');
            document.getElementById('center-status').className = `badge shrink-0 ${statusColors[c.status] ?? 'badge-neutral'}`;
            document.getElementById('center-occupancy').textContent =
                c.capacity_persons ? `${c.current_occupancy} / ${c.capacity_persons} persons` : 'No capacity set';
            document.getElementById('cm-name-input').value = c.camp_manager_name || '';
            document.getElementById('cm-contact-input').value = c.camp_manager_contact || '';
            document.getElementById('acm-name-input').value = c.assistant_camp_manager_name || '';
            document.getElementById('acm-contact-input').value = c.assistant_camp_manager_contact || '';

            if (c.photo_url) {
                document.getElementById('center-photo').src = c.photo_url;
                document.getElementById('center-photo').alt = c.name;
                document.getElementById('center-photo').classList.remove('hidden');
                document.getElementById('center-photo-placeholder').classList.add('hidden');
            }

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

    document.getElementById('cm-save-btn').addEventListener('click', async () => {
        const button = document.getElementById('cm-save-btn');
        button.disabled = true;

        try {
            await Api.request(`/evacuation-centers/${centerId}`, {
                method: 'PATCH',
                body: JSON.stringify({
                    camp_manager_name: document.getElementById('cm-name-input').value || null,
                    camp_manager_contact: document.getElementById('cm-contact-input').value || null,
                    assistant_camp_manager_name: document.getElementById('acm-name-input').value || null,
                    assistant_camp_manager_contact: document.getElementById('acm-contact-input').value || null,
                }),
            });
            const msg = document.getElementById('cm-saved-msg');
            msg.classList.remove('hidden');
            setTimeout(() => msg.classList.add('hidden'), 1500);
        } catch (error) {
            showFormErrors(error);
        } finally {
            button.disabled = false;
        }
    });
</script>
@endsection
