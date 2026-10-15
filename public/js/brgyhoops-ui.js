/* =========================================================
   BrgyHoops UI feedback (no dependencies)

   1. Toasts      — server flash messages rendered in [data-toast-region]
                    are re-announced through an aria-live region; new ones
                    via window.BrgyHoops.toast(type, message).
   2. Confirm     — <form data-confirm="Message" data-confirm-title="…"
                    data-confirm-action="Delete" data-confirm-variant="danger">
                    opens an accessible <dialog>; Cancel/Escape keeps the form
                    unsubmitted. Confirm resubmits with requestSubmit(), so the
                    method, CSRF token, submitter and HTML validation are kept.
   3. Loading     — on a real submission the submit button shows a spinner
                    and data-loading-text; repeat submits are blocked. Fields
                    are never disabled, so no values are dropped. State resets
                    when the page is restored from the back/forward cache.
                    Opt out with <form data-no-loading>.
   ========================================================= */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------------------------------------------------
       Toasts
       --------------------------------------------------------- */
    var TOAST_TYPES = {
        success: { icon: 'fa-circle-check', title: 'Success', timeout: 5000 },
        info: { icon: 'fa-circle-info', title: 'Notice', timeout: 6000 },
        warning: { icon: 'fa-triangle-exclamation', title: 'Warning', timeout: 8000 },
        // Errors stay until dismissed so they can be read at any pace.
        error: { icon: 'fa-circle-exclamation', title: 'Error', timeout: 0 }
    };

    function toastRegion() {
        var region = document.querySelector('[data-toast-region]');

        if (!region) {
            region = document.createElement('div');
            region.className = 'bh-toast-region';
            region.setAttribute('data-toast-region', '');
            document.body.appendChild(region);
        }

        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-relevant', 'additions');

        return region;
    }

    function dismissToast(toast) {
        if (!toast || toast.getAttribute('data-leaving') === '1') {
            return;
        }

        toast.setAttribute('data-leaving', '1');
        window.clearTimeout(toast._bhTimer);

        if (reduceMotion) {
            toast.remove();
            return;
        }

        toast.classList.add('is-leaving');
        window.setTimeout(function () {
            toast.remove();
        }, 230);
    }

    function startTimer(toast, timeout) {
        window.clearTimeout(toast._bhTimer);

        if (timeout > 0) {
            toast._bhTimer = window.setTimeout(function () {
                dismissToast(toast);
            }, timeout);
        }
    }

    function showToast(type, message) {
        var config = TOAST_TYPES[type] || TOAST_TYPES.info;
        var text = String(message || '').trim();

        if (text === '') {
            return null;
        }

        type = TOAST_TYPES[type] ? type : 'info';

        var region = toastRegion();
        var key = type + '|' + text;

        // No duplicates: restart the timer of an identical visible toast.
        var existing = Array.prototype.find.call(region.children, function (child) {
            return child.getAttribute('data-toast-key') === key && child.getAttribute('data-leaving') !== '1';
        });

        if (existing) {
            startTimer(existing, config.timeout);
            return existing;
        }

        var toast = document.createElement('div');
        toast.className = 'bh-toast bh-toast-' + type + (reduceMotion ? '' : ' is-entering');
        toast.setAttribute('data-toast-key', key);
        // Errors interrupt; everything else is announced politely.
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

        var icon = document.createElement('i');
        icon.className = 'fa-solid ' + config.icon + ' bh-toast-icon';
        icon.setAttribute('aria-hidden', 'true');

        var body = document.createElement('div');
        body.className = 'bh-toast-body';

        var title = document.createElement('strong');
        title.className = 'bh-toast-title';
        title.textContent = config.title;

        var content = document.createElement('span');
        content.textContent = text; // textContent: never interpreted as HTML

        body.appendChild(title);
        body.appendChild(content);

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'bh-toast-close';
        close.setAttribute('aria-label', 'Dismiss notification');
        close.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
        close.addEventListener('click', function () {
            dismissToast(toast);
        });

        toast.appendChild(icon);
        toast.appendChild(body);
        toast.appendChild(close);

        // Pause auto-dismiss while the user is hovering or focused on it.
        toast.addEventListener('mouseenter', function () { window.clearTimeout(toast._bhTimer); });
        toast.addEventListener('mouseleave', function () { startTimer(toast, config.timeout); });
        toast.addEventListener('focusin', function () { window.clearTimeout(toast._bhTimer); });
        toast.addEventListener('focusout', function () { startTimer(toast, config.timeout); });

        region.appendChild(toast);

        if (!reduceMotion) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    toast.classList.remove('is-entering');
                });
            });
        }

        startTimer(toast, config.timeout);

        return toast;
    }

    // Server-rendered toasts: remove the static copies and re-add them after
    // the live region is registered, so screen readers announce them.
    function initServerToasts() {
        var region = document.querySelector('[data-toast-region]');

        if (!region) {
            return;
        }

        var pending = Array.prototype.map.call(region.querySelectorAll('[data-toast]'), function (element) {
            var item = {
                type: element.getAttribute('data-toast'),
                message: element.getAttribute('data-toast-message') || element.textContent
            };

            element.remove();

            return item;
        });

        toastRegion();

        window.setTimeout(function () {
            pending.forEach(function (item) {
                showToast(item.type, item.message);
            });
        }, 120);
    }

    /* ---------------------------------------------------------
       Confirmation dialog
       --------------------------------------------------------- */
    var dialog = null;

    function buildDialog() {
        if (dialog) {
            return dialog;
        }

        dialog = document.createElement('dialog');
        dialog.className = 'bh-dialog';
        dialog.setAttribute('aria-labelledby', 'bh-dialog-title');
        dialog.setAttribute('aria-describedby', 'bh-dialog-message');
        dialog.innerHTML =
            '<div class="bh-dialog-inner">' +
                '<div class="bh-dialog-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></div>' +
                '<h2 class="bh-dialog-title" id="bh-dialog-title"></h2>' +
                '<p class="bh-dialog-message" id="bh-dialog-message"></p>' +
                '<div class="bh-dialog-actions">' +
                    '<button type="button" class="bh-dialog-cancel" data-dialog-cancel>Cancel</button>' +
                    '<button type="button" class="bh-dialog-confirm" data-dialog-confirm>Confirm</button>' +
                '</div>' +
            '</div>';

        document.body.appendChild(dialog);

        return dialog;
    }

    function confirmAction(form, opener) {
        var message = form.getAttribute('data-confirm') || 'Are you sure?';

        // Very old browsers without <dialog>: fall back to the native prompt.
        if (typeof window.HTMLDialogElement !== 'function') {
            return Promise.resolve(window.confirm(message));
        }

        var element = buildDialog();
        var danger = form.getAttribute('data-confirm-variant') === 'danger';
        var confirmButton = element.querySelector('[data-dialog-confirm]');
        var cancelButton = element.querySelector('[data-dialog-cancel]');
        var icon = element.querySelector('.bh-dialog-icon i');

        element.classList.toggle('bh-dialog-danger', danger);
        icon.className = 'fa-solid ' + (danger ? 'fa-triangle-exclamation' : 'fa-circle-question');
        element.querySelector('.bh-dialog-title').textContent = form.getAttribute('data-confirm-title') || 'Please confirm';
        element.querySelector('.bh-dialog-message').textContent = message;
        confirmButton.textContent = form.getAttribute('data-confirm-action') || 'Confirm';

        return new Promise(function (resolve) {
            var settled = false;

            function finish(result) {
                if (settled) {
                    return;
                }

                settled = true;
                confirmButton.removeEventListener('click', onConfirm);
                cancelButton.removeEventListener('click', onCancel);
                element.removeEventListener('cancel', onCancel);
                element.removeEventListener('click', onBackdrop);

                if (element.open) {
                    element.close();
                }

                // Return focus to the button that opened the dialog.
                if (!result && opener && typeof opener.focus === 'function') {
                    opener.focus();
                }

                resolve(result);
            }

            function onConfirm() { finish(true); }
            function onCancel(event) {
                if (event) {
                    event.preventDefault();
                }
                finish(false);
            }
            function onBackdrop(event) {
                if (event.target === element) {
                    finish(false);
                }
            }

            confirmButton.addEventListener('click', onConfirm);
            cancelButton.addEventListener('click', onCancel);
            element.addEventListener('cancel', onCancel); // Escape key
            element.addEventListener('click', onBackdrop);

            element.showModal();

            // Destructive actions start on Cancel; others on Confirm.
            (danger ? cancelButton : confirmButton).focus();
        });
    }

    /* ---------------------------------------------------------
       Loading state
       --------------------------------------------------------- */
    function defaultSubmitter(form) {
        return form.querySelector('button[type="submit"], button:not([type]), input[type="submit"]');
    }

    function setLoading(form, button) {
        form.setAttribute('data-submitting', '1');
        form.setAttribute('aria-busy', 'true');

        if (!button || button.getAttribute('data-bh-loading') === '1') {
            return;
        }

        var text = button.getAttribute('data-loading-text') || 'Please wait…';

        button.setAttribute('data-bh-loading', '1');
        button.setAttribute('aria-disabled', 'true');
        button.classList.add('bh-is-loading');

        if (button.tagName === 'INPUT') {
            button._bhOriginal = button.value;
            button.value = text;
            return;
        }

        // Keep the width stable while the label changes.
        button.style.minWidth = button.offsetWidth + 'px';
        button._bhOriginal = button.innerHTML;
        button.innerHTML = '';

        var spinner = document.createElement('span');
        spinner.className = 'bh-spinner';
        spinner.setAttribute('aria-hidden', 'true');

        var label = document.createElement('span');
        label.textContent = text;

        button.appendChild(spinner);
        button.appendChild(label);
    }

    function resetLoading(root) {
        Array.prototype.forEach.call((root || document).querySelectorAll('[data-submitting]'), function (form) {
            form.removeAttribute('data-submitting');
            form.removeAttribute('data-confirmed');
            form.removeAttribute('aria-busy');
        });

        Array.prototype.forEach.call((root || document).querySelectorAll('[data-bh-loading]'), function (button) {
            if (button.tagName === 'INPUT') {
                button.value = button._bhOriginal || button.value;
            } else if (button._bhOriginal !== undefined) {
                button.innerHTML = button._bhOriginal;
            }

            button.style.minWidth = '';
            button.classList.remove('bh-is-loading');
            button.removeAttribute('aria-disabled');
            button.removeAttribute('data-bh-loading');
        });
    }

    function wantsLoading(form) {
        if (form.hasAttribute('data-no-loading') || (form.getAttribute('target') || '') === '_blank') {
            return false;
        }

        var method = (form.getAttribute('method') || 'get').toLowerCase();

        if (method === 'dialog') {
            return false;
        }

        // GET forms (searches) only when they opt in with data-loading-text.
        return method !== 'get' || !!form.querySelector('[data-loading-text]');
    }

    function resubmit(form, submitter) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);

            // requestSubmit() runs synchronously. If HTML validation blocked it,
            // no submit event fired: ask again next time.
            form.removeAttribute('data-confirmed');
            return;
        }

        // Fallback without requestSubmit(): submit() skips the submit event.
        if (wantsLoading(form)) {
            setLoading(form, submitter || defaultSubmitter(form));
        }

        HTMLFormElement.prototype.submit.call(form);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;

        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }

        var submitter = event.submitter || defaultSubmitter(form);

        // Already submitting: swallow double clicks / repeated Enter presses.
        if (form.getAttribute('data-submitting') === '1') {
            event.preventDefault();
            return;
        }

        if (form.hasAttribute('data-confirm') && form.getAttribute('data-confirmed') !== '1') {
            event.preventDefault();

            confirmAction(form, submitter).then(function (confirmed) {
                if (confirmed) {
                    form.setAttribute('data-confirmed', '1');
                    resubmit(form, submitter);
                }
            });

            return;
        }

        form.removeAttribute('data-confirmed');

        if (wantsLoading(form)) {
            setLoading(form, submitter);
        }
    });

    // Back/forward cache restores the old DOM: never leave buttons stuck.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            resetLoading();
        }
    });

    /* ---------------------------------------------------------
       Public API + init
       --------------------------------------------------------- */
    window.BrgyHoops = window.BrgyHoops || {};
    window.BrgyHoops.toast = showToast;
    window.BrgyHoops.confirm = function (message, options) {
        var proxy = document.createElement('form');
        options = options || {};
        proxy.setAttribute('data-confirm', message);
        if (options.title) { proxy.setAttribute('data-confirm-title', options.title); }
        if (options.action) { proxy.setAttribute('data-confirm-action', options.action); }
        if (options.variant) { proxy.setAttribute('data-confirm-variant', options.variant); }
        return confirmAction(proxy, document.activeElement);
    };
    window.BrgyHoops.resetLoading = resetLoading;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initServerToasts);
    } else {
        initServerToasts();
    }
})();
