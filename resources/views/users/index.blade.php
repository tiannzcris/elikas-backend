@extends('layouts.app')

@section('title', 'User management')
@section('nav-users', 'active')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="min-w-0">
            <h1 class="text-xl font-semibold mb-1">User management</h1>
            <p class="text-sm text-gray-500">Create and manage accounts for CSWD personnel and barangay officials.</p>
        </div>
        {{-- Opens the modal below (blank, add mode) instead of navigating to
            /users/create -- that route/page still exists untouched as a
            fallback, following the same pattern established for the
            alerts page. Each row's "Edit" action below opens the SAME
            modal, pre-populated, rather than a separate one -- both modes
            still share one form, same as the /users/create and
            /users/{id}/edit routes always have. --}}
        <button type="button" id="add-user-btn" class="shrink-0 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Add user
        </button>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>
    <div id="not-admin-notice" class="hidden bg-amber-50 text-amber-700 text-sm rounded-lg p-3 mb-4">
        Only administrators can manage user accounts.
    </div>

    <div id="page-body" class="hidden">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total users</p>
                    <p id="stat-total" class="text-2xl font-bold text-gray-800">&mdash;</p>
                    <p class="text-xs text-gray-400 italic mt-1">All accounts</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-users text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Active users</p>
                    <p id="stat-active" class="text-2xl font-bold text-gray-800">&mdash;</p>
                    <p id="stat-active-pct" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-user-check text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
                <div>
                    <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Inactive / suspended</p>
                    <p id="stat-inactive" class="text-2xl font-bold text-gray-800">&mdash;</p>
                    <p id="stat-inactive-pct" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
                </div>
                <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-user-off text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 min-w-0">
                <div class="flex flex-wrap items-center gap-3 mb-4">
                    <div class="relative flex-1 min-w-[200px]">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 16px;" aria-hidden="true"></i>
                        <input id="search-input" type="text" placeholder="Search by name, email, or username..."
                            class="w-full border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm">
                    </div>
                    <select id="role-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All roles</option>
                    </select>
                    <select id="status-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>

                <div id="users-list" class="bg-white border border-gray-200 rounded-xl overflow-hidden"></div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Users by role</p>
                    <div class="flex items-center gap-4">
                        <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                            <canvas id="roleChart" role="img" aria-label="Doughnut chart of users by role"></canvas>
                        </div>
                        <div id="role-legend" class="flex-1 space-y-2 text-sm"></div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Barangay officials by barangay</p>
                    <div id="barangay-distribution" class="space-y-2.5 text-xs"></div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Recent activity</p>
                    <div id="activity-timeline" class="space-y-4 text-xs"></div>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Role permissions</p>
                    <div class="space-y-3 text-xs">
                        <div>
                            <p class="font-medium text-gray-700 mb-0.5">
                                <span class="w-2 h-2 rounded-full inline-block mr-1" style="background:#A855F7"></span>
                                System Administrator
                            </p>
                            <p class="text-gray-500">Full system access, user management, backups.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700 mb-0.5">
                                <span class="w-2 h-2 rounded-full inline-block mr-1" style="background:#3B82F6"></span>
                                CSWD Personnel
                            </p>
                            <p class="text-gray-500">Monitoring, reporting, alerts, predictive analytics, GIS management.</p>
                        </div>
                        <div>
                            <p class="font-medium text-gray-700 mb-0.5">
                                <span class="w-2 h-2 rounded-full inline-block mr-1" style="background:#22C55E"></span>
                                Barangay Official
                            </p>
                            <p class="text-gray-500">Evacuee registration and barangay-level reports.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="delete-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-md w-full p-5">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-alert-triangle text-red-600" style="font-size: 18px;" aria-hidden="true"></i>
                </div>
                <div>
                    <p class="font-semibold text-gray-800">Delete account</p>
                    <p class="text-xs text-gray-500">This action is permanent and cannot be undone.</p>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-3">
                You are about to permanently delete <strong id="delete-modal-name"></strong>'s account
                (<span id="delete-modal-email" class="font-mono text-xs"></span>). All login access will be removed immediately.
            </p>

            <div id="delete-modal-error" class="hidden bg-red-50 text-red-700 text-xs rounded-lg p-3 mb-3"></div>

            <label class="text-xs text-gray-600 block mb-1">
                Type <strong id="delete-modal-confirm-email"></strong> to confirm
            </label>
            <input type="text" id="delete-confirm-input" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-4" autocomplete="off">

            <div class="flex justify-end gap-2">
                <button type="button" id="delete-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="button" id="delete-modal-confirm" disabled
                    class="text-sm text-white bg-red-600 rounded-lg px-4 py-2 opacity-50 cursor-not-allowed">
                    Delete permanently
                </button>
            </div>
        </div>
    </div>

    <div id="user-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800" id="user-modal-title">Add a user</p>
                    <p class="text-xs text-gray-500">Creates a login that works for both the web dashboard and the offline desktop companion.</p>
                </div>
                <button type="button" id="user-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="user-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="user-form" class="flex flex-col gap-4 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Full name</label>
                        <input type="text" id="user-name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1 flex items-center gap-1">
                            Email
                            <i id="user-email-lock-icon" class="hidden ti ti-lock text-gray-400" style="font-size: 13px;" aria-hidden="true"></i>
                        </label>
                        <input type="email" id="user-email" required placeholder="e.g. juan.delacruz@ligao.gov.ph" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed disabled:border-gray-200">
                        <p class="text-xs text-gray-400 mt-1">This is what they'll use to log in -- can't be changed after the account is created.</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1" id="user-password-label">Password</label>
                        <div class="flex gap-2">
                            <input type="password" id="user-password" class="flex-1 min-w-0 border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Minimum 8 characters">
                            <button type="button" id="user-generate-password-btn"
                                class="shrink-0 text-xs font-medium text-brand border border-brand/30 rounded-lg px-3 hover:bg-brand-light">
                                Generate
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Contact number (optional)</label>
                        <input type="text" id="user-contact_number" placeholder="09XXXXXXXXX" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1 flex items-center gap-1">
                            Role
                            <i id="user-role-lock-icon" class="hidden ti ti-lock text-gray-400" style="font-size: 13px;" aria-hidden="true"></i>
                        </label>
                        <select id="user-role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed disabled:border-gray-200">
                            <option value="administrator">Administrator</option>
                            <option value="cswd_personnel">CSWD Personnel</option>
                            <option value="barangay_official">Barangay Official</option>
                        </select>
                        <p id="user-role-locked-note" class="hidden text-xs text-gray-400 mt-1">Role and barangay can't be changed after an account is created -- create a new account instead if this needs to change.</p>
                    </div>
                    <div id="user-barangay-field" class="hidden">
                        <label class="text-sm text-gray-600 block mb-1 flex items-center gap-1">
                            Barangay
                            <i id="user-barangay-lock-icon" class="hidden ti ti-lock text-gray-400" style="font-size: 13px;" aria-hidden="true"></i>
                        </label>
                        <select id="user-barangay_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed disabled:border-gray-200">
                            <option value="">Select barangay</option>
                        </select>
                    </div>
                    <div id="user-status-field" class="hidden">
                        <label class="text-sm text-gray-600 block mb-1">Status</label>
                        <select id="user-status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="user-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="user-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                        Create account
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    const roleColors = {
        administrator: 'bg-purple-50 text-purple-700',
        cswd_personnel: 'bg-blue-50 text-blue-700',
        barangay_official: 'bg-green-50 text-green-700',
        resident: 'bg-gray-100 text-gray-600',
    };
    const roleChartColors = {
        administrator: '#A855F7', cswd_personnel: '#3B82F6',
        barangay_official: '#22C55E', resident: '#9CA3AF',
    };
    const statusColors = {
        active: 'bg-green-50 text-green-700',
        inactive: 'bg-gray-100 text-gray-600',
        suspended: 'bg-red-50 text-red-700',
    };
    const AVATAR_COLORS = ['#2563EB', '#16A34A', '#D97706', '#DB2777', '#7C3AED', '#0891B2'];

    function avatarFor(name) {
        const label = name || '?';
        const initials = label.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase() || '?';
        let hash = 0;
        for (let i = 0; i < label.length; i++) hash = label.charCodeAt(i) + ((hash << 5) - hash);
        const color = AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
        return `<div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-semibold shrink-0" style="background:${color}">${initials}</div>`;
    }

    let allUsers = [];
    let allLogs = [];
    let roleChartInstance = null;
    let pendingDeleteUser = null;
    // currentUser is already declared as a global by layouts/app.blade.php's
    // own inline script, which runs before this one (earlier in the
    // document, ahead of @yield('scripts')) -- redeclaring it here with
    // `const` threw "Identifier 'currentUser' has already been declared",
    // a SyntaxError that silently broke this entire script block, which is
    // why the whole list/stats/sidebar disappeared. Same bug, same fix as
    // the one already found on dashboard.blade.php earlier.

    function lastLoginFor(userId) {
        const login = allLogs.find((l) => l.action === 'user.login' && l.user?.id === userId);
        return login ? login.created_at : null;
    }

    async function toggleStatus(userId, currentStatus) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const verb = newStatus === 'active' ? 'reactivate' : 'deactivate';
        if (! confirm(`Are you sure you want to ${verb} this account?`)) return;

        try {
            await Api.request(`/users/${userId}`, { method: 'PATCH', body: JSON.stringify({ status: newStatus }) });
            loadUsers();
        } catch (error) {
            showFormErrors(error);
        }
    }

    // Deletion requires typing the exact email address to confirm --
    // deliberately more friction than a confirm() dialog, so a careless
    // double-click can't delete the wrong account. Looks up the user from
    // the already-loaded allUsers array rather than embedding the user
    // object as inline JSON in the button's onclick attribute, since a
    // name/email could in principle contain a quote character that would
    // break the generated HTML.
    function openDeleteModal(userId) {
        const user = allUsers.find((u) => u.id === userId);
        if (! user) return;

        pendingDeleteUser = user;
        document.getElementById('delete-modal-name').textContent = user.name;
        document.getElementById('delete-modal-email').textContent = user.email;
        document.getElementById('delete-modal-confirm-email').textContent = user.email;
        document.getElementById('delete-confirm-input').value = '';
        document.getElementById('delete-modal-error').classList.add('hidden');
        updateDeleteConfirmButtonState();

        document.getElementById('delete-modal').classList.remove('hidden');
        document.getElementById('delete-modal').classList.add('flex');
    }

    function closeDeleteModal() {
        pendingDeleteUser = null;
        document.getElementById('delete-modal').classList.add('hidden');
        document.getElementById('delete-modal').classList.remove('flex');
    }

    function updateDeleteConfirmButtonState() {
        const typed = document.getElementById('delete-confirm-input').value;
        const button = document.getElementById('delete-modal-confirm');
        const matches = pendingDeleteUser && typed === pendingDeleteUser.email;

        button.disabled = ! matches;
        button.classList.toggle('opacity-50', ! matches);
        button.classList.toggle('cursor-not-allowed', ! matches);
    }

    document.getElementById('delete-confirm-input').addEventListener('input', updateDeleteConfirmButtonState);
    document.getElementById('delete-modal-cancel').addEventListener('click', closeDeleteModal);

    document.getElementById('delete-modal-confirm').addEventListener('click', async () => {
        if (! pendingDeleteUser) return;

        const button = document.getElementById('delete-modal-confirm');
        button.disabled = true;
        button.textContent = 'Deleting...';

        try {
            await Api.request(`/users/${pendingDeleteUser.id}`, { method: 'DELETE' });
            closeDeleteModal();
            loadUsers();
        } catch (error) {
            // Shows the backend's exact reason inline in the modal (e.g.
            // "This account has 3 alerts sent... use Deactivate instead")
            // rather than a generic error, so the admin understands why
            // and what to do next without leaving the modal.
            const errorBox = document.getElementById('delete-modal-error');
            errorBox.textContent = error.message;
            errorBox.classList.remove('hidden');
            button.textContent = 'Delete permanently';
            updateDeleteConfirmButtonState();
        }
    });

    function renderTable() {
        const query = document.getElementById('search-input').value.trim().toLowerCase();
        const role = document.getElementById('role-filter').value;
        const status = document.getElementById('status-filter').value;

        const filtered = allUsers.filter((u) => {
            const matchesSearch = ! query ||
                u.name.toLowerCase().includes(query) || u.email.toLowerCase().includes(query);
            const matchesRole = ! role || u.role === role;
            const matchesStatus = ! status || u.status === status;
            return matchesSearch && matchesRole && matchesStatus;
        });

        if (filtered.length === 0) {
            document.getElementById('users-list').innerHTML =
                '<p class="text-gray-400 text-sm text-center py-16">No users match this filter.</p>';
            return;
        }

        document.getElementById('users-list').innerHTML = `
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">User</th>
                            <th class="text-left px-4 py-3">Role</th>
                            <th class="text-left px-4 py-3">Barangay / Office</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3">Last login</th>
                            <th class="text-left px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${filtered.map((u) => {
                            const lastLogin = lastLoginFor(u.id);
                            return `
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        ${avatarFor(u.name)}
                                        <div>
                                            <p class="font-medium">${u.name}</p>
                                            <p class="text-xs text-gray-400">${u.email}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-lg ${roleColors[u.role] ?? ''}">${u.role_display_name}</span></td>
                                <td class="px-4 py-3 text-gray-500">${u.barangay?.name ?? '&mdash;'}</td>
                                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-lg ${statusColors[u.status] ?? ''}">${u.status}</span></td>
                                <td class="px-4 py-3 text-gray-500">${lastLogin ? new Date(lastLogin).toLocaleString() : 'Never'}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <button onclick="openUserModal(${u.id})" class="text-xs text-brand hover:underline">Edit</button>
                                        <button onclick="toggleStatus(${u.id}, '${u.status}')" class="text-xs ${u.status === 'active' ? 'text-red-500' : 'text-green-600'} hover:underline">
                                            ${u.status === 'active' ? 'Deactivate' : 'Reactivate'}
                                        </button>
                                        ${currentUser && u.id === currentUser.id ? '' : `
                                            <button onclick="openDeleteModal(${u.id})"
                                                class="text-xs font-medium text-red-700 bg-red-50 hover:bg-red-100 rounded px-2 py-1 flex items-center gap-1">
                                                <i class="ti ti-trash" style="font-size: 12px;" aria-hidden="true"></i> Delete
                                            </button>
                                        `}
                                    </div>
                                </td>
                            </tr>`;
                        }).join('')}
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 text-xs text-gray-400">
                Showing ${filtered.length} of ${allUsers.length} users
            </div>`;
    }

    function renderStatCards() {
        const total = allUsers.length;
        const active = allUsers.filter((u) => u.status === 'active').length;
        const inactive = total - active;

        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-active').textContent = active;
        document.getElementById('stat-inactive').textContent = inactive;
        document.getElementById('stat-active-pct').innerHTML = total ? `${Math.round(active / total * 100)}% of total users` : '&mdash;';
        document.getElementById('stat-inactive-pct').innerHTML = total ? `${Math.round(inactive / total * 100)}% of total users` : '&mdash;';
    }

    function renderSidebar() {
        // Users by role
        const roleLabels = {};
        allUsers.forEach((u) => { roleLabels[u.role] = u.role_display_name; });
        const roleCounts = Object.keys(roleLabels).map((key) => ({
            key, label: roleLabels[key], count: allUsers.filter((u) => u.role === key).length,
        })).sort((a, b) => b.count - a.count);
        const roleTotal = allUsers.length || 1;

        document.getElementById('role-legend').innerHTML = roleCounts.map((r) => `
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600">
                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${roleChartColors[r.key] ?? '#9CA3AF'}"></span>${r.label}
                </span>
                <span class="font-medium text-gray-800">${r.count} <span class="text-gray-400 font-normal">(${Math.round(r.count / roleTotal * 100)}%)</span></span>
            </div>`).join('');

        if (roleChartInstance) roleChartInstance.destroy();
        roleChartInstance = new Chart(document.getElementById('roleChart'), {
            type: 'doughnut',
            data: {
                labels: roleCounts.map((r) => r.label),
                datasets: [{ data: roleCounts.map((r) => r.count), backgroundColor: roleCounts.map((r) => roleChartColors[r.key] ?? '#9CA3AF'), borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
        });

        // Barangay officials by barangay (top 5)
        const officials = allUsers.filter((u) => u.role === 'barangay_official');
        const byBarangay = {};
        officials.forEach((u) => {
            const name = u.barangay?.name ?? 'Unassigned';
            byBarangay[name] = (byBarangay[name] ?? 0) + 1;
        });
        const topBarangays = Object.entries(byBarangay).sort((a, b) => b[1] - a[1]).slice(0, 5);
        const maxBrgy = Math.max(...topBarangays.map(([, c]) => c), 1);
        document.getElementById('barangay-distribution').innerHTML = topBarangays.length
            ? topBarangays.map(([name, count]) => `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">${name}</span>
                        <span class="font-medium text-gray-800">${count}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-green-500 h-1.5 rounded-full" style="width:${count / maxBrgy * 100}%"></div>
                    </div>
                </div>`).join('')
            : '<p class="text-gray-400">No barangay officials yet.</p>';

        // Recent activity from the real audit trail
        const recent = allLogs.slice(0, 6);
        document.getElementById('activity-timeline').innerHTML = recent.length ? recent.map((l) => `
            <div class="flex gap-2.5">
                <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-blue-400"></span>
                <div class="min-w-0">
                    <p class="text-gray-700 font-medium truncate">${l.description ?? l.action}</p>
                    <p class="text-gray-400">${l.user?.name ?? 'System'} &middot; ${new Date(l.created_at).toLocaleString()}</p>
                </div>
            </div>`).join('') : '<p class="text-gray-400">No activity recorded yet.</p>';
    }

    async function loadUsers() {
        try {
            const [usersResult, logsResult] = await Promise.all([
                Api.get('/users'),
                Api.get('/system-logs?per_page=200'),
            ]);

            allUsers = usersResult.data;
            allLogs = logsResult.data.data;

            document.getElementById('page-body').classList.remove('hidden');

            const roleFilter = document.getElementById('role-filter');
            const seenRoles = {};
            allUsers.forEach((u) => { seenRoles[u.role] = u.role_display_name; });
            roleFilter.insertAdjacentHTML('beforeend',
                Object.entries(seenRoles).map(([value, label]) => `<option value="${value}">${label}</option>`).join(''));

            renderStatCards();
            renderTable();
            renderSidebar();
        } catch (error) {
            if (error.status === 403) {
                document.getElementById('not-admin-notice').classList.remove('hidden');
            } else {
                showFormErrors(error);
            }
        }
    }

    document.getElementById('search-input').addEventListener('input', renderTable);
    document.getElementById('role-filter').addEventListener('change', renderTable);
    document.getElementById('status-filter').addEventListener('change', renderTable);

    loadUsers();

    // --- Add/Edit user modal ----------------------------------------------
    // /users/create and /users/{id}/edit both still exist untouched as
    // fallbacks. Both modes share this one modal/form, same as they always
    // shared one Blade file -- editingUserId (null = add, a real id = edit)
    // replaces the isEdit-from-URL check that page used.

    let editingUserId = null;

    document.getElementById('user-role').addEventListener('change', (e) => {
        document.getElementById('user-barangay-field').classList.toggle('hidden', e.target.value !== 'barangay_official');
    });

    // Shared by both modes identically -- "generate a password for a new
    // account" and "an admin resetting an existing user's password" are
    // the same action on the same field.
    document.getElementById('user-generate-password-btn').addEventListener('click', () => {
        const charset = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%^&*';
        const randomValues = crypto.getRandomValues(new Uint32Array(14));
        const generated = Array.from(randomValues, (v) => charset[v % charset.length]).join('');

        const passwordInput = document.getElementById('user-password');
        passwordInput.type = 'text'; // visible, not masked -- the admin just generated it and needs to read/copy it
        passwordInput.value = generated;
    });

    async function openUserModal(userId = null) {
        editingUserId = userId;

        document.getElementById('user-modal-errors').classList.add('hidden');
        document.getElementById('user-form').reset();
        document.getElementById('user-password').type = 'password';
        document.getElementById('user-barangay-field').classList.add('hidden');

        // Reset from any previous edit-mode open -- re-locked below only
        // if this open turns out to be edit mode too.
        document.getElementById('user-role').disabled = false;
        document.getElementById('user-barangay_id').disabled = false;
        document.getElementById('user-role-locked-note').classList.add('hidden');
        document.getElementById('user-role-lock-icon').classList.add('hidden');
        document.getElementById('user-barangay-lock-icon').classList.add('hidden');
        document.getElementById('user-email-lock-icon').classList.add('hidden');

        document.getElementById('user-modal').classList.remove('hidden');
        document.getElementById('user-modal').classList.add('flex');

        try {
            const barangays = await Api.get('/barangays');
            document.getElementById('user-barangay_id').innerHTML =
                '<option value="">Select barangay</option>' +
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');

            if (editingUserId === null) {
                document.getElementById('user-modal-title').textContent = 'Add a user';
                document.getElementById('user-submit-btn').textContent = 'Create account';
                document.getElementById('user-password-label').textContent = 'Password';
                document.getElementById('user-password').placeholder = 'Minimum 8 characters';
                document.getElementById('user-email').disabled = false;
                document.getElementById('user-status-field').classList.add('hidden');
                return;
            }

            // Looked up from the already-loaded allUsers array (same as
            // openDeleteModal already does) rather than a fresh API call --
            // the list on this page is already the full, current data.
            const user = allUsers.find((u) => u.id === editingUserId);
            if (! user) throw new Error('User not found.');

            document.getElementById('user-modal-title').textContent = 'Edit user';
            document.getElementById('user-submit-btn').textContent = 'Save changes';
            document.getElementById('user-password-label').textContent = 'New password (optional)';
            document.getElementById('user-password').placeholder = 'Leave blank to keep current password';
            document.getElementById('user-status-field').classList.remove('hidden');

            document.getElementById('user-name').value = user.name;
            document.getElementById('user-email').value = user.email;
            document.getElementById('user-email').disabled = true; // email isn't editable after creation
            document.getElementById('user-email-lock-icon').classList.remove('hidden');
            document.getElementById('user-contact_number').value = user.contact_number ?? '';
            document.getElementById('user-role').value = user.role;
            document.getElementById('user-status').value = user.status;
            if (user.role === 'barangay_official') {
                document.getElementById('user-barangay-field').classList.remove('hidden');
                document.getElementById('user-barangay_id').value = user.barangay?.id ?? '';
            }

            // Role and barangay are set once at account creation and never
            // changeable afterward (UpdateUserRequest doesn't even
            // validate them anymore, so a submission couldn't change them
            // either way -- this just makes that visible instead of
            // letting the admin fill in a value that would silently be
            // ignored).
            document.getElementById('user-role').disabled = true;
            document.getElementById('user-barangay_id').disabled = true;
            document.getElementById('user-role-locked-note').classList.remove('hidden');
            document.getElementById('user-role-lock-icon').classList.remove('hidden');
            document.getElementById('user-barangay-lock-icon').classList.remove('hidden');
        } catch (error) {
            const box = document.getElementById('user-modal-errors');
            box.innerHTML = `<p>${error.message}</p>`;
            box.classList.remove('hidden');
        }
    }

    function closeUserModal() {
        editingUserId = null;
        document.getElementById('user-modal').classList.add('hidden');
        document.getElementById('user-modal').classList.remove('flex');
    }

    document.getElementById('add-user-btn').addEventListener('click', () => openUserModal(null));
    document.getElementById('user-modal-close').addEventListener('click', closeUserModal);
    document.getElementById('user-modal-cancel').addEventListener('click', closeUserModal);

    document.getElementById('user-modal').addEventListener('click', (e) => {
        if (e.target.id === 'user-modal') closeUserModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('user-modal').classList.contains('hidden')) {
            closeUserModal();
        }
    });

    document.getElementById('user-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const isEdit = editingUserId !== null;

        const payload = {
            name: document.getElementById('user-name').value,
            contact_number: document.getElementById('user-contact_number').value || null,
        };

        const password = document.getElementById('user-password').value;
        if (password) payload.password = password;

        if (isEdit) {
            // role/barangay_id deliberately omitted -- UpdateUserRequest no
            // longer validates either, so sending the (disabled, unchanged)
            // select values here would just be silently ignored server-side.
            payload.status = document.getElementById('user-status').value;
        } else {
            payload.role = document.getElementById('user-role').value;
            payload.barangay_id = document.getElementById('user-role').value === 'barangay_official'
                ? (document.getElementById('user-barangay_id').value || null) : null;
            payload.email = document.getElementById('user-email').value;
            if (! password) {
                const box = document.getElementById('user-modal-errors');
                box.innerHTML = '<p>Password is required when creating a new account.</p>';
                box.classList.remove('hidden');
                return;
            }
        }

        const button = document.getElementById('user-submit-btn');
        button.disabled = true;
        button.textContent = isEdit ? 'Saving...' : 'Creating...';

        try {
            if (isEdit) {
                await Api.request(`/users/${editingUserId}`, { method: 'PATCH', body: JSON.stringify(payload) });
            } else {
                const result = await Api.post('/users', payload);
                alert(result.message);
            }
            closeUserModal();
            await loadUsers(); // refresh in place, no full page reload
        } catch (error) {
            const box = document.getElementById('user-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = isEdit ? 'Save changes' : 'Create account';
        }
    });
</script>
@endsection
