<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alerts · E-LIKAS</title>
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
                <a href="/community-alerts" class="text-brand border-b-2 border-brand pb-1">Alerts</a>
                <a href="/find-evacuation-centers" class="text-gray-600 hover:text-brand">Evacuation Centers</a>
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
                <a href="/community-alerts" class="block px-3 py-2 rounded-lg text-brand bg-brand-light font-semibold">Alerts</a>
                <a href="/find-evacuation-centers" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Evacuation Centers</a>
                <a href="/hotlines" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Hotlines</a>
                <a href="/contact" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Contact</a>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        <section class="relative overflow-hidden" style="background: #16264D;">
            <div class="absolute inset-0" style="background: linear-gradient(120deg, rgba(15,28,58,0.94) 40%, rgba(31,58,110,0.72));"></div>
            <div class="relative max-w-7xl mx-auto px-6 py-14 sm:py-16">
                <p class="text-xs font-semibold tracking-widest text-blue-300 uppercase mb-3" data-aos="fade-up" data-aos-duration="500">Stay informed</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-3" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100">Community Alerts</h1>
                <p class="text-blue-100/80 text-base max-w-2xl" data-aos="fade-up" data-aos-duration="600" data-aos-delay="200">
                    Official alerts and advisories sent by CSWDO Ligao City -- no account needed. This
                    list refreshes with the same alerts sent through the E-LIKAS mobile app.
                </p>
            </div>
        </section>

        <section class="max-w-4xl mx-auto px-6 py-12 sm:py-16">
            <div id="alerts-loading" class="text-center text-gray-400 text-sm py-16">
                <i class="ti ti-loader-2" style="font-size: 28px;" aria-hidden="true"></i>
                <p class="mt-2">Loading alerts...</p>
            </div>

            <div id="alerts-error" class="hidden text-center text-gray-500 text-sm py-16 border border-gray-200 rounded-2xl">
                <i class="ti ti-wifi-off text-gray-300" style="font-size: 32px;" aria-hidden="true"></i>
                <p class="mt-2">Unable to load alerts right now. Please try again in a moment.</p>
            </div>

            <div id="alerts-empty" class="hidden text-center text-gray-500 text-sm py-16 border border-gray-200 rounded-2xl">
                <i class="ti ti-bell-off text-gray-300" style="font-size: 32px;" aria-hidden="true"></i>
                <p class="mt-2">No alerts have been sent yet. Check back later.</p>
            </div>

            <div id="alerts-list" class="flex flex-col gap-4"></div>

            <div class="text-center mt-8">
                <button type="button" id="load-more-btn" class="hidden text-sm font-medium text-brand border border-brand/30 rounded-lg px-5 py-2.5 hover:bg-brand-light">
                    Load more
                </button>
            </div>
        </section>
    </main>

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

    {{-- Same envelope/error handling the staff dashboard uses (Api.get(),
        {success,message,data} response shape) -- but this page never calls
        Api.requireAuth(), and /public/alerts needs no bearer token, so it
        works the same for a visitor with no account at all. --}}
    <script src="/js/api.js"></script>
    <script>
        const severityStyles = {
            mandatory: { badge: 'bg-red-50 text-red-700', label: 'Mandatory evacuation', icon: 'ti-alert-triangle-filled', iconColor: 'text-red-600' },
            advisory: { badge: 'bg-orange-50 text-orange-700', label: 'Advisory', icon: 'ti-info-circle', iconColor: 'text-orange-500' },
            info: { badge: 'bg-blue-50 text-blue-700', label: 'Info', icon: 'ti-info-circle', iconColor: 'text-blue-500' },
            all_clear: { badge: 'bg-green-50 text-green-700', label: 'All clear', icon: 'ti-circle-check', iconColor: 'text-green-600' },
        };
        const typeLabels = {
            typhoon: 'Typhoon', flood: 'Flood', volcanic: 'Volcanic',
            earthquake: 'Earthquake', general_advisory: 'General advisory',
        };

        let currentPage = 1;
        let lastPage = 1;

        // Alert title/message are staff-authored free text, not sanitized
        // at creation time -- escaped here since this renders via
        // innerHTML on a public, unauthenticated page.
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text ?? '';
            return div.innerHTML;
        }

        function formatDate(iso) {
            if (!iso) return '';
            return new Date(iso).toLocaleString('en-PH', {
                year: 'numeric', month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
            });
        }

        function renderAlerts(alerts, append) {
            const html = alerts.map((a) => {
                const style = severityStyles[a.severity] ?? severityStyles.info;
                const typeLabel = typeLabels[a.alert_type] ?? a.alert_type;
                return `
                    <article class="border border-gray-200 rounded-2xl p-5 sm:p-6" data-aos="fade-up">
                        <div class="flex items-start gap-4">
                            <div class="w-11 h-11 rounded-full ${style.badge} flex items-center justify-center shrink-0">
                                <i class="ti ${style.icon} ${style.iconColor}" style="font-size: 20px;" aria-hidden="true"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full ${style.badge}">${style.label}</span>
                                    <span class="text-xs text-gray-400">${typeLabel}</span>
                                </div>
                                <h2 class="font-bold text-gray-900 text-lg mb-1">${escapeHtml(a.title)}</h2>
                                <p class="text-sm text-gray-600 mb-3 whitespace-pre-line">${escapeHtml(a.message)}</p>
                                <p class="text-xs text-gray-400">
                                    <i class="ti ti-clock" style="font-size: 12px;" aria-hidden="true"></i>
                                    ${formatDate(a.date_sent ?? a.created_at)}
                                </p>
                            </div>
                        </div>
                    </article>`;
            }).join('');

            const list = document.getElementById('alerts-list');
            list.innerHTML = append ? list.innerHTML + html : html;
        }

        async function loadAlerts(page) {
            try {
                const result = await Api.get(`/public/alerts?page=${page}`);
                const payload = result.data; // {data: [...], links, meta}
                const alerts = payload.data ?? [];

                document.getElementById('alerts-loading').classList.add('hidden');

                if (page === 1 && alerts.length === 0) {
                    document.getElementById('alerts-empty').classList.remove('hidden');
                    return;
                }

                renderAlerts(alerts, page > 1);

                currentPage = payload.meta?.current_page ?? page;
                lastPage = payload.meta?.last_page ?? page;

                const loadMoreBtn = document.getElementById('load-more-btn');
                loadMoreBtn.classList.toggle('hidden', currentPage >= lastPage);
            } catch (error) {
                document.getElementById('alerts-loading').classList.add('hidden');
                document.getElementById('alerts-error').classList.remove('hidden');
            }
        }

        document.getElementById('load-more-btn').addEventListener('click', () => {
            loadAlerts(currentPage + 1);
        });

        loadAlerts(1);
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true, offset: 60, easing: 'ease-out-cubic' });
    </script>
</body>
</html>
