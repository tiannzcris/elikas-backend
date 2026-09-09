<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Hotlines · E-LIKAS</title>
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
                <a href="/find-evacuation-centers" class="text-gray-600 hover:text-brand">Evacuation Centers</a>
                <a href="/hotlines" class="text-brand border-b-2 border-brand pb-1">Hotlines</a>
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
                <a href="/find-evacuation-centers" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Evacuation Centers</a>
                <a href="/hotlines" class="block px-3 py-2 rounded-lg text-brand bg-brand-light font-semibold">Hotlines</a>
                <a href="/contact" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Contact</a>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        <section class="relative overflow-hidden" style="background: #16264D;">
            <div class="absolute inset-0" style="background: linear-gradient(120deg, rgba(15,28,58,0.94) 40%, rgba(31,58,110,0.72));"></div>
            <div class="relative max-w-7xl mx-auto px-6 py-14 sm:py-16">
                <p class="text-xs font-semibold tracking-widest text-blue-300 uppercase mb-3" data-aos="fade-up" data-aos-duration="500">One tap away</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white leading-tight mb-3" data-aos="fade-up" data-aos-duration="600" data-aos-delay="100">Emergency Hotlines</h1>
                <p class="text-blue-100/80 text-base max-w-2xl" data-aos="fade-up" data-aos-duration="600" data-aos-delay="200">
                    Important contact numbers for Ligao City -- the same list shown in the E-LIKAS
                    mobile app. Tap a number on your phone to call directly.
                </p>
            </div>
        </section>

        <section class="max-w-4xl mx-auto px-6 py-12 sm:py-16">
            {{--
                PENDING: exact phone numbers not yet confirmed here.

                The mobile app's hotline list lives in a compiled Dart/Flutter
                binary (features/emergency_hotlines/domain/hotline.dart) that
                isn't present in this backend repo, so it can't be read
                directly the way the rest of this task's data sources could.
                Six real Philippine mobile numbers were recovered from the
                compiled app for cross-checking, but WHICH number belongs to
                WHICH organization below could not be reliably determined
                without risking a wrong pairing -- unacceptable for emergency
                contact information. Fill in the real number for each
                organization below once confirmed, then remove this comment
                and the amber "Number pending confirmation" badges.
            --}}
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl p-4 mb-8 flex items-start gap-3">
                <i class="ti ti-alert-triangle text-amber-500 shrink-0 mt-0.5" style="font-size: 18px;" aria-hidden="true"></i>
                <p>Phone numbers below are pending final confirmation against the mobile app and will be updated shortly.</p>
            </div>

            <div class="flex flex-col gap-4">
                @php
                    // Matches the 6 organizations already confirmed as the
                    // mobile app's hardcoded hotline list -- numbers are
                    // intentionally left null (see comment above) rather
                    // than guessed.
                    $hotlines = [
                        ['name' => 'CSWDO Ligao City', 'description' => 'City Social Welfare and Development Office -- disaster response coordination and assistance.', 'icon' => 'ti-building-community', 'number' => null],
                        ['name' => 'MDRRMO Ligao', 'description' => 'Municipal/City Disaster Risk Reduction and Management Office -- disaster response and coordination.', 'icon' => 'ti-alert-triangle', 'number' => null],
                        ['name' => 'Philippine Red Cross', 'description' => 'Emergency medical assistance, rescue, and relief operations.', 'icon' => 'ti-first-aid-kit', 'number' => null],
                        ['name' => 'PNP Ligao City', 'description' => 'Philippine National Police -- peace and order, emergency police response.', 'icon' => 'ti-shield-check', 'number' => null],
                        ['name' => 'BFP Ligao City', 'description' => 'Bureau of Fire Protection -- fire emergency response.', 'icon' => 'ti-flame', 'number' => null],
                        ['name' => 'City Health Office', 'description' => 'Medical concerns, health emergencies, and public health advisories.', 'icon' => 'ti-heartbeat', 'number' => null],
                    ];
                @endphp

                @foreach ($hotlines as $hotline)
                    <div class="border border-gray-200 rounded-2xl p-5 sm:p-6 flex items-center gap-4" data-aos="fade-up">
                        <div class="w-12 h-12 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                            <i class="ti {{ $hotline['icon'] }} text-brand" style="font-size: 22px;" aria-hidden="true"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900">{{ $hotline['name'] }}</p>
                            <p class="text-xs text-gray-500 mb-2">{{ $hotline['description'] }}</p>
                            @if ($hotline['number'])
                                <a href="tel:{{ $hotline['number'] }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-brand hover:text-brand-dark">
                                    <i class="ti ti-phone" style="font-size: 14px;" aria-hidden="true"></i> {{ $hotline['number'] }}
                                </a>
                            @else
                                <span class="inline-flex items-center gap-1.5 text-xs font-medium text-amber-700 bg-amber-50 rounded-full px-2.5 py-1">
                                    <i class="ti ti-clock" style="font-size: 13px;" aria-hidden="true"></i> Number pending confirmation
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true, offset: 60, easing: 'ease-out-cubic' });
    </script>
</body>
</html>
