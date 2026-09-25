/*
|--------------------------------------------------------------------------
| KNOWURLOCAL — Support Response Builder
|--------------------------------------------------------------------------
|
| This module manages the dynamic Official Response builder inside
| the Support Request modal.
|
| Supported components:
| - Text
| - Image
| - File
| - Link
| - QR Code
|
| RESPONSIBILITY:
| This module owns the response-builder UI and client-side validation.
|
| It does NOT submit the Support Request form.
|
| The Manage Modal module is responsible for the actual HTTP request.
|
| SECURITY NOTE:
| Client-side validation is only for user experience.
| Laravel must always perform authoritative validation.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| DOM REFERENCES
|--------------------------------------------------------------------------
|
| These are assigned when initializeResponseBuilder() runs.
|
| We intentionally do not query the DOM immediately when this module
| is imported. The page entry point controls initialization.
|--------------------------------------------------------------------------
*/

let builder = null;

let componentsContainer = null;

let latestResponseContainer = null;

let latestResponseComponentsContainer = null;

let historyContainer = null;

let historyComponentsContainer = null;

let emptyState = null;

let addButton = null;

let componentMenu = null;


/*
|--------------------------------------------------------------------------
| INITIALIZATION STATE
|--------------------------------------------------------------------------
|
| Prevents the component from registering duplicate event listeners
| if initializeResponseBuilder() is accidentally called more than once.
|--------------------------------------------------------------------------
*/

let isInitialized = false;


/*
|--------------------------------------------------------------------------
| CONFIGURATION
|--------------------------------------------------------------------------
*/


/*
| Maximum number of components allowed in one official response.
|
| This mirrors Laravel's:
|
| components => max:10
*/
const MAX_COMPONENTS = 10;


/*
| Maximum content length.
|
| This mirrors Laravel's:
|
| components.*.content => max:5000
*/
const MAX_TEXT_LENGTH = 5000;


/*
| Maximum optional label length.
|
| This mirrors Laravel's:
|
| components.*.label => max:255
*/
const MAX_LABEL_LENGTH = 255;


/*
| Only these component types are allowed.
|
| The backend must enforce the same rule.
*/
const ALLOWED_COMPONENT_TYPES = [
    'text',
    'image',
    'file',
    'link',
    'qr_code',
];


/*
|--------------------------------------------------------------------------
| ALLOWED FILE TYPES
|--------------------------------------------------------------------------
|
| These checks improve the user experience.
|
| They are NOT a security boundary.
|
| Laravel must independently validate the uploaded file.
|--------------------------------------------------------------------------
*/

const ALLOWED_FILE_TYPES = {
    image: [
        'image/jpeg',
        'image/png',
        'image/webp',
    ],

    file: [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],
};


/*
|--------------------------------------------------------------------------
| STATE
|--------------------------------------------------------------------------
*/

/*
| Each dynamically-created component receives a unique index.
|
| Example:
|
| components[0][content]
| components[1][content]
| components[2][content]
*/
let componentCounter = 0;


/*
|--------------------------------------------------------------------------
| HTML ESCAPING
|--------------------------------------------------------------------------
|
| Dynamic strings must never be inserted into HTML without escaping.
|
| This protects generated component markup from HTML injection.
|--------------------------------------------------------------------------
*/

const escapeHtml = (value) => {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
};


/*
|--------------------------------------------------------------------------
| COMPONENT INDEX
|--------------------------------------------------------------------------
*/

const getNextComponentIndex = () => {
    const index = componentCounter;

    componentCounter += 1;

    return index;
};


/*
|--------------------------------------------------------------------------
| COMPONENT COUNT
|--------------------------------------------------------------------------
*/

export const getComponentCount = () => {
    if (!componentsContainer) {
        return 0;
    }

    return componentsContainer.querySelectorAll(
        '.support-response-component'
    ).length;
};


/*
|--------------------------------------------------------------------------
| EMPTY STATE
|--------------------------------------------------------------------------
*/

const refreshEmptyState = () => {
    if (!emptyState) {
        return;
    }

    const hasComponents =
        getComponentCount() > 0;

    emptyState.hidden = hasComponents;
};


/*
|--------------------------------------------------------------------------
| URL SECURITY
|--------------------------------------------------------------------------
|
| Only HTTP and HTTPS URLs are accepted.
|
| Examples rejected:
|
| javascript:...
| data:...
| file:...
|--------------------------------------------------------------------------
*/

const isSafeUrl = (value) => {
    try {
        const url = new URL(value);

        return (
            url.protocol === 'http:' ||
            url.protocol === 'https:'
        );
    } catch {
        return false;
    }
};

/*
 * =========================================================
 * ATTACHMENT URL SECURITY
 * =========================================================
 *
 * Attachment URLs must be generated by our Laravel application.
 *
 * We do not allow arbitrary URLs to become image sources or
 * attachment links.
 */
const isSafeAttachmentUrl = (value) => {

    if (!value) {
        return false;
    }

    try {
        const url =
            new URL(
                value,
                window.location.origin
            );

        /*
         * The attachment endpoint must remain on the same
         * origin as KNOWURLOCAL.
         */
        if (
            url.origin !==
            window.location.origin
        ) {
            return false;
        }

        /*
         * Only allow the expected admin attachment route.
         */
        return url.pathname.includes(
            '/admin/support-requests/'
        ) &&
        url.pathname.endsWith(
            '/attachment'
        );

    } catch {
        return false;
    }
};


/*
|--------------------------------------------------------------------------
| COMPONENT HEADER
|--------------------------------------------------------------------------
*/

