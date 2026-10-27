/**
 * KNOWURLOCAL
 * My Inquiries — Inquiry Card Renderer
 *
 * Responsible for updating an existing inquiry card when
 * fresh server data is received through the realtime flow.
 */

/**
 * Status configuration.
 *
 * Keeping status-related UI in one place prevents duplicated
 * conditionals throughout the renderer.
 */
const STATUS_CONFIG = {
    pending: {
        label: 'Waiting for response',
        icon: 'ph-clock',
    },

    awaiting_confirmation: {
        label: 'Response available',
        icon: 'ph-question',
    },

    needs_follow_up: {
        label: 'Follow-up needed',
        icon: 'ph-arrow-counter-clockwise',
    },

    answered: {
        label: 'Resolved',
        icon: 'ph-check',
    },

    trashed: {
        label: 'Trashed by administration',
        icon: 'ph-trash',
    },
};

/**
 * Returns the configuration for a status.
 *
 * Unknown statuses intentionally fall back to the pending-style
 * presentation instead of trusting arbitrary server-provided
 * values as CSS classes or icon class names.
 */
function getStatusConfig(status) {
    return STATUS_CONFIG[status] ?? {
        label: 'Waiting for response',
        icon: 'ph-clock',
    };
}

/**
 * Creates an element with a class name.
 *
 * Using DOM APIs instead of innerHTML means response content
 * is inserted as text rather than interpreted as HTML.
 */
function createElement(tagName, className) {
    const element = document.createElement(tagName);

    if (className) {
        element.className = className;
    }

    return element;
}

/**
 * Safely creates an icon.
 *
 * The icon name comes only from STATUS_CONFIG above, never
 * directly from the server response.
 */
function createIcon(iconName) {
    const icon = document.createElement('i');

    icon.classList.add('ph-light', iconName);
    icon.setAttribute('aria-hidden', 'true');

    return icon;
}

/**
 * Creates a response component.
 *
 * Supported component types:
 * - text
 * - image
 * - file
 * - link
 * - qr_code
 */
