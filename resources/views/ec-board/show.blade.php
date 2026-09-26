@extends('layouts.app')

@section('title', 'EC Information Board')
@section('nav-ecboard', 'active')

@section('content')
    {{-- Shared column grid for BOTH board tables (age & sex, sectoral):
        the label column takes what's left and Male/Female/Total are fixed,
        so the numbers line up in one continuous column down the whole
        board -- the same ruled layout as the printed DSWD EC Information
        Board this page mirrors. --}}
    <style>
        .ecb-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-variant-numeric: tabular-nums; }
        .ecb-table col.ecb-num { width: 4.25rem; }
        @media (min-width: 640px) { .ecb-table col.ecb-num { width: 6rem; } }
        .ecb-table th, .ecb-table td { padding: 0.4rem 1rem; }
        .ecb-table th:not(:first-child), .ecb-table td:not(:first-child) { text-align: right; }
        @media (max-width: 639px) { .ecb-table th, .ecb-table td { padding: 0.4rem 0.625rem; } }
        .ecb-cell-input { width: 100%; max-width: 4.5rem; text-align: right; }
    </style>

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
        <a href="/evacuation-centers" class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-brand">
            <i class="ti ti-building" style="font-size: 13px;" aria-hidden="true"></i> All evacuation centers
        </a>
    </div>

    <div id="content-wrap" class="hidden">
        {{-- Board on the left, the fast-entry Add Evacuee panel on the right
            (sticky on wide screens, so it stays in reach while the board
            scrolls). Below lg the board comes first -- it names the center
            and event being added to -- and the panel follows it. --}}
        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_21rem] gap-5 items-start mb-5">

            {{-- The board itself: header block -> age & sex -> sectoral, as
                one sheet, in the official template's own order. --}}
            <section id="ecb-board" data-region="board"
                class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                <div class="px-4 sm:px-5 pt-4 pb-3 flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="flex items-center gap-2 text-xs font-medium text-gray-500">
                            EC Information Board
                            <span class="inline-flex items-center gap-1 text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Live</span>
                        </p>
                        <h1 id="ecb-center-name" class="text-lg font-semibold text-gray-900 leading-snug mt-0.5"></h1>
                        <p class="text-sm text-gray-500">Barangay <span id="ecb-barangay" class="text-gray-700 font-medium"></span></p>
                    </div>
                    <label class="flex flex-col gap-1 text-xs text-gray-500 w-full sm:w-auto">
                        Event
                        <select id="ecb-event-select" class="border border-gray-300 rounded-lg px-2.5 py-1.5 text-sm text-gray-800 sm:min-w-[14rem]"></select>
                    </label>
                </div>

                {{-- Header figures, ruled like the template's own header
                    rows. Matches its row order too: the 4Ps family count
                    sits here WITH Families/Persons, before the Age & Sex
                    table -- not down with the Sectoral table, whose own
                    "4Ps beneficiary" row is a different, per-person-sex
                    figure. Live, like the rest of this strip (families here
                    now marked 4Ps -- see liveFourPsFamiliesNow()). --}}
                <div class="grid grid-cols-2 sm:grid-cols-[1fr_1fr_1fr_1fr_auto] border-y border-gray-200 divide-gray-200 text-sm"
                    title="&quot;Now&quot; reflects current records exactly; &quot;Cumulative&quot; is a running total from every evacuee added here and never drops when someone is later removed.">
                    <div class="px-4 sm:px-5 py-2.5 border-r border-b sm:border-b-0 border-gray-200">
                        <p class="text-xs text-gray-500">Families, cumulative</p>
                        <p id="ecb-families-cumulative" class="text-xl font-semibold text-gray-600 tabular-nums">0</p>
                    </div>
                    <div class="px-4 sm:px-5 py-2.5 border-b sm:border-b-0 sm:border-r border-gray-200">
                        <p class="text-xs text-gray-500">Families now</p>
                        <p id="ecb-families-now" class="text-xl font-bold text-gray-900 tabular-nums">0</p>
                    </div>
                    <div class="px-4 sm:px-5 py-2.5 border-r border-b sm:border-b-0 border-gray-200">
                        <p class="text-xs text-gray-500">Persons, cumulative</p>
                        <p id="ecb-persons-cumulative" class="text-xl font-semibold text-gray-600 tabular-nums">0</p>
                    </div>
                    <div class="px-4 sm:px-5 py-2.5 border-b sm:border-b-0 sm:border-r border-gray-200">
                        <p class="text-xs text-gray-500">Persons now</p>
                        <p id="ecb-persons-now" class="text-xl font-bold text-gray-900 tabular-nums">0</p>
                    </div>
                    <div class="col-span-2 sm:col-span-1 px-4 sm:px-5 py-2.5 flex sm:block items-center justify-between gap-3"
                        title="Families here now that are marked as 4Ps beneficiaries.">
                        <p class="text-xs text-gray-500">4Ps families</p>
                        <p id="ecb-beneficiaries-4ps" class="text-xl font-semibold text-gray-900 tabular-nums">0</p>
                    </div>
                </div>

                {{-- Age & sex: all live, computed server-side from real
                    records (EvacuationCenterQuickCount::liveAgeSexBreakdown). --}}
                <table class="ecb-table text-sm">
                    <colgroup><col><col class="ecb-num"><col class="ecb-num"><col class="ecb-num"></colgroup>
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs text-gray-600">
                            <th class="text-left font-semibold">Age group</th>
                            <th class="font-medium">Male</th>
                            <th class="font-medium">Female</th>
                            <th class="font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody id="age-sex-rows"></tbody>
                    {{-- Grand total row: the number people scan for first,
                        so it gets more weight than a data row. --}}
                    <tfoot>
                        <tr class="border-t-2 border-gray-300 font-bold text-gray-900">
                            <td>Total</td>
                            <td id="age-total-male">0</td>
                            <td id="age-total-female">0</td>
                            <td id="age-total-all">0</td>
                        </tr>
                    </tfoot>
                </table>

                {{-- Sectoral: same columns as the table above, so the board
                    reads straight down. Every row is live -- nothing here is
                    typed in (EvacuationCenterQuickCount::liveSectoralBreakdown()):
                    per-person rows from the flags Add Evacuee records, the
                    child-/single-headed rows once per household from its
                    "New household" answers, by the head's sex. --}}
                <div class="border-t-2 border-gray-300">
                    <table class="ecb-table text-sm">
                        <colgroup><col><col class="ecb-num"><col class="ecb-num"><col class="ecb-num"></colgroup>
                        <thead>
                            <tr class="border-b border-gray-200 bg-gray-50 text-xs text-gray-600">
                                <th class="text-left font-semibold">Sectoral group</th>
                                <th class="font-medium">Male</th>
                                <th class="font-medium">Female</th>
                                <th class="font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody id="sectoral-rows"></tbody>
                    </table>

                    <p class="px-4 sm:px-5 py-3 border-t border-gray-200 text-xs text-gray-600">
                        Counted from each evacuee's sectoral details. Child- and single-headed families are counted once per household, by the head's sex, from the answers given when the household was added.
                    </p>
                </div>
            </section>

            {{-- Add Evacuee: the fast-entry path, kept to a narrow panel --
                bracket, sex, household, optional flags, one button. --}}
            <section data-region="add-evacuee" class="bg-white border border-gray-200 rounded-xl p-4 lg:sticky lg:top-2">
                <p class="text-sm font-semibold text-gray-800">Add evacuee</p>
                <p class="text-xs text-gray-500 mt-0.5 mb-3">Name and birthdate can be added later on the Evacuees page.</p>

                <div id="add-evacuee-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-3"></div>

                <form id="add-evacuee-form" class="flex flex-col gap-3">
                    <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] gap-2">
                        <div>
                            <label for="ae-age-bracket" class="text-xs text-gray-500 block mb-1">Age group</label>
                            <select id="ae-age-bracket" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></select>
                        </div>
                        <div>
                            <label for="ae-sex" class="text-xs text-gray-500 block mb-1">Sex</label>
                            <select id="ae-sex" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <p class="text-xs text-gray-500 mb-1">Household</p>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-0.5 grid grid-cols-2 text-xs mb-2">
                            <button type="button" id="ae-mode-existing-btn" class="ae-mode-btn px-2 py-1.5 rounded-md font-medium bg-brand text-white" data-mode="existing">
                                Already here
                            </button>
                            <button type="button" id="ae-mode-new-btn" class="ae-mode-btn px-2 py-1.5 rounded-md font-medium text-gray-500" data-mode="new">
                                New household
                            </button>
                        </div>

                        <div id="ae-existing-section">
                            <select id="ae-family-id" aria-label="Household already at this center" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="">No households registered here yet</option>
                            </select>
                        </div>

                        {{-- Asked once per NEW household (never per person) --
                            see Family::isSingleHeaded()/isChildHeaded()/
                            headSex(). "Not yet known" is always allowed and
                            is stored as null, never guessed as "no". When
                            this person IS the head, their own sex and age
                            group answer the head questions, so those hide. --}}
                        <div id="ae-new-section" class="hidden grid-cols-1 gap-2">
                            <select id="ae-barangay-id" aria-label="Barangay" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></select>
                            <input type="text" id="ae-family-name" aria-label="Head of family name" placeholder="Head of family, e.g. Juan Dela Cruz" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" id="ae-head-is-self" checked> This person is the household head
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <div id="ae-head-sex-field" class="hidden">
                                    <label for="ae-head-sex" class="text-xs text-gray-500 block mb-1">Head's sex</label>
                                    <select id="ae-head-sex" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                        <option value="">Not yet known</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div id="ae-head-minor-field" class="hidden">
                                    <label for="ae-head-is-minor" class="text-xs text-gray-500 block mb-1">Head is a minor?</label>
                                    <select id="ae-head-is-minor" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                        <option value="">Not yet known</option>
                                        <option value="1">Yes (under 18)</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <label for="ae-single-headed" class="text-xs text-gray-500 block mb-1">Only one household head? (single-headed)</label>
                                    <select id="ae-single-headed" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                        <option value="">Not yet known</option>
                                        <option value="1">Yes</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Optional sectoral flags for THIS one person, collapsed
                        by default so the common case (most evacuees are none
                        of these) stays one click. Unticked means "not
                        recorded", not "no" -- only ticked flags are sent, and
                        the board's live sectoral figures only count those.
                        Pregnant/lactating are hidden (and cleared) for a
                        male evacuee. --}}
                    <details id="ae-sectoral" class="border border-gray-200 rounded-lg">
                        <summary class="cursor-pointer select-none px-3 py-2 text-sm text-gray-700">
                            Sectoral details <span class="text-gray-500">(optional)</span>
                            <span id="ae-sectoral-count" class="hidden ml-1 text-xs px-2 py-0.5 rounded-lg bg-brand-light text-brand"></span>
                        </summary>
                        <div class="px-3 pb-1 pt-1 grid grid-cols-2 gap-x-3 gap-y-1.5 text-sm text-gray-700">
                            <label class="flex items-center gap-2"><input type="checkbox" class="ae-flag" value="is_pwd"> PWD</label>
                            <label class="flex items-center gap-2" data-female-only><input type="checkbox" class="ae-flag" value="is_pregnant"> Pregnant</label>
                            <label class="flex items-center gap-2" data-female-only><input type="checkbox" class="ae-flag" value="is_lactating"> Lactating</label>
                            <label class="flex items-center gap-2"><input type="checkbox" class="ae-flag" value="is_solo_parent"> Solo parent</label>
                            <label class="flex items-center gap-2"><input type="checkbox" class="ae-flag" value="is_indigenous_person"> Indigenous person</label>
                            <label class="flex items-center gap-2"><input type="checkbox" class="ae-flag" value="is_4ps_beneficiary"> 4Ps beneficiary</label>
                        </div>
                        <p class="px-3 pb-2.5 pt-1 text-xs text-gray-500">Tick only what you know. Leaving a box unticked records nothing -- it doesn't mean "no".</p>
                    </details>

                    <div>
                        <button type="submit" id="add-evacuee-submit-btn"
                            class="w-full bg-brand hover:bg-brand-dark disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg px-4 py-2.5">
                            + Add evacuee
                        </button>
                        <p id="add-evacuee-success-msg" class="hidden text-xs text-green-600 font-medium mt-1.5">&check; Added -- form's ready for the next one.</p>
                        <p id="add-evacuee-disabled-note" class="hidden text-xs text-gray-500 mt-1.5">No active disaster event -- can't add evacuees right now.</p>
                    </div>
                </form>
            </section>
        </div>

        <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-5"></div>

        {{-- Quick Departure: the reverse of Add Evacuee, by bracket + sex +
            quantity rather than by name, for the same speed reason. Used
            far less often than adding, so it sits last, as one compact row. --}}
        <section data-region="quick-departure" class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm font-semibold text-gray-800">Quick departure</p>
            <p class="text-xs text-gray-500 mt-0.5 mb-3">Marks that many people currently here as departed, oldest arrivals in that group first. To check out one person by name, use the Evacuees page.</p>

            <div id="quick-departure-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-3"></div>

            <form id="quick-departure-form" class="grid grid-cols-2 lg:grid-cols-[minmax(0,1.3fr)_minmax(0,1fr)_6rem_minmax(0,1.2fr)_auto] gap-2 items-end">
                <div class="col-span-2 lg:col-span-1">
                    <label for="qd-age-bracket" class="text-xs text-gray-500 block mb-1">Age group</label>
                    <select id="qd-age-bracket" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></select>
                </div>
                <div>
                    <label for="qd-sex" class="text-xs text-gray-500 block mb-1">Sex</label>
                    <select id="qd-sex" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label for="qd-quantity" class="text-xs text-gray-500 block mb-1">How many</label>
                    <input type="number" min="1" value="1" id="qd-quantity" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                </div>
                <div class="col-span-2 lg:col-span-1">
                    <label for="qd-status" class="text-xs text-gray-500 block mb-1">Reason</label>
                    <select id="qd-status" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                        <option value="returned_home">Returned home</option>
                        <option value="transferred">Transferred elsewhere</option>
                    </select>
                </div>
                <button type="submit" id="quick-departure-submit-btn"
                    class="col-span-2 lg:col-span-1 bg-gray-800 hover:bg-gray-900 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-medium rounded-lg px-4 py-2">
                    Mark as departed
                </button>
            </form>
            <p id="quick-departure-success-msg" class="hidden text-xs text-green-600 font-medium mt-2">&check; Marked as departed.</p>
            <p id="quick-departure-disabled-note" class="hidden text-xs text-gray-500 mt-2">No active disaster event -- can't log departures right now.</p>
        </section>
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

    // Every sectoral row is a live, read-only figure -- nothing on the
    // board is typed in (see EvacuationCenterQuickCount::liveSectoralBreakdown()).
    function renderSectoralRows(groupsByKey) {
        document.getElementById('sectoral-rows').innerHTML = sectoralGroups.map(([group, label]) => {
            const male = groupsByKey[group]?.male_count ?? 0;
            const female = groupsByKey[group]?.female_count ?? 0;

            return `
            <tr class="border-t border-gray-100" data-group="${group}">
                <td class="text-gray-700">${label}</td>
                <td>${male}</td>
                <td>${female}</td>
                <td class="font-medium text-gray-900">${male + female}</td>
            </tr>`;
        }).join('');
    }

    // qc.families_now/persons_now/age_groups/age_groups_total are all LIVE
    // (computed server-side from real records, see
    // EvacuationCenterQuickCount's live*() methods) -- this just displays
    // them, there's nothing to recompute client-side for that part.
    function renderEcBoard(qc) {
        document.getElementById('ecb-families-cumulative').textContent = qc.families_cumulative;
        document.getElementById('ecb-families-now').textContent = qc.families_now;
        document.getElementById('ecb-persons-cumulative').textContent = qc.persons_cumulative;
        document.getElementById('ecb-persons-now').textContent = qc.persons_now;
        document.getElementById('ecb-beneficiaries-4ps').textContent = qc.beneficiaries_4ps;

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
                <td class="${isUnclassified ? '' : 'text-gray-700'}">${label}</td>
                <td>${row.male_count}</td>
                <td>${row.female_count}</td>
                <td class="font-medium ${isUnclassified ? '' : 'text-gray-900'}">${total}</td>
            </tr>`;
        }).join('');
        document.getElementById('age-total-male').textContent = qc.age_groups_total.male_count;
        document.getElementById('age-total-female').textContent = qc.age_groups_total.female_count;
        document.getElementById('age-total-all').textContent = qc.age_groups_total.total_persons;

        renderSectoralRows(Object.fromEntries(qc.sectoral_groups.map((row) => [row.sectoral_group, row])));
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
    // at this center, and barangays for a brand-new household) -- runs on
    // load and again on every event change / successful add (a new
    // household just added should appear in the "already here" list for
    // the next person added to it).
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

    document.getElementById('ae-age-bracket').innerHTML =
        ageBrackets.map(([key, label]) => `<option value="${key}">${label}</option>`).join('');
    document.getElementById('qd-age-bracket').innerHTML =
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

            const quickDepartureBtn = document.getElementById('quick-departure-submit-btn');
            quickDepartureBtn.disabled = ! openEvents.length;
            document.getElementById('quick-departure-disabled-note').classList.toggle('hidden', !! openEvents.length);

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

    // --- Add Evacuee ---------------------------------------------------------

    // Optional per-person sectoral flags. The badge on the collapsed
    // header shows how many are ticked, so a closed section never hides
    // that something is set.
    function updateSectoralFlagsUi() {
        const isMale = document.getElementById('ae-sex').value === 'male';
        document.querySelectorAll('#ae-sectoral [data-female-only]').forEach((label) => {
            label.classList.toggle('hidden', isMale);
            if (isMale) label.querySelector('input').checked = false;
        });

        const ticked = document.querySelectorAll('#ae-sectoral .ae-flag:checked').length;
        const badge = document.getElementById('ae-sectoral-count');
        badge.textContent = `${ticked} ticked`;
        badge.classList.toggle('hidden', ticked === 0);
    }

    document.getElementById('ae-sex').addEventListener('change', updateSectoralFlagsUi);
    document.getElementById('ae-sectoral').addEventListener('change', updateSectoralFlagsUi);
    updateSectoralFlagsUi();

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

    // New household's head questions: when this person IS the head, their
    // own sex and age group answer "head's sex" and "head is a minor?", so
    // those two only show when the head is someone else.
    function updateHeadQuestionsUi() {
        const headIsSelf = document.getElementById('ae-head-is-self').checked;
        document.getElementById('ae-head-sex-field').classList.toggle('hidden', headIsSelf);
        document.getElementById('ae-head-minor-field').classList.toggle('hidden', headIsSelf);
    }

    document.getElementById('ae-head-is-self').addEventListener('change', updateHeadQuestionsUi);
    updateHeadQuestionsUi();

    // '' (Not yet known) -> null, never a guessed "no".
    const triState = (value) => (value === '' ? null : value === '1');

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
            payload.head_is_self = document.getElementById('ae-head-is-self').checked;
            payload.is_single_headed = triState(document.getElementById('ae-single-headed').value);
            if (! payload.head_is_self) {
                payload.head_sex = document.getElementById('ae-head-sex').value || null;
                payload.head_is_minor = triState(document.getElementById('ae-head-is-minor').value);
            }
        }

        // Only ticked flags are sent (as true); anything unticked is simply
        // left out, which the server stores as "not recorded" (null).
        document.querySelectorAll('#ae-sectoral .ae-flag:checked').forEach((box) => {
            payload[box.value] = true;
        });

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
            // The household answers belong to the household just created,
            // so they reset too, ready for the next new one.
            document.getElementById('ae-head-is-self').checked = true;
            ['ae-head-sex', 'ae-head-is-minor', 'ae-single-headed'].forEach((id) => { document.getElementById(id).value = ''; });
            updateHeadQuestionsUi();
            // Sectoral flags describe the person just added, so they reset
            // for the next one (unlike household mode, which carries over).
            document.querySelectorAll('#ae-sectoral .ae-flag').forEach((box) => { box.checked = false; });
            updateSectoralFlagsUi();
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

    // --- Quick Departure -----------------------------------------------

    document.getElementById('quick-departure-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const eventId = document.getElementById('ecb-event-select').value;
        const payload = {
            evacuation_event_id: Number(eventId),
            age_bracket: document.getElementById('qd-age-bracket').value,
            sex: document.getElementById('qd-sex').value,
            quantity: Number(document.getElementById('qd-quantity').value) || 0,
            status: document.getElementById('qd-status').value,
        };

        const errorBox = document.getElementById('quick-departure-errors');
        errorBox.classList.add('hidden');

        const button = document.getElementById('quick-departure-submit-btn');
        button.disabled = true;
        button.textContent = 'Marking...';

        try {
            await Api.request(`/evacuation-centers/${centerId}/quick-departure`, {
                method: 'POST',
                body: JSON.stringify(payload),
            });

            // Reset just the quantity for the next batch -- bracket/sex/
            // reason intentionally carry over, same "ready for the next
            // one" convenience as Add Evacuee above.
            document.getElementById('qd-quantity').value = 1;
            const successMsg = document.getElementById('quick-departure-success-msg');
            successMsg.classList.remove('hidden');
            setTimeout(() => successMsg.classList.add('hidden'), 2500);

            // Refreshes the live "Now" figures and age/sex breakdown --
            // this action never touches cumulative, so nothing else on the
            // page needs updating.
            await loadEcBoard(eventId);
        } catch (error) {
            // The "only N available" block from quickDeparture() is a
            // plain top-level message, not a per-field errors object --
            // same fallback families/index.blade.php's own forms use.
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            errorBox.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            errorBox.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Mark as departed';
        }
    });
</script>
@endsection
