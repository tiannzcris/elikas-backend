@extends('layouts.app')

@section('title', 'Evacuation events')
@section('nav-events', 'active')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold mb-1">Evacuation events</h1>
            <p class="text-sm text-gray-500">Disaster events tracked by the system -- create one here before registering evacuees under it.</p>
        </div>
        <a href="/evacuation-events/create" id="add-event-btn"
            class="hidden bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Create event
        </a>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div id="hero-card" class="hidden bg-white border border-gray-200 rounded-xl p-5 mb-6"></div>

    <div id="stats-row" class="hidden grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total events</p>
                <p id="stat-total" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F59E0B;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Monitoring</p>
                <p id="stat-monitoring" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                <i class="ti ti-eye text-amber-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Active</p>
                <p id="stat-active" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-check text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #6B7280;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Closed</p>
                <p id="stat-closed" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                <i class="ti ti-archive text-gray-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total displaced</p>
                <p id="stat-displaced" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div id="status-tabs" class="flex items-center gap-2 flex-wrap"></div>
                <div class="relative">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 15px;" aria-hidden="true"></i>
                    <input id="search-input" type="text" placeholder="Search by name or type..."
                        class="border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm w-56">
                </div>
            </div>

            <div id="events-list" class="flex flex-col gap-3"></div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Events by status</p>
                <div class="flex items-center gap-4">
                    <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                        <canvas id="statusChart" role="img" aria-label="Doughnut chart of events by status"></canvas>
                    </div>
                    <div id="status-legend" class="flex-1 space-y-2 text-sm"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Events by type</p>
                <div id="type-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Recent activity</p>
                <div id="activity-timeline" class="space-y-4 text-xs"></div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    const user = Api.getUser();
    if (user && user.role !== 'barangay_official') {
        document.getElementById('add-event-btn').classList.remove('hidden');
    }

    const statusColors = {
        active: 'bg-green-50 text-green-700',
        monitoring: 'bg-amber-50 text-amber-700',
        closed: 'bg-gray-100 text-gray-600',
    };
    const statusDots = { active: '#22C55E', monitoring: '#F59E0B', closed: '#6B7280' };
    const statusLabels = { active: 'Active', monitoring: 'Monitoring', closed: 'Closed' };
    const eventTypeLabels = {
        typhoon: 'Typhoon', flood: 'Flood', volcanic_eruption: 'Volcanic eruption',
        earthquake: 'Earthquake', other: 'Other',
    };

    let allEvents = [];
    let allLogs = [];
    let statusFilter = 'all';
    let statusChartInstance = null;
    const canManage = user && user.role !== 'barangay_official';

    async function closeEvent(id) {
        if (! confirm('Close this event? It will no longer be selectable for new evacuee registration, but its reports and predictions stay available.')) {
            return;
        }
        try {
            await Api.request(`/evacuation-events/${id}`, { method: 'PATCH', body: JSON.stringify({ status: 'closed' }) });
            loadEvents();
        } catch (error) {
            showFormErrors(error);
        }
    }

    function renderHero(event) {
        const hero = document.getElementById('hero-card');
        if (! event) {
            hero.classList.add('hidden');
            return;
        }
        hero.classList.remove('hidden');

        // Only 3 statuses actually exist on this model (monitoring/active/
        // closed) -- shown as a 3-step tracker using the event's real
        // status and start/end dates. No fabricated per-stage timestamps
        // (e.g. "notified at 8:07 AM"): those transitions aren't
        // individually recorded anywhere in the schema.
        const steps = ['monitoring', 'active', 'closed'];
        const currentIndex = steps.indexOf(event.status);

        hero.innerHTML = `
            <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs px-2 py-0.5 rounded-lg font-semibold ${statusColors[event.status] ?? ''}">${(statusLabels[event.status] ?? event.status).toUpperCase()}</span>
                        ${event.typhoon_category ? `<span class="text-xs px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700">${event.typhoon_category}</span>` : ''}
                        ${event.alert_level ? `<span class="text-xs px-2 py-0.5 rounded-lg bg-red-50 text-red-700">${event.alert_level}</span>` : ''}
                    </div>
                    <h2 class="text-lg font-semibold text-gray-800">${event.name}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        ${eventTypeLabels[event.event_type] ?? event.event_type} &middot; Started ${event.start_date}${event.end_date ? ' &middot; Ended ' + event.end_date : ''}
                    </p>
                </div>
                <a href="/evacuation-events/${event.id}/edit" class="text-sm text-brand hover:underline shrink-0">View / edit details</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Rainfall</p>
                    <p class="text-lg font-bold text-gray-800">${event.rainfall_mm !== null ? event.rainfall_mm + ' mm' : '&mdash;'}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Wind speed</p>
                    <p class="text-lg font-bold text-gray-800">${event.max_wind_speed_kph !== null ? event.max_wind_speed_kph + ' kph' : '&mdash;'}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Families affected</p>
                    <p class="text-lg font-bold text-gray-800">${event.total_families_displaced}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Persons displaced</p>
                    <p class="text-lg font-bold text-gray-800">${event.total_persons_displaced}</p>
                </div>
            </div>

            <div id="hero-derived" class="grid grid-cols-2 gap-4 mb-5"></div>

            <div>
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-2">Status progress</p>
                <div class="flex items-center">
                    ${steps.map((step, i) => `
                        <div class="flex items-center ${i < steps.length - 1 ? 'flex-1' : ''}">
                            <div class="flex flex-col items-center">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs"
                                    style="background: ${i <= currentIndex ? statusDots[step] : '#E5E7EB'}">
                                    ${i < currentIndex ? '<i class=\"ti ti-check\" style=\"font-size:13px;\"></i>' : ''}
                                </span>
                                <span class="text-xs mt-1 ${i <= currentIndex ? 'text-gray-700 font-medium' : 'text-gray-400'}">${statusLabels[step]}</span>
                            </div>
                            ${i < steps.length - 1 ? `<div class="flex-1 h-0.5 mx-2" style="background: ${i < currentIndex ? statusDots[step] : '#E5E7EB'}"></div>` : ''}
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        // Affected barangays / evacuation centers used aren't stored on the
        // event itself -- derived from that event's registered families,
        // same source of truth the DROMIC report preview uses.
        Api.get(`/families?evacuation_event_id=${event.id}&per_page=200`).then((result) => {
            const families = result.data.data;
            const barangays = new Set(families.map((f) => f.barangay?.id).filter(Boolean));
            const centers = new Set(families.map((f) => f.evacuation_center?.id).filter(Boolean));
            document.getElementById('hero-derived').innerHTML = `
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Affected barangays</p>
                    <p class="text-base font-semibold text-gray-800">${barangays.size}</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-500">Evacuation centers in use</p>
                    <p class="text-base font-semibold text-gray-800">${centers.size}</p>
                </div>`;
        }).catch(() => {});
    }

    function renderStatusTabs() {
        const counts = { all: allEvents.length };
        Object.keys(statusLabels).forEach((key) => {
            counts[key] = allEvents.filter((e) => e.status === key).length;
        });
        const labels = { all: 'All', monitoring: 'Monitoring', active: 'Active', closed: 'Closed' };

        document.getElementById('status-tabs').innerHTML = Object.keys(labels).map((key) => `
            <button data-filter="${key}"
                class="status-tab text-sm px-3 py-1.5 rounded-lg border ${statusFilter === key ? 'border-brand bg-brand-light text-brand font-medium' : 'border-gray-300 text-gray-600 hover:bg-gray-50'}">
                ${labels[key]} (${counts[key]})
            </button>
        `).join('');

        document.querySelectorAll('.status-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                statusFilter = btn.dataset.filter;
                renderStatusTabs();
                renderEventsList();
            });
        });
    }

    function renderEventsList() {
        const query = document.getElementById('search-input').value.trim().toLowerCase();

        const filtered = allEvents.filter((e) => {
            const matchesFilter = statusFilter === 'all' || e.status === statusFilter;
            const matchesSearch = ! query ||
                e.name.toLowerCase().includes(query) ||
                (eventTypeLabels[e.event_type] ?? e.event_type).toLowerCase().includes(query);
            return matchesFilter && matchesSearch;
        });

        document.getElementById('events-list').innerHTML = filtered.length === 0
            ? '<p class="text-gray-400 text-sm text-center py-16 bg-white border border-gray-200 rounded-xl">No disaster events match this filter.</p>'
            : filtered.map((e) => `
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start gap-2.5">
                            <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                <i class="ti ti-alert-triangle text-blue-500" style="font-size: 18px;" aria-hidden="true"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <p class="font-medium text-sm">${e.name}</p>
                                    <span class="text-xs px-2 py-0.5 rounded-lg ${statusColors[e.status] ?? ''}">${statusLabels[e.status] ?? e.status}</span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    ${eventTypeLabels[e.event_type] ?? e.event_type}
                                    ${e.typhoon_category ? ' &middot; ' + e.typhoon_category : ''}
                                    ${e.rainfall_mm !== null ? ` &middot; ${e.rainfall_mm}mm rainfall` : ''}
                                    ${e.max_wind_speed_kph !== null ? ` &middot; ${e.max_wind_speed_kph}kph wind` : ''}
                                </p>
                                <p class="text-xs text-gray-400 mt-1">Started ${e.start_date}${e.end_date ? ' &middot; Ended ' + e.end_date : ''}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm"><span class="font-semibold">${e.total_families_displaced}</span> <span class="text-gray-500 text-xs">families</span></p>
                            <p class="text-sm"><span class="font-semibold">${e.total_persons_displaced}</span> <span class="text-gray-500 text-xs">persons</span></p>
                        </div>
                    </div>
                    ${canManage && e.status !== 'closed' ? `
                        <div class="border-t border-gray-100 mt-3 pt-3 flex gap-3">
                            <a href="/evacuation-events/${e.id}/edit" class="text-xs text-brand hover:underline">Edit</a>
                            <button onclick="closeEvent(${e.id})" class="text-xs text-red-500 hover:underline">Close event</button>
                        </div>
                    ` : ''}
                </div>
            `).join('');
    }

    function renderSidebar() {
        // Events by status
        const statusCounts = Object.keys(statusLabels).map((key) => ({
            key, label: statusLabels[key], count: allEvents.filter((e) => e.status === key).length,
        }));
        const statusTotal = allEvents.length || 1;
        document.getElementById('status-legend').innerHTML = statusCounts.map((s) => `
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600">
                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${statusDots[s.key]}"></span>${s.label}
                </span>
                <span class="font-medium text-gray-800">${s.count} <span class="text-gray-400 font-normal">(${Math.round(s.count / statusTotal * 100)}%)</span></span>
            </div>`).join('');

        if (statusChartInstance) statusChartInstance.destroy();
        statusChartInstance = new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusCounts.map((s) => s.label),
                datasets: [{ data: statusCounts.map((s) => s.count), backgroundColor: statusCounts.map((s) => statusDots[s.key]), borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
        });

        // Events by type
        const maxType = Math.max(...Object.keys(eventTypeLabels).map((k) => allEvents.filter((e) => e.event_type === k).length), 1);
        document.getElementById('type-distribution').innerHTML = Object.entries(eventTypeLabels).map(([key, label]) => {
            const count = allEvents.filter((e) => e.event_type === key).length;
            return `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">${label}</span>
                        <span class="font-medium text-gray-800">${count}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width:${count / maxType * 100}%"></div>
                    </div>
                </div>`;
        }).join('');

        // Recent activity, reused from the same audit trail as the other
        // pages, filtered to this module's actions.
        const recent = allLogs.filter((l) => l.action === 'evacuation_event.created' || l.action === 'evacuation_event.closed').slice(0, 6);
        document.getElementById('activity-timeline').innerHTML = recent.length ? recent.map((l) => `
            <div class="flex gap-2.5">
                <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-blue-400"></span>
                <div class="min-w-0">
                    <p class="text-gray-700 font-medium truncate">${l.description ?? l.action}</p>
                    <p class="text-gray-400">${l.user?.name ?? 'System'} &middot; ${new Date(l.created_at).toLocaleString()}</p>
                </div>
            </div>`).join('') : '<p class="text-gray-400">No activity recorded yet.</p>';
    }

    async function loadEvents() {
        try {
            // /system-logs is administrator-only server-side (unlike
            // canManage's broader admin+CSWD scope) -- gate the call
            // itself so CSWD personnel don't get a 403 on page load.
            const isAdministrator = user && user.role === 'administrator';
            const [eventsResult, logsResult] = await Promise.all([
                Api.get('/evacuation-events'),
                isAdministrator ? Api.get('/system-logs?per_page=200') : Promise.resolve({ data: { data: [] } }),
            ]);
            allEvents = eventsResult.data;
            allLogs = logsResult.data.data;

            if (allEvents.length > 0) {
                document.getElementById('stats-row').classList.remove('hidden');
                document.getElementById('stat-total').textContent = allEvents.length;
                document.getElementById('stat-monitoring').textContent = allEvents.filter((e) => e.status === 'monitoring').length;
                document.getElementById('stat-active').textContent = allEvents.filter((e) => e.status === 'active').length;
                document.getElementById('stat-closed').textContent = allEvents.filter((e) => e.status === 'closed').length;
                document.getElementById('stat-displaced').textContent =
                    allEvents.reduce((sum, e) => sum + (e.total_persons_displaced ?? 0), 0).toLocaleString();
            }

            const featured = allEvents.find((e) => e.status === 'active') ?? allEvents.find((e) => e.status === 'monitoring');
            renderHero(featured);

            renderStatusTabs();
            renderEventsList();
            renderSidebar();
        } catch (error) {
            showFormErrors(error);
        }
    }

    document.getElementById('search-input').addEventListener('input', renderEventsList);

    loadEvents();
</script>
@endsection