function createResponseComponent(component) {
    if (!component || typeof component !== 'object') {
        return null;
    }

    const type = component.type;
    const content =
        typeof component.content === 'string'
            ? component.content
            : '';

    const label =
        typeof component.label === 'string'
            ? component.label
            : '';

    /**
     * Plain text response.
     */
    if (type === 'text') {
        const wrapper = createElement(
            'div',
            'response-component response-component-text'
        );

        const paragraph = document.createElement('p');

        paragraph.textContent = content;

        wrapper.appendChild(paragraph);

        return wrapper;
    }

    /**
     * Image response.
     *
     * The URL is supplied by Laravel's authorized attachment
     * endpoint rather than exposing the private storage path.
     */
    if (type === 'image') {
        if (!component.attachment_url) {
            return null;
        }

        const wrapper = createElement(
            'div',
            'response-component response-component-image'
        );

        if (label) {
            const labelElement = createElement(
                'span',
                'response-component-label'
            );

            labelElement.textContent = label;

            wrapper.appendChild(labelElement);
        }

        const button = document.createElement('button');

        button.type = 'button';
        button.className = 'response-image-trigger';
        button.dataset.imageUrl = component.attachment_url;
        button.setAttribute(
            'aria-label',
            'View response image'
        );

        const image = document.createElement('img');

        image.src = component.attachment_url;
        image.alt = label || 'Official response image';
        image.loading = 'lazy';

        button.appendChild(image);
        wrapper.appendChild(button);

        return wrapper;
    }

    /** File and link resources use the same card hierarchy as chatbot resources. */
    if (type === 'file') {
        if (!component.attachment_url) return null;
        const link = document.createElement('a');
        link.className = 'response-resource-card is-file';
        link.href = component.attachment_url;
        link.setAttribute('download', '');
        link.setAttribute('aria-label', `Download ${label || 'attachment'}`);
        const iconWrap = createElement('span', 'response-resource-icon');
        iconWrap.appendChild(createIcon('ph-file-arrow-down'));
        const copy = createElement('span', 'response-resource-copy');
        const title = createElement('span', 'response-resource-title');
        title.textContent = label || 'Download attachment';
        const detail = createElement('span', 'response-resource-detail');
        detail.textContent = 'File attachment';
        copy.append(title, detail);
        const action = createIcon('ph-download-simple');
        action.classList.add('response-resource-action');
        link.append(iconWrap, copy, action);
        return link;
    }

    if (type === 'link') {
        if (!isSafeHttpUrl(content)) return null;
        const link = document.createElement('a');
        link.className = 'response-resource-card is-link';
        link.href = content;
        // Internal agency navigation stays in this tab, matching chatbot behavior.
        let internalAgencyNavigation = false;
        try {
            const parsed = new URL(content, window.location.href);
            internalAgencyNavigation = parsed.origin === window.location.origin
                && /^\/navigate\/[^/]+\/?$/.test(parsed.pathname);
        } catch (_) {}
        if (!internalAgencyNavigation) {
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
        }
        link.setAttribute('aria-label', `Open ${label || content}`);
        const iconWrap = createElement('span', 'response-resource-icon');
        iconWrap.appendChild(createIcon('ph-globe'));
        const copy = createElement('span', 'response-resource-copy');
        const title = createElement('span', 'response-resource-title');
        title.textContent = label || content;
        const detail = createElement('span', 'response-resource-detail');
        try { detail.textContent = new URL(content).hostname.replace(/^www\./, ''); }
        catch (_) { detail.textContent = content; }
        copy.append(title, detail);
        const action = createIcon('ph-arrow-up-right');
        action.classList.add('response-resource-action');
        link.append(iconWrap, copy, action);
        return link;
    }

    /**
     * QR response: local QR matrix rendered as an SVG, with a normal destination link.
     */
    if (type === 'qr_code') {
        if (!isSafeHttpUrl(content)) return null;

        const wrapper = createElement('div', 'response-component response-component-qr');
        const heading = createElement('div', 'response-qr-heading');
        heading.appendChild(createIcon('ph-qr-code'));

        const headingText = document.createElement('span');
        headingText.textContent = label || 'QR code';
        heading.appendChild(headingText);

        const qr = createElement('div', 'response-qr-code');
        qr.dataset.qrValue = content;
        qr.setAttribute('role', 'img');
        qr.setAttribute('aria-label', `${label || 'Scannable'} QR code`);

        const link = document.createElement('a');
        link.className = 'response-qr-open';
        link.href = content;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.append(document.createTextNode('Open destination '), createIcon('ph-arrow-up-right'));

        wrapper.append(heading, qr, link);
        return wrapper;
    }

    /**
     * Unknown component types are ignored.
     *
     * This is safer than rendering arbitrary server-provided
     * markup into the page.
     */
    return null;
}

/** Only HTTP(S) destinations are valid for external links and QR codes. */
function isSafeHttpUrl(value) {
    try {
        const url = new URL(value, window.location.origin);
        return ['http:', 'https:'].includes(url.protocol);
    } catch {
        return false;
    }
}

/**
 * Generate QR SVGs with the bundled QR engine. This is intentionally local,
 * so the inquiry page does not depend on a remote QR image service.
 */
export function renderInquiryQRCodes(root = document) {
    const QRCore = window.QRCodeCore;
    root.querySelectorAll('.response-qr-code[data-qr-value]').forEach((container) => {
        if (container.dataset.qrRendered === '1') return;

        const value = container.dataset.qrValue;
        if (!isSafeHttpUrl(value)) return;

        if (typeof QRCore !== 'function') {
            showQrFallback(container, value);
            return;
        }

        try {
            let qr = null;
            let lastError = null;

            for (const level of [QRCore.CorrectLevel?.M, QRCore.CorrectLevel?.L]) {
                if (level === undefined) continue;
                try {
                    const candidate = new QRCore(0, level);
                    candidate.addData(value);
                    candidate.make();
                    qr = candidate;
                    break;
                } catch (error) {
                    lastError = error;
                }
            }

            if (!qr) throw lastError || new Error('Unable to encode QR destination.');

            const count = qr.getModuleCount();
            const quietZone = 4;
            const size = count + quietZone * 2;
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
            svg.setAttribute('role', 'img');
            svg.setAttribute('aria-label', container.getAttribute('aria-label') || 'Scannable QR code');
            svg.setAttribute('shape-rendering', 'crispEdges');
            svg.classList.add('response-qr-svg');

            const background = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            background.setAttribute('width', String(size));
            background.setAttribute('height', String(size));
            background.setAttribute('fill', '#ffffff');
            svg.appendChild(background);

            for (let row = 0; row < count; row++) {
                for (let col = 0; col < count; col++) {
                    if (!qr.isDark(row, col)) continue;
                    const module = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    module.setAttribute('x', String(col + quietZone));
                    module.setAttribute('y', String(row + quietZone));
                    module.setAttribute('width', '1');
                    module.setAttribute('height', '1');
                    module.setAttribute('fill', '#111827');
                    svg.appendChild(module);
                }
            }

            container.replaceChildren(svg);
            container.dataset.qrRendered = '1';
        } catch (error) {
            console.error('Unable to render inquiry QR code:', error);
            showQrFallback(container, value);
        }
    });
}

