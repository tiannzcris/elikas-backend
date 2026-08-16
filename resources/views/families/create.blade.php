@extends('layouts.app')

@section('title', 'Register a family')
@section('nav-families', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Register a family</h1>
    <p class="text-sm text-gray-500 mb-6">Register every member of an arriving household in one step.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <form id="register-form" class="flex flex-col gap-6 max-w-3xl">
        <div class="bg-white border border-gray-200 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-gray-600 block mb-1">Barangay</label>
                <select id="barangay_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
                <label class="text-sm text-gray-600 block mb-1 mt-3">Street/Sitio Address (optional)</label>
                <input type="text" id="home_address" placeholder="e.g. Purok 3, Sitio Mabuhay" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Disaster event</label>
                <select id="evacuation_event_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Displacement type</label>
                <select id="displacement_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="inside_center">Inside an evacuation center</option>
                    <option value="outside_center">Outside (evacuated to relatives/other location)</option>
                </select>
            </div>
            <div id="center-field">
                <label class="text-sm text-gray-600 block mb-1">Evacuation center</label>
                <select id="evacuation_center_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
            </div>
            <label class="flex items-center gap-2 text-sm text-gray-600 col-span-2">
                <input type="checkbox" id="is_4ps_beneficiary"> Household is a 4Ps beneficiary
            </label>
        </div>

        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-medium text-gray-700">Household members</h2>
                <button type="button" id="add-member-btn" class="text-sm text-brand hover:underline">+ Add another member</button>
            </div>
            <div id="members-container" class="flex flex-col gap-4"></div>
        </div>

        <button type="submit" id="submit-btn"
            class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5 w-fit">
            Register family
        </button>
    </form>
@endsection

@section('scripts')
<script>
    let memberCount = 0;

    function memberRowHtml(index) {
        return `
        <div class="member-row bg-white border border-gray-200 rounded-xl p-4" data-index="${index}">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-medium text-gray-600">Member ${index + 1}</p>
                ${index > 0 ? `<button type="button" class="remove-member text-xs text-red-500 hover:underline">Remove</button>` : ''}
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <input type="text" placeholder="First name" class="m-first_name border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <input type="text" placeholder="Middle name" class="m-middle_name border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <input type="text" placeholder="Last name" class="m-last_name border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <select class="m-sex border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    <option value="">Sex</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
                <input type="date" class="m-date_of_birth border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                <div>
                    <input type="text" placeholder="09XXXXXXXXX" class="m-contact_number w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
                    <button type="button" class="same-as-head-btn text-xs text-brand hover:underline mt-1">Same as head of family</button>
                </div>
            </div>
            <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-600 items-center">
                <label class="flex items-center gap-1.5"><input type="radio" name="head-${index}" class="m-is_head_of_family"> Head of family</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_pwd"> PWD</label>
                <input type="text" placeholder="PWD type (e.g. visual, mobility)" class="m-pwd_type hidden border border-gray-300 rounded-lg px-2 py-1 text-xs">
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_pregnant"> Pregnant</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_lactating"> Lactating</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_solo_parent"> Solo parent</label>
                <label class="flex items-center gap-1.5"><input type="checkbox" class="m-is_indigenous_person"> Indigenous person</label>
            </div>
            <p class="text-xs text-gray-400 mt-2">Contact number is required for every member -- if someone doesn't have their own phone (e.g. a child or elderly member), use "Same as head of family" to reuse the household's number.</p>
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

        const members = Array.from(document.querySelectorAll('.member-row')).map((row) => ({
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
        }));

        const payload = {
            barangay_id: Number(document.getElementById('barangay_id').value),
            home_address: document.getElementById('home_address').value || null,
            evacuation_event_id: Number(document.getElementById('evacuation_event_id').value),
            displacement_type: document.getElementById('displacement_type').value,
            evacuation_center_id: document.getElementById('evacuation_center_id').value
                ? Number(document.getElementById('evacuation_center_id').value)
                : null,
            is_4ps_beneficiary: document.getElementById('is_4ps_beneficiary').checked,
            members,
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
