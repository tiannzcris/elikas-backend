<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-LIKAS · CSWDO Ligao City</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#2F5496', dark: '#1F3A6E', light: '#EAF0FB' },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
        .hidden { display: none; }
    </style>
</head>
<body class="bg-white text-gray-900">

    <header class="border-b border-gray-100 sticky top-0 bg-white/95 backdrop-blur z-40">
        <div class="max-w-7xl mx-auto px-6 py-3 flex items-center justify-between gap-4">
            <a href="/" class="flex items-center gap-2.5 shrink-0">
                <img src="/images/elikas-emblem.png" alt="E-LIKAS" class="w-10 h-10 object-contain">
                <div class="leading-tight">
                    <p class="font-extrabold text-lg tracking-tight"><span class="text-red-600">E</span>-LIKAS</p>
                    <p class="text-[9px] text-gray-400 tracking-wide uppercase">Electronic Ligao Kaligtasan Sistema</p>
                </div>
            </a>
            <nav class="hidden sm:flex items-center gap-8 text-sm font-medium">
                <a href="/" class="text-brand border-b-2 border-brand pb-1">Home</a>
                <a href="/about" class="text-gray-600 hover:text-brand">About</a>
                <a href="/contact" class="text-gray-600 hover:text-brand">Contact</a>
            </nav>
        </div>
    </header>

    <section class="relative overflow-hidden" style="background: #16264D;">
        <div class="absolute -inset-4" style="background-image: url('/images/ligao-city-hall.jpg'); background-size: cover; background-position: center; filter: blur(2px);"></div>
        <div class="absolute inset-0" style="background: linear-gradient(120deg, rgba(15,28,58,0.94) 40%, rgba(31,58,110,0.72));"></div>

        <div class="relative max-w-7xl mx-auto px-6 py-20 sm:py-28 grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <p class="text-xs font-semibold tracking-widest text-blue-300 uppercase mb-3">Electronic Ligao Kaligtasan Sistema</p>
                <h1 class="text-4xl sm:text-5xl font-extrabold text-white leading-tight mb-5">Know where to go, before you need to.</h1>
                <p class="text-blue-100/80 text-base mb-8 max-w-lg">
                    E-LIKAS helps residents of Ligao City find evacuation centers, track disaster
                    alerts, and stay prepared — with or without an internet connection.
                </p>
                <div class="flex flex-wrap gap-3">
                    <button type="button" id="mobile-app-btn"
                        class="flex items-center gap-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg px-5 py-3">
                        <i class="ti ti-device-mobile" style="font-size: 16px;" aria-hidden="true"></i> Get the Mobile App
                    </button>
                    <button type="button" id="about-modal-open-btn"
                        class="flex items-center gap-2 border border-white/30 text-white text-sm font-semibold rounded-lg px-5 py-3 hover:bg-white/10">
                        <i class="ti ti-info-circle" style="font-size: 16px;" aria-hidden="true"></i> What is E-LIKAS?
                    </button>
                </div>
            </div>

            <div class="hidden lg:flex justify-center">
                <img src="/images/elikas-emblem.png" alt="" class="w-72 h-72 object-contain" style="filter: drop-shadow(0 15px 35px rgba(0,0,0,0.45));">
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 py-16 sm:py-20">
        <div class="text-center mb-12 max-w-2xl mx-auto">
            <p class="text-xs font-semibold tracking-widest text-brand uppercase mb-2">What it does</p>
            <h2 class="text-2xl sm:text-3xl font-bold text-gray-900">Built around the two moments that matter most in a disaster.</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border border-gray-200 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                        <i class="ti ti-calendar-check text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900">Before a disaster</p>
                        <p class="text-xs text-brand font-medium">Stay ahead of it.</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-start gap-3 py-3">
                        <i class="ti ti-map-pin text-brand shrink-0 mt-0.5" style="font-size: 16px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Hazard maps</p>
                            <p class="text-xs text-gray-500">View hazard and risk areas in Ligao City.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 py-3">
                        <i class="ti ti-bell text-brand shrink-0 mt-0.5" style="font-size: 16px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Real-time alerts</p>
                            <p class="text-xs text-gray-500">Receive important updates and announcements early.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 py-3">
                        <i class="ti ti-phone text-brand shrink-0 mt-0.5" style="font-size: 16px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Emergency hotlines</p>
                            <p class="text-xs text-gray-500">One tap access to important contacts when you need help.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="border border-gray-200 rounded-2xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-11 h-11 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                        <i class="ti ti-door-exit text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900">During a disaster</p>
                        <p class="text-xs text-brand font-medium">Get to safety.</p>
                    </div>
                </div>
                <div class="divide-y divide-gray-100">
                    <div class="flex items-start gap-3 py-3">
                        <i class="ti ti-map-pin-filled text-brand shrink-0 mt-0.5" style="font-size: 16px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Find nearest evacuation center</p>
                            <p class="text-xs text-gray-500">See open evacuation centers near you.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 py-3">
                        <i class="ti ti-route text-brand shrink-0 mt-0.5" style="font-size: 16px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Get directions</p>
                            <p class="text-xs text-gray-500">Navigate your way to a safe place.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 py-3">
                        <i class="ti ti-wifi-off text-brand shrink-0 mt-0.5" style="font-size: 16px;" aria-hidden="true"></i>
                        <div>
                            <p class="text-sm font-semibold text-gray-800">Offline access</p>
                            <p class="text-xs text-gray-500">Access last-loaded information even without an internet connection.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- The one "Staff Login" entry point on this page (the small
            header link was removed as a duplicate) -- plain href straight
            to the real /login form, no modal/intermediate page. --}}
        <div class="mt-6 bg-brand-light rounded-2xl p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-5 text-center sm:text-left">
            <div>
                <p class="text-xs font-semibold tracking-widest text-brand uppercase mb-1">For CSWDO &amp; barangay staff</p>
                <p class="text-lg font-bold text-gray-900">Manage centers, alerts, and evacuee records.</p>
            </div>
            <div class="shrink-0">
                <a href="/login"
                    class="inline-flex items-center gap-2 bg-brand-dark hover:bg-brand text-white text-sm font-semibold rounded-lg px-5 py-3">
                    <i class="ti ti-lock" style="font-size: 16px;" aria-hidden="true"></i> Staff Login
                </a>
                <p class="text-xs text-gray-500 mt-2">Secure access for authorized staff.</p>
            </div>
        </div>
    </section>

    <footer class="text-white" style="background: #16264D;">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 sm:grid-cols-2 gap-10">
            <div>
                <div class="flex items-center gap-2.5 mb-3">
                    <img src="/images/elikas-emblem.png" alt="" class="w-9 h-9 object-contain">
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

    {{-- "What is E-LIKAS?" modal -- popup over the homepage with a
        blurred background; closing (X or "Back to Home") returns to this
        same page without navigating away. --}}
    <div id="about-modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
        <div id="about-modal-backdrop" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <button type="button" id="about-modal-close-1" class="flex items-center gap-2 text-sm font-medium text-gray-600 border border-gray-200 rounded-full pl-2 pr-4 py-1.5 hover:bg-gray-50">
                    <span class="w-6 h-6 rounded-full border border-gray-200 flex items-center justify-center">
                        <i class="ti ti-arrow-left" style="font-size: 13px;" aria-hidden="true"></i>
                    </span>
                    Back to Home
                </button>
                <button type="button" id="about-modal-close-2" class="w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                    <i class="ti ti-x" style="font-size: 16px;" aria-hidden="true"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-6">
                <div class="lg:col-span-2">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-3">What is E-LIKAS?</h2>
                    <div class="w-12 h-1 bg-brand rounded-full mb-4"></div>

                    <p class="text-sm text-gray-600 mb-4">
                        E-LIKAS (Electronic Ligao Kaligtasan Sistema) is a disaster evacuation
                        management system built for the residents and disaster response staff of
                        Ligao City, Albay.
                    </p>
                    <p class="text-sm text-gray-600">
                        The system connects two sides of disaster response: <strong>residents</strong>,
                        who need <strong>fast, reliable information about where to go and what's
                        happening</strong>, and <strong>CSWDO and barangay staff</strong>, who need to
                        coordinate evacuation centers, track evacuees, and send alerts --
                        <strong>even when internet connectivity is unreliable</strong>.
                    </p>
                </div>
                <div class="hidden lg:flex items-center justify-center">
                    <img src="/images/elikas-emblem.png" alt="" class="w-40 h-40 object-contain">
                </div>
            </div>

            <div class="divide-y divide-gray-100 border-t border-gray-100 mb-6">
                <div class="flex items-start gap-4 py-4">
                    <div class="w-10 h-10 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                        <i class="ti ti-device-mobile text-brand" style="font-size: 18px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm text-gray-600">
                        For <strong>residents</strong>, E-LIKAS means checking a mobile app to see
                        open evacuation centers, hazard maps, and real-time alerts, without needing
                        an account.
                    </p>
                </div>
                <div class="flex items-start gap-4 py-4">
                    <div class="w-10 h-10 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                        <i class="ti ti-users text-brand" style="font-size: 18px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm text-gray-600">
                        For <strong>staff</strong>, it means a coordinated system for managing
                        evacuation centers, registering evacuee families, and issuing disaster
                        advisories -- with an offline mode built specifically for the moments when a
                        stable connection can't be counted on.
                    </p>
                </div>
                <div class="flex items-start gap-4 py-4">
                    <div class="w-10 h-10 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                        <i class="ti ti-wifi-off text-brand" style="font-size: 18px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm text-gray-600">
                        E-LIKAS is designed to work even when the internet can't be relied on,
                        ensuring that information, records, and alerts are always available when it
                        matters most.
                    </p>
                </div>
                <div class="flex items-start gap-4 py-4">
                    <div class="w-10 h-10 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                        <i class="ti ti-school text-brand" style="font-size: 18px;" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm text-gray-600">
                        E-LIKAS began as a Bachelor of Science in Information Technology capstone
                        project, developed with guidance from CSWDO Ligao City.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-brand-light rounded-xl p-5">
                <div class="flex items-start gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shrink-0">
                        <i class="ti ti-device-mobile text-brand" style="font-size: 16px;" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">For Residents</p>
                        <p class="text-xs text-gray-500">Check alerts, evacuation centers, and hazard maps anytime, anywhere.</p>
                    </div>
                </div>
                <div class="flex items-start gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shrink-0">
                        <i class="ti ti-users text-brand" style="font-size: 16px;" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">For Staff</p>
                        <p class="text-xs text-gray-500">Manage centers, register evacuees, and send advisories efficiently.</p>
                    </div>
                </div>
                <div class="flex items-start gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-white flex items-center justify-center shrink-0">
                        <i class="ti ti-wifi-off text-brand" style="font-size: 16px;" aria-hidden="true"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-gray-900">Built for Unreliable Connections</p>
                        <p class="text-xs text-gray-500">Offline-capable tools when internet can't be relied on.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- "Get the Mobile App" modal -- the homepage-specific entry point.
        Content/wording reused verbatim from
        resources/views/download-mobile-app.blade.php (that dedicated page
        stays untouched and keeps working -- it's linked from elsewhere,
        e.g. SMS messages). Shows the download warnings BEFORE the real
        download link, rather than linking straight to the .apk. --}}
    <div id="mobile-app-modal" class="hidden fixed inset-0 z-50 items-center justify-center p-4">
        <div id="mobile-app-modal-backdrop" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl max-w-md w-full max-h-[90vh] overflow-y-auto p-8 text-center">
            <button type="button" id="mobile-app-modal-close" class="absolute top-4 right-4 w-9 h-9 rounded-full border border-gray-200 flex items-center justify-center text-gray-500 hover:bg-gray-50">
                <i class="ti ti-x" style="font-size: 16px;" aria-hidden="true"></i>
            </button>

            <p class="text-brand font-bold text-2xl mb-1">E-LIKAS</p>
            <p class="text-sm text-gray-500 mb-6">Mobile App for Residents</p>

            <p class="text-sm text-gray-600 mb-6 text-left">
                Get real-time disaster alerts, find the nearest evacuation center, check
                hazard maps, and access emergency hotlines. No account needed, and it
                works offline once you've opened it at least once.
            </p>

            <a href="{{ url(config('elikas.mobile_app_download_url', '/downloads/E-LIKAS-Mobile.apk') ?? '/downloads/E-LIKAS-Mobile.apk') }}"
                class="block text-center bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg py-3 transition-colors shadow-sm">
                Download for Android
            </a>

            <p class="text-xs text-gray-400 mt-4 text-left">
                Your browser may show a warning during the download itself and ask you to
                keep/confirm the file -- this is expected. Your phone may then ask you to
                allow installs from this source since the app isn't on the Play Store yet --
                go to Settings and allow it when prompted, then open the downloaded file to
                install.
            </p>
        </div>
    </div>

    <script>
        function openModal(id) {
            const el = document.getElementById(id);
            el.classList.remove('hidden');
            el.classList.add('flex');
        }

        function closeModal(id) {
            const el = document.getElementById(id);
            el.classList.add('hidden');
            el.classList.remove('flex');
        }

        document.getElementById('about-modal-open-btn').addEventListener('click', () => openModal('about-modal'));
        document.getElementById('about-modal-close-1').addEventListener('click', () => closeModal('about-modal'));
        document.getElementById('about-modal-close-2').addEventListener('click', () => closeModal('about-modal'));
        document.getElementById('about-modal-backdrop').addEventListener('click', () => closeModal('about-modal'));

        document.getElementById('mobile-app-btn').addEventListener('click', () => openModal('mobile-app-modal'));
        document.getElementById('mobile-app-modal-close').addEventListener('click', () => closeModal('mobile-app-modal'));
        document.getElementById('mobile-app-modal-backdrop').addEventListener('click', () => closeModal('mobile-app-modal'));

        document.addEventListener('keydown', (e) => {
            if (e.key !== 'Escape') return;
            closeModal('about-modal');
            closeModal('mobile-app-modal');
        });
    </script>
</body>
</html>