function showQrFallback(container, value) {
    const link = document.createElement('a');
    link.href = value;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.className = 'response-qr-open';
    link.textContent = 'Open destination';
    container.replaceChildren(link);
}

/**
 * Rebuilds the official response section.
 */
function renderOfficialResponse(card, inquiry) {
    const response = inquiry.latest_response;
    const existingResponse = response?.id
        ? Array.from(card.querySelectorAll('.official-response'))
            .find(section => String(section.dataset.responseId) === String(response.id)) || null
        : card.querySelector('.official-response:last-of-type');

    /**
     * If there is no response, remove an old response section.
     */
    if (!response) {
        card.querySelectorAll('.official-response').forEach(section => section.remove());
        return;
    }

    const section = existingResponse || createElement('section', 'official-response');
    if (response.id !== undefined && response.id !== null) {
        section.dataset.responseId = String(response.id);
    }

    /**
     * Build the response header.
     */
    let header = section.querySelector(
        '.official-response-header'
    );

    if (!header) {
        header = createElement(
            'div',
            'official-response-header'
        );

        const title = createElement(
            'div',
            'official-response-title'
        );

        title.appendChild(
            createIcon('ph-check-circle')
        );

        const titleText = document.createElement('span');

        titleText.textContent = 'Official Response';

        title.appendChild(titleText);

        header.appendChild(title);

        section.appendChild(header);
    }

    /**
     * Update the response date.
     */
    const forwardedAt =
        response.forwarded_at;

    let responseDate =
        header.querySelector('.response-date');

    if (forwardedAt) {
        if (!responseDate) {
            responseDate = document.createElement('time');

            responseDate.className = 'response-date';

            header.appendChild(responseDate);
        }

        const date = new Date(forwardedAt);

        if (!Number.isNaN(date.getTime())) {
            responseDate.dateTime = date.toISOString();

            responseDate.textContent =
                date.toLocaleDateString(
                    'en-US',
                    {
                        month: 'short',
                        day: '2-digit',
                        year: 'numeric',
                    }
                );
        }
    } else {
        responseDate?.remove();
    }

    /**
     * Rebuild only the response component container.
     *
     * This prevents stale components from a previous response
     * from remaining visible.
     */
    let componentsContainer =
        section.querySelector('.response-components');

    if (!componentsContainer) {
        componentsContainer = createElement(
            'div',
            'response-components'
        );

        section.appendChild(
            componentsContainer
        );
    }

    componentsContainer.replaceChildren();

    const components =
        Array.isArray(response.components)
            ? response.components
            : [];

    const renderOrder = ['image', 'text', 'file', 'link', 'qr_code'];
    components
        .map((component, index) => ({ component, index }))
        .sort((a, b) => {
            const aOrder = renderOrder.indexOf(a.component?.type);
            const bOrder = renderOrder.indexOf(b.component?.type);
            return (aOrder < 0 ? renderOrder.length : aOrder) -
                (bOrder < 0 ? renderOrder.length : bOrder) || a.index - b.index;
        })
        .forEach(({ component }) => {
            const element = createResponseComponent(component);
            if (!element) return;

            // Keep multiple images in a compact responsive gallery while leaving
            // text, files, links, and QR codes in their established reading order.
            if (component?.type === 'image') {
                let imageGrid = componentsContainer.querySelector(':scope > .response-image-grid');
                if (!imageGrid) {
                    imageGrid = createElement('div', 'response-image-grid');
                    imageGrid.setAttribute('aria-label', 'Response images');
                    componentsContainer.appendChild(imageGrid);
                }
                imageGrid.appendChild(element);
                return;
            }

            componentsContainer.appendChild(element);
        });

    renderInquiryQRCodes(componentsContainer);

    /**
     * Insert the response section into the card if it
     * did not previously exist.
     */
    if (!existingResponse) {
        const previousResponses = card.querySelectorAll('.official-response');
        const lastResponse = previousResponses.length
            ? previousResponses[previousResponses.length - 1]
            : null;
        const questionSection = card.querySelector('.inquiry-question-full');

        if (lastResponse) {
            const followingElement = lastResponse.nextElementSibling;
            const insertionAnchor = followingElement?.matches('.citizen-follow-up')
                ? followingElement
                : lastResponse;
            insertionAnchor.insertAdjacentElement('afterend', section);
        } else if (questionSection) {
            questionSection.insertAdjacentElement('afterend', section);
        } else {
            card.querySelector('.inquiry-details-inner')?.appendChild(section);
        }
    }
}

