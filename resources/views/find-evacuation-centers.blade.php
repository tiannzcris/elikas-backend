<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evacuation Centers · E-LIKAS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E', light: '#EAF0FB' } } } } };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }

        @keyframes page-reform {
            0% { opacity: 0; transform: scale(0.94); }
            60% { opacity: 1; transform: scale(1.01); }
            100% { opacity: 1; transform: scale(1); }
        }
        body { animation: page-reform 0.4s cubic-bezier(0.16, 1, 0.3, 1); transform-origin: center top; }

        #page-loading-bar {
            position: fixed; top: 0; left: 0; height: 3px; width: 0%;
            background: linear-gradient(90deg, #2F5496, #4C7BC9);
            z-index: 9999;
            transition: width 0.15s ease-out;
        }
        #page-loading-bar.active { width: 70%; }
    </style>
</head>
<body class="bg-white text-gray-900 min-h-screen flex flex-col">
    <div id="page-loading-bar"></div>

    <header class="border-b border-gray-100 sticky top-0 bg-white/95 backdrop-blur z-40">
        <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-2.5 shrink-0">
                <img src="/images/elikas-emblem-icon.png" alt="E-LIKAS" class="w-10 h-10 object-contain">
                <div class="leading-tight">
                    <p class="font-extrabold text-lg tracking-tight"><span class="text-red-600">E</span>-LIKAS</p>
                    <p class="text-[9px] text-gray-400 tracking-wide uppercase">Electronic Ligao Kaligtasan Sistema</p>
                </div>
            </a>
            <nav class="hidden sm:flex items-center gap-5 text-sm font-medium">
                <a href="/" class="text-gray-600 hover:text-brand">Home</a>
                <a href="/about" class="text-gray-600 hover:text-brand">About</a>
                <a href="/community-alerts" class="text-gray-600 hover:text-brand">Alerts</a>
                <a href="/find-evacuation-centers" class="text-brand border-b-2 border-brand pb-1">Evacuation Centers</a>
                <a href="/hotlines" class="text-gray-600 hover:text-brand">Hotlines</a>
                <a href="/contact" class="text-gray-600 hover:text-brand">Contact</a>
            </nav>
            <button type="button" id="mobile-menu-btn" class="sm:hidden w-9 h-9 flex items-center justify-center text-gray-600 hover:text-brand" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
                <i class="ti ti-menu-2" id="mobile-menu-icon" style="font-size: 22px;" aria-hidden="true"></i>
            </button>
        </div>
        <nav id="mobile-menu" class="hidden sm:hidden border-t border-gray-100 bg-white">
            <div class="max-w-7xl mx-auto px-6 py-3 flex flex-col gap-1 text-sm font-medium">
                <a href="/" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Home</a>
                <a href="/about" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">About</a>
                <a href="/community-alerts" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Alerts</a>
                <a href="/find-evacuation-centers" class="block px-3 py-2 rounded-lg text-brand bg-brand-light font-semibold">Evacuation Centers</a>
                <a href="/hotlines" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Hotlines</a>
                <a href="/contact" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Contact</a>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        <section class="relative overflow-hidden" style="background: #16264D;">
            <div class="absolute inset-0" style="background: linear-gradient(120deg, rgba(15,28,58,0.94) 40%, rgba(31,58,110,0.72));"></div>
            <div class="relative max-w-7xl mx-auto px-6 py-14 sm:py-16">
                <p class="text-xs font-semibold tracking-widest text-blue-300 uppercase mb-3" data-aos="fade-up" data-aos-duration="500">Know where to go</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-3" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100">Evacuation Centers &amp; Hazard Map</h1>
                <p class="text-blue-100/80 text-base max-w-2xl" data-aos="fade-up" data-aos-duration="600" data-aos-delay="200">
                    Find the nearest evacuation center and see mapped hazard zones across Ligao City --
                    no account needed. This is a read-only view of the same map CSWDO staff use internally.
                </p>
            </div>
        </section>

        <section class="max-w-7xl mx-auto px-6 py-10 sm:py-12">
            <div id="map-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
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
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Search centers</p>
                        <button type="button" id="find-near-me-btn" class="w-full flex items-center justify-center gap-2 bg-brand-light hover:bg-blue-100 text-brand text-xs font-semibold rounded-lg py-2 mb-2">
                            <i class="ti ti-current-location" style="font-size: 15px;" aria-hidden="true"></i> Find centers near me
                        </button>
                        <p id="location-status" class="hidden text-xs text-gray-500 mb-3"></p>
                        <div class="relative mb-2">
                            <i class="ti ti-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400" style="font-size: 14px;" aria-hidden="true"></i>
                            <input id="center-search" type="text" placeholder="Search by name or barangay..."
                                class="w-full border border-gray-300 rounded-lg pl-8 pr-2 py-1.5 text-xs">
                        </div>
                        <select id="center-status-filter" class="w-full border border-gray-300 rounded-lg px-2 py-1.5 text-xs mb-3">
                            <option value="">All status</option>
                            <option value="active">Active</option>
                            <option value="on_standby">On standby</option>
                            <option value="full">Full</option>
                            <option value="closed">Closed</option>
                        </select>
                        <div id="center-list" class="flex flex-col gap-3 text-sm max-h-96 overflow-y-auto"></div>
                    </div>
                </div>

                <div class="order-1 lg:order-none lg:col-span-3 flex flex-col gap-2">
                    <div class="bg-white border border-gray-200 rounded-xl p-2 flex flex-wrap items-center justify-between gap-2">
                        <p id="map-updated" class="text-xs text-gray-400 pl-1"></p>
                        <button id="reset-view-btn" class="flex items-center gap-1.5 text-xs text-gray-600 border border-gray-300 rounded-lg px-2.5 py-1.5 hover:bg-gray-50">
                            <i class="ti ti-refresh" style="font-size: 13px;" aria-hidden="true"></i> Reset view
                        </button>
                    </div>
                    <div id="map" class="rounded-xl h-[420px] lg:h-[680px]"></div>
                </div>
            </div>
        </section>
    </main>

    {{-- Full center-detail modal -- same hidden/flex + bg-black/50 pattern
        already established elsewhere in this app (e.g. the staff GIS
        map's hazard-zone form). Opened by clicking a map marker OR a list
        item; fetches /public/evacuation-centers/{id} fresh each time so
        the facilities checklist and occupancy are current, not whatever
        was loaded on page load. --}}
    <div id="center-detail-modal" class="hidden fixed inset-0 bg-black/50 z-[9999] items-center justify-center p-4">
        <div class="bg-white rounded-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="flex items-start justify-between p-5 border-b border-gray-100">
                <p id="detail-heading" class="font-semibold text-gray-800">Evacuation Center</p>
                <button type="button" id="detail-close-btn" class="text-gray-400 hover:text-gray-600 shrink-0">
                    <i class="ti ti-x" style="font-size: 20px;" aria-hidden="true"></i>
                </button>
            </div>
            <div id="detail-loading" class="text-center text-gray-400 text-sm py-16">
                <i class="ti ti-loader-2" style="font-size: 24px;" aria-hidden="true"></i>
                <p class="mt-2">Loading center details...</p>
            </div>
            <div id="detail-error" class="hidden text-center text-gray-500 text-sm py-16">
                <i class="ti ti-wifi-off text-gray-300" style="font-size: 28px;" aria-hidden="true"></i>
                <p class="mt-2">Unable to load this center's details right now.</p>
            </div>
            <div id="detail-body" class="hidden p-5">
                <div class="mb-4">
                    <img id="detail-photo" src="" alt="" class="hidden w-full h-52 object-cover rounded-lg">
                    <div id="detail-photo-placeholder" class="w-full h-52 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300">
                        <i class="ti ti-building" style="font-size: 40px;" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <h2 id="detail-name" class="font-bold text-gray-900 text-lg"></h2>
                        <p id="detail-address" class="text-sm text-gray-500"></p>
                    </div>
                    <span id="detail-status" class="text-xs px-2 py-1 rounded-lg font-medium shrink-0"></span>
                </div>
                <p id="detail-occupancy" class="text-sm text-gray-600 mb-1"></p>
                <p id="detail-distance" class="hidden text-sm text-brand font-medium mb-3"></p>

                <a id="detail-directions-btn" href="#" target="_blank" rel="noopener"
                    class="flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg py-2.5 mb-5">
                    <i class="ti ti-route" style="font-size: 16px;" aria-hidden="true"></i> Get Directions
                </a>

                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Facilities</p>
                <div id="detail-facilities" class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-sm"></div>
            </div>
        </div>
    </div>

    <footer class="text-white" style="background: #16264D;">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 sm:grid-cols-2 gap-10">
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <img src="/images/elikas-emblem-icon.png" alt="" class="w-9 h-9 object-contain">
                    <div class="leading-tight">
                        <p class="font-extrabold">E-LIKAS</p>
                        <p class="text-[9px] text-blue-200/70 tracking-wide uppercase">Electronic Ligao Kaligtasan Sistema</p>
                    </div>
                </div>
                <p class="text-sm text-blue-100/70 mb-4 max-w-sm">
                    E-LIKAS is a public service initiative of the City Social Welfare and
                    Development Office (CSWDO) Ligao City.
                </p>
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
                        <i class="ti ti-brand-facebook" style="font-size: 15px;" aria-hidden="true"></i>
                    </span>
                    <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
                        <i class="ti ti-brand-messenger" style="font-size: 15px;" aria-hidden="true"></i>
                    </span>
                    <span class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center">
                        <i class="ti ti-world" style="font-size: 15px;" aria-hidden="true"></i>
                    </span>
                </div>
            </div>
            <div class="flex sm:justify-end items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-white/10 flex items-center justify-center shrink-0">
                    <i class="ti ti-shield-check" style="font-size: 18px;" aria-hidden="true"></i>
                </div>
                <p class="text-sm text-blue-100/80">Be prepared. Be informed. Be safe.<br>Together, let's build a safer Ligao City.</p>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-blue-200/60">
                <p>E-LIKAS · CSWDO Ligao City</p>
                <a href="/privacy" class="hover:text-white">Privacy Statement</a>
            </div>
        </div>
    </footer>

    <script>
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        const mobileMenuIcon = document.getElementById('mobile-menu-icon');

        mobileMenuBtn.addEventListener('click', () => {
            const opening = mobileMenu.classList.contains('hidden');
            mobileMenu.classList.toggle('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', opening ? 'true' : 'false');
            mobileMenuIcon.className = opening ? 'ti ti-x' : 'ti ti-menu-2';
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth < 640 || mobileMenu.classList.contains('hidden')) return;
            mobileMenu.classList.add('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
            mobileMenuIcon.className = 'ti ti-menu-2';
        });

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape' || mobileMenu.classList.contains('hidden')) return;
            mobileMenu.classList.add('hidden');
            mobileMenuBtn.setAttribute('aria-expanded', 'false');
            mobileMenuIcon.className = 'ti ti-menu-2';
        });

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            if (url.pathname === window.location.pathname && url.hash) return;

            e.preventDefault();
            document.getElementById('page-loading-bar').classList.add('active');
            setTimeout(() => { window.location.href = link.href; }, 120);
        });

        window.addEventListener('pageshow', () => {
            document.getElementById('page-loading-bar').classList.remove('active');
        });
    </script>

    <script src="/js/api.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        // Same 19 facility_type values/labels as the staff detail page
        // (resources/views/evacuation-centers/show.blade.php) -- kept in
        // sync manually since this page has no shared JS module to import
        // it from.
        const facilityTypes = [
            ['toilet_male', 'Toilet (male)'], ['toilet_female', 'Toilet (female)'], ['toilet_common', 'Toilet (common)'],
            ['latrine_compost_pit', 'Latrine (compost pit)'], ['latrine_sealed', 'Latrine (sealed)'],
            ['bathing_area_male', 'Bathing area (male)'], ['bathing_area_female', 'Bathing area (female)'], ['bathing_area_common', 'Bathing area (common)'],
            ['handwashing_facility', 'Handwashing facility'], ['laundry_space', 'Laundry space'],
            ['women_friendly_space', 'Women-friendly space'], ['child_friendly_space', 'Child-friendly space'],
            ['health_facility', 'Health facility'], ['prayer_room', 'Prayer room'], ['community_kitchen', 'Community kitchen'],
            ['livestock_area', 'Livestock area'], ['camp_management_desk', 'Camp management desk'],
            ['info_board', 'Info board'], ['storage_area', 'Storage area'],
        ];

        const statusColors = {
            active: 'bg-green-50 text-green-700', on_standby: 'bg-gray-100 text-gray-600',
            full: 'bg-amber-50 text-amber-700', closed: 'bg-red-50 text-red-700',
        };

        // Great-circle straight-line distance, meters. Client-side rather
        // than the server's EvacuationCenter::nearestTo() -- that query
        // deliberately excludes full/closed centers (a "which center should
        // I go to RIGHT NOW" filter), but this list shows distance on
        // EVERY center regardless of status for full transparency, so the
        // two "which centers" rules don't actually match.
        function haversineMeters(lat1, lng1, lat2, lng2) {
            const R = 6371000;
            const toRad = (d) => (d * Math.PI) / 180;
            const dLat = toRad(lat2 - lat1);
            const dLng = toRad(lng2 - lng1);
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
            return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        }

        function formatDistance(meters) {
            return meters < 1000 ? `${Math.round(meters)} m away` : `${(meters / 1000).toFixed(1)} km away`;
        }

        let userLocation = null; // {latitude, longitude} once granted, else null

        const MAP_CENTER = [13.1391, 123.5321];
        const MAP_ZOOM = 13;

        const map = L.map('map').setView(MAP_CENTER, MAP_ZOOM);

        // Same satellite/street toggle as the staff GIS map -- but this is
        // the ONLY Leaflet control on this page: no draw tool, no
        // edit/delete, this is a read-only public view.
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

        window.addEventListener('resize', () => map.invalidateSize());
        document.getElementById('reset-view-btn').addEventListener('click', () => map.setView(MAP_CENTER, MAP_ZOOM));

        let centerLayer = null;
        let hazardLayer = null;
        let allCenterFeatures = [];
        let allHazardFeatures = [];
        // Separate from the map's own GeoJSON features -- the list/search
        // panel below reads from /public/evacuation-centers, which returns
        // a plain array (not paginated, no photo_url), matching what
        // residents are meant to see rather than the staff map-data shape.
        let allCentersList = [];

        function visibleHazardTypes() {
            const active = new Set();
            document.querySelectorAll('.hazard-type-toggle:checked').forEach((el) => {
                hazardGroups[el.dataset.group].forEach((t) => active.add(t));
            });
            return active;
        }

        function renderMapCenters() {
            if (centerLayer) map.removeLayer(centerLayer);

            const showLayer = document.getElementById('layer-centers').checked;

            centerLayer = L.geoJSON({ type: 'FeatureCollection', features: allCenterFeatures }, {
                pointToLayer: (feature, latlng) => L.circleMarker(latlng, {
                    radius: 8,
                    fillColor: centerColors[feature.properties.status] ?? '#666',
                    color: '#fff',
                    weight: 2,
                    fillOpacity: 0.9,
                }),
                onEachFeature: (feature, layer) => {
                    // Opens the full detail modal directly on click, rather
                    // than a small Leaflet popup -- richer info (facilities,
                    // Get Directions) doesn't fit well in a popup bubble.
                    layer.on('click', () => openCenterDetail(feature.properties.id));
                },
            });
            if (showLayer) centerLayer.addTo(map);
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
                        <strong>${escapeHtml(feature.properties.area_name)}</strong><br>
                        ${escapeHtml((feature.properties.hazard_type ?? '').replace('_', ' '))}
                        ${feature.properties.description ? '<br>' + escapeHtml(feature.properties.description) : ''}
                    `);
                },
            });
            if (showLayer) hazardLayer.addTo(map);
        }

        function renderCenterList() {
            const query = document.getElementById('center-search').value.trim().toLowerCase();
            const status = document.getElementById('center-status-filter').value;

            let filtered = allCentersList.filter((c) => {
                const matchesQuery = !query || c.name.toLowerCase().includes(query) || (c.barangay ?? '').toLowerCase().includes(query);
                const matchesStatus = !status || c.status === status;
                return matchesQuery && matchesStatus;
            });

            // Only actually sorts once userLocation is set -- distance_meters
            // is attached to every item in allCentersList by
            // findCentersNearMe() below, independent of the search/status
            // filter above, so it survives re-filtering.
            if (userLocation) {
                filtered = [...filtered].sort((a, b) => (a.distance_meters ?? Infinity) - (b.distance_meters ?? Infinity));
            }

            document.getElementById('center-list').innerHTML = filtered.length === 0
                ? '<p class="text-xs text-gray-400 text-center py-6">No centers match this filter.</p>'
                : filtered.map((c) => `
                    <button class="center-list-item text-left w-full" data-id="${c.id}" data-lat="${c.latitude ?? ''}" data-lng="${c.longitude ?? ''}">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full inline-block shrink-0" style="background:${centerColors[c.status] ?? '#666'}"></span>
                            <span class="font-medium text-gray-700">${escapeHtml(c.name)}</span>
                        </span>
                        <p class="text-xs text-gray-400 pl-4">
                            ${escapeHtml(c.barangay ?? '')}${c.capacity_persons ? ` · ${c.current_occupancy} / ${c.capacity_persons}` : ''}
                            ${c.distance_meters != null ? ` · <span class="text-brand font-medium">${formatDistance(c.distance_meters)}</span>` : ''}
                        </p>
                    </button>`).join('');

            document.querySelectorAll('.center-list-item').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const lat = Number(btn.dataset.lat);
                    const lng = Number(btn.dataset.lng);
                    if (lat && lng) map.setView([lat, lng], 16);
                    openCenterDetail(Number(btn.dataset.id));
                });
            });
        }

        function findCentersNearMe() {
            const btn = document.getElementById('find-near-me-btn');
            const status = document.getElementById('location-status');

            if (!navigator.geolocation) {
                status.textContent = 'Location isn\'t supported by this browser. Showing all centers without distance sorting.';
                status.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = '<i class="ti ti-loader-2" style="font-size: 15px;" aria-hidden="true"></i> Locating...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLocation = { latitude: position.coords.latitude, longitude: position.coords.longitude };
                    allCentersList.forEach((c) => {
                        c.distance_meters = (c.latitude != null && c.longitude != null)
                            ? haversineMeters(userLocation.latitude, userLocation.longitude, c.latitude, c.longitude)
                            : null;
                    });

                    status.textContent = 'Showing distances from your current location, nearest first.';
                    status.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-current-location-filled" style="font-size: 15px;" aria-hidden="true"></i> Location found';
                    renderCenterList();
                },
                (error) => {
                    // Fails gracefully -- the full list stays exactly as
                    // usable as before, just without distance sorting.
                    const reason = error.code === error.PERMISSION_DENIED
                        ? 'Location access was denied.'
                        : 'Could not determine your location.';
                    status.textContent = `${reason} Showing all centers without distance sorting.`;
                    status.classList.remove('hidden');
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ti ti-current-location" style="font-size: 15px;" aria-hidden="true"></i> Find centers near me';
                },
                { enableHighAccuracy: false, timeout: 10000 }
            );
        }

        document.getElementById('find-near-me-btn').addEventListener('click', findCentersNearMe);

        function closeCenterDetail() {
            document.getElementById('center-detail-modal').classList.add('hidden');
            document.getElementById('center-detail-modal').classList.remove('flex');
        }

        async function openCenterDetail(id) {
            const modal = document.getElementById('center-detail-modal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            document.getElementById('detail-loading').classList.remove('hidden');
            document.getElementById('detail-error').classList.add('hidden');
            document.getElementById('detail-body').classList.add('hidden');

            try {
                const result = await Api.get(`/public/evacuation-centers/${id}`);
                const c = result.data;

                document.getElementById('detail-heading').textContent = c.name;
                document.getElementById('detail-name').textContent = c.name;
                document.getElementById('detail-address').textContent = `${c.barangay ?? '—'} · ${c.address ?? ''}`;
                document.getElementById('detail-status').textContent = (c.status ?? '').replace('_', ' ');
                document.getElementById('detail-status').className = `text-xs px-2 py-1 rounded-lg font-medium shrink-0 ${statusColors[c.status] ?? ''}`;
                document.getElementById('detail-occupancy').textContent = c.capacity_persons
                    ? `Occupancy: ${c.current_occupancy} / ${c.capacity_persons} persons`
                    : 'No capacity set';

                const distanceEl = document.getElementById('detail-distance');
                const known = allCentersList.find((x) => x.id === c.id);
                if (known?.distance_meters != null) {
                    distanceEl.textContent = formatDistance(known.distance_meters);
                    distanceEl.classList.remove('hidden');
                } else {
                    distanceEl.classList.add('hidden');
                }

                const photo = document.getElementById('detail-photo');
                const placeholder = document.getElementById('detail-photo-placeholder');
                if (c.photo_url) {
                    photo.src = c.photo_url;
                    photo.alt = c.name;
                    photo.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                } else {
                    photo.classList.add('hidden');
                    placeholder.classList.remove('hidden');
                }

                // Hands off to the resident's own maps app rather than
                // building custom in-app routing -- omitting an explicit
                // origin lets the maps app default to the device's current
                // location, so this works even if this page's own
                // "Find centers near me" was never used/granted.
                const directionsBtn = document.getElementById('detail-directions-btn');
                if (c.latitude != null && c.longitude != null) {
                    directionsBtn.href = `https://www.google.com/maps/dir/?api=1&destination=${c.latitude},${c.longitude}`;
                    directionsBtn.classList.remove('hidden');
                } else {
                    directionsBtn.classList.add('hidden');
                }

                const existingFacilities = {};
                (c.facilities || []).forEach((f) => { existingFacilities[f.facility_type] = f; });

                document.getElementById('detail-facilities').innerHTML = facilityTypes.map(([type, label]) => {
                    const f = existingFacilities[type];
                    const available = f?.is_available ?? false;
                    const icon = available ? 'ti-circle-check text-green-600' : 'ti-circle-x text-gray-300';
                    const note = (!available && f?.concerns_and_needs) ? ` <span class="text-gray-400">(${escapeHtml(f.concerns_and_needs)})</span>` : '';
                    return `<div class="flex items-start gap-1.5"><i class="ti ${icon} shrink-0 mt-0.5" style="font-size: 15px;" aria-hidden="true"></i> <span class="${available ? 'text-gray-700' : 'text-gray-400'}">${label}${f ? ` (${f.quantity})` : ''}${note}</span></div>`;
                }).join('');

                document.getElementById('detail-loading').classList.add('hidden');
                document.getElementById('detail-body').classList.remove('hidden');
            } catch (error) {
                document.getElementById('detail-loading').classList.add('hidden');
                document.getElementById('detail-error').classList.remove('hidden');
            }
        }

        document.getElementById('detail-close-btn').addEventListener('click', closeCenterDetail);
        document.getElementById('center-detail-modal').addEventListener('click', (e) => {
            if (e.target.id === 'center-detail-modal') closeCenterDetail();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !document.getElementById('center-detail-modal').classList.contains('hidden')) {
                closeCenterDetail();
            }
        });

        async function loadMapData() {
            try {
                const result = await Api.get('/public/gis/map-data');
                allCenterFeatures = result.data.evacuation_centers.features;
                allHazardFeatures = result.data.hazard_areas.features;
                renderMapCenters();
                renderHazards();
                document.getElementById('map-updated').textContent = `Loaded ${new Date().toLocaleTimeString()}`;
            } catch (error) {
                document.getElementById('map-errors').textContent = 'Unable to load the map right now. Please try again in a moment.';
                document.getElementById('map-errors').classList.remove('hidden');
            }
        }

        async function loadCenterList() {
            try {
                const result = await Api.get('/public/evacuation-centers');
                allCentersList = result.data;
                renderCenterList();
            } catch (error) {
                document.getElementById('center-list').innerHTML =
                    '<p class="text-xs text-red-500 text-center py-6">Unable to load the center list.</p>';
            }
        }

        document.getElementById('layer-centers').addEventListener('change', renderMapCenters);
        document.getElementById('layer-hazards').addEventListener('change', renderHazards);
        document.querySelectorAll('.hazard-type-toggle').forEach((el) => el.addEventListener('change', renderHazards));
        document.getElementById('center-search').addEventListener('input', renderCenterList);
        document.getElementById('center-status-filter').addEventListener('change', renderCenterList);

        loadMapData();
        loadCenterList();
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true, offset: 60, easing: 'ease-out-cubic' });
    </script>
</body>
</html>
