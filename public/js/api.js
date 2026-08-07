/**
 * Shared helper for every dashboard page. Handles:
 *  - storing the Sanctum token after login
 *  - attaching it to every API call automatically
 *  - redirecting to /login if a protected page is opened without one
 *
 * Auth here is deliberately client-side only (token in localStorage, checked
 * by JS) rather than server-side Blade sessions -- the web dashboard and the
 * Flutter app both talk to the exact same token-based API (see Chapter 3's
 * Sanctum section), so there's only one auth system to maintain, not two.
 *
 * "Remember me" makes this a real functional choice, not decorative: checked
 * -> localStorage (survives closing the browser). Unchecked -> sessionStorage
 * (gone as soon as the tab/browser closes). Reads check both, since we don't
 * know which one was used until we look.
 */
const Api = {
    baseUrl: '/api/v1',

    getToken() {
        return localStorage.getItem('elikas_token') || sessionStorage.getItem('elikas_token');
    },

    setToken(token, remember = true) {
        (remember ? localStorage : sessionStorage).setItem('elikas_token', token);
    },

    setUser(user, remember = true) {
        (remember ? localStorage : sessionStorage).setItem('elikas_user', JSON.stringify(user));
    },

    getUser() {
        const raw = localStorage.getItem('elikas_user') || sessionStorage.getItem('elikas_user');
        return raw ? JSON.parse(raw) : null;
    },

    clear() {
        localStorage.removeItem('elikas_token');
        localStorage.removeItem('elikas_user');
        sessionStorage.removeItem('elikas_token');
        sessionStorage.removeItem('elikas_user');
    },

    /**
     * Every page that requires login calls this once at the top of its
     * script. If there's no token, it never even tries the API call --
     * straight back to /login.
     */
    requireAuth() {
        if (! this.getToken()) {
            window.location.href = '/login';
        }
    },

    async request(path, options = {}) {
        const headers = {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            ...options.headers,
        };

        const token = this.getToken();
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        const response = await fetch(`${this.baseUrl}${path}`, { ...options, headers });
        const body = await response.json().catch(() => null);

        // A 401 means the token is gone/expired server-side (e.g. an admin
        // revoked it, or it was a stale token from a previous session) --
        // there's no recovering from this client-side, so send them back to
        // log in again rather than showing a confusing error on the page.
        if (response.status === 401 && path !== '/auth/login') {
            this.clear();
            window.location.href = '/login';
            return null;
        }

        if (! response.ok) {
            throw new ApiError(body?.message || 'Something went wrong.', response.status, body?.errors);
        }

        return body;
    },

    get(path) {
        return this.request(path, { method: 'GET' });
    },

    post(path, data) {
        return this.request(path, { method: 'POST', body: JSON.stringify(data) });
    },

    patch(path, data) {
        return this.request(path, { method: 'PATCH', body: JSON.stringify(data) });
    },
};

class ApiError extends Error {
    constructor(message, status, errors) {
        super(message);
        this.status = status;
        this.errors = errors; // field-level validation errors, e.g. {"members": ["..."]}
    }
}

/**
 * Renders API validation errors under an alert box with id="form-errors".
 * Every form page includes a <div id="form-errors" class="hidden">...</div>
 * and calls this in its catch block, so error display looks the same
 * everywhere instead of each page inventing its own.
 */
function showFormErrors(error) {
    const box = document.getElementById('form-errors');
    if (! box) {
        alert(error.message);
        return;
    }

    let messages = [error.message];
    if (error.errors) {
        messages = Object.values(error.errors).flat();
    }

    box.innerHTML = messages.map((m) => `<p>${m}</p>`).join('');
    box.classList.remove('hidden');
    box.scrollIntoView({ behavior: 'smooth', block: 'center' });
}
