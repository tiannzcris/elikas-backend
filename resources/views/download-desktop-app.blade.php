<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Download the Desktop App · E-LIKAS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E', light: '#EAF0FB' } } } } };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }

        {{-- Same page-transition polish as the rest of the public site
            (home.blade.php etc.) -- this page previously had neither,
            which was part of why it read as visually disconnected from
            everything else. --}}
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
{{-- Top-anchored, not viewport-height-centered -- see
    download-mobile-app.blade.php's own note on why vh-based centering is
    unstable on mobile (shifts under a tap as the browser chrome resizes). --}}
<body class="bg-gray-50 min-h-screen p-6 sm:p-8">
    <div id="page-loading-bar"></div>

    <div class="w-full max-w-lg mx-auto pt-6 sm:pt-16">
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2.5 mb-8">
            <img src="/images/elikas-emblem-icon.png" alt="" class="w-9 h-9 object-contain">
            <div class="leading-tight text-center">
                <p class="font-extrabold text-base tracking-tight"><span class="text-red-600">E</span>-LIKAS</p>
                <p class="text-[9px] text-gray-500 tracking-wide uppercase">Electronic Ligao Kaligtasan Sistema</p>
            </div>
        </a>

        <div class="bg-white border border-gray-200 rounded-2xl p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-14 h-14 rounded-full bg-brand-light flex items-center justify-center shrink-0 mb-4">
                    <i class="ti ti-device-desktop text-brand" style="font-size: 26px;" aria-hidden="true"></i>
                </div>
                <h1 class="text-xl font-bold text-gray-900 mb-1">Desktop Companion App</h1>
                <p class="text-sm text-gray-500 max-w-sm">
                    Register families while offline, right from the computer at your barangay hall.
                </p>
            </div>

            <div class="divide-y divide-gray-100 border-y border-gray-100 mb-6">
                <div class="flex items-start gap-3 py-3">
                    <i class="ti ti-wifi-off text-brand shrink-0 mt-0.5" style="font-size: 18px;" aria-hidden="true"></i>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Works fully offline</p>
                        <p class="text-xs text-gray-500">Keep registering families even with no internet connection at all.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 py-3">
                    <i class="ti ti-refresh text-brand shrink-0 mt-0.5" style="font-size: 18px;" aria-hidden="true"></i>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Syncs automatically</p>
                        <p class="text-xs text-gray-500">Everything you register uploads to E-LIKAS on its own once the connection returns.</p>
                    </div>
                </div>
                <div class="flex items-start gap-3 py-3">
                    <i class="ti ti-download text-brand shrink-0 mt-0.5" style="font-size: 18px;" aria-hidden="true"></i>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">One-time install</p>
                        <p class="text-xs text-gray-500">Set it up once on the computer you'll use for on-the-ground registration.</p>
                    </div>
                </div>
            </div>

            <a href="{{ url(config('elikas.desktop_app_download_url', '/downloads/E-LIKAS-Setup.exe') ?? '/downloads/E-LIKAS-Setup.exe') }}"
                class="flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg py-3 transition-colors shadow-sm">
                <i class="ti ti-brand-windows" style="font-size: 16px;" aria-hidden="true"></i> Download for Windows
            </a>

            {{-- Amber, not plain gray -- this is a genuine "don't be
                alarmed" heads-up, same pending/attention color convention
                already established elsewhere in this app (e.g. the EC
                Board's own sectoral-figures notice), not just decorative. --}}
            <div class="flex items-start gap-2 bg-amber-50 text-amber-800 text-xs rounded-lg p-3 mt-4">
                <i class="ti ti-alert-circle shrink-0 mt-0.5" style="font-size: 15px;" aria-hidden="true"></i>
                <p>
                    Your browser may show a warning during the download itself and ask you to
                    click "Keep" (Chrome) or similar to continue -- this is expected, not a sign
                    of a problem. Windows may then show a second security warning during install
                    since this app isn't yet digitally signed -- click "More info" then "Run
                    anyway" to continue.
                </p>
            </div>
        </div>

        <p class="text-sm text-gray-500 mt-6 text-center">
            Already have it installed? <a href="{{ url('/login') }}" class="text-brand hover:underline font-medium">Log in to the web dashboard</a> instead.
        </p>
        <a href="{{ url('/privacy') }}" class="block text-center text-xs text-gray-500 hover:text-gray-600 mt-4">
            Privacy Statement
        </a>
    </div>

    <script>
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
</body>
</html>
