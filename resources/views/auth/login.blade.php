<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in · E-LIKAS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2F5496', dark: '#1F3A6E' } } } } };
    </script>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; }
        .hidden { display: none; }
        .input-field {
            width: 100%; background: #F8F9FB; border: 1px solid #E5E7EB; border-radius: 10px;
            padding: 11px 14px; font-size: 14px; transition: border-color .15s, box-shadow .15s;
        }
        .input-field:focus {
            outline: none; border-color: #2F5496; background: white;
            box-shadow: 0 0 0 3px rgba(47,84,150,0.12);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <div class="flex-1 flex items-center justify-center p-8">
        <div class="w-full max-w-sm">
            <p class="text-xs font-semibold tracking-widest text-brand uppercase mb-2">E-LIKAS Staff Portal</p>
            <h1 class="text-3xl font-bold tracking-tight text-gray-900 mb-1">Welcome back</h1>
            <p class="text-sm text-gray-500 mb-8">Log in to the E-LIKAS staff dashboard.</p>

            <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

            <form id="login-form" class="flex flex-col gap-4">
                <div>
                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wide block mb-1.5">Email</label>
                    <input type="email" id="email" required placeholder="name@ligaocity.gov.ph" class="input-field">
                </div>
                <div>
                    <label class="text-xs font-medium text-gray-600 uppercase tracking-wide block mb-1.5">Password</label>
                    <input type="password" id="password" required class="input-field">
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-600 select-none cursor-pointer">
                    <input type="checkbox" id="remember-me" class="rounded border-gray-300 text-brand focus:ring-brand">
                    Remember me
                </label>

                <button type="submit" id="login-button"
                    class="bg-brand hover:bg-brand-dark text-white text-sm font-semibold rounded-lg py-3 mt-2 transition-colors shadow-sm">
                    Log in
                </button>
            </form>
        </div>
    </div>

    <div class="hidden md:block flex-1 relative overflow-hidden">
        <div class="absolute -inset-4" style="background-image: url('/images/ligao-city-hall.jpg'); background-size: cover; background-position: center; filter: blur(5px);"></div>
        <div class="absolute inset-0" style="background: linear-gradient(160deg, rgba(31,58,110,0.88), rgba(47,84,150,0.72));"></div>

        <div class="relative h-full flex flex-col items-center justify-center text-center px-8">
            <div class="flex items-center gap-5 mb-7">
                <img src="/images/ligao-city-seal.jpg" alt="Official Seal of the City Government of Ligao"
                    class="w-20 h-20 rounded-full ring-4 ring-white/25 shadow-lg object-cover">
                <img src="/images/cswdo-ligao-logo.jpg" alt="CSWDO Ligao City logo"
                    class="w-20 h-20 rounded-full ring-4 ring-white/25 shadow-lg object-cover">
            </div>
            <p class="text-white font-bold text-4xl tracking-tight mb-2">E-LIKAS</p>
            <p class="text-sm tracking-wide" style="color: #CFE0F5;">CSWDO Ligao City</p>
        </div>
    </div>

    <script src="/js/api.js"></script>
    <script>
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
                window.location.href = '/dashboard';
            } catch (error) {
                showFormErrors(error);
                button.disabled = false;
                button.textContent = 'Log in';
            }
        });
    </script>
</body>
</html>