const createComponentHeader = (
    title,
    description,
    icon
) => {
    return `
        <div class="support-response-component-header">

            <div class="support-response-component-heading">

                <div
                    class="support-response-component-icon"
                    aria-hidden="true"
                >
                    <i class="ph-light ${escapeHtml(icon)}"></i>
                </div>

                <div class="support-response-component-heading-text">

                    <strong>
                        ${escapeHtml(title)}
                    </strong>

                    <span>
                        ${escapeHtml(description)}
                    </span>

                </div>

            </div>

            <button
                type="button"
                class="support-response-remove"
                data-action="remove-component"
                aria-label="Remove ${escapeHtml(title)}"
                title="Remove component"
            >
                <i
                    class="ph-light ph-trash"
                    aria-hidden="true"
                ></i>
            </button>

        </div>
    `;
};


/*
|--------------------------------------------------------------------------
| TEXT COMPONENT
|--------------------------------------------------------------------------
*/

const createTextComponent = (index) => {
    return `
        <div
            class="support-response-component"
            data-component-index="${index}"
            data-component-type="text"
        >

            ${createComponentHeader(
                'Text',
                'Write the official response.',
                'ph-text-aa'
            )}

            <div class="support-response-component-body">

                <label
                    class="support-response-field-label"
                    for="support-response-${index}-content"
                >
                    Response
                </label>

                <textarea
                    id="support-response-${index}-content"
                    name="components[${index}][content]"
                    class="support-response-textarea"
                    rows="5"
                    maxlength="${MAX_TEXT_LENGTH}"
                    placeholder="Write the official response for the citizen..."
                    data-field="content"
                    required
                ></textarea>

                <div class="support-response-field-meta">

                    <span>
                        This will be shown as part of the official response.
                    </span>

                    <span>
                        <strong data-character-count>0</strong>
                        / ${MAX_TEXT_LENGTH}
                    </span>

                </div>

                <input
                    type="hidden"
                    name="components[${index}][type]"
                    value="text"
                >

            </div>

        </div>
    `;
};


/*
|--------------------------------------------------------------------------
| IMAGE COMPONENT
|--------------------------------------------------------------------------
*/

const createImageComponent = (index) => {
    return `
        <div
            class="support-response-component"
            data-component-index="${index}"
            data-component-type="image"
        >

            ${createComponentHeader(
                'Image',
                'Attach an image to the response.',
                'ph-image'
            )}

            <div class="support-response-component-body">

                <label
                    class="support-response-field-label"
                    for="support-response-${index}-file"
                >
                    Image
                </label>

                <input
                    id="support-response-${index}-file"
                    type="file"
                    name="components[${index}][file]"
                    class="support-response-file-input"
                    accept=".jpg,.jpeg,.png,.webp"
                    data-field="file"
                    required
                >

                <div
                    class="support-response-file-meta"
                    data-file-meta
                >
                    JPG, PNG, or WEBP · Maximum 5 MB
                </div>

                <input
                    type="hidden"
                    name="components[${index}][type]"
                    value="image"
                >

            </div>

        </div>
    `;
};


/*
|--------------------------------------------------------------------------
| FILE COMPONENT
|--------------------------------------------------------------------------
*/

const createFileComponent = (index) => {
    return `
        <div
            class="support-response-component"
            data-component-index="${index}"
            data-component-type="file"
        >

            ${createComponentHeader(
                'File',
                'Attach a document to the response.',
                'ph-file'
            )}

            <div class="support-response-component-body">

                <label
                    class="support-response-field-label"
                    for="support-response-${index}-file"
                >
                    Document
                </label>

                <input
                    id="support-response-${index}-file"
                    type="file"
                    name="components[${index}][file]"
                    class="support-response-file-input"
                    accept=".pdf,.doc,.docx,.xls,.xlsx"
                    data-field="file"
                    required
                >

                <div
                    class="support-response-file-meta"
                    data-file-meta
                >
                    PDF, DOC, DOCX, XLS, or XLSX · Maximum 5 MB
                </div>

                <input
                    type="hidden"
                    name="components[${index}][type]"
                    value="file"
                >

            </div>

        </div>
    `;
};


/*
|--------------------------------------------------------------------------
| LINK COMPONENT
|--------------------------------------------------------------------------
*/

const createLinkComponent = (index) => {
    return `
        <div
            class="support-response-component"
            data-component-index="${index}"
            data-component-type="link"
        >

            ${createComponentHeader(
                'Link',
                'Provide a secure web link.',
                'ph-link'
            )}

            <div class="support-response-component-body">

                <label
                    class="support-response-field-label"
                    for="support-response-${index}-label"
                >
                    Link label
                </label>

                <input
                    id="support-response-${index}-label"
                    type="text"
                    name="components[${index}][label]"
                    class="support-response-input"
                    maxlength="${MAX_LABEL_LENGTH}"
                    placeholder="Example: Official application form"
                    data-field="label"
                >

                <label
                    class="support-response-field-label"
                    for="support-response-${index}-content"
                >
                    URL
                </label>

                <input
                    id="support-response-${index}-content"
                    type="url"
                    name="components[${index}][content]"
                    class="support-response-input"
                    maxlength="${MAX_TEXT_LENGTH}"
                    placeholder="https://example.gov.ph"
                    inputmode="url"
                    autocomplete="url"
                    data-field="content"
                    required
                >

                <div
                    class="support-response-field-error"
                    data-url-error
                    hidden
                >
                    Please enter a valid HTTP or HTTPS URL.
                </div>

                <input
                    type="hidden"
                    name="components[${index}][type]"
                    value="link"
                >

            </div>

        </div>
    `;
};


