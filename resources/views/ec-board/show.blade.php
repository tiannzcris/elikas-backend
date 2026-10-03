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
        /* Side by side (xl): narrower number columns leave each table's
           label column room for its longest label on one line. */
        @media (min-width: 1280px) { .ecb-table col.ecb-num { width: 4.5rem; } }
        .ecb-table th, .ecb-table td { padding: 0.4rem 1rem; }
        .ecb-table th:not(:first-child), .ecb-table td:not(:first-child) { text-align: right; }
        @media (max-width: 639px) { .ecb-table th, .ecb-table td { padding: 0.4rem 0.625rem; } }
        .ecb-cell-input { width: 100%; max-width: 4.5rem; text-align: right; }

        /* Add evacuee's sections: a hairline between each, a plain
           sentence-case heading, and nothing else -- the grouping itself
           is the structure. The legend is floated so it sits inside the
           section like any other heading rather than on its border. */
        .ae-section { min-width: 0; border-top: 1px solid #F3F4F6; padding-top: 0.75rem; margin-top: 0.75rem; }
        .ae-section:first-child, #add-evacuee-errors + .ae-section { border-top: 0; padding-top: 0; margin-top: 0; }
        .ae-section-title { float: left; width: 100%; margin-bottom: 0.5rem; font-size: 0.75rem; line-height: 1rem; font-weight: 600; color: #1F2937; }
        .ae-section-title + * { clear: both; }
    </style>

    {{-- Way back on the left; the board's two actions on the right. Each
        opens its form in a pop-up over the board (see openBoardModal()),
        so the board keeps the whole width and stays in view, dimmed,
        behind the form. Both stay disabled until the board has loaded
        with an open event to record against. --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        {{-- A real secondary-button treatment (border + hover fill), not a
            plain sentence -- matches this app's own existing ghost-button
            convention (e.g. modal Cancel buttons: border-gray-300 +
            hover:bg-gray-50) rather than inventing a new style. --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
            <a id="back-to-center-link" href="#"
                class="btn btn-secondary px-3 py-1.5">
                <i class="ti ti-arrow-left" style="font-size: 15px;" aria-hidden="true"></i>
                <span id="back-to-center-label">Back to center info</span>
            </a>
            <a href="/evacuation-centers" class="inline-flex items-center gap-1 text-xs text-gray-600 hover:text-brand hover:underline underline-offset-2">
                <i class="ti ti-building" style="font-size: 13px;" aria-hidden="true"></i> All evacuation centers
            </a>
        </div>
        <div data-region="board-actions" class="flex items-center gap-2 w-full sm:w-auto">
            <button type="button" id="open-quick-departure-btn" class="btn btn-secondary flex-1 sm:flex-none" aria-haspopup="dialog" disabled>
                <i class="ti ti-door-exit" style="font-size: 16px;" aria-hidden="true"></i> Quick departure
            </button>
            <button type="button" id="open-add-evacuee-btn" class="btn btn-primary flex-1 sm:flex-none" aria-haspopup="dialog" disabled>
                <i class="ti ti-user-plus" style="font-size: 16px;" aria-hidden="true"></i> Add evacuee
            </button>
        </div>
    </div>

    <p id="board-actions-disabled-note" class="hidden callout callout-info mb-4">No active disaster event -- can't add evacuees or log departures right now.</p>

    <div id="content-wrap" class="hidden">
        {{-- The board itself: header block -> age & sex -> sectoral, as one
            sheet, in the official template's own order. On wide screens
            the two tables sit side by side (age & sex first, on the left)
            so neither stretches across the whole page. --}}
        <section id="ecb-board" data-region="board"
            class="card overflow-hidden mb-5">
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
                    <select id="ecb-event-select" class="input input-sm sm:min-w-[14rem]"></select>
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

            <div class="xl:grid xl:grid-cols-2 xl:items-start">
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

                {{-- Sectoral: same columns as the age & sex table, so the
                    board reads straight down on a narrower screen. Every row
                    is live -- nothing here is typed in
                    (EvacuationCenterQuickCount::liveSectoralBreakdown()):
                    per-person rows from the flags Add Evacuee records, the
                    child-/single-headed rows once per household from its
                    "New household" answers, by the head's sex. --}}
                <div class="border-t-2 border-gray-300 xl:border-t-0 xl:border-l xl:border-gray-200">
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
            </div>
        </section>

        <div id="form-errors" class="hidden callout callout-danger mb-5"></div>
    </div>

    {{-- Add Evacuee: the fast-entry path -- bracket, sex, household,
        optional flags, one button -- in a centered pop-up. The header and
        the read-back + buttons stay put while the questions between them
        scroll, so the Add button is always in reach however many
        questions are open. It stays open after each add, ready for the
        next person, with the board updating behind it. --}}
    <div id="add-evacuee-modal" class="hidden modal-backdrop">
        <div data-region="add-evacuee" class="modal max-w-lg max-h-[90dvh] flex flex-col overflow-hidden"
            role="dialog" aria-modal="true" aria-labelledby="add-evacuee-title" aria-describedby="add-evacuee-context">
            <div class="modal-header shrink-0">
                <div class="min-w-0">
                    <h2 id="add-evacuee-title" class="modal-title">Add evacuee</h2>
                    <p id="add-evacuee-context" class="text-xs text-gray-600 font-medium mt-0.5"></p>
                    <p class="text-xs text-gray-500">Name and birthdate can be added later on the Evacuees page.</p>
                </div>
                <button type="button" class="btn-icon -mr-1.5" data-close-modal aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <form id="add-evacuee-form" class="flex flex-col flex-1 min-h-0">
                <div class="flex-1 min-h-0 overflow-y-auto px-5 py-4">
                    <div id="add-evacuee-errors" class="hidden callout callout-danger mb-3"></div>

                    {{-- Four sections, in the order staff actually answer
                        them: the person, their household, the household's
                        head (only when that's someone else), then optional
                        sectoral details. Each section is about ONE subject,
                        so a field never leaves it unclear who it describes. --}}
                    <fieldset class="ae-section">
                        <legend class="ae-section-title">Who is this person?</legend>
                        <div class="grid grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] gap-2">
                            <div>
                                <label for="ae-age-bracket" class="label-sm">Age group</label>
                                <select id="ae-age-bracket" class="input px-2"></select>
                            </div>
                            <div>
                                <label for="ae-sex" class="label-sm">Sex</label>
                                <select id="ae-sex" class="input px-2">
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
                            <select id="ae-family-id" aria-label="Household already at this center" class="input px-2">
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
                            <select id="ae-barangay-id" aria-label="Barangay" class="input px-2"></select>
                            <div>
                                <label for="ae-family-name" class="label-sm">Household head's name</label>
                                <input type="text" id="ae-family-name" placeholder="e.g. Juan Dela Cruz" class="input px-2">
                            </div>
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" id="ae-head-is-self" checked> This person is the household head
                            </label>
                            <div>
                                <label for="ae-single-headed" class="label-sm">Only one household head? (single-headed)</label>
                                <select id="ae-single-headed" class="input px-2">
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
                                    <label for="ae-head-sex" class="label-sm">Head's sex</label>
                                    <select id="ae-head-sex" class="input px-2">
                                        <option value="">Not yet known</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div id="ae-head-minor-field">
                                    <label for="ae-head-is-minor" class="label-sm">Head is a minor?</label>
                                    <select id="ae-head-is-minor" class="input px-2">
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
                                <span id="ae-sectoral-count" class="hidden ml-1 badge badge-info"></span>
                            </summary>
                            <div class="px-3 pb-1 pt-1 grid grid-cols-2 sm:grid-cols-3 gap-x-3 gap-y-1.5 text-sm text-gray-700">
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
                </div>

                <div class="shrink-0 border-t border-gray-200 px-5 pt-3 pb-4">
                    {{-- Plain-language read-back of exactly what Add
                        evacuee will record, rewritten on every change --
                        the last check before saving (see
                        renderAddEvacueeSummary()). --}}
                    <div class="rounded-lg bg-brand-light/40 border border-brand-light px-3 py-2.5 mb-3" aria-live="polite">
                        <p class="text-xs font-semibold text-gray-800 mb-1">Will be recorded</p>
                        <ul id="ae-summary" class="text-xs text-gray-700 space-y-0.5"></ul>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <p id="add-evacuee-success-msg" class="hidden mr-auto text-xs text-green-700 font-medium" role="status">&check; Added -- form's ready for the next one.</p>
                        <button type="button" class="btn btn-secondary" data-close-modal>Close</button>
                        <button type="submit" id="add-evacuee-submit-btn"
                            class="btn btn-primary disabled:opacity-40">
                            + Add evacuee
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Quick Departure: the reverse of Add Evacuee, by bracket + sex +
        quantity rather than by name, for the same speed reason. Like Add
        evacuee, it stays open after each batch, ready for the next. --}}
    <div id="quick-departure-modal" class="hidden modal-backdrop">
        <div data-region="quick-departure" class="modal max-w-md max-h-[90dvh]"
            role="dialog" aria-modal="true" aria-labelledby="quick-departure-title" aria-describedby="quick-departure-context">
            <div class="modal-header">
                <div class="min-w-0">
                    <h2 id="quick-departure-title" class="modal-title">Quick departure</h2>
                    <p id="quick-departure-context" class="text-xs text-gray-600 font-medium mt-0.5"></p>
                    <p class="text-xs text-gray-500">Marks that many people currently here as departed, oldest arrivals in that group first.</p>
                </div>
                <button type="button" class="btn-icon -mr-1.5" data-close-modal aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="quick-departure-errors" class="hidden callout callout-danger mx-5 mt-4"></div>

            <form id="quick-departure-form" class="grid grid-cols-2 gap-x-3 gap-y-4 p-5">
                <div class="col-span-2">
                    <label for="qd-age-bracket" class="label">Age group</label>
                    <select id="qd-age-bracket" class="input"></select>
                </div>
                <div>
                    <label for="qd-sex" class="label">Sex</label>
                    <select id="qd-sex" class="input">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div>
                    <label for="qd-quantity" class="label">How many</label>
                    <input type="number" min="1" value="1" id="qd-quantity" class="input">
                </div>
                <div class="col-span-2">
                    <label for="qd-status" class="label">Reason</label>
                    <select id="qd-status" class="input">
                        <option value="returned_home">Returned home</option>
                        <option value="transferred">Transferred elsewhere</option>
                    </select>
                    <p class="help">To check out one specific person, open their family on the Evacuees page and use Check out on their row.</p>
                </div>

                <div class="modal-footer col-span-2 items-center">
                    <p id="quick-departure-success-msg" class="hidden mr-auto text-xs text-green-700 font-medium" role="status">&check; Marked as departed.</p>
                    <button type="button" class="btn btn-secondary" data-close-modal>Close</button>
                    <button type="submit" id="quick-departure-submit-btn"
                        class="btn btn-neutral disabled:opacity-40">
                        Mark as departed
                    </button>
                </div>
            </form>
        </div>
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

            const quickDepartureBtn = document.getElementById('quick-departure-submit-btn');
            quickDepartureBtn.disabled = ! openEvents.length;

            // The buttons that open the two forms follow the same rule, and
            // say why when there's nothing to record against.
            document.getElementById('open-add-evacuee-btn').disabled = ! openEvents.length;
            document.getElementById('open-quick-departure-btn').disabled = ! openEvents.length;
            document.getElementById('board-actions-disabled-note').classList.toggle('hidden', !! openEvents.length);

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

    // --- Pop-up forms --------------------------------------------------------

    // Add evacuee and Quick departure each open in a centered pop-up over
    // the board. The X, Close, Escape and a click on the dimmed backdrop all
    // close it. Focus moves to the form's first field on open, stays inside
    // the pop-up while it's open (Tab wraps around), and goes back to the
    // button that opened it on close. Nothing in the form is reset by
    // closing -- reopening carries on where staff left off, the same as
    // the old always-open panels.
    let openBoardModalEl = null;
    let boardModalOpener = null;

    function openBoardModal(modalId, opener, firstFieldId) {
        // Which center and event the form records against -- the board's
        // own header says so, but it's dimmed behind the pop-up.
        const eventSelect = document.getElementById('ecb-event-select');
        const context = [document.getElementById('ecb-center-name').textContent, eventSelect.selectedOptions[0]?.textContent]
            .filter(Boolean).join(' · ');
        document.querySelectorAll('#add-evacuee-context, #quick-departure-context').forEach((el) => { el.textContent = context; });

        document.getElementById('add-evacuee-errors').classList.add('hidden');
        document.getElementById('quick-departure-errors').classList.add('hidden');

        openBoardModalEl = document.getElementById(modalId);
        boardModalOpener = opener;
        openBoardModalEl.classList.remove('hidden');
        openBoardModalEl.classList.add('flex');
        document.getElementById(firstFieldId).focus();
    }

    function closeBoardModal() {
        if (! openBoardModalEl) return;
        openBoardModalEl.classList.add('hidden');
        openBoardModalEl.classList.remove('flex');
        openBoardModalEl = null;
        boardModalOpener?.focus();
    }

    document.getElementById('open-add-evacuee-btn').addEventListener('click', (e) => {
        openBoardModal('add-evacuee-modal', e.currentTarget, 'ae-age-bracket');
    });
    document.getElementById('open-quick-departure-btn').addEventListener('click', (e) => {
        openBoardModal('quick-departure-modal', e.currentTarget, 'qd-age-bracket');
    });

    document.querySelectorAll('#add-evacuee-modal, #quick-departure-modal').forEach((modal) => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal || e.target.closest('[data-close-modal]')) closeBoardModal();
        });
    });

    document.addEventListener('keydown', (e) => {
        if (! openBoardModalEl) return;
        if (e.key === 'Escape') {
            closeBoardModal();
            return;
        }
        if (e.key !== 'Tab') return;
        const focusable = [...openBoardModalEl.querySelectorAll('button, input, select, textarea, summary, a[href]')]
            .filter((el) => ! el.disabled && el.getClientRects().length);
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (! e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    // An error comes back at the top of the form, which may be scrolled
    // out of view by then -- bring it into view.
    const showFormError = (errorBox, error) => {
        const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
        errorBox.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
        errorBox.classList.remove('hidden');
        errorBox.scrollIntoView({ block: 'nearest' });
    };

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
            showFormError(errorBox, error);
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
            showFormError(errorBox, error);
        } finally {
            button.disabled = false;
            button.textContent = 'Mark as departed';
        }
    });
</script>
@endsection
