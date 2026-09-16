@extends('layouts.app')

@section('title', 'Evacuation center')
@section('nav-centers', 'active')

@section('content')
    <a href="/evacuation-centers" class="text-sm text-gray-500 hover:text-brand">&larr; Back to evacuation centers</a>

    <div id="content-wrap" class="hidden mt-4 max-w-3xl">
        {{-- EC Information Board: the real DSWD/CSWDO aggregate report --
            families/persons cumulative vs now, age/sex disaggregation, and
            sectoral group breakdown. Deliberately the first thing on this
            page, well above the detailed family/evacuee registration flow.
            "Add Evacuee" (age/sex breakdown) creates REAL Evacuee+Family
            records directly -- it is not a typed number reconciled against
            generated placeholders afterward, so families_now/persons_now
            and the age/sex table below are all LIVE, computed straight from
            those records every time this page loads. Only
            families_cumulative/persons_cumulative (a running total that
            survives someone later being removed) and the
            beneficiaries_4ps/sectoral figures (not known until a person's
            fuller details are filled in, so kept as a directly-reported
            aggregate) are still separately tracked. --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6">
            <div class="mb-3">
                <p class="text-sm font-semibold text-gray-800">EC Information Board</p>
                <p class="text-xs text-gray-500">Add evacuees here first -- register full details later, as time allows.</p>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 border-y border-gray-100 py-2 mb-4">
                <span><span class="text-gray-400">Barangay:</span> <span id="ecb-barangay" class="text-gray-700 font-medium"></span></span>
                <span><span class="text-gray-400">Evacuation center:</span> <span id="ecb-center-name" class="text-gray-700 font-medium"></span></span>
                <span class="flex items-center gap-1.5">
                    <span class="text-gray-400">Event:</span>
                    <select id="ecb-event-select" class="border border-gray-300 rounded-lg px-2 py-1 text-xs"></select>
                </span>
                <span class="ml-auto flex items-center gap-1"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Live</span>
            </div>

            <p class="text-xs font-semibold text-gray-600 mb-2">Displaced families / persons</p>
            <div class="overflow-x-auto mb-1">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="text-xs text-gray-500">
                            <th class="text-left font-medium py-1"></th>
                            <th class="text-left font-medium py-1 w-28">Cumulative</th>
                            <th class="text-left font-medium py-1 w-28">Now</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t border-gray-100">
                            <td class="py-1.5 text-gray-700">Families</td>
                            <td class="py-1.5 font-medium" id="ecb-families-cumulative">0</td>
                            <td class="py-1.5 font-medium" id="ecb-families-now">0</td>
                        </tr>
                        <tr class="border-t border-gray-100">
                            <td class="py-1.5 text-gray-700">Persons</td>
                            <td class="py-1.5 font-medium" id="ecb-persons-cumulative">0</td>
                            <td class="py-1.5 font-medium" id="ecb-persons-now">0</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-400 mb-4">"Now" reflects current records exactly; "Cumulative" is a running total from every evacuee added here and never drops when someone is later removed.</p>

            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-600">Age &amp; sex disaggregation</p>
                <button type="button" id="add-evacuee-btn" class="text-xs text-brand hover:underline">+ Add evacuee</button>
            </div>
            <div class="overflow-x-auto mb-4">
                <table class="w-full text-sm border-collapse">
                    <thead>
                        <tr class="text-xs text-gray-500">
                            <th class="text-left font-medium py-1">Age bracket</th>
                            <th class="text-left font-medium py-1 w-20">Male</th>
                            <th class="text-left font-medium py-1 w-20">Female</th>
                            <th class="text-left font-medium py-1 w-20">Total</th>
                        </tr>
                    </thead>
                    <tbody id="age-sex-rows"></tbody>
                    <tfoot>
                        <tr class="border-t-2 border-gray-200 font-semibold text-gray-800">
                            <td class="py-1.5">Total</td>
                            <td class="py-1.5" id="age-total-male">0</td>
                            <td class="py-1.5" id="age-total-female">0</td>
                            <td class="py-1.5" id="age-total-all">0</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Only these two remain manually saved -- see this page's own
                notes and EvacuationCenterController::updateQuickCount()'s
                docblock for why sectoral flags stay a reported aggregate
                instead of also going live. --}}
            <form id="ecboard-form">
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-600 mb-1" for="ecb-beneficiaries-4ps">4Ps beneficiary families</label>
                    <input type="number" min="0" id="ecb-beneficiaries-4ps" class="w-28 border border-gray-300 rounded-lg px-2 py-1 text-sm">
                </div>

                <p class="text-xs font-semibold text-gray-600 mb-2">Sectoral group breakdown</p>
                <div class="overflow-x-auto mb-4">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="text-xs text-gray-500">
                                <th class="text-left font-medium py-1">Sectoral group</th>
                                <th class="text-left font-medium py-1 w-20">Male</th>
                                <th class="text-left font-medium py-1 w-20">Female</th>
                                <th class="text-left font-medium py-1 w-20">Total</th>
                            </tr>
                        </thead>
                        <tbody id="sectoral-rows"></tbody>
                    </table>
                </div>

                <p id="ecb-updated-meta" class="text-xs text-gray-400 mb-3">Beneficiaries/sectoral figures not yet reported for this event.</p>

                <button type="submit" id="ecb-save-btn"
                    class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                    Save beneficiaries &amp; sectoral figures
                </button>
            </form>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6">
            <div class="mb-4">
                <img id="center-photo" src="" alt="" class="hidden w-full h-64 object-cover rounded-lg">
                <div id="center-photo-placeholder" class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300">
                    <i class="ti ti-building" style="font-size: 48px;" aria-hidden="true"></i>
                </div>
            </div>
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
                    <p class="text-xs text-gray-500 mb-1">Camp manager</p>
                    <input type="text" id="cm-name-input" placeholder="Camp manager name" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-sm">
                </div>
                <div>
                    <p class="text-xs text-gray-500 mb-1">Contact</p>
                    <input type="text" id="cm-contact-input" placeholder="Contact number" class="w-full border border-gray-300 rounded-lg px-2 py-1 text-sm">
                </div>
            </div>
            <div class="flex items-center gap-2 mt-3">
                <button type="button" id="cm-save-btn" class="bg-brand hover:bg-brand-dark text-white text-xs font-medium rounded-lg px-3 py-1.5">
                    Save camp manager info
                </button>
                <span id="cm-saved-msg" class="hidden text-xs text-green-600">Saved!</span>
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

    <div id="add-evacuee-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800">Add evacuee</p>
                    <p class="text-xs text-gray-500">Creates a real record right away -- fill in their full name and birthdate later via "Add details" on the Evacuees page.</p>
                </div>
                <button type="button" id="add-evacuee-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="add-evacuee-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="add-evacuee-form" class="flex flex-col gap-4 p-5">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Age bracket</label>
                        <select id="ae-age-bracket" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Sex</label>
                        <select id="ae-sex" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-1 flex w-fit text-sm">
                    <button type="button" id="ae-mode-existing-btn" class="ae-mode-btn px-3 py-1.5 rounded-lg font-medium bg-brand text-white" data-mode="existing">
                        Existing household
                    </button>
                    <button type="button" id="ae-mode-new-btn" class="ae-mode-btn px-3 py-1.5 rounded-lg font-medium text-gray-500" data-mode="new">
                        New household
                    </button>
                </div>

                <div id="ae-existing-section">
                    <label class="text-xs text-gray-500 block mb-1">Household already at this center</label>
                    <select id="ae-family-id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">No households registered here yet</option>
                    </select>
                </div>

                <div id="ae-new-section" class="hidden flex-col gap-3">
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Barangay</label>
                        <select id="ae-barangay-id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Head of family name</label>
                        <input type="text" id="ae-family-name" placeholder="e.g. Juan Dela Cruz" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="add-evacuee-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="add-evacuee-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                        Add evacuee
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const centerId = window.location.pathname.split('/').pop();
    let centerBarangayId = null;

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

    // Matches the exact age_bracket / sectoral_group enum values this
    // system uses (see Evacuee::age_bracket/age_bracket_override and the
    // evacuation_center_quick_count_sectoral_groups migration) -- order
    // here is the order the EC Board template presents them in, and the
    // resource returns rows in this same order.
    const ageBrackets = [
        ['infant', 'Infant (0-6 mos)'], ['toddler', 'Toddler (7 mos - 2 yrs)'], ['preschooler', 'Preschooler (3-5 yrs)'],
        ['school_age', 'School age (6-12 yrs)'], ['teenage', 'Teenage (13-17 yrs)'],
        ['adult', 'Adult (18-59 yrs)'], ['senior_citizen', 'Senior citizen (60+)'],
    ];

    const sectoralGroups = [
        ['pwd', 'Persons with disability (PWD)'], ['child_headed_family', 'Child-headed family'],
        ['single_headed_family', 'Single-headed family'], ['solo_parent', 'Solo parent'],
        ['pregnant_women', 'Pregnant women'], ['lactating_mothers', 'Lactating mothers'],
        ['four_ps_beneficiary', '4Ps beneficiary'], ['indigenous_peoples', 'Indigenous peoples'],
    ];

    const ageBracketLabel = (key) => ageBrackets.find(([k]) => k === key)?.[1] ?? key;

    let existingFacilities = {};

    function renderSectoralRows() {
        document.getElementById('sectoral-rows').innerHTML = sectoralGroups.map(([group, label]) => `
            <tr class="border-t border-gray-100" data-group="${group}">
                <td class="py-1.5 text-gray-700">${label}</td>
                <td class="py-1.5"><input type="number" min="0" value="0" class="sect-male w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm"></td>
                <td class="py-1.5"><input type="number" min="0" value="0" class="sect-female w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm"></td>
                <td class="py-1.5 sect-row-total text-gray-500">0</td>
            </tr>
        `).join('');
    }

    // Recomputed client-side on every keystroke so the Total row/column
    // always matches what's on screen -- never sent to the server, which
    // derives the same figures itself from the saved rows.
    function recalculateSectoralTotals() {
        document.querySelectorAll('#sectoral-rows tr').forEach((row) => {
            const male = Number(row.querySelector('.sect-male').value) || 0;
            const female = Number(row.querySelector('.sect-female').value) || 0;
            row.querySelector('.sect-row-total').textContent = male + female;
        });
    }

    // qc.families_now/persons_now/age_groups/age_groups_total are all LIVE
    // (computed server-side from real records, see
    // EvacuationCenterQuickCount's live*() methods) -- this just displays
    // them, there's nothing to recompute client-side anymore for that part.
    function renderEcBoard(qc) {
        document.getElementById('ecb-families-cumulative').textContent = qc.families_cumulative;
        document.getElementById('ecb-families-now').textContent = qc.families_now;
        document.getElementById('ecb-persons-cumulative').textContent = qc.persons_cumulative;
        document.getElementById('ecb-persons-now').textContent = qc.persons_now;
        document.getElementById('ecb-beneficiaries-4ps').value = qc.beneficiaries_4ps;

        // The 'unclassified' row (always last -- see
        // EvacuationCenterQuickCount::liveAgeSexBreakdown()) covers anyone
        // currently here missing sex and/or age bracket entirely. Its own
        // Total must come from total_count, not male_count + female_count
        // -- someone with unknown sex is in total_count but in neither
        // column, and dropping them here would silently reintroduce the
        // exact Now-vs-breakdown mismatch this row exists to prevent.
        document.getElementById('age-sex-rows').innerHTML = qc.age_groups.map((row) => {
            const isUnclassified = row.age_bracket === 'unclassified';
            const label = isUnclassified ? 'Not yet classified' : ageBracketLabel(row.age_bracket);
            const total = isUnclassified ? row.total_count : (row.male_count + row.female_count);
            const rowClass = isUnclassified ? 'border-t border-gray-100 bg-amber-50 text-amber-800' : 'border-t border-gray-100';

            return `
            <tr class="${rowClass}">
                <td class="py-1.5 ${isUnclassified ? '' : 'text-gray-700'}">${label}</td>
                <td class="py-1.5">${row.male_count}</td>
                <td class="py-1.5">${row.female_count}</td>
                <td class="py-1.5 ${isUnclassified ? '' : 'text-gray-500'}">${total}</td>
            </tr>`;
        }).join('');
        document.getElementById('age-total-male').textContent = qc.age_groups_total.male_count;
        document.getElementById('age-total-female').textContent = qc.age_groups_total.female_count;
        document.getElementById('age-total-all').textContent = qc.age_groups_total.total_persons;

        const sectoralByGroup = Object.fromEntries(qc.sectoral_groups.map((row) => [row.sectoral_group, row]));
        document.querySelectorAll('#sectoral-rows tr').forEach((row) => {
            const data = sectoralByGroup[row.dataset.group];
            row.querySelector('.sect-male').value = data?.male_count ?? 0;
            row.querySelector('.sect-female').value = data?.female_count ?? 0;
        });
        recalculateSectoralTotals();

        document.getElementById('ecb-updated-meta').textContent = qc.updated_at
            ? `Beneficiaries/sectoral figures last saved ${new Date(qc.updated_at).toLocaleString()}${qc.updated_by_name ? ` by ${qc.updated_by_name}` : ''}`
            : 'Beneficiaries/sectoral figures not yet reported for this event.';
    }

    async function loadEcBoard(eventId) {
        if (! eventId) {
            return;
        }
        try {
            const result = await Api.get(`/evacuation-centers/${centerId}/quick-count?evacuation_event_id=${eventId}`);
            renderEcBoard(result.data);
        } catch (error) {
            showFormErrors(error);
        }
    }

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

    renderSectoralRows();

    (async () => {
        try {
            const [result, eventsResult] = await Promise.all([
                Api.get(`/evacuation-centers/${centerId}`),
                Api.get('/evacuation-events'),
            ]);
            const c = result.data;
            centerBarangayId = c.barangay?.id ?? null;

            document.getElementById('ecb-barangay').textContent = c.barangay?.name ?? '—';
            document.getElementById('ecb-center-name').textContent = c.name;

            // Same non-closed filter as families/create.blade.php -- staff
            // report the board against a live event, not a closed one.
            // Defaults to the most recent open event so the common case
            // (one active disaster) needs no extra clicks.
            const openEvents = eventsResult.data.filter((ev) => ev.status !== 'closed');
            const eventSelect = document.getElementById('ecb-event-select');
            eventSelect.innerHTML = openEvents.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join('')
                || '<option value="">No active events</option>';
            document.getElementById('add-evacuee-btn').disabled = ! openEvents.length;
            document.getElementById('add-evacuee-btn').classList.toggle('opacity-40', ! openEvents.length);
            if (openEvents.length) {
                await loadEcBoard(eventSelect.value);
            }

            document.getElementById('center-name').textContent = c.name;
            document.getElementById('center-subtitle').textContent = `${c.barangay?.name ?? '—'} · ${c.address}`;
            document.getElementById('center-status').textContent = c.status.replace('_', ' ');
            document.getElementById('center-status').className = `text-xs px-2 py-1 rounded-lg ${statusColors[c.status] ?? ''}`;
            document.getElementById('center-occupancy').textContent =
                c.capacity_persons ? `${c.current_occupancy} / ${c.capacity_persons} persons` : 'No capacity set';
            document.getElementById('cm-name-input').value = c.camp_manager_name || '';
            document.getElementById('cm-contact-input').value = c.camp_manager_contact || '';

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

    document.getElementById('ecb-event-select').addEventListener('change', (e) => {
        loadEcBoard(e.target.value);
    });

    document.getElementById('sectoral-rows').addEventListener('input', recalculateSectoralTotals);

    document.getElementById('ecboard-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const eventId = document.getElementById('ecb-event-select').value;
        if (! eventId) {
            return;
        }

        const button = document.getElementById('ecb-save-btn');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            const result = await Api.request(`/evacuation-centers/${centerId}/quick-count`, {
                method: 'PUT',
                body: JSON.stringify({
                    evacuation_event_id: Number(eventId),
                    beneficiaries_4ps: Number(document.getElementById('ecb-beneficiaries-4ps').value) || 0,
                    sectoral_groups: Array.from(document.querySelectorAll('#sectoral-rows tr')).map((row) => ({
                        sectoral_group: row.dataset.group,
                        male_count: Number(row.querySelector('.sect-male').value) || 0,
                        female_count: Number(row.querySelector('.sect-female').value) || 0,
                    })),
                }),
            });
            renderEcBoard(result.data);
            button.textContent = 'Saved!';
            setTimeout(() => { button.textContent = 'Save beneficiaries & sectoral figures'; }, 1500);
        } catch (error) {
            showFormErrors(error);
            button.textContent = 'Save beneficiaries & sectoral figures';
        } finally {
            button.disabled = false;
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

    // --- Add Evacuee modal --------------------------------------------------

    let aeMode = 'existing';

    document.querySelectorAll('.ae-mode-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            aeMode = btn.dataset.mode;

            document.querySelectorAll('.ae-mode-btn').forEach((b) => {
                b.classList.toggle('bg-brand', b === btn);
                b.classList.toggle('text-white', b === btn);
                b.classList.toggle('text-gray-500', b !== btn);
            });

            document.getElementById('ae-existing-section').classList.toggle('hidden', aeMode !== 'existing');
            document.getElementById('ae-new-section').classList.toggle('hidden', aeMode !== 'new');
            document.getElementById('ae-new-section').classList.toggle('flex', aeMode === 'new');
        });
    });

    async function openAddEvacueeModal() {
        const eventId = document.getElementById('ecb-event-select').value;
        if (! eventId) {
            return;
        }

        document.getElementById('add-evacuee-errors').classList.add('hidden');
        document.getElementById('add-evacuee-form').reset();
        document.getElementById('ae-mode-existing-btn').click();

        document.getElementById('ae-age-bracket').innerHTML =
            ageBrackets.map(([key, label]) => `<option value="${key}">${label}</option>`).join('');

        document.getElementById('add-evacuee-modal').classList.remove('hidden');
        document.getElementById('add-evacuee-modal').classList.add('flex');

        try {
            const [familiesResult, barangaysResult] = await Promise.all([
                Api.get(`/evacuation-centers/${centerId}/families?evacuation_event_id=${eventId}`),
                Api.get('/barangays'),
            ]);

            const families = familiesResult.data;
            document.getElementById('ae-family-id').innerHTML = families.length
                ? families.map((f) => {
                    const label = f.name || f.head_of_family?.full_name || `Family #${f.id}`;
                    return `<option value="${f.id}">${label} (${f.member_count} member${f.member_count === 1 ? '' : 's'})</option>`;
                }).join('')
                : '<option value="">No households registered here yet</option>';

            document.getElementById('ae-barangay-id').innerHTML =
                barangaysResult.data.map((b) => `<option value="${b.id}" ${b.id === centerBarangayId ? 'selected' : ''}>${b.name}</option>`).join('');
        } catch (error) {
            // Dropdowns just stay at their default single option if this
            // fails -- the rest of the form is still usable.
        }
    }

    function closeAddEvacueeModal() {
        document.getElementById('add-evacuee-modal').classList.add('hidden');
        document.getElementById('add-evacuee-modal').classList.remove('flex');
    }

    document.getElementById('add-evacuee-btn').addEventListener('click', openAddEvacueeModal);
    document.getElementById('add-evacuee-modal-close').addEventListener('click', closeAddEvacueeModal);
    document.getElementById('add-evacuee-cancel').addEventListener('click', closeAddEvacueeModal);

    document.getElementById('add-evacuee-modal').addEventListener('click', (e) => {
        if (e.target.id === 'add-evacuee-modal') closeAddEvacueeModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('add-evacuee-modal').classList.contains('hidden')) {
            closeAddEvacueeModal();
        }
    });

    document.getElementById('add-evacuee-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const eventId = document.getElementById('ecb-event-select').value;
        const payload = {
            evacuation_event_id: Number(eventId),
            age_bracket: document.getElementById('ae-age-bracket').value,
            sex: document.getElementById('ae-sex').value,
            household_mode: aeMode,
        };

        if (aeMode === 'existing') {
            payload.family_id = Number(document.getElementById('ae-family-id').value) || null;
        } else {
            payload.barangay_id = Number(document.getElementById('ae-barangay-id').value) || null;
            payload.family_name = document.getElementById('ae-family-name').value;
        }

        const button = document.getElementById('add-evacuee-submit-btn');
        button.disabled = true;
        button.textContent = 'Adding...';

        try {
            await Api.request(`/evacuation-centers/${centerId}/evacuees`, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            closeAddEvacueeModal();
            await loadEcBoard(eventId); // refresh the live counts in place
        } catch (error) {
            const box = document.getElementById('add-evacuee-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Add evacuee';
        }
    });
</script>
@endsection
