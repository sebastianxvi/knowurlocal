
/*
|--------------------------------------------------------------------------
| KNOWURLOCAL — FAQ RESPONSE CONTENT BUILDER
|--------------------------------------------------------------------------
|
| The FAQ composer intentionally separates:
| 1. English response text
| 2. Tagalog / Taglish response text
| 3. Additional attachments
|
| Text components only appear under a language. Attachments never become
| part of the language response area, which keeps the modal easy to scan.
|
| Client-side checks are only UX safeguards. Laravel remains authoritative.
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {
    const englishContainer = document.getElementById('faq-response-english');
    const filipinoContainer = document.getElementById('faq-response-filipino');
    const attachmentContainer = document.getElementById('faq-attachment-components');
    const attachmentEmpty = document.getElementById('faq-attachments-empty');
    const attachmentAdd = document.getElementById('faq-attachment-add');
    const attachmentMenu = document.getElementById('faq-attachment-menu');

    if (!englishContainer || !filipinoContainer || !attachmentContainer) {
        return;
    }

    const MAX_TEXT_PER_LANGUAGE = 10;
    const MAX_ATTACHMENTS = 10;
    const MAX_TEXT = 5000;
    const MAX_FILE = 5 * 1024 * 1024;

    let counter = 0;

    const iconFor = {
        text: 'ph-chat-text',
        image: 'ph-image',
        file: 'ph-file',
        link: 'ph-link',
        qr_code: 'ph-qr-code',
    };

    const labelFor = {
        image: 'Image',
        file: 'File',
        link: 'Link',
        qr_code: 'QR code',
    };

    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const safeUrl = value => {
        try {
            const url = new URL(value);
            return ['http:', 'https:'].includes(url.protocol);
        } catch {
            return false;
        }
    };

    const safeAttachmentUrl = value => {
        if (!value) return false;

        try {
            const url = new URL(value, window.location.origin);

            if (url.origin !== window.location.origin) {
                return false;
            }

            return (
                url.pathname.startsWith('/admin/support-requests/')
                || url.pathname.startsWith('/faqs/')
                || url.pathname.startsWith('/storage/')
            );
        } catch {
            return false;
        }
    };

    const allTextItems = () => document.querySelectorAll(
        '#faq-response-english .faq-response-item, #faq-response-filipino .faq-response-item'
    );

    const refresh = () => {
        const attachmentCount = attachmentContainer.querySelectorAll('.faq-response-item').length;
        const textCount = allTextItems().length;
        const totalCount = attachmentCount + textCount;
        const limitReached = totalCount >= (MAX_ATTACHMENTS);
        attachmentEmpty.hidden = attachmentCount > 0;

        if (attachmentAdd) {
            attachmentAdd.disabled = limitReached;
            attachmentAdd.setAttribute('aria-disabled', limitReached ? 'true' : 'false');
        }

        document.querySelectorAll('.faq-response-add-language').forEach(button => {
            const target = button.dataset.responseLanguage === 'fil'
                ? filipinoContainer
                : englishContainer;

            const count = target.querySelectorAll('.faq-response-item').length;
            button.disabled = count >= MAX_TEXT_PER_LANGUAGE || totalCount >= MAX_ATTACHMENTS;
            button.setAttribute('aria-disabled', button.disabled ? 'true' : 'false');
        });
    };

    const closeAttachmentMenu = () => {
        if (!attachmentMenu || !attachmentAdd) return;
        attachmentMenu.hidden = true;
        attachmentAdd.setAttribute('aria-expanded', 'false');
    };

    const createHeader = (type, index, title, subtitle, readonly = false) => `
        <div class="faq-response-item-header">
            <div class="faq-response-item-title">
                <span class="faq-response-item-icon" aria-hidden="true">
                    <i class="ph-light ${iconFor[type]}"></i>
                </span>
                <span>
                    <strong>${escapeHtml(title)}</strong>
                    <small>${escapeHtml(subtitle)}</small>
                </span>
            </div>
            ${readonly ? '' : `
                <button
                    type="button"
                    class="faq-response-remove"
                    data-remove-response="${index}"
                    aria-label="Remove ${escapeHtml(title)}"
                    title="Remove"
                >
                    <i class="ph-light ph-trash" aria-hidden="true"></i>
                </button>
            `}
        </div>
    `;

    const createText = (language, values = {}, readonly = false) => {
        const index = counter++;
        const container = language === 'fil' ? filipinoContainer : englishContainer;
        const content = values.content ?? '';
        const disabled = readonly ? 'disabled' : '';

        const item = document.createElement('article');
        item.className = 'faq-response-item';
        item.dataset.responseType = 'text';
        item.dataset.responseLanguage = language;
        item.dataset.responseIndex = index;

        item.innerHTML = `
            ${createHeader(
                'text',
                index,
                language === 'fil' ? 'Tagalog / Taglish text' : 'English text',
                'Supporting response block',
                readonly
            )}
            <div class="faq-response-item-body">
                <label for="faq-response-${index}-content">Response text</label>
                <textarea
                    id="faq-response-${index}-content"
                    name="response_components[${index}][content]"
                    rows="4"
                    maxlength="${MAX_TEXT}"
                    placeholder="${language === 'fil' ? 'Add a Tagalog / Taglish response...' : 'Add an English response...'}"
                    ${disabled}
                >${escapeHtml(content)}</textarea>
                <input type="hidden" name="response_components[${index}][type]" value="text">
                <input type="hidden" name="response_components[${index}][language]" value="${language}">
            </div>
        `;

        container.appendChild(item);
    };

    const createAttachment = (type, values = {}, readonly = false, faqId = null) => {
        const index = counter++;
        const componentIndex = Number.isInteger(values.componentIndex)
            ? values.componentIndex
            : index;

        const content = values.content ?? '';
        const label = values.label ?? '';
        const attachmentUrl = values.attachment_url
            || (faqId && ['image', 'file'].includes(type)
                ? `/faqs/${encodeURIComponent(faqId)}/response-attachments/${componentIndex}`
                : '');
        const hasExistingAttachment = Boolean(
            content
            || attachmentUrl
            || values.source_support_request_id
            || values.source_legacy_support_request_id
        );
        const disabled = readonly ? 'disabled' : '';
        const canPreviewAttachment = safeAttachmentUrl(attachmentUrl);

        const item = document.createElement('article');
        item.className = 'faq-response-item faq-attachment-item';
        item.dataset.responseType = type;
        item.dataset.responseIndex = index;

        let body = '';

        if (type === 'link' || type === 'qr_code') {
            const safeDestination = safeUrl(content);

            if (readonly) {
                body = `
                    <div class="faq-response-item-body-inner">
                        ${label ? `
                            <div class="support-response-readonly-label">
                                ${type === 'qr_code' ? 'QR code label' : 'Link label'}
                            </div>
                            <div class="support-response-readonly-content">
                                ${escapeHtml(label)}
                            </div>
                        ` : ''}

                        ${type === 'qr_code' && safeDestination ? `
                            <div class="support-response-qr-preview">
                                <div class="support-response-qr-placeholder" data-qr-value="${escapeHtml(content)}">
                                    <i class="ph-light ph-qr-code" aria-hidden="true"></i>
                                    <span>QR destination</span>
                                </div>
                                <div class="support-response-qr-details">
                                    <div class="support-response-readonly-label">Destination URL</div>
                                    <a class="support-response-readonly-link" href="${escapeHtml(content)}" target="_blank" rel="noopener noreferrer">
                                        ${escapeHtml(content)}
                                        <i class="ph-light ph-arrow-square-out" aria-hidden="true"></i>
                                    </a>
                                </div>
                            </div>
                        ` : `
                            <div class="support-response-readonly-label">URL</div>
                            ${safeDestination
                                ? `<a class="support-response-readonly-link" href="${escapeHtml(content)}" target="_blank" rel="noopener noreferrer">${escapeHtml(content)}<i class="ph-light ph-arrow-square-out" aria-hidden="true"></i></a>`
                                : `<div class="support-response-readonly-content">${escapeHtml(content)}</div>`}
                        `}
                    </div>
                `;
            } else {
                body = `
                    <div class="faq-response-item-body-inner">
                        <label for="faq-response-${index}-content">
                            ${type === 'qr_code' ? 'Destination URL' : 'Official URL'}
                        </label>
                        <input
                            id="faq-response-${index}-content"
                            type="url"
                            name="response_components[${index}][content]"
                            value="${escapeHtml(content)}"
                            placeholder="https://official-government-website.gov.ph/..."
                            inputmode="url"
                            autocomplete="url"
                            required
                        >

                        <label for="faq-response-${index}-label">
                            Label <span>(optional)</span>
                        </label>
                        <input
                            id="faq-response-${index}-label"
                            type="text"
                            name="response_components[${index}][label]"
                            value="${escapeHtml(label)}"
                            maxlength="255"
                            placeholder="Short description"
                        >

                        ${type === 'qr_code' && safeDestination ? `
                            <div class="support-response-qr-preview">
                                <div class="support-response-qr-placeholder" data-qr-value="${escapeHtml(content)}">
                                    <i class="ph-light ph-qr-code" aria-hidden="true"></i>
                                    <span>QR destination</span>
                                </div>
                                <div class="support-response-qr-details">
                                    <div class="support-response-readonly-label">Destination URL</div>
                                    <a class="support-response-readonly-link" href="${escapeHtml(content)}" target="_blank" rel="noopener noreferrer">${escapeHtml(content)}<i class="ph-light ph-arrow-square-out" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                `;
            }
        }

        if (type === 'image' || type === 'file') {
            const accept = type === 'image'
                ? '.jpg,.jpeg,.png,.webp'
                : '.pdf,.doc,.docx,.xls,.xlsx';

            const preview = canPreviewAttachment
                ? type === 'image'
                    ? `
                        <div class="support-response-saved-attachment support-response-saved-image">
                            <a
                                class="support-response-saved-image-preview"
                                href="${escapeHtml(attachmentUrl)}"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Open saved FAQ image"
                            >
                                <img
                                    src="${escapeHtml(attachmentUrl)}"
                                    alt="Saved FAQ attachment preview"
                                    loading="lazy"
                                >
                            </a>
                            <div class="support-response-saved-attachment-footer">
                                <div class="support-response-saved-attachment-info">
                                    <i class="ph-light ph-image" aria-hidden="true"></i>
                                    <span>${escapeHtml(label || 'Saved image')}</span>
                                </div>
                                <a
                                    class="support-response-saved-attachment-action"
                                    href="${escapeHtml(attachmentUrl)}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <i class="ph-light ph-arrow-square-out" aria-hidden="true"></i>
                                    <span>View image</span>
                                </a>
                            </div>
                        </div>
                    `
                    : `
                        <div class="support-response-saved-attachment support-response-saved-document">
                            <div class="support-response-saved-document-icon">
                                <i class="ph-light ph-file-text" aria-hidden="true"></i>
                            </div>
                            <div class="support-response-saved-document-content">
                                <strong>${escapeHtml(label || 'Saved document')}</strong>
                                <span>The original file is securely stored.</span>
                            </div>
                            <a
                                class="support-response-saved-attachment-action"
                                href="${escapeHtml(attachmentUrl)}"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <i class="ph-light ph-arrow-square-out" aria-hidden="true"></i>
                                <span>Open file</span>
                            </a>
                        </div>
                    `
                : hasExistingAttachment
                    ? `
                        <div class="support-response-saved-file support-response-saved-file-missing">
                            <i class="ph-light ${iconFor[type]}" aria-hidden="true"></i>
                            <span>Attachment preview is unavailable.</span>
                        </div>
                    `
                    : '';

            body = `
                <div class="faq-response-item-body-inner">
                    <label for="faq-response-${index}-file">
                        ${type === 'image' ? 'Image' : 'Document'}
                    </label>

                    <input
                        id="faq-response-${index}-file"
                        type="file"
                        name="response_components[${index}][file]"
                        accept="${accept}"
                        ${disabled}
                        ${hasExistingAttachment ? '' : 'required'}
                    >

                    ${preview}

                    ${values.source_support_request_id ? `
                        <input type="hidden" name="response_components[${index}][source_support_request_id]" value="${escapeHtml(values.source_support_request_id)}">
                        <input type="hidden" name="response_components[${index}][source_response_id]" value="${escapeHtml(values.source_response_id)}">
                        <input type="hidden" name="response_components[${index}][source_component_id]" value="${escapeHtml(values.source_component_id)}">
                    ` : ''}

                    ${values.source_legacy_support_request_id ? `
                        <input type="hidden" name="response_components[${index}][source_legacy_support_request_id]" value="${escapeHtml(values.source_legacy_support_request_id)}">
                    ` : ''}

                    ${content ? `
                        <input type="hidden" name="response_components[${index}][existing_content]" value="${escapeHtml(content)}">
                    ` : ''}

                    <label for="faq-response-${index}-label">
                        Label <span>(optional)</span>
                    </label>
                    <input
                        id="faq-response-${index}-label"
                        type="text"
                        name="response_components[${index}][label]"
                        value="${escapeHtml(label)}"
                        maxlength="255"
                        placeholder="Short description"
                        ${disabled}
                    >

                    <p class="faq-response-hint">
                        <i class="ph-light ph-shield-check"></i>
                        ${type === 'image' ? 'JPG, PNG, or WEBP · Maximum 5 MB' : 'PDF, Word, or Excel · Maximum 5 MB'}
                    </p>
                </div>
            `;
        }

        item.innerHTML = `
            ${createHeader(type, index, labelFor[type], 'Additional attachment', readonly)}
            ${body}
            <input type="hidden" name="response_components[${index}][type]" value="${type}">
            <input type="hidden" name="response_components[${index}][language]" value="attachment">
        `;

        attachmentContainer.appendChild(item);

        if (!readonly) {
            item.querySelectorAll('input[type="file"]').forEach(input => {
                input.addEventListener('change', () => {
                    const file = input.files?.[0];
                    if (!file) return;

                    const allowed = type === 'image'
                        ? ['image/jpeg', 'image/png', 'image/webp']
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

            item.querySelectorAll('input[type="url"]').forEach(input => {
                input.addEventListener('blur', () => {
                    input.setCustomValidity(
                        input.value && !safeUrl(input.value)
                            ? 'Only HTTP or HTTPS URLs are allowed.'
                            : ''
                    );
                });
            });
        }
    };

    const reset = () => {
        englishContainer.replaceChildren();
        filipinoContainer.replaceChildren();
        attachmentContainer.replaceChildren();
        counter = 0;
        closeAttachmentMenu();
        refresh();
    };

    const load = (components = [], readonly = false, faqId = null) => {
        reset();

        if (!Array.isArray(components)) return;

        components.slice(0, MAX_ATTACHMENTS + MAX_TEXT_PER_LANGUAGE * 2).forEach((component, componentIndex) => {
            if (!component || !component.type) return;

            const normalized = {
                ...component,
                componentIndex,
            };

            if (normalized.type === 'text') {
                createText(normalized.language === 'fil' ? 'fil' : 'en', normalized, readonly);
                return;
            }

            if (['image', 'file', 'link', 'qr_code'].includes(normalized.type)) {
                createAttachment(normalized.type, normalized, readonly, faqId);
            }
        });

        refresh();
    };

    document.querySelectorAll('.faq-response-add-language').forEach(button => {
        button.addEventListener('click', () => {
            if (button.disabled) return;
            if (allTextItems().length + attachmentContainer.querySelectorAll('.faq-response-item').length >= MAX_ATTACHMENTS) return;
            createText(button.dataset.responseLanguage === 'fil' ? 'fil' : 'en');
            refresh();

            const target = button.dataset.responseLanguage === 'fil'
                ? filipinoContainer
                : englishContainer;

            target.lastElementChild?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        });
    });

    if (attachmentAdd && attachmentMenu) {
        attachmentAdd.addEventListener('click', () => {
            if (attachmentAdd.disabled) return;

            const isOpen = !attachmentMenu.hidden;
            attachmentMenu.hidden = isOpen;
            attachmentAdd.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
        });

        attachmentMenu.addEventListener('click', event => {
            const option = event.target.closest('[data-attachment-type]');
            if (!option) return;

            const type = option.dataset.attachmentType;
            if (!['image', 'file', 'link', 'qr_code'].includes(type)) return;

            if (allTextItems().length + attachmentContainer.querySelectorAll('.faq-response-item').length >= MAX_ATTACHMENTS) {
                return;
            }

            createAttachment(type);
            closeAttachmentMenu();
            refresh();

            attachmentContainer.lastElementChild?.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        });
    }

    document.addEventListener('click', event => {
        if (!event.target.closest('.faq-attachments-section')) {
            closeAttachmentMenu();
        }
    });

    document.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-response]');
        if (!remove) return;

        const item = remove.closest('.faq-response-item');
        if (item) item.remove();
        refresh();
    });

    const replaceLanguageTexts = (language, values = []) => {
        const container = language === 'fil' ? filipinoContainer : englishContainer;
        container.replaceChildren();
        values
            .map(value => String(value ?? '').trim())
            .filter(Boolean)
            .slice(0, MAX_TEXT_PER_LANGUAGE)
            .forEach(value => createText(language, { content: value }));
        refresh();
    };

    window.FaqResponseBuilder = {
        reset,
        load,
        replaceLanguageTexts,
        getData: () => Array.from(allTextItems()).map(item => ({
            type: 'text',
            language: item.dataset.responseLanguage
        })),
    };
});
