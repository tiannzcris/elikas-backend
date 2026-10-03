@extends('layouts.app')

@section('title', 'Family details')
@section('nav-families', 'active')

@section('content')
    <a id="back-link" href="/families" class="btn btn-secondary px-3 py-1.5">
        <i class="ti ti-arrow-left" style="font-size: 15px;" aria-hidden="true"></i>
        <span id="back-link-label">Back to families</span>
    </a>

    <div id="content-wrap" class="hidden mt-4">
        <h1 class="page-title mb-2" id="family-title">Family</h1>
        {{-- A leftover "EC Board bulk entry" household, not a real family
            (see the 2026_09_27_000001 migration): says so, plainly, before
            anything else on the page. --}}
        <div id="family-legacy-notice" class="hidden mb-3 items-start gap-2 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-3 py-2.5">
            <i class="ti ti-alert-triangle shrink-0 mt-0.5" style="font-size: 16px;" aria-hidden="true"></i>
            <span><span class="font-semibold">Legacy bulk entry -- needs manual review.</span> These people were created together from an old headcount, not registered as one family, so they're counted as a single family until someone who knows who's who moves them into their real families. No one new can be added here.</span>
        </div>
        <p class="text-sm text-gray-600" id="family-subtitle"></p>
        <p class="text-sm text-gray-600 hidden" id="family-address"></p>

        <div class="card mt-4 divide-y divide-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <p class="text-sm text-gray-700" id="family-center">Evacuation center: &mdash;</p>
            <button type="button" id="change-center-btn" class="btn btn-secondary btn-sm shrink-0">Change evacuation center</button>
        </div>
        {{-- Household-level status behind the EC Board's child-/single-
            headed rows (see Family::isChildHeaded()/isSingleHeaded()/
            headSex()) -- editable at any time, for households created
            before these questions existed or answered "not yet known". --}}
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <p class="text-sm text-gray-700" id="family-household">Family details: &mdash;</p>
            <button type="button" id="edit-household-btn" class="btn btn-secondary btn-sm shrink-0">Edit family details</button>
        </div>
        </div>
        {{-- Visible reminder for a household whose head is someone who
            hasn't been linked as a member yet -- its head figures are the
            answers given for them, not a real person's record. Amber, the
            same "needs attention" convention as "details pending". --}}
        <p id="family-head-unlinked" class="hidden mt-3 items-start gap-1.5 text-xs text-amber-900 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            <i class="ti ti-alert-circle shrink-0 mt-px" style="font-size: 14px;" aria-hidden="true"></i>
            <span id="family-head-unlinked-text"></span>
        </p>
        <div class="flex flex-wrap items-center justify-between gap-3 mt-6 mb-3">
            <h2 class="card-title">Members</h2>
            {{-- Only while at least one member is still checked in (see
                renderDepartButton()); gone once everyone has left. --}}
            <button type="button" id="depart-family-btn" class="hidden btn btn-secondary btn-sm" aria-haspopup="dialog">
                <i class="ti ti-door-exit" style="font-size: 15px;" aria-hidden="true"></i> Mark family as departed
            </button>
        </div>
        <div class="card divide-y divide-gray-100" id="members-list"></div>
    </div>

    <div id="edit-member-modal" class="hidden modal-backdrop">
        <div class="modal max-w-2xl">
            <div class="modal-header">
                <div>
                    <h2 id="member-modal-title" class="modal-title">Edit member</h2>
                    <p class="text-xs text-gray-500" id="member-modal-subtitle">Corrects this person's own details -- doesn't change their family or check-in status.</p>
                </div>
                <button type="button" id="member-modal-close" class="btn-icon -mr-1.5" aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="member-modal-errors" class="hidden callout callout-danger mx-5 mt-4"></div>

            <form id="member-form" class="flex flex-col gap-4 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="text" placeholder="First name" aria-label="First name" id="m-first_name" class="input" required>
                    <input type="text" placeholder="Middle name" aria-label="Middle name" id="m-middle_name" class="input">
                    <input type="text" placeholder="Last name" aria-label="Last name" id="m-last_name" class="input" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <select id="m-sex" aria-label="Sex" class="input self-end" required>
                        <option value="">Sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <div>
                        <label for="m-date_of_birth" class="label-sm">Date of birth</label>
                        <input type="date" id="m-date_of_birth" class="input" required>
                    </div>
                    <select id="m-civil_status" aria-label="Civil status" class="input self-end">
                        <option value="">Civil status (optional)</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="widowed">Widowed</option>
                        <option value="separated">Separated</option>
                        <option value="divorced">Divorced</option>
                    </select>
                </div>
                <div>
                    <input type="text" placeholder="09XXXXXXXXX" aria-label="Contact number" id="m-contact_number" class="input">
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm text-gray-700 items-center bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_pwd"> PWD</label>
                    <input type="text" placeholder="PWD type (e.g. visual, mobility)" id="m-pwd_type" class="hidden input input-sm w-auto text-xs">
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_pregnant"> Pregnant</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_lactating"> Lactating</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_solo_parent"> Solo parent</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_indigenous_person"> Indigenous person</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_4ps_beneficiary"> 4Ps beneficiary</label>
                </div>

                <div class="modal-footer">
                    <button type="button" id="member-modal-cancel" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" id="member-submit-btn" class="btn btn-primary">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Check out ONE named member (EvacueeController::checkOut()) --
        closes their open evacuation record, so they drop out of every
        live "Now" figure; cumulative counts never go down. The per-person
        counterpart of the EC Board's Quick Departure, same two reasons. --}}
    <div id="checkout-modal" class="hidden modal-backdrop">
        <div class="modal max-w-md">
            <div class="modal-header">
                <div>
                    <h2 id="checkout-modal-title" class="modal-title">Check out</h2>
                    <p class="text-xs text-gray-500" id="checkout-modal-subtitle"></p>
                </div>
                <button type="button" id="checkout-modal-close" class="btn-icon -mr-1.5" aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="checkout-modal-errors" class="hidden callout callout-danger mx-5 mt-4"></div>

            <form id="checkout-form" class="flex flex-col gap-4 p-5">
                <div>
                    <label for="checkout-status" class="label">Reason</label>
                    <select id="checkout-status" class="input">
                        <option value="returned_home">Returned home</option>
                        <option value="transferred">Transferred elsewhere</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" id="checkout-modal-cancel" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" id="checkout-submit-btn" class="btn btn-neutral">
                        Check out
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Mark family as departed: checks out several members at once, with
        one reason for all of them. Lists only members still checked in.
        Each ticked member goes through the same single-person Check out
        call above (POST /evacuees/{id}/check-out), one at a time, so each
        gets its own record closed and its own log entry; one that fails
        doesn't undo the others. --}}
    <div id="depart-family-modal" class="hidden modal-backdrop">
        <div class="modal max-w-md" role="dialog" aria-modal="true" aria-labelledby="depart-family-title">
            <div class="modal-header">
                <div>
                    <h2 id="depart-family-title" class="modal-title">Mark family as departed</h2>
                    <p class="text-xs text-gray-500">Checks out everyone ticked, with one reason for all of them. Members already checked out aren't listed.</p>
                </div>
                <button type="button" id="depart-family-close" class="btn-icon -mr-1.5" aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="depart-family-errors" class="hidden callout callout-danger mx-5 mt-4" role="alert"></div>

            <form id="depart-family-form" class="flex flex-col gap-4 p-5">
                <fieldset>
                    <legend class="label">Who is leaving</legend>
                    <div id="depart-family-members" class="border border-gray-200 rounded-lg divide-y divide-gray-100"></div>
                </fieldset>
                <div>
                    <label for="depart-family-status" class="label">Reason</label>
                    <select id="depart-family-status" class="input">
                        <option value="returned_home">Returned home</option>
                        <option value="transferred">Transferred elsewhere</option>
                        <option value="other">Others</option>
                    </select>
                    <p class="help">Applies to everyone ticked above.</p>
                </div>

                <div class="modal-footer">
                    <button type="button" id="depart-family-cancel" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" id="depart-family-submit-btn" class="btn btn-neutral">
                        Mark as departed
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="household-modal" class="hidden modal-backdrop">
        <div class="modal max-w-md">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title">Edit family details</h2>
                    <p class="text-xs text-gray-500">Used for the child- and single-headed family counts on the EC Board and reports. Leave anything you don't know as "Not yet known".</p>
                </div>
                <button type="button" id="household-modal-close" class="btn-icon -mr-1.5" aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="household-modal-errors" class="hidden callout callout-danger mx-5 mt-4"></div>

            <form id="household-form" class="flex flex-col gap-4 p-5">
                <div>
                    <label for="hh-head" class="label">Family head</label>
                    <select id="hh-head" class="input"></select>
                    <p id="hh-head-note" class="text-xs text-gray-500 mt-1"></p>
                </div>
                <div id="hh-unlisted-fields" class="hidden grid grid-cols-2 gap-3">
                    <div>
                        <label for="hh-head-sex" class="label">Head's sex</label>
                        <select id="hh-head-sex" class="input">
                            <option value="">Not yet known</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label for="hh-head-is-minor" class="label">Head is a minor?</label>
                        <select id="hh-head-is-minor" class="input">
                            <option value="">Not yet known</option>
                            <option value="1">Yes (under 18)</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="hh-single-headed" class="label">Only one family head? (single-headed)</label>
                    <select id="hh-single-headed" class="input">
                        <option value="">Not yet known</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" id="household-modal-cancel" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" id="household-submit-btn" class="btn btn-primary">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="change-center-modal" class="hidden modal-backdrop">
        <div class="modal max-w-md">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title">Change evacuation center</h2>
                    <p class="text-xs text-gray-500">Reassigns every currently checked-in member of this family to a new center.</p>
                </div>
                <button type="button" id="center-modal-close" class="btn-icon -mr-1.5" aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="center-modal-errors" class="hidden callout callout-danger mx-5 mt-4"></div>

            <form id="center-form" class="flex flex-col gap-4 p-5">
                <div>
                    <label class="label">Evacuation center</label>
                    <select id="center-select" required class="input">
                        <option value="">Select center</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" id="center-modal-cancel" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" id="center-submit-btn" class="btn btn-primary">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    const familyId = window.location.pathname.split('/').pop();
    let currentFamily = null;
    let editingEvacueeId = null;

    const sentenceCase = (text) => text.charAt(0).toUpperCase() + text.slice(1);
    // 'other' is the "Others" check-out reason; the rest read fine as is.
    const memberStatusLabels = { other: 'Departed (other reason)' };

    function renderMembers() {
        document.getElementById('members-list').innerHTML = currentFamily.members.map((m, idx) => {
            const activeRecord = m.evacuation_records.find(r => ! r.date_out);
            const sectoral = Object.entries(m.sectoral)
                .filter(([key, val]) => val === true)
                .map(([key]) => key.replace('is_', '').replace(/_/g, ' '))
                .join(', ');

            const isHead = m.id === currentFamily.head_of_family?.id;

            // Placeholder: registered via the quick-headcount path (or an
            // in-progress incremental fill-in) and still missing one of
            // first/last name, sex, or date of birth -- age/age_bracket are
            // both null for these (see Evacuee::getAgeBracketAttribute()),
            // so that line is skipped entirely rather than shown as blank.
            const nameLine = m.is_placeholder
                ? `Member ${idx + 1} <span class="text-amber-800 font-normal">— details pending</span>`
                : `${m.full_name} <span class="text-gray-600 font-normal">(${m.age} yrs, ${m.age_bracket.replace('_', ' ')})</span>`;

            return `
            <div class="p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-gray-900">
                        ${nameLine}
                        ${isHead ? '<span class="badge badge-info ml-1">Head of family</span>' : ''}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        ${activeRecord ? `Checked in at ${activeRecord.evacuation_center?.name ?? 'unspecified location'}` : 'Checked out'}
                        ${sectoral ? ' · ' + sectoral : ''}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <span class="badge ${m.status === 'active' ? 'badge-success' : 'badge-neutral'} mr-1">${memberStatusLabels[m.status] ?? sentenceCase(m.status.replace('_', ' '))}</span>
                    <button type="button" class="edit-member-btn btn btn-sm ${m.is_placeholder ? 'btn-attention' : 'btn-secondary'}" data-id="${m.id}">${m.is_placeholder ? 'Add details' : 'Edit'}</button>
                    ${activeRecord ? `<button type="button" class="checkout-member-btn btn btn-sm btn-secondary" data-id="${m.id}">Check out</button>` : ''}
                    <button type="button" class="remove-member-btn btn btn-sm btn-danger-secondary" data-id="${m.id}">Remove</button>
                </div>
            </div>`;
        }).join('');
    }

    // Named (not an inline IIFE) so it can be called again after a
    // successful edit, refreshing the member list in place instead of a
    // full page reload.
    async function loadFamily() {
        try {
            const result = await Api.get(`/families/${familyId}`);
            currentFamily = result.data;

            // currentFamily.name is set when this household was created via
            // the EC Board's "Add Evacuee -> New household" path (see
            // FamilyResource) -- it has no head of family on file yet to
            // fall back to otherwise.
            document.getElementById('family-title').textContent = currentFamily.name
                ? currentFamily.name
                : `${currentFamily.barangay?.name ?? 'Unknown barangay'} — ${currentFamily.head_of_family?.full_name ?? 'Family'}`;
            document.getElementById('family-subtitle').textContent =
                `${currentFamily.evacuation_event?.name ?? ''} · Registered ${new Date(currentFamily.created_at).toLocaleString()}`;

            const addressEl = document.getElementById('family-address');
            if (currentFamily.home_address) {
                addressEl.textContent = `Home address: ${currentFamily.home_address}`;
                addressEl.classList.remove('hidden');
            } else {
                addressEl.classList.add('hidden');
            }

            document.getElementById('family-center').textContent =
                `Evacuation center: ${currentFamily.evacuation_center?.name ?? 'None assigned'}`;
            renderHouseholdLine();

            // Context-aware "Back": if we arrived via the Evacuees page's
            // own barangay -> center -> family drill-down (see
            // families/index.blade.php's familyDetailReturnParams()), Back
            // should return to that SAME drilled-into barangay/center list,
            // not jump all the way over to the top-level Evacuees landing
            // view. Any other arrival path (no recognized query params)
            // falls back to the previous, simpler default. Same
            // context-aware pattern already proven on the EC Board
            // section's own back-button fix (see ec-board/show.blade.php).
            const params = new URLSearchParams(window.location.search);
            const backLink = document.getElementById('back-link');
            if (params.get('from') === 'families' && params.get('barangay')) {
                const barangayId = params.get('barangay');
                const centerId = params.get('center');
                backLink.href = `/families?barangay=${barangayId}${centerId ? `&center=${centerId}` : ''}`;
                document.getElementById('back-link-label').textContent = centerId && centerId !== 'none'
                    ? `Back to ${currentFamily.evacuation_center?.name ?? 'this center'}`
                    : `Back to ${currentFamily.barangay?.name ?? 'this barangay'}`;
            }

            renderMembers();
            renderDepartButton();
            document.getElementById('content-wrap').classList.remove('hidden');
        } catch (error) {
            showFormErrors(error);
        }
    }

    loadFamily();

    // --- Edit-member modal -------------------------------------------------

    document.getElementById('m-is_pwd').addEventListener('change', (e) => {
        const pwdTypeInput = document.getElementById('m-pwd_type');
        pwdTypeInput.classList.toggle('hidden', ! e.target.checked);
        pwdTypeInput.required = e.target.checked;
    });

    function openMemberModal(evacueeId) {
        // Looked up from the already-loaded family data rather than a
        // fresh API call -- this page just loaded the full member list.
        const member = currentFamily.members.find((m) => m.id === evacueeId);
        if (! member) return;

        editingEvacueeId = evacueeId;

        document.getElementById('member-modal-title').textContent = member.is_placeholder ? 'Add details' : 'Edit member';
        document.getElementById('member-modal-subtitle').textContent = member.is_placeholder
            ? 'Fill in this person\'s real details -- this is currently a placeholder from a quick headcount registration.'
            : 'Corrects this person\'s own details -- doesn\'t change their family or check-in status.';

        document.getElementById('member-modal-errors').classList.add('hidden');
        clearFormErrors('member-form');
        document.getElementById('member-form').reset();

        // ?? '' throughout -- a placeholder member (see is_placeholder)
        // can have first_name/last_name/sex/date_of_birth still null;
        // assigning null straight to an <input>/<select>'s value would
        // otherwise stringify to the literal text "null" instead of
        // leaving the field genuinely blank for staff to fill in.
        document.getElementById('m-first_name').value = member.first_name ?? '';
        document.getElementById('m-middle_name').value = member.middle_name ?? '';
        document.getElementById('m-last_name').value = member.last_name ?? '';
        document.getElementById('m-sex').value = member.sex ?? '';
        document.getElementById('m-date_of_birth').value = member.date_of_birth ?? '';
        document.getElementById('m-civil_status').value = member.civil_status ?? '';
        document.getElementById('m-contact_number').value = member.contact_number ?? '';

        document.getElementById('m-is_pwd').checked = member.sectoral.is_pwd;
        document.getElementById('m-pwd_type').value = member.sectoral.pwd_type ?? '';
        document.getElementById('m-pwd_type').classList.toggle('hidden', ! member.sectoral.is_pwd);
        document.getElementById('m-pwd_type').required = member.sectoral.is_pwd;
        document.getElementById('m-is_pregnant').checked = member.sectoral.is_pregnant;
        document.getElementById('m-is_lactating').checked = member.sectoral.is_lactating;
        document.getElementById('m-is_solo_parent').checked = member.sectoral.is_solo_parent;
        document.getElementById('m-is_indigenous_person').checked = member.sectoral.is_indigenous_person;
        document.getElementById('m-is_4ps_beneficiary').checked = member.sectoral.is_4ps_beneficiary;

        document.getElementById('edit-member-modal').classList.remove('hidden');
        document.getElementById('edit-member-modal').classList.add('flex');
    }

    function closeMemberModal() {
        editingEvacueeId = null;
        document.getElementById('edit-member-modal').classList.add('hidden');
        document.getElementById('edit-member-modal').classList.remove('flex');
    }

    // Delegated -- the member list is re-rendered on every loadFamily() call.
    document.getElementById('members-list').addEventListener('click', async (e) => {
        if (e.target.classList.contains('edit-member-btn')) {
            openMemberModal(Number(e.target.dataset.id));
        }

        if (e.target.classList.contains('checkout-member-btn')) {
            openCheckoutModal(Number(e.target.dataset.id));
        }

        if (e.target.classList.contains('remove-member-btn')) {
            const evacueeId = Number(e.target.dataset.id);
            const member = currentFamily.members.find((m) => m.id === evacueeId);
            if (! member) return;

            const memberLabel = member.is_placeholder
                ? `Member ${currentFamily.members.indexOf(member) + 1} (details pending)`
                : member.full_name;

            const confirmed = await Ui.confirm({
                title: 'Remove this member?',
                message: `${memberLabel} will be permanently removed from this family. This can't be undone.`,
                confirmLabel: 'Remove member',
            });
            if (! confirmed) return;

            // Determined client-side (rather than parsing the response
            // message) -- removing the head of family when they're the
            // only remaining member deletes the whole Family record too
            // (see EvacueeController::destroy()), so there's no family
            // left here to reload afterward.
            const isLastMember = currentFamily.head_of_family?.id === evacueeId && currentFamily.members.length === 1;

            const button = e.target;
            button.disabled = true;
            button.textContent = 'Removing...';

            try {
                const result = await Api.request(`/evacuees/${evacueeId}`, { method: 'DELETE' });
                if (isLastMember) {
                    Ui.toastAfterRedirect(result.message);
                    window.location.href = '/families';
                    return;
                }
                await loadFamily(); // refresh in place, no full page reload
                Ui.toast('Member removed');
            } catch (error) {
                showFormErrors(error);
                button.disabled = false;
                button.textContent = 'Remove';
            }
        }
    });

    document.getElementById('member-modal-close').addEventListener('click', closeMemberModal);
    document.getElementById('member-modal-cancel').addEventListener('click', closeMemberModal);

    document.getElementById('edit-member-modal').addEventListener('click', (e) => {
        if (e.target.id === 'edit-member-modal') closeMemberModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('edit-member-modal').classList.contains('hidden')) {
            closeMemberModal();
        }
    });

    document.getElementById('member-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            first_name: document.getElementById('m-first_name').value,
            middle_name: document.getElementById('m-middle_name').value || null,
            last_name: document.getElementById('m-last_name').value,
            sex: document.getElementById('m-sex').value,
            date_of_birth: document.getElementById('m-date_of_birth').value,
            civil_status: document.getElementById('m-civil_status').value || null,
            contact_number: document.getElementById('m-contact_number').value || null,
            is_pwd: document.getElementById('m-is_pwd').checked,
            pwd_type: document.getElementById('m-is_pwd').checked ? document.getElementById('m-pwd_type').value : null,
            is_pregnant: document.getElementById('m-is_pregnant').checked,
            is_lactating: document.getElementById('m-is_lactating').checked,
            is_solo_parent: document.getElementById('m-is_solo_parent').checked,
            is_indigenous_person: document.getElementById('m-is_indigenous_person').checked,
            is_4ps_beneficiary: document.getElementById('m-is_4ps_beneficiary').checked,
        };

        const button = document.getElementById('member-submit-btn');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            await Api.request(`/evacuees/${editingEvacueeId}`, { method: 'PATCH', body: JSON.stringify(payload) });
            closeMemberModal();
            Ui.toast('Member details saved');
            await loadFamily(); // refresh in place, no full page reload
        } catch (error) {
            showFormErrors(error, { form: 'member-form', box: 'member-modal-errors', prefix: 'm-' });
        } finally {
            button.disabled = false;
            button.textContent = 'Save changes';
        }
    });

    // --- Check-out modal ----------------------------------------------------

    let checkingOutEvacueeId = null;

    // Same label the member list uses, so the modal and the confirm dialog
    // name exactly the row that was clicked.
    const memberDisplayName = (member) => (member.is_placeholder
        ? `Member ${currentFamily.members.indexOf(member) + 1} (details pending)`
        : member.full_name.replace(/\s+/g, ' ').trim()); // no blank-middle-name gap

    function openCheckoutModal(evacueeId) {
        const member = currentFamily.members.find((m) => m.id === evacueeId);
        const activeRecord = member?.evacuation_records.find((r) => ! r.date_out);
        if (! member || ! activeRecord) return;

        checkingOutEvacueeId = evacueeId;
        document.getElementById('checkout-modal-title').textContent = `Check out ${memberDisplayName(member)}`;
        document.getElementById('checkout-modal-subtitle').textContent =
            `Closes their stay at ${activeRecord.evacuation_center?.name ?? 'their current location'}, so they no longer count as here now.`;
        document.getElementById('checkout-status').value = 'returned_home';
        document.getElementById('checkout-modal-errors').classList.add('hidden');
        clearFormErrors('checkout-form');
        document.getElementById('checkout-modal').classList.remove('hidden');
        document.getElementById('checkout-modal').classList.add('flex');
    }

    function closeCheckoutModal() {
        checkingOutEvacueeId = null;
        document.getElementById('checkout-modal').classList.add('hidden');
        document.getElementById('checkout-modal').classList.remove('flex');
    }

    document.getElementById('checkout-modal-close').addEventListener('click', closeCheckoutModal);
    document.getElementById('checkout-modal-cancel').addEventListener('click', closeCheckoutModal);

    document.getElementById('checkout-modal').addEventListener('click', (e) => {
        if (e.target.id === 'checkout-modal') closeCheckoutModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('checkout-modal').classList.contains('hidden')) {
            closeCheckoutModal();
        }
    });

    document.getElementById('checkout-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const member = currentFamily.members.find((m) => m.id === checkingOutEvacueeId);
        if (! member) return;

        const status = document.getElementById('checkout-status').value;
        const reason = status === 'transferred' ? 'transferred elsewhere' : 'returned home';
        const memberName = memberDisplayName(member);
        // A real, lasting change -- confirmed explicitly, like Remove.
        const confirmed = await Ui.confirm({
            title: `Check out ${memberName}?`,
            message: `They'll be recorded as ${reason} and no longer count as here now.`,
            confirmLabel: 'Check out',
            tone: 'neutral',
        });
        if (! confirmed) return;

        const button = document.getElementById('checkout-submit-btn');
        button.disabled = true;
        button.textContent = 'Checking out...';

        try {
            await Api.request(`/evacuees/${checkingOutEvacueeId}/check-out`, { method: 'POST', body: JSON.stringify({ status }) });
            closeCheckoutModal();
            await loadFamily(); // refresh in place -- the row now reads "Checked out"
            Ui.toast(`${memberName} checked out`);
        } catch (error) {
            showFormErrors(error, { form: 'checkout-form', box: 'checkout-modal-errors', fields: { status: 'checkout-status' } });
        } finally {
            button.disabled = false;
            button.textContent = 'Check out';
        }
    });

    // --- Mark family as departed --------------------------------------------

    const departReasons = { returned_home: 'returned home', transferred: 'transferred elsewhere', other: 'departed for another reason' };
    const checkedInMembers = () => currentFamily.members.filter((m) => m.evacuation_records.some((r) => ! r.date_out));
    const tickedDepartIds = () => [...document.querySelectorAll('#depart-family-members .depart-member:checked')].map((box) => Number(box.value));

    function renderDepartButton() {
        document.getElementById('depart-family-btn').classList.toggle('hidden', checkedInMembers().length === 0);
    }

    // Everyone still checked in, ticked unless `ticked` says otherwise
    // (after a partly failed batch: only the ones that failed).
    function renderDepartMembers(ticked = null) {
        const here = checkedInMembers();
        document.getElementById('depart-family-members').innerHTML = here.length ? here.map((m) => {
            const record = m.evacuation_records.find((r) => ! r.date_out);
            const isHead = m.id === currentFamily.head_of_family?.id;
            const checked = ticked === null || ticked.has(m.id);
            return `
                <label class="flex items-start gap-3 px-3 py-2.5 text-sm text-gray-900 cursor-pointer">
                    <input type="checkbox" class="depart-member mt-0.5" value="${m.id}" ${checked ? 'checked' : ''}>
                    <span class="min-w-0">
                        <span class="font-medium">${Ui.escapeHtml(memberDisplayName(m))}</span>
                        ${isHead ? '<span class="badge badge-info ml-1">Head of family</span>' : ''}
                        <span class="block text-xs text-gray-500">Checked in at ${Ui.escapeHtml(record.evacuation_center?.name ?? 'unspecified location')}</span>
                    </span>
                </label>`;
        }).join('') : '<p class="px-3 py-2.5 text-sm text-gray-500">No one in this family is checked in any more.</p>';
        updateDepartSubmit();
    }

    function updateDepartSubmit() {
        const count = tickedDepartIds().length;
        const button = document.getElementById('depart-family-submit-btn');
        button.disabled = count === 0;
        button.textContent = count === 0 ? 'Mark as departed' : `Mark ${count} as departed`;
    }

    function openDepartModal() {
        document.getElementById('depart-family-status').value = 'returned_home';
        document.getElementById('depart-family-errors').classList.add('hidden');
        renderDepartMembers();
        document.getElementById('depart-family-modal').classList.remove('hidden');
        document.getElementById('depart-family-modal').classList.add('flex');
        document.querySelector('#depart-family-members .depart-member')?.focus();
    }

    function closeDepartModal() {
        document.getElementById('depart-family-modal').classList.add('hidden');
        document.getElementById('depart-family-modal').classList.remove('flex');
        document.getElementById('depart-family-btn').focus();
    }

    document.getElementById('depart-family-btn').addEventListener('click', openDepartModal);
    document.getElementById('depart-family-close').addEventListener('click', closeDepartModal);
    document.getElementById('depart-family-cancel').addEventListener('click', closeDepartModal);
    document.getElementById('depart-family-members').addEventListener('change', updateDepartSubmit);
    document.getElementById('depart-family-modal').addEventListener('click', (e) => {
        if (e.target.id === 'depart-family-modal') closeDepartModal();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('depart-family-modal').classList.contains('hidden')) {
            closeDepartModal();
        }
    });

    document.getElementById('depart-family-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const ids = tickedDepartIds();
        if (! ids.length) return;
        const members = ids.map((id) => currentFamily.members.find((m) => m.id === id));
        const status = document.getElementById('depart-family-status').value;

        // Same weight as the single Check out's confirmation.
        const confirmed = await Ui.confirm({
            title: members.length === 1 ? `Mark ${memberDisplayName(members[0])} as departed?` : `Mark ${members.length} members as departed?`,
            message: `They'll be recorded as ${departReasons[status]} and no longer count as here now.`,
            confirmLabel: 'Mark as departed',
            tone: 'neutral',
        });
        if (! confirmed) return;

        const button = document.getElementById('depart-family-submit-btn');
        button.disabled = true;
        button.textContent = 'Marking...';
        document.getElementById('depart-family-errors').classList.add('hidden');

        // One at a time through the single-person endpoint: each success
        // stands on its own, and a failure is reported by name.
        const failed = [];
        for (const member of members) {
            try {
                await Api.request(`/evacuees/${member.id}/check-out`, { method: 'POST', body: JSON.stringify({ status }) });
            } catch (error) {
                const reason = error.errors ? Object.values(error.errors).flat().join(' ') : error.message;
                failed.push({ member, name: memberDisplayName(member), reason });
            }
        }

        const done = members.length - failed.length;
        await loadFamily();

        if (! failed.length) {
            closeDepartModal();
            Ui.toast(`${done} ${done === 1 ? 'member' : 'members'} marked as departed`);
            return;
        }

        // Partly done: the ones that went through stay checked out. The
        // list now shows who is still here, with only the failed ones
        // ticked, ready to try again.
        renderDepartMembers(new Set(failed.map((f) => f.member.id)));
        const box = document.getElementById('depart-family-errors');
        box.innerHTML = `<p class="font-medium">${done ? `${done} of ${members.length} marked as departed.` : 'No one was marked as departed.'} ${failed.length === 1 ? 'This one was' : 'These were'} not:</p>`
            + `<ul class="list-disc pl-5 mt-1">${failed.map((f) => `<li>${Ui.escapeHtml(f.name)}: ${Ui.escapeHtml(f.reason)}</li>`).join('')}</ul>`;
        box.classList.remove('hidden');
        if (done) Ui.toast(`${done} ${done === 1 ? 'member' : 'members'} marked as departed`);
    });

    // --- Edit-household modal -----------------------------------------------

    // null -> "Not yet known", never shown as a guessed "No".
    const yesNoUnknown = (value) => (value === null || value === undefined ? 'Not yet known' : (value ? 'Yes' : 'No'));
    const triState = (value) => (value === '' ? null : value === '1');
    const memberLabel = (m, idx) => (m.is_placeholder
        ? `Member ${idx + 1} (details pending${m.sex ? `, ${m.sex}` : ''}${m.age_bracket ? `, ${m.age_bracket.replace('_', ' ')}` : ''})`
        : m.full_name);

    function renderHouseholdLine() {
        const f = currentFamily;
        const sex = f.head_sex ? ` (${f.head_sex})` : '';
        document.getElementById('family-household').textContent =
            `Family details: single-headed ${yesNoUnknown(f.is_single_headed)}, child-headed ${yesNoUnknown(f.is_child_headed)}${sex}`;

        // Legacy bulk entry: its own notice replaces the head reminder --
        // there's no real head to link for a lumped-together headcount.
        const legacy = !! f.is_legacy_bulk_entry;
        const legacyNotice = document.getElementById('family-legacy-notice');
        legacyNotice.classList.toggle('hidden', ! legacy);
        legacyNotice.classList.toggle('flex', legacy);

        // "Head not yet linked": no member is the head yet, so the head's
        // sex/minor figures are only the answers given for them.
        const reminder = document.getElementById('family-head-unlinked');
        const unlinked = ! f.head_of_family && ! legacy;
        reminder.classList.toggle('hidden', ! unlinked);
        reminder.classList.toggle('flex', unlinked);
        if (unlinked) {
            const answered = [f.head_sex, f.is_child_headed === null ? null : (f.is_child_headed ? 'a minor' : 'not a minor')].filter(Boolean);
            document.getElementById('family-head-unlinked-text').textContent =
                `Head not yet linked. ${answered.length ? `Counts use the answers given for the head (${answered.join(', ')}) ` : 'Nothing is known about the head yet '}until a member is linked. When the head is added to this family, tick "This person is the family head", or choose them here in Edit family details.`;
        }
    }

    function updateHouseholdHeadUi() {
        const value = document.getElementById('hh-head').value;
        const unlisted = value === 'unlisted';
        document.getElementById('hh-unlisted-fields').classList.toggle('hidden', ! unlisted);
        document.getElementById('hh-head-note').textContent = unlisted
            ? 'Answer for the head directly below.'
            : 'This member\'s own sex and age are used for the head -- a real birthdate, once added, always decides "minor".';
    }

    function openHouseholdModal() {
        const f = currentFamily;
        const headId = f.head_of_family?.id ?? null;

        // A family with a head on file can only reassign it to another
        // member, never unlink it (see FamilyController::updateHousehold()).
        document.getElementById('hh-head').innerHTML =
            f.members.map((m, idx) => `<option value="${m.id}">${memberLabel(m, idx)}</option>`).join('')
            + (headId ? '' : '<option value="unlisted">Someone not listed here</option>');
        document.getElementById('hh-head').value = headId ?? 'unlisted';

        const toSelect = (value) => (value === null || value === undefined ? '' : (value ? '1' : '0'));
        document.getElementById('hh-single-headed').value = toSelect(f.is_single_headed);
        document.getElementById('hh-head-sex').value = headId ? '' : (f.head_sex ?? '');
        document.getElementById('hh-head-is-minor').value = headId ? '' : toSelect(f.is_child_headed);

        updateHouseholdHeadUi();
        document.getElementById('household-modal-errors').classList.add('hidden');
        clearFormErrors('household-form');
        document.getElementById('household-modal').classList.remove('hidden');
        document.getElementById('household-modal').classList.add('flex');
    }

    function closeHouseholdModal() {
        document.getElementById('household-modal').classList.add('hidden');
        document.getElementById('household-modal').classList.remove('flex');
    }

    document.getElementById('edit-household-btn').addEventListener('click', openHouseholdModal);
    document.getElementById('household-modal-close').addEventListener('click', closeHouseholdModal);
    document.getElementById('household-modal-cancel').addEventListener('click', closeHouseholdModal);
    document.getElementById('hh-head').addEventListener('change', updateHouseholdHeadUi);

    document.getElementById('household-modal').addEventListener('click', (e) => {
        if (e.target.id === 'household-modal') closeHouseholdModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('household-modal').classList.contains('hidden')) {
            closeHouseholdModal();
        }
    });

    document.getElementById('household-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const head = document.getElementById('hh-head').value;
        const unlisted = head === 'unlisted';
        const payload = {
            head_of_family_evacuee_id: unlisted ? null : Number(head),
            is_single_headed: triState(document.getElementById('hh-single-headed').value),
            head_sex: unlisted ? (document.getElementById('hh-head-sex').value || null) : null,
            head_is_minor: unlisted ? triState(document.getElementById('hh-head-is-minor').value) : null,
        };

        const button = document.getElementById('household-submit-btn');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            await Api.request(`/families/${familyId}/household`, { method: 'PATCH', body: JSON.stringify(payload) });
            closeHouseholdModal();
            Ui.toast('Family details saved');
            await loadFamily(); // refresh in place, no full page reload
        } catch (error) {
            showFormErrors(error, {
                form: 'household-form',
                box: 'household-modal-errors',
                fields: {
                    head_of_family_evacuee_id: 'hh-head', is_single_headed: 'hh-single-headed',
                    head_sex: 'hh-head-sex', head_is_minor: 'hh-head-is-minor',
                },
            });
        } finally {
            button.disabled = false;
            button.textContent = 'Save changes';
        }
    });

    // --- Change-evacuation-center modal -------------------------------------

    async function openCenterModal() {
        document.getElementById('center-modal-errors').classList.add('hidden');
        clearFormErrors('center-form');

        const select = document.getElementById('center-select');
        select.innerHTML = '<option value="">Select center</option>';

        try {
            const centers = await Api.get('/evacuation-centers');
            select.innerHTML += centers.data.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
            select.value = currentFamily.evacuation_center?.id ?? '';
        } catch (error) {
            closeCenterModal();
            showFormErrors(error);
            return;
        }

        document.getElementById('change-center-modal').classList.remove('hidden');
        document.getElementById('change-center-modal').classList.add('flex');
    }

    function closeCenterModal() {
        document.getElementById('change-center-modal').classList.add('hidden');
        document.getElementById('change-center-modal').classList.remove('flex');
    }

    document.getElementById('change-center-btn').addEventListener('click', openCenterModal);
    document.getElementById('center-modal-close').addEventListener('click', closeCenterModal);
    document.getElementById('center-modal-cancel').addEventListener('click', closeCenterModal);

    document.getElementById('change-center-modal').addEventListener('click', (e) => {
        if (e.target.id === 'change-center-modal') closeCenterModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('change-center-modal').classList.contains('hidden')) {
            closeCenterModal();
        }
    });

    document.getElementById('center-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = { evacuation_center_id: Number(document.getElementById('center-select').value) };

        const button = document.getElementById('center-submit-btn');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            await Api.request(`/families/${familyId}/evacuation-center`, { method: 'PATCH', body: JSON.stringify(payload) });
            closeCenterModal();
            Ui.toast('Evacuation center changed');
            await loadFamily(); // refresh in place, no full page reload
        } catch (error) {
            showFormErrors(error, { form: 'center-form', box: 'center-modal-errors', fields: { evacuation_center_id: 'center-select' } });
        } finally {
            button.disabled = false;
            button.textContent = 'Save changes';
        }
    });
</script>
@endsection
