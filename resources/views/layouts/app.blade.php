<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'E-LIKAS') · CSWDO Ligao City</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        {{-- Matches the sidebar's active-nav-link blue
                            (#2563EB, set in the .nav-link.active rule below)
                            exactly, so every primary button/link/active-tab
                            across the whole app uses the same blue instead
                            of the previous unrelated shade. --}}
                        brand: {
                            DEFAULT: '#2563EB',
                            dark: '#1D4ED8',
                            light: '#DBEAFE',
                        },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
        .hidden { display: none; }
        /* Sidebar deliberately reuses brand.dark (#1F3A6E) -- already an
           established color in this project's palette (button hover
           states), not a new color introduced just for this. */
        .nav-link { display: flex; align-items: center; gap: 0.625rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; color: #A8C2E8; }
        .nav-link i { font-size: 16px; }
        .nav-link:hover { background: rgba(255,255,255,0.08); color: white; }
        .nav-link.active { background: #2563EB; color: white; font-weight: 500; }
    </style>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <aside class="w-56 shrink-0 h-full p-3 flex flex-col relative overflow-hidden" style="background: #1F3A6E;">
            {{-- Decorative watermark art covering the FULL sidebar height (not
                just a natural-aspect-ratio slice pinned to the bottom, which
                left a visible gap on tall viewports) -- object-fit:cover with
                object-position:bottom keeps the family/evacuation-center
                scene anchored at the bottom edge while stretching to fill
                whatever space is available above it. --}}
            <img src="/images/elikas-sidebar-art.png" alt=""
                class="absolute inset-0 w-full h-full pointer-events-none select-none"
                style="z-index: 0; opacity: 0.65; object-fit: cover; object-position: bottom;">

            <div class="relative flex flex-col h-full" style="z-index: 1;">
            <div class="flex items-center gap-2.5 px-2 pb-4 mb-2 border-b border-white/10">
                {{-- Emblem PNG (1024x1536 confirmed via getimagesize), zoomed
                    via fixed-pixel background-size/position. mix-blend-mode:
                    screen makes any of the source file's solid BLACK
                    background that falls within this crop disappear into
                    whatever's behind it (the navy sidebar) instead of
                    showing as a visible black patch -- pure black
                    contributes nothing under "screen" blending, so exact
                    crop precision matters far less than it did without it. --}}
                <div class="w-20 h-20 rounded-full shrink-0" style="background-image: url('/images/elikas-emblem.png'); background-repeat: no-repeat; background-size: 114px 171px; background-position: -17px -20px; mix-blend-mode: screen;"></div>
                <div class="leading-tight">
                    <p class="text-white font-semibold text-sm">E-LIKAS</p>
                    <p class="text-xs" style="color: #A8C2E8;">CSWDO Ligao City</p>
                </div>
            </div>

            <nav class="flex flex-col gap-1">
                <a href="/dashboard" class="nav-link @yield('nav-dashboard')">
                    <i class="ti ti-layout-dashboard" aria-hidden="true"></i> Dashboard
                </a>
                <a href="/evacuation-events" class="nav-link @yield('nav-events')">
                    <i class="ti ti-alert-triangle" aria-hidden="true"></i> Evacuation events
                </a>
                <a href="/families" class="nav-link @yield('nav-families')">
                    <i class="ti ti-users" aria-hidden="true"></i> Evacuees
                </a>
                <a href="/evacuation-centers" class="nav-link @yield('nav-centers')">
                    <i class="ti ti-building" aria-hidden="true"></i> Evacuation centers
                </a>
                <a href="/gis-map" class="nav-link @yield('nav-gis')">
                    <i class="ti ti-map" aria-hidden="true"></i> GIS map
                </a>
                <a href="/alerts" class="nav-link @yield('nav-alerts')">
                    <i class="ti ti-speakerphone" aria-hidden="true"></i> Alerts
                </a>
                <a href="/predictive-analytics" id="nav-analytics-link" class="nav-link @yield('nav-analytics')">
                    <i class="ti ti-chart-line" aria-hidden="true"></i> Predictive analytics
                </a>
                <a href="/reports" class="nav-link @yield('nav-reports')">
                    <i class="ti ti-file-report" aria-hidden="true"></i> DROMIC reports
                </a>
                <a href="/users" id="nav-users-link" class="hidden nav-link @yield('nav-users')">
                    <i class="ti ti-users-group" aria-hidden="true"></i> User management
                </a>
            </nav>

            <div id="sidebar-status" class="px-3 py-3 mt-auto border-t border-white/10" style="background: rgba(31,58,110,0.75); border-radius: 0.5rem;">
                <p id="sidebar-status-label" class="text-xs" style="color: #A8C2E8;">Loading...</p>
                <div id="sidebar-status-indicator" class="hidden items-center gap-1.5 mt-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span>
                    <p class="text-xs text-green-300 font-medium">Operations active</p>
                </div>
            </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <div class="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-3 shrink-0">
                <div class="flex items-center gap-3 text-xs text-gray-400">
                    <span class="flex items-center gap-1.5">
                        <i class="ti ti-calendar" style="font-size: 14px;" aria-hidden="true"></i>
                        <span id="topbar-datetime"></span>
                    </span>
                    <span class="text-gray-300">&middot;</span>
                    <span class="flex items-center gap-1.5">
                        <i class="ti ti-map-pin" style="font-size: 14px;" aria-hidden="true"></i>
                        Ligao City, Albay
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <a href="/alerts/create"
                        class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg px-4 py-2">
                        <i class="ti ti-bell-ringing" style="font-size: 16px;" aria-hidden="true"></i>
                        Send emergency alert
                    </a>
                    <div class="relative">
                        <button id="notif-bell" class="relative text-gray-500 hover:text-brand">
                            <i class="ti ti-bell" style="font-size: 18px;" aria-hidden="true"></i>
                            <span id="notif-badge" class="hidden absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] leading-none rounded-full w-4 h-4 flex items-center justify-center">0</span>
                        </button>
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white border border-gray-200 rounded-xl shadow-lg z-50 max-h-96 overflow-y-auto">
                            <div class="p-3 border-b border-gray-100 text-xs font-medium text-gray-500">Recent alerts</div>
                            <div id="notif-list" class="divide-y divide-gray-100"></div>
                        </div>
                    </div>
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center gap-2.5">
                            <div id="topbar-avatar" class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-semibold shrink-0" style="background:#2563EB"></div>
                            <div class="text-left leading-tight">
                                <p id="topbar-user-name" class="text-sm font-medium text-gray-700"></p>
                                <p id="topbar-user-role" class="text-xs text-gray-400"></p>
                            </div>
                            <i class="ti ti-chevron-down text-gray-400" style="font-size: 14px;" aria-hidden="true"></i>
                        </button>
                        <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1">
                            <button onclick="Api.clear(); window.location.href = '/login';"
                                class="w-full text-left px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 flex items-center gap-2">
                                <i class="ti ti-logout" style="font-size: 15px;" aria-hidden="true"></i> Log out
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <main class="flex-1 overflow-y-auto p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="/js/api.js"></script>
    <script>
        // Runs on every page using this layout: enforce login, and show the
        // logged-in user's name in the topbar.
        Api.requireAuth();
        const currentUser = Api.getUser();
        if (currentUser) {
            document.getElementById('topbar-user-name').textContent = currentUser.name;
            document.getElementById('topbar-user-role').textContent = currentUser.role_display_name || currentUser.role;

            const initials = currentUser.name.trim().split(/\s+/).slice(0, 2).map((w) => w[0]).join('').toUpperCase() || '?';
            document.getElementById('topbar-avatar').textContent = initials;

            if (currentUser.role === 'administrator') {
                document.getElementById('nav-users-link').classList.remove('hidden');
            }

            // Predictive analytics is a CSWD/admin planning tool per
            // Chapter 1's role scope, not part of "evacuee registration and
            // barangay-level reports" -- hidden from barangay officials
            // entirely, not just its write actions.
            if (currentUser.role === 'barangay_official') {
                document.getElementById('nav-analytics-link').classList.add('hidden');
            }
        }

        // Live date/time in the topbar, matching the reference design --
        // simple setInterval, no library needed.
        function updateTopbarClock() {
            const now = new Date();
            const formatted = now.toLocaleString('en-US', {
                month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit',
            });
            document.getElementById('topbar-datetime').textContent = formatted;
        }
        updateTopbarClock();
        setInterval(updateTopbarClock, 1000 * 30);

        // Sidebar status indicator reflects a REAL active event, not a
        // decorative placeholder -- shows the name of whichever disaster
        // event is currently active, or a neutral "no active response"
        // state if none is. The pulsing dot only appears when something is
        // actually active.
        Api.get('/evacuation-events').then((result) => {
            const active = result.data.find((e) => e.status === 'active');
            const label = document.getElementById('sidebar-status-label');
            const indicator = document.getElementById('sidebar-status-indicator');
            if (active) {
                label.textContent = `${active.name} response`;
                indicator.classList.remove('hidden');
                indicator.classList.add('flex');
            } else {
                label.textContent = 'No active disaster response';
                indicator.classList.add('hidden');
            }
        }).catch(() => {
            document.getElementById('sidebar-status-label').textContent = 'Status unavailable';
        });
    </script>

    {{-- Pusher + Echo: served locally from node_modules (copied into public/js/
    per STEP5_ALERTS_SETUP.md), NOT a CDN. A plain CDN <script> tag for
    laravel-echo does not reliably produce a browser-ready global build --
    the correct file (echo.iife.js) only exists after `npm install`, which
    was already done during the Reverb setup step earlier in this project. --}}
    <script src="/js/pusher.min.js"></script>
    <script src="/js/echo.iife.js"></script>
    <script>
        let notifCount = 0;

        function renderNotification(alert, prepend = false) {
            const html = `
                <div class="p-3 text-sm">
                    <p class="font-medium">${alert.title}</p>
                    <p class="text-gray-500 text-xs mt-0.5">${alert.message}</p>
                    <p class="text-gray-400 text-xs mt-1">${new Date(alert.created_at).toLocaleString()}</p>
                </div>`;
            const list = document.getElementById('notif-list');
            if (prepend) {
                list.insertAdjacentHTML('afterbegin', html);
            } else {
                list.insertAdjacentHTML('beforeend', html);
            }
        }

        document.getElementById('notif-bell').addEventListener('click', () => {
            document.getElementById('notif-dropdown').classList.toggle('hidden');
            document.getElementById('user-menu-dropdown').classList.add('hidden');
            notifCount = 0;
            document.getElementById('notif-badge').classList.add('hidden');
        });

        document.getElementById('user-menu-btn').addEventListener('click', () => {
            document.getElementById('user-menu-dropdown').classList.toggle('hidden');
            document.getElementById('notif-dropdown').classList.add('hidden');
        });

        document.addEventListener('click', (e) => {
            if (! e.target.closest('#user-menu-btn') && ! e.target.closest('#user-menu-dropdown')) {
                document.getElementById('user-menu-dropdown').classList.add('hidden');
            }
            if (! e.target.closest('#notif-bell') && ! e.target.closest('#notif-dropdown')) {
                document.getElementById('notif-dropdown').classList.add('hidden');
            }
        });

        // Show the last few alerts on load, so the dropdown isn't empty
        // just because no NEW alert has arrived yet this session.
        Api.get('/alerts?per_page=5').then((result) => {
            result.data.data.forEach((alert) => renderNotification(alert));
        }).catch(() => {});

        // Real-time: connects to the Reverb server running via
        // `php artisan reverb:start`. If that command isn't running, this
        // connection simply won't succeed -- the rest of the dashboard still
        // works fine, you just won't see live alert push updates.
        try {
            window.Pusher = Pusher;

            // Defensive: some laravel-echo IIFE builds expose the actual
            // class at Echo.default instead of Echo itself, depending on
            // how the ES module's default export got bundled -- this
            // resolves whichever shape is actually present instead of
            // assuming one.
            const EchoConstructor = (typeof Echo === 'function') ? Echo : (Echo && Echo.default);

            if (typeof EchoConstructor !== 'function') {
                throw new Error('Echo constructor not found on either Echo or Echo.default -- check console.log(Echo) to see its actual shape.');
            }

            const echo = new EchoConstructor({
                broadcaster: 'reverb',
                key: '{{ env('REVERB_APP_KEY') }}',
                wsHost: '127.0.0.1',
                wsPort: 8080,
                wssPort: 8080,
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
            });

            echo.channel('dashboard-alerts').listen('.alert.created', (alert) => {
                renderNotification(alert, true);
                notifCount++;
                document.getElementById('notif-badge').textContent = notifCount;
                document.getElementById('notif-badge').classList.remove('hidden');
            });
        } catch (error) {
            console.warn('Reverb real-time connection not available:', error);
        }
    </script>
    @yield('scripts')
</body>
</html>
