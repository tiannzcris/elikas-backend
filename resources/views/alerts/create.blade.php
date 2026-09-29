@extends('layouts.app')

@section('title', 'Send an alert')
@section('nav-alerts', 'active')

@section('content')
    <div class="page-header">
        <div class="min-w-0">
            <h1 class="page-title">Send an alert</h1>
            <p class="page-subtitle">Broadcasts instantly to the dashboard. SMS is optional and best-effort.</p>
        </div>
    </div>

    <div id="form-errors" class="hidden callout callout-danger mb-4 max-w-2xl"></div>

    <form id="alert-form" class="flex flex-col gap-4 max-w-2xl">
        <div class="card p-4 flex flex-col gap-4">
            <div>
                <label class="label">Title</label>
                <input type="text" id="title" required maxlength="200"
                    placeholder="e.g. Typhoon Warning: Signal #2"
                    class="input">
            </div>
            <div>
                <label class="label">Message</label>
                <textarea id="message" required maxlength="1000" rows="4"
                    placeholder="e.g. Residents in low-lying areas of Barangay Pawa are advised to evacuate immediately. Proceed to the nearest evacuation center."
                    class="input"></textarea>
                <p class="text-xs text-gray-500 mt-1">Plain language, no jargon -- this is what residents and barangay officials will actually read.</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="label">Urgency</label>
                    <select id="severity" required class="input">
                        <option value="mandatory">Mandatory evacuation</option>
                        <option value="advisory" selected>Advisory</option>
                        <option value="info">Info</option>
                        <option value="all_clear">All clear</option>
                    </select>
                </div>
                <div>
                    <label class="label">Alert type</label>
                    <select id="alert_type" required class="input">
                        <option value="typhoon">Typhoon</option>
                        <option value="flood">Flood</option>
                        <option value="volcanic">Volcanic</option>
                        <option value="earthquake">Earthquake</option>
                        <option value="general_advisory">General advisory</option>
                    </select>
                </div>
                <div>
                    <label class="label">Related disaster event (optional)</label>
                    <select id="evacuation_event_id" class="input">
                        <option value="">None</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card p-4">
            <h2 class="card-title mb-3">SMS delivery (optional)</h2>
            <label class="flex items-center gap-2 text-sm text-gray-700 mb-2">
                <input type="checkbox" id="notify_barangay_officials"> Notify barangay officials by SMS
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 mb-3">
                <input type="checkbox" id="notify_evacuees"> Notify registered evacuees by SMS (uses their contact number on file)
            </label>
            <div>
                <label class="label">Limit SMS to one barangay (optional)</label>
                <select id="barangay_id" class="input">
                    <option value="">All barangays</option>
                </select>
            </div>
            <p class="text-xs text-gray-500 mt-3">
                SMS delivery depends on Semaphore's connection to each recipient's network -- the
                alert always reaches the live dashboard regardless of SMS outcome.
            </p>
        </div>

        <button type="submit" id="submit-btn"
            class="btn btn-primary w-fit">
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