/*
|--------------------------------------------------------------------------
| QR CODE COMPONENT
|--------------------------------------------------------------------------
|
| The database currently stores the destination URL.
|
| Actual QR image generation can be implemented separately.
|--------------------------------------------------------------------------
*/

const createQrComponent = (index) => {
    return `
        <div
            class="support-response-component"
            data-component-index="${index}"
            data-component-type="qr_code"
        >

            ${createComponentHeader(
                'QR Code',
                'Provide the URL encoded by the QR code.',
                'ph-qr-code'
            )}

            <div class="support-response-component-body">

                <label
                    class="support-response-field-label"
                    for="support-response-${index}-label"
                >
                    QR code label
                </label>

                <input
                    id="support-response-${index}-label"
                    type="text"
                    name="components[${index}][label]"
                    class="support-response-input"
                    maxlength="${MAX_LABEL_LENGTH}"
                    placeholder="Example: Scan to apply online"
                    data-field="label"
                >

                <label
                    class="support-response-field-label"
                    for="support-response-${index}-content"
                >
                    Destination URL
                </label>

                <input
                    id="support-response-${index}-content"
                    type="url"
                    name="components[${index}][content]"
                    class="support-response-input"
                    maxlength="${MAX_TEXT_LENGTH}"
                    placeholder="https://example.gov.ph/application"
                    inputmode="url"
                    autocomplete="url"
                    data-field="content"
                    required
                >

                <div
                    class="support-response-field-error"
                    data-url-error
                    hidden
                >
                    Please enter a valid HTTP or HTTPS URL.
                </div>

                <input
                    type="hidden"
                    name="components[${index}][type]"
                    value="qr_code"
                >

            </div>

        </div>
    `;
};


/*
|--------------------------------------------------------------------------
| CREATE COMPONENT
|--------------------------------------------------------------------------
*/

const createComponent = (type) => {

    /*
    | Never create an unknown component type.
    */
    if (!ALLOWED_COMPONENT_TYPES.includes(type)) {
        return null;
    }

    const index =
        getNextComponentIndex();

    switch (type) {

        case 'text':
            return createTextComponent(index);

        case 'image':
            return createImageComponent(index);

        case 'file':
            return createFileComponent(index);

        case 'link':
            return createLinkComponent(index);

        case 'qr_code':
            return createQrComponent(index);

        default:
            return null;
    }
};

/*
|--------------------------------------------------------------------------
| CREATE SAVED COMPONENT
|--------------------------------------------------------------------------
|
| Creates a read-only representation of one previously forwarded
| response component.
|--------------------------------------------------------------------------
*/

