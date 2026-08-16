@extends('layouts.app')

@section('title', 'Family details')
@section('nav-families', 'active')

@section('content')
    <a href="/families" class="text-sm text-gray-500 hover:text-brand">&larr; Back to families</a>

    <div id="content-wrap" class="hidden mt-4">
        <h1 class="text-xl font-semibold mb-1" id="family-title">Family</h1>
        <p class="text-sm text-gray-500" id="family-subtitle"></p>
        <p class="text-sm text-gray-500 mb-6 hidden" id="family-address"></p>

        <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100" id="members-list"></div>
    </div>

    <div id="edit-member-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800">Edit member</p>
                    <p class="text-xs text-gray-500">Corrects this person's own details -- doesn't change their household or check-in status.</p>
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
@endsection

@section('scripts')
<script>
    const familyId = window.location.pathname.split('/').pop();
    let currentFamily = null;
    let editingEvacueeId = null;

    function renderMembers() {
        document.getElementById('members-list').innerHTML = currentFamily.members.map((m) => {
            const activeRecord = m.evacuation_records.find(r => ! r.date_out);
            const sectoral = Object.entries(m.sectoral)
                .filter(([key, val]) => val === true)
                .map(([key]) => key.replace('is_', '').replace(/_/g, ' '))
                .join(', ');

            const isHead = m.id === currentFamily.head_of_family?.id;

            return `
            <div class="p-4 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-sm font-medium">
                        ${m.full_name} <span class="text-gray-400 font-normal">(${m.age} yrs, ${m.age_bracket.replace('_', ' ')})</span>
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
                    <button type="button" class="edit-member-btn text-xs text-brand hover:underline" data-id="${m.id}">Edit</button>
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

            document.getElementById('family-title').textContent =
                `${currentFamily.barangay?.name ?? 'Unknown barangay'} — ${currentFamily.head_of_family?.full_name ?? 'Family'}`;
            document.getElementById('family-subtitle').textContent =
                `${currentFamily.evacuation_event?.name ?? ''} · Registered ${new Date(currentFamily.created_at).toLocaleString()}`;

            const addressEl = document.getElementById('family-address');
            if (currentFamily.home_address) {
                addressEl.textContent = `Home address: ${currentFamily.home_address}`;
                addressEl.classList.remove('hidden');
            } else {
                addressEl.classList.add('hidden');
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

        document.getElementById('member-modal-errors').classList.add('hidden');
        document.getElementById('member-form').reset();

        document.getElementById('m-first_name').value = member.first_name;
        document.getElementById('m-middle_name').value = member.middle_name ?? '';
        document.getElementById('m-last_name').value = member.last_name;
        document.getElementById('m-sex').value = member.sex;
        document.getElementById('m-date_of_birth').value = member.date_of_birth;
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

        if (e.target.classList.contains('remove-member-btn')) {
            const evacueeId = Number(e.target.dataset.id);
            const member = currentFamily.members.find((m) => m.id === evacueeId);
            if (! member) return;

            if (! confirm(`Permanently remove ${member.full_name} from this family? This cannot be undone.`)) {
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
</script>
@endsection
