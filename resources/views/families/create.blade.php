@extends('layouts.app')

@section('title', 'Register a family')
@section('nav-families', 'active')

@section('content')
    <div class="page-header">
        <div class="min-w-0">
            <h1 class="page-title">Register a family</h1>
            <p class="page-subtitle">Register every member of an arriving household in one step.</p>
        </div>
    </div>

    <div id="form-errors" class="hidden callout callout-danger mb-4"></div>

    <form id="register-form" class="flex flex-col gap-6 max-w-3xl">
        <div class="card p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="label">Barangay</label>
                <select id="barangay_id" required class="input"></select>
                <label class="label mt-3">Street/Sitio Address (optional)</label>
                <input type="text" id="home_address" placeholder="e.g. Purok 3, Sitio Mabuhay" class="input">
            </div>
            <div>
                <label class="label">Disaster event</label>
                <select id="evacuation_event_id" required class="input"></select>
            </div>
            <div>
                <label class="label">Displacement type</label>
                <select id="displacement_type" required class="input">
                    <option value="inside_center">Inside an evacuation center</option>
                    <option value="outside_center">Outside (evacuated to relatives/other location)</option>
                </select>
            </div>
            <div id="center-field">
                <label class="label">Evacuation center</label>
                <select id="evacuation_center_id" class="input"></select>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-700 sm:col-span-2">
                <input type="checkbox" id="is_4ps_beneficiary"> Household is a 4Ps beneficiary
            </label>
        </div>

        {{-- Quick headcount registration was removed from here -- the EC
            Information Board (evacuation center detail page) now serves
            that purpose: its age/sex breakdown generates real placeholder
            evacuees directly, so staff enter a fast headcount there
            instead of a second, separate place. Full-detail registration
            is the only path here. --}}
        <div id="full-mode-section">
            <div class="flex items-center justify-between mb-3">
                <h2 class="card-title">Household members</h2>
                <button type="button" id="add-member-btn" class="link text-sm">+ Add another member</button>
            </div>
            <div id="members-container" class="flex flex-col gap-4"></div>
        </div>

        <button type="submit" id="submit-btn"
            class="btn btn-primary w-fit">
            Register family
        </button>
    </form>
@endsection

