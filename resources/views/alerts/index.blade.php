@extends('layouts.app')

@section('title', 'Alerts')
@section('nav-alerts', 'active')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold mb-1">Alerts</h1>
            <p class="text-sm text-gray-500">Advisories sent to the dashboard, barangay officials, and evacuees.</p>
        </div>
        {{-- Opens the modal below instead of navigating to /alerts/create --
            that route/page still exists untouched (the topbar's global
            "Send emergency alert" button still links there directly, and
            it's the fallback if this page's JS ever fails to load). This
            is a pilot for one form only; the others aren't being converted
            yet. --}}
        <button type="button" id="send-alert-btn"
            class="hidden bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Send an alert
        </button>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div id="stats-row" class="hidden grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total alerts</p>
                <p id="stat-total" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p class="text-xs text-gray-400 italic mt-1">All time</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-speakerphone text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">SMS delivered</p>
                <p id="stat-delivered" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-delivered-pct" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-check text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #EF4444;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">SMS failed</p>
                <p id="stat-failed" class="text-2xl font-bold text-gray-800">&mdash;</p>
                <p id="stat-failed-pct" class="text-xs text-gray-400 italic mt-1">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-x text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 min-w-0">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div id="severity-tabs" class="flex items-center gap-2 flex-wrap"></div>
                <select id="type-filter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All types</option>
                    <option value="typhoon">Typhoon</option>
                    <option value="flood">Flood</option>
                    <option value="volcanic">Volcanic</option>
                    <option value="earthquake">Earthquake</option>
                    <option value="general_advisory">General advisory</option>
                </select>
            </div>

            <div id="empty-state" class="hidden text-center py-16 text-gray-400 text-sm bg-white border border-gray-200 rounded-xl">
                No alerts sent yet.
            </div>

            <div id="no-match-state" class="hidden text-center py-16 text-gray-400 text-sm bg-white border border-gray-200 rounded-xl">
                No alerts match this filter.
            </div>

            <div id="alerts-list" class="flex flex-col gap-3"></div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">SMS delivery summary</p>
                <div class="flex items-center gap-4">
                    <div style="position: relative; width: 96px; height: 96px;" class="shrink-0">
                        <canvas id="deliveryChart" role="img" aria-label="Doughnut chart of SMS delivery status"></canvas>
                    </div>
                    <div id="delivery-legend" class="flex-1 space-y-2 text-sm"></div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Alerts by severity</p>
                <div id="severity-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Alerts by type</p>
                <div id="type-distribution" class="space-y-2.5 text-xs"></div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Recent activity</p>
                <div id="activity-timeline" class="space-y-4 text-xs"></div>
            </div>
        </div>
    </div>

    <div id="send-alert-modal" class="hidden fixed inset-0 bg-black/50 z-50 items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <div>
                    <p class="font-semibold text-gray-800">Send an alert</p>
                    <p class="text-xs text-gray-500">Broadcasts instantly to the dashboard. SMS is optional and best-effort.</p>
                </div>
                <button type="button" id="alert-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="alert-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="alert-form" class="flex flex-col gap-4 p-5">
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Title</label>
                    <input type="text" id="alert-title" required maxlength="200"
                        placeholder="e.g. Typhoon Warning: Signal #2"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Message</label>
                    <textarea id="alert-message" required maxlength="1000" rows="4"
                        placeholder="e.g. Residents in low-lying areas of Barangay Pawa are advised to evacuate immediately. Proceed to the nearest evacuation center."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                    <p class="text-xs text-gray-400 mt-1">Plain language, no jargon -- this is what residents and barangay officials will actually read.</p>
                </div>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Urgency</label>
                        <select id="alert-severity" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="mandatory">Mandatory evacuation</option>
                            <option value="advisory" selected>Advisory</option>
                            <option value="info">Info</option>
                            <option value="all_clear">All clear</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Alert type</label>
                        <select id="alert-type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="typhoon">Typhoon</option>
                            <option value="flood">Flood</option>
                            <option value="volcanic">Volcanic</option>
                            <option value="earthquake">Earthquake</option>
                            <option value="general_advisory">General advisory</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Related disaster event (optional)</label>
                        <select id="alert-evacuation-event" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">None</option>
                        </select>
                    </div>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">SMS delivery (optional)</p>
                    <label class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                        <input type="checkbox" id="alert-notify-officials"> Notify barangay officials by SMS
                    </label>
                    <label class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                        <input type="checkbox" id="alert-notify-evacuees"> Notify registered evacuees by SMS (uses their contact number on file)
                    </label>
                    <div>
                        <label class="text-sm text-gray-600 block mb-1">Limit SMS to one barangay (optional)</label>
                        <select id="alert-barangay" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">All barangays</option>
                        </select>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">
                        If SMS isn't configured yet (no Semaphore account), the alert still sends to the live
                        dashboard — SMS attempts are just logged instead of actually delivered.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <button type="button" id="alert-modal-cancel" class="text-sm text-gray-600 border border-gray-300 rounded-lg px-4 py-2 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" id="alert-submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
                        Send alert
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
        document.getElementById('send-alert-btn').classList.remove('hidden');
    }

    const severityStyles = {
        mandatory: { badge: 'bg-red-50 text-red-700', border: '#EF4444', label: 'Mandatory', dot: '#EF4444' },
        advisory: { badge: 'bg-orange-50 text-orange-700', border: '#F97316', label: 'Advisory', dot: '#F97316' },
        info: { badge: 'bg-blue-50 text-blue-700', border: '#3B82F6', label: 'Info', dot: '#3B82F6' },
        all_clear: { badge: 'bg-green-50 text-green-700', border: '#22C55E', label: 'All clear', dot: '#22C55E' },
    };

    const typeIcons = {
        typhoon: 'ti-wind', flood: 'ti-droplet', volcanic: 'ti-mountain',
        earthquake: 'ti-activity', general_advisory: 'ti-info-circle',
    };
    const typeLabels = {
        typhoon: 'Typhoon', flood: 'Flood', volcanic: 'Volcanic',
        earthquake: 'Earthquake', general_advisory: 'General advisory',
    };

    let allAlerts = [];
    let severityFilter = 'all';
    let deliveryChartInstance = null;

    function renderSeverityTabs() {
        const counts = { all: allAlerts.length };
        Object.keys(severityStyles).forEach((key) => {
            counts[key] = allAlerts.filter((a) => a.severity === key).length;
        });
        const labels = { all: 'All', mandatory: 'Mandatory', advisory: 'Advisory', info: 'Info', all_clear: 'All clear' };

        document.getElementById('severity-tabs').innerHTML = Object.keys(labels).map((key) => `
            <button data-filter="${key}"
                class="severity-tab text-sm px-3 py-1.5 rounded-lg border ${severityFilter === key ? 'border-brand bg-brand-light text-brand font-medium' : 'border-gray-300 text-gray-600 hover:bg-gray-50'}">
                ${labels[key]} (${counts[key]})
            </button>
        `).join('');

        document.querySelectorAll('.severity-tab').forEach((btn) => {
            btn.addEventListener('click', () => {
                severityFilter = btn.dataset.filter;
                renderSeverityTabs();
                renderAlertsList();
            });
        });
    }

    function renderAlertsList() {
        const typeFilterValue = document.getElementById('type-filter').value;

        const filtered = allAlerts.filter((a) => {
            const matchesSeverity = severityFilter === 'all' || a.severity === severityFilter;
            const matchesType = ! typeFilterValue || a.alert_type === typeFilterValue;
            return matchesSeverity && matchesType;
        });

        document.getElementById('no-match-state').classList.toggle('hidden', filtered.length !== 0);
        document.getElementById('alerts-list').innerHTML = filtered.map((a) => {
            const sev = severityStyles[a.severity] ?? severityStyles.info;
            const deliveryPct = a.recipient_summary && a.recipient_summary.total > 0
                ? Math.round((a.recipient_summary.sent / a.recipient_summary.total) * 100)
                : null;

            return `
            <div class="bg-white rounded-xl p-4" style="border-left: 4px solid ${sev.border};">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-2.5">
                        <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                            <i class="ti ${typeIcons[a.alert_type] ?? 'ti-speakerphone'} text-red-500" style="font-size: 18px;" aria-hidden="true"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <span class="text-xs px-2 py-0.5 rounded-lg font-semibold ${sev.badge}">${sev.label.toUpperCase()}</span>
                                <span class="text-xs text-gray-400">${typeLabels[a.alert_type] ?? a.alert_type} &middot; ${new Date(a.created_at).toLocaleString()}</span>
                                ${a.evacuation_event ? `<span class="text-xs px-2 py-0.5 rounded-lg bg-gray-100 text-gray-600">${a.evacuation_event.name}</span>` : ''}
                            </div>
                            <p class="font-medium text-sm">${a.title}</p>
                            <p class="text-sm text-gray-600 mt-1">${a.message}</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 border-t border-gray-100 pt-3 flex-wrap">
                    <span>Sent by ${a.sender?.name ?? 'Unknown'}</span>
                    ${a.recipient_summary ? `
                        <span>&middot; ${a.recipient_summary.total} SMS recipient(s)</span>
                        <span class="text-green-600">${a.recipient_summary.sent} delivered</span>
                        ${a.recipient_summary.failed > 0 ? `<span class="text-red-500">${a.recipient_summary.failed} failed</span>` : ''}
                        ${a.recipient_summary.pending > 0 ? `<span class="text-gray-400">${a.recipient_summary.pending} pending</span>` : ''}
                    ` : ''}
                </div>
                ${deliveryPct !== null ? `
                    <div class="h-1.5 bg-gray-100 rounded-full mt-2">
                        <div class="h-1.5 bg-green-500 rounded-full" style="width: ${deliveryPct}%"></div>
                    </div>
                ` : ''}
            </div>`;
        }).join('');
    }

    function renderSidebar() {
        // SMS delivery summary
        const totals = allAlerts.reduce((acc, a) => {
            if (! a.recipient_summary) return acc;
            acc.sent += a.recipient_summary.sent;
            acc.failed += a.recipient_summary.failed;
            acc.pending += a.recipient_summary.pending;
            return acc;
        }, { sent: 0, failed: 0, pending: 0 });

        const deliveryMeta = [
            ['sent', 'Delivered', '#22C55E'],
            ['failed', 'Failed', '#EF4444'],
            ['pending', 'Pending', '#9CA3AF'],
        ];
        const deliveryTotal = totals.sent + totals.failed + totals.pending || 1;

        document.getElementById('delivery-legend').innerHTML = deliveryMeta.map(([key, label, color]) => `
            <div class="flex items-center justify-between">
                <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:${color}"></span>${label}</span>
                <span class="font-medium text-gray-800">${totals[key]} <span class="text-gray-400 font-normal">(${Math.round(totals[key] / deliveryTotal * 100)}%)</span></span>
            </div>`).join('');

        if (deliveryChartInstance) deliveryChartInstance.destroy();
        deliveryChartInstance = new Chart(document.getElementById('deliveryChart'), {
            type: 'doughnut',
            data: {
                labels: deliveryMeta.map(([, label]) => label),
                datasets: [{ data: deliveryMeta.map(([key]) => totals[key]), backgroundColor: deliveryMeta.map(([, , c]) => c), borderWidth: 0 }],
            },
            options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } },
        });

        // Alerts by severity
        const maxSeverity = Math.max(...Object.keys(severityStyles).map((k) => allAlerts.filter((a) => a.severity === k).length), 1);
        document.getElementById('severity-distribution').innerHTML = Object.entries(severityStyles).map(([key, meta]) => {
            const count = allAlerts.filter((a) => a.severity === key).length;
            return `
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-gray-600">${meta.label}</span>
                        <span class="font-medium text-gray-800">${count}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full" style="width:${count / maxSeverity * 100}%; background:${meta.dot}"></div>
                    </div>
                </div>`;
        }).join('');

        // Alerts by type
        const maxType = Math.max(...Object.keys(typeLabels).map((k) => allAlerts.filter((a) => a.alert_type === k).length), 1);
        document.getElementById('type-distribution').innerHTML = Object.entries(typeLabels).map(([key, label]) => {
            const count = allAlerts.filter((a) => a.alert_type === key).length;
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

        // Recent activity -- one real event per alert (its actual send
        // time), not a fabricated multi-step lifecycle: the schema only
        // records a single created_at/date_sent per alert.
        const recent = [...allAlerts].sort((a, b) => new Date(b.created_at) - new Date(a.created_at)).slice(0, 6);
        document.getElementById('activity-timeline').innerHTML = recent.length ? recent.map((a) => {
            const sev = severityStyles[a.severity] ?? severityStyles.info;
            return `
                <div class="flex gap-2.5">
                    <span class="w-2 h-2 rounded-full mt-1.5 shrink-0" style="background:${sev.dot}"></span>
                    <div class="min-w-0">
                        <p class="text-gray-700 font-medium truncate">${a.title}</p>
                        <p class="text-gray-400">${a.sender?.name ?? 'System'} &middot; ${new Date(a.created_at).toLocaleString()}</p>
                        ${a.recipient_summary && a.recipient_summary.total > 0 ? `<p class="text-gray-400">${a.recipient_summary.sent}/${a.recipient_summary.total} SMS delivered</p>` : ''}
                    </div>
                </div>`;
        }).join('') : '<p class="text-gray-400">No activity yet.</p>';
    }

    document.getElementById('type-filter').addEventListener('change', renderAlertsList);

    // Named (not an inline IIFE) so it can be called again after a
    // successful send from the modal, refreshing the list in place instead
    // of a full page reload.
    async function loadAlerts() {
        try {
            const result = await Api.get('/alerts?per_page=100');
            allAlerts = result.data.data;

            if (allAlerts.length === 0) {
                document.getElementById('empty-state').classList.remove('hidden');
                document.getElementById('stats-row').classList.add('hidden');
                return;
            }

            // Explicitly re-hidden here (not just left alone) -- without
            // this, sending the first-ever alert from the modal would
            // refresh the list but leave the "No alerts sent yet" empty
            // state stuck on screen alongside it, since previously this
            // function only ever ran once per full page load.
            document.getElementById('empty-state').classList.add('hidden');
            document.getElementById('stats-row').classList.remove('hidden');
            document.getElementById('stat-total').textContent = result.data.meta?.total ?? allAlerts.length;

            const totalRecipients = allAlerts.reduce((sum, a) => sum + (a.recipient_summary?.total ?? 0), 0);
            const delivered = allAlerts.reduce((sum, a) => sum + (a.recipient_summary?.sent ?? 0), 0);
            const failed = allAlerts.reduce((sum, a) => sum + (a.recipient_summary?.failed ?? 0), 0);
            document.getElementById('stat-delivered').textContent = delivered;
            document.getElementById('stat-failed').textContent = failed;
            document.getElementById('stat-delivered-pct').textContent =
                totalRecipients ? `${Math.round(delivered / totalRecipients * 100)}% success rate` : 'No SMS sent yet';
            document.getElementById('stat-failed-pct').textContent =
                totalRecipients ? `${Math.round(failed / totalRecipients * 100)}% failure rate` : 'No SMS sent yet';

            renderSeverityTabs();
            renderAlertsList();
            renderSidebar();
        } catch (error) {
            showFormErrors(error);
        }
    }

    loadAlerts();

    // --- Send-alert modal -------------------------------------------------
    // Pilot: only this one create form is a modal for now. /alerts/create
    // still exists untouched as a real page (the topbar's global "Send
    // emergency alert" button still links straight there).

    async function openAlertModal() {
        document.getElementById('alert-modal-errors').classList.add('hidden');
        document.getElementById('alert-form').reset();
        document.getElementById('send-alert-modal').classList.remove('hidden');
        document.getElementById('send-alert-modal').classList.add('flex');

        try {
            const [events, barangays] = await Promise.all([
                Api.get('/evacuation-events'),
                Api.get('/barangays'),
            ]);
            document.getElementById('alert-evacuation-event').innerHTML =
                '<option value="">None</option>' +
                events.data.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join('');
            document.getElementById('alert-barangay').innerHTML =
                '<option value="">All barangays</option>' +
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');
        } catch (error) {
            // Dropdowns just stay at their default single option if this
            // fails -- the rest of the form is still usable.
        }
    }

    function closeAlertModal() {
        document.getElementById('send-alert-modal').classList.add('hidden');
        document.getElementById('send-alert-modal').classList.remove('flex');
    }

    document.getElementById('send-alert-btn').addEventListener('click', openAlertModal);
    document.getElementById('alert-modal-close').addEventListener('click', closeAlertModal);
    document.getElementById('alert-modal-cancel').addEventListener('click', closeAlertModal);

    // Exposed globally so the topbar's "Send emergency alert" button
    // (present on every page, defined in layouts/app.blade.php) can open
    // this same modal directly when it's already sitting on /alerts,
    // instead of doing a full navigation + reload.
    window.openAlertModal = openAlertModal;

    // Landing here via the topbar button from another page navigates to
    // /alerts?compose=1 -- auto-open the modal once so the click still
    // feels like one action instead of "go to the list, then click again".
    if (new URLSearchParams(window.location.search).get('compose') === '1') {
        openAlertModal();
    }

    // Clicking the dimmed overlay itself (not the white card sitting on
    // top of it) closes the modal -- checking e.target against the
    // outermost element specifically, so clicks inside the form never
    // bubble into an accidental close.
    document.getElementById('send-alert-modal').addEventListener('click', (e) => {
        if (e.target.id === 'send-alert-modal') closeAlertModal();
    });

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && ! document.getElementById('send-alert-modal').classList.contains('hidden')) {
            closeAlertModal();
        }
    });

    document.getElementById('alert-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            title: document.getElementById('alert-title').value,
            message: document.getElementById('alert-message').value,
            alert_type: document.getElementById('alert-type').value,
            severity: document.getElementById('alert-severity').value,
            evacuation_event_id: document.getElementById('alert-evacuation-event').value || null,
            notify_barangay_officials: document.getElementById('alert-notify-officials').checked,
            notify_evacuees: document.getElementById('alert-notify-evacuees').checked,
            barangay_id: document.getElementById('alert-barangay').value || null,
        };

        const button = document.getElementById('alert-submit-btn');
        button.disabled = true;
        button.textContent = 'Sending...';

        try {
            await Api.post('/alerts', payload);
            closeAlertModal();
            await loadAlerts(); // refresh in place, no full page reload
        } catch (error) {
            // Shown inside the modal itself (not the page's #form-errors
            // box, which sits behind the modal and wouldn't be visible)
            // -- same message/errors-array handling showFormErrors uses.
            const box = document.getElementById('alert-modal-errors');
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = 'Send alert';
        }
    });
</script>
@endsection
