@extends('layouts.app')

@section('title', 'Dashboard')
@section('nav-dashboard', 'active')

@section('content')
    <div class="page-header">
        <div class="min-w-0">
            <h1 class="page-title" id="dashboard-greeting">Dashboard</h1>
            <p class="page-subtitle">Here's what's registered so far.</p>
        </div>
    </div>

    <div class="stat-strip grid-cols-2 lg:grid-cols-4 mb-6">
        <div class="stat">
            <p class="stat-label">Total evacuees</p>
            <p id="stat-evacuees" class="stat-value">&mdash;</p>
            <p class="stat-note">Currently displaced, active event(s)</p>
        </div>
        <div class="stat">
            <p class="stat-label">Active centers</p>
            <p id="stat-centers" class="stat-value">&mdash;</p>
            <p class="stat-note">Facilities in use</p>
        </div>
        <div class="stat">
            <p class="stat-label">Predicted influx</p>
            <p id="stat-predicted" class="stat-value">&mdash;</p>
            <p class="stat-note">AI forecast, latest</p>
        </div>
        {{-- The one figure that's a risk signal, not a neutral count:
            setAtRiskTile() below adds .stat-alert (red dot + red figure)
            only once there's a real problem, so 0 stays calm. --}}
        <div id="at-risk-card" class="stat">
            <p class="stat-label">Centers at risk</p>
            <p id="stat-at-risk" class="stat-value">&mdash;</p>
            <p class="stat-note">Near or above capacity</p>
        </div>
    </div>

    {{-- Hidden per CSWDO: outside_center registration has no real
        operational use for them -- see families/index.blade.php's own
        header button for the full reasoning. Route/page stay fully intact
        and reachable directly; this is a UI visibility change only. --}}
    <a href="/families/create"
        class="hidden btn btn-primary mb-6">
        + Register a family
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="card p-4">
            <div class="card-header">
                <h2 class="card-title">Recent evacuation events</h2>
                <a href="/evacuation-events" class="link text-sm">View all</a>
            </div>
            <div id="recent-events-list" class="flex flex-col gap-2 text-sm"></div>
        </div>

        <div class="card p-4">
            <div class="card-header">
                <h2 class="card-title">Evacuation centers overview</h2>
                <a href="/evacuation-centers" class="link text-sm">View all</a>
            </div>
            <div class="flex items-center gap-4 mb-3">
                <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                    <canvas id="centersChart" role="img" aria-label="Doughnut chart of evacuation center utilization"></canvas>
                </div>
                <div id="centers-legend" class="flex-1 space-y-1.5 text-sm"></div>
            </div>
            <div id="centers-banner" class="callout callout-info flex items-center gap-2 text-xs">
                <i class="ti ti-info-circle shrink-0" style="font-size: 15px;" aria-hidden="true"></i>
                <span id="centers-banner-text">Loading...</span>
            </div>
        </div>

        <div class="card p-4">
            <div class="card-header">
                <h2 class="card-title">Alerts</h2>
                <a href="/alerts" class="link text-sm">View all</a>
            </div>
            <div id="alerts-summary" class="mb-3"></div>
            {{-- Severity is carried by the tint + icon + label; the count
                itself stays in ink. --}}
            <div class="grid grid-cols-2 gap-2">
                <div class="rounded-lg border border-orange-200 bg-orange-50 px-3 py-2">
                    <p id="stat-advisories" class="text-lg font-semibold text-gray-900">&mdash;</p>
                    <p class="flex items-center gap-1 text-xs font-medium text-orange-800">
                        <i class="ti ti-speakerphone" style="font-size: 13px;" aria-hidden="true"></i> Advisories
                    </p>
                </div>
                <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2">
                    <p id="stat-critical" class="text-lg font-semibold text-gray-900">&mdash;</p>
                    <p class="flex items-center gap-1 text-xs font-medium text-red-700">
                        <i class="ti ti-alert-triangle" style="font-size: 13px;" aria-hidden="true"></i> Critical alerts
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 card p-4">
            <div class="card-header justify-start">
                <h2 class="card-title">Predicted influx</h2>
                <span class="badge badge-neutral">AI forecast</span>
            </div>
            <div id="predicted-influx-content"></div>
        </div>

        <div class="card p-4">
            <h2 class="card-title mb-3">Quick actions</h2>
            <div class="grid grid-cols-2 gap-2">
                <a href="/evacuation-events/create" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-50 transition-colors">
                    <i class="ti ti-calendar-plus text-brand-700" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-sm text-gray-700">Add evacuation event</span>
                </a>
                <a href="/evacuation-centers" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-50 transition-colors">
                    <i class="ti ti-building text-brand-700" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-sm text-gray-700">Manage centers</span>
                </a>
                <a href="/gis-map" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-50 transition-colors">
                    <i class="ti ti-map text-brand-700" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-sm text-gray-700">View GIS map</span>
                </a>
                <a href="/reports" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-50 transition-colors">
                    <i class="ti ti-file-report text-brand-700" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-sm text-gray-700">Generate report</span>
                </a>
            </div>
        </div>
    </div>

    <div id="chart-card" class="hidden card p-4">
        <h2 class="card-title mb-3">Persons displaced by event</h2>
        <div style="position: relative; width: 100%; height: 220px;">
            <canvas id="eventsChart" role="img" aria-label="Bar chart of persons displaced per disaster event">Loading chart data</canvas>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    // currentUser is already declared as a global by layouts/app.blade.php's
    // own inline script, which runs before this one (it sits earlier in the
    // document, ahead of @yield('scripts')) -- redeclaring it here with
    // `const` threw "Identifier 'currentUser' has already been declared",
    // a SyntaxError that silently broke this entire script block.
    if (currentUser) {
        document.getElementById('dashboard-greeting').textContent = `Welcome back, ${currentUser.name}!`;
    }

    const eventTypeIcons = {
        typhoon: 'ti-wind', flood: 'ti-droplet', volcanic_eruption: 'ti-mountain',
        earthquake: 'ti-activity', other: 'ti-alert-triangle',
    };
    const eventStatusColors = {
        active: 'badge badge-success', monitoring: 'badge badge-warning', closed: 'badge badge-neutral',
    };
    const eventStatusLabels = { active: 'Active', monitoring: 'Monitoring', closed: 'Closed' };

    // Shared by both the success and error paths below (error treats it as
    // 0, same as every other stat on this page) so the tile can never end
    // up red from a stale previous load while showing "0".
    function setAtRiskTile(count) {
        document.getElementById('stat-at-risk').textContent = count;
        document.getElementById('at-risk-card').classList.toggle('stat-alert', count > 0);
    }

    (async () => {
        try {
            // /families/stats defaults to CURRENT state only (non-closed
            // events) and returns real counts via direct queries, not a
            // paginated array -- total_persons is a genuine evacuee/person
            // count, not a family/household count (this card previously
            // showed the family total under a "Total evacuees" label).
            const result = await Api.get('/families/stats');
            document.getElementById('stat-evacuees').textContent = result.data.total_persons;
        } catch (error) {
            document.getElementById('stat-evacuees').textContent = '0';
        }
    })();

    (async () => {
        try {
            // Reuses /public/evacuation-centers rather than the staff
            // /evacuation-centers lookup, specifically because the public
            // endpoint includes occupancy_percent (needed for "at risk"
            // below) while the staff lookup only returns id/name/status --
            // one call instead of fetching centers twice.
            const result = await Api.get('/public/evacuation-centers');
            const centers = result.data;
            const activeCount = centers.filter((c) => c.status === 'active').length;
            document.getElementById('stat-centers').textContent = `${activeCount} / ${centers.length}`;

            const atRiskCount = centers.filter((c) => c.occupancy_percent !== null && c.occupancy_percent >= 90).length;
            setAtRiskTile(atRiskCount);

            // Evacuation centers overview donut -- three mutually exclusive
            // buckets derived from real occupancy fields already above.
            const inUse = centers.filter((c) => c.current_occupancy > 0 && (c.occupancy_percent ?? 0) < 90).length;
            const available = centers.length - inUse - atRiskCount;

            document.getElementById('centers-legend').innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-gray-700"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#15803D"></span>In use</span>
                    <span class="font-medium text-gray-900 tabular-nums">${inUse} <span class="text-gray-500 font-normal">(${centers.length ? Math.round(inUse / centers.length * 100) : 0}%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-gray-700"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#2563EB"></span>Available</span>
                    <span class="font-medium text-gray-900 tabular-nums">${available} <span class="text-gray-500 font-normal">(${centers.length ? Math.round(available / centers.length * 100) : 0}%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-gray-700"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#DC2626"></span>At risk</span>
                    <span class="font-medium text-gray-900 tabular-nums">${atRiskCount} <span class="text-gray-500 font-normal">(${centers.length ? Math.round(atRiskCount / centers.length * 100) : 0}%)</span></span>
                </div>`;

            new Chart(document.getElementById('centersChart'), {
                type: 'doughnut',
                data: {
                    labels: ['In use', 'Available', 'At risk'],
                    datasets: [{ data: [inUse, available, atRiskCount], backgroundColor: ['#15803D', '#2563EB', '#DC2626'], borderColor: '#FFFFFF', borderWidth: 2 }],
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
            });

            document.getElementById('centers-banner-text').textContent = centers.length === 0
                ? 'No evacuation centers registered yet.'
                : available > 0
                    ? `${available} evacuation center(s) available.`
                    : 'No centers currently available -- all in use or at risk.';
        } catch (error) {
            document.getElementById('stat-centers').textContent = '0';
            setAtRiskTile(0);
            document.getElementById('centers-banner-text').textContent = 'Could not load center data.';
        }
    })();

    (async () => {
        try {
            const result = await Api.get('/predictions?per_page=1');
            const latest = result.data.data[0];
            document.getElementById('stat-predicted').textContent = latest ? latest.predicted_evacuees : 'None yet';

            const box = document.getElementById('predicted-influx-content');
            if (! latest) {
                box.innerHTML = `
                    <div class="flex flex-col items-center text-center py-8">
                        <i class="ti ti-cloud-off text-gray-300 mb-2" style="font-size: 32px;" aria-hidden="true"></i>
                        <p class="text-sm font-medium text-gray-700">No forecast data available yet.</p>
                        <p class="text-sm text-gray-500 mt-1">Forecast will appear here when available.</p>
                        <a href="/predictive-analytics" class="btn btn-secondary btn-sm mt-3">Generate a forecast</a>
                    </div>`;
                return;
            }

            box.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3">
                    <div>
                        <p class="text-xs font-medium text-gray-600">Predicted evacuees</p>
                        <p class="text-xl font-semibold text-gray-900">${latest.predicted_evacuees}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600">Predicted occupancy</p>
                        <p class="text-xl font-semibold text-gray-900">${latest.predicted_center_occupancy ?? '—'}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-600">Est. resource cost</p>
                        <p class="text-xl font-semibold text-gray-900">₱${Number(latest.predicted_resources_needed ?? 0).toLocaleString()}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mb-1">
                    Input: ${latest.input_payload.rainfall_mm}mm rainfall, ${latest.input_payload.wind_speed_kph}kph wind &middot;
                    generated ${new Date(latest.generated_at).toLocaleString()} &middot; ${latest.model_used}
                </p>
                <a href="/predictive-analytics" class="link text-sm">View full analytics &amp; AI recommendations</a>`;
        } catch (error) {
            document.getElementById('stat-predicted').textContent = 'None yet';
            document.getElementById('predicted-influx-content').innerHTML =
                '<p class="text-sm text-gray-500 text-center py-8">Could not load forecast data.</p>';
        }
    })();

    (async () => {
        try {
            const result = await Api.get('/alerts?per_page=20');
            const alerts = result.data.data;

            document.getElementById('stat-advisories').textContent = alerts.filter((a) => a.severity === 'advisory').length;
            document.getElementById('stat-critical').textContent = alerts.filter((a) => a.severity === 'mandatory').length;

            const summary = document.getElementById('alerts-summary');
            if (alerts.length === 0) {
                summary.innerHTML = `
                    <div class="flex items-start gap-2 bg-green-50 border border-green-200 rounded-lg p-3">
                        <i class="ti ti-circle-check text-green-700 shrink-0" style="font-size: 18px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">No active alerts</p>
                            <p class="text-xs text-green-700">There are currently no active alerts in Ligao City.</p>
                        </div>
                    </div>`;
                return;
            }

            // Severity color families mirror alerts/index.blade.php's own
            // severityStyles (mandatory=red, advisory=orange, info=blue,
            // all_clear=green) -- keep both in sync if severities ever
            // change. Previously every severity rendered in the same
            // neutral gray box, so a mandatory evacuation order looked no
            // different here from a routine advisory; the two small tiles
            // below already color-code advisories/critical counts, this
            // card just wasn't using the same vocabulary for the one alert
            // it actually shows.
            const severityTint = {
                mandatory: { bg: 'bg-red-50 border-red-200', icon: 'ti-alert-triangle', iconColor: 'text-red-700', title: 'text-red-900', body: 'text-red-800', badge: 'badge-danger', label: 'Mandatory' },
                advisory: { bg: 'bg-orange-50 border-orange-200', icon: 'ti-speakerphone', iconColor: 'text-orange-700', title: 'text-orange-900', body: 'text-orange-800', badge: 'badge-advisory', label: 'Advisory' },
                info: { bg: 'bg-blue-50 border-blue-200', icon: 'ti-info-circle', iconColor: 'text-blue-700', title: 'text-blue-900', body: 'text-blue-800', badge: 'badge-info', label: 'Info' },
                all_clear: { bg: 'bg-green-50 border-green-200', icon: 'ti-circle-check', iconColor: 'text-green-700', title: 'text-green-900', body: 'text-green-800', badge: 'badge-success', label: 'All clear' },
            };

            const latest = alerts[0];
            const sev = severityTint[latest.severity] ?? severityTint.info;
            summary.innerHTML = `
                <div class="flex items-start gap-2 ${sev.bg} border rounded-lg p-3">
                    <i class="ti ${sev.icon} ${sev.iconColor} shrink-0 mt-0.5" style="font-size: 18px;" aria-hidden="true"></i>
                    <div class="min-w-0">
                        <span class="badge ${sev.badge}">${sev.label}</span>
                        <p class="text-sm font-medium ${sev.title} truncate mt-1">${latest.title}</p>
                        <p class="text-xs ${sev.body}">${new Date(latest.created_at).toLocaleString()}</p>
                    </div>
                </div>`;
        } catch (error) {
            document.getElementById('stat-advisories').textContent = '0';
            document.getElementById('stat-critical').textContent = '0';
        }
    })();

    (async () => {
        try {
            const result = await Api.get('/evacuation-events');
            const events = result.data;

            document.getElementById('recent-events-list').innerHTML = events.length === 0
                ? '<p class="text-gray-500 text-sm text-center py-6">No disaster events yet.</p>'
                : events.slice(0, 3).map((e) => `
                    <a href="/evacuation-events" class="flex items-center gap-3 hover:bg-gray-50 rounded-lg -mx-2 px-2 py-1.5 transition-colors">
                        <div class="icon-chip">
                            <i class="ti ${eventTypeIcons[e.event_type] ?? 'ti-alert-triangle'}" style="font-size: 17px;" aria-hidden="true"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate">${e.name}</p>
                            <p class="text-xs text-gray-500">${e.start_date}</p>
                        </div>
                        <span class="shrink-0 ${eventStatusColors[e.status] ?? 'badge badge-neutral'}">${eventStatusLabels[e.status] ?? e.status}</span>
                    </a>`).join('');

            const withDisplaced = events.filter((e) => e.total_persons_displaced > 0);
            if (withDisplaced.length === 0) return;

            document.getElementById('chart-card').classList.remove('hidden');

            new Chart(document.getElementById('eventsChart'), {
                type: 'bar',
                data: {
                    labels: withDisplaced.map((e) => e.name),
                    datasets: [{
                        label: 'Persons displaced',
                        data: withDisplaced.map((e) => e.total_persons_displaced),
                        backgroundColor: '#2563EB',
                        borderRadius: 4,
                        maxBarThickness: 40,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `Evacuees: ${ctx.parsed.y.toLocaleString()}`,
                            },
                        },
                    },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#E5E7EB' } },
                        x: { grid: { display: false } },
                    },
                },
            });
        } catch (error) {
            document.getElementById('recent-events-list').innerHTML =
                '<p class="text-gray-500 text-sm text-center py-6">Could not load events.</p>';
        }
    })();
</script>
@endsection
