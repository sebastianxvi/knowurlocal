
/*
|--------------------------------------------------------------------------
| KNOWURLOCAL — FAQ SUPPORTING CONTENT BUILDER
|--------------------------------------------------------------------------
|
| This is intentionally smaller and quieter than the Support Request
| response composer. FAQ answers already contain the primary response;
| these components are only supporting knowledge content.
|
| Allowed:
| text, image, link, file, qr_code
|
| Security:
| - Dynamic text is inserted through textContent where possible.
| - URLs are limited to http/https before previewing.
| - Files are validated for size/type client-side for UX only.
| - Laravel remains the authoritative validator.
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('faq-response-components');
    const empty = document.getElementById('faq-response-empty');
    const addButton = document.getElementById('faq-response-add');
    const menu = document.getElementById('faq-response-menu');

    if (!container || !empty || !addButton || !menu) {
        return;
    }

    const MAX = 10;
    const MAX_TEXT = 5000;
    const MAX_FILE = 5 * 1024 * 1024;
    let counter = 0;

    const icons = {
        text: 'ph-text-aa',
        image: 'ph-image',
        link: 'ph-link',
        file: 'ph-file',
        qr_code: 'ph-qr-code',
    };

    const labels = {
        text: ['Text note', 'A short clarification or instruction.'],
        image: ['Image', 'A visual guide or supporting image.'],
        link: ['Link', 'An official online resource.'],
        file: ['File', 'A document citizens may need.'],
        qr_code: ['QR code', 'An official destination encoded for QR access.'],
    };

    const safeUrl = (value) => {
        try {
            const url = new URL(value);
            return ['http:', 'https:'].includes(url.protocol);
        } catch {
            return false;
        }
    };

    const escapeHtml = (value) => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const refresh = () => {
        const count = container.querySelectorAll('.faq-response-item').length;
        empty.hidden = count > 0;
        addButton.disabled = count >= MAX;
        addButton.setAttribute('aria-disabled', count >= MAX ? 'true' : 'false');
    };

    const closeMenu = () => {
        menu.hidden = true;
        addButton.setAttribute('aria-expanded', 'false');
    };

    const createHeader = (type, index) => `
        <div class="faq-response-item-header">
            <div class="faq-response-item-title">
                <span class="faq-response-item-icon" aria-hidden="true">
                    <i class="ph-light ${icons[type]}"></i>
                </span>
                <span>
                    <strong>${escapeHtml(labels[type][0])}</strong>
                    <small>${escapeHtml(labels[type][1])}</small>
                </span>
            </div>
            <button
                type="button"
                class="faq-response-remove"
                data-remove-response="${index}"
                aria-label="Remove ${escapeHtml(labels[type][0])}"
                title="Remove"
            >
                <i class="ph-light ph-trash" aria-hidden="true"></i>
            </button>
        </div>
    `;

    const createItem = (type, values = {}, readonly = false) => {
        const index = counter++;
        const disabled = readonly ? 'disabled' : '';
        const content = values.content ?? '';
        const label = values.label ?? '';
        const existing = values.content ?? '';

        let body = '';

        if (type === 'text') {
            body = `
                <label for="faq-response-${index}-content">Content</label>
                <textarea
                    id="faq-response-${index}-content"
                    name="response_components[${index}][content]"
                    rows="4"
                    maxlength="${MAX_TEXT}"
                    placeholder="Add a short supporting note..."
                    ${disabled}
                >${escapeHtml(content)}</textarea>
                <input type="hidden" name="response_components[${index}][type]" value="text">
            `;
        }

        if (type === 'link' || type === 'qr_code') {
            const placeholder = type === 'qr_code'
                ? 'https://example.gov.ph/...'
                : 'https://official-government-website.gov.ph/...';

            body = `
                <label for="faq-response-${index}-content">${type === 'qr_code' ? 'Destination URL' : 'Official URL'}</label>
                <input
                    id="faq-response-${index}-content"
                    type="url"
                    name="response_components[${index}][content]"
                    value="${escapeHtml(content)}"
                    placeholder="${placeholder}"
                    inputmode="url"
                    autocomplete="url"
                    ${disabled}
                    required
                >
                <label for="faq-response-${index}-label">Label <span>(optional)</span></label>
                <input
                    id="faq-response-${index}-label"
                    type="text"
                    name="response_components[${index}][label]"
                    value="${escapeHtml(label)}"
                    maxlength="255"
                    placeholder="${type === 'qr_code' ? 'What this QR code opens' : 'How this link should be described'}"
                    ${disabled}
                >
                <input type="hidden" name="response_components[${index}][type]" value="${type}">
                ${type === 'qr_code' ? '<p class="faq-response-hint"><i class="ph-light ph-info"></i> The citizen-facing QR destination will use this URL.</p>' : ''}
            `;
        }

        if (type === 'image' || type === 'file') {
            const accept = type === 'image'
                ? '.jpg,.jpeg,.png,.webp'
                : '.pdf,.doc,.docx,.xls,.xlsx';

            body = `
                <label for="faq-response-${index}-file">${type === 'image' ? 'Image file' : 'Document file'}</label>
                <input
                    id="faq-response-${index}-file"
                    type="file"
                    name="response_components[${index}][file]"
                    accept="${accept}"
                    ${disabled}
                    ${existing ? '' : 'required'}
                >
                ${existing ? `
                    <input type="hidden"
                        name="response_components[${index}][existing_content]"
                        value="${escapeHtml(existing)}">
                    <div class="faq-response-existing-file">
                        <i class="ph-light ${type === 'image' ? 'ph-image' : 'ph-file'}"></i>
                        <span>Existing ${type === 'image' ? 'image' : 'file'} will be kept unless replaced.</span>
                    </div>
                ` : ''}
                <label for="faq-response-${index}-label">Label <span>(optional)</span></label>
                <input
                    id="faq-response-${index}-label"
                    type="text"
                    name="response_components[${index}][label]"
                    value="${escapeHtml(label)}"
                    maxlength="255"
                    placeholder="A short description"
                    ${disabled}
                >
                <p class="faq-response-hint">
                    <i class="ph-light ph-shield-check"></i>
                    ${type === 'image' ? 'JPG, PNG, or WEBP · Maximum 5 MB' : 'PDF, Word, or Excel · Maximum 5 MB'}
                </p>
                <input type="hidden" name="response_components[${index}][type]" value="${type}">
            `;
        }

        const wrapper = document.createElement('article');
        wrapper.className = 'faq-response-item';
        wrapper.dataset.responseType = type;
        wrapper.dataset.responseIndex = index;
        wrapper.innerHTML = `
            ${createHeader(type, index)}
            <div class="faq-response-item-body">
                ${body}
            </div>
        `;

        container.appendChild(wrapper);

        if (!readonly) {
            wrapper.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', () => {
                    const file = input.files?.[0];
                    if (!file) return;

                    const allowed = type === 'image'
                        ? ['image/jpeg','image/png','image/webp']
                        : [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ];

                    if (!allowed.includes(file.type) || file.size > MAX_FILE) {
                        input.value = '';
                        if (typeof window.showAlertModal === 'function') {
                            window.showAlertModal({
                                title: 'Invalid file',
                                text: 'Please choose a supported file up to 5 MB.',
                                icon: 'ph-light ph-warning-circle',
                                variant: 'danger',
                                confirmText: 'OK',
                                showCancel: false,
                                loading: false
                            });
                        }
                    }
                });
            });

            wrapper.querySelectorAll('input[type="url"]').forEach(input => {
                input.addEventListener('blur', () => {
                    if (input.value && !safeUrl(input.value)) {
                        input.setCustomValidity('Only HTTP or HTTPS URLs are allowed.');
                    } else {
                        input.setCustomValidity('');
                    }
                });
            });
        }

        refresh();
    };

    const reset = () => {
        container.replaceChildren();
        counter = 0;
        refresh();
        closeMenu();
    };

    const load = (components = [], readonly = false) => {
        reset();

        if (!Array.isArray(components)) {
            return;
        }

        components.slice(0, MAX).forEach(component => {
            if (component && labels[component.type]) {
                createItem(component.type, component, readonly);
            }
        });

        refresh();
    };

    addButton.addEventListener('click', () => {
        if (addButton.disabled) return;
        const isOpen = !menu.hidden;
        if (isOpen) {
            closeMenu();
            return;
        }
        menu.hidden = false;
        addButton.setAttribute('aria-expanded', 'true');
    });

    menu.addEventListener('click', event => {
        const option = event.target.closest('[data-response-type]');
        if (!option) return;

        const type = option.dataset.responseType;
        if (!labels[type] || container.querySelectorAll('.faq-response-item').length >= MAX) return;

        createItem(type);
        closeMenu();

        const items = container.querySelectorAll('.faq-response-item');
        items[items.length - 1]?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    container.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-response]');
        if (!remove) return;

        const item = remove.closest('.faq-response-item');
        if (item) item.remove();

        refresh();
    });

    document.addEventListener('click', event => {
        if (!event.target.closest('.faq-response-add-wrap')) {
            closeMenu();
        }
    });

    window.FaqResponseBuilder = {
        reset,
        load,
        getData: () => Array.from(container.querySelectorAll('.faq-response-item')).map(item => ({
            type: item.dataset.responseType
        })),
    };
});
