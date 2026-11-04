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
        passwordChange: document.getElementById('profilePasswordChangeForm')
    };
    if (!backdrop || !menu || !moreButton) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let activeModal = null;
    let passwordForOtp = '';

    const modalCopy = {
        name: ['Edit name', 'Update the name shown on your KNOWURLOCAL profile.'],
        email: ['Edit email', 'Change the email address associated with your account.'],
        passwordRequest: ['Change password', 'Verify your account before setting a new password.'],
        passwordChange: ['Verify and change password', 'Enter the emailed code and choose a new password.']
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
        passwordForOtp = '';
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
        button.addEventListener('click', () => openModal(button.dataset.openProfileModal));
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
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            if (!backdrop.hidden) closeModal();
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

    forms.passwordRequest.addEventListener('submit', async event => {
        event.preventDefault();
        const form = forms.passwordRequest;
        passwordForOtp = form.elements.current_password.value;
        setBusy(form, true, 'Sending code…');
        try {
            const result = await request('/profile/password/otp', 'POST', { current_password: passwordForOtp });
            forms.passwordChange.elements.current_password.value = passwordForOtp;
            openModal('passwordChange');
            const explainer = forms.passwordChange.querySelector('.profile-password-explainer');
            if (explainer) explainer.textContent = result.message + ' Enter the code and your new password.';
        } catch (error) {
            showError(form, error.message);
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
        } catch (error) {
            showError(form, error.message);
        } finally {
            setBusy(form, false);
        }
    });

    const pictureInput = document.getElementById('profilePictureInput');
    document.getElementById('chooseProfilePicture')?.addEventListener('click', () => pictureInput?.click());
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
        pictureButton.disabled = true;
        try {
            const result = await request('/profile/picture', 'POST', body, true);
            const image = document.createElement('img');
            image.id = 'profilePictureImage';
            image.alt = 'Profile picture';
            image.src = result.url;
            document.getElementById('profilePicture').replaceChildren(image);
        } catch (error) {
            window.showAlertModal?.({ title: 'Could not update picture', text: error.message, variant: 'error' });
        } finally {
            pictureButton.disabled = false;
            pictureInput.value = '';
        }
    });
})();
