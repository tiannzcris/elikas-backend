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
        // FormData (file uploads) needs the browser to set its own
        // Content-Type with the correct multipart boundary -- forcing
        // application/json here would send a body the boundary doesn't
        // match, and the server would fail to parse it at all.
        const isFormData = options.body instanceof FormData;

        const headers = {
            Accept: 'application/json',
            ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
            ...options.headers,
        };

        const token = this.getToken();
        if (token) {
            headers.Authorization = `Bearer ${token}`;
        }

        // cache: 'no-store' -- every page reads live server state through
        // this one shared helper (EC Board's quick-count included), so a
        // stale browser-cached GET response here would silently hide
        // whatever just changed server-side until a hard refresh. Placed
        // before ...options so a call site can still override it
        // explicitly if a future caller ever genuinely needs to.
        const response = await fetch(`${this.baseUrl}${path}`, { cache: 'no-store', ...options, headers });
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
 * Shows an API error. Every form page includes a
 * <div id="form-errors" class="hidden callout callout-danger"></div> and
 * calls this in its catch block, so error display looks the same everywhere
 * instead of each page inventing its own.
 *
 * Pass `form` (the form element or its id) and each field-level validation
 * error is shown directly under its own field instead (see "Form inputs" in
 * docs/design-system.md). The box then keeps only what has no field on
 * screen, plus a one-line pointer to the highlighted fields. Fields are
 * found, in order, by:
 *   fields[key]     -- an id, an element, or a function returning one
 *   fieldFor(key)   -- for keys like members.2.first_name
 *   #{prefix}{key}  -- the usual case: the field's id is the API key
 *   [name="{key}"]
 * Options: { form, box (element or id, default 'form-errors'), prefix, fields, fieldFor }.
 */
function showFormErrors(error, options = {}) {
    const byId = (elOrId) => (typeof elOrId === 'string' ? document.getElementById(elOrId) : elOrId);
    // box: null means "fields only" -- the page's box is then used only for
    // a message that has no field on screen.
    const fieldsOnly = 'box' in options && options.box === null;
    let box = fieldsOnly ? null : byId(options.box ?? 'form-errors');
    const form = byId(options.form ?? null);

    if (form) clearFormErrors(form);

    if (! box && ! form) {
        alert(error.message);
        return;
    }

    const unplaced = [];
    let placed = 0;
    let firstField = null;

    if (error.errors) {
        for (const [key, messages] of Object.entries(error.errors)) {
            const field = form ? findErrorField(form, key, options) : null;
            if (field) {
                markFieldError(field, [].concat(messages).join(' '));
                placed++;
                firstField ??= field;
            } else {
                unplaced.push(...[].concat(messages));
            }
        }
    } else {
        unplaced.push(error.message);
    }

    if (fieldsOnly && unplaced.length) box = document.getElementById('form-errors');

    if (box && (unplaced.length || ! fieldsOnly)) {
        const lines = [...unplaced];
        if (placed && ! fieldsOnly) {
            lines.push(placed === 1 ? 'Check the highlighted field.' : `Check the ${placed} highlighted fields.`);
        }
        box.innerHTML = lines.map((m) => `<p>${escapeErrorText(m)}</p>`).join('');
        box.classList.remove('hidden');
    }

    if (firstField) {
        firstField.focus({ preventScroll: true });
        firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (box) {
        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/** Removes every inline field error (and hides nothing else) inside `form`. */
function clearFormErrors(form) {
    const scope = typeof form === 'string' ? document.getElementById(form) : form;
    if (! scope) return;
    scope.querySelectorAll('.field-error').forEach((p) => p.remove());
    scope.querySelectorAll('[aria-invalid="true"]').forEach((field) => unmarkField(field));
}

function escapeErrorText(text) {
    return String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
}

function findErrorField(form, key, options) {
    const resolve = (found) => (typeof found === 'string' ? document.getElementById(found) : found);
    const visible = (el) => (el && el.getClientRects().length ? el : null);

    const mapped = options.fields?.[key];
    if (mapped) return visible(resolve(typeof mapped === 'function' ? mapped(key) : mapped));

    const viaFn = options.fieldFor ? resolve(options.fieldFor(key)) : null;
    if (viaFn) return visible(viaFn);

    return visible(form.querySelector(`#${CSS.escape((options.prefix ?? '') + key)}`))
        || visible(form.querySelector(`[name="${CSS.escape(key)}"]`));
}

function markFieldError(field, message) {
    // A checkbox's message goes under its whole label, not beside the box.
    let anchor = ['checkbox', 'radio'].includes(field.type) ? (field.closest('label') ?? field) : field;

    // A field sharing a positioned wrapper with an overlaid button or icon
    // (password show/hide, lock icons): the message goes after the whole
    // wrapper, or the overlay would stretch over it.
    const holder = anchor.parentElement;
    if (holder && getComputedStyle(holder).position === 'relative'
        && [...holder.children].some((child) => child !== anchor && getComputedStyle(child).position === 'absolute')) {
        anchor = holder;
    }

    // A field sitting directly in a grid or flex row gets a wrapper first,
    // so the message lands under it inside the same cell instead of taking
    // a cell of its own. Column-span/flex sizing moves to the wrapper.
    const parentDisplay = anchor.parentElement ? getComputedStyle(anchor.parentElement).display : '';
    if (/grid|flex/.test(parentDisplay)) {
        const wrap = document.createElement('div');
        wrap.className = 'min-w-0';
        wrap.dataset.fieldWrap = '';
        [...anchor.classList]
            .filter((c) => /^([a-z0-9]+:)?(col-span-|flex-1$|grow$|basis-)/.test(c))
            .forEach((c) => wrap.classList.add(c));
        const hadFocus = document.activeElement === field;
        anchor.replaceWith(wrap);
        wrap.append(anchor);
        if (hadFocus) field.focus({ preventScroll: true });
        anchor = wrap;
    }

    const p = document.createElement('p');
    p.className = 'field-error';
    p.id = `field-error-${Math.random().toString(36).slice(2, 9)}`;
    p.innerHTML = `<i class="ti ti-alert-circle shrink-0" style="font-size: 14px; margin-top: 1px;" aria-hidden="true"></i><span>${escapeErrorText(message)}</span>`;
    // Inside the new wrapper when one was made, otherwise right after the
    // field (or its label, or its positioned wrapper).
    if (anchor.dataset?.fieldWrap !== undefined) {
        anchor.append(p);
    } else {
        anchor.insertAdjacentElement('afterend', p);
    }

    field.setAttribute('aria-invalid', 'true');
    field.dataset.errorId = p.id;
    field.setAttribute('aria-describedby', [field.getAttribute('aria-describedby'), p.id].filter(Boolean).join(' '));

    // Editing the field clears its message.
    const clear = () => {
        p.remove();
        unmarkField(field);
        field.removeEventListener('input', clear);
        field.removeEventListener('change', clear);
    };
    field.addEventListener('input', clear);
    field.addEventListener('change', clear);
}

function unmarkField(field) {
    const errorId = field.dataset.errorId;
    field.removeAttribute('aria-invalid');
    if (errorId) {
        document.getElementById(errorId)?.remove();
        const rest = (field.getAttribute('aria-describedby') || '').split(' ').filter((id) => id && id !== errorId);
        rest.length ? field.setAttribute('aria-describedby', rest.join(' ')) : field.removeAttribute('aria-describedby');
        delete field.dataset.errorId;
    }
}
