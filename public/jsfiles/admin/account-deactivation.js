/* Shared reason + password workflow for every managed account type. */
(function () {
    'use strict';

    document.addEventListener('submit', function (event) {
        const form = event.target instanceof HTMLFormElement
            ? event.target.closest('form.js-account-deactivation-form')
            : null;
        if (!form) return;

        event.preventDefault();
        event.stopImmediatePropagation();
        if (form.dataset.confirming === 'true') return;
        form.dataset.confirming = 'true';

        const row = form.closest('tr');
        const subject = row?.dataset.name
            || row?.querySelector('.actor-name')?.textContent.trim()
            || row?.querySelector('td')?.textContent.trim()
            || 'this account';
        const verificationUrl = form.dataset.verificationUrl;
        const csrf = form.querySelector('input[name="_token"]')?.value
            || document.querySelector('meta[name="csrf-token"]')?.content || '';

        const reset = () => { form.dataset.confirming = 'false'; form.dataset.cancelled = 'true'; };
        const setHidden = (name, value) => {
            let field = form.querySelector(`input[name="${name}"]`);
            if (!field) {
                field = document.createElement('input');
                field.type = 'hidden';
                field.name = name;
                form.appendChild(field);
            }
            field.value = value;
        };

        window.showAlertModal({
            title: 'Deactivate account',
            text: `You are about to deactivate ${subject}. Please provide a reason. The account owner will be able to see this explanation when they try to sign in.`,
            icon: 'ph-light ph-user-minus',
            variant: 'danger',
            input: {
                type: 'textarea',
                label: 'Reason for deactivation',
                placeholder: 'Explain why this account is being deactivated…',
                required: true,
                minLength: 5,
                maxLength: 2000,
                validationMessage: 'Please provide a reason of at least 5 characters.'
            },
            confirmText: 'Continue',
            cancelText: 'Cancel',
            showCancel: true,
            onCancel: reset,
            onConfirm: async (rawReason) => {
                const reason = String(rawReason || '').trim();
                if (reason.length < 5) throw new Error('Please provide a reason of at least 5 characters.');
                setHidden('deactivation_reason', reason);
                window.closeAlertModal(true);

                window.showAlertModal({
                    title: 'Administrative security verification',
                    text: `Confirm your identity before deactivating ${subject}.`,
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
                    confirmText: 'Verify and deactivate',
                    cancelText: 'Cancel',
                    showCancel: true,
                    allowCancelDuringLoading: true,
                    onCancel: reset,
                    onConfirm: async (password) => {
                        form.dataset.cancelled = 'false';
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
                        if (form.dataset.cancelled === 'true') return;
                        if (!response.ok || payload.verified !== true) {
                            throw new Error(payload.message || 'Password verification failed.');
                        }
                        setHidden('current_password', password);
                        window.closeAlertModal(true);
                        form.dataset.confirming = 'false';
                        HTMLFormElement.prototype.submit.call(form);
                    }
                });
            }
        });
    }, true);
})();
