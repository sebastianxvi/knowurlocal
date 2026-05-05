// ================= FORM VALIDATION SYSTEM =================
document.querySelectorAll('.floating-group[data-validate]').forEach(group => {

    const input = group.querySelector('input, textarea');
    const message = group.querySelector('.form-message');

    if (!input) return;

    input.addEventListener('input', () => {

        const type = group.dataset.validate;
        let value = input.value.trim();

        let valid = true;
        let errorMsg = '';

        switch(type){

            case 'email':
                valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
                errorMsg = 'Enter a valid email address';
                break;

            case 'required':
                valid = value !== '';
                errorMsg = 'This field is required';
                break;

            case 'phone':
                valid = /^(09|\+639)\d{9}$/.test(value);
                errorMsg = 'Enter a valid PH phone number';
                break;

            case 'landline':
                valid = /^[0-9\-]{6,15}$/.test(value);
                errorMsg = 'Enter a valid landline number';
                break;

            case 'website':
                // 🔥 AUTO-FIX (UX IMPROVEMENT)
                if (value && !value.startsWith('http')) {
                    value = 'https://' + value;
                    input.value = value;
                }

                // 🔐 STRICT VALIDATION (MATCH LARAVEL)
                valid = /^(https?:\/\/)[\w.-]+\.[a-z]{2,}.*$/i.test(value);
                errorMsg = 'Enter a valid URL (must start with http:// or https://)';
                break;

            case 'facebook':
                valid = /^(https?:\/\/)?(www\.)?facebook\.com\/.+/.test(value);
                errorMsg = 'Enter a valid Facebook link';
                break;

            case 'optional-text':
                valid = true;
                break;
        }

        // ================= RESET STATE =================
        group.classList.remove('error', 'success');

        // ================= EMPTY = NEUTRAL =================
        if (value === '') {
            if (message) message.textContent = '';
            return;
        }

        // ================= OPTIONAL TEXT (NO VALIDATION) =================
        if (type === 'optional-text') {
            if (message) message.textContent = '';
            return;
        }

        // ================= OPTIONAL BUT VALIDATED =================
        // (like website, email, facebook)
        if (type !== 'required' && !valid) {
            group.classList.add('error');
            if (message) message.textContent = errorMsg;
            return;
        }

        // ================= FINAL RESULT =================
        if (!valid){
            group.classList.add('error');
            if (message) message.textContent = errorMsg;
        } else {
            group.classList.add('success');
            if (message) message.textContent = '';
        }

    });

});


// ================= RESET FUNCTION =================
function resetFormValidation(form){

    form.reset();

    form.querySelectorAll('.floating-group').forEach(group => {
        group.classList.remove('error', 'success');

        const message = group.querySelector('.form-message');
        if (message) message.textContent = '';
    });

}

// 🔥 GLOBAL ACCESS (SAFE)
window.resetFormValidation = resetFormValidation;