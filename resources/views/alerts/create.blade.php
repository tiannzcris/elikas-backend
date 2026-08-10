@extends('layouts.app')

@section('title', 'Send an alert')
@section('nav-alerts', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Send an alert</h1>
    <p class="text-sm text-gray-500 mb-6">Broadcasts instantly to the dashboard. SMS is optional and best-effort.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4 max-w-2xl"></div>

    <form id="alert-form" class="flex flex-col gap-4 max-w-2xl">
        <div class="bg-white border border-gray-200 rounded-xl p-4 flex flex-col gap-4">
            <div>
                <label class="text-sm text-gray-600 block mb-1">Title</label>
                <input type="text" id="title" required maxlength="200"
                    placeholder="e.g. Typhoon Warning: Signal #2"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Message</label>
                <textarea id="message" required maxlength="1000" rows="4"
                    placeholder="e.g. Residents in low-lying areas of Barangay Pawa are advised to evacuate immediately. Proceed to the nearest evacuation center."
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></textarea>
                <p class="text-xs text-gray-400 mt-1">Plain language, no jargon -- this is what residents and barangay officials will actually read.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Urgency</label>
                    <select id="severity" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="mandatory">Mandatory evacuation</option>
                        <option value="advisory" selected>Advisory</option>
                        <option value="info">Info</option>
                        <option value="all_clear">All clear</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Alert type</label>
                    <select id="alert_type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="typhoon">Typhoon</option>
                        <option value="flood">Flood</option>
                        <option value="volcanic">Volcanic</option>
                        <option value="earthquake">Earthquake</option>
                        <option value="general_advisory">General advisory</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-600 block mb-1">Related disaster event (optional)</label>
                    <select id="evacuation_event_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                        <option value="">None</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm font-medium text-gray-700 mb-3">SMS delivery (optional)</p>
            <label class="flex items-center gap-2 text-sm text-gray-600 mb-2">
                <input type="checkbox" id="notify_barangay_officials"> Notify barangay officials by SMS
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                <input type="checkbox" id="notify_evacuees"> Notify registered evacuees by SMS (uses their contact number on file)
            </label>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Limit SMS to one barangay (optional)</label>
                <select id="barangay_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">All barangays</option>
                </select>
            </div>
            <p class="text-xs text-gray-400 mt-3">
                If SMS isn't configured yet (no Semaphore account), the alert still sends to the live
                dashboard — SMS attempts are just logged instead of actually delivered.
            </p>
        </div>

        <button type="submit" id="submit-btn"
            class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5 w-fit">
            Send alert
        </button>
    </form>
@endsection

@section('scripts')
<script>
    (async () => {
        try {
            const [events, barangays] = await Promise.all([
                Api.get('/evacuation-events'),
                Api.get('/barangays'),
            ]);

            document.getElementById('evacuation_event_id').insertAdjacentHTML('beforeend',
                events.data.map((ev) => `<option value="${ev.id}">${ev.name}</option>`).join(''));

            document.getElementById('barangay_id').insertAdjacentHTML('beforeend',
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join(''));
        } catch (error) {
            showFormErrors(error);
        }
    })();

    document.getElementById('alert-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        const payload = {
            title: document.getElementById('title').value,
            message: document.getElementById('message').value,
            alert_type: document.getElementById('alert_type').value,
            severity: document.getElementById('severity').value,
            evacuation_event_id: document.getElementById('evacuation_event_id').value || null,
            notify_barangay_officials: document.getElementById('notify_barangay_officials').checked,
            notify_evacuees: document.getElementById('notify_evacuees').checked,
            barangay_id: document.getElementById('barangay_id').value || null,
        };

        const button = document.getElementById('submit-btn');
        button.disabled = true;
        button.textContent = 'Sending...';

        try {
            await Api.post('/alerts', payload);
            window.location.href = '/alerts';
        } catch (error) {
            showFormErrors(error);
            button.disabled = false;
            button.textContent = 'Send alert';
        }
    });
</script>
@endsection
