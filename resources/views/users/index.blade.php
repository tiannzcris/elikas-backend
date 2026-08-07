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

    <div id="page-body" class="hidden">
        <div class="grid grid-cols-3 gap-4 mb-6">
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
                                    <div class="flex gap-3">
                                        <a href="/users/${u.id}/edit" class="text-xs text-brand hover:underline">Edit</a>
                                        <button onclick="toggleStatus(${u.id}, '${u.status}')" class="text-xs ${u.status === 'active' ? 'text-red-500' : 'text-green-600'} hover:underline">
                                            ${u.status === 'active' ? 'Deactivate' : 'Reactivate'}
                                        </button>
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
</script>
@endsection
