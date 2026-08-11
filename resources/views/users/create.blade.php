@extends('layouts.app')

@section('title', 'User account')
@section('nav-users', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1" id="page-title">Add a user</h1>
    <p class="text-sm text-gray-500 mb-6">Creates a login that works for both the web dashboard and the offline desktop companion.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4 max-w-2xl"></div>

    <form id="user-form" class="flex flex-col gap-4 max-w-2xl">
        <div class="bg-white border border-gray-200 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="text-sm text-gray-600 block mb-1">Full name</label>
                <input type="text" id="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Email</label>
                <input type="email" id="email" required placeholder="e.g. juan.delacruz@ligao.gov.ph" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">This is what they'll use to log in -- can't be changed after the account is created.</p>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1" id="password-label">Password</label>
                <div class="flex gap-2">
                    <input type="password" id="password" class="flex-1 min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Minimum 8 characters">
                    <button type="button" id="generate-password-btn"
                        class="shrink-0 text-xs font-medium text-brand border border-brand/30 rounded-lg px-3 hover:bg-brand-light">
                        Generate
                    </button>
                </div>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Contact number (optional)</label>
                <input type="text" id="contact_number" placeholder="09XXXXXXXXX" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Role</label>
                <select id="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="administrator">Administrator</option>
                    <option value="cswd_personnel">CSWD Personnel</option>
                    <option value="barangay_official">Barangay Official</option>
                </select>
                <p id="role-locked-note" class="hidden text-xs text-gray-400 mt-1">Role and barangay can't be changed after an account is created -- create a new account instead if this needs to change.</p>
            </div>
            <div id="barangay-field" class="hidden">
                <label class="text-sm text-gray-600 block mb-1">Barangay</label>
                <select id="barangay_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select barangay</option>
                </select>
            </div>
            <div id="status-field" class="hidden">
                <label class="text-sm text-gray-600 block mb-1">Status</label>
                <select id="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5 w-fit">
            Create account
        </button>
    </form>
@endsection

@section('scripts')
<script>
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const isEdit = pathParts.includes('edit');
    const userId = isEdit ? pathParts[1] : null;

    document.getElementById('role').addEventListener('change', (e) => {
        document.getElementById('barangay-field').classList.toggle('hidden', e.target.value !== 'barangay_official');
    });

    // Not inside the isEdit branch below -- this button and its input live
    // in the single form shared by both create and edit modes, so wiring
    // it once here covers "creating a new account" and "an admin resetting
    // an existing user's password" identically, with no special-casing.
    document.getElementById('generate-password-btn').addEventListener('click', () => {
        const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%^&*';
        const randomValues = crypto.getRandomValues(new Uint32Array(14));
        const generated = Array.from(randomValues, (v) => charset[v % charset.length]).join('');

        const passwordInput = document.getElementById('password');
        passwordInput.type = 'text'; // visible, not masked -- the admin just generated it and needs to read/copy it
        passwordInput.value = generated;
    });

    (async () => {
        try {
            const barangays = await Api.get('/barangays');
            document.getElementById('barangay_id').insertAdjacentHTML('beforeend',
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join(''));

            if (isEdit) {
                document.getElementById('page-title').textContent = 'Edit user';
                document.getElementById('submit-btn').textContent = 'Save changes';
                document.getElementById('password-label').textContent = 'New password (optional)';
                document.getElementById('password').placeholder = 'Leave blank to keep current password';
                document.getElementById('status-field').classList.remove('hidden');

                const result = await Api.get('/users');
                const user = result.data.find((u) => u.id === Number(userId));
                if (! user) throw new Error('User not found.');

                document.getElementById('name').value = user.name;
                document.getElementById('email').value = user.email;
                document.getElementById('email').disabled = true; // email isn't editable after creation
                document.getElementById('contact_number').value = user.contact_number ?? '';
                document.getElementById('role').value = user.role;
                document.getElementById('status').value = user.status;
                if (user.role === 'barangay_official') {
                    document.getElementById('barangay-field').classList.remove('hidden');
                    document.getElementById('barangay_id').value = user.barangay?.id ?? '';
                }

                // Role and barangay are set once at account creation and
                // never changeable afterward (UpdateUserRequest doesn't
                // even validate them anymore, so a submission couldn't
                // change them either way -- this just makes that visible
                // instead of letting the admin fill in a value that would
                // silently be ignored).
                document.getElementById('role').disabled = true;
                document.getElementById('barangay_id').disabled = true;
                document.getElementById('role-locked-note').classList.remove('hidden');
            }
        } catch (error) {
            showFormErrors(error);
        }
    })();

    document.getElementById('user-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            name: document.getElementById('name').value,
            contact_number: document.getElementById('contact_number').value || null,
        };

        const password = document.getElementById('password').value;
        if (password) payload.password = password;

        if (isEdit) {
            // role/barangay_id deliberately omitted -- UpdateUserRequest no
            // longer validates either, so sending the (disabled, unchanged)
            // select values here would just be silently ignored server-side.
            payload.status = document.getElementById('status').value;
        } else {
            payload.role = document.getElementById('role').value;
            payload.barangay_id = document.getElementById('role').value === 'barangay_official'
                ? (document.getElementById('barangay_id').value || null) : null;
            payload.email = document.getElementById('email').value;
            if (! password) {
                showFormErrors({ message: 'Password is required when creating a new account.' });
                return;
            }
        }

        const button = document.getElementById('submit-btn');
        button.disabled = true;
        button.textContent = isEdit ? 'Saving...' : 'Creating...';

        try {
            if (isEdit) {
                await Api.request(`/users/${userId}`, { method: 'PATCH', body: JSON.stringify(payload) });
            } else {
                const result = await Api.post('/users', payload);
                alert(result.message);
            }
            window.location.href = '/users';
        } catch (error) {
            showFormErrors(error);
            button.disabled = false;
            button.textContent = isEdit ? 'Save changes' : 'Create account';
        }
    });
</script>
@endsection