const createSavedComponent = (
    component,
    index
) => {
    /*
     * Read the component type from Laravel.
     *
     * The value is checked against our allowed component types
     * before this function is called.
     */
    const type =
        component?.type;

    /*
     * Convert potentially null values into safe strings.
     */
    const content =
        String(component?.content ?? '');

    const label =
        String(component?.label ?? '');

    /*
     * Laravel supplies this URL only for private attachments.
     *
     * We intentionally do NOT use component.content as a URL.
     */
    const attachmentUrl =
        String(
            component?.attachment_url ?? ''
        );


    /*
     * =========================================================
     * TEXT
     * =========================================================
     */

    if (type === 'text') {

        return `
            <div
                class="support-response-component support-response-component-readonly"
                data-component-index="${index}"
                data-component-type="text"
            >

                ${createComponentHeader(
                    'Text',
                    'Official response content.',
                    'ph-text-aa'
                )}

                <div class="support-response-component-body">

                    <div class="support-response-readonly-label">
                        Response
                    </div>

                    <div
                        class="support-response-readonly-content"
                        data-saved-content
                    >${escapeHtml(content)}</div>

                </div>

            </div>
        `;
    }


    /*
     * =========================================================
     * IMAGE
     * =========================================================
     */

    if (type === 'image') {

        /*
         * Only render an image preview when Laravel supplied
         * the authenticated attachment endpoint.
         */
        const hasAttachment =
            isSafeAttachmentUrl(
                attachmentUrl
            );

        return `
            <div
                class="support-response-component support-response-component-readonly"
                data-component-index="${index}"
                data-component-type="image"
            >

                ${createComponentHeader(
                    'Image',
                    'Previously forwarded image.',
                    'ph-image'
                )}

                <div class="support-response-component-body">

                    <div class="support-response-readonly-label">
                        Attachment
                    </div>

                    ${
                        hasAttachment
                            ? `
                                <div
                                    class="support-response-saved-attachment support-response-saved-image"
                                >

                                    <a
                                        class="support-response-saved-image-preview"
                                        href="${escapeHtml(attachmentUrl)}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        aria-label="Open previously forwarded image"
                                    >
                                        <img
                                            src="${escapeHtml(attachmentUrl)}"
                                            alt="Previously forwarded response image"
                                            loading="lazy"
                                        >
                                    </a>

                                    <div class="support-response-saved-attachment-footer">

                                        <div class="support-response-saved-attachment-info">

                                            <i
                                                class="ph-light ph-image"
                                                aria-hidden="true"
                                            ></i>

                                            <span>
                                                Previously forwarded image
                                            </span>

                                        </div>

                                        <a
                                            class="support-response-saved-attachment-action"
                                            href="${escapeHtml(attachmentUrl)}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <i
                                                class="ph-light ph-arrow-square-out"
                                                aria-hidden="true"
                                            ></i>

                                            <span>
                                                View image
                                            </span>
                                        </a>

                                    </div>

                                </div>
                            `
                            : `
                                <div
                                    class="support-response-saved-file support-response-saved-file-missing"
                                >
                                    <i
                                        class="ph-light ph-image"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Image attachment is unavailable.
                                    </span>
                                </div>
                            `
                    }

                </div>

            </div>
        `;
    }


    /*
     * =========================================================
     * FILE
     * =========================================================
     */

    if (type === 'file') {

        const hasAttachment =
            isSafeAttachmentUrl(
                attachmentUrl
            );

        /*
         * We cannot safely display the original filename yet
         * because the current database schema stores only the
         * private storage path.
         *
         * basename() is intentionally NOT performed here because
         * the random storage filename is not a user-facing filename.
         */
        return `
            <div
                class="support-response-component support-response-component-readonly"
                data-component-index="${index}"
                data-component-type="file"
            >

                ${createComponentHeader(
                    'File',
                    'Previously forwarded document.',
                    'ph-file'
                )}

                <div class="support-response-component-body">

                    <div class="support-response-readonly-label">
                        Attachment
                    </div>

                    ${
                        hasAttachment
                            ? `
                                <div
                                    class="support-response-saved-attachment support-response-saved-document"
                                >

                                    <div class="support-response-saved-document-icon">
                                        <i
                                            class="ph-light ph-file-text"
                                            aria-hidden="true"
                                        ></i>
                                    </div>

                                    <div class="support-response-saved-document-content">

                                        <strong>
                                            Previously forwarded document
                                        </strong>

                                        <span>
                                            The original file was securely stored.
                                        </span>

                                    </div>

                                    <a
                                        class="support-response-saved-attachment-action"
                                        href="${escapeHtml(attachmentUrl)}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <i
                                            class="ph-light ph-arrow-square-out"
                                            aria-hidden="true"
                                        ></i>

                                        <span>
                                            Open file
                                        </span>
                                    </a>

                                </div>
                            `
                            : `
                                <div
                                    class="support-response-saved-file support-response-saved-file-missing"
                                >
                                    <i
                                        class="ph-light ph-file-x"
                                        aria-hidden="true"
                                    ></i>

                                    <span>
                                        Document attachment is unavailable.
                                    </span>
                                </div>
                            `
                    }

                </div>

            </div>
        `;
    }


    /*
     * =========================================================
     * LINK
     * =========================================================
     */

    if (type === 'link') {

        /*
         * Do not generate an anchor for an unsafe URL.
         *
         * The backend should already validate this, but the
         * frontend performs defense-in-depth validation.
         */
        const safeLink =
            isSafeUrl(content);

        return `
            <div
                class="support-response-component support-response-component-readonly"
                data-component-index="${index}"
                data-component-type="link"
            >

                ${createComponentHeader(
                    'Link',
                    'Previously forwarded web link.',
                    'ph-link'
                )}

                <div class="support-response-component-body">

                    ${
                        label
                            ? `
                                <div class="support-response-readonly-label">
                                    Link label
                                </div>

                                <div class="support-response-readonly-content">
                                    ${escapeHtml(label)}
                                </div>
                            `
                            : ''
                    }

                    <div class="support-response-readonly-label">
                        URL
                    </div>

                    ${
                        safeLink
                            ? `
                                <a
                                    class="support-response-readonly-link"
                                    href="${escapeHtml(content)}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    ${escapeHtml(content)}

                                    <i
                                        class="ph-light ph-arrow-square-out"
                                        aria-hidden="true"
                                    ></i>
                                </a>
                            `
                            : `
                                <div class="support-response-readonly-content">
                                    ${escapeHtml(content)}
                                </div>
                            `
                    }

                </div>

            </div>
        `;
    }


    /*
     * =========================================================
     * QR CODE
     * =========================================================
     */

    if (type === 'qr_code') {

        const safeQrUrl =
            isSafeUrl(content);

        return `
            <div
                class="support-response-component support-response-component-readonly"
                data-component-index="${index}"
                data-component-type="qr_code"
            >

                ${createComponentHeader(
                    'QR Code',
                    'Previously forwarded QR destination.',
                    'ph-qr-code'
                )}

                <div class="support-response-component-body">

                    ${
                        label
                            ? `
                                <div class="support-response-readonly-label">
                                    QR code label
                                </div>

                                <div class="support-response-readonly-content">
                                    ${escapeHtml(label)}
                                </div>
                            `
                            : ''
                    }

                    ${
                        safeQrUrl
                            ? `
                                <div class="support-response-qr-preview">

                                    <div
                                        class="support-response-qr-placeholder"
                                        data-qr-value="${escapeHtml(content)}"
                                    >
                                        <i
                                            class="ph-light ph-qr-code"
                                            aria-hidden="true"
                                        ></i>

                                        <span>
                                            QR destination
                                        </span>
                                    </div>

                                    <div class="support-response-qr-details">

                                        <div class="support-response-readonly-label">
                                            Destination URL
                                        </div>

                                        <a
                                            class="support-response-readonly-link"
                                            href="${escapeHtml(content)}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            ${escapeHtml(content)}

                                            <i
                                                class="ph-light ph-arrow-square-out"
                                                aria-hidden="true"
                                            ></i>
                                        </a>

                                    </div>

                                </div>
                            `
                            : `
                                <div class="support-response-readonly-content">
                                    ${escapeHtml(content)}
                                </div>
                            `
                    }

                </div>

            </div>
        `;
    }


    return null;
};


/*
|--------------------------------------------------------------------------
| RENDER PREVIOUS RESPONSE HISTORY
|--------------------------------------------------------------------------
|
| Historical responses are displayed separately from the editable
| response builder.
|
| This is important because previously forwarded components must
| never become part of the new FormData submission.
|--------------------------------------------------------------------------
*/

