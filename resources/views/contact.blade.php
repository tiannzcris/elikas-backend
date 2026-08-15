<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact · E-LIKAS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E', light: '#EAF0FB' } } } } };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
        @keyframes page-fade-in { from { opacity: 0; } to { opacity: 1; } }
        body { animation: page-fade-in 0.3s ease-out; }
        body.page-fade-out { opacity: 0; transition: opacity 0.2s ease-in; }
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
                <a href="/about" class="text-gray-600 hover:text-brand">About</a>
                <a href="/contact" class="text-brand border-b-2 border-brand pb-1">Contact</a>
            </nav>
        </div>
    </header>

    <main class="flex-1 flex items-center justify-center px-6 py-20">
        <div class="text-center max-w-md">
            <div class="w-14 h-14 rounded-full bg-brand-light flex items-center justify-center mx-auto mb-4">
                <i class="ti ti-tools text-brand" style="font-size: 24px;" aria-hidden="true"></i>
            </div>
            <h1 class="text-xl font-bold text-gray-900 mb-2">Contact page coming soon</h1>
            <p class="text-sm text-gray-500">This page is a placeholder while the design for it is finalized.</p>
        </div>
    </main>

    <footer class="border-t border-gray-100">
        <div class="max-w-7xl mx-auto px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-gray-400">
            <p>E-LIKAS · CSWDO Ligao City</p>
            <a href="/privacy" class="hover:text-brand">Privacy Statement</a>
        </div>
    </footer>

    <script>
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;
            if (url.pathname === window.location.pathname && url.hash) return;

            e.preventDefault();
            document.body.classList.add('page-fade-out');
            setTimeout(() => { window.location.href = link.href; }, 200);
        });

        window.addEventListener('pageshow', () => {
            document.body.classList.remove('page-fade-out');
        });
    </script>
</body>
</html>
