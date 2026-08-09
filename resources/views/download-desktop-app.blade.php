<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download the Desktop App · E-LIKAS</title>
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
            <h1 class="text-xl font-bold text-gray-900 mb-1 text-center">Desktop Companion App</h1>
            <p class="text-sm text-gray-500 mb-6 text-center">
                Register families while offline, right from the computer at your barangay hall.
            </p>

            <p class="text-sm text-gray-600 mb-6">
                Install this once on the computer you'll use for on-the-ground registration.
                It works even with no internet connection, and syncs everything back to
                E-LIKAS automatically once the connection returns.
            </p>

            <a href="{{ url(config('elikas.desktop_app_download_url')) }}"
                class="block text-center bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg py-3 transition-colors shadow-sm">
                Download for Windows
            </a>

            <p class="text-xs text-gray-400 mt-4 text-center">
                Windows may show a security warning during install since this app isn't yet
                digitally signed -- click "More info" then "Run anyway" to continue. This is
                expected, not a sign of a problem.
            </p>
        </div>

        <p class="text-sm text-gray-500 mt-6 text-center">
            Already have it installed? <a href="{{ url('/login') }}" class="text-brand hover:underline">Log in to the web dashboard</a> instead.
        </p>
    </div>
</body>
</html>