export const renderSavedResponseHistory = (
    response
) => {

    if (
        !latestResponseContainer ||
        !latestResponseComponentsContainer
    ) {
        return;
    }


    /*
     * Start clean so opening another ticket cannot display
     * the previous ticket's response history.
     */
    latestResponseComponentsContainer.replaceChildren();


    /*
     * Make sure the response contains a valid component collection.
     */
    const components =
        Array.isArray(response?.components)
            ? [...response.components]
            : [];


    /*
     * Preserve the official component order.
     */
    components.sort(
        (first, second) =>
            Number(first?.sort_order ?? 0) -
            Number(second?.sort_order ?? 0)
    );


    /*
     * Only render component types that the application recognizes.
     */
    const validComponents =
        components.filter(
            component =>
                ALLOWED_COMPONENT_TYPES.includes(
                    component?.type
                )
        );


    /*
     * Render every previous component as read-only.
     */
    validComponents.forEach(
        component => {

            const index =
                getNextComponentIndex();


            const markup =
                createSavedComponent(
                    component,
                    index
                );


            if (!markup) {
                return;
            }


            latestResponseComponentsContainer.insertAdjacentHTML(
                'beforeend',
                markup
            );
        }
    );


    /*
     * Remove destructive controls from historical components.
     */
    latestResponseComponentsContainer
        .querySelectorAll(
            '[data-action="remove-component"]'
        )
        .forEach(
            button => button.remove()
        );


    /*
     * Only show the history section when there is actually
     * something meaningful to display.
     */
    latestResponseContainer.hidden =
        validComponents.length === 0;
};

/**
 * Render one historical response attempt.
 *
 * This creates a compact, collapsible record.
 * The historical response remains display-only.
 */
export const renderResponseHistoryItem = (response) => {
    if (!historyComponentsContainer) {
        return;
    }

    const components = Array.isArray(response?.components)
        ? [...response.components]
        : [];

    components.sort(
        (first, second) =>
            Number(first?.sort_order ?? 0) -
            Number(second?.sort_order ?? 0)
    );

    const validComponents = components.filter(
        component =>
            ALLOWED_COMPONENT_TYPES.includes(component?.type)
    );

    const wrapper = document.createElement('div');

    wrapper.className = 'support-response-history-item';

    wrapper.dataset.responseId = String(
        response?.id ?? ''
    );

    const header = document.createElement('button');

    header.type = 'button';

    header.className =
        'support-response-history-item-toggle';

    header.setAttribute(
        'aria-expanded',
        'false'
    );

    const adminName =
        response?.admin
            ? [
                response.admin.first_name,
                response.admin.last_name,
            ]
                .filter(Boolean)
                .join(' ')
            : 'Administrator';

    const status =
        String(response?.status ?? '')
            .replaceAll('_', ' ');

    const formattedStatus =
        status
            ? status.charAt(0).toUpperCase() + status.slice(1)
            : 'Response';

    const forwardedAt =
        response?.forwarded_at
            ? new Date(response.forwarded_at)
                .toLocaleString()
            : 'Date unavailable';

    header.innerHTML = `
        <span class="support-response-history-item-main">
            <span class="support-response-history-item-title">
                Official Response #${escapeHtml(response?.id)}
            </span>

            <span class="support-response-history-item-meta">
                ${escapeHtml(formattedStatus)}
                ·
                ${escapeHtml(adminName)}
                ·
                ${escapeHtml(forwardedAt)}
            </span>
        </span>

        <i
            class="ph-light ph-caret-down"
            aria-hidden="true"
        ></i>
    `;

    const content = document.createElement('div');

    content.className =
        'support-response-history-item-content';

    content.hidden = true;

    if (response?.follow_up_reason) {
        const followUp = document.createElement('div');

        followUp.className =
            'support-response-history-follow-up';

        const label = document.createElement('strong');

        label.textContent =
            'Citizen follow-up';

        const reason = document.createElement('p');

        reason.textContent =
            response.follow_up_reason;

        followUp.append(
            label,
            reason
        );

        content.appendChild(followUp);
    }

    const componentContainer =
        document.createElement('div');

    componentContainer.className =
        'support-response-history-components';

    validComponents.forEach(
        (component) => {

            const index =
                getNextComponentIndex();

            const markup =
                createSavedComponent(
                    component,
                    index
                );

            if (!markup) {
                return;
            }

            componentContainer.insertAdjacentHTML(
                'beforeend',
                markup
            );
        }
    );

    componentContainer
        .querySelectorAll(
            '[data-action="remove-component"]'
        )
        .forEach(
            button => button.remove()
        );

    content.appendChild(
        componentContainer
    );

    header.addEventListener(
        'click',
        () => {

            const isExpanded =
                header.getAttribute(
                    'aria-expanded'
                ) === 'true';

            header.setAttribute(
                'aria-expanded',
                isExpanded
                    ? 'false'
                    : 'true'
            );

            content.hidden =
                isExpanded;

            wrapper.classList.toggle(
                'is-expanded',
                !isExpanded
            );
        }
    );

    wrapper.append(
        header,
        content
    );

    historyComponentsContainer.appendChild(
        wrapper
    );
};


/*
|--------------------------------------------------------------------------
| BUILDER READ-ONLY STATE
|--------------------------------------------------------------------------
|
| Existing official responses should be treated as historical records.
| We therefore disable the controls that would modify them.
|--------------------------------------------------------------------------
*/

