<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in · E-LIKAS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    @include('partials.design-system')
</head>
<body class="min-h-screen flex">
    <main class="flex-1 flex items-center justify-center p-4 sm:p-8">
        <div class="w-full max-w-sm">
            <div class="flex items-center gap-3 mb-8">
                <img src="/images/elikas-logo-mark.png" alt="" class="w-12 h-12 shrink-0">
                <div class="leading-tight">
                    <p class="text-lg font-semibold tracking-tight text-navy">E-LIKAS</p>
                    <p class="text-xs text-gray-600">Staff portal, CSWDO Ligao City</p>
                </div>
            </div>

            <h1 class="text-2xl leading-8 font-semibold tracking-tight text-gray-900">Welcome back</h1>
            <p class="text-sm text-gray-600 mt-1 mb-6">Log in to the E-LIKAS staff dashboard.</p>

            <div id="form-errors" class="hidden callout callout-danger mb-4" role="alert"></div>

            <form id="login-form" class="card p-5 sm:p-6 flex flex-col gap-4">
                <div>
                    <label for="email" class="label">Email</label>
                    <input type="email" id="email" required autocomplete="username" placeholder="name@ligaocity.gov.ph" class="input py-2.5">
                </div>
                <div>
                    <label for="password" class="label">Password</label>
                    <div class="relative">
                        <input type="password" id="password" required autocomplete="current-password" class="input py-2.5 pr-11">
                        <button type="button" id="toggle-password" tabindex="-1"
                            class="absolute right-0 top-0 h-full w-11 flex items-center justify-center text-gray-500 hover:text-gray-900"
                            aria-label="Show password" aria-pressed="false">
                            <i class="ti ti-eye" id="toggle-password-icon" style="font-size: 18px;" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700 select-none cursor-pointer">
                    <input type="checkbox" id="remember-me" class="w-4 h-4">
                    Remember me
                </label>

                <button type="submit" id="login-button" class="btn btn-primary w-full py-2.5 mt-1">
                    Log in
                </button>
            </form>

            <a href="/privacy" class="block text-center text-sm text-gray-600 hover:text-brand hover:underline underline-offset-2 mt-6">
                Privacy Statement
            </a>
        </div>
    </main>

    {{-- Institutional panel: Ligao City Hall behind the logo navy, with the
        city seal and the CSWDO logo -- who this portal belongs to. --}}
    <aside class="hidden md:block flex-1 relative overflow-hidden">
        <div class="absolute -inset-4" style="background-image: url('/images/ligao-city-hall.jpg'); background-size: cover; background-position: center; filter: blur(5px);"></div>
        <div class="absolute inset-0" style="background: linear-gradient(160deg, rgba(7,58,97,0.92), rgba(9,71,118,0.80));"></div>

        <div class="relative h-full flex flex-col items-center justify-center text-center px-8">
            <div class="flex items-center gap-5 mb-8">
                <img src="/images/ligao-city-seal.jpg" alt="Official Seal of the City Government of Ligao"
                    class="w-20 h-20 rounded-full ring-4 ring-white/25 shadow-lg object-cover">
                <img src="/images/cswdo-ligao-logo.jpg" alt="CSWDO Ligao City logo"
                    class="w-20 h-20 rounded-full ring-4 ring-white/25 shadow-lg object-cover">
            </div>
            <p class="text-white font-semibold text-4xl tracking-tight mb-2">E-LIKAS</p>
            <p class="text-sm text-[#C7D7F0]">CSWDO Ligao City</p>
        </div>
    </aside>

    <script src="/js/api.js"></script>
    <script>
        document.getElementById('toggle-password').addEventListener('click', () => {
            const input = document.getElementById('password');
            const icon = document.getElementById('toggle-password-icon');
            const button = document.getElementById('toggle-password');

            const showing = input.type === 'password';
            input.type = showing ? 'text' : 'password';
            icon.className = showing ? 'ti ti-eye-off' : 'ti ti-eye';
            button.setAttribute('aria-label', showing ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', showing ? 'true' : 'false');
        });

        document.getElementById('login-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            const button = document.getElementById('login-button');
            button.disabled = true;
            button.textContent = 'Logging in...';

            try {
                const result = await Api.post('/auth/login', {
                    email: document.getElementById('email').value,
                    password: document.getElementById('password').value,
                });

                // Remember me: checked -> localStorage (persists after the
                // browser closes, current default behavior). Unchecked ->
                // sessionStorage (cleared as soon as the tab/browser
                // closes) -- a real functional difference, not just a
                // decorative checkbox.
                const remember = document.getElementById('remember-me').checked;
                Api.setToken(result.data.token, remember);
                Api.setUser(result.data.user, remember);
                // Barangay officials start on EC Board -- their Dashboard
                // would be mostly city-wide figures (see layouts/app).
                window.location.href = result.data.user.role === 'barangay_official' ? '/ec-board' : '/dashboard';
            } catch (error) {
                showFormErrors(error);
                button.disabled = false;
                button.textContent = 'Log in';
            }
        });
    </script>
</body>
</html>
