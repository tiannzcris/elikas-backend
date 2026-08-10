<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Download the Mobile App · E-LIKAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E' } } } } };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-8">
    <div class="w-full max-w-md">
        <p class="text-xs font-semibold tracking-widest text-brand uppercase mb-2 text-center">E-LIKAS</p>

        <div class="bg-white border border-gray-200 rounded-2xl p-8 shadow-sm">
            <h1 class="text-xl font-bold text-gray-900 mb-1 text-center">Mobile App for Residents</h1>
            <p class="text-sm text-gray-500 mb-6 text-center">
                Real-time disaster alerts, evacuation centers, and hazard maps -- no account needed.
            </p>

            <p class="text-sm text-gray-600 mb-6">
                Get real-time disaster alerts, find the nearest evacuation center, check
                hazard maps, and access emergency hotlines. No account needed, and it
                works offline once you've opened it at least once.
            </p>

            <a href="{{ url(config('elikas.mobile_app_download_url')) }}"
                class="block text-center bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg py-3 transition-colors shadow-sm">
                Download for Android
            </a>

            <p class="text-xs text-gray-400 mt-4 text-center">
                Your browser may show a warning during the download itself and ask you to
                keep/confirm the file -- this is expected. Your phone may then ask you to
                allow installs from this source since the app isn't on the Play Store yet --
                go to Settings and allow it when prompted, then open the downloaded file to
                install.
            </p>
        </div>

        <a href="{{ url('/privacy') }}" class="block text-center text-xs text-gray-400 hover:text-gray-600 mt-6">
            Privacy Statement
        </a>
    </div>
</body>
</html>