const setBuilderReadOnly = (
    isReadOnly
) => {

    if (!builder) {
        return;
    }


    /*
     * Disable the Add Response Component control.
     */
    if (addButton) {

        addButton.disabled =
            isReadOnly;

        addButton.setAttribute(
            'aria-disabled',
            isReadOnly
                ? 'true'
                : 'false'
        );
    }


    /*
     * Hide the component-type menu whenever the builder
     * becomes read-only.
     */
    if (
        isReadOnly &&
        componentMenu
    ) {

        closeComponentMenu();
    }


    /*
     * Existing saved components do not have remove buttons
     * because createSavedComponent() uses the same header
     * structure but must not permit modification.
     *
     * We also make sure dynamically created inputs cannot
     * accidentally remain interactive.
     */
    componentsContainer
        ?.querySelectorAll(
            'input, textarea, button'
        )
        .forEach(
            element => {

                element.disabled =
                    isReadOnly;
            }
        );
};

/*
|--------------------------------------------------------------------------
| LOAD SAVED RESPONSE
|--------------------------------------------------------------------------
|
| Reconstructs an already-forwarded Official Response from the data
| returned by Laravel.
|
| IMPORTANT:
| Saved responses are rendered as READ-ONLY.
|
| We intentionally do not place saved image/file paths into <input
| type="file"> because browsers prohibit programmatically assigning
| files to file inputs.
|
| A previously forwarded response is also an audit record, so it
| should not silently become editable.
|--------------------------------------------------------------------------
*/

export const loadSavedResponse = (
    response
) => {

    /*
     * The builder must already be initialized before it can render
     * saved components.
     */
    if (!componentsContainer) {
        return;
    }


    /*
     * Start from a clean builder state.
     *
     * This protects against accidentally displaying components
     * belonging to the previously opened ticket.
     */
    resetResponseBuilder();


    /*
     * Make sure the response contains a component collection.
     */
    const components =
        Array.isArray(response?.components)
            ? [...response.components]
            : [];


    /*
     * The database already stores sort_order, but sorting again
     * here guarantees the frontend preserves the official response
     * order regardless of the order returned by the API.
     */
    components.sort(
        (first, second) =>
            Number(first?.sort_order ?? 0) -
            Number(second?.sort_order ?? 0)
    );


    /*
     * Ignore invalid or unsupported component types.
     *
     * The backend is still the authoritative security boundary.
     * This is simply defensive handling on the client.
     */
    const validComponents =
        components.filter(
            component =>
                ALLOWED_COMPONENT_TYPES.includes(
                    component?.type
                )
        );


    /*
     * Rebuild every saved component as a read-only card.
     */
    validComponents.forEach(
        component => {

            const index =
                getNextComponentIndex();


            const markup =
                createSavedComponent(
                    component,
                    index
                );


            if (!markup) {
                return;
            }


            componentsContainer.insertAdjacentHTML(
                'beforeend',
                markup
            );
        }
    );


    /*
    * Saved components are historical records.
    * Remove destructive controls from their headers.
    */
    componentsContainer
        .querySelectorAll(
            '.support-response-component-readonly [data-action="remove-component"]'
        )
        .forEach(
            button => button.remove()
        );


    refreshEmptyState();

    setBuilderReadOnly(
        validComponents.length > 0
    );
};


/*
|--------------------------------------------------------------------------
| ADD COMPONENT
|--------------------------------------------------------------------------
*/

const addComponent = (type) => {

    /*
    | Make sure the builder has been initialized.
    */
    if (!componentsContainer) {
        return;
    }


    /*
    | Prevent the UI from exceeding the backend component limit.
    */
    if (getComponentCount() >= MAX_COMPONENTS) {

        window.alert(
            `A response can contain a maximum of ${MAX_COMPONENTS} components.`
        );

        return;
    }


    const markup =
        createComponent(type);

    if (!markup) {
        return;
    }


    componentsContainer.insertAdjacentHTML(
        'beforeend',
        markup
    );


    refreshEmptyState();


    const component =
        componentsContainer.lastElementChild;


    component?.scrollIntoView({
        behavior: 'smooth',
        block: 'nearest',
    });


    closeComponentMenu();
};


/*
|--------------------------------------------------------------------------
| REMOVE COMPONENT
|--------------------------------------------------------------------------
*/

const removeComponent = (button) => {

    const component =
        button.closest(
            '.support-response-component'
        );


    if (!component) {
        return;
    }


    /*
    | Removing the component also removes its form inputs.
    |
    | Therefore those values will not be included in FormData.
    */
    component.remove();


    refreshEmptyState();
};


/*
|--------------------------------------------------------------------------
| COMPONENT MENU
|--------------------------------------------------------------------------
*/

const openComponentMenu = () => {

    if (!componentMenu || !addButton) {
        return;
    }


    componentMenu.hidden = false;


    addButton.setAttribute(
        'aria-expanded',
        'true'
    );
};


const closeComponentMenu = () => {

    if (!componentMenu || !addButton) {
        return;
    }


    componentMenu.hidden = true;


    addButton.setAttribute(
        'aria-expanded',
        'false'
    );
};


const toggleComponentMenu = () => {

    if (!componentMenu) {
        return;
    }


    if (componentMenu.hidden) {
        openComponentMenu();
    } else {
        closeComponentMenu();
    }
};


/*
|--------------------------------------------------------------------------
| CHARACTER COUNTER
|--------------------------------------------------------------------------
*/

const updateCharacterCount = (textarea) => {

    const component =
        textarea.closest(
            '.support-response-component'
        );


    if (!component) {
        return;
    }


    const counter =
        component.querySelector(
            '[data-character-count]'
        );


    if (!counter) {
        return;
    }


    counter.textContent =
        textarea.value.length;
};


