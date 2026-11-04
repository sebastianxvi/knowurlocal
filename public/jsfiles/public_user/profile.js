(() => {
    'use strict';

    const backdrop = document.getElementById('profileModalBackdrop');
    const menu = document.getElementById('profileActionsMenu');
    const moreButton = document.getElementById('profileMoreButton');
    const modalTitle = document.getElementById('profileModalTitle');
    const modalDescription = document.getElementById('profileModalDescription');
    const forms = {
        name: document.getElementById('profileNameForm'),
        email: document.getElementById('profileEmailForm'),
        passwordRequest: document.getElementById('profilePasswordRequestForm'),
        passwordOtp: document.getElementById('profilePasswordOtpForm'),
        passwordChange: document.getElementById('profilePasswordChangeForm')
    };
    if (!backdrop || !menu || !moreButton) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let activeModal = null;

    const modalCopy = {
        name: ['Edit name', 'Update the name shown on your KNOWURLOCAL profile.'],
        email: ['Edit email', 'Change the email address associated with your account.'],
        passwordRequest: ['Change password', 'Choose whether to send an email verification code.'],
        passwordOtp: ['Email verification', 'Enter the six-digit code sent to your email.'],
        passwordChange: ['Set a new password', 'Your email is verified. Confirm your current password and choose a new one.']
    };

    async function request(url, method, body, isFormData = false) {
        const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': csrf };
        if (!isFormData) headers['Content-Type'] = 'application/json';
        const response = await fetch(url, {
            method, credentials: 'same-origin', headers,
            body: isFormData ? body : JSON.stringify(body)
        });
        const result = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = result.errors ? Object.values(result.errors).flat().join(' ') :
                (result.message || 'Something went wrong. Please try again.');
            throw new Error(message);
        }
        return result;
    }

    function openModal(which) {
        closeMenu();
        // Accept the legacy menu value as well as the explicit request-form key.
        if (which === 'password') which = 'passwordRequest';
        Object.values(forms).forEach(form => { form.hidden = true; });
        const selected = forms[which];
        if (!selected) return;
        selected.hidden = false;
        selected.querySelectorAll('.profile-modal-error').forEach(error => {
            error.hidden = true;
            error.textContent = '';
        });
        [modalTitle.textContent, modalDescription.textContent] = modalCopy[which];
        activeModal = which;
        backdrop.hidden = false;
        document.body.classList.add('profile-editor-open');
        selected.querySelector('input:not([type="hidden"])')?.focus();
    }

    function closeModal() {
        backdrop.hidden = true;
        document.body.classList.remove('profile-editor-open');
        activeModal = null;
        forms.passwordRequest.reset();
        forms.passwordChange.reset();
    }

    function closeMenu() {
        menu.hidden = true;
        moreButton.setAttribute('aria-expanded', 'false');
    }

    function showError(form, message) {
        const error = form.querySelector('.profile-modal-error');
        if (!error) return;
        error.textContent = message;
        error.hidden = false;
    }

    function setBusy(form, busy, busyLabel = 'Saving…') {
        const button = form.querySelector('[type="submit"]');
        if (!button) return;
        if (!button.dataset.defaultLabel) button.dataset.defaultLabel = button.textContent.trim();
        button.disabled = busy;
        button.textContent = busy ? busyLabel : button.dataset.defaultLabel;
    }

    moreButton.addEventListener('click', () => {
        const open = menu.hidden;
        menu.hidden = !open;
        moreButton.setAttribute('aria-expanded', String(open));
    });

    menu.querySelectorAll('[data-open-profile-modal]').forEach(button => {
        button.addEventListener('click', () => {
            if (button.dataset.openProfileModal === 'passwordRequest') {
                requestPasswordOtpWithConfirmation();
                return;
            }
            openModal(button.dataset.openProfileModal);
        });
    });
    document.getElementById('profileModalClose')?.addEventListener('click', closeModal);
    backdrop.addEventListener('click', event => {
        if (event.target === backdrop) closeModal();
    });
    backdrop.querySelectorAll('[data-close-profile-modal]').forEach(button => {
        button.addEventListener('click', closeModal);
    });
    document.addEventListener('click', event => {
        if (!event.target.closest('.profile-actions-wrap')) closeMenu();
    });
    const pictureOptionsBackdrop = document.getElementById('profilePictureOptionsBackdrop');
    const pictureViewerBackdrop = document.getElementById('profilePictureViewerBackdrop');
    const pictureViewerImage = document.getElementById('profilePictureViewerImage');

    function closePictureOptions() {
        if (pictureOptionsBackdrop) pictureOptionsBackdrop.hidden = true;
    }

    function openPictureOptions() {
        closeMenu();
        if (pictureOptionsBackdrop) pictureOptionsBackdrop.hidden = false;
        document.getElementById('profilePictureOptionsClose')?.focus();
    }

    function openPictureViewer() {
        const currentImage = document.querySelector('#profilePictureImage');
        if (!currentImage || !pictureViewerBackdrop || !pictureViewerImage) return;
        pictureViewerImage.src = currentImage.src;
        closePictureOptions();
        pictureViewerBackdrop.hidden = false;
        document.getElementById('profilePictureViewerClose')?.focus();
    }

    function closePictureViewer() {
        if (pictureViewerBackdrop) pictureViewerBackdrop.hidden = true;
        if (pictureViewerImage) pictureViewerImage.removeAttribute('src');
    }

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (pictureViewerBackdrop && !pictureViewerBackdrop.hidden) closePictureViewer();
            else if (pictureOptionsBackdrop && !pictureOptionsBackdrop.hidden) closePictureOptions();
            else if (!backdrop.hidden) closeModal();
            closeMenu();
        }
    });

    async function submitDetails(form, fields) {
        const payload = Object.fromEntries(new FormData(form).entries());
        Object.keys(payload).forEach(key => {
            if (!fields.includes(key)) delete payload[key];
        });
        setBusy(form, true);
        try {
            const result = await request('/profile/details', 'PUT', payload);
            if (window.showAlertModal) {
                window.showAlertModal({ title: 'Updated', text: result.message, variant: 'success', showConfirm: false });
            }
            window.location.reload();
        } catch (error) {
            showError(form, error.message);
        } finally {
            setBusy(form, false);
        }
    }

    forms.name.addEventListener('submit', event => {
        event.preventDefault();
        submitDetails(forms.name, ['first_name', 'last_name', 'current_password']);
    });
    forms.email.addEventListener('submit', event => {
        event.preventDefault();
        submitDetails(forms.email, ['email', 'current_password']);
    });

    async function requestPasswordOtpWithConfirmation() {
        closeMenu();
        window.showAlertModal?.({
            title: 'Send verification code?',
            text: `A new one-time code will be sent to ${document.querySelector('#profilePasswordRequestForm strong')?.textContent || 'your account email'}. You can cancel now and keep your password unchanged.`,
            variant: 'info',
            confirmText: 'Send OTP',
            cancelText: 'Cancel',
            showConfirm: true,
            showCancel: true,
            loadingText: 'Sending OTP…',
            onConfirm: async () => {
                const result = await request('/profile/password/otp', 'POST', {});
                window.closeAlertModal?.();
                openModal('passwordOtp');
                resetOtpInputs();
                const status = document.getElementById('profileOtpStatus');
                if (status) status.textContent = result.message || 'Code sent. It expires in 10 minutes.';
                startOtpResendCooldown(60);
                otpDigits[0]?.focus();
            }
        });
    }

    const otpDigits = Array.from(document.querySelectorAll('.profile-otp-inputs input'));
    const otpValue = document.getElementById('profilePasswordOtpValue');

    function resetOtpInputs() {
        otpDigits.forEach(input => { input.value = ''; });
        if (otpValue) otpValue.value = '';
        const error = forms.passwordOtp.querySelector('.profile-modal-error');
        if (error) { error.hidden = true; error.textContent = ''; }
    }

    let otpResendInterval = null;
    const resendOtpButton = document.getElementById('profileResendPasswordOtp');

    function startOtpResendCooldown(seconds) {
        if (!resendOtpButton) return;
        if (otpResendInterval) clearInterval(otpResendInterval);
        let remaining = seconds;
        resendOtpButton.disabled = remaining > 0;
        resendOtpButton.textContent = remaining > 0 ? `Resend in ${remaining}s` : 'Resend';
        if (remaining <= 0) return;
        otpResendInterval = setInterval(() => {
            remaining -= 1;
            resendOtpButton.textContent = remaining > 0 ? `Resend in ${remaining}s` : 'Resend';
            resendOtpButton.disabled = remaining > 0;
            if (remaining <= 0) {
                clearInterval(otpResendInterval);
                otpResendInterval = null;
            }
        }, 1000);
    }

    resendOtpButton?.addEventListener('click', async () => {
        if (resendOtpButton.disabled) return;
        resendOtpButton.disabled = true;
        resendOtpButton.textContent = 'Sending…';
        try {
            const result = await request('/profile/password/otp', 'POST', {});
            resetOtpInputs();
            const status = document.getElementById('profileOtpStatus');
            if (status) status.textContent = result.message || 'A new code has been sent.';
            startOtpResendCooldown(60);
            otpDigits[0]?.focus();
        } catch (error) {
            const status = document.getElementById('profileOtpStatus');
            if (status) status.textContent = error.message;
            startOtpResendCooldown(60);
        }
    });

    function syncOtpValue() {
        if (otpValue) otpValue.value = otpDigits.map(input => input.value).join('');
    }

    otpDigits.forEach((input, index) => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(-1);
            syncOtpValue();
            if (input.value && index < otpDigits.length - 1) otpDigits[index + 1].focus();
        });
        input.addEventListener('keydown', event => {
            if (event.key === 'Backspace' && !input.value && index > 0) {
                otpDigits[index - 1].focus();
            } else if (event.key === 'ArrowLeft' && index > 0) {
                otpDigits[index - 1].focus();
            } else if (event.key === 'ArrowRight' && index < otpDigits.length - 1) {
                otpDigits[index + 1].focus();
            }
        });
        input.addEventListener('paste', event => {
            const pasted = (event.clipboardData?.getData('text') || '').replace(/\D/g, '').slice(0, 6);
            if (!pasted) return;
            event.preventDefault();
            otpDigits.forEach((digit, digitIndex) => { digit.value = pasted[digitIndex] || ''; });
            syncOtpValue();
            otpDigits[Math.min(pasted.length, otpDigits.length - 1)]?.focus();
        });
    });

    forms.passwordOtp.addEventListener('submit', async event => {
        event.preventDefault();
        syncOtpValue();
        const form = forms.passwordOtp;
        if (!/^\d{6}$/.test(otpValue?.value || '')) {
            showError(form, 'Enter all six digits of the verification code.');
            return;
        }
        setBusy(form, true, 'Verifying…');
        try {
            const result = await request('/profile/password/otp/verify', 'POST', { otp: otpValue.value });
            openModal('passwordChange');
            const explainer = forms.passwordChange.querySelector('.profile-password-explainer');
            if (explainer) explainer.textContent = result.message + ' Enter your current password and choose a new password.';
            forms.passwordChange.elements.current_password.focus();
        } catch (error) {
            showError(form, error.message);
            otpDigits.forEach(input => { input.value = ''; });
            syncOtpValue();
            otpDigits[0]?.focus();
        } finally {
            setBusy(form, false);
        }
    });

    forms.passwordChange.addEventListener('submit', async event => {
        event.preventDefault();
        const form = forms.passwordChange;
        const payload = Object.fromEntries(new FormData(form).entries());
        if (payload.password !== payload.password_confirmation) {
            showError(form, 'The new password and confirmation do not match.');
            updatePasswordMatch();
            return;
        }
        setBusy(form, true, 'Changing password…');
        try {
            const result = await request('/profile/password', 'PUT', payload);
            closeModal();
            if (window.showAlertModal) {
                window.showAlertModal({ title: 'Password changed', text: result.message, variant: 'success', showConfirm: false });
            }
            form.reset();
            forms.passwordRequest.reset();
            resetOtpInputs();
        } catch (error) {
            showError(form, error.message);
        } finally {
            setBusy(form, false);
        }
    });

    document.getElementById('profileSendPasswordOtp')?.addEventListener('click', requestPasswordOtpWithConfirmation);

    document.querySelectorAll('.profile-password-toggle').forEach(button => {
        button.addEventListener('click', () => {
            const input = button.closest('.profile-password-input-wrap')?.querySelector('input');
            if (!input) return;
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            button.setAttribute('aria-label', `${reveal ? 'Hide' : 'Show'} ${input.name.replaceAll('_', ' ')}`);
            const icon = button.querySelector('i');
            if (icon) icon.className = reveal ? 'ph-light ph-eye-slash' : 'ph-light ph-eye';
        });
    });

    const newPasswordInput = forms.passwordChange.elements.password;
    const confirmPasswordInput = forms.passwordChange.elements.password_confirmation;
    const matchMessage = document.getElementById('profilePasswordMatch');

    function updatePasswordMatch() {
        const newValue = newPasswordInput.value;
        const confirmValue = confirmPasswordInput.value;
        if (!confirmValue) {
            matchMessage.textContent = '';
            matchMessage.classList.remove('is-match', 'is-mismatch');
            confirmPasswordInput.setCustomValidity('');
            return;
        }
        const matches = newValue === confirmValue;
        matchMessage.textContent = matches ? 'Passwords match.' : 'Passwords do not match.';
        matchMessage.classList.toggle('is-match', matches);
        matchMessage.classList.toggle('is-mismatch', !matches);
        confirmPasswordInput.setCustomValidity(matches ? '' : 'Passwords do not match.');
    }

    newPasswordInput.addEventListener('input', updatePasswordMatch);
    confirmPasswordInput.addEventListener('input', updatePasswordMatch);

    const pictureInput = document.getElementById('profilePictureInput');
    document.getElementById('chooseProfilePicture')?.addEventListener('click', openPictureOptions);
    document.getElementById('profilePicture')?.addEventListener('click', openPictureOptions);
    document.getElementById('profilePictureOptionsClose')?.addEventListener('click', closePictureOptions);
    pictureOptionsBackdrop?.addEventListener('click', event => {
        if (event.target === pictureOptionsBackdrop) closePictureOptions();
    });
    document.getElementById('viewProfilePicture')?.addEventListener('click', openPictureViewer);
    document.getElementById('profilePictureViewerClose')?.addEventListener('click', closePictureViewer);
    pictureViewerBackdrop?.addEventListener('click', event => {
        if (event.target === pictureViewerBackdrop) closePictureViewer();
    });

    document.getElementById('replaceProfilePicture')?.addEventListener('click', () => {
        closePictureOptions();
        window.showAlertModal?.({
            title: 'Replace profile picture?',
            text: 'Your current profile picture will be replaced with the image you choose. This change cannot be undone.',
            variant: 'warning',
            confirmText: 'Choose new picture',
            cancelText: 'Keep current picture',
            showConfirm: true,
            showCancel: true,
            onConfirm: () => {
                window.closeAlertModal?.();
                pictureInput?.click();
            }
        });
    });

    pictureInput?.addEventListener('change', async () => {
        const file = pictureInput.files?.[0];
        if (!file) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            window.showAlertModal?.({ title: 'Unsupported image', text: 'Choose a JPG, PNG, or WEBP image up to 5 MB.', variant: 'warning' });
            pictureInput.value = '';
            return;
        }
        const body = new FormData();
        body.append('profile_picture', file);
        const pictureButton = document.getElementById('chooseProfilePicture');
        if (pictureButton) pictureButton.disabled = true;
        try {
            const result = await request('/profile/picture', 'POST', body, true);
            const image = document.createElement('img');
            image.id = 'profilePictureImage';
            image.alt = 'Profile picture';
            image.src = result.url;
            document.getElementById('profilePicture').replaceChildren(image);
            window.showAlertModal?.({ title: 'Profile picture updated', text: result.message, variant: 'success', showConfirm: false });
        } catch (error) {
            window.showAlertModal?.({ title: 'Could not update picture', text: error.message, variant: 'error' });
        } finally {
            if (pictureButton) pictureButton.disabled = false;
            pictureInput.value = '';
        }
    });
})();
