<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Download the Mobile App · E-LIKAS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E', light: '#EAF0FB' } } } } };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen p-6 sm:p-8">
    {{-- Was min-h-screen + flex items-center justify-center: vertically
        centering against the FULL viewport height is unstable on mobile --
        the browser's address bar shows/hides as the page loads/scrolls,
        changing the effective viewport height AFTER the layout already
        rendered, which shifts this centered content (including the
        Download button) out from under a tap that was aimed a moment
        earlier. A simple top-anchored layout with its own top padding
        doesn't have that problem, since it's never computed from the
        viewport height at all. --}}
    {{-- Same layout as download-desktop-app.blade.php, so the two
        download pages read as one set. --}}
    <div class="w-full max-w-lg mx-auto pt-6 sm:pt-16">
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2.5 mb-8">
            <img src="/images/elikas-logo-mark.png" alt="" class="w-9 h-9 object-contain">
            <div class="leading-tight text-center">
                <p class="font-extrabold text-base tracking-tight"><span class="text-[#094776]">E-LIKAS</span></p>
                <p class="text-[9px] text-gray-500 tracking-wide uppercase">Electronic Ligao Kaligtasan Sistema</p>
            </div>
        </a>

        <div class="bg-white border border-gray-200 rounded-2xl p-6 sm:p-8 shadow-sm">
            <div class="flex flex-col items-center text-center mb-6">
                <div class="w-14 h-14 rounded-full bg-brand-light flex items-center justify-center shrink-0 mb-4">
                    <i class="ti ti-device-mobile text-brand" style="font-size: 26px;" aria-hidden="true"></i>
                </div>
                <h1 class="text-xl font-bold text-gray-900 mb-1">Mobile App for Residents</h1>
                <p class="text-sm text-gray-500 max-w-sm">
                    Real-time disaster alerts, evacuation centers, and hazard maps -- no account needed.
                </p>
            </div>

            <p class="text-sm text-gray-600 border-y border-gray-100 py-4 mb-6">
                Get real-time disaster alerts, find the nearest evacuation center, check
                hazard maps, and access emergency hotlines. No account needed, and it
                works offline once you've opened it at least once.
            </p>

            {{-- download attribute: the phone saves the .apk directly rather
                than navigating to the file URL. --}}
            <a href="{{ url(config('elikas.mobile_app_download_url', '/downloads/E-LIKAS-Mobile.apk') ?? '/downloads/E-LIKAS-Mobile.apk') }}" download
                class="flex items-center justify-center gap-2 bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg py-3 transition-colors shadow-sm">
                <i class="ti ti-brand-android" style="font-size: 16px;" aria-hidden="true"></i> Download for Android
            </a>

            {{-- Amber, same "don't be alarmed" heads-up as the desktop page. --}}
            <div class="flex items-start gap-2 bg-amber-50 text-amber-800 text-xs rounded-lg p-3 mt-4">
                <i class="ti ti-alert-circle shrink-0 mt-0.5" style="font-size: 15px;" aria-hidden="true"></i>
                <p>
                    Your browser may show a warning during the download itself and ask you to
                    keep/confirm the file -- this is expected. Your phone may then ask you to
                    allow installs from this source since the app isn't on the Play Store yet --
                    go to Settings and allow it when prompted, then open the downloaded file to
                    install.
                </p>
            </div>
        </div>

        <a href="{{ url('/privacy') }}" class="block text-center text-xs text-gray-500 hover:text-gray-600 mt-6">
            Privacy Statement
        </a>
    </div>
</body>
</html>