/*
|--------------------------------------------------------------------------
| FILE VALIDATION
|--------------------------------------------------------------------------
*/

const validateFile = (input) => {

    const component =
        input.closest(
            '.support-response-component'
        );


    if (!component) {
        return false;
    }


    const type =
        component.dataset.componentType;


    const file =
        input.files?.[0];


    /*
    | A file is required for both image and file components.
    */
    if (!file) {
        return false;
    }


    const MAX_FILE_SIZE =
        5 * 1024 * 1024;


    const allowedTypes =
        ALLOWED_FILE_TYPES[type] ?? [];


    /*
    |--------------------------------------------------------------------------
    | FILE SIZE
    |--------------------------------------------------------------------------
    */

    if (file.size > MAX_FILE_SIZE) {

        window.alert(
            'The selected file is larger than 5 MB.'
        );


        input.value = '';


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | FILE MIME TYPE
    |--------------------------------------------------------------------------
    |
    | This checks the browser-reported MIME type.
    |
    | It is only an early UX check.
    | Laravel must independently validate the uploaded file.
    |--------------------------------------------------------------------------
    */

    if (!allowedTypes.includes(file.type)) {

        window.alert(
            'The selected file type is not allowed.'
        );


        input.value = '';


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | DISPLAY SELECTED FILE
    |--------------------------------------------------------------------------
    */

    const meta =
        component.querySelector(
            '[data-file-meta]'
        );


    if (meta) {

        meta.textContent =
            `${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB`;
    }


    return true;
};


/*
|--------------------------------------------------------------------------
| URL VALIDATION
|--------------------------------------------------------------------------
*/

const validateUrl = (input) => {

    const component =
        input.closest(
            '.support-response-component'
        );


    if (!component) {
        return false;
    }


    const error =
        component.querySelector(
            '[data-url-error]'
        );


    const value =
        input.value.trim();


    /*
    | URL fields are required.
    */
    if (
        !value ||
        !isSafeUrl(value)
    ) {

        input.setCustomValidity(
            'Please enter a valid HTTP or HTTPS URL.'
        );


        if (error) {
            error.hidden = false;
        }


        return false;
    }


    /*
    | Clear the browser's custom validation state.
    */
    input.setCustomValidity('');


    if (error) {
        error.hidden = true;
    }


    return true;
};


/*
|--------------------------------------------------------------------------
| RESPONSE BUILDER VALIDATION
|--------------------------------------------------------------------------
|
| This validates the complete builder without submitting the form.
|
| The Manage Modal calls this before making the HTTP request.
|--------------------------------------------------------------------------
*/

export const validateResponseBuilder = () => {

    if (!componentsContainer) {
        return {
            valid: false,
            message:
                'The response builder is not available.',
        };
    }


    const components =
        componentsContainer.querySelectorAll(
            '.support-response-component'
        );


    /*
    |--------------------------------------------------------------------------
    | REQUIRE AT LEAST ONE COMPONENT
    |--------------------------------------------------------------------------
    */

    if (components.length === 0) {

        return {
            valid: false,
            message:
                'Please add at least one component to the official response.',
        };
    }


    let valid = true;

    let message =
        'Please review the official response components.';


    /*
    |--------------------------------------------------------------------------
    | VALIDATE EVERY COMPONENT
    |--------------------------------------------------------------------------
    */

    components.forEach((component) => {

        const type =
            component.dataset.componentType;


        /*
        |--------------------------------------------------------------------------
        | TEXT
        |--------------------------------------------------------------------------
        */

        if (type === 'text') {

            const textarea =
                component.querySelector(
                    'textarea[data-field="content"]'
                );


            if (
                !textarea ||
                textarea.value.trim() === ''
            ) {

                valid = false;

                message =
                    'Text response components cannot be empty.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | LINK / QR CODE
        |--------------------------------------------------------------------------
        */

        if (
            type === 'link' ||
            type === 'qr_code'
        ) {

            const urlInput =
                component.querySelector(
                    'input[type="url"][data-field="content"]'
                );


            if (
                !urlInput ||
                !validateUrl(urlInput)
            ) {

                valid = false;

                message =
                    'Please enter a valid HTTP or HTTPS URL.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE / FILE
        |--------------------------------------------------------------------------
        */

        if (
            type === 'image' ||
            type === 'file'
        ) {

            const fileInput =
                component.querySelector(
                    '.support-response-file-input'
                );


            if (
                !fileInput ||
                !validateFile(fileInput)
            ) {

                valid = false;

                message =
                    'Please select a valid file for the response component.';
            }
        }
    });


    return {
        valid,
        message,
    };
};


/*
|--------------------------------------------------------------------------
| RESET BUILDER
|--------------------------------------------------------------------------
|
| Called whenever a different ticket is opened or the modal is cancelled.
|--------------------------------------------------------------------------
*/

export const resetResponseBuilder = () => {

    /*
    | If initialization has not happened yet, there is nothing to reset.
    */
    if (!componentsContainer) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE ALL COMPONENTS
    |--------------------------------------------------------------------------
    */

    componentsContainer.replaceChildren();

    if (latestResponseComponentsContainer) {
        latestResponseComponentsContainer.replaceChildren();
    }

    if (latestResponseContainer) {
        latestResponseContainer.hidden = true;
    }

    if (historyComponentsContainer) {
        historyComponentsContainer.replaceChildren();
    }

    if (historyContainer) {
        historyContainer.hidden = true;
    }


    /*
    |--------------------------------------------------------------------------
    | RESET INDEX
    |--------------------------------------------------------------------------
    |
    | The next response starts again at components[0].
    |--------------------------------------------------------------------------
    */

    componentCounter = 0;


    /*
    |--------------------------------------------------------------------------
    | CLOSE COMPONENT MENU
    |--------------------------------------------------------------------------
    */

    closeComponentMenu();


    /*
    |--------------------------------------------------------------------------
    | RESTORE EMPTY STATE
    |--------------------------------------------------------------------------
    */

    refreshEmptyState();

    /*
 * Reset the builder back to editable mode.
 *
 * The next ticket may be a new response attempt. 
 */
setBuilderReadOnly(false);
};


/*
|--------------------------------------------------------------------------
| INITIALIZE RESPONSE BUILDER
|--------------------------------------------------------------------------
|
| This function is called by support-requests/index.js.
|
| The builder is initialized explicitly by the feature entry point.
| No DOMContentLoaded listener is required here.
|--------------------------------------------------------------------------
*/

export function initializeResponseBuilder() {

    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE INITIALIZATION
    |--------------------------------------------------------------------------
    */

    if (isInitialized) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | DOM REFERENCES
    |--------------------------------------------------------------------------
    */

    builder =
        document.getElementById(
            'support-response-builder'
        );

    componentsContainer =
        document.getElementById(
            'support-response-components'
        );

    latestResponseContainer =
        document.getElementById(
            'support-response-latest'
        );

    latestResponseComponentsContainer =
        document.getElementById(
            'support-response-latest-components'
        );
        
    historyContainer =
        document.getElementById(
            'support-response-history'
        );

    historyComponentsContainer =
        document.getElementById(
            'support-response-history-components'
        );

    emptyState =
        document.getElementById(
            'support-response-empty'
        );

    addButton =
        document.getElementById(
            'support-add-component'
        );

    componentMenu =
        document.getElementById(
            'support-component-menu'
        );


    /*
    |--------------------------------------------------------------------------
    | REQUIRED DOM VALIDATION
    |--------------------------------------------------------------------------
    |
    | Every element below is required for the builder to operate.
    |
    | If one is missing, stop initialization safely.
    |--------------------------------------------------------------------------
    */

    if (
        !builder ||
        !componentsContainer ||
        !emptyState ||
        !addButton ||
        !componentMenu
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | COMPONENT REMOVE
    |--------------------------------------------------------------------------
    |
    | Components are dynamically generated, so event delegation allows
    | this listener to work for components created later as well.
    |--------------------------------------------------------------------------
    */

    componentsContainer.addEventListener(
        'click',
        (event) => {

            const removeButton =
                event.target.closest(
                    '[data-action="remove-component"]'
                );


            if (!removeButton) {
                return;
            }


            event.preventDefault();

            removeComponent(
                removeButton
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | TEXT INPUT
    |--------------------------------------------------------------------------
    */

    componentsContainer.addEventListener(
        'input',
        (event) => {

            const textarea =
                event.target.closest(
                    'textarea[data-field="content"]'
                );


            if (!textarea) {
                return;
            }


            const component =
                textarea.closest(
                    '[data-component-type="text"]'
                );


            if (!component) {
                return;
            }


            updateCharacterCount(
                textarea
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | FILE / URL CHANGE
    |--------------------------------------------------------------------------
    */

    componentsContainer.addEventListener(
        'change',
        (event) => {

            const input =
                event.target;


            /*
            |--------------------------------------------------------------------------
            | FILE
            |--------------------------------------------------------------------------
            */

            if (
                input.matches(
                    '.support-response-file-input'
                )
            ) {

                validateFile(
                    input
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | URL
            |--------------------------------------------------------------------------
            */

            if (
                input.matches(
                    'input[type="url"][data-field="content"]'
                )
            ) {

                validateUrl(
                    input
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | URL INPUT
    |--------------------------------------------------------------------------
    */

    componentsContainer.addEventListener(
        'input',
        (event) => {

            const input =
                event.target;


            if (
                input.matches(
                    'input[type="url"][data-field="content"]'
                )
            ) {

                validateUrl(
                    input
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ADD RESPONSE COMPONENT
    |--------------------------------------------------------------------------
    |
    | Use a direct listener because this button itself is static.
    |--------------------------------------------------------------------------
    */

    addButton.addEventListener(
        'click',
        (event) => {

            event.preventDefault();

            event.stopPropagation();

            toggleComponentMenu();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | COMPONENT TYPE SELECTION
    |--------------------------------------------------------------------------
    |
    | The individual component buttons are static, but delegation keeps
    | this logic resilient if the menu is later rendered dynamically.
    |--------------------------------------------------------------------------
    */

    componentMenu.addEventListener(
        'click',
        (event) => {

            const option =
                event.target.closest(
                    '[data-component-type]'
                );


            if (!option) {
                return;
            }


            event.preventDefault();

            event.stopPropagation();


            const type =
                option.dataset.componentType;


            addComponent(
                type
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLOSE MENU WHEN CLICKING OUTSIDE
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        (event) => {

            if (
                !builder.contains(
                    event.target
                )
            ) {

                closeComponentMenu();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        (event) => {

            if (
                event.key === 'Escape' &&
                !componentMenu.hidden
            ) {

                closeComponentMenu();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | INITIAL STATE
    |--------------------------------------------------------------------------
    */

    refreshEmptyState();


    /*
    |--------------------------------------------------------------------------
    | MARK INITIALIZED
    |--------------------------------------------------------------------------
    */

    isInitialized = true;
}