/**
 * Removes the waiting-for-response message when an
 * official response becomes available.
 */
function removePendingMessage(card) {
    card.querySelector(
        '.inquiry-pending-message'
    )?.remove();
}

/**
 * Keeps the small "New" response marker synchronized with the
 * authoritative inquiry state.
 */
function updateUnreadIndicator(card, inquiry) {
    const status = inquiry?.deleted_at ? 'trashed' : inquiry?.status;
    // Trashed tickets are excluded from unread-response indicators.
    const isTrashUnread = false;

    // The server is authoritative in both directions: a fresh response resets
    // answer_seen_at to null, even if this card had previously been read.
    card.dataset.answerSeen = inquiry?.answer_seen_at ? '1' : '0';

    const isResponseUnread =
        status !== 'trashed' &&
        card?.dataset.answerSeen !== '1' &&
        (
            (
                status === 'awaiting_confirmation' &&
                !inquiry?.answer_seen_at
            ) ||
            (
                status === 'answered' &&
                !inquiry?.answer_seen_at
            )
        );

    const unreadType = isTrashUnread
        ? 'trash'
        : isResponseUnread
            ? 'response'
            : null;

    let indicator =
        card.querySelector('.inquiry-unread-badge');

    if (!unreadType) {
        indicator?.remove();
        return;
    }

    const label = unreadType === 'trash'
        ? 'New trashed inquiry'
        : 'New response';

    if (indicator && indicator.dataset.unreadType === unreadType) {
        return;
    }

    if (!indicator) {
        const statusBadge =
            card.querySelector('.inquiry-status-badge');

        if (!statusBadge) {
            return;
        }

        indicator = createElement(
            'span',
            'inquiry-unread-badge'
        );

        statusBadge.insertAdjacentElement(
            'afterend',
            indicator
        );
    }

    indicator.dataset.unreadType = unreadType;
    indicator.title = label;
    indicator.setAttribute('aria-label', label);
    indicator.textContent = 'New';
}

/**
 * Updates the status UI.
 */
function updateStatus(card, status) {
    const config = getStatusConfig(status);

    card.dataset.status = status;
    card.classList.toggle('is-trashed', status === 'trashed');

    /**
     * Update the status icon container.
     */
    const statusBadge =
        card.querySelector(
            '.inquiry-status-badge'
        );

    if (statusBadge) {
        statusBadge.className =
            `inquiry-status-badge ${status}`;

        statusBadge.replaceChildren(
            createIcon(config.icon),
            document.createTextNode(config.label)
        );
    }
}


/**
 * Creates the citizen confirmation section.
 *
 * The generated structure intentionally matches
 * response-confirmation.blade.php so the initial
 * server-rendered UI and realtime-created UI behave
 * identically.
 */
