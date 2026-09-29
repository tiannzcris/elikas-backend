<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'E-LIKAS') · CSWDO Ligao City</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    {{-- Public Sans: the open-source typeface built for government
        interfaces. Falls back to the system UI stack if Google Fonts is
        unreachable, so a blocked font never blocks the page. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&display=swap">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        {{-- Design tokens -- the written source of truth for every value here
            (and the contrast ratio behind each pairing) is
            docs/design-system.md. Change it there first. --}}
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Public Sans"', 'ui-sans-serif', 'system-ui', '-apple-system', '"Segoe UI"', 'Roboto', 'Arial', 'sans-serif'],
                    },
                    colors: {
                        // DEFAULT/dark/light kept as-is: pages already use
                        // bg-brand, hover:bg-brand-dark, bg-brand-light.
                        brand: {
                            DEFAULT: '#2563EB',
                            dark: '#1D4ED8',
                            light: '#DBEAFE',
                            50: '#EFF6FF',
                            100: '#DBEAFE',
                            200: '#BFDBFE',
                            600: '#2563EB',
                            700: '#1D4ED8',
                            800: '#1E40AF',
                        },
                        // The logo's own navy (public/images/elikas-logo-mark.png).
                        navy: { DEFAULT: '#094776', dark: '#073A61' },
                        // Form-field border: 3.30:1 on white, the WCAG 1.4.11
                        // minimum for a control's boundary (gray-300 was 1.47).
                        field: '#868E9C',
                    },
                },
            },
        };
    </script>
    <style>
        .hidden { display: none; }
    </style>
    {{-- Component classes (see docs/design-system.md section 4). In
        @layer components, so a utility on the same element always wins --
        e.g. "btn btn-primary w-full". None of them set display where a page
        toggles hidden/flex itself (modals, callouts, empty states). --}}
    <style type="text/tailwindcss">
        @layer base {
            body { @apply font-sans text-gray-900 bg-gray-50 antialiased; }
            ::placeholder { color: #6B7280; opacity: 1; }
            input[type="checkbox"], input[type="radio"] { accent-color: #2563EB; }
            :focus-visible { outline: 2px solid #1D4ED8; outline-offset: 2px; }
            #sidebar :focus-visible { outline-color: #FFFFFF; }
            /* Leaflet sets its own Helvetica stack; keep map popups/controls in
               the app's typeface. (body prefix outranks leaflet.css, which
               loads later.) */
            body .leaflet-container { font-family: inherit; }
            @media (prefers-reduced-motion: reduce) {
                *, *::before, *::after { animation-duration: 0.01ms !important; animation-iteration-count: 1 !important; transition-duration: 0.01ms !important; }
            }
        }

        @layer components {
            /* Page header */
            .page-header { @apply flex flex-wrap items-start justify-between gap-4 mb-6; }
            .page-title { @apply text-[1.375rem] leading-7 font-semibold tracking-tight text-gray-900; }
            .page-subtitle { @apply mt-1 text-sm text-gray-600 max-w-2xl; }

            /* Cards */
            .card { @apply bg-white border border-gray-200 rounded-xl; }
            .card-header { @apply flex items-center justify-between gap-3 mb-3; }
            .card-title { @apply text-sm font-semibold text-gray-900; }
            .icon-chip { @apply w-9 h-9 rounded-lg bg-brand-50 text-brand-700 flex items-center justify-center shrink-0; }

            /* Stat strip: one ruled card of figures. Each cell draws its own
               right/bottom hairline; the container's overflow clips the ones
               on the outer edge, so it wraps cleanly at any column count. */
            .stat-strip { @apply grid bg-white border border-gray-200 rounded-xl overflow-hidden; }
            .stat { @apply relative min-w-0 px-4 py-3.5; box-shadow: 1px 0 0 0 #E5E7EB, 0 1px 0 0 #E5E7EB; }
            .stat-label { @apply flex items-center gap-1.5 text-xs font-medium text-gray-600; }
            .stat-value { @apply mt-1 text-2xl leading-8 font-semibold text-gray-900; }
            .stat-note { @apply mt-0.5 text-xs text-gray-500; }
            .stat-alert .stat-value { @apply text-red-700; }
            .stat-alert .stat-label::before { content: ''; @apply w-2 h-2 rounded-full bg-red-600 shrink-0; }

            /* Buttons */
            .btn { @apply inline-flex items-center justify-center gap-1.5 rounded-lg border border-transparent px-4 py-2 text-sm font-medium leading-5 whitespace-nowrap transition-colors disabled:opacity-50 disabled:cursor-not-allowed; }
            .btn-sm { @apply px-3 py-1.5 text-xs leading-4; }
            .btn-primary { @apply bg-brand text-white hover:bg-brand-dark; }
            .btn-secondary { @apply bg-white text-gray-700 border-gray-300 hover:bg-gray-50 hover:border-gray-400 hover:text-gray-900; }
            .btn-neutral { @apply bg-gray-800 text-white hover:bg-gray-900; }
            .btn-danger { @apply bg-red-600 text-white hover:bg-red-700; }
            .btn-danger-secondary { @apply bg-white text-red-700 border-red-200 hover:bg-red-50 hover:border-red-300; }
            .btn-attention { @apply bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100; }
            .btn-ghost { @apply text-gray-600 hover:bg-gray-100 hover:text-gray-900; }
            .btn-icon { @apply inline-flex items-center justify-center w-8 h-8 shrink-0 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-900 transition-colors; }
            .link { @apply font-medium text-brand hover:text-brand-dark hover:underline underline-offset-2; }
            .link-danger { @apply font-medium text-red-700 hover:underline underline-offset-2; }

            /* Filter chips */
            .chip { @apply inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors; }
            .chip-active { @apply border-brand bg-brand-50 text-brand-700 font-medium hover:bg-brand-50; }

            /* Forms */
            .label { @apply block mb-1 text-sm font-medium text-gray-700; }
            .label-sm { @apply block mb-1 text-xs font-medium text-gray-600; }
            .help { @apply mt-1 text-xs text-gray-500; }
            .input { @apply w-full rounded-lg border border-field bg-white px-3 py-2 text-sm text-gray-900 transition-colors focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/25 disabled:cursor-not-allowed disabled:border-gray-300 disabled:bg-gray-100 disabled:text-gray-600; }
            .input-sm { @apply px-2.5 py-1.5; }

            /* Badges */
            .badge { @apply inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium leading-4 whitespace-nowrap ring-1 ring-inset; }
            .badge-neutral { @apply bg-gray-100 text-gray-700 ring-gray-500/20; }
            .badge-success { @apply bg-green-50 text-green-800 ring-green-600/20; }
            .badge-warning { @apply bg-amber-50 text-amber-800 ring-amber-600/30; }
            .badge-danger { @apply bg-red-50 text-red-700 ring-red-600/20; }
            .badge-info { @apply bg-blue-50 text-blue-700 ring-blue-600/20; }
            .badge-advisory { @apply bg-orange-50 text-orange-800 ring-orange-600/25; }
            .badge-teal { @apply bg-teal-50 text-teal-800 ring-teal-600/20; }

            /* Callouts (block, not flex: form-error boxes hold several <p>s) */
            .callout { @apply rounded-lg border px-3 py-2.5 text-sm; }
            .callout-danger { @apply bg-red-50 border-red-200 text-red-800; }
            .callout-warning { @apply bg-amber-50 border-amber-200 text-amber-900; }
            .callout-info { @apply bg-blue-50 border-blue-200 text-blue-900; }
            .callout-success { @apply bg-green-50 border-green-200 text-green-900; }

            /* Tables */
            /* relative: contains the sr-only header labels, so they stay inside
               the table's own overflow-x-auto wrapper on narrow screens. */
            .data-table { @apply relative w-full text-sm; }
            .data-table thead th { @apply bg-gray-50 px-4 py-2.5 text-left text-xs font-semibold text-gray-600 border-b border-gray-200 whitespace-nowrap; }
            .data-table tbody td { @apply px-4 py-3 text-gray-700 border-b border-gray-100 align-middle; }
            .data-table tbody tr:last-child td { @apply border-b-0; }
            .data-table .num { @apply text-right tabular-nums; }
            .data-table tr.row-link { @apply cursor-pointer hover:bg-gray-50; }
            .data-table tfoot td, .data-table tr.row-total td { @apply px-4 py-3 border-t-2 border-gray-300 font-bold text-gray-900; }
            .table-meta { @apply px-4 py-3 border-t border-gray-200 text-xs text-gray-500; }

            /* Meters */
            .meter { @apply w-full h-1.5 rounded-full bg-gray-100 overflow-hidden; }
            .meter-fill { @apply h-full rounded-full; }

            /* Modals (backdrop display is toggled by each page: hidden/flex) */
            .modal-backdrop { @apply fixed inset-0 z-50 items-center justify-center bg-gray-900/50 p-4; }
            .modal { @apply w-full max-h-[90vh] overflow-y-auto rounded-xl bg-white shadow-xl; }
            .modal-header { @apply flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4; }
            .modal-title { @apply text-base font-semibold text-gray-900; }
            .modal-footer { @apply flex flex-wrap justify-end gap-2 border-t border-gray-200 pt-4; }

            /* Sidebar navigation */
            .nav-link { @apply relative flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium leading-5 text-[#C7D7F0] transition-colors hover:bg-white/10 hover:text-white; }
            .nav-link i { font-size: 18px; width: 18px; text-align: center; }
            .nav-link.active { @apply bg-white/15 text-white font-semibold; }
            .nav-link.active::before { content: ''; @apply absolute left-0 top-2 bottom-2 w-[3px] rounded-r bg-blue-300; }
            .nav-group-label { @apply px-3 mb-1 text-xs font-semibold text-[#A8C2E8]; }
        }
    </style>
</head>
<body>
    <div class="flex h-screen overflow-hidden">
        {{-- Backdrop, mobile only: dims the page behind the sidebar when it's
            open as an overlay. Clicking it closes the sidebar, same as
            tapping outside any other dismissible panel in this layout. --}}
        <div id="sidebar-backdrop" class="hidden fixed inset-0 bg-black/50 z-30 md:hidden"></div>

        {{-- Hidden off-screen by default on mobile (-translate-x-full),
            slides in as a fixed-position overlay when toggled -- doesn't
            push page content, matching how the notif/user dropdowns already
            overlay rather than reflow the layout. md:relative + md:translate-x-0
            fully restores the original always-visible desktop behavior;
            md:relative (not md:static) is required, not just cosmetic --
            this element's children (the watermark image, the status box)
            are absolutely/relatively positioned against IT, and `static`
            would stop it from being their containing block on desktop. --}}
        <aside id="sidebar" class="w-60 shrink-0 h-full p-3 flex flex-col overflow-hidden bg-navy fixed md:relative inset-y-0 left-0 z-40 -translate-x-full md:translate-x-0 transition-transform duration-200">
            <div class="flex items-center gap-3 px-1 pb-4 mb-4 border-b border-white/10">
                {{-- The logo's dark-navy arms disappear against the navy
                    sidebar, so the mark always sits on a white circle. --}}
                <div class="w-12 h-12 rounded-full shrink-0 bg-white flex items-center justify-center overflow-hidden">
                    <img src="/images/elikas-logo-mark.png" alt="E-LIKAS" class="w-[78%] h-[78%] object-contain">
                </div>
                <div class="leading-tight min-w-0">
                    <p class="text-white font-semibold text-base tracking-tight">E-LIKAS</p>
                    <p class="text-xs text-[#C7D7F0]">Web Dashboard</p>
                </div>
            </div>

            {{-- Grouped by what staff are doing. A group whose links are all
                hidden for the signed-in role (see the script below) hides its
                label too, so no one sees an empty heading. Scrolls on its own
                on short screens; the status box below stays pinned. --}}
            <nav class="flex-1 min-h-0 overflow-y-auto flex flex-col gap-4" aria-label="Main">
                <div class="flex flex-col gap-0.5" data-nav-group>
                    <a href="/dashboard" id="nav-dashboard-link" class="nav-link @yield('nav-dashboard')">
                        <i class="ti ti-layout-dashboard" aria-hidden="true"></i> Dashboard
                    </a>
                </div>

                <div class="flex flex-col gap-0.5" data-nav-group>
                    <p class="nav-group-label">Operations</p>
                    {{-- Sole entry path to a center's EC Board -- barangay ->
                        center -> board, skipping the occupancy/facilities-
                        focused Evacuation Centers page entirely (that page has
                        no EC Board link of its own; see show.blade.php). --}}
                    <a href="/ec-board" class="nav-link @yield('nav-ecboard')">
                        <i class="ti ti-clipboard-list" aria-hidden="true"></i> EC Board
                    </a>
                    <a href="/evacuation-events" id="nav-events-link" class="nav-link @yield('nav-events')">
                        <i class="ti ti-alert-triangle" aria-hidden="true"></i> Evacuation events
                    </a>
                    <a href="/evacuation-centers" class="nav-link @yield('nav-centers')">
                        <i class="ti ti-building" aria-hidden="true"></i> Evacuation centers
                    </a>
                    <a href="/families" class="nav-link @yield('nav-families')">
                        <i class="ti ti-users" aria-hidden="true"></i> Evacuees
                    </a>
                </div>

                <div class="flex flex-col gap-0.5" data-nav-group>
                    <p class="nav-group-label">Monitoring</p>
                    <a href="/gis-map" class="nav-link @yield('nav-gis')">
                        <i class="ti ti-map" aria-hidden="true"></i> GIS map
                    </a>
                    <a href="/alerts" class="nav-link @yield('nav-alerts')">
                        <i class="ti ti-speakerphone" aria-hidden="true"></i> Alerts
                    </a>
                    <a href="/predictive-analytics" id="nav-analytics-link" class="nav-link @yield('nav-analytics')">
                        <i class="ti ti-chart-line" aria-hidden="true"></i> Predictive analytics
                    </a>
                </div>

                <div class="flex flex-col gap-0.5" data-nav-group>
                    <p class="nav-group-label">Reports</p>
                    <a href="/reports" class="nav-link @yield('nav-reports')">
                        <i class="ti ti-file-report" aria-hidden="true"></i> <span id="nav-reports-label">DROMIC reports</span>
                    </a>
                </div>

                {{-- Starts hidden: its only link is admin-only, so non-admins
                    never see the label flash in before the script runs. --}}
                <div class="hidden flex flex-col gap-0.5" data-nav-group>
                    <p class="nav-group-label">Administration</p>
                    <a href="/users" id="nav-users-link" class="hidden nav-link @yield('nav-users')">
                        <i class="ti ti-users-group" aria-hidden="true"></i> User management
                    </a>
                </div>
            </nav>

            <div id="sidebar-status" class="shrink-0 mt-3 rounded-lg border border-white/15 bg-navy-dark px-3 py-2.5">
                <p id="sidebar-status-label" class="text-xs text-[#C7D7F0]">Loading...</p>
                <div id="sidebar-status-indicator" class="hidden items-center gap-1.5 mt-1">
                    <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse" aria-hidden="true"></span>
                    <p class="text-xs text-green-300 font-semibold">Operations active</p>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <header class="flex items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 sm:px-6 h-16 shrink-0">
                <div class="flex items-center gap-3 text-sm text-gray-600 min-w-0">
                    {{-- The toggle button itself stays always-visible on
                        mobile (md:hidden only hides it on desktop) -- only
                        the date/location text next to it collapses away
                        below md, since the button living inside this same
                        flex container must never disappear along with them. --}}
                    <button id="sidebar-toggle-btn" class="btn-icon md:hidden -ml-1" aria-label="Toggle navigation menu">
                        <i class="ti ti-menu-2" style="font-size: 20px;" aria-hidden="true"></i>
                    </button>
                    <span class="hidden md:flex items-center gap-1.5">
                        <i class="ti ti-calendar text-gray-500" style="font-size: 16px;" aria-hidden="true"></i>
                        <span id="topbar-datetime"></span>
                    </span>
                    <span class="hidden md:block w-px h-4 bg-gray-200" aria-hidden="true"></span>
                    <span class="hidden md:flex items-center gap-1.5">
                        <i class="ti ti-map-pin text-gray-500" style="font-size: 16px;" aria-hidden="true"></i>
                        Ligao City, Albay
                    </span>
                </div>
                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    {{-- Icon-only below sm (~640px) -- the full label plus
                        the avatar/name block on the same row was forcing
                        this whole bar to wrap onto two or three lines on
                        phone-width screens; the button still does exactly
                        the same thing either way, just without the label. --}}
                    <a href="/alerts?compose=1" id="topbar-alert-btn"
                        class="btn btn-danger font-semibold px-3 sm:px-4">
                        <i class="ti ti-bell-ringing" style="font-size: 16px;" aria-hidden="true"></i>
                        <span class="hidden sm:inline">Send emergency alert</span>
                    </a>
                    <div class="relative">
                        <button id="notif-bell" class="btn-icon relative w-9 h-9" aria-label="Recent alerts">
                            <i class="ti ti-bell" style="font-size: 20px;" aria-hidden="true"></i>
                            <span id="notif-badge" class="hidden absolute top-0.5 right-0.5 bg-red-600 text-white text-[11px] font-semibold leading-none rounded-full min-w-[1.125rem] h-[1.125rem] px-1 flex items-center justify-center ring-2 ring-white">0</span>
                        </button>
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 max-w-[calc(100vw-2rem)] bg-white border border-gray-200 rounded-xl shadow-lg z-50 max-h-96 overflow-y-auto">
                            <div class="px-4 py-3 border-b border-gray-200 text-sm font-semibold text-gray-900">Recent alerts</div>
                            <div id="notif-list" class="divide-y divide-gray-100"></div>
                        </div>
                    </div>
                    <div class="relative">
                        <button id="user-menu-btn" class="flex items-center gap-2.5 rounded-lg pl-1 pr-2 py-1 hover:bg-gray-50" aria-label="Account menu">
                            <div id="topbar-avatar" class="w-9 h-9 rounded-full flex items-center justify-center bg-brand text-white text-xs font-semibold shrink-0"></div>
                            <div class="hidden sm:block text-left leading-tight">
                                <p id="topbar-user-name" class="text-sm font-medium text-gray-900"></p>
                                <p id="topbar-user-role" class="text-xs text-gray-500"></p>
                            </div>
                            <i class="ti ti-chevron-down text-gray-500 hidden sm:inline" style="font-size: 14px;" aria-hidden="true"></i>
                        </button>
                        <div id="user-menu-dropdown" class="hidden absolute right-0 mt-2 w-44 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1">
                            <button onclick="Api.clear(); window.location.href = '/login';"
                                class="w-full text-left px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                                <i class="ti ti-logout" style="font-size: 15px;" aria-hidden="true"></i> Log out
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto p-4 sm:p-6" id="main-content">
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

                // Also not for barangay officials: Dashboard (mostly
                // city-wide figures, only one scoped to their barangay) and
                // Evacuation events (view-only for them, and EC Board has
                // its own event picker). Their start page is EC Board, so
                // a stray link or bookmark to either lands there instead.
                document.getElementById('nav-dashboard-link').classList.add('hidden');
                document.getElementById('nav-events-link').classList.add('hidden');
                if (/^\/(dashboard|evacuation-events)(\/|$)/.test(window.location.pathname)) {
                    window.location.replace('/ec-board');
                }

                // They can't send alerts (admin/CSWD only, enforced
                // server-side) -- same rule as the Alerts page's own button.
                document.getElementById('topbar-alert-btn').classList.add('hidden');

                // The only report they can generate is the EC Information
                // Board, so that's what this page is for them.
                document.getElementById('nav-reports-label').textContent = 'EC Information Board';
            }
        }

        // After the role rules above: a nav group with no visible links for
        // this role hides its label as well.
        document.querySelectorAll('[data-nav-group]').forEach((group) => {
            const anyVisible = [...group.querySelectorAll('.nav-link')].some((link) => ! link.classList.contains('hidden'));
            group.classList.toggle('hidden', ! anyVisible);
        });

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

        // Mobile sidebar: hidden off-screen by default (see the -translate-x-full
        // class on #sidebar), opened as a dimmed overlay by the hamburger
        // button. Closes on backdrop click, Escape, or picking a nav link --
        // all no-ops on desktop, since md:translate-x-0 already keeps the
        // sidebar visible there regardless of this class toggling.
        const sidebarEl = document.getElementById('sidebar');
        const sidebarBackdrop = document.getElementById('sidebar-backdrop');

        function openSidebar() {
            sidebarEl.classList.remove('-translate-x-full');
            sidebarBackdrop.classList.remove('hidden');
        }

        function closeSidebar() {
            sidebarEl.classList.add('-translate-x-full');
            sidebarBackdrop.classList.add('hidden');
        }

        document.getElementById('sidebar-toggle-btn').addEventListener('click', () => {
            sidebarEl.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
        });
        sidebarBackdrop.addEventListener('click', closeSidebar);
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeSidebar();
        });
        sidebarEl.querySelectorAll('.nav-link').forEach((link) => link.addEventListener('click', closeSidebar));

        // The alerts list page defines a modal and exposes window.openAlertModal
        // once it's ready. When we're already sitting on /alerts, open that
        // modal directly instead of doing a pointless full-page navigation;
        // from any other page, let the link through so it lands on
        // /alerts?compose=1 and auto-opens the modal there.
        document.getElementById('topbar-alert-btn').addEventListener('click', (e) => {
            if (window.location.pathname === '/alerts' && typeof window.openAlertModal === 'function') {
                e.preventDefault();
                window.openAlertModal();
            }
        });

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
                <div class="px-4 py-3 text-sm">
                    <p class="font-medium text-gray-900">${alert.title}</p>
                    <p class="text-gray-600 text-xs mt-0.5">${alert.message}</p>
                    <p class="text-gray-500 text-xs mt-1">${new Date(alert.created_at).toLocaleString()}</p>
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
    <script>
        // Chart typography/colors from docs/design-system.md -- every chart
        // on these pages is created after an awaited API call, so these are
        // in place before any of them render.
        if (window.Chart) {
            Chart.defaults.font.family = '"Public Sans", ui-sans-serif, system-ui, sans-serif';
            Chart.defaults.color = '#4B5563';
            Chart.defaults.borderColor = '#E5E7EB';
        }
    </script>
</body>
</html>
