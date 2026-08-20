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

    <div id="stats-row" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #16a34a;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Active centers</p>
                <p id="stat-active" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-green-50 flex items-center justify-center shrink-0">
                <i class="ti ti-building text-green-600" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #F59E0B;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Near full (&ge;75%)</p>
                <p id="stat-near-full" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                <i class="ti ti-alert-triangle text-amber-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #6b7280;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Closed centers</p>
                <p id="stat-closed" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0">
                <i class="ti ti-door-off text-gray-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
        <div class="bg-white rounded-xl p-4 flex items-center justify-between" style="border-left: 4px solid #dc2626;">
            <div>
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-1">Hazard zones mapped</p>
                <p id="stat-hazards" class="text-2xl font-bold text-gray-800">&mdash;</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                <i class="ti ti-map-pin-exclamation text-red-500" style="font-size: 20px;" aria-hidden="true"></i>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- order-2 puts the map first on mobile (order-1 below) -- a user
            opening this page wants to see the map immediately, not scroll
            past four stacked filter panels to reach it. lg:order-none
            restores normal source order (controls, then map) at desktop
            width, where they already sit side by side anyway. --}}
        <div class="order-2 lg:order-none lg:col-span-1 flex flex-col gap-4">
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <label class="flex items-center justify-between mb-2 cursor-pointer">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Evacuation centers</span>
                    <input type="checkbox" id="layer-centers" checked class="rounded border-gray-300 text-brand focus:ring-brand">
                </label>
                <div class="flex flex-col gap-2 text-sm">
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#16a34a"></span> Active — accepting evacuees</span>
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#6b7280"></span> On standby</span>
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#dc2626"></span> Full — at/near capacity</span>
                    <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full inline-block" style="background:#9ca3af"></span> Closed — not in operation</span>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <label class="flex items-center justify-between mb-2 cursor-pointer">
                    <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Hazard zones</span>
                    <input type="checkbox" id="layer-hazards" checked class="rounded border-gray-300 text-brand focus:ring-brand">
                </label>
                <div class="flex flex-col gap-2 text-sm">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="hazard-type-toggle rounded border-gray-300 text-brand focus:ring-brand" data-group="flood" checked>
                        <span class="w-3 h-3 rounded inline-block" style="background:#2563eb"></span> Flood
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="hazard-type-toggle rounded border-gray-300 text-brand focus:ring-brand" data-group="landslide_lahar" checked>
                        <span class="w-3 h-3 rounded inline-block" style="background:#ea580c"></span> Landslide / lahar
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="hazard-type-toggle rounded border-gray-300 text-brand focus:ring-brand" data-group="storm_surge" checked>
                        <span class="w-3 h-3 rounded inline-block" style="background:#0d9488"></span> Storm surge
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="hazard-type-toggle rounded border-gray-300 text-brand focus:ring-brand" data-group="volcanic" checked>
                        <span class="w-3 h-3 rounded inline-block" style="background:#dc2626"></span> Volcanic danger zone
                    </label>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Evacuation centers</p>
                <div class="relative mb-2">
                    <i class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 14px;" aria-hidden="true"></i>
                    <input id="center-search" type="text" placeholder="Search centers..."
                        class="w-full border border-gray-300 rounded-lg pl-8 pr-2 py-1.5 text-xs">
                </div>
                <select id="center-status-filter" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs mb-3">
                    <option value="">All status</option>
                    <option value="active">Active</option>
                    <option value="on_standby">On standby</option>
                    <option value="full">Full</option>
                    <option value="closed">Closed</option>
                </select>
                <div id="center-list" class="flex flex-col gap-3 text-sm max-h-64 overflow-y-auto"></div>
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

            <div id="draw-hint" class="hidden bg-white border border-gray-200 rounded-xl p-4 text-xs text-gray-500">
                Use the polygon tool in the map's top-right corner to draw a new hazard zone.
            </div>
        </div>

        <div class="order-1 lg:order-none lg:col-span-3 flex flex-col gap-2">
            <div class="bg-white border border-gray-200 rounded-xl p-2 flex flex-wrap items-center justify-between gap-2">
                <p id="map-updated" class="text-xs text-gray-400 pl-1"></p>
                <div class="flex items-center gap-2">
                    <button id="reset-view-btn" class="flex items-center gap-1.5 text-xs text-gray-600 border border-gray-300 rounded-lg px-2.5 py-1.5 hover:bg-gray-50">
                        <i class="ti ti-refresh" style="font-size: 13px;" aria-hidden="true"></i> Reset view
                    </button>
                    <button id="fullscreen-btn" class="flex items-center gap-1.5 text-xs text-gray-600 border border-gray-300 rounded-lg px-2.5 py-1.5 hover:bg-gray-50">
                        <i class="ti ti-maximize" style="font-size: 13px;" aria-hidden="true"></i> Fullscreen
                    </button>
                </div>
            </div>
            {{-- 420px on mobile (still a genuinely usable map, not squeezed --
                comparable to a typical mobile maps app), the original 680px
                restored from lg up where there's room for it beside the
                control sidebar. --}}
            <div id="map" class="rounded-xl h-[420px] lg:h-[680px]"></div>
        </div>
    </div>