function createConfirmationSection(requestId) {
    const section = createElement(
        'section',
        'response-confirmation'
    );

    /*
     * Give the section an accessible heading relationship.
     */
    section.setAttribute(
        'aria-labelledby',
        `response-confirmation-title-${requestId}`
    );

    /*
     * ---------------------------------------------------------
     * Confirmation header
     * ---------------------------------------------------------
     */
    const header = createElement(
        'div',
        'response-confirmation-header'
    );

    const headerIcon = createElement(
        'div',
        'response-confirmation-icon'
    );

    headerIcon.setAttribute(
        'aria-hidden',
        'true'
    );

    headerIcon.appendChild(
        createIcon('ph-question')
    );

    const headerContent =
        document.createElement('div');

    const title =
        document.createElement('h3');

    title.id =
        `response-confirmation-title-${requestId}`;

    title.textContent =
        'Did this response resolve your concern?';

    const description =
        document.createElement('p');

    description.textContent =
        'Let the office know whether you need further assistance.';

    headerContent.appendChild(title);
    headerContent.appendChild(description);

    header.appendChild(headerIcon);
    header.appendChild(headerContent);

    section.appendChild(header);

    /*
     * ---------------------------------------------------------
     * Confirmation actions
     * ---------------------------------------------------------
     */
    const actions = createElement(
        'div',
        'confirmation-actions'
    );

    const confirmButton =
        document.createElement('button');

    confirmButton.type = 'button';

    confirmButton.className =
        'confirmation-btn confirmation-btn-primary';

    confirmButton.dataset.confirmResponse = '';

    confirmButton.appendChild(
        createIcon('ph-check')
    );

    confirmButton.appendChild(
        document.createTextNode(
            'Yes, this resolved my concern'
        )
    );

    const followUpButton =
        document.createElement('button');

    followUpButton.type = 'button';

    followUpButton.className =
        'confirmation-btn confirmation-btn-secondary';

    followUpButton.dataset.followUpResponse = '';

    followUpButton.appendChild(
        createIcon(
            'ph-arrow-counter-clockwise'
        )
    );

    followUpButton.appendChild(
        document.createTextNode(
            'No, I still need help'
        )
    );

    actions.appendChild(confirmButton);
    actions.appendChild(followUpButton);

    section.appendChild(actions);

    /*
     * ---------------------------------------------------------
     * Follow-up form
     * ---------------------------------------------------------
     */
    const form =
        document.createElement('form');

    form.className =
        'follow-up-form';

    form.hidden = true;

    const label =
        document.createElement('label');

    const reasonId =
        `follow-up-reason-${requestId}`;

    label.htmlFor = reasonId;

    label.textContent =
        'What still needs clarification?';

    const textarea =
        document.createElement('textarea');

    textarea.id = reasonId;
    textarea.name = 'reason';
    textarea.rows = 4;
    textarea.maxLength = 2000;

    textarea.placeholder =
        'Tell the office what information is still missing or unclear.';

    const formActions = createElement(
        'div',
        'follow-up-form-actions'
    );

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'confirmation-btn confirmation-btn-secondary';
    cancelButton.dataset.cancelFollowUp = '';
    cancelButton.appendChild(createIcon('ph-arrow-u-up-left'));
    cancelButton.appendChild(document.createTextNode('Cancel'));

    const submitButton =
        document.createElement('button');

    submitButton.type = 'submit';

    submitButton.className =
        'confirmation-btn confirmation-btn-primary';

    submitButton.appendChild(
        createIcon('ph-paper-plane-tilt')
    );

    submitButton.appendChild(
        document.createTextNode(
            'Request follow-up'
        )
    );

    formActions.appendChild(cancelButton);
    formActions.appendChild(submitButton);

    form.appendChild(label);
    form.appendChild(textarea);
    form.appendChild(formActions);

    section.appendChild(form);

    return section;
}

/**
 * Updates the confirmation controls according
 * to the latest server state.
 */
function updateConfirmationState(
    card,
    status,
    latestResponse = null
) {
    const confirmations = Array.from(
        card.querySelectorAll('.response-confirmation')
    );

    // The confirmation UI is valid only while the ticket awaits a citizen
    // decision. Remove it when the authoritative server status has changed.
    if (status !== 'awaiting_confirmation') {
        confirmations.forEach(section => section.remove());
        return;
    }

    const requestId = card.dataset.id;
    if (!requestId) return;

    // Reuse the existing confirmation section (including any in-progress form)
    // rather than returning early and leaving it attached to an older response.
    let confirmation = confirmations.shift() || null;
    confirmations.forEach(section => section.remove());

    if (!confirmation) {
        confirmation = createConfirmationSection(requestId);
    }

    // Match the authoritative latest response ID when supplied by Laravel.
    // The last rendered response is a defensive fallback for legacy payloads.
    const responseId = latestResponse?.id;
    let latestSection = responseId !== undefined && responseId !== null
        ? Array.from(card.querySelectorAll('.official-response'))
            .find(section => String(section.dataset.responseId) === String(responseId))
        : null;

    if (!latestSection) {
        const responseSections = card.querySelectorAll('.official-response');
        latestSection = responseSections[responseSections.length - 1] || null;
    }

    if (latestSection) {
        // Moving an existing node preserves its listeners and form state. This
        // makes the live-update path converge on the same placement as Blade.
        if (confirmation.previousElementSibling !== latestSection) {
            latestSection.insertAdjacentElement('afterend', confirmation);
        }
        return;
    }

    const questionSection = card.querySelector('.inquiry-question-full');
    questionSection?.insertAdjacentElement('afterend', confirmation);
}



