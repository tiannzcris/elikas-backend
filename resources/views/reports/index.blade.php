@extends('layouts.app')

@section('title', 'DROMIC reports')
@section('nav-reports', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1">DROMIC reports</h1>
    <p class="text-sm text-gray-500 mb-6">Generate official-format reports directly from registered data.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div class="grid grid-cols-2 gap-4 mb-8">
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

    <div id="report-ready-banner" class="hidden bg-green-50 border border-green-200 rounded-xl p-4 mb-8 flex items-center justify-between">
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

    <div id="preview-card" class="hidden bg-white border border-gray-200 rounded-xl p-4 mb-8 overflow-x-auto">
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

    <p class="text-xs text-gray-400 mb-4 max-w-2xl">
        These reports eliminate manual data entry and arithmetic, but a few columns in the official
        DROMIC template aren't tracked by this system yet (e.g. "Child-Headed Family" isn't a recorded
        field) and are left blank rather than guessed. Review before submitting anywhere official.
    </p>

    <p class="text-sm font-medium text-gray-700 mb-3">Previously generated reports</p>
    <div id="reports-list" class="flex flex-col gap-2"></div>
@endsection

@section('scripts')
<script>
    const reportTypeLabels = {
        dromic_region_v: 'DROMIC Region V',
        ec_information_board: 'EC Information Board',
        dromic_strandee: 'DROMIC Strandee',
        dromic_cccm_idp: 'DROMIC CCCM/IDP',
        custom: 'Custom report',
    };

    async function loadReportsList() {
        const result = await Api.get('/reports');
        const reports = result.data.data;

        document.getElementById('reports-list').innerHTML = reports.length === 0
            ? '<p class="text-gray-400 text-sm text-center py-8">No reports generated yet.</p>'
            : reports.map((r) => `
                <div class="bg-white border border-gray-200 rounded-xl p-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium">${reportTypeLabels[r.report_type] ?? r.report_type}</p>
                        <p class="text-xs text-gray-500">
                            ${r.evacuation_event?.name ?? ''} · by ${r.generated_by ?? 'Unknown'} ·
                            ${new Date(r.generated_at).toLocaleString()}
                        </p>
                    </div>
                    <a href="${r.download_url}" class="text-sm text-brand hover:underline"
                       onclick="downloadWithAuth(event, '${r.download_url}')">Download</a>
                </div>
            `).join('');
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
