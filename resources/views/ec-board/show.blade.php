@extends('layouts.app')

@section('title', 'EC Information Board')
@section('nav-ecboard', 'active')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
        {{-- A real secondary-button treatment (border + hover fill), not a
            plain sentence -- matches this app's own existing ghost-button
            convention (e.g. modal Cancel buttons: border-gray-300 +
            hover:bg-gray-50) rather than inventing a new style. --}}
        <a id="back-to-center-link" href="#"
            class="inline-flex items-center gap-1.5 text-sm font-medium text-gray-600 border border-gray-300 rounded-lg px-3 py-1.5 hover:bg-gray-50 hover:border-gray-400 hover:text-brand transition-colors">
            <i class="ti ti-arrow-left" style="font-size: 15px;" aria-hidden="true"></i>
            <span id="back-to-center-label">Back to center info</span>
        </a>
        <a href="/evacuation-centers" class="inline-flex items-center gap-1 text-xs text-gray-400 hover:text-brand">
            <i class="ti ti-building" style="font-size: 13px;" aria-hidden="true"></i> All evacuation centers
        </a>
    </div>

    <div id="content-wrap" class="hidden">
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
            <div class="flex items-center justify-between mb-1">
                <p class="text-sm font-semibold text-gray-800">EC Information Board</p>
                <span class="flex items-center gap-1 text-xs text-gray-400 shrink-0"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Live</span>
            </div>
            <p class="text-xs text-gray-500 mb-3">Add evacuees here first -- register full details later, as time allows.</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 border-t border-gray-100 pt-3">
                <span><span class="text-gray-400">Barangay:</span> <span id="ecb-barangay" class="text-gray-700 font-medium"></span></span>
                <span><span class="text-gray-400">Evacuation center:</span> <span id="ecb-center-name" class="text-gray-700 font-medium"></span></span>
                <span class="flex items-center gap-1.5">
                    <span class="text-gray-400">Event:</span>
                    <select id="ecb-event-select" class="border border-gray-300 rounded-lg px-2 py-1 text-xs"></select>
                </span>
            </div>
        </div>

        {{-- Key numbers as scannable stat cards (matching the Dashboard/
            Evacuation Centers list pages' own stat-card pattern) instead of
            a plain cumulative-vs-now table -- these are the figures staff
            scan for first. --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-2">
            <div class="bg-white rounded-xl p-4" style="border-left: 4px solid #93C5FD;">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Families cumulative</p>
                <p id="ecb-families-cumulative" class="text-2xl font-bold text-gray-800">0</p>
            </div>
            <div class="bg-white rounded-xl p-4" style="border-left: 4px solid #3B82F6;">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Families now</p>
                <p id="ecb-families-now" class="text-2xl font-bold text-gray-800">0</p>
            </div>
            <div class="bg-white rounded-xl p-4" style="border-left: 4px solid #FDBA74;">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Persons cumulative</p>
                <p id="ecb-persons-cumulative" class="text-2xl font-bold text-gray-800">0</p>
            </div>
            <div class="bg-white rounded-xl p-4" style="border-left: 4px solid #F97316;">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Persons now</p>
                <p id="ecb-persons-now" class="text-2xl font-bold text-gray-800">0</p>
            </div>
        </div>
        <p class="text-xs text-gray-400 mb-6">"Now" reflects current records exactly; "Cumulative" is a running total from every evacuee added here and never drops when someone is later removed.</p>

        {{-- Two-column: the Add Evacuee form is the primary task on this
            page, so it gets the wider main column with generous padding;
            the age/sex breakdown sits beside it as a compact reference so
            staff can check current counts without scrolling away from the
            form. Below lg, stacks to a single column (form first). --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-6">
                <div class="mb-5">
                    <p class="text-base font-semibold text-gray-800">Add evacuee</p>
                    <p class="text-xs text-gray-500 mt-0.5">Creates a real record right away -- fill in their full name and birthdate later via "Add details" on the Evacuees page.</p>
                </div>

                <div id="add-evacuee-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

                <form id="add-evacuee-form" class="flex flex-col gap-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Age bracket</label>
                            <select id="ae-age-bracket" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm"></select>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 block mb-1">Sex</label>
                            <select id="ae-sex" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-1 flex w-fit text-sm mb-4">
                            <button type="button" id="ae-mode-existing-btn" class="ae-mode-btn px-3 py-1.5 rounded-lg font-medium bg-brand text-white" data-mode="existing">
                                Existing household
                            </button>
                            <button type="button" id="ae-mode-new-btn" class="ae-mode-btn px-3 py-1.5 rounded-lg font-medium text-gray-500" data-mode="new">
                                New household
                            </button>
                        </div>

                        <div id="ae-existing-section">
                            <label class="text-xs text-gray-500 block mb-1">Household already at this center</label>
                            <select id="ae-family-id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                                <option value="">No households registered here yet</option>
                            </select>
                        </div>

                        <div id="ae-new-section" class="hidden grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Barangay</label>
                                <select id="ae-barangay-id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm"></select>
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Head of family name</label>
                                <input type="text" id="ae-family-name" placeholder="e.g. Juan Dela Cruz" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
                        <button type="submit" id="add-evacuee-submit-btn"
                            class="bg-brand hover:bg-brand-dark disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg px-5 py-2.5">
                            + Add evacuee
                        </button>
                        <span id="add-evacuee-success-msg" class="hidden text-xs text-green-600 font-medium">&check; Added -- form's ready for the next one.</span>
                        <span id="add-evacuee-disabled-note" class="hidden text-xs text-gray-400">No active disaster event -- can't add evacuees right now.</span>
                    </div>
                </form>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 lg:self-start">
                <p class="text-sm font-semibold text-gray-700 mb-3">Age &amp; sex breakdown</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr class="text-xs text-gray-500">
                                <th class="text-left font-medium py-1">Age bracket</th>
                                <th class="text-left font-medium py-1 w-12">Male</th>
                                <th class="text-left font-medium py-1 w-12">Female</th>
                                <th class="text-left font-medium py-1 w-12">Total</th>
                            </tr>
                        </thead>
                        <tbody id="age-sex-rows"></tbody>
                        {{-- Grand total row: the number people scan for first,
                            so it needs more visual weight than a data row --
                            bolder text, shaded background, a heavier top
                            border to separate it from the detail rows above. --}}
                        <tfoot>
                            <tr class="border-t-2 border-gray-300 bg-gray-50 font-bold text-gray-900">
                                <td class="py-2">Total</td>
                                <td class="py-2" id="age-total-male">0</td>
                                <td class="py-2" id="age-total-female">0</td>
                                <td class="py-2" id="age-total-all">0</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Only these two remain manually saved -- see this page's own
            notes and EvacuationCenterController::updateQuickCount()'s
            docblock for why sectoral flags stay a reported aggregate
            instead of also going live. Kept as its own full-width section
            below the form+breakdown row, rather than squeezed into the
            narrow sidebar, since it's a longer secondary form. --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-4">
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

        <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>
    </div>
@endsection

@section('scripts')
<script>
    // URL is /ec-board/{id} -- centerId is simply the last segment.
    const centerId = window.location.pathname.split('/').pop();
    let centerBarangayId = null;

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

        // Amber for "needs attention" once reported it's just informational
        // (neutral gray) -- the same pending/attention convention already
        // used for the "Not yet classified" age-bracket row above and the
        // Evacuees page's own pending-details badge, reused here rather
        // than left as plain gray text regardless of state.
        const updatedMeta = document.getElementById('ecb-updated-meta');
        if (qc.updated_at) {
            updatedMeta.className = 'text-xs text-gray-400 mb-3';
            updatedMeta.textContent = `Beneficiaries/sectoral figures last saved ${new Date(qc.updated_at).toLocaleString()}${qc.updated_by_name ? ` by ${qc.updated_by_name}` : ''}`;
        } else {
            updatedMeta.className = 'inline-flex items-center gap-1.5 text-xs text-amber-700 bg-amber-50 px-2.5 py-1.5 rounded-lg mb-3';
            updatedMeta.innerHTML = '<i class="ti ti-alert-circle" style="font-size: 14px;" aria-hidden="true"></i> Beneficiaries/sectoral figures not yet reported for this event.';
        }
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

    // Populates the "Add evacuee" form's own dropdowns (households already
    // at this center, and barangays for a brand-new household) -- was
    // previously only loaded when the old modal opened; now the form is
    // always on the page, so this runs on load and again on every event
    // change / successful add (a new household just added should appear in
    // the "existing household" list for the next person added to it).
    async function loadAddEvacueeFormData(eventId) {
        if (! eventId) {
            return;
        }
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
            // Dropdowns just stay at their previous options if this fails --
            // the rest of the form is still usable.
        }
    }

    renderSectoralRows();
    document.getElementById('ae-age-bracket').innerHTML =
        ageBrackets.map(([key, label]) => `<option value="${key}">${label}</option>`).join('');

    (async () => {
        try {
            const [result, eventsResult] = await Promise.all([
                Api.get(`/evacuation-centers/${centerId}`),
                Api.get('/evacuation-events'),
            ]);
            const c = result.data;
            centerBarangayId = c.barangay?.id ?? null;

            // Context-aware "Back": if we arrived via the EC Board section's
            // own barangay -> centers-in-barangay flow (see
            // ec-board/index.blade.php's per-center link), Back should
            // return to that SAME barangay's centers list, not jump over to
            // the separate basic-info/management page. Any other arrival
            // path (no recognized query params) falls back to the sensible
            // default: this center's own basic-info page.
            const params = new URLSearchParams(window.location.search);
            const fromBarangayId = params.get('barangay');
            const backLink = document.getElementById('back-to-center-link');
            if (params.get('from') === 'ec-board' && fromBarangayId) {
                backLink.href = `/ec-board?barangay=${fromBarangayId}`;
                document.getElementById('back-to-center-label').textContent = `Back to ${c.barangay?.name ?? 'barangay'}'s centers`;
            } else {
                backLink.href = `/evacuation-centers/${centerId}`;
            }

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

            const submitBtn = document.getElementById('add-evacuee-submit-btn');
            submitBtn.disabled = ! openEvents.length;
            document.getElementById('add-evacuee-disabled-note').classList.toggle('hidden', !! openEvents.length);

            if (openEvents.length) {
                await Promise.all([loadEcBoard(eventSelect.value), loadAddEvacueeFormData(eventSelect.value)]);
            }

            document.getElementById('content-wrap').classList.remove('hidden');
        } catch (error) {
            showFormErrors(error);
        }
    })();

    document.getElementById('ecb-event-select').addEventListener('change', (e) => {
        loadEcBoard(e.target.value);
        loadAddEvacueeFormData(e.target.value);
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

    // --- Add Evacuee form (inline -- see redesign notes above) --------------

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
            document.getElementById('ae-new-section').classList.toggle('grid', aeMode === 'new');
        });
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

        const errorBox = document.getElementById('add-evacuee-errors');
        errorBox.classList.add('hidden');

        const button = document.getElementById('add-evacuee-submit-btn');
        button.disabled = true;
        button.textContent = 'Adding...';

        try {
            await Api.request(`/evacuation-centers/${centerId}/evacuees`, {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            // Reset for the next person instead of navigating away --
            // "Add Evacuee" is the fast-entry path, so staff adding several
            // people in a row shouldn't have to re-open anything between
            // each one. Household mode/selection intentionally carries over
            // (the next arrival is often from the same family).
            document.getElementById('ae-family-name').value = '';
            const successMsg = document.getElementById('add-evacuee-success-msg');
            successMsg.classList.remove('hidden');
            setTimeout(() => successMsg.classList.add('hidden'), 2500);

            await Promise.all([loadEcBoard(eventId), loadAddEvacueeFormData(eventId)]);
        } catch (error) {
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            errorBox.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            errorBox.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = '+ Add evacuee';
        }
    });
</script>
@endsection