/**
 * Adds a small visual indicator that the card was
 * updated in realtime.
 */
function flashRealtimeUpdate(card) {
    card.classList.remove(
        'realtime-response'
    );

    /**
     * Force a browser reflow so the animation can
     * restart when multiple updates happen.
     */
    void card.offsetWidth;

    card.classList.add(
        'realtime-response'
    );

    window.setTimeout(() => {
        card.classList.remove(
            'realtime-response'
        );
    }, 2500);
}

function updateTrashNotice(card, inquiry) {
    const isTrashed = Boolean(inquiry?.deleted_at);
    let notice = card.querySelector('[data-trash-notice]');

    if (isTrashed && !notice) {
        notice = createElement('section', 'inquiry-trash-notice');
        notice.dataset.trashNotice = '';
        notice.innerHTML = `
            <div class="inquiry-trash-notice-icon" aria-hidden="true">
                <i class="ph-light ph-trash"></i>
            </div>
            <div class="inquiry-trash-notice-content">
                <strong>This inquiry was moved to the trash</strong>
                <p>The administration removed this inquiry from the active support queue. Your inquiry remains visible here for your records.</p>
                <p class="inquiry-trash-reason" data-trash-reason-wrap>
                    <span>Reason from administration:</span>
                    <span data-trash-reason></span>
                </p>
                <time data-trash-date></time>
            </div>
        `;

        const detailsInner = card.querySelector('.inquiry-details-inner');
        detailsInner?.prepend(notice);
    }

    if (!isTrashed) {
        notice?.remove();
        notice = null;
    }

    const detailsInner = card.querySelector('.inquiry-details-inner');
    if (detailsInner) {
        Array.from(detailsInner.children).forEach(child => {
            if (child !== notice) child.hidden = isTrashed;
        });
    }

    if (!notice) return;

    const reasonWrap = notice.querySelector('[data-trash-reason-wrap]');
    const reason = notice.querySelector('[data-trash-reason]');
    if (inquiry.trash_reason) {
        reason.textContent = inquiry.trash_reason;
        reasonWrap.hidden = false;
    } else {
        reasonWrap.hidden = true;
    }

    const date = notice.querySelector('[data-trash-date]');
    if (date) {
        if (inquiry.deleted_at) {
            const parsed = new Date(inquiry.deleted_at);
            date.textContent = Number.isNaN(parsed.getTime())
                ? ''
                : parsed.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
            date.hidden = Number.isNaN(parsed.getTime());
        } else {
            date.hidden = true;
        }
    }

}

/**
 * Updates an existing inquiry card with authoritative
 * data returned from Laravel.
 */
export function updateInquiryCard(
    card,
    inquiry
) {
    if (
        !card ||
        !inquiry ||
        typeof inquiry !== 'object'
    ) {
        return;
    }

    /**
     * Never trust the response ID from the server
     * as a selector. The card was already located by
     * the realtime event using the inquiry ID.
     */
    updateStatus(
        card,
        inquiry.deleted_at ? 'trashed' : inquiry.status
    );

    updateTrashNotice(
        card,
        inquiry
    );

    updateUnreadIndicator(
        card,
        inquiry
    );

    const summaryPreview = card.querySelector('.messenger-row-preview');
    if (summaryPreview && inquiry.deleted_at) {
        summaryPreview.textContent = 'Ticket moved to trash by administration';
    } else if (summaryPreview && summaryPreview.textContent.trim() === 'Ticket moved to trash by administration') {
        const responseText = (inquiry.latest_response?.components || [])
            .filter(component => component.type === 'text' && component.content)
            .map(component => component.content)
            .join(' ');
        summaryPreview.textContent = responseText || inquiry.answer || inquiry.question || 'Ticket submitted';
    }

    if (!inquiry.deleted_at) {
        renderOfficialResponse(
            card,
            inquiry
        );

        if (inquiry.latest_response) {
            removePendingMessage(card);
        }

        updateConfirmationState(
            card,
            inquiry.status,
            inquiry.latest_response
        );
    }

    flashRealtimeUpdate(card);

    window.dispatchEvent(
    new CustomEvent('inquiry:updated', {
        detail: {
            card,
            status: inquiry.deleted_at ? 'trashed' : inquiry.status,
        },
    })
);
}