@endsection

@section('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />
<script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>
<script>
    const MAP_CENTER = [13.1391, 123.5321];
    const MAP_ZOOM = 13;

    const map = L.map('map').setView(MAP_CENTER, MAP_ZOOM);

    // Street (default) and satellite base layers, toggleable via Leaflet's
    // built-in layer control -- satellite makes buildings/houses visible,
    // which street tiles alone don't, for accurately placing markers.
    const streetLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);
    const satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri',
    });
    L.control.layers({ 'Street': streetLayer, 'Satellite': satelliteLayer }).addTo(map);

    const hazardColors = {
        flood: '#2563eb', landslide: '#ea580c', lahar: '#ea580c',
        storm_surge: '#0d9488', volcanic_danger_zone: '#dc2626',
    };
    const hazardGroups = {
        flood: ['flood'], landslide_lahar: ['landslide', 'lahar'],
        storm_surge: ['storm_surge'], volcanic: ['volcanic_danger_zone'],
    };
    const centerColors = {
        active: '#16a34a', on_standby: '#6b7280', full: '#dc2626', closed: '#9ca3af',
    };

    const drawnItems = new L.FeatureGroup().addTo(map);
    const user = Api.getUser();
    const canManage = user && user.role !== 'barangay_official';

    // The map container's height changes at the lg breakpoint (420px ->
    // 680px, see the h-[420px] lg:h-[680px] classes) -- Leaflet only
    // measures its container once at L.map() init and won't notice a
    // later CSS-driven resize (e.g. rotating a phone, or resizing a
    // desktop window across the breakpoint) without being told.
    window.addEventListener('resize', () => map.invalidateSize());

    document.getElementById('reset-view-btn').addEventListener('click', () => map.setView(MAP_CENTER, MAP_ZOOM));

    document.getElementById('fullscreen-btn').addEventListener('click', () => {
        const container = document.getElementById('map');
        if (! document.fullscreenElement) {
            container.requestFullscreen().then(() => setTimeout(() => map.invalidateSize(), 150)).catch(() => {});
        } else {
            document.exitFullscreen().then(() => setTimeout(() => map.invalidateSize(), 150));
        }
    });

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
    let allCenterFeatures = [];
    let allHazardFeatures = [];

    function visibleHazardTypes() {
        const active = new Set();
        document.querySelectorAll('.hazard-type-toggle:checked').forEach((el) => {
            hazardGroups[el.dataset.group].forEach((t) => active.add(t));
        });
        return active;
    }

    function renderCenters() {
        if (centerLayer) map.removeLayer(centerLayer);

        const query = document.getElementById('center-search').value.trim().toLowerCase();
        const status = document.getElementById('center-status-filter').value;
        const showLayer = document.getElementById('layer-centers').checked;

        const filtered = allCenterFeatures.filter((f) => {
            const p = f.properties;
            const matchesQuery = ! query || p.name.toLowerCase().includes(query) || (p.barangay ?? '').toLowerCase().includes(query);
            const matchesStatus = ! status || p.status === status;
            return matchesQuery && matchesStatus;
        });

        centerLayer = L.geoJSON({ type: 'FeatureCollection', features: filtered }, {
            pointToLayer: (feature, latlng) => L.circleMarker(latlng, {
                radius: 8,
                fillColor: centerColors[feature.properties.status] ?? '#666',
                color: '#fff',
                weight: 2,
                fillOpacity: 0.9,
            }),
            onEachFeature: (feature, layer) => {
                const p = feature.properties;
                // 180x120 thumbnail when a photo's been uploaded, a
                // neutral placeholder icon (never a broken image) when not.
                const popupPhoto = p.photo_url
                    ? `<img src="${p.photo_url}" alt="${p.name}" style="width:180px;height:120px;object-fit:cover;border-radius:6px;margin-bottom:6px;display:block;">`
                    : `<div style="width:180px;height:120px;background:#f3f4f6;border-radius:6px;margin-bottom:6px;display:flex;align-items:center;justify-content:center;color:#9ca3af;"><i class="ti ti-building" style="font-size:32px;" aria-hidden="true"></i></div>`;

                layer.bindPopup(`
                    ${popupPhoto}
                    <strong>${p.name}</strong><br>
                    ${p.barangay ?? ''} · ${p.status.replace('_', ' ')}<br>
                    ${p.capacity_persons ? `Occupancy: ${p.current_occupancy} / ${p.capacity_persons}` : 'No capacity set'}<br>
                    <a href="/evacuation-centers/${p.id}">View details</a>
                `);
            },
        });
        if (showLayer) centerLayer.addTo(map);

        document.getElementById('center-list').innerHTML = filtered.length === 0
            ? '<p class="text-xs text-gray-400 text-center py-6">No centers match this filter.</p>'
            : filtered.map((f) => {
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
    }

    function renderHazards() {
        if (hazardLayer) map.removeLayer(hazardLayer);

        const showLayer = document.getElementById('layer-hazards').checked;
        const visibleTypes = visibleHazardTypes();
        const filtered = allHazardFeatures.filter((f) => visibleTypes.has(f.properties.hazard_type));

        hazardLayer = L.geoJSON({ type: 'FeatureCollection', features: filtered }, {
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
        });
        if (showLayer) hazardLayer.addTo(map);
    }

    function renderStats() {
        document.getElementById('stat-active').textContent = allCenterFeatures.filter((f) => f.properties.status === 'active').length;
        document.getElementById('stat-near-full').textContent = allCenterFeatures.filter((f) => (f.properties.occupancy_percent ?? 0) >= 75).length;
        document.getElementById('stat-closed').textContent = allCenterFeatures.filter((f) => f.properties.status === 'closed').length;
        document.getElementById('stat-hazards').textContent = allHazardFeatures.length;
    }

    async function loadMapData() {
        try {
            const result = await Api.get('/gis/map-data');

            drawnItems.clearLayers();
            allCenterFeatures = result.data.evacuation_centers.features;
            allHazardFeatures = result.data.hazard_areas.features;

            renderStats();
            renderCenters();
            renderHazards();
            document.getElementById('map-updated').textContent = `Loaded ${new Date().toLocaleTimeString()}`;
        } catch (error) {
            showFormErrors(error);
        }
    }

    document.getElementById('layer-centers').addEventListener('change', renderCenters);
    document.getElementById('center-search').addEventListener('input', renderCenters);
    document.getElementById('center-status-filter').addEventListener('change', renderCenters);
    document.getElementById('layer-hazards').addEventListener('change', renderHazards);
    document.querySelectorAll('.hazard-type-toggle').forEach((el) => el.addEventListener('change', renderHazards));

    loadMapData();
</script>
@endsection
