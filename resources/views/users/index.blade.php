@extends('layouts.app')

@section('title', 'User management')
@section('nav-users', 'active')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold mb-1">User management</h1>
            <p class="text-sm text-gray-500">Create and manage accounts for CSWD personnel and barangay officials.</p>
        </div>
        <a href="/users/create" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Add user
        </a>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>
    <div id="not-admin-notice" class="hidden bg-amber-50 text-amber-700 text-sm rounded-lg p-3 mb-4">
        Only administrators can manage user accounts.
    </div>

    <div id="users-list" class="bg-white border border-gray-200 rounded-xl overflow-hidden"></div>
@endsection

@section('scripts')
<script>
    const roleColors = {
        administrator: 'bg-purple-50 text-purple-700',
        cswd_personnel: 'bg-blue-50 text-blue-700',
        barangay_official: 'bg-green-50 text-green-700',
    };
    const statusColors = {
        active: 'bg-green-50 text-green-700',
        inactive: 'bg-gray-100 text-gray-600',
        suspended: 'bg-red-50 text-red-700',
    };

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

    async function loadUsers() {
        try {
            const result = await Api.get('/users');
            const users = result.data;

            document.getElementById('users-list').innerHTML = `
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="text-left px-4 py-3">Name</th>
                            <th class="text-left px-4 py-3">Email</th>
                            <th class="text-left px-4 py-3">Role</th>
                            <th class="text-left px-4 py-3">Barangay</th>
                            <th class="text-left px-4 py-3">Status</th>
                            <th class="text-left px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        ${users.map((u) => `
                            <tr class="border-t border-gray-100">
                                <td class="px-4 py-3 font-medium">${u.name}</td>
                                <td class="px-4 py-3 text-gray-500">${u.email}</td>
                                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-lg ${roleColors[u.role] ?? ''}">${u.role_display_name}</span></td>
                                <td class="px-4 py-3 text-gray-500">${u.barangay?.name ?? '&mdash;'}</td>
                                <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded-lg ${statusColors[u.status] ?? ''}">${u.status}</span></td>
                                <td class="px-4 py-3 flex gap-3">
                                    <a href="/users/${u.id}/edit" class="text-xs text-brand hover:underline">Edit</a>
                                    <button onclick="toggleStatus(${u.id}, '${u.status}')" class="text-xs ${u.status === 'active' ? 'text-red-500' : 'text-green-600'} hover:underline">
                                        ${u.status === 'active' ? 'Deactivate' : 'Reactivate'}
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>`;
        } catch (error) {
            if (error.status === 403) {
                document.getElementById('not-admin-notice').classList.remove('hidden');
            } else {
                showFormErrors(error);
            }
        }
    }

    loadUsers();
</script>
@endsection
