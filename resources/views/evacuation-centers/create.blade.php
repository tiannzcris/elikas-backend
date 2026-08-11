@extends('layouts.app')

@section('title', 'Add evacuation center')
@section('nav-centers', 'active')

@section('content')
    <h1 class="text-xl font-semibold mb-1" id="page-title">Add evacuation center</h1>
    <p class="text-sm text-gray-500 mb-6">Click the map to set the exact location.</p>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4 max-w-3xl"></div>

    <form id="center-form" class="flex flex-col gap-4 max-w-3xl">
        <div class="bg-white border border-gray-200 rounded-xl p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="text-sm text-gray-600 block mb-1">Name</label>
                <input type="text" id="name" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Barangay</label>
                <select id="barangay_id" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm"></select>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Type</label>
                <select id="type" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="school">School</option>
                    <option value="covered_court">Covered court</option>
                    <option value="church">Church</option>
                    <option value="barangay_hall">Barangay hall</option>
                    <option value="gymnasium">Gymnasium</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="text-sm text-gray-600 block mb-1">Address</label>
                <input type="text" id="address" required placeholder="e.g. Purok 3, Barangay Bacong, Ligao City"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Capacity (families)</label>
                <input type="number" id="capacity_families" min="0" placeholder="e.g. 50" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">Leave blank if not yet known.</p>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Capacity (persons)</label>
                <input type="number" id="capacity_persons" min="0" placeholder="e.g. 250" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <p class="text-xs text-gray-400 mt-1">Leave blank if not yet known.</p>
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Camp manager name</label>
                <input type="text" id="camp_manager_name" placeholder="e.g. Juan Dela Cruz" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Camp manager contact</label>
                <input type="text" id="camp_manager_contact" placeholder="09XXXXXXXXX" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Status</label>
                <select id="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="on_standby">On standby</option>
                    <option value="active">Active</option>
                    <option value="full">Full</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <p class="text-sm text-gray-600 mb-2">
                Location <span id="coords-display" class="text-gray-400">(click the map to set)</span>
            </p>
            <div id="picker-map" style="height: 350px; border-radius: 0.5rem;"></div>
        </div>

        <button type="submit" id="submit-btn"
            class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg px-4 py-2.5 w-fit">
            Save evacuation center
        </button>
    </form>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Detects edit mode from the URL itself (/evacuation-centers/{id}/edit)
    // rather than needing a separate view file -- same pattern already
    // used by users/create.blade.php and evacuation-events/create.blade.php.
    const pathParts = window.location.pathname.split('/').filter(Boolean);
    const isEdit = pathParts.includes('edit');
    const centerId = isEdit ? pathParts[1] : null;

    // Ligao City, Albay -- reasonable default center/zoom until the user
    // clicks (or until the existing center's coordinates are loaded below,
    // in edit mode). This map is never inside a hidden container on this
    // page (unlike the modal version on the index page), so there's no
    // invalidateSize() gotcha to work around here.
    const map = L.map('picker-map').setView([13.1391, 123.5321], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    let marker = null;
    let selectedLat = null;
    let selectedLng = null;

    function setMarker(lat, lng) {
        selectedLat = lat;
        selectedLng = lng;

        const latlng = [lat, lng];
        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng).addTo(map);
        }

        document.getElementById('coords-display').textContent =
            `(${selectedLat.toFixed(6)}, ${selectedLng.toFixed(6)})`;
        document.getElementById('coords-display').classList.remove('text-gray-400');
    }

    map.on('click', (e) => setMarker(e.latlng.lat, e.latlng.lng));

    (async () => {
        try {
            const barangays = await Api.get('/barangays');
            document.getElementById('barangay_id').innerHTML =
                '<option value="">Select barangay</option>' +
                barangays.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');

            if (isEdit) {
                document.getElementById('page-title').textContent = 'Edit evacuation center';
                document.getElementById('submit-btn').textContent = 'Save changes';

                const result = await Api.get(`/evacuation-centers/${centerId}`);
                const center = result.data;

                document.getElementById('name').value = center.name;
                document.getElementById('barangay_id').value = center.barangay?.id ?? '';
                document.getElementById('type').value = center.type;
                document.getElementById('address').value = center.address;
                document.getElementById('capacity_families').value = center.capacity_families ?? '';
                document.getElementById('capacity_persons').value = center.capacity_persons ?? '';
                document.getElementById('camp_manager_name').value = center.camp_manager_name ?? '';
                document.getElementById('camp_manager_contact').value = center.camp_manager_contact ?? '';
                document.getElementById('status').value = center.status;

                // Shows the center's EXISTING location with a marker
                // already placed, instead of starting blank -- map view
                // is centered on it too, not left at the citywide default.
                map.setView([center.latitude, center.longitude], 16);
                setMarker(center.latitude, center.longitude);
            }
        } catch (error) {
            showFormErrors(error);
        }
    })();

    document.getElementById('center-form').addEventListener('submit', async (e) => {
        e.preventDefault();

        if (selectedLat === null) {
            showFormErrors({ message: 'Click the map to set the evacuation center\'s location before saving.' });
            return;
        }

        const payload = {
            barangay_id: Number(document.getElementById('barangay_id').value),
            name: document.getElementById('name').value,
            type: document.getElementById('type').value,
            address: document.getElementById('address').value,
            latitude: selectedLat,
            longitude: selectedLng,
            capacity_families: document.getElementById('capacity_families').value || null,
            capacity_persons: document.getElementById('capacity_persons').value || null,
            camp_manager_name: document.getElementById('camp_manager_name').value || null,
            camp_manager_contact: document.getElementById('camp_manager_contact').value || null,
            status: document.getElementById('status').value,
        };

        const button = document.getElementById('submit-btn');
        button.disabled = true;
        button.textContent = 'Saving...';

        try {
            if (isEdit) {
                await Api.request(`/evacuation-centers/${centerId}`, { method: 'PATCH', body: JSON.stringify(payload) });
            } else {
                await Api.post('/evacuation-centers', payload);
            }
            window.location.href = '/evacuation-centers';
        } catch (error) {
            showFormErrors(error);
            button.disabled = false;
            button.textContent = isEdit ? 'Save changes' : 'Save evacuation center';
        }
    });
</script>
@endsection
