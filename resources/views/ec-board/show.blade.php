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

        /* Add evacuee's sections: a hairline between each, a plain
           sentence-case heading, and nothing else -- the grouping itself
           is the structure. The legend is floated so it sits inside the
           section like any other heading rather than on its border. */
        .ae-section { min-width: 0; border-top: 1px solid #F3F4F6; padding-top: 0.75rem; margin-top: 0.75rem; }
        .ae-section:first-child { border-top: 0; padding-top: 0; margin-top: 0; }
        .ae-section-title { float: left; width: 100%; margin-bottom: 0.5rem; font-size: 0.75rem; line-height: 1rem; font-weight: 600; color: #1F2937; }
        .ae-section-title + * { clear: both; }
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
            {{-- On wide screens the panel stays in view (sticky) and never
                grows taller than the space left below it on screen (set by
                fitAddEvacueePanel()) -- its fields scroll inside it
                instead, under the pinned read-back + button. --}}
            <section data-region="add-evacuee" class="bg-white border border-gray-200 rounded-xl px-4 pt-4 lg:sticky lg:top-2 lg:overflow-y-auto">
                <p class="text-sm font-semibold text-gray-800">Add evacuee</p>
                <p class="text-xs text-gray-500 mt-0.5 mb-3">Name and birthdate can be added later on the Evacuees page.</p>

                <div id="add-evacuee-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-3"></div>

                <form id="add-evacuee-form" class="flex flex-col">
                    {{-- Four sections, in the order staff actually answer
                        them: the person, their household, the household's
                        head (only when that's someone else), then optional
                        sectoral details. Each section is about ONE subject,
                        so a field never leaves it unclear who it describes. --}}
                    <fieldset class="ae-section">
                        <legend class="ae-section-title">Who is this person?</legend>
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
                        {{-- Shown whenever this person is being recorded as the
                            household head, right under the two fields that
                            then describe the head too. --}}
                        <p id="ae-head-note" class="hidden mt-2 items-start gap-1.5 text-xs text-brand-dark">
                            <i class="ti ti-user-check shrink-0 mt-px" style="font-size: 14px;" aria-hidden="true"></i>
                            <span>This person's age and sex will be used for the household head.</span>
                        </p>
                    </fieldset>

                    <fieldset class="ae-section">
                        <legend class="ae-section-title">Household</legend>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-0.5 grid grid-cols-2 text-xs mb-2">
                            <button type="button" id="ae-mode-existing-btn" class="ae-mode-btn px-2 py-1.5 rounded-md font-medium bg-brand text-white" data-mode="existing">
                                Already here
                            </button>
                            <button type="button" id="ae-mode-new-btn" class="ae-mode-btn px-2 py-1.5 rounded-md font-medium text-gray-500" data-mode="new">
                                New household
                            </button>
                        </div>

                        <div id="ae-existing-section" class="flex flex-col gap-2">
                            <select id="ae-family-id" aria-label="Household already at this center" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                <option value="">No households registered here yet</option>
                            </select>
                            {{-- Only for a household whose head was "someone
                                else" and hasn't been linked yet -- the real
                                head arriving later (see addEvacuee()). --}}
                            <label id="ae-existing-head" class="hidden items-start gap-2 text-sm text-gray-700">
                                <input type="checkbox" id="ae-existing-head-is-self" class="mt-0.5">
                                <span>This person is the household head <span class="block text-xs text-gray-500">This household has no head linked yet.</span></span>
                            </label>
                        </div>

                        {{-- Asked once per NEW household (never per person) --
                            see Family::isSingleHeaded()/isChildHeaded()/
                            headSex(). "Not yet known" is always allowed and
                            is stored as null, never guessed as "no". --}}
                        <div id="ae-new-section" class="hidden grid-cols-1 gap-2">
                            <select id="ae-barangay-id" aria-label="Barangay" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm"></select>
                            <div>
                                <label for="ae-family-name" class="text-xs text-gray-500 block mb-1">Household head's name</label>
                                <input type="text" id="ae-family-name" placeholder="e.g. Juan Dela Cruz" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" id="ae-head-is-self" checked> This person is the household head
                            </label>
                            <div>
                                <label for="ae-single-headed" class="text-xs text-gray-500 block mb-1">Only one household head? (single-headed)</label>
                                <select id="ae-single-headed" class="w-full border border-gray-300 rounded-lg px-2 py-2 text-sm">
                                    <option value="">Not yet known</option>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>

                    {{-- Only when the head is someone OTHER than the person
                        being added: set apart (dashed inset) so these two
                        answers can't be mistaken for this person's own. --}}
                    <div id="ae-head-section" role="group" aria-labelledby="ae-head-section-title" class="ae-section hidden">
                        <div class="border border-dashed border-gray-300 bg-gray-50 rounded-lg p-3">
                            <p id="ae-head-section-title" class="text-xs font-semibold text-gray-800 mb-1">About the actual household head</p>
                            <p class="text-xs text-gray-500 -mt-1 mb-2">Someone other than the person you're adding. Used until they're added and linked.</p>
                            <div class="grid grid-cols-2 gap-2">
                                <div id="ae-head-sex-field">
                                    <label for="ae-head-sex" class="text-xs text-gray-500 block mb-1">Head's sex</label>
                                    <select id="ae-head-sex" class="w-full border border-gray-300 bg-white rounded-lg px-2 py-2 text-sm">
                                        <option value="">Not yet known</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div id="ae-head-minor-field">
                                    <label for="ae-head-is-minor" class="text-xs text-gray-500 block mb-1">Head is a minor?</label>
                                    <select id="ae-head-is-minor" class="w-full border border-gray-300 bg-white rounded-lg px-2 py-2 text-sm">
                                        <option value="">Not yet known</option>
                                        <option value="1">Yes (under 18)</option>
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
                    <div class="ae-section">
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
                    </div>

                    {{-- Pinned to the bottom of whatever is scrolling (the
                        panel itself on wide screens, the page on a phone),
                        so the read-back and the Add button are always in
                        view however many questions are open above them.
                        The negative side margins let it reach the panel's edges;
                        the panel has no bottom padding, this supplies it. --}}
                    <div class="sticky bottom-0 z-10 bg-white -mx-4 mt-3 px-4 pt-3 pb-4 border-t border-gray-200 rounded-b-xl">
                        {{-- Plain-language read-back of exactly what Add
                            evacuee will record, rewritten on every change --
                            the last check before saving (see
                            renderAddEvacueeSummary()). --}}
                        <div class="rounded-lg bg-brand-light/40 border border-brand-light px-3 py-2.5 mb-2.5" aria-live="polite">
                            <p class="text-xs font-semibold text-gray-800 mb-1">Will be recorded</p>
                            <ul id="ae-summary" class="text-xs text-gray-700 space-y-0.5"></ul>
                        </div>
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
    // Households already at this center, by id (from loadAddEvacueeFormData()).
    let aeFamilies = {};

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
            const previouslySelected = document.getElementById('ae-family-id').value;
            aeFamilies = Object.fromEntries(families.map((f) => [String(f.id), f]));
            document.getElementById('ae-family-id').innerHTML = families.length
                ? families.map((f) => {
                    const label = f.name || f.head_of_family?.full_name || `Family #${f.id}`;
                    return `<option value="${f.id}">${label} (${f.member_count} member${f.member_count === 1 ? '' : 's'})</option>`;
                }).join('')
                : '<option value="">No households registered here yet</option>';

            // Keep the household staff were adding to selected after a
            // refresh (the next arrival is often from the same family).
            if (aeFamilies[previouslySelected]) document.getElementById('ae-family-id').value = previouslySelected;

            document.getElementById('ae-barangay-id').innerHTML =
                barangaysResult.data.map((b) => `<option value="${b.id}" ${b.id === centerBarangayId ? 'selected' : ''}>${b.name}</option>`).join('');

            // Which households still have no head linked decides whether
            // Already here offers "This person is the household head".
            updateHeadQuestionsUi();
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
            fitAddEvacueePanel();
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
            updateHeadQuestionsUi();
        });
    });

    // New household's head questions: when this person IS the head, their
    // own sex and age group answer "head's sex" and "head is a minor?", so
    // those two only show when the head is someone else.
    // Whether the person being added is being recorded as the head: the
    // New household tickbox, or -- for an existing household that has no
    // head linked yet -- the Already here one.
    function selectedHousehold() {
        return aeFamilies[document.getElementById('ae-family-id').value] ?? null;
    }

    function existingHeadTickOffered() {
        const household = selectedHousehold();
        return aeMode === 'existing' && household !== null && ! household.head_of_family;
    }

    function personIsHead() {
        return aeMode === 'new'
            ? document.getElementById('ae-head-is-self').checked
            : existingHeadTickOffered() && document.getElementById('ae-existing-head-is-self').checked;
    }

    function updateHeadQuestionsUi() {
        const offered = existingHeadTickOffered();
        const existingTick = document.getElementById('ae-existing-head');
        existingTick.classList.toggle('hidden', ! offered);
        existingTick.classList.toggle('flex', offered);
        if (! offered) document.getElementById('ae-existing-head-is-self').checked = false;

        const note = document.getElementById('ae-head-note');
        note.classList.toggle('hidden', ! personIsHead());
        note.classList.toggle('flex', personIsHead());

        document.getElementById('ae-head-section').classList.toggle(
            'hidden', ! (aeMode === 'new' && ! document.getElementById('ae-head-is-self').checked)
        );
        renderAddEvacueeSummary();
    }

    // The "Will be recorded" read-back: plain sentences built from exactly
    // what the form will send, so a wrong answer is visible before saving.
    function renderAddEvacueeSummary() {
        const value = (id) => document.getElementById(id).value;
        const optionText = (id) => document.getElementById(id).selectedOptions[0]?.textContent.trim() ?? '';
        const isMinorBracket = (bracket) => ['infant', 'toddler', 'preschooler', 'school_age', 'teenage'].includes(bracket);
        const minorText = (isMinor) => (isMinor === null ? 'minor or not: not yet known' : (isMinor ? 'a minor' : 'not a minor'));
        const answer = (id) => ({ '': 'not yet known', 1: 'yes', 0: 'no' })[value(id)];

        const lines = [`Adding 1 ${value('ae-sex')}, ${optionText('ae-age-bracket').toLowerCase()}.`];

        if (aeMode === 'existing') {
            const household = selectedHousehold();
            lines.push(household ? `Joins the household already here: ${optionText('ae-family-id')}.` : 'Choose the household this person belongs to.');
            if (personIsHead()) {
                lines.push(`Becomes that household's head (${minorText(isMinorBracket(value('ae-age-bracket')))}).`);
            }
        } else {
            const name = document.getElementById('ae-family-name').value.trim();
            lines.push(`New household: ${name || '(head\'s name not entered yet)'}, ${optionText('ae-barangay-id') || 'no barangay chosen'}.`);
            lines.push(personIsHead()
                ? `Head: this person (${minorText(isMinorBracket(value('ae-age-bracket')))}).`
                : `Head: someone else, ${value('ae-head-sex') || 'sex not yet known'}, ${minorText(value('ae-head-is-minor') === '' ? null : value('ae-head-is-minor') === '1')}.`);
            lines.push(`Single-headed: ${answer('ae-single-headed')}.`);
        }

        const flags = [...document.querySelectorAll('#ae-sectoral .ae-flag:checked')].map((box) => box.parentElement.textContent.trim());
        lines.push(flags.length ? `Sectoral: ${flags.join(', ')}.` : 'No sectoral details.');

        document.getElementById('ae-summary').innerHTML = lines.map((line) => `<li>${escapeHtml(line)}</li>`).join('');
    }

    const escapeHtml = (text) => text.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    document.getElementById('ae-head-is-self').addEventListener('change', updateHeadQuestionsUi);
    document.getElementById('ae-family-id').addEventListener('change', updateHeadQuestionsUi);
    document.getElementById('ae-existing-head-is-self').addEventListener('change', updateHeadQuestionsUi);
    // Anything else on the form only changes the read-back.
    document.getElementById('add-evacuee-form').addEventListener('input', renderAddEvacueeSummary);
    document.getElementById('add-evacuee-form').addEventListener('change', renderAddEvacueeSummary);
    updateHeadQuestionsUi();

    // Wide screens: cap the sticky panel at the space actually left below
    // its top edge -- lower down before the page is scrolled, taller once it
    // sticks -- so its pinned read-back + Add button never fall off the
    // bottom of the screen. A fixed CSS max-height can't do both. Phones
    // don't need it: the page scrolls and the footer sticks to the screen.
    function fitAddEvacueePanel() {
        const panel = document.querySelector('[data-region="add-evacuee"]');
        if (! window.matchMedia('(min-width: 1024px)').matches) {
            panel.style.maxHeight = '';
            return;
        }
        const main = document.querySelector('main').getBoundingClientRect();
        const gap = 8; // matches the panel's lg:top-2
        const top = Math.max(panel.getBoundingClientRect().top, main.top + gap);
        panel.style.maxHeight = `${Math.max(240, Math.min(main.bottom, window.innerHeight) - top - gap)}px`;
    }

    document.querySelector('main').addEventListener('scroll', fitAddEvacueePanel, { passive: true });
    window.addEventListener('resize', fitAddEvacueePanel);

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
            // Only ever offered for a household with no head linked yet.
            if (personIsHead()) payload.head_is_self = true;
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
            document.getElementById('ae-existing-head-is-self').checked = false;
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
