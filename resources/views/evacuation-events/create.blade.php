@extends('layouts.app')

@section('title', 'Disaster event')
@section('nav-events', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1" id="page-title">Create a disaster event</h1>
    <p class="text-sm text-gray-500 mb-6">This becomes selectable for evacuee registration, reports, alerts, and predictions.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4 max-w-2xl"></div>

    <form id="event-form" class="flex flex-col gap-4 max-w-2xl">
        <div class="bg-white border border-gray-200 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm text-gray-600 block mb-1">Event name</label>
                <input type="text" id="name" required placeholder="e.g. Typhoon Rolly 2026" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Event type</label>
                <select id="event_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="typhoon">Typhoon</option>
                    <option value="flood">Flood</option>
                    <option value="volcanic_eruption">Volcanic eruption</option>
                    <option value="earthquake">Earthquake</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Status</label>
                <select id="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="monitoring">Monitoring</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Typhoon category / alert level (optional)</label>
                <input type="text" id="typhoon_category" placeholder="e.g. Signal No. 2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Alert level (optional)</label>
                <input type="text" id="alert_level" placeholder="e.g. Alert Level 3" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Rainfall, mm (optional)</label>
                <input type="number" step="0.1" id="rainfall_mm" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Max wind speed, kph (optional)</label>
                <input type="number" step="0.1" id="max_wind_speed_kph" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Start date</label>
                <input type="date" id="start_date" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">End date (optional)</label>
                <input type="date" id="end_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm text-gray-600 block mb-1">Description (optional)</label>
                <textarea id="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
            </div>
        </div>

        <button type="submit" id="submit-btn" class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5 w-fit">
            Create event
        </button>
    </form>
@endsection

@section('scripts')
<script>
    // Detects edit mode from the URL itself (/evacuation-events/{id}/edit)
    // rather than needing a separate view file -- the form and validation
    // are identical either way, only the HTTP verb and pre-fill differ.
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const isEdit = pathParts.includes('edit');
    const eventId = isEdit ? pathParts[1] : null;

    if (isEdit) {
        document.getElementById('page-title').textContent = 'Edit disaster event';
        document.getElementById('submit-btn').textContent = 'Save changes';

        (async () => {
            try {
                const result = await Api.get(`/evacuation-events/${eventId}`);
                const e = result.data;
                document.getElementById('name').value = e.name;
                document.getElementById('event_type').value = e.event_type;
                document.getElementById('status').value = e.status;
                document.getElementById('typhoon_category').value = e.typhoon_category ?? '';
                document.getElementById('alert_level').value = e.alert_level ?? '';
                document.getElementById('rainfall_mm').value = e.rainfall_mm ?? '';
                document.getElementById('max_wind_speed_kph').value = e.max_wind_speed_kph ?? '';
                document.getElementById('start_date').value = e.start_date;
                document.getElementById('end_date').value = e.end_date ?? '';
                document.getElementById('description').value = e.description ?? '';
            } catch (error) {
                showFormErrors(error);
            }
        })();
    }

    document.getElementById('event-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            name: document.getElementById('name').value,
            event_type: document.getElementById('event_type').value,
            status: document.getElementById('status').value,
            typhoon_category: document.getElementById('typhoon_category').value || null,
            alert_level: document.getElementById('alert_level').value || null,
            rainfall_mm: document.getElementById('rainfall_mm').value || null,
            max_wind_speed_kph: document.getElementById('max_wind_speed_kph').value || null,
            start_date: document.getElementById('start_date').value,
            end_date: document.getElementById('end_date').value || null,
            description: document.getElementById('description').value || null,
        };

        const button = document.getElementById('submit-btn');
        button.disabled = true;
        button.textContent = isEdit ? 'Saving...' : 'Creating...';

        try {
            if (isEdit) {
                await Api.request(`/evacuation-events/${eventId}`, { method: 'PATCH', body: JSON.stringify(payload) });
            } else {
                await Api.post('/evacuation-events', payload);
            }
            window.location.href = '/evacuation-events';
        } catch (error) {
            showFormErrors(error);
            button.disabled = false;
            button.textContent = isEdit ? 'Save changes' : 'Create event';
        }
    });
</script>
@endsection
