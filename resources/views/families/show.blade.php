@extends('layouts.app')

@section('title', 'Family details')
@section('nav-families', 'active')

@section('content')
    <a id="back-link" href="/families" class="text-sm text-gray-500 hover:text-brand">&larr; Back to families</a>

    <div id="content-wrap" class="hidden mt-4">
        <h1 class="text-xl font-semibold mb-1" id="family-title">Family</h1>
        <p class="text-sm text-gray-500" id="family-subtitle"></p>
        <p class="text-sm text-gray-500 hidden" id="family-address"></p>

        <div class="flex items-center justify-between gap-3 mt-2">
            <p class="text-sm text-gray-600" id="family-center">Evacuation center: &mdash;</p>
            <button type="button" id="change-center-btn" class="text-xs text-brand hover:underline shrink-0">Change evacuation center</button>
        </div>
        {{-- Household-level status behind the EC Board's child-/single-
            headed rows (see Family::isChildHeaded()/isSingleHeaded()/
            headSex()) -- editable at any time, for households created
            before these questions existed or answered "not yet known". --}}
        <div class="flex items-center justify-between gap-3 mt-1">
            <p class="text-sm text-gray-600" id="family-household">Household: &mdash;</p>
            <button type="button" id="edit-household-btn" class="text-xs text-brand hover:underline shrink-0">Edit household</button>
        </div>
        {{-- Visible reminder for a household whose head is someone who
            hasn't been linked as a member yet -- its head figures are the
            answers given for them, not a real person's record. Amber, the
            same "needs attention" convention as "details pending". --}}
        <p id="family-head-unlinked" class="hidden mt-2 items-start gap-1.5 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            <i class="ti ti-alert-circle shrink-0 mt-px" style="font-size: 14px;" aria-hidden="true"></i>
            <span id="family-head-unlinked-text"></span>
        </p>
        <div class="mb-6"></div>

        <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100" id="members-list"></div>
    </div>

    <div id="edit-member-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800" id="member-modal-title">Edit member</p>
                    <p class="text-xs text-gray-500" id="member-modal-subtitle">Corrects this person's own details -- doesn't change their household or check-in status.</p>
                </div>
                <button type="button" id="member-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="member-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="member-form" class="flex flex-col gap-4 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input type="text" placeholder="First name" id="m-first_name" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    <input type="text" placeholder="Middle name" id="m-middle_name" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <input type="text" placeholder="Last name" id="m-last_name" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <select id="m-sex" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                        <option value="">Sex</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                    <div>
                        <label class="text-xs text-gray-500 block mb-1">Date of birth</label>
                        <input type="date" id="m-date_of_birth" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    </div>
                    <select id="m-civil_status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Civil status (optional)</option>
                        <option value="single">Single</option>
                        <option value="married">Married</option>
                        <option value="widowed">Widowed</option>
                        <option value="separated">Separated</option>
                        <option value="divorced">Divorced</option>
                    </select>
                </div>
                <div>
                    <input type="text" placeholder="09XXXXXXXXX" id="m-contact_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div class="flex flex-wrap gap-4 text-xs text-gray-600 items-center bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_pwd"> PWD</label>
                    <input type="text" placeholder="PWD type (e.g. visual, mobility)" id="m-pwd_type" class="hidden border border-gray-300 rounded-lg px-2 py-1 text-xs">
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_pregnant"> Pregnant</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_lactating"> Lactating</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_solo_parent"> Solo parent</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_indigenous_person"> Indigenous person</label>
                    <label class="flex items-center gap-1.5"><input type="checkbox" id="m-is_4ps_beneficiary"> 4Ps beneficiary</label>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="member-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="member-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
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
    <div id="checkout-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800" id="checkout-modal-title">Check out</p>
                    <p class="text-xs text-gray-500" id="checkout-modal-subtitle"></p>
                </div>
                <button type="button" id="checkout-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="checkout-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="checkout-form" class="flex flex-col gap-4 p-5">
                <div>
                    <label for="checkout-status" class="text-sm text-gray-600 block mb-1">Reason</label>
                    <select id="checkout-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="returned_home">Returned home</option>
                        <option value="transferred">Transferred elsewhere</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="checkout-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="checkout-submit-btn" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium rounded-lg px-4 py-2.5">
                        Check out
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="household-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800">Edit household</p>
                    <p class="text-xs text-gray-500">Used for the child- and single-headed family counts on the EC Board and reports. Leave anything you don't know as "Not yet known".</p>
                </div>
                <button type="button" id="household-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="household-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="household-form" class="flex flex-col gap-4 p-5">
                <div>
                    <label for="hh-head" class="text-sm text-gray-600 block mb-1">Household head</label>
                    <select id="hh-head" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                    <p id="hh-head-note" class="text-xs text-gray-500 mt-1"></p>
                </div>
                <div id="hh-unlisted-fields" class="hidden grid grid-cols-2 gap-3">
                    <div>
                        <label for="hh-head-sex" class="text-sm text-gray-600 block mb-1">Head's sex</label>
                        <select id="hh-head-sex" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Not yet known</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label for="hh-head-is-minor" class="text-sm text-gray-600 block mb-1">Head is a minor?</label>
                        <select id="hh-head-is-minor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">Not yet known</option>
                            <option value="1">Yes (under 18)</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label for="hh-single-headed" class="text-sm text-gray-600 block mb-1">Only one household head? (single-headed)</label>
                    <select id="hh-single-headed" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Not yet known</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="household-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="household-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="change-center-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800">Change evacuation center</p>
                    <p class="text-xs text-gray-500">Reassigns every currently checked-in member of this family to a new center.</p>
                </div>
                <button type="button" id="center-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="center-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="center-form" class="flex flex-col gap-4 p-5">
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Evacuation center</label>
                    <select id="center-select" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">Select center</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="center-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="center-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
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
                ? `Member ${idx + 1} <span class="text-amber-600 font-normal">— details pending</span>`
                : `${m.full_name} <span class="text-gray-500 font-normal">(${m.age} yrs, ${m.age_bracket.replace('_', ' ')})</span>`;

            return `
            <div class="p-4 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium">
                        ${nameLine}
                        ${isHead ? '<span class="text-xs px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 ml-1">Head of family</span>' : ''}
                    </p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        ${activeRecord ? `Checked in at ${activeRecord.evacuation_center?.name ?? 'unspecified location'}` : 'Checked out'}
                        ${sectoral ? ' · ' + sectoral : ''}
                    </p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs px-2 py-1 rounded-lg ${m.status === 'active' ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-500'}">
                        ${m.status.replace('_', ' ')}
                    </span>
                    <button type="button" class="edit-member-btn text-xs ${m.is_placeholder ? 'text-amber-600 font-medium' : 'text-brand'} hover:underline" data-id="${m.id}">
                        ${m.is_placeholder ? 'Add details' : 'Edit'}
                    </button>
                    ${activeRecord ? `<button type="button" class="checkout-member-btn text-xs text-gray-700 hover:underline" data-id="${m.id}">Check out</button>` : ''}
                    <button type="button" class="remove-member-btn text-xs text-red-500 hover:underline" data-id="${m.id}">Remove</button>
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
                backLink.textContent = centerId && centerId !== 'none'
                    ? `← Back to ${currentFamily.evacuation_center?.name ?? 'this center'}`
                    : `← Back to ${currentFamily.barangay?.name ?? 'this barangay'}`;
            }

            renderMembers();
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
            : 'Corrects this person\'s own details -- doesn\'t change their household or check-in status.';

        document.getElementById('member-modal-errors').classList.add('hidden');
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

            if (! confirm(`Permanently remove ${memberLabel} from this family? This cannot be undone.`)) {
                return;
            }

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
                    alert(result.message);
                    window.location.href = '/families';
                    return;
                }
                await loadFamily(); // refresh in place, no full page reload
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
            await loadFamily(); // refresh in place, no full page reload
        } catch (error) {
            const box = document.getElementById('member-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
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
        // A real, lasting change -- confirmed explicitly, like Remove.
        if (! confirm(`Check out ${memberDisplayName(member)} as ${reason}? They'll no longer count as here now.`)) {
            return;
        }

        const button = document.getElementById('checkout-submit-btn');
        button.disabled = true;
        button.textContent = 'Checking out...';

        try {
            await Api.request(`/evacuees/${checkingOutEvacueeId}/check-out`, { method: 'POST', body: JSON.stringify({ status }) });
            closeCheckoutModal();
            await loadFamily(); // refresh in place -- the row now reads "Checked out"
        } catch (error) {
            const box = document.getElementById('checkout-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Check out';
        }
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
            `Household: single-headed ${yesNoUnknown(f.is_single_headed)}, child-headed ${yesNoUnknown(f.is_child_headed)}${sex}`;

        // "Head not yet linked": no member is the head yet, so the head's
        // sex/minor figures are only the answers given for them.
        const reminder = document.getElementById('family-head-unlinked');
        const unlinked = ! f.head_of_family;
        reminder.classList.toggle('hidden', ! unlinked);
        reminder.classList.toggle('flex', unlinked);
        if (unlinked) {
            const answered = [f.head_sex, f.is_child_headed === null ? null : (f.is_child_headed ? 'a minor' : 'not a minor')].filter(Boolean);
            document.getElementById('family-head-unlinked-text').textContent =
                `Head not yet linked. ${answered.length ? `Counts use the answers given for the head (${answered.join(', ')}) ` : 'Nothing is known about the head yet '}until a member is linked. When the head is added to this household, tick "This person is the household head", or choose them here in Edit household.`;
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
            await loadFamily(); // refresh in place, no full page reload
        } catch (error) {
            const box = document.getElementById('household-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Save changes';
        }
    });

    // --- Change-evacuation-center modal -------------------------------------

    async function openCenterModal() {
        document.getElementById('center-modal-errors').classList.add('hidden');

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
            await loadFamily(); // refresh in place, no full page reload
        } catch (error) {
            const box = document.getElementById('center-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Save changes';
        }
    });
</script>
@endsection
