@extends('layouts.app')

@section('title', 'Evacuation events')
@section('nav-events', 'active')

@section('content')
    <div class="page-header">
        <div class="min-w-0">
            <h1 class="page-title">Evacuation events</h1>
            <p class="page-subtitle">Disaster events tracked by the system -- create one here before registering evacuees under it.</p>
        </div>
        {{-- Opens the modal below instead of navigating to /evacuation-events/create --
            that route/page still exists untouched (also still reused for
            editing an existing event, which stays a full page for now --
            only the create flow moved to a modal), following the same
            pattern established for the alerts page. --}}
        <button type="button" id="add-event-btn"
            class="hidden shrink-0 btn btn-primary">
            + Create event
        </button>
    </div>

    <div id="form-errors" class="hidden callout callout-danger mb-4"></div>

    <div id="hero-card" class="hidden card p-5 mb-6"></div>

    <div id="stats-row" class="hidden stat-strip grid-cols-2 lg:grid-cols-5 mb-6">
        <div class="stat">
            <p class="stat-label">Total events</p>
            <p id="stat-total" class="stat-value">&mdash;</p>
        </div>
        <div class="stat">
            <p class="stat-label">Monitoring</p>
            <p id="stat-monitoring" class="stat-value">&mdash;</p>
        </div>
        <div class="stat">
            <p class="stat-label">Active</p>
            <p id="stat-active" class="stat-value">&mdash;</p>
        </div>
        <div class="stat">
            <p class="stat-label">Closed</p>
            <p id="stat-closed" class="stat-value">&mdash;</p>
        </div>
        <div class="stat">
            <p class="stat-label">Total displaced</p>
            <p id="stat-displaced" class="stat-value">&mdash;</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div id="status-tabs" class="flex items-center gap-2 flex-wrap"></div>
                <div class="relative w-full sm:w-auto">
                    <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" style="font-size: 15px;" aria-hidden="true"></i>
                    <input id="search-input" type="text" placeholder="Search by name or type..."
                        class="input pl-9 sm:w-64">
                </div>
            </div>

            <div id="events-list" class="flex flex-col gap-3"></div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="card p-4">
                <h2 class="card-title mb-3">Events by status</h2>
                <div class="flex items-center gap-4">
                    <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                        <canvas id="statusChart" role="img" aria-label="Doughnut chart of events by status"></canvas>
                    </div>
                    <div id="status-legend" class="flex-1 space-y-2 text-sm"></div>
                </div>
            </div>

            <div class="card p-4">
                <h2 class="card-title mb-3">Events by type</h2>
                <div id="type-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="card p-4">
                <h2 class="card-title mb-3">Recent activity</h2>
                <div id="activity-timeline" class="space-y-4 text-xs"></div>
            </div>
        </div>
    </div>

    <div id="create-event-modal" class="hidden modal-backdrop">
        <div class="modal max-w-2xl">
            <div class="modal-header">
                <div>
                    <h2 class="modal-title">Create a disaster event</h2>
                    <p class="text-xs text-gray-500">This becomes selectable for evacuee registration, reports, alerts, and predictions.</p>
                </div>
                <button type="button" id="event-modal-close" class="btn-icon -mr-1.5" aria-label="Close">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="event-modal-errors" class="hidden callout callout-danger mx-5 mt-4"></div>

            <form id="event-form" class="flex flex-col gap-4 p-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="label">Event name</label>
                        <input type="text" id="ev-name" required placeholder="e.g. Typhoon Rolly 2026" class="input">
                    </div>
                    <div>
                        <label class="label">Event type</label>
                        <select id="ev-event_type" required class="input">
                            <option value="typhoon">Typhoon</option>
                            <option value="flood">Flood</option>
                            <option value="volcanic_eruption">Volcanic eruption</option>
                            <option value="earthquake">Earthquake</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select id="ev-status" required class="input">
                            <option value="monitoring">Monitoring</option>
                            <option value="active">Active</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div id="field-ev-typhoon_category">
                        <label class="label">Typhoon category (optional)</label>
                        <input type="text" id="ev-typhoon_category" placeholder="e.g. Signal No. 2" class="input">
                    </div>
                    <div id="field-ev-alert_level">
                        <label class="label">Alert level (optional)</label>
                        <input type="text" id="ev-alert_level" placeholder="e.g. Alert Level 3" class="input">
                    </div>
                    <div id="field-ev-rainfall_mm">
                        <label class="label">Rainfall, mm (optional)</label>
                        <input type="number" step="0.1" id="ev-rainfall_mm" class="input">
                    </div>
                    <div id="field-ev-max_wind_speed_kph">
                        <label class="label">Max wind speed, kph (optional)</label>
                        <input type="number" step="0.1" id="ev-max_wind_speed_kph" class="input">
                    </div>
                    <div>
                        <label class="label">Start date</label>
                        <input type="date" id="ev-start_date" required class="input">
                    </div>
                    <div>
                        <label class="label">End date (optional)</label>
                        <input type="date" id="ev-end_date" class="input">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="label">Description (optional)</label>
                        <textarea id="ev-description" rows="2" class="input"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" id="event-modal-cancel" class="btn btn-secondary">
                        Cancel
                    </button>
                    <button type="submit" id="event-submit-btn" class="btn btn-primary">
                        Create event
                    </button>
                </div>
            </form>
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
        active: 'badge-success',
        monitoring: 'badge-warning',
        closed: 'badge-neutral',
    };
    // Status series colors -- see docs/design-system.md, chart palette.
    const statusDots = { active: '#15803D', monitoring: '#EDA100', closed: '#9CA3AF' };
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
        const confirmed = await Ui.confirm({
            title: 'Close this event?',
            message: 'Staff can no longer add evacuees to it. Its reports and predictions stay available.',
            confirmLabel: 'Close event',
        });
        if (! confirmed) return;
        try {
            await Api.request(`/evacuation-events/${id}`, { method: 'PATCH', body: JSON.stringify({ status: 'closed' }) });
            loadEvents();
            Ui.toast('Event closed');
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
                        <span class="badge ${statusColors[event.status] ?? 'badge-neutral'}">${statusLabels[event.status] ?? event.status}</span>
                        ${event.typhoon_category ? `<span class="badge badge-info">${event.typhoon_category}</span>` : ''}
                        ${event.alert_level ? `<span class="badge badge-danger">${event.alert_level}</span>` : ''}
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">${event.name}</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        ${eventTypeLabels[event.event_type] ?? event.event_type} &middot; Started ${event.start_date}${event.end_date ? ' &middot; Ended ' + event.end_date : ''}
                    </p>
                </div>
                <a href="/evacuation-events/${event.id}/edit" class="btn btn-secondary btn-sm shrink-0">View / edit details</a>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Rainfall</p>
                    <p class="text-lg font-semibold text-gray-900">${event.rainfall_mm !== null ? event.rainfall_mm + ' mm' : '&mdash;'}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Wind speed</p>
                    <p class="text-lg font-semibold text-gray-900">${event.max_wind_speed_kph !== null ? event.max_wind_speed_kph + ' kph' : '&mdash;'}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Families affected</p>
                    <p class="text-lg font-semibold text-gray-900">${event.total_families_displaced}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-600 mb-1">Persons displaced</p>
                    <p class="text-lg font-semibold text-gray-900">${event.total_persons_displaced}</p>
                </div>
            </div>

            <div id="hero-derived" class="grid grid-cols-2 gap-4 mb-5"></div>

            <div>
                <p class="text-xs font-medium text-gray-600 mb-2">Status progress</p>
                <div class="flex items-center">
                    ${steps.map((step, i) => `
                        <div class="flex items-center ${i < steps.length - 1 ? 'flex-1' : ''}">
                            <div class="flex flex-col items-center">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center text-white text-xs"
                                    style="background: ${i <= currentIndex ? statusDots[step] : '#E5E7EB'}">
                                    ${i < currentIndex ? '<i class=\"ti ti-check\" style=\"font-size:13px;\"></i>' : ''}
                                </span>
                                <span class="text-xs mt-1 ${i <= currentIndex ? 'text-gray-900 font-medium' : 'text-gray-500'}">${statusLabels[step]}</span>
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
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-600">Affected barangays</p>
                    <p class="text-base font-semibold text-gray-900">${barangays.size}</p>
                </div>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <p class="text-xs font-medium text-gray-600">Evacuation centers in use</p>
                    <p class="text-base font-semibold text-gray-900">${centers.size}</p>
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
                class="status-tab chip ${statusFilter === key ? 'chip-active' : ''}" aria-pressed="${statusFilter === key}">
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
            ? '<p class="card text-gray-500 text-sm text-center py-16">No disaster events match this filter.</p>'
            : filtered.map((e) => `
                <div class="card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3 min-w-0">
                            <div class="icon-chip">
                                <i class="ti ti-alert-triangle" style="font-size: 18px;" aria-hidden="true"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1">
                                    <p class="font-semibold text-sm text-gray-900">${e.name}</p>
                                    <span class="badge ${statusColors[e.status] ?? 'badge-neutral'}">${statusLabels[e.status] ?? e.status}</span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    ${eventTypeLabels[e.event_type] ?? e.event_type}
                                    ${e.typhoon_category ? ' &middot; ' + e.typhoon_category : ''}
                                    ${e.rainfall_mm !== null ? ` &middot; ${e.rainfall_mm}mm rainfall` : ''}
                                    ${e.max_wind_speed_kph !== null ? ` &middot; ${e.max_wind_speed_kph}kph wind` : ''}
                                </p>
                                <p class="text-xs text-gray-500 mt-1">Started ${e.start_date}${e.end_date ? ' &middot; Ended ' + e.end_date : ''}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0 tabular-nums">
                            <p class="text-sm"><span class="font-semibold text-gray-900">${e.total_families_displaced}</span> <span class="text-gray-500 text-xs">families</span></p>
                            <p class="text-sm"><span class="font-semibold text-gray-900">${e.total_persons_displaced}</span> <span class="text-gray-500 text-xs">persons</span></p>
                        </div>
                    </div>
                    ${canManage && e.status !== 'closed' ? `
                        <div class="border-t border-gray-100 mt-3 pt-3 flex gap-2">
                            <a href="/evacuation-events/${e.id}/edit" class="btn btn-secondary btn-sm">Edit</a>
                            <button type="button" onclick="closeEvent(${e.id})" class="btn btn-danger-secondary btn-sm">Close event</button>
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
                <span class="flex items-center gap-1.5 text-gray-700">
                    <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${statusDots[s.key]}"></span>${s.label}
                </span>
                <span class="font-medium text-gray-900 tabular-nums">${s.count} <span class="text-gray-500 font-normal">(${Math.round(s.count / statusTotal * 100)}%)</span></span>
            </div>`).join('');

        if (statusChartInstance) statusChartInstance.destroy();
        statusChartInstance = new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statusCounts.map((s) => s.label),
                datasets: [{ data: statusCounts.map((s) => s.count), backgroundColor: statusCounts.map((s) => statusDots[s.key]), borderColor: '#FFFFFF', borderWidth: 2 }],
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
                        <span class="font-medium text-gray-900 tabular-nums">${count}</span>
                    </div>
                    <div class="meter">
                        <div class="meter-fill bg-brand" style="width:${count / maxType * 100}%"></div>
                    </div>
                </div>`;
        }).join('');

        // Recent activity, reused from the same audit trail as the other
        // pages, filtered to this module's actions.
        const recent = allLogs.filter((l) => l.action === 'evacuation_event.created' || l.action === 'evacuation_event.closed').slice(0, 6);
        document.getElementById('activity-timeline').innerHTML = recent.length ? recent.map((l) => `
            <div class="flex gap-2.5">
                <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-brand" aria-hidden="true"></span>
                <div class="min-w-0">
                    <p class="text-gray-900 font-medium truncate">${l.description ?? l.action}</p>
                    <p class="text-gray-500">${l.user?.name ?? 'System'} &middot; ${new Date(l.created_at).toLocaleString()}</p>
                </div>
            </div>`).join('') : '<p class="text-gray-500">No activity recorded yet.</p>';
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

    // --- Create-event modal -------------------------------------------
    // /evacuation-events/create still exists untouched as a fallback (and
    // is still reused for editing an existing event -- only the create
    // flow moved to a modal here).

    // Same type-to-field mapping and clear-on-hide behavior as
    // evacuation-events/create.blade.php's updateTypeFields() -- this
    // modal is a separate, duplicate form (not the standalone create/edit
    // page), so it needs its own copy scoped to the ev- prefixed IDs.
    const EVENT_TYPE_FIELDS = {
        typhoon: ['ev-typhoon_category', 'ev-max_wind_speed_kph', 'ev-rainfall_mm'],
        flood: ['ev-rainfall_mm'],
        volcanic_eruption: ['ev-alert_level'],
        earthquake: [],
        other: [],
    };
    const ALL_EVENT_TYPE_SPECIFIC_FIELDS = ['ev-typhoon_category', 'ev-alert_level', 'ev-rainfall_mm', 'ev-max_wind_speed_kph'];

    function updateEventTypeFields() {
        const visible = EVENT_TYPE_FIELDS[document.getElementById('ev-event_type').value] || [];

        ALL_EVENT_TYPE_SPECIFIC_FIELDS.forEach((field) => {
            const isVisible = visible.includes(field);
            document.getElementById(`field-${field}`).style.display = isVisible ? 'block' : 'none';
            if (! isVisible) {
                document.getElementById(field).value = '';
            }
        });
    }

    document.getElementById('ev-event_type').addEventListener('change', updateEventTypeFields);

    function openEventModal() {
        document.getElementById('event-modal-errors').classList.add('hidden');
        clearFormErrors('event-form');
        document.getElementById('event-form').reset();
        updateEventTypeFields(); // after reset(), so it matches the now-default "Typhoon" selection
        document.getElementById('create-event-modal').classList.remove('hidden');
        document.getElementById('create-event-modal').classList.add('flex');
    }

    function closeEventModal() {
        document.getElementById('create-event-modal').classList.add('hidden');
        document.getElementById('create-event-modal').classList.remove('flex');
    }

    document.getElementById('add-event-btn').addEventListener('click', openEventModal);
    document.getElementById('event-modal-close').addEventListener('click', closeEventModal);
    document.getElementById('event-modal-cancel').addEventListener('click', closeEventModal);

    document.getElementById('create-event-modal').addEventListener('click', (e) => {
        if (e.target.id === 'create-event-modal') closeEventModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('create-event-modal').classList.contains('hidden')) {
            closeEventModal();
        }
    });

    document.getElementById('event-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            name: document.getElementById('ev-name').value,
            event_type: document.getElementById('ev-event_type').value,
            status: document.getElementById('ev-status').value,
            typhoon_category: document.getElementById('ev-typhoon_category').value || null,
            alert_level: document.getElementById('ev-alert_level').value || null,
            rainfall_mm: document.getElementById('ev-rainfall_mm').value || null,
            max_wind_speed_kph: document.getElementById('ev-max_wind_speed_kph').value || null,
            start_date: document.getElementById('ev-start_date').value,
            end_date: document.getElementById('ev-end_date').value || null,
            description: document.getElementById('ev-description').value || null,
        };

        const button = document.getElementById('event-submit-btn');
        button.disabled = true;
        button.textContent = 'Creating...';

        try {
            await Api.post('/evacuation-events', payload);
            closeEventModal();
            Ui.toast('Event created');
            await loadEvents(); // refresh in place, no full page reload
        } catch (error) {
            showFormErrors(error, { form: 'event-form', box: 'event-modal-errors', prefix: 'ev-' });
        } finally {
            button.disabled = false;
            button.textContent = 'Create event';
        }
    });
</script>
@endsection
