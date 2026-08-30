@extends('layouts.app')

@section('title', 'Dashboard')
@section('nav-dashboard', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1" id="dashboard-greeting">Dashboard</h1>
    <p class="text-sm text-gray-500 mb-6">Here's what's registered so far.</p>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total evacuees</p>
                <p id="stat-evacuees" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Currently displaced, active event(s)</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Active centers</p>
                <p id="stat-centers" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Facilities in use</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-building text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Predicted influx</p>
                <p id="stat-predicted" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">AI forecast, latest</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-trending-up text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #EF4444;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Centers at risk</p>
                <p id="stat-at-risk" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Near or above capacity</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <a href="/families/create"
        class="inline-block bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5 mb-6">
        + Register a family
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-700">Recent evacuation events</p>
                <a href="/evacuation-events" class="text-xs text-brand hover:underline">View all</a>
            </div>
            <div id="recent-events-list" class="flex flex-col gap-3 text-sm"></div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-700">Evacuation centers overview</p>
                <a href="/evacuation-centers" class="text-xs text-brand hover:underline">View all</a>
            </div>
            <div class="flex items-center gap-4 mb-3">
                <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                    <canvas id="centersChart" role="img" aria-label="Doughnut chart of evacuation center utilization"></canvas>
                </div>
                <div id="centers-legend" class="flex-1 space-y-1.5 text-sm"></div>
            </div>
            <div id="centers-banner" class="bg-blue-50 text-blue-700 text-xs rounded-lg px-3 py-2 flex items-center gap-1.5">
                <i class="ti ti-info-circle" style="font-size: 14px;" aria-hidden="true"></i>
                <span id="centers-banner-text">Loading...</span>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <p class="text-sm font-semibold text-gray-700">Alerts</p>
                <a href="/alerts" class="text-xs text-brand hover:underline">View all</a>
            </div>
            <div id="alerts-summary" class="mb-3"></div>
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-orange-50 rounded-lg px-3 py-2 text-center">
                    <p id="stat-advisories" class="text-lg font-bold text-orange-600">&mdash;</p>
                    <p class="text-xs text-orange-700">Advisories</p>
                </div>
                <div class="bg-red-50 rounded-lg px-3 py-2 text-center">
                    <p id="stat-critical" class="text-lg font-bold text-red-600">&mdash;</p>
                    <p class="text-xs text-red-700">Critical alerts</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-6 h-6 rounded-md bg-purple-500 flex items-center justify-center shrink-0">
                    <i class="ti ti-sparkles text-white" style="font-size: 13px;" aria-hidden="true"></i>
                </div>
                <p class="text-sm font-semibold text-gray-700">Predicted influx</p>
                <span class="text-xs px-2 py-0.5 rounded-lg bg-purple-50 text-purple-700">AI forecast</span>
            </div>
            <div id="predicted-influx-content"></div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm font-semibold text-gray-700 mb-3">Quick actions</p>
            <div class="grid grid-cols-2 gap-2">
                <a href="/evacuation-events/create" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-light">
                    <i class="ti ti-calendar-plus text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-xs text-gray-600">Add evacuation event</span>
                </a>
                <a href="/evacuation-centers" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-light">
                    <i class="ti ti-building text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-xs text-gray-600">Manage centers</span>
                </a>
                <a href="/gis-map" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-light">
                    <i class="ti ti-map text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-xs text-gray-600">View GIS map</span>
                </a>
                <a href="/reports" class="flex flex-col items-center text-center gap-1.5 border border-gray-200 rounded-lg p-3 hover:border-brand hover:bg-brand-light">
                    <i class="ti ti-file-report text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                    <span class="text-xs text-gray-600">Generate report</span>
                </a>
            </div>
        </div>
    </div>

    <div id="chart-card" class="hidden bg-white border border-gray-200 rounded-xl p-4">
        <div class="flex items-center gap-2 mb-2">
            <div class="w-6 h-6 rounded-md bg-blue-500 flex items-center justify-center">
                <i class="ti ti-chart-bar text-white" style="font-size: 14px;" aria-hidden="true"></i>
            </div>
            <p class="text-sm text-gray-600">Persons displaced by event</p>
        </div>
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
        active: 'bg-green-50 text-green-700', monitoring: 'bg-amber-50 text-amber-700', closed: 'bg-gray-100 text-gray-600',
    };
    const eventStatusLabels = { active: 'Active', monitoring: 'Monitoring', closed: 'Closed' };

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
            document.getElementById('stat-at-risk').textContent = atRiskCount;

            // Evacuation centers overview donut -- three mutually exclusive
            // buckets derived from real occupancy fields already above.
            const inUse = centers.filter((c) => c.current_occupancy > 0 && (c.occupancy_percent ?? 0) < 90).length;
            const available = centers.length - inUse - atRiskCount;

            document.getElementById('centers-legend').innerHTML = `
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block bg-green-500"></span>In use</span>
                    <span class="font-medium text-gray-800">${inUse} <span class="text-gray-400 font-normal">(${centers.length ? Math.round(inUse / centers.length * 100) : 0}%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block bg-blue-500"></span>Available</span>
                    <span class="font-medium text-gray-800">${available} <span class="text-gray-400 font-normal">(${centers.length ? Math.round(available / centers.length * 100) : 0}%)</span></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block bg-red-500"></span>At risk</span>
                    <span class="font-medium text-gray-800">${atRiskCount} <span class="text-gray-400 font-normal">(${centers.length ? Math.round(atRiskCount / centers.length * 100) : 0}%)</span></span>
                </div>`;

            new Chart(document.getElementById('centersChart'), {
                type: 'doughnut',
                data: {
                    labels: ['In use', 'Available', 'At risk'],
                    datasets: [{ data: [inUse, available, atRiskCount], backgroundColor: ['#22C55E', '#3B82F6', '#EF4444'], borderWidth: 0 }],
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
            document.getElementById('stat-at-risk').textContent = '0';
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
                        <p class="text-sm font-medium text-gray-500">No forecast data available yet.</p>
                        <p class="text-xs text-gray-400 mt-1">Forecast will appear here when available.</p>
                        <a href="/predictive-analytics" class="text-xs text-brand hover:underline mt-2">Generate a forecast &rarr;</a>
                    </div>`;
                return;
            }

            box.innerHTML = `
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-3">
                    <div>
                        <p class="text-xs text-gray-500">Predicted evacuees</p>
                        <p class="text-xl font-bold text-gray-800">${latest.predicted_evacuees}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Predicted occupancy</p>
                        <p class="text-xl font-bold text-gray-800">${latest.predicted_center_occupancy ?? '—'}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Est. resource cost</p>
                        <p class="text-xl font-bold text-gray-800">₱${Number(latest.predicted_resources_needed ?? 0).toLocaleString()}</p>
                    </div>
                </div>
                <p class="text-xs text-gray-400">
                    Input: ${latest.input_payload.rainfall_mm}mm rainfall, ${latest.input_payload.wind_speed_kph}kph wind &middot;
                    generated ${new Date(latest.generated_at).toLocaleString()} &middot; ${latest.model_used}
                </p>
                <a href="/predictive-analytics" class="text-xs text-brand hover:underline">View full analytics &amp; AI recommendations &rarr;</a>`;
        } catch (error) {
            document.getElementById('stat-predicted').textContent = 'None yet';
            document.getElementById('predicted-influx-content').innerHTML =
                '<p class="text-sm text-gray-400 text-center py-8">Could not load forecast data.</p>';
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
                    <div class="flex items-start gap-2 bg-green-50 rounded-lg p-3">
                        <i class="ti ti-circle-check text-green-600 shrink-0" style="font-size: 18px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-medium text-green-800">No active alerts</p>
                            <p class="text-xs text-green-700">There are currently no active alerts in Ligao City.</p>
                        </div>
                    </div>`;
                return;
            }

            const latest = alerts[0];
            summary.innerHTML = `
                <div class="flex items-start gap-2 bg-gray-50 rounded-lg p-3">
                    <i class="ti ti-speakerphone text-gray-500 shrink-0" style="font-size: 18px;" aria-hidden="true"></i>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800 truncate">${latest.title}</p>
                        <p class="text-xs text-gray-500">${new Date(latest.created_at).toLocaleString()}</p>
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
                ? '<p class="text-gray-400 text-sm text-center py-6">No disaster events yet.</p>'
                : events.slice(0, 3).map((e) => `
                    <a href="/evacuation-events" class="flex items-center gap-2.5 hover:bg-gray-50 rounded-lg -mx-1 px-1 py-1">
                        <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                            <i class="ti ${eventTypeIcons[e.event_type] ?? 'ti-alert-triangle'} text-blue-500" style="font-size: 17px;" aria-hidden="true"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-700 truncate">${e.name}</p>
                            <p class="text-xs text-gray-400">${e.start_date}</p>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-lg shrink-0 ${eventStatusColors[e.status] ?? ''}">${eventStatusLabels[e.status] ?? e.status}</span>
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
                        backgroundColor: '#2a78d6',
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
                        y: { beginAtZero: true, grid: { color: '#e1e0d9' } },
                        x: { grid: { display: false } },
                    },
                },
            });
        } catch (error) {
            document.getElementById('recent-events-list').innerHTML =
                '<p class="text-gray-400 text-sm text-center py-6">Could not load events.</p>';
        }
    })();
</script>
@endsection
