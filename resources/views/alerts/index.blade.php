@extends('layouts.app')

@section('title', 'Alerts')
@section('nav-alerts', 'active')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold mb-1">Alerts</h1>
            <p class="text-sm text-gray-500">Advisories sent to the dashboard, barangay officials, and evacuees.</p>
        </div>
        <a href="/alerts/create" id="send-alert-btn"
            class="hidden bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5">
            + Send an alert
        </a>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div id="stats-row" class="hidden grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total alerts</p>
                <p id="stat-total" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-speakerphone text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #22C55E;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">SMS delivered</p>
                <p id="stat-delivered" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-check text-green-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #EF4444;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">SMS failed</p>
                <p id="stat-failed" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-x text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div id="empty-state" class="hidden text-center py-16 text-gray-400 text-sm">
        No alerts sent yet.
    </div>

    <div id="alerts-list" class="flex flex-col gap-3"></div>
@endsection

@section('scripts')
<script>
    const user = Api.getUser();
    if (user && user.role !== 'barangay_official') {
        document.getElementById('send-alert-btn').classList.remove('hidden');
    }

    const severityStyles = {
        mandatory: { badge: 'bg-red-50 text-red-700', border: '#EF4444', label: 'Mandatory' },
        advisory: { badge: 'bg-orange-50 text-orange-700', border: '#F97316', label: 'Advisory' },
        info: { badge: 'bg-blue-50 text-blue-700', border: '#3B82F6', label: 'Info' },
        all_clear: { badge: 'bg-green-50 text-green-700', border: '#22C55E', label: 'All clear' },
    };

    (async () => {
        try {
            const result = await Api.get('/alerts');
            const alerts = result.data.data;

            if (alerts.length === 0) {
                document.getElementById('empty-state').classList.remove('hidden');
                return;
            }

            document.getElementById('stats-row').classList.remove('hidden');
            document.getElementById('stats-row').classList.add('grid');
            document.getElementById('stat-total').textContent = result.data.meta?.total ?? alerts.length;
            document.getElementById('stat-delivered').textContent =
                alerts.reduce((sum, a) => sum + (a.recipient_summary?.sent ?? 0), 0);
            document.getElementById('stat-failed').textContent =
                alerts.reduce((sum, a) => sum + (a.recipient_summary?.failed ?? 0), 0);

            const typeIcons = {
                typhoon: 'ti-wind', flood: 'ti-droplet', volcanic: 'ti-mountain',
                earthquake: 'ti-activity', general_advisory: 'ti-info-circle',
            };

            document.getElementById('alerts-list').innerHTML = alerts.map((a) => {
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
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-xs px-2 py-0.5 rounded-lg font-semibold ${sev.badge}">${sev.label.toUpperCase()}</span>
                                    <span class="text-xs text-gray-400">${a.alert_type.replace('_', ' ')} &middot; ${new Date(a.created_at).toLocaleString()}</span>
                                </div>
                                <p class="font-medium text-sm">${a.title}</p>
                                <p class="text-sm text-gray-600 mt-1">${a.message}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-3 text-xs text-gray-500 border-t border-gray-100 pt-3">
                        <span>Sent by ${a.sender?.name ?? 'Unknown'}</span>
                        ${a.recipient_summary ? `
                            <span>· ${a.recipient_summary.total} SMS recipient(s)</span>
                            <span class="text-green-600">${a.recipient_summary.sent} delivered</span>
                            ${a.recipient_summary.failed > 0 ? `<span class="text-red-500">${a.recipient_summary.failed} failed</span>` : ''}
                        ` : ''}
                    </div>
                    ${deliveryPct !== null ? `
                        <div class="h-1.5 bg-gray-100 rounded-full mt-2">
                            <div class="h-1.5 bg-green-500 rounded-full" style="width: ${deliveryPct}%"></div>
                        </div>
                    ` : ''}
                </div>`;
            }).join('');
        } catch (error) {
            showFormErrors(error);
        }
    })();
</script>
@endsection
