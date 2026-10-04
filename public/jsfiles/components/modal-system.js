/*
 * KNOWURLOCAL shared alert and confirmation system.
 *
 * One lightweight implementation is used for informational notices,
 * validation errors, and consequential admin confirmations.
 */
(function () {
    const modal = document.getElementById('alert-modal');
    if (!modal) return;

    const titleEl = document.getElementById('alert-modal-title');
    const nameEl = document.getElementById('alert-modal-name');
    const textEl = document.getElementById('alert-modal-text');
    const iconEl = document.getElementById('alert-modal-icon');
    const messageBox = document.getElementById('alert-modal-message');
    const actions = modal.querySelector('.actions');
    const inputWrap = document.getElementById('alert-modal-input-wrap');
    const inputLabel = document.getElementById('alert-modal-input-label');
    const inputEl = document.getElementById('alert-modal-input');
    const inputError = document.getElementById('alert-modal-input-error');

    let closeTimer = null;
    let previousFocus = null;
    let activeConfig = {};

    const standardAcknowledgements = new Set(['ok', 'okay', 'close', 'got it', 'dismiss']);

    function getButtons() {
        return {
            confirm: document.getElementById('alert-modal-confirm'),
            cancel: document.getElementById('alert-modal-cancel'),
        };
    }

    function clearCloseTimer() {
        if (closeTimer !== null) {
            window.clearTimeout(closeTimer);
            closeTimer = null;
        }
    }

    function setVisible(visible, immediate = false) {
        clearCloseTimer();
        modal.classList.toggle('show', visible);
        modal.setAttribute('aria-hidden', visible ? 'false' : 'true');

        if (visible) {
            modal.classList.remove('hidden');
            requestAnimationFrame(() => modal.classList.add('is-ready'));
            return;
        }

        modal.classList.remove('is-ready');
        if (immediate) {
            modal.classList.add('hidden');
        } else {
            closeTimer = window.setTimeout(() => {
                modal.classList.add('hidden');
                closeTimer = null;
                if (previousFocus && typeof previousFocus.focus === 'function') {
                    previousFocus.focus({ preventScroll: true });
                }
                previousFocus = null;
            }, 130);
        }
    }

    function closeAlertModal(immediate = false) {
        setVisible(false, immediate);
    }

    function showAlertModal(config = {}) {
        closeAlertModal(true);
        activeConfig = config;
        previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;

        const buttons = getButtons();
        let confirmBtn = buttons.confirm;
        let cancelBtn = buttons.cancel;

        // Replace buttons so callbacks from earlier dialogs can never accumulate.
        const freshConfirm = confirmBtn.cloneNode(true);
        const freshCancel = cancelBtn.cloneNode(true);
        confirmBtn.replaceWith(freshConfirm);
        cancelBtn.replaceWith(freshCancel);
        confirmBtn = freshConfirm;
        cancelBtn = freshCancel;

        titleEl.textContent = config.title || (config.variant === 'success' ? 'All set' : 'Please review');
        nameEl.textContent = config.name || '';
        nameEl.hidden = !config.name;
        textEl.textContent = config.text || '';

        const inputConfig = config.input && typeof config.input === 'object' ? config.input : null;
        if (inputWrap && inputLabel && inputEl && inputError) {
            inputWrap.hidden = !inputConfig;
            inputEl.hidden = !inputConfig;
            inputEl.value = inputConfig ? String(inputConfig.value || '') : '';
            inputEl.required = Boolean(inputConfig?.required);
            inputEl.minLength = Number.isFinite(inputConfig?.minLength) ? inputConfig.minLength : 0;
            inputEl.maxLength = Number.isFinite(inputConfig?.maxLength) ? inputConfig.maxLength : 500;
            inputEl.placeholder = inputConfig?.placeholder || '';
            inputEl.setAttribute('aria-describedby', 'alert-modal-input-error');
            inputLabel.textContent = inputConfig?.label || 'Additional information';
            inputError.textContent = '';
            inputError.hidden = true;
            inputEl.setAttribute('aria-invalid', 'false');
            inputEl.oninput = () => {
                inputError.hidden = true;
                inputError.textContent = '';
                inputEl.setAttribute('aria-invalid', 'false');
            };
        }

        iconEl.replaceChildren();
        const iconValue = config.icon || (config.variant === 'success' ? 'ph-light ph-check-circle' : config.variant === 'danger' ? 'ph-light ph-warning-circle' : 'ph-light ph-info');
        const icon = document.createElement('i');
        icon.setAttribute('aria-hidden', 'true');
        if (/^(fa-|ph-|bi-|ri-|lucide-)/.test(iconValue) || iconValue.includes(' ')) {
            icon.className = iconValue;
        } else {
            icon.textContent = iconValue;
        }
        iconEl.appendChild(icon);

        messageBox.classList.remove('danger', 'success', 'warning', 'info');
        messageBox.classList.add(['success', 'danger', 'warning', 'info'].includes(config.variant) ? config.variant : 'info');
        modal.classList.toggle('is-confirmation', Boolean(config.showCancel));

        const confirmLabel = String(config.confirmText || 'Continue');
        const normalizedLabel = confirmLabel.trim().toLowerCase();
        const isSimpleSuccessAcknowledgement = config.variant === 'success'
            && standardAcknowledgements.has(normalizedLabel)
            && config.showConfirm !== true;
        const showConfirm = config.showConfirm !== undefined
            ? Boolean(config.showConfirm)
            : !isSimpleSuccessAcknowledgement;
        const showCancel = Boolean(config.showCancel);

        confirmBtn.textContent = confirmLabel;
        confirmBtn.className = 'btn confirm';
        cancelBtn.className = 'btn cancel';
        confirmBtn.dataset.loading = 'false';
        confirmBtn.disabled = false;
        cancelBtn.textContent = config.cancelText || 'Cancel';
        confirmBtn.hidden = !showConfirm;
        cancelBtn.hidden = !showCancel;
        actions.hidden = !showConfirm && !showCancel;

        if (config.variant === 'danger') confirmBtn.classList.add('danger-btn');
        if (config.variant === 'success') confirmBtn.classList.add('success-btn');
        if (config.variant === 'warning') confirmBtn.classList.add('warning-btn');

        const restoreActionAfterError = (error) => {
            console.error('Alert modal action failed:', error);
            confirmBtn.dataset.loading = 'false';
            confirmBtn.disabled = false;
            cancelBtn.disabled = false;
            confirmBtn.textContent = confirmLabel;
            messageBox.classList.remove('success', 'warning', 'info');
            messageBox.classList.add('danger');
            titleEl.textContent = 'Action could not be completed';
            textEl.textContent = error?.message || 'The request could not be completed. Please check your connection and try again.';
            iconEl.replaceChildren();
            const errorIcon = document.createElement('i');
            errorIcon.className = 'ph-light ph-warning-circle';
            errorIcon.setAttribute('aria-hidden', 'true');
            iconEl.appendChild(errorIcon);
            if (typeof config.onError === 'function') config.onError(error);
        };

        confirmBtn.addEventListener('click', () => {
            if (confirmBtn.disabled || confirmBtn.dataset.loading === 'true') return;

            const inputValue = inputConfig && inputEl ? inputEl.value : undefined;
            if (inputConfig && inputEl) {
                const trimmedLength = inputValue.trim().length;
                const minimum = Number.isFinite(inputConfig.minLength) ? inputConfig.minLength : 0;
                if ((inputConfig.required && trimmedLength === 0) || trimmedLength < minimum) {
                    inputError.textContent = inputConfig.validationMessage || `Please enter at least ${minimum} characters.`;
                    inputError.hidden = false;
                    inputEl.setAttribute('aria-invalid', 'true');
                    inputEl.focus();
                    return;
                }
            }

            if (typeof config.onConfirm !== 'function') {
                closeAlertModal();
                return;
            }

            const shouldShowLoading = config.loading !== false;
            if (shouldShowLoading) {
                confirmBtn.dataset.loading = 'true';
                confirmBtn.disabled = true;
                confirmBtn.textContent = config.loadingText || 'Working…';
                cancelBtn.disabled = true;
            }

            try {
                const result = config.onConfirm(inputValue);
                if (result && typeof result.then === 'function') {
                    result.catch(restoreActionAfterError);
                }
            } catch (error) {
                restoreActionAfterError(error);
            }
        });

        cancelBtn.addEventListener('click', () => {
            if (cancelBtn.disabled) return;
            closeAlertModal();
            if (typeof config.onCancel === 'function') config.onCancel();
        });

        setVisible(true);

        // Keep keyboard focus inside the action area without a heavy focus animation.
        const focusTarget = inputConfig ? inputEl : showCancel ? cancelBtn : showConfirm ? confirmBtn : modal.querySelector('.modal');
        if (focusTarget && typeof focusTarget.focus === 'function') {
            window.setTimeout(() => focusTarget.focus({ preventScroll: true }), 0);
        }

        const autoCloseMs = Number.isFinite(config.autoCloseMs)
            ? Math.max(0, config.autoCloseMs)
            : (!showConfirm && !showCancel && config.variant === 'success' ? 1400 : 0);
        if (autoCloseMs > 0) {
            closeTimer = window.setTimeout(() => closeAlertModal(), autoCloseMs);
        }
    }

    function showConfirmModal(config = {}) {
        return new Promise((resolve) => {
            showAlertModal({
                ...config,
                variant: config.variant || 'danger',
                showConfirm: true,
                showCancel: true,
                closeOnBackdrop: false,
                onConfirm: () => {
                    closeAlertModal();
                    resolve(true);
                },
                onCancel: () => resolve(false),
            });
        });
    }

    /*
     * Modal dismissal is intentionally explicit.
     *
     * Clicking the backdrop or pressing Escape must never silently
     * discard an admin confirmation/input. The active dialog is closed
     * only by its visible action button (or by an explicit auto-close
     * configured by the caller).
     */
    modal.addEventListener('click', (event) => {
        if (event.target === modal) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (modal.classList.contains('hidden') || !modal.classList.contains('show')) return;
        if (event.key === 'Escape') {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    window.showAlertModal = showAlertModal;
    window.closeAlertModal = closeAlertModal;
    window.showConfirmModal = showConfirmModal;
    window.notifyUser = (text, options = {}) => {
        if (typeof window.showAlertModal === 'function') {
            window.showAlertModal({
                title: options.title || 'Notice',
                text: String(text ?? ''),
                icon: options.icon || (options.variant === 'success' ? 'ph-light ph-check-circle' : 'ph-light ph-info'),
                variant: options.variant || 'info',
                confirmText: options.confirmText || 'OK',
                showConfirm: options.showConfirm !== undefined ? options.showConfirm : options.variant !== 'success',
                showCancel: false,
                loading: false,
                ...options,
            });
        } else {
            window.alert(String(text ?? ''));
        }
    };

    // Centralized flash feedback means every admin route gets the same treatment.
    document.addEventListener('DOMContentLoaded', () => {
        const flashMessages = [
            ['__FLASH_SUCCESS__', 'Success', 'success'],
            ['__FLASH_ERROR__', 'Something needs attention', 'danger'],
            ['__FLASH_WARNING__', 'Please note', 'warning'],
            ['__FLASH_INFO__', 'Information', 'info'],
        ];

        for (const [key, title, variant] of flashMessages) {
            const message = window[key];
            if (!message) continue;
            window[key] = null;
            showAlertModal({
                title,
                text: String(message),
                variant,
                icon: variant === 'success' ? 'ph-light ph-check-circle' : variant === 'danger' ? 'ph-light ph-warning-circle' : variant === 'warning' ? 'ph-light ph-warning' : 'ph-light ph-info',
                confirmText: 'OK',
                showConfirm: variant !== 'success',
                showCancel: false,
                loading: false,
                autoCloseMs: variant === 'success' ? 1400 : 0,
            });
            break;
        }
    });
})();