@section('scripts')
<script>
    let memberCount = 0;

    function memberRowHtml(index) {
        return `
        <div class="member-row card p-4" data-index="${index}">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-900">Member ${index + 1}</p>
                ${index > 0 ? `<button type="button" class="remove-member btn btn-sm btn-danger-secondary">Remove</button>` : ''}
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" placeholder="First name" class="m-first_name input" required>
                <input type="text" placeholder="Middle name" class="m-middle_name input">
                <input type="text" placeholder="Last name" class="m-last_name input" required>
                <select class="m-sex input" required>
                    <option value="">Sex</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                <input type="date" class="m-date_of_birth input" required>
                <div>
                    <input type="text" placeholder="09XXXXXXXXX" class="m-contact_number input" required>
                    <button type="button" class="same-as-head-btn link text-xs mt-1">Same as head of family</button>
                </div>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-2 mt-3 text-sm text-gray-700 items-center">
                <label class="flex items-center gap-1.5"><input type="radio" name="head-${index}" class="m-is_head_of_family"> Head of family</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_pwd"> PWD</label>
                <input type="text" placeholder="PWD type (e.g. visual, mobility)" class="m-pwd_type hidden input input-sm w-auto text-xs">
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_pregnant"> Pregnant</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_lactating"> Lactating</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_solo_parent"> Solo parent</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_indigenous_person"> Indigenous person</label>
            </div>
            <p class="text-xs text-gray-500 mt-2">Contact number is required for every member -- if someone doesn't have their own phone (e.g. a child or elderly member), use "Same as head of family" to reuse the household's number.</p>
        </div>`;
    }

    function addMemberRow() {
        document.getElementById('members-container').insertAdjacentHTML('beforeend', memberRowHtml(memberCount));
        memberCount++;
    }

    document.getElementById('add-member-btn').addEventListener('click', addMemberRow);
    addMemberRow(); // start with one member row (the head of family)

    document.getElementById('members-container').addEventListener('click', (e) => {
        if (e.target.classList.contains('remove-member')) {
            e.target.closest('.member-row').remove();
        }

        // Copies the head of family's own contact number into whichever
        // row's button was clicked -- contact_number is required for every
        // member now, and not every member (a child, an elderly relative)
        // realistically has their own phone.
        if (e.target.classList.contains('same-as-head-btn')) {
            const headRow = document.querySelector('#members-container .m-is_head_of_family:checked')?.closest('.member-row');
            if (! headRow) return;
            const headNumber = headRow.querySelector('.m-contact_number').value;
            e.target.closest('.member-row').querySelector('.m-contact_number').value = headNumber;
        }
    });

    // The backend requires pwd_type whenever is_pwd is checked (see
    // RegisterFamilyRequest's required_if rule) -- this reveals the matching
    // input instead of letting the user hit a confusing validation error
    // with no visible field to fix.
    document.getElementById('members-container').addEventListener('change', (e) => {
        if (e.target.classList.contains('m-is_pwd')) {
            const pwdTypeInput = e.target.closest('.member-row').querySelector('.m-pwd_type');
            pwdTypeInput.classList.toggle('hidden', ! e.target.checked);
            pwdTypeInput.required = e.target.checked;
        }

        // The head of family's own row doesn't need a "same as head"
        // shortcut for its own number -- shown on every other row instead.
        if (e.target.classList.contains('m-is_head_of_family')) {
            document.querySelectorAll('#members-container .same-as-head-btn').forEach((btn) => {
                btn.classList.remove('hidden');
            });
            e.target.closest('.member-row').querySelector('.same-as-head-btn').classList.add('hidden');
        }
    });

    document.getElementById('displacement_type').addEventListener('change', (e) => {
        document.getElementById('center-field').style.display = e.target.value === 'inside_center' ? 'block' : 'none';
    });

    // Populate dropdowns from the lookup endpoints added alongside this form.
    // Barangay stays a full, free choice for every role including barangay
    // officials -- an evacuee's home barangay can genuinely differ from
    // whichever barangay's staff happens to be registering them (e.g. they
    // fled to a center outside their own barangay).
    (async () => {
        try {
            const [barangays, events] = await Promise.all([
                Api.get('/barangays'),
                Api.get('/evacuation-events'),
            ]);

            document.getElementById('barangay_id').innerHTML =
                '<option value="">Select barangay</option>' +
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');

            // Filtered client-side to non-closed events only -- the API
            // itself now returns ALL events (closed ones are needed by the
            // DROMIC reports and predictive analytics pages), so each page
            // that consumes it filters to what actually makes sense there.
            // Registering a new evacuee into an already-closed disaster
            // isn't a valid action, so closed events never appear here.
            const openEvents = events.data.filter((ev) => ev.status !== 'closed');
            document.getElementById('evacuation_event_id').innerHTML =
                '<option value="">Select event</option>' +
                openEvents.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join('');
        } catch (error) {
            showFormErrors(error);
        }
    })();

    // Reload evacuation centers whenever the barangay changes.
    document.getElementById('barangay_id').addEventListener('change', async (e) => {
        const select = document.getElementById('evacuation_center_id');
        if (! e.target.value) {
            select.innerHTML = '<option value="">Select barangay first</option>';
            return;
        }
        const centers = await Api.get(`/evacuation-centers?barangay_id=${e.target.value}`);
        select.innerHTML = '<option value="">Select center</option>' +
            centers.data.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');
    });

    document.getElementById('register-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            barangay_id: Number(document.getElementById('barangay_id').value),
            home_address: document.getElementById('home_address').value || null,
            evacuation_event_id: Number(document.getElementById('evacuation_event_id').value),
            displacement_type: document.getElementById('displacement_type').value,
            evacuation_center_id: document.getElementById('evacuation_center_id').value
                ? Number(document.getElementById('evacuation_center_id').value)
                : null,
            is_4ps_beneficiary: document.getElementById('is_4ps_beneficiary').checked,
            members: Array.from(document.querySelectorAll('.member-row')).map((row) => ({
                first_name: row.querySelector('.m-first_name').value,
                middle_name: row.querySelector('.m-middle_name').value || null,
                last_name: row.querySelector('.m-last_name').value,
                sex: row.querySelector('.m-sex').value,
                date_of_birth: row.querySelector('.m-date_of_birth').value,
                contact_number: row.querySelector('.m-contact_number').value || null,
                is_head_of_family: row.querySelector('.m-is_head_of_family').checked,
                is_pwd: row.querySelector('.m-is_pwd').checked,
                pwd_type: row.querySelector('.m-is_pwd').checked ? row.querySelector('.m-pwd_type').value : null,
                is_pregnant: row.querySelector('.m-is_pregnant').checked,
                is_lactating: row.querySelector('.m-is_lactating').checked,
                is_solo_parent: row.querySelector('.m-is_solo_parent').checked,
                is_indigenous_person: row.querySelector('.m-is_indigenous_person').checked,
            })),
        };

        const button = document.getElementById('submit-btn');
        button.disabled = true;
        button.textContent = 'Registering...';

        try {
            await Api.post('/families/register', payload);
            window.location.href = '/families';
        } catch (error) {
            showFormErrors(error);
            button.disabled = false;
            button.textContent = 'Register family';
        }
    });
</script>
@endsection
