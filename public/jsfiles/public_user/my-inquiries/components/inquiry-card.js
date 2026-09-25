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
        icon: 'ph-seal-question',
    },

    needs_follow_up: {
        label: 'Follow-up needed',
        icon: 'ph-arrow-counter-clockwise',
    },

    answered: {
        label: 'Resolved',
        icon: 'ph-check',
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

    /**
     * File response.
     */
    if (type === 'file') {
        if (!component.attachment_url) {
            return null;
        }

        const wrapper = createElement(
            'div',
            'response-component response-component-file'
        );

        wrapper.appendChild(
            createIcon('ph-file')
        );

        const info = createElement(
            'div',
            'response-file-info'
        );

        if (label) {
            const labelElement = createElement(
                'span',
                'response-component-label'
            );

            labelElement.textContent = label;

            info.appendChild(labelElement);
        }

        const link = document.createElement('a');

        link.href = component.attachment_url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = 'View document';

        info.appendChild(link);
        wrapper.appendChild(info);

        return wrapper;
    }

    /**
     * Normal external link response.
     */
    if (type === 'link') {
        if (!content) {
            return null;
        }

        const wrapper = createElement(
            'div',
            'response-component response-component-link'
        );

        wrapper.appendChild(
            createIcon('ph-link')
        );

        const info = createElement(
            'div',
            'response-link-info'
        );

        if (label) {
            const labelElement = createElement(
                'span',
                'response-component-label'
            );

            labelElement.textContent = label;

            info.appendChild(labelElement);
        }

        const link = document.createElement('a');

        link.href = content;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = content;

        info.appendChild(link);
        wrapper.appendChild(info);

        return wrapper;
    }

    /**
     * QR destination response.
     */
    if (type === 'qr_code') {
        if (!content) {
            return null;
        }

        const wrapper = createElement(
            'div',
            'response-component response-component-qr'
        );

        wrapper.appendChild(
            createIcon('ph-qr-code')
        );

        const info = createElement(
            'div',
            'response-qr-info'
        );

        if (label) {
            const labelElement = createElement(
                'span',
                'response-component-label'
            );

            labelElement.textContent = label;

            info.appendChild(labelElement);
        }

        const link = document.createElement('a');

        link.href = content;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = 'Open QR destination';

        info.appendChild(link);
        wrapper.appendChild(info);

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

/**
 * Rebuilds the official response section.
 */
function renderOfficialResponse(card, inquiry) {
    const existingResponse = card.querySelector(
        '.official-response'
    );

    const response = inquiry.latest_response;

    /**
     * If there is no response, remove an old response section.
     */
    if (!response) {
        existingResponse?.remove();

        return;
    }

    const section =
        existingResponse ||
        createElement(
            'section',
            'official-response'
        );

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
            createIcon('ph-seal-check')
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

    components.forEach((component) => {
        const element =
            createResponseComponent(component);

        if (element) {
            componentsContainer.appendChild(element);
        }
    });

    /**
     * Insert the response section into the card if it
     * did not previously exist.
     */
    if (!existingResponse) {
        const questionSection =
            card.querySelector(
                '.inquiry-question-full'
            );

        questionSection?.insertAdjacentElement(
            'afterend',
            section
        );
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
 * Updates the status UI.
 */
function updateStatus(card, status) {
    const config = getStatusConfig(status);

    card.dataset.status = status;

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
        createIcon('ph-seal-question')
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

    formActions.appendChild(
        submitButton
    );

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
    status
) {
    const existingConfirmation =
        card.querySelector(
            '.response-confirmation'
        );

    /*
     * Only awaiting_confirmation should display
     * the confirmation interface.
     */
    if (
        status !==
        'awaiting_confirmation'
    ) {
        existingConfirmation?.remove();

        return;
    }

    /*
     * If Blade already rendered the confirmation
     * component, do not create a duplicate.
     */
    if (existingConfirmation) {
        return;
    }

    /*
     * The card's data-id is the authoritative
     * inquiry identifier for this DOM element.
     */
    const requestId =
        card.dataset.id;

    if (!requestId) {
        return;
    }

    const confirmation =
        createConfirmationSection(
            requestId
        );

    /*
     * Place the confirmation interface directly
     * after the official response.
     */
    const officialResponse =
        card.querySelector(
            '.official-response'
        );

    if (officialResponse) {
        officialResponse.insertAdjacentElement(
            'afterend',
            confirmation
        );

        return;
    }

    /*
     * Defensive fallback for an unexpected state.
     */
    const questionSection =
        card.querySelector(
            '.inquiry-question-full'
        );

    questionSection?.insertAdjacentElement(
        'afterend',
        confirmation
    );
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
        inquiry.status
    );

    renderOfficialResponse(
        card,
        inquiry
    );

    if (inquiry.latest_response) {
        removePendingMessage(card);
    }

    updateConfirmationState(
        card,
        inquiry.status
    );

    flashRealtimeUpdate(card);

    window.dispatchEvent(
    new CustomEvent('inquiry:updated', {
        detail: {
            card,
            status: inquiry.status,
        },
    })
);
}