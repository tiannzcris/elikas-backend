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

    <div id="stats-row" class="hidden grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #3B82F6;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Total events</p>
                <p id="stat-total" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-blue-500" style="font-size: 20px;" aria-hidden="true"></i>
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

    <div id="events-list" class="flex flex-col gap-3"></div>
@endsection

@section('scripts')
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

    async function loadEvents() {
        try {
            const result = await Api.get('/evacuation-events');
            const events = result.data;
            const canManage = user && user.role !== 'barangay_official';

            if (events.length > 0) {
                document.getElementById('stats-row').classList.remove('hidden');
                document.getElementById('stats-row').classList.add('grid');
                document.getElementById('stat-total').textContent = events.length;
                document.getElementById('stat-active').textContent = events.filter((e) => e.status === 'active').length;
                document.getElementById('stat-closed').textContent = events.filter((e) => e.status === 'closed').length;
                document.getElementById('stat-displaced').textContent =
                    events.reduce((sum, e) => sum + (e.total_persons_displaced ?? 0), 0).toLocaleString();
            }

            document.getElementById('events-list').innerHTML = events.length === 0
                ? '<p class="text-gray-400 text-sm text-center py-16">No disaster events yet.</p>'
                : events.map((e) => `
                    <div class="bg-white border border-gray-200 rounded-xl p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-start gap-2.5">
                                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                                    <i class="ti ti-alert-triangle text-blue-500" style="font-size: 18px;" aria-hidden="true"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="font-medium text-sm">${e.name}</p>
                                        <span class="text-xs px-2 py-0.5 rounded-lg ${statusColors[e.status] ?? ''}">${e.status}</span>
                                    </div>
                                    <p class="text-xs text-gray-500">
                                        ${e.event_type.replace('_', ' ')}
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
        } catch (error) {
            showFormErrors(error);
        }
    }

    loadEvents();
</script>
@endsection
