/*
 * Administrative password gate for sensitive lifecycle actions.
 *
 * The client verification improves UX, but every protected controller also
 * verifies current_password on the actual mutation request.
 */
(function () {
    'use strict';

    window.requestAdminPasswordForForm = function ({
        form,
        verificationUrl,
        actionDescription,
        subject,
        passwordFieldName = 'current_password',
        onCancel = null
    }) {
        if (!form || typeof window.showAlertModal !== 'function' || !verificationUrl) {
            if (typeof window.showAlertModal === 'function') {
                window.showAlertModal({
                    title: 'Security verification unavailable',
                    text: 'Please refresh the page and try again.',
                    variant: 'danger',
                    confirmText: 'OK',
                    showCancel: false
                });
            }
            return;
        }

        let cancelled = false;
        const csrf = form.querySelector('input[name="_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content
            || '';

        window.showAlertModal({
            title: 'Administrative security verification',
            text: `Confirm your identity before you ${actionDescription} ${subject}.`,
            icon: 'ph-light ph-shield-check',
            variant: 'info',
            input: {
                type: 'password',
                label: 'Confirm your administrator password',
                placeholder: 'Enter your current account password',
                required: true,
                maxLength: 255,
                validationMessage: 'Your administrator password is required.'
            },
            confirmText: 'Verify and continue',
            cancelText: 'Cancel',
            showCancel: true,
            allowCancelDuringLoading: true,
            onCancel: () => {
                cancelled = true;
                if (typeof onCancel === 'function') onCancel();
            },
            onConfirm: async (password) => {
                const response = await fetch(verificationUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ password })
                });

                const payload = await response.json().catch(() => ({}));

                // A cancel during the request must never allow the mutation to proceed.
                if (cancelled) return;

                if (!response.ok || payload.verified !== true) {
                    throw new Error(payload.message || 'Password verification failed.');
                }

                if (cancelled) return;

                let passwordField = form.querySelector(`input[name="${passwordFieldName}"]`);
                if (!passwordField) {
                    passwordField = document.createElement('input');
                    passwordField.type = 'hidden';
                    passwordField.name = passwordFieldName;
                    form.appendChild(passwordField);
                }

                passwordField.value = password;
                window.closeAlertModal(true);
                form.submit();
            }
        });
    };
})();
