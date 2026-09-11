@extends('layouts.app')

@section('title', 'Alerts')
@section('nav-alerts', 'active')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div class="min-w-0">
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
            class="hidden shrink-0 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Send an alert
        </button>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div id="stats-row" class="hidden grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
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
                    <p id="alert-modal-heading" class="font-semibold text-gray-800">Send an alert</p>
                    <p id="alert-modal-subheading" class="text-xs text-gray-500">Broadcasts instantly to the dashboard. SMS is optional and best-effort.</p>
                </div>
                <button type="button" id="alert-modal-close" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>

            <div id="alert-modal-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mx-5 mt-4"></div>

            <form id="alert-form" class="flex flex-col gap-4 p-5">
                <div class="bg-brand-light border border-blue-100 rounded-xl p-3 flex items-center justify-between gap-3">
                    <p class="text-xs text-gray-600">
                        <i class="ti ti-file-text" style="font-size: 14px;" aria-hidden="true"></i>
                        Template available for <strong id="template-type-label">Typhoon</strong> -- fills Title/Message below, still fully editable.
                    </p>
                    <button type="button" id="use-template-btn" class="text-xs font-semibold text-brand hover:text-brand-dark bg-white border border-brand/30 rounded-lg px-3 py-1.5 whitespace-nowrap shrink-0">
                        Use template
                    </button>
                </div>
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
                    {{-- A plain <textarea> can't render partial bold/colored text
                        within its own value, so "highlight the remaining
                        bracketed option" is done via this callout below it
                        instead of inline styling inside the field itself. --}}
                    <p id="bracket-warning" class="hidden text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mt-2">
                        <i class="ti ti-alert-triangle" style="font-size: 13px;" aria-hidden="true"></i>
                        Delete the bracketed choices that don't apply, keeping only the one matching the
                        selected Urgency: <strong id="bracket-warning-text"></strong>
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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

                <p id="alert-edit-note" class="hidden text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                    <i class="ti ti-info-circle" style="font-size: 13px;" aria-hidden="true"></i>
                    SMS was already sent when this alert was originally created. Editing only updates its
                    content on the dashboard and history -- it does not resend anything to anyone.
                </p>
                <div id="sms-delivery-section" class="bg-gray-50 border border-gray-200 rounded-xl p-4">
                    <p class="text-sm font-medium text-gray-700 mb-3">SMS delivery (optional)</p>

                    <div class="bg-white border border-gray-200 rounded-lg p-3 mb-3">
                        <label class="text-sm text-gray-600 block mb-1">Send to a specific evacuee only</label>
                        <div class="relative">
                            <input type="text" id="evacuee-search-input" placeholder="Search by name..." autocomplete="off"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <div id="evacuee-search-results" class="hidden absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
                        </div>
                        <input type="hidden" id="alert-evacuee-id" value="">
                        <div id="evacuee-selected-banner" class="hidden mt-2 flex items-center justify-between gap-2 bg-brand-light text-brand text-sm rounded-lg px-3 py-2">
                            <span class="flex items-center gap-1.5">
                                <i class="ti ti-user-check" style="font-size: 15px;" aria-hidden="true"></i>
                                This alert will be sent to <strong id="evacuee-selected-name"></strong> ONLY -- not barangay-wide.
                            </span>
                            <button type="button" id="evacuee-selected-clear" class="text-brand hover:text-brand-dark shrink-0">
                                <i class="ti ti-x" style="font-size: 15px;" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <label id="notify-officials-label" class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                        <input type="checkbox" id="alert-notify-officials"> Notify barangay officials by SMS
                    </label>
                    <label id="notify-evacuees-label" class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                        <input type="checkbox" id="alert-notify-evacuees"> Notify registered evacuees by SMS (uses their contact number on file)
                    </label>
                    <div id="barangay-limit-field">
                        <label class="text-sm text-gray-600 block mb-1">Limit SMS to one barangay (optional)</label>
                        <select id="alert-barangay" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <option value="">All barangays</option>
                        </select>
                    </div>
                    <p class="text-xs text-gray-400 mt-3">
                        SMS delivery depends on Semaphore's connection to each recipient's network -- the
                        alert always reaches the live dashboard regardless of SMS outcome.
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
    const canManage = !! (user && user.role !== 'barangay_official');
    if (canManage) {
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
                    ${canManage ? `
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" class="alert-edit-btn w-7 h-7 flex items-center justify-center text-gray-400 hover:text-brand hover:bg-gray-50 rounded-lg" data-id="${a.id}" aria-label="Edit alert">
                                <i class="ti ti-pencil" style="font-size: 15px;" aria-hidden="true"></i>
                            </button>
                            <button type="button" class="alert-delete-btn w-7 h-7 flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg" data-id="${a.id}" data-title="${a.title.replace(/"/g, '&quot;')}" aria-label="Delete alert">
                                <i class="ti ti-trash" style="font-size: 15px;" aria-hidden="true"></i>
                            </button>
                        </div>
                    ` : ''}
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

    // Delegated -- #alerts-list is fully replaced (innerHTML) on every
    // renderAlertsList() call, so listeners bound directly to individual
    // buttons would be lost on the next render.
    document.getElementById('alerts-list').addEventListener('click', (e) => {
        const editBtn = e.target.closest('.alert-edit-btn');
        if (editBtn) {
            const alertToEdit = allAlerts.find((a) => a.id === Number(editBtn.dataset.id));
            if (alertToEdit) openAlertModal(alertToEdit);
            return;
        }

        const deleteBtn = e.target.closest('.alert-delete-btn');
        if (deleteBtn) {
            deleteAlert(Number(deleteBtn.dataset.id), deleteBtn.dataset.title);
        }
    });

    async function deleteAlert(id, title) {
        if (! confirm(`Delete the alert "${title}"? This cannot be undone.`)) {
            return;
        }

        try {
            await Api.request(`/alerts/${id}`, { method: 'DELETE' });
            await loadAlerts();
        } catch (error) {
            showFormErrors(error);
        }
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

    // --- Single-evacuee targeting (evacuee_id) -----------------------------
    // Reuses GET /evacuees?search=... (the same name-search EvacueeController
    // already supports for the staff evacuee list) rather than a new
    // endpoint. Selecting an evacuee disables the barangay-wide toggle/
    // select entirely rather than just leaving them checkable-but-ignored --
    // form.reset() alone would NOT undo that disabled state, which is why
    // clearSelectedEvacuee() is also called explicitly from openAlertModal().
    let selectedEvacueeId = null;
    let evacueeSearchDebounce = null;

    function clearSelectedEvacuee() {
        selectedEvacueeId = null;
        document.getElementById('alert-evacuee-id').value = '';
        document.getElementById('evacuee-search-input').value = '';
        document.getElementById('evacuee-search-results').classList.add('hidden');
        document.getElementById('evacuee-search-results').innerHTML = '';
        document.getElementById('evacuee-selected-banner').classList.add('hidden');

        document.getElementById('alert-notify-evacuees').disabled = false;
        document.getElementById('notify-evacuees-label').classList.remove('opacity-50');
        document.getElementById('alert-barangay').disabled = false;
        document.getElementById('barangay-limit-field').classList.remove('opacity-50');
    }

    function selectEvacuee(evacuee) {
        selectedEvacueeId = evacuee.id;
        document.getElementById('alert-evacuee-id').value = evacuee.id;
        document.getElementById('evacuee-search-input').value = evacuee.full_name;
        document.getElementById('evacuee-search-results').classList.add('hidden');
        document.getElementById('evacuee-selected-name').textContent = evacuee.full_name;
        document.getElementById('evacuee-selected-banner').classList.remove('hidden');

        // Overrides/disables the barangay-wide path entirely -- avoids any
        // ambiguity about which targeting mode is actually active, per the
        // explicit request that these two modes never coexist visually.
        document.getElementById('alert-notify-evacuees').checked = false;
        document.getElementById('alert-notify-evacuees').disabled = true;
        document.getElementById('notify-evacuees-label').classList.add('opacity-50');
        document.getElementById('alert-barangay').value = '';
        document.getElementById('alert-barangay').disabled = true;
        document.getElementById('barangay-limit-field').classList.add('opacity-50');
    }

    document.getElementById('evacuee-search-input').addEventListener('input', (e) => {
        clearTimeout(evacueeSearchDebounce);
        const term = e.target.value.trim();

        if (term.length < 2) {
            document.getElementById('evacuee-search-results').classList.add('hidden');
            return;
        }

        evacueeSearchDebounce = setTimeout(async () => {
            try {
                const result = await Api.get(`/evacuees?search=${encodeURIComponent(term)}&per_page=8`);
                const evacuees = result.data.data;
                const box = document.getElementById('evacuee-search-results');

                box.innerHTML = evacuees.length === 0
                    ? '<p class="text-xs text-gray-400 px-3 py-2">No matching evacuees.</p>'
                    : evacuees.map((ev) => `
                        <button type="button" class="evacuee-result-item block w-full text-left px-3 py-2 text-sm hover:bg-gray-50" data-id="${ev.id}">
                            <span class="font-medium text-gray-700">${ev.full_name}</span>
                            <span class="text-xs text-gray-400 block">${ev.contact_number ?? 'No contact number on file'}</span>
                        </button>`).join('');

                document.querySelectorAll('.evacuee-result-item').forEach((btn) => {
                    btn.addEventListener('click', () => {
                        const evacuee = evacuees.find((ev) => ev.id === Number(btn.dataset.id));
                        if (evacuee) selectEvacuee(evacuee);
                    });
                });

                box.classList.remove('hidden');
            } catch (error) {
                // Search box just stays closed if this fails -- the rest
                // of the form (barangay-wide targeting) is still usable.
            }
        }, 300);
    });

    document.getElementById('evacuee-selected-clear').addEventListener('click', clearSelectedEvacuee);

    // --- Message templates ---------------------------------------------
    // Natural Filipino/Taglish, matching how real LGU/barangay emergency
    // announcements actually communicate -- one template per alert_type,
    // each usable across all 4 Urgency levels via a bracketed choice staff
    // narrow down to the one that applies (see bracket-warning below).
    const templates = {
        typhoon: {
            title: 'Babala sa Bagyo -- [Barangay/Lungsod]',
            message: 'May bagyong umaapekto sa ating lugar. [LUMIKAS AGAD papunta sa pinakamalapit na evacuation center / SUBAYBAYAN ang kalagayan at maghanda ng go-bag / MANATILING NAKAALAM sa opisyal na balita]. Iwasan ang mga baha at mababang bahagi ng lugar. Para sa tulong, tumawag sa inyong barangay o sa CSWDO Ligao City.',
        },
        flood: {
            title: 'Babala sa Baha -- [Barangay/Lungsod]',
            message: 'May tumataas na tubig-baha na naiulat sa ating lugar. [LUMIKAS AGAD papunta sa mataas na lugar o sa pinakamalapit na evacuation center / SUBAYBAYAN ang taas ng baha malapit sa inyong bahay / MANATILING NAKAALAM at iwasan ang paglabas kung hindi kailangan]. Huwag tumawid sa baha, lakad man o sasakyan.',
        },
        volcanic: {
            // [Alert Level] is NOT barangay-related -- no auto-fill source
            // exists for it on this form, so it's left for staff to edit
            // directly (e.g. "Alert Level 3"), same as the bracketed
            // urgency choice in the message.
            title: 'Babala sa Bulkang Mayon -- [Alert Level]',
            message: 'Itinaas ng PHIVOLCS ang alert status ng Bulkang Mayon. [LUMIKAS AGAD kung kayo ay nasa loob ng Permanent o Extended Danger Zone / SUBAYBAYAN ang opisyal na bulletin ng PHIVOLCS at maghanda para sa posibleng paglikas / MANATILING NAKAALAM sa opisyal na balita]. Iwasan ang danger zone ng bulkan sa lahat ng oras.',
        },
        earthquake: {
            title: 'Babala sa Lindol -- [Barangay/Lungsod]',
            message: 'May lindol na naramdaman sa ating lugar. [LUMIKAS AGAD papunta sa bukas na lugar, malayo sa mga gusali, kung may pinaghihinalaang sira / SUBAYBAYAN ang inyong paligid para sa aftershocks o sira sa bahay / MANATILING NAKAALAM sa opisyal na balita]. Suriin muna ang inyong bahay bago pumasok.',
        },
        general_advisory: {
            title: 'Paalala -- [Barangay/Lungsod]',
            message: 'Ito ay opisyal na paalala mula sa CSWDO Ligao City. [Ilagay dito ang espesipikong impormasyon]. Para sa katanungan o tulong, tumawag sa inyong barangay o sa CSWDO Ligao City.',
        },
    };

    // What was last substituted into the Title for [Barangay/Lungsod] --
    // lets a later barangay-dropdown change re-fill just that portion via
    // an exact find-and-replace, without touching any other edits staff
    // may have already made. Stays null when no template has been applied
    // yet, or the applied template's title has no barangay placeholder
    // (volcanic's [Alert Level] isn't one).
    let lastBarangayFillValue = null;

    function currentBarangayFillValue() {
        const select = document.getElementById('alert-barangay');
        if (! select.value) return 'Ligao City';
        return select.options[select.selectedIndex].text;
    }

    function updateTemplateLabel() {
        const type = document.getElementById('alert-type').value;
        document.getElementById('template-type-label').textContent = typeLabels[type] ?? type;
    }

    function updateBracketWarning() {
        const match = document.getElementById('alert-message').value.match(/\[[^\]]*\]/);
        const warning = document.getElementById('bracket-warning');
        if (match) {
            document.getElementById('bracket-warning-text').textContent = match[0];
            warning.classList.remove('hidden');
        } else {
            warning.classList.add('hidden');
        }
    }

    function applyTemplate() {
        const type = document.getElementById('alert-type').value;
        const tpl = templates[type];
        if (! tpl) return;

        let title = tpl.title;
        if (title.includes('[Barangay/Lungsod]')) {
            const fillValue = currentBarangayFillValue();
            title = title.replace('[Barangay/Lungsod]', fillValue);
            lastBarangayFillValue = fillValue;
        } else {
            lastBarangayFillValue = null;
        }

        document.getElementById('alert-title').value = title;
        document.getElementById('alert-message').value = tpl.message;
        updateBracketWarning();
    }

    document.getElementById('use-template-btn').addEventListener('click', applyTemplate);
    document.getElementById('alert-type').addEventListener('change', updateTemplateLabel);
    document.getElementById('alert-message').addEventListener('input', updateBracketWarning);

    // Re-fills ONLY the barangay portion of the title, and only if it's
    // still there verbatim -- String.replace() is a no-op if staff already
    // edited that part of the title away, so this never clobbers a manual
    // edit.
    document.getElementById('alert-barangay').addEventListener('change', () => {
        if (lastBarangayFillValue === null) return;
        const newFillValue = currentBarangayFillValue();
        const titleField = document.getElementById('alert-title');
        titleField.value = titleField.value.replace(lastBarangayFillValue, newFillValue);
        lastBarangayFillValue = newFillValue;
    });

    // --- Send/Edit modal --------------------------------------------------
    // null = creating a new alert (POST). An Alert object = editing an
    // existing one (PATCH) -- editing never touches SMS/recipients at all,
    // so the whole SMS delivery section (and the template picker, which
    // depends on the barangay dropdown living inside it) is hidden in
    // that case.
    let editingAlert = null;

    async function openAlertModal(alertToEdit = null) {
        editingAlert = alertToEdit;

        document.getElementById('alert-modal-errors').classList.add('hidden');
        document.getElementById('alert-form').reset();
        clearSelectedEvacuee();
        lastBarangayFillValue = null;
        updateBracketWarning();
        document.getElementById('send-alert-modal').classList.remove('hidden');
        document.getElementById('send-alert-modal').classList.add('flex');

        const isEditing = !! editingAlert;
        document.getElementById('alert-modal-heading').textContent = isEditing ? 'Edit alert' : 'Send an alert';
        document.getElementById('alert-modal-subheading').textContent = isEditing
            ? 'Changes save immediately and never resend SMS.'
            : 'Broadcasts instantly to the dashboard. SMS is optional and best-effort.';
        document.querySelector('#alert-form > .bg-brand-light').classList.toggle('hidden', isEditing);
        document.getElementById('sms-delivery-section').classList.toggle('hidden', isEditing);
        document.getElementById('alert-edit-note').classList.toggle('hidden', ! isEditing);
        document.getElementById('alert-submit-btn').textContent = isEditing ? 'Save changes' : 'Send alert';

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

            if (isEditing) {
                document.getElementById('alert-title').value = editingAlert.title;
                document.getElementById('alert-message').value = editingAlert.message;
                document.getElementById('alert-type').value = editingAlert.alert_type;
                document.getElementById('alert-severity').value = editingAlert.severity;
                document.getElementById('alert-evacuation-event').value = editingAlert.evacuation_event?.id ?? '';
                updateBracketWarning();
            }
        } catch (error) {
            // Dropdowns just stay at their default single option if this
            // fails -- the rest of the form is still usable.
        }

        updateTemplateLabel();
    }

    function closeAlertModal() {
        document.getElementById('send-alert-modal').classList.add('hidden');
        document.getElementById('send-alert-modal').classList.remove('flex');
        editingAlert = null;
    }

    document.getElementById('send-alert-btn').addEventListener('click', () => openAlertModal());
    document.getElementById('alert-modal-close').addEventListener('click', closeAlertModal);
    document.getElementById('alert-modal-cancel').addEventListener('click', closeAlertModal);

    // Exposed globally so the topbar's "Send emergency alert" button
    // (present on every page, defined in layouts/app.blade.php) can open
    // this same modal directly when it's already sitting on /alerts,
    // instead of doing a full navigation + reload.
    window.openAlertModal = () => openAlertModal();

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

        const box = document.getElementById('alert-modal-errors');
        box.classList.add('hidden');

        // A literal "[" surviving to Send means an urgency choice (or,
        // for volcanic/general_advisory, a free-fill placeholder) was
        // never actually resolved -- blocks sending rather than letting a
        // half-filled template go out during a real emergency.
        if (document.getElementById('alert-message').value.includes('[')) {
            box.innerHTML = '<p>Remove the bracketed placeholder text in the Message before sending -- pick the one urgency option that applies and delete the other two (or fill in the free-text placeholder).</p>';
            box.classList.remove('hidden');
            return;
        }

        const isEditing = !! editingAlert;
        const button = document.getElementById('alert-submit-btn');
        button.disabled = true;
        button.textContent = isEditing ? 'Saving...' : 'Sending...';

        try {
            if (isEditing) {
                const payload = {
                    title: document.getElementById('alert-title').value,
                    message: document.getElementById('alert-message').value,
                    alert_type: document.getElementById('alert-type').value,
                    severity: document.getElementById('alert-severity').value,
                    evacuation_event_id: document.getElementById('alert-evacuation-event').value || null,
                };
                await Api.request(`/alerts/${editingAlert.id}`, { method: 'PATCH', body: JSON.stringify(payload) });
            } else {
                const payload = {
                    title: document.getElementById('alert-title').value,
                    message: document.getElementById('alert-message').value,
                    alert_type: document.getElementById('alert-type').value,
                    severity: document.getElementById('alert-severity').value,
                    evacuation_event_id: document.getElementById('alert-evacuation-event').value || null,
                    notify_barangay_officials: document.getElementById('alert-notify-officials').checked,
                    notify_evacuees: document.getElementById('alert-notify-evacuees').checked,
                    barangay_id: document.getElementById('alert-barangay').value || null,
                    evacuee_id: selectedEvacueeId,
                };
                await Api.post('/alerts', payload);
            }
            closeAlertModal();
            await loadAlerts(); // refresh in place, no full page reload
        } catch (error) {
            // Shown inside the modal itself (not the page's #form-errors
            // box, which sits behind the modal and wouldn't be visible)
            // -- same message/errors-array handling showFormErrors uses.
            const messages = error.errors ? Object.values(error.errors).flat() : [error.message];
            box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
            box.classList.remove('hidden');
        } finally {
            button.disabled = false;
            button.textContent = isEditing ? 'Save changes' : 'Send alert';
        }
    });
</script>
@endsection
