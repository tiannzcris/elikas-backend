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
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-sm bg-white border border-gray-200 rounded-xl p-8">
        <div class="text-center mb-6">
            <p class="text-brand font-semibold text-xl">E-LIKAS</p>
            <p class="text-xs text-gray-400 mt-1">CSWDO Ligao City &middot; Staff login</p>
        </div>

        <div id="form-errors" class="hidden bg-red-50 text-red-700 text-sm rounded-lg p-3 mb-4"></div>

        <form id="login-form" class="flex flex-col gap-4">
            <div>
                <label class="text-sm text-gray-600 block mb-1">Email</label>
                <input type="email" id="email" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <div>
                <label class="text-sm text-gray-600 block mb-1">Password</label>
                <input type="password" id="password" required
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-brand">
            </div>
            <button type="submit" id="login-button"
                class="bg-brand hover:bg-brand-dark text-white text-sm font-medium rounded-lg py-2.5 mt-2">
                Log in
            </button>
        </form>
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

                Api.setToken(result.data.token);
                Api.setUser(result.data.user);
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
