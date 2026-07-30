@extends('layouts.app')

@section('title', 'GIS map')
@section('nav-gis', 'active')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-xl font-semibold mb-1">GIS map</h1>
            <p class="text-sm text-gray-500">Evacuation centers and mapped hazard zones across Ligao City.</p>
        </div>
    </div>

    <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

    <div class="grid grid-cols-4 gap-4">
        <div class="col-span-1 flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Center status</p>
                <div class="flex flex-col gap-2 text-sm">
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#16a34a"></span> Active — accepting evacuees</span>
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#dc2626"></span> Full — at/near capacity</span>
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#9ca3af"></span> Closed — not in operation</span>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Hazard zones</p>
                <div class="flex flex-col gap-2 text-sm">
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded inline-block" style="background:#2563eb"></span> Flood</span>
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded inline-block" style="background:#ea580c"></span> Landslide / lahar</span>
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded inline-block" style="background:#0d9488"></span> Storm surge</span>
                    <span class="flex items-center gap-2"><span class="w-3 h-3 rounded inline-block" style="background:#dc2626"></span> Volcanic danger zone</span>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Evacuation centers</p>
                <div id="center-list" class="flex flex-col gap-3 text-sm max-h-72 overflow-y-auto"></div>
            </div>

            <div id="hazard-form-panel" class="hidden bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-sm font-medium mb-3">New hazard zone</p>
                <div class="flex flex-col gap-3">
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">Area name</label>
                        <input type="text" id="hz-name" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">Hazard type</label>
                        <select id="hz-type" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm">
                            <option value="flood">Flood</option>
                            <option value="landslide">Landslide</option>
                            <option value="lahar">Lahar</option>
                            <option value="storm_surge">Storm surge</option>
                            <option value="volcanic_danger_zone">Volcanic danger zone</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">Barangay (optional)</label>
                        <select id="hz-barangay" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></select>
                    </div>
                    <div>
                        <label class="text-xs text-gray-600 block mb-1">Description</label>
                        <textarea id="hz-description" rows="2" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-sm"></textarea>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" id="hz-save"
                            class="flex-1 bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg py-2">Save</button>
                        <button type="button" id="hz-cancel"
                            class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-600 text-sm font-medium rounded-lg py-2">Cancel</button>
                    </div>
                </div>
            </div>

            <div id="draw-hint" class="col-span-1 bg-white border border-gray-200 rounded-xl p-4 text-xs text-gray-500">
                Use the polygon tool in the map's top-right corner to draw a new hazard zone.
            </div>
        </div>

        <div id="map" class="col-span-3" style="height: 720px; border-radius: 0.75rem;"></div>
    </div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
    const map = L.map('map').setView([13.1391, 123.5321], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    const hazardColors = {
        flood: '#2563eb', landslide: '#ea580c', lahar: '#ea580c',
        storm_surge: '#0d9488', volcanic_danger_zone: '#dc2626',
    };
    const centerColors = {
        active: '#16a34a', on_standby: '#6b7280', full: '#dc2626', closed: '#9ca3af',
    };

    const drawnItems = new L.FeatureGroup().addTo(map);
    const user = Api.getUser();
    const canManage = user && user.role !== 'barangay_official';

    // Only administrators/CSWD personnel can draw new hazard zones --
    // barangay officials get a plain read-only map.
    if (canManage) {
        const drawControl = new L.Control.Draw({
            draw: {
                polygon: { shapeOptions: { color: '#2563eb' } },
                polyline: false, rectangle: false, circle: false, marker: false, circlemarker: false,
            },
            edit: false,
        });
        map.addControl(drawControl);
        document.getElementById('draw-hint').classList.remove('hidden');

        let pendingLayer = null;

        map.on(L.Draw.Event.CREATED, (e) => {
            pendingLayer = e.layer;
            drawnItems.addLayer(pendingLayer);
            document.getElementById('hazard-form-panel').classList.remove('hidden');
            document.getElementById('draw-hint').classList.add('hidden');
        });

        document.getElementById('hz-cancel').addEventListener('click', () => {
            if (pendingLayer) drawnItems.removeLayer(pendingLayer);
            pendingLayer = null;
            document.getElementById('hazard-form-panel').classList.add('hidden');
            document.getElementById('draw-hint').classList.remove('hidden');
        });

        document.getElementById('hz-save').addEventListener('click', async () => {
            if (! pendingLayer) return;

            const name = document.getElementById('hz-name').value;
            if (! name) {
                showFormErrors({ message: 'Give the hazard zone a name before saving.' });
                return;
            }

            const payload = {
                area_name: name,
                hazard_type: document.getElementById('hz-type').value,
                barangay_id: document.getElementById('hz-barangay').value || null,
                description: document.getElementById('hz-description').value || null,
                geojson: pendingLayer.toGeoJSON().geometry,
            };

            try {
                await Api.post('/hazard-areas', payload);
                document.getElementById('hazard-form-panel').classList.add('hidden');
                document.getElementById('draw-hint').classList.remove('hidden');
                pendingLayer = null;
                loadMapData(); // refresh so the new zone renders with its proper color/popup, not just the raw drawn shape
            } catch (error) {
                showFormErrors(error);
            }
        });

        // Populate the barangay dropdown in the hazard-zone form.
        Api.get('/barangays').then((result) => {
            document.getElementById('hz-barangay').innerHTML =
                '<option value="">None</option>' +
                result.data.map((b) => `<option value="${b.id}">${b.name}</option>`).join('');
        });
    }

    let centerLayer = null;
    let hazardLayer = null;

    async function loadMapData() {
        try {
            const result = await Api.get('/gis/map-data');

            if (centerLayer) map.removeLayer(centerLayer);
            if (hazardLayer) map.removeLayer(hazardLayer);
            drawnItems.clearLayers();

            hazardLayer = L.geoJSON(result.data.hazard_areas, {
                style: (feature) => ({
                    color: hazardColors[feature.properties.hazard_type] ?? '#666',
                    fillOpacity: 0.25,
                    weight: 2,
                }),
                onEachFeature: (feature, layer) => {
                    layer.bindPopup(`
                        <strong>${feature.properties.area_name}</strong><br>
                        ${feature.properties.hazard_type.replace('_', ' ')}
                        ${feature.properties.description ? '<br>' + feature.properties.description : ''}
                    `);
                },
            }).addTo(map);

            centerLayer = L.geoJSON(result.data.evacuation_centers, {
                pointToLayer: (feature, latlng) => L.circleMarker(latlng, {
                    radius: 8,
                    fillColor: centerColors[feature.properties.status] ?? '#666',
                    color: '#fff',
                    weight: 2,
                    fillOpacity: 0.9,
                }),
                onEachFeature: (feature, layer) => {
                    const p = feature.properties;
                    layer.bindPopup(`
                        <strong>${p.name}</strong><br>
                        ${p.barangay ?? ''} \u00b7 ${p.status.replace('_', ' ')}<br>
                        ${p.capacity_persons ? `Occupancy: ${p.current_occupancy} / ${p.capacity_persons}` : 'No capacity set'}<br>
                        <a href="/evacuation-centers/${p.id}">View details</a>
                    `);
                },
            }).addTo(map);

            // Reuses the same features just plotted -- no separate API
            // call needed for this list. Clicking a row pans/zooms the map
            // to that center and opens its popup.
            document.getElementById('center-list').innerHTML = result.data.evacuation_centers.features.map((f) => {
                const p = f.properties;
                const [lng, lat] = f.geometry.coordinates;
                const pct = p.occupancy_percent ?? 0;
                return `
                    <button class="center-list-item text-left w-full" data-lat="${lat}" data-lng="${lng}">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full inline-block shrink-0" style="background:${centerColors[p.status] ?? '#666'}"></span>
                            <span class="font-medium text-gray-700">${p.name}</span>
                        </span>
                        ${p.capacity_persons ? `<p class="text-xs text-gray-400 pl-4">${p.current_occupancy} / ${p.capacity_persons} (${pct}%)</p>` : ''}
                    </button>`;
            }).join('');

            document.querySelectorAll('.center-list-item').forEach((btn) => {
                btn.addEventListener('click', () => {
                    map.setView([Number(btn.dataset.lat), Number(btn.dataset.lng)], 16);
                    centerLayer.eachLayer((layer) => {
                        if (layer.getLatLng().lat === Number(btn.dataset.lat) && layer.getLatLng().lng === Number(btn.dataset.lng)) {
                            layer.openPopup();
                        }
                    });
                });
            });
        } catch (error) {
            showFormErrors(error);
        }
    }

    loadMapData();
</script>
@endsection
