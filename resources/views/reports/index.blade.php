@extends('layouts.app')

@section('title', 'DROMIC reports')
@section('nav-reports', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1">DROMIC reports</h1>
    <p class="text-sm text-gray-500 mb-6">Generate official-format reports directly from registered data.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div class="grid grid-cols-2 gap-4 mb-6">
        <div id="region-v-card" class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-md bg-purple-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-file-report text-purple-500" style="font-size: 15px;" aria-hidden="true"></i>
                </div>
                <p class="text-sm font-medium">DROMIC Region V report</p>
            </div>
            <p class="text-xs text-gray-500 mb-3">Full consolidated report for a disaster event, scoped to Ligao City.</p>
            <select id="region-v-event" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3"></select>
            <button id="generate-region-v" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2 w-full">
                Generate
            </button>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center gap-2 mb-1">
                <div class="w-7 h-7 rounded-md bg-purple-50 flex items-center justify-center shrink-0">
                    <i class="ti ti-clipboard-list text-purple-500" style="font-size: 15px;" aria-hidden="true"></i>
                </div>
                <p class="text-sm font-medium">EC Information Board</p>
            </div>
            <p class="text-xs text-gray-500 mb-3">Single-page board for one evacuation center.</p>
            <select id="ec-board-event" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-2"></select>
            <select id="ec-board-center" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm mb-3"></select>
            <button id="generate-ec-board" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2 w-full">
                Generate
            </button>
        </div>
    </div>

    <div id="report-ready-banner" class="hidden bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i class="ti ti-circle-check text-green-600" style="font-size: 20px;" aria-hidden="true"></i>
            <div>
                <p class="text-sm font-medium text-green-800">Report ready</p>
                <p id="report-ready-detail" class="text-xs text-green-700"></p>
            </div>
        </div>
        <a id="report-ready-download" href="#" class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg px-4 py-2">
            <i class="ti ti-download" style="font-size: 15px;" aria-hidden="true"></i> Download .xlsx
        </a>
    </div>

    <div id="preview-card" class="hidden bg-white border border-gray-200 rounded-xl p-4 mb-6 overflow-x-auto">
        <p class="text-sm font-medium mb-3">Preview &mdash; barangay breakdown</p>
        <table class="w-full text-sm">
            <thead class="text-gray-400 text-xs uppercase">
                <tr>
                    <th class="text-left px-2 py-2">Barangay</th>
                    <th class="text-right px-2 py-2">Affected fam.</th>
                    <th class="text-right px-2 py-2">Persons</th>
                    <th class="text-left px-2 py-2">Evacuation center</th>
                    <th class="text-right px-2 py-2">4Ps</th>
                    <th class="text-right px-2 py-2">PWD</th>
                </tr>
            </thead>
            <tbody id="preview-tbody"></tbody>
        </table>
    </div>

    <div id="stats-row" class="hidden grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total reports</p>
                <p id="stat-total" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">All time</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-file-report text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">This month</p>
                <p id="stat-this-month" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Generated so far</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-calendar-stats text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #A855F7;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Persons affected</p>
                <p id="stat-persons" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">Live count, reported events</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                <i class="ti ti-users text-purple-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F97316;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Latest report</p>
                <p id="stat-latest" class="text-lg font-bold text-gray-800">&mdash;</p>
                <p id="stat-latest-date" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-orange-50 flex items-center justify-center shrink-0">
                <i class="ti ti-clock text-orange-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <p class="text-xs text-gray-400 mb-6 max-w-2xl">
        These reports eliminate manual data entry and arithmetic, but a few columns in the official
        DROMIC template aren't tracked by this system yet (e.g. "Child-Headed Family" isn't a recorded
        field) and are left blank rather than guessed. Review before submitting anywhere official.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <p class="text-sm font-medium text-gray-700">Previously generated reports</p>
                <div class="flex items-center gap-3">
                    <div class="relative">
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 15px;" aria-hidden="true"></i>
                        <input id="search-input" type="text" placeholder="Search by event or report type..."
                            class="border border-gray-300 rounded-lg pl-9 pr-3 py-2 text-sm w-56">
                    </div>
                    <select id="type-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">All report types</option>
                        <option value="dromic_region_v">DROMIC Region V</option>
                        <option value="ec_information_board">EC Information Board</option>
                        <option value="dromic_strandee">DROMIC Strandee</option>
                        <option value="dromic_cccm_idp">DROMIC CCCM/IDP</option>
                        <option value="custom">Custom report</option>
                    </select>
                </div>
            </div>

            <div id="reports-list" class="flex flex-col gap-2"></div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Reports by type</p>
                <div class="flex items-center gap-4">
                    <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                        <canvas id="typeChart" role="img" aria-label="Doughnut chart of reports by type"></canvas>
                    </div>
                    <div id="type-legend" class="flex-1 space-y-2 text-sm"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Reports generated (last 6 months)</p>
                <div id="month-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Recent activity</p>
                <div id="activity-timeline" class="space-y-4 text-xs"></div>
            </div>

            <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 flex gap-2.5">
                <i class="ti ti-info-circle text-blue-500 shrink-0" style="font-size: 16px;" aria-hidden="true"></i>
                <p class="text-xs text-blue-800">DROMIC reports are submitted to OCD Region V within 24 hours after data validation.</p>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
    const reportTypeLabels = {
        dromic_region_v: 'DROMIC Region V',
        ec_information_board: 'EC Information Board',
        dromic_strandee: 'DROMIC Strandee',
        dromic_cccm_idp: 'DROMIC CCCM/IDP',
        custom: 'Custom report',
    };
    const reportTypeColors = {
        dromic_region_v: '#3B82F6', ec_information_board: '#22C55E',
        dromic_strandee: '#F59E0B', dromic_cccm_idp: '#A855F7', custom: '#6B7280',
    };
    const reportTypeIcons = {
        dromic_region_v: 'ti-file-report', ec_information_board: 'ti-clipboard-list',
        dromic_strandee: 'ti-file-alert', dromic_cccm_idp: 'ti-file-description', custom: 'ti-file',
    };

    let allReports = [];
    let allLogs = [];
    let typeChartInstance = null;

    function renderReportsList() {
        const query = document.getElementById('search-input').value.trim().toLowerCase();
        const type = document.getElementById('type-filter').value;

        const filtered = allReports.filter((r) => {
            const label = (reportTypeLabels[r.report_type] ?? r.report_type).toLowerCase();
            const eventName = (r.evacuation_event?.name ?? '').toLowerCase();
            const matchesSearch = ! query || label.includes(query) || eventName.includes(query);
            const matchesType = ! type || r.report_type === type;
            return matchesSearch && matchesType;
        });

        document.getElementById('reports-list').innerHTML = filtered.length === 0
            ? '<p class="text-gray-400 text-sm text-center py-8 bg-white border border-gray-200 rounded-xl">No reports match this filter.</p>'
            : filtered.map((r) => `
                <div class="bg-white border border-gray-200 rounded-xl p-3 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center shrink-0">
                            <i class="ti ${reportTypeIcons[r.report_type] ?? 'ti-file'} text-purple-500" style="font-size: 16px;" aria-hidden="true"></i>
                        </div>
                        <div>
                            <p class="text-sm font-medium">${reportTypeLabels[r.report_type] ?? r.report_type}</p>
                            <p class="text-xs text-gray-500">
                                ${r.evacuation_event?.name ?? ''} &middot; by ${r.generated_by ?? 'Unknown'} &middot;
                                ${new Date(r.generated_at).toLocaleString()}
                            </p>
                        </div>
                    </div>
                    <a href="${r.download_url}" class="text-sm text-brand hover:underline"
                       onclick="downloadWithAuth(event, '${r.download_url}')">Download</a>
                </div>
            `).join('');
    }

    async function loadReportsList() {
        // /system-logs is administrator-only server-side, unlike /reports
        // which all three staff roles can read -- gate the call itself so
        // CSWD personnel/barangay officials don't get a 403 that breaks
        // the whole Promise.all just because they can't see the audit log.
        const user = Api.getUser();
        const isAdministrator = user && user.role === 'administrator';
        const [reportsResult, logsResult] = await Promise.all([
            Api.get('/reports?per_page=100'),
            isAdministrator ? Api.get('/system-logs?per_page=200') : Promise.resolve({ data: { data: [] } }),
        ]);
        allReports = reportsResult.data.data;
        allLogs = logsResult.data.data;

        renderReportsList();
        renderStats(reportsResult.data.meta?.total ?? allReports.length);
        renderSidebar();
    }

    function renderStats(total) {
        document.getElementById('stats-row').classList.remove('hidden');
        document.getElementById('stat-total').textContent = total;

        const now = new Date();
        const thisMonth = allReports.filter((r) => {
            const d = new Date(r.generated_at);
            return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
        }).length;
        document.getElementById('stat-this-month').textContent = thisMonth;

        if (allReports.length > 0) {
            const latest = [...allReports].sort((a, b) => new Date(b.generated_at) - new Date(a.generated_at))[0];
            document.getElementById('stat-latest').textContent = reportTypeLabels[latest.report_type] ?? latest.report_type;
            document.getElementById('stat-latest-date').textContent = new Date(latest.generated_at).toLocaleString();
        } else {
            document.getElementById('stat-latest').textContent = 'None yet';
        }
    }

    async function renderPersonsAffected() {
        try {
            const eventIds = [...new Set(allReports.map((r) => r.evacuation_event?.id).filter(Boolean))];
            if (eventIds.length === 0) {
                document.getElementById('stat-persons').textContent = '0';
                return;
            }
            const events = await Api.get('/evacuation-events');
            const total = events.data
                .filter((ev) => eventIds.includes(ev.id))
                .reduce((sum, ev) => sum + (ev.total_persons_displaced ?? 0), 0);
            document.getElementById('stat-persons').textContent = total.toLocaleString();
        } catch (error) {
            document.getElementById('stat-persons').textContent = '—';
        }
    }

    function renderSidebar() {
        // Reports by type
        const typeCounts = Object.keys(reportTypeLabels).map((key) => ({
            key, label: reportTypeLabels[key], count: allReports.filter((r) => r.report_type === key).length,
        })).filter((t) => t.count > 0);

        if (typeCounts.length === 0) {
            document.getElementById('type-legend').innerHTML = '<p class="text-gray-400">No reports yet.</p>';
        } else {
            const typeTotal = allReports.length || 1;
            document.getElementById('type-legend').innerHTML = typeCounts.map((t) => `
                <div class="flex items-center justify-between">
                    <span class="flex items-center gap-1.5 text-gray-600">
                        <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${reportTypeColors[t.key]}"></span>${t.label}
                    </span>
                    <span class="font-medium text-gray-800">${t.count} <span class="text-gray-400 font-normal">(${Math.round(t.count / typeTotal * 100)}%)</span></span>
                </div>`).join('');

            if (typeChartInstance) typeChartInstance.destroy();
            typeChartInstance = new Chart(document.getElementById('typeChart'), {
                type: 'doughnut',
                data: {
                    labels: typeCounts.map((t) => t.label),
                    datasets: [{ data: typeCounts.map((t) => t.count), backgroundColor: typeCounts.map((t) => reportTypeColors[t.key]), borderWidth: 0 }],
                },
                options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
            });
        }

        // Reports generated per month, last 6 months -- generation activity,
        // not a content-value trend (this system doesn't snapshot each
        // report's figures over time, so a persons-affected trend line
        // can't honestly be reconstructed from stored data).
        const months = [];
        const now = new Date();
        for (let i = 5; i >= 0; i--) {
            const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
            months.push({ year: d.getFullYear(), month: d.getMonth(), label: d.toLocaleDateString('en-US', { month: 'short' }) });
        }
        const monthCounts = months.map((m) => ({
            ...m,
            count: allReports.filter((r) => {
                const d = new Date(r.generated_at);
                return d.getFullYear() === m.year && d.getMonth() === m.month;
            }).length,
        }));
        const maxMonth = Math.max(...monthCounts.map((m) => m.count), 1);
        document.getElementById('month-distribution').innerHTML = monthCounts.map((m) => `
            <div>
                <div class="flex items-center justify-between mb-1">
                    <span class="text-gray-600">${m.label}</span>
                    <span class="font-medium text-gray-800">${m.count}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                    <div class="bg-blue-500 h-1.5 rounded-full" style="width:${m.count / maxMonth * 100}%"></div>
                </div>
            </div>`).join('');

        // Recent activity -- reuses the same audit trail as the Users and
        // Alerts pages, filtered to report-generation events.
        const recentReportLogs = allLogs.filter((l) => l.action === 'report.generated').slice(0, 6);
        document.getElementById('activity-timeline').innerHTML = recentReportLogs.length ? recentReportLogs.map((l) => `
            <div class="flex gap-2.5">
                <span class="w-2 h-2 rounded-full mt-1.5 shrink-0 bg-purple-400"></span>
                <div class="min-w-0">
                    <p class="text-gray-700 font-medium truncate">${l.description ?? l.action}</p>
                    <p class="text-gray-400">${l.user?.name ?? 'System'} &middot; ${new Date(l.created_at).toLocaleString()}</p>
                </div>
            </div>`).join('') : '<p class="text-gray-400">No activity recorded yet.</p>';

        renderPersonsAffected();
    }

    // The download route requires the same Bearer token as every other API
    // call -- a plain <a href> can't attach an Authorization header, so this
    // fetches the file with the token and triggers a save manually instead.
    async function downloadWithAuth(event, url) {
        event.preventDefault();
        try {
            const response = await fetch(url, {
                headers: { Authorization: `Bearer ${Api.getToken()}` },
            });
            if (! response.ok) throw new Error('Download failed.');
            const blob = await response.blob();
            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            const disposition = response.headers.get('Content-Disposition') || '';
            const match = disposition.match(/filename="?([^"]+)"?/);
            link.download = match ? match[1] : 'report.xlsx';
            link.click();
        } catch (error) {
            showFormErrors({ message: 'Could not download the report. Please try again.' });
        }
    }

    document.getElementById('search-input').addEventListener('input', renderReportsList);
    document.getElementById('type-filter').addEventListener('change', renderReportsList);

    (async () => {
        try {
            const user = Api.getUser();

            // DROMIC Region V stays admin/CSWD-only (city-wide report) --
            // hide the whole card for barangay officials rather than show
            // a Generate button that would just 403.
            if (user && user.role === 'barangay_official') {
                document.getElementById('region-v-card').classList.add('hidden');
            }

            const events = await Api.get('/evacuation-events');
            const eventOptions = events.data.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join('');
            document.getElementById('region-v-event').innerHTML = eventOptions;
            document.getElementById('ec-board-event').innerHTML = eventOptions;

            const centers = await Api.get('/evacuation-centers');
            // Barangay officials only see their own barangay's centers here
            // -- matches the same restriction now enforced server-side, so
            // they never even see an option that would be rejected.
            const visibleCenters = (user && user.role === 'barangay_official')
                ? centers.data.filter((c) => c.barangay_id === user.barangay?.id)
                : centers.data;
            document.getElementById('ec-board-center').innerHTML =
                visibleCenters.map((c) => `<option value="${c.id}">${c.name}</option>`).join('');

            await loadReportsList();
        } catch (error) {
            showFormErrors(error);
        }
    })();

    document.getElementById('generate-region-v').addEventListener('click', async (e) => {
        const button = e.target;
        const eventId = Number(document.getElementById('region-v-event').value);
        const eventName = document.getElementById('region-v-event').selectedOptions[0]?.textContent ?? '';
        button.disabled = true;
        button.textContent = 'Generating...';
        try {
            const [genResult, previewResult] = await Promise.all([
                Api.post('/reports/dromic-region-v', { evacuation_event_id: eventId }),
                Api.get(`/reports/dromic-region-v/preview?evacuation_event_id=${eventId}`),
            ]);

            document.getElementById('report-ready-banner').classList.remove('hidden');
            document.getElementById('report-ready-banner').classList.add('flex');
            document.getElementById('report-ready-detail').textContent =
                `DROMIC Region V report · ${eventName} · generated ${new Date().toLocaleString()}`;
            document.getElementById('report-ready-download').onclick = (evt) =>
                downloadWithAuth(evt, genResult.data.download_url);

            const rows = previewResult.data;
            document.getElementById('preview-card').classList.remove('hidden');
            document.getElementById('preview-tbody').innerHTML = rows.length === 0
                ? '<tr><td colspan="6" class="text-center text-gray-400 py-4">No registered families for this event yet.</td></tr>'
                : rows.map((r) => `
                    <tr class="border-t border-gray-100">
                        <td class="px-2 py-2">${r.barangay}</td>
                        <td class="px-2 py-2 text-right">${r.affected_families}</td>
                        <td class="px-2 py-2 text-right">${r.persons}</td>
                        <td class="px-2 py-2">${r.evacuation_center ?? '&mdash;'}</td>
                        <td class="px-2 py-2 text-right">${r.fourps_count}</td>
                        <td class="px-2 py-2 text-right">${r.pwd_count}</td>
                    </tr>
                `).join('');

            await loadReportsList();
        } catch (error) {
            showFormErrors(error);
        } finally {
            button.disabled = false;
            button.textContent = 'Generate';
        }
    });

    document.getElementById('generate-ec-board').addEventListener('click', async (e) => {
        const button = e.target;
        button.disabled = true;
        button.textContent = 'Generating...';
        try {
            await Api.post('/reports/ec-information-board', {
                evacuation_event_id: Number(document.getElementById('ec-board-event').value),
                evacuation_center_id: Number(document.getElementById('ec-board-center').value),
            });
            await loadReportsList();
        } catch (error) {
            showFormErrors(error);
        } finally {
            button.disabled = false;
            button.textContent = 'Generate';
        }
    });
</script>
@endsection
