<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About · E-LIKAS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E', light: '#EAF0FB' } } } } };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }

        /* Page transition: content collapses toward center on the way
           out, then snaps into place with a slight overshoot -- reads as
           "suddenly forming" rather than a slow fade. Distinct from AOS's
           scroll-triggered reveals below, which animate individual
           sections as they enter the viewport within a page. */
        @keyframes page-reform {
            0% { opacity: 0; transform: scale(0.94); }
            60% { opacity: 1; transform: scale(1.01); }
            100% { opacity: 1; transform: scale(1); }
        }
        body { animation: page-reform 0.4s cubic-bezier(0.16, 1, 0.3, 1); transform-origin: center top; }
        body.page-collapse { opacity: 0; transform: scale(0.94); transition: opacity 0.25s ease-in, transform 0.25s ease-in; }

        /* Lead paragraph: a slightly larger, lighter-weight treatment for
           the first paragraph under a heading, distinct from the smaller
           supporting text under it -- gives desktop readers a size step
           beyond "everything is text-sm", without changing anything on
           mobile. */
        .lead-text { font-size: 1rem; line-height: 1.7; color: #4B5563; }
        @media (min-width: 1024px) {
            .lead-text { font-size: 1.125rem; line-height: 1.8; }
        }
    </style>
</head>
<body class="bg-white text-gray-900 min-h-screen flex flex-col">

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
                <a href="/" class="text-gray-600 hover:text-brand">Home</a>
                <a href="/about" class="text-brand border-b-2 border-brand pb-1">About</a>
                <a href="/contact" class="text-gray-600 hover:text-brand">Contact</a>
            </nav>
            <button type="button" id="mobile-menu-btn" class="sm:hidden w-9 h-9 flex items-center justify-center text-gray-600 hover:text-brand" aria-label="Open menu" aria-expanded="false" aria-controls="mobile-menu">
                <i class="ti ti-menu-2" id="mobile-menu-icon" style="font-size: 22px;" aria-hidden="true"></i>
            </button>
        </div>
        <nav id="mobile-menu" class="hidden sm:hidden border-t border-gray-100 bg-white">
            <div class="max-w-7xl mx-auto px-6 py-3 flex flex-col gap-1 text-sm font-medium">
                <a href="/" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Home</a>
                <a href="/about" class="block px-3 py-2 rounded-lg text-brand bg-brand-light font-semibold">About</a>
                <a href="/contact" class="block px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-50">Contact</a>
            </div>
        </nav>
    </header>

    <main class="flex-1">
        <section class="max-w-6xl mx-auto px-6 py-16 sm:py-20">
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-12 items-start">
                <div class="lg:col-span-3" data-aos="fade-right">
                    <p class="text-xs font-semibold tracking-widest text-brand uppercase mb-2">About</p>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-gray-900 mb-3">About E-LIKAS</h1>
                    <div class="w-12 h-1 bg-brand rounded-full mb-5"></div>
                    <p class="lead-text mb-10 max-w-xl">
                        Disaster response depends on information reaching the right people at
                        the right time — residents need to know where it's safe to go, and
                        responders need to know who needs help and where. E-LIKAS was built
                        around that simple idea.
                    </p>

                    <div class="space-y-8">
                        <div class="flex gap-4" data-aos="fade-up" data-aos-delay="0">
                            <div class="w-11 h-11 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                                <i class="ti ti-target text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-gray-900 lg:text-lg mb-1">Our Mission</h2>
                                <p class="text-sm lg:text-base text-gray-600 lg:leading-relaxed">
                                    To make evacuation information clear, current, and accessible to every
                                    resident of Ligao City — whether they're checking a phone at home or a
                                    staff member is registering families at an evacuation center with no
                                    signal.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4" data-aos="fade-up" data-aos-delay="100">
                            <div class="w-11 h-11 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                                <i class="ti ti-sitemap text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-gray-900 lg:text-lg mb-1">How It Works</h2>
                                <p class="text-sm lg:text-base text-gray-600 lg:leading-relaxed">
                                    E-LIKAS is made up of three connected tools: a web dashboard for CSWDO
                                    and barangay staff to manage evacuation centers, alerts, and evacuee
                                    records; an offline-capable companion app for registering evacuees in
                                    the field, even without internet; and a mobile app for residents to
                                    check alerts, evacuation centers, and hazard maps, no account required.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-4" data-aos="fade-up" data-aos-delay="200">
                            <div class="w-11 h-11 rounded-full bg-brand-light flex items-center justify-center shrink-0">
                                <i class="ti ti-school text-brand" style="font-size: 20px;" aria-hidden="true"></i>
                            </div>
                            <div>
                                <h2 class="font-bold text-gray-900 lg:text-lg mb-1">Where We Started</h2>
                                <p class="text-sm lg:text-base text-gray-600 lg:leading-relaxed">
                                    E-LIKAS began as a capstone project for the Bachelor of Science in
                                    Information Technology program at Infotech Development System
                                    Colleges, developed with the guidance and cooperation of the City
                                    Social Welfare and Development Office (CSWDO) of Ligao City.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 lg:pt-8" data-aos="fade-left">
                    <img src="/images/about-dashboard-mockup.png" alt="E-LIKAS web dashboard and mobile app" class="w-full h-auto object-contain">
                </div>
            </div>
        </section>
    </main>

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
            document.body.classList.add('page-collapse');
            setTimeout(() => { window.location.href = link.href; }, 250);
        });

        window.addEventListener('pageshow', () => {
            document.body.classList.remove('page-collapse');
        });
    </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        AOS.init({ duration: 600, once: true, offset: 60, easing: 'ease-out-cubic' });
    </script>
</body>
</html>
