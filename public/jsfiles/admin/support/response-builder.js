document.addEventListener('DOMContentLoaded', () => {
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
    | The page coordinator is responsible for the actual HTTP request.
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
    */

    const builder = document.getElementById(
        'support-response-builder'
    );

    /*
    | The JavaScript file may be loaded on pages that do not contain
    | the response builder.
    |
    | Returning here keeps the script safe to load globally.
    */
    if (!builder) {
        return;
    }


    const componentsContainer = document.getElementById(
        'support-response-components'
    );

    const emptyState = document.getElementById(
        'support-response-empty'
    );

    const addButton = document.getElementById(
        'support-add-component'
    );

    const componentMenu = document.getElementById(
        'support-component-menu'
    );


    /*
    |--------------------------------------------------------------------------
    | REQUIRED DOM VALIDATION
    |--------------------------------------------------------------------------
    |
    | Fail safely if the expected builder markup is incomplete.
    |--------------------------------------------------------------------------
    */

    if (
        !componentsContainer ||
        !emptyState ||
        !addButton ||
        !componentMenu
    ) {
        return;
    }


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
    | This protects the generated component markup from HTML injection.
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

    const getComponentCount = () => {
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
    | ADD COMPONENT
    |--------------------------------------------------------------------------
    */

    const addComponent = (type) => {

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
        componentMenu.hidden = false;

        addButton.setAttribute(
            'aria-expanded',
            'true'
        );
    };


    const closeComponentMenu = () => {
        componentMenu.hidden = true;

        addButton.setAttribute(
            'aria-expanded',
            'false'
        );
    };


    const toggleComponentMenu = () => {
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
        | Check file size.
        */
        if (file.size > MAX_FILE_SIZE) {

            window.alert(
                'The selected file is larger than 5 MB.'
            );

            input.value = '';

            return false;
        }


        /*
        | Check the browser-reported MIME type.
        |
        | This is only an early UX check.
        | The server must independently inspect and validate the upload.
        */
        if (!allowedTypes.includes(file.type)) {

            window.alert(
                'The selected file type is not allowed.'
            );

            input.value = '';

            return false;
        }


        /*
        | Display the selected file information.
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
    | This method validates the complete builder without submitting the form.
    |
    | The page coordinator calls this before making the HTTP request.
    |--------------------------------------------------------------------------
    */

    const validateResponseBuilder = () => {

        const components =
            componentsContainer.querySelectorAll(
                '.support-response-component'
            );


        /*
        | At least one component is required.
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
        | Validate every dynamically-created component.
        */
        components.forEach((component) => {

            const type =
                component.dataset.componentType;


            /*
            | Text components must contain actual text.
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
            | Link and QR Code components must contain
            | safe HTTP/HTTPS URLs.
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
            | Image and file components must contain
            | an acceptable upload.
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

    const resetResponseBuilder = () => {

        /*
        | Remove all dynamically-created components.
        */
        componentsContainer.replaceChildren();


        /*
        | Reset indexes so the next response starts from zero.
        */
        componentCounter = 0;


        /*
        | Close the component menu.
        */
        closeComponentMenu();


        /*
        | Restore the initial empty state.
        */
        refreshEmptyState();
    };


    /*
    |--------------------------------------------------------------------------
    | EVENT DELEGATION — REMOVE COMPONENT
    |--------------------------------------------------------------------------
    |
    | Components are dynamically created.
    |
    | Event delegation lets us use one listener for all current
    | and future components.
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

            removeComponent(removeButton);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | EVENT DELEGATION — TEXT INPUT
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

            updateCharacterCount(textarea);
        }
    );


    /*
    |--------------------------------------------------------------------------
    | EVENT DELEGATION — FILE CHANGE
    |--------------------------------------------------------------------------
    */

    componentsContainer.addEventListener(
        'change',
        (event) => {

            const input =
                event.target;


            if (
                input.matches(
                    '.support-response-file-input'
                )
            ) {

                validateFile(input);

                return;
            }


            if (
                input.matches(
                    'input[type="url"][data-field="content"]'
                )
            ) {

                validateUrl(input);
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | EVENT DELEGATION — URL INPUT
    |--------------------------------------------------------------------------
    |
    | Validate while the user types so invalid URLs are detected early.
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

                validateUrl(input);
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ADD RESPONSE BUTTON
    |--------------------------------------------------------------------------
    */

    addButton.addEventListener(
        'click',
        (event) => {

            /*
            | Prevent the document-level outside-click listener
            | from immediately closing the menu.
            */
            event.stopPropagation();

            toggleComponentMenu();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESPONSE COMPONENT OPTIONS
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

            const type =
                option.dataset.componentType;

            addComponent(type);
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

            if (!builder.contains(event.target)) {
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
    | PUBLIC BUILDER API
    |--------------------------------------------------------------------------
    |
    | The page coordinator interacts with the builder through this small API.
    |
    | This keeps the builder's internal state private.
    |--------------------------------------------------------------------------
    */

    window.SupportResponseBuilder = {

        /*
        | Remove all components and restore the initial state.
        */
        reset: resetResponseBuilder,


        /*
        | Return the number of currently-added components.
        */
        getComponentCount: () => {
            return getComponentCount();
        },


        /*
        | Validate the current response without submitting it.
        */
        validate: validateResponseBuilder,
    };


    /*
    |--------------------------------------------------------------------------
    | INITIAL STATE
    |--------------------------------------------------------------------------
    */

    refreshEmptyState();
});