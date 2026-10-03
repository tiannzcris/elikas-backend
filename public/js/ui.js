/**
 * Shared dashboard UI pieces (see docs/design-system.md, "Confirm dialog"
 * and "Toasts"). Loaded by layouts/app.blade.php after api.js, so every
 * staff page can use them:
 *
 *   if (! await Ui.confirm({ title: 'Close this event?', message: '...', confirmLabel: 'Close event' })) return;
 *   Ui.toast('Alert sent');
 *   Ui.toastAfterRedirect('Family registered'); window.location.href = '/families';
 */
const Ui = (() => {
    const escapeHtml = (text) => String(text).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);

    // --- Confirm dialog ---------------------------------------------------

    // Replaces the browser's confirm(): same question, but in the app's own
    // modal, with buttons that say what they do. Resolves true only when the
    // confirm button is pressed; Cancel, Escape and a click on the dimmed
    // backdrop all resolve false.
    let confirmEl = null;

    function buildConfirm() {
        document.body.insertAdjacentHTML('beforeend', `
            <div id="ui-confirm" class="hidden modal-backdrop z-[70]">
                <div class="modal max-w-md p-5" role="alertdialog" aria-modal="true"
                    aria-labelledby="ui-confirm-title" aria-describedby="ui-confirm-message">
                    <div class="flex items-start gap-3">
                        <div id="ui-confirm-icon" class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                            <i class="ti ti-alert-triangle text-red-600" style="font-size: 18px;" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 id="ui-confirm-title" class="modal-title"></h2>
                            <p id="ui-confirm-message" class="mt-1 text-sm text-gray-700"></p>
                        </div>
                    </div>
                    <div class="flex flex-wrap justify-end gap-2 mt-5">
                        <button type="button" id="ui-confirm-cancel" class="btn btn-secondary"></button>
                        <button type="button" id="ui-confirm-ok" class="btn"></button>
                    </div>
                </div>
            </div>`);
        confirmEl = document.getElementById('ui-confirm');
    }

    /**
     * tone: 'danger' (deletes or removes something; solid red confirm, the
     * design system's "final confirm inside a delete dialog"), 'neutral'
     * (a committing action that isn't destructive) or 'primary'.
     */
    function confirm({ title, message = '', confirmLabel = 'Confirm', cancelLabel = 'Cancel', tone = 'danger' }) {
        if (! confirmEl) buildConfirm();

        const okBtn = document.getElementById('ui-confirm-ok');
        const cancelBtn = document.getElementById('ui-confirm-cancel');
        document.getElementById('ui-confirm-title').textContent = title;
        document.getElementById('ui-confirm-message').textContent = message;
        document.getElementById('ui-confirm-message').classList.toggle('hidden', ! message);
        document.getElementById('ui-confirm-icon').classList.toggle('hidden', tone !== 'danger');
        okBtn.textContent = confirmLabel;
        okBtn.className = `btn ${{ danger: 'btn-danger', neutral: 'btn-neutral', primary: 'btn-primary' }[tone] ?? 'btn-primary'}`;
        cancelBtn.textContent = cancelLabel;

        const opener = document.activeElement;
        confirmEl.classList.remove('hidden');
        confirmEl.classList.add('flex');
        // Destructive: the safe answer has focus, so a stray Enter cancels.
        (tone === 'danger' ? cancelBtn : okBtn).focus();

        return new Promise((resolve) => {
            const finish = (answer) => {
                confirmEl.classList.add('hidden');
                confirmEl.classList.remove('flex');
                okBtn.removeEventListener('click', onOk);
                cancelBtn.removeEventListener('click', onCancel);
                confirmEl.removeEventListener('click', onBackdrop);
                document.removeEventListener('keydown', onKey, true);
                if (opener && document.contains(opener)) opener.focus();
                resolve(answer);
            };
            const onOk = () => finish(true);
            const onCancel = () => finish(false);
            const onBackdrop = (e) => { if (e.target === confirmEl) finish(false); };
            // Capture phase, and stopped there: a page's own Escape handler
            // (an open modal underneath) must not also fire.
            const onKey = (e) => {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    finish(false);
                } else if (e.key === 'Tab') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    (document.activeElement === okBtn ? cancelBtn : okBtn).focus();
                }
            };

            okBtn.addEventListener('click', onOk);
            cancelBtn.addEventListener('click', onCancel);
            confirmEl.addEventListener('click', onBackdrop);
            document.addEventListener('keydown', onKey, true);
        });
    }

    // --- Toasts -----------------------------------------------------------

    // A short confirmation ("Saved", "Alert sent") in the bottom corner. It
    // never blocks the page: nothing else on screen is covered or disabled,
    // and it goes away by itself (paused while the pointer or focus is on it).
    let toastRegion = null;

    const toastTones = {
        success: { icon: 'ti-circle-check', color: 'text-green-400', duration: 4000 },
        info: { icon: 'ti-info-circle', color: 'text-blue-300', duration: 4000 },
        // Something done, but staff still need to act on it: stays until
        // dismissed.
        warning: { icon: 'ti-alert-triangle', color: 'text-amber-300', duration: 0 },
        danger: { icon: 'ti-alert-circle', color: 'text-red-300', duration: 7000 },
    };

    function toast(message, { tone = 'success', duration } = {}) {
        if (! toastRegion) {
            document.body.insertAdjacentHTML('beforeend',
                '<div id="ui-toasts" class="fixed z-[80] bottom-4 inset-x-4 sm:inset-x-auto sm:right-6 sm:bottom-6 flex flex-col items-stretch sm:items-end gap-2 pointer-events-none" role="status" aria-live="polite"></div>');
            toastRegion = document.getElementById('ui-toasts');
        }

        const look = toastTones[tone] ?? toastTones.success;
        const el = document.createElement('div');
        el.className = 'ui-toast';
        el.innerHTML = `
            <i class="ti ${look.icon} ${look.color} shrink-0" style="font-size: 18px;" aria-hidden="true"></i>
            <p class="flex-1 min-w-0">${escapeHtml(message)}</p>
            <button type="button" class="ui-toast-close" aria-label="Dismiss"><i class="ti ti-x" style="font-size: 16px;" aria-hidden="true"></i></button>`;
        toastRegion.append(el);

        let timer = null;
        const dismiss = () => {
            clearTimeout(timer);
            el.classList.add('ui-toast-leaving');
            setTimeout(() => el.remove(), 200);
        };
        const start = () => {
            const ms = duration ?? look.duration;
            if (ms > 0) timer = setTimeout(dismiss, ms);
        };
        const pause = () => clearTimeout(timer);

        el.querySelector('.ui-toast-close').addEventListener('click', dismiss);
        el.addEventListener('mouseenter', pause);
        el.addEventListener('mouseleave', start);
        el.addEventListener('focusin', pause);
        el.addEventListener('focusout', start);
        start();
    }

    // For a save that navigates to another page: the toast shows there,
    // once, right after it loads.
    function toastAfterRedirect(message, options = {}) {
        try {
            sessionStorage.setItem('elikas_toast', JSON.stringify({ message, options }));
        } catch (e) {
            // Storage unavailable: the save still happened; skip the toast.
        }
    }

    function showPendingToast() {
        let pending = null;
        try {
            pending = JSON.parse(sessionStorage.getItem('elikas_toast') || 'null');
            sessionStorage.removeItem('elikas_toast');
        } catch (e) {
            return;
        }
        if (pending?.message) toast(pending.message, pending.options);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showPendingToast);
    } else {
        showPendingToast();
    }

    return { confirm, toast, toastAfterRedirect, escapeHtml };
})();
