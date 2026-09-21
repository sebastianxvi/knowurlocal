/*
|--------------------------------------------------------------------------
| KNOWURLOCAL — Support Request Manage Modal
|--------------------------------------------------------------------------
|
| This module owns the Support Request management modal.
|
| Responsibilities:
| - Open the selected Support Request
| - Populate request information
| - Manage agency selection
| - Coordinate the Response Builder
| - Submit the official response
| - Handle submission state
| - Close/reset the modal
|
| This module does NOT build response components.
| That responsibility belongs to response-builder.js.
|
| SECURITY:
| - CSRF token is read from the page.
| - FormData is submitted same-origin.
| - Client-side validation is UX only.
| - Laravel remains the authoritative validator.
|--------------------------------------------------------------------------
*/

import {
    resetResponseBuilder,
    validateResponseBuilder,
    loadSavedResponse,
} from './response-builder.js';


/*
|--------------------------------------------------------------------------
| DOM REFERENCES
|--------------------------------------------------------------------------
*/

let supportModal = null;

let replyForm = null;

let methodInput = null;

let saveButton = null;


/*
|--------------------------------------------------------------------------
| AGENCY SELECTOR REFERENCES
|--------------------------------------------------------------------------
*/

let agencySelect = null;

let agencySearchInput = null;

let agencyOptionsContainer = null;

let agencySelector = null;


/*
|--------------------------------------------------------------------------
| MODULE STATE
|--------------------------------------------------------------------------
*/

let currentRequestId = null;

let searchableAgencies = [];

let agencyDropdownOpen = false;

let isInitialized = false;


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
|
| Laravel's CSRF token is read from the page rather than hard-coded.
|--------------------------------------------------------------------------
*/

const csrfToken =
    document
        .querySelector(
            'meta[name="csrf-token"]'
        )
        ?.getAttribute('content') || '';


/*
|--------------------------------------------------------------------------
| SHARED MODAL STATE
|--------------------------------------------------------------------------
*/

const setModalState = (
    modal,
    isOpen
) => {

    if (!modal) {
        return;
    }


    modal.classList.toggle(
        'active',
        isOpen
    );


    /*
    | Keep the accessibility state synchronized
    | with the visual state.
    */
    modal.setAttribute(
        'aria-hidden',
        isOpen
            ? 'false'
            : 'true'
    );
};


/*
|--------------------------------------------------------------------------
| CLIENT MESSAGE
|--------------------------------------------------------------------------
|
| Uses KNOWURLOCAL's existing alert modal when available.
|--------------------------------------------------------------------------
*/

const showClientMessage = (
    title,
    text
) => {

    if (
        typeof window.showAlertModal ===
        'function'
    ) {

        window.showAlertModal({

            title,

            text,

            icon:
                'ph-light ph-warning-circle',

            variant:
                'danger',

            confirmText:
                'OK',

            showCancel:
                false,

            onConfirm: () => {

                if (
                    typeof window.closeAlertModal ===
                    'function'
                ) {
                    window.closeAlertModal();
                }
            },
        });


        return;
    }


    /*
    | Fallback if the shared alert system
    | is not available.
    */
    window.alert(text);
};


/*
|--------------------------------------------------------------------------
| AGENCY DATA
|--------------------------------------------------------------------------
|
| The native <select> remains the actual form field.
|
| The custom searchable UI only controls that select.
|--------------------------------------------------------------------------
*/

const buildAgencyData = () => {

    if (
        !agencySelect ||
        !agencyOptionsContainer
    ) {
        return;
    }


    searchableAgencies =
        Array.from(
            agencySelect.options
        )
            .filter(
                option =>
                    option.value !== ''
            )
            .map(option => {

                const fullName =
                    option.dataset.fullName ||
                    option.textContent.trim();


                const abbreviation =
                    option.dataset.abbr ||
                    '';


                return {

                    value:
                        option.value,

                    fullName:
                        fullName.trim(),

                    abbreviation:
                        abbreviation.trim(),

                    searchText:
                        `${fullName} ${abbreviation}`
                            .toLowerCase()
                            .trim(),
                };
            });
};


/*
|--------------------------------------------------------------------------
| RENDER AGENCY OPTIONS
|--------------------------------------------------------------------------
|
| textContent is deliberately used instead of innerHTML.
|
| Agency names originate from database data and therefore should
| never be treated as trusted HTML.
|--------------------------------------------------------------------------
*/

const renderAgencyOptions = (
    searchTerm = ''
) => {

    if (!agencyOptionsContainer) {
        return;
    }


    const normalizedSearch =
        searchTerm
            .toLowerCase()
            .trim();


    const matches =
        searchableAgencies.filter(
            agency =>
                agency.searchText.includes(
                    normalizedSearch
                )
        );


    /*
    | Clear previous results safely.
    */
    agencyOptionsContainer.replaceChildren();


    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    if (matches.length === 0) {

        const emptyState =
            document.createElement(
                'div'
            );


        emptyState.className =
            'searchable-select-empty';


        emptyState.textContent =
            'No matching agencies found.';


        agencyOptionsContainer.appendChild(
            emptyState
        );


        return;
    }


    /*
    |--------------------------------------------------------------------------
    | OPTIONS
    |--------------------------------------------------------------------------
    */

    matches.forEach(
        agency => {

            const option =
                document.createElement(
                    'button'
                );


            option.type =
                'button';


            option.className =
                'searchable-select-option';


            option.setAttribute(
                'role',
                'option'
            );


            option.setAttribute(
                'aria-selected',
                agencySelect?.value ===
                    agency.value
                    ? 'true'
                    : 'false'
            );


            option.dataset.value =
                agency.value;


            /*
            | textContent prevents database content
            | from being interpreted as HTML.
            */
            option.textContent =
                agency.fullName;


            if (
                agency.abbreviation
            ) {

                option.textContent +=
                    ` (${agency.abbreviation.toUpperCase()})`;
            }


            if (
                agencySelect &&
                agencySelect.value ===
                    agency.value
            ) {

                option.classList.add(
                    'is-selected'
                );
            }


            option.addEventListener(
                'click',
                () => {

                    selectAgency(
                        agency.value
                    );
                }
            );


            agencyOptionsContainer.appendChild(
                option
            );
        }
    );
};


/*
|--------------------------------------------------------------------------
| OPEN AGENCY DROPDOWN
|--------------------------------------------------------------------------
*/

const openAgencyDropdown = () => {

    if (
        !agencySearchInput ||
        !agencySelector
    ) {
        return;
    }


    if (
        agencySearchInput.readOnly ||
        agencySearchInput.disabled
    ) {
        return;
    }


    agencyDropdownOpen =
        true;


    agencySelector.classList.add(
        'is-open'
    );


    agencySearchInput.setAttribute(
        'aria-expanded',
        'true'
    );


    renderAgencyOptions(
        agencySearchInput.value
    );
};


/*
|--------------------------------------------------------------------------
| CLOSE AGENCY DROPDOWN
|--------------------------------------------------------------------------
*/

const closeAgencyDropdown = () => {

    agencyDropdownOpen =
        false;


    agencySelector?.classList.remove(
        'is-open'
    );


    agencySearchInput?.setAttribute(
        'aria-expanded',
        'false'
    );
};


/*
|--------------------------------------------------------------------------
| SELECT AGENCY
|--------------------------------------------------------------------------
|
| The native select remains the authoritative form value.
|--------------------------------------------------------------------------
*/

const selectAgency = (
    agencyValue
) => {

    if (
        !agencySelect ||
        !agencySearchInput
    ) {
        return;
    }


    const selectedOption =
        Array.from(
            agencySelect.options
        ).find(
            option =>
                option.value ===
                String(agencyValue)
        );


    /*
    |--------------------------------------------------------------------------
    | INVALID AGENCY
    |--------------------------------------------------------------------------
    */

    if (!selectedOption) {

        agencySelect.value =
            '';

        agencySearchInput.value =
            '';

        agencySearchInput.setCustomValidity(
            'Please select an agency from the list.'
        );

        closeAgencyDropdown();

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | SYNCHRONIZE NATIVE SELECT
    |--------------------------------------------------------------------------
    */

    agencySelect.value =
        selectedOption.value;


    /*
    |--------------------------------------------------------------------------
    | SYNCHRONIZE SEARCH FIELD
    |--------------------------------------------------------------------------
    */

    agencySearchInput.value =
        selectedOption.dataset.fullName ||
        selectedOption.textContent.trim();


    agencySearchInput.setCustomValidity(
        ''
    );


    closeAgencyDropdown();


    /*
    | Notify other code that the native agency
    | value changed.
    */
    agencySelect.dispatchEvent(
        new Event(
            'change',
            {
                bubbles: true,
            }
        )
    );
};


/*
|--------------------------------------------------------------------------
| AGENCY SEARCH INPUT
|--------------------------------------------------------------------------
*/

const initializeAgencySelector = () => {

    if (
        !agencySearchInput
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | FOCUS
    |--------------------------------------------------------------------------
    */

    agencySearchInput.addEventListener(
        'focus',
        openAgencyDropdown
    );


    /*
    |--------------------------------------------------------------------------
    | CLICK
    |--------------------------------------------------------------------------
    */

    agencySearchInput.addEventListener(
        'click',
        openAgencyDropdown
    );


    /*
    |--------------------------------------------------------------------------
    | INPUT
    |--------------------------------------------------------------------------
    */

    agencySearchInput.addEventListener(
        'input',
        () => {

            /*
            | Typing means the previously selected native
            | value is no longer guaranteed to match.
            */
            if (agencySelect) {
                agencySelect.value =
                    '';
            }


            agencySearchInput.setCustomValidity(
                'Please select an agency from the list.'
            );


            openAgencyDropdown();


            renderAgencyOptions(
                agencySearchInput.value
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | KEYBOARD
    |--------------------------------------------------------------------------
    */

    agencySearchInput.addEventListener(
        'keydown',
        event => {

            /*
            | Escape closes the dropdown.
            */
            if (
                event.key ===
                'Escape'
            ) {

                closeAgencyDropdown();

                return;
            }


            /*
            | Enter selects the first visible result.
            */
            if (
                event.key ===
                    'Enter' &&
                agencyDropdownOpen
            ) {

                const firstOption =
                    agencyOptionsContainer?.querySelector(
                        '.searchable-select-option'
                    );


                if (firstOption) {

                    event.preventDefault();


                    selectAgency(
                        firstOption.dataset.value
                    );
                }
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | OUTSIDE CLICK
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        event => {

            if (!agencySelector) {
                return;
            }


            if (
                !agencySelector.contains(
                    event.target
                )
            ) {

                closeAgencyDropdown();
            }
        }
    );
};


/*
|--------------------------------------------------------------------------
| RESET SUBMIT BUTTON
|--------------------------------------------------------------------------
|
| Restores the normal "Forward to Citizen" state.
|
| This is used whenever the modal is reset or when a ticket is
| eligible for a new official response.
|--------------------------------------------------------------------------
*/

const resetSubmitButton = () => {

    if (!saveButton) {
        return;
    }


    /*
     * Re-enable the button.
     */
    saveButton.disabled =
        false;


    /*
     * Clear the submission lock.
     *
     * This is separate from the workflow status.
     */
    saveButton.dataset.submitting =
        'false';


    /*
     * Restore the normal button label.
     */
    const label =
        saveButton.querySelector(
            '[data-submit-label]'
        );


    if (label) {

        label.textContent =
            'Forward to Citizen';
    }


    /*
     * Remove the workflow-state marker.
     */
    saveButton.removeAttribute(
        'data-workflow-locked'
    );
};

/*
|--------------------------------------------------------------------------
| APPLY WORKFLOW SUBMIT STATE
|--------------------------------------------------------------------------
|
| Controls whether the administrator can create another official
| response for the current ticket.
|
| IMPORTANT:
| This is UX protection only.
|
| The Laravel controller also enforces the same rule server-side.
|--------------------------------------------------------------------------
*/

const applyWorkflowSubmitState = (
    status
) => {

    if (!saveButton) {
        return;
    }


    /*
     * These states mean the ticket is not currently accepting
     * another official response.
     */
    const lockedStatuses = [
        'awaiting_confirmation',
        'answered',
    ];


    const isLocked =
        lockedStatuses.includes(
            String(status || '')
        );


    /*
     * Always start from the normal state.
     */
    resetSubmitButton();


    if (!isLocked) {
        return;
    }


    /*
     * Disable the actual submit control.
     *
     * This prevents normal user interaction.
     */
    saveButton.disabled =
        true;


    /*
     * Mark the button as workflow-locked.
     *
     * This gives us a clear state we can inspect during
     * debugging without relying only on disabled=true.
     */
    saveButton.dataset.workflowLocked =
        'true';


    /*
     * Use a status-specific label so the administrator knows
     * WHY forwarding is unavailable.
     */
    const label =
        saveButton.querySelector(
            '[data-submit-label]'
        );


    if (!label) {
        return;
    }


    if (
        status ===
        'awaiting_confirmation'
    ) {

        label.textContent =
            'Awaiting Citizen Confirmation';

        return;
    }


    if (
        status ===
        'answered'
    ) {

        label.textContent =
            'Response Already Answered';
    }
};


/*
|--------------------------------------------------------------------------
| LOCK SUBMIT BUTTON
|--------------------------------------------------------------------------
*/

const lockSubmitButton = () => {

    if (!saveButton) {
        return;
    }


    saveButton.dataset.submitting =
        'true';


    saveButton.disabled =
        true;


    const label =
        saveButton.querySelector(
            '[data-submit-label]'
        );


    if (label) {

        label.textContent =
            'Forwarding...';
    }
};


/*
|--------------------------------------------------------------------------
| LOAD LATEST OFFICIAL RESPONSE
|--------------------------------------------------------------------------
|
| Retrieves the latest response already saved for the selected
| Support Request.
|
| The Manage Modal owns the HTTP request because it knows which
| Support Request is currently being managed.
|
| The Response Builder owns the rendering because it knows how
| to construct Text, Image, File, Link, and QR Code components.
|--------------------------------------------------------------------------
*/

const loadLatestResponse = async (
    requestId
) => {

    /*
     * A request ID is required before we can query Laravel.
     */
    if (!requestId) {
        return null;
    }


    const template =
    replyForm?.dataset.latestResponseUrl;

if (!template) {
    throw new Error(
        'The latest response endpoint is not configured.'
    );
}

const responseUrl =
    template.replace(
        '__ID__',
        encodeURIComponent(requestId)
    );

    /*
     * Ask Laravel for the latest saved response.
     *
     * GET is intentionally used because this operation is
     * read-only.
     */
    const response = await fetch(
        responseUrl,
        {
            method: 'GET',

            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },

            credentials: 'same-origin',
        }
    );


    /*
     * Parse the server response.
     */
    let data = null;

    try {

        data = await response.json();

    } catch {

        throw new Error(
            'The server returned an invalid response.'
        );
    }


    /*
     * Reject failed HTTP responses and unsuccessful
     * application responses.
     */
    if (
        !response.ok ||
        !data?.success
    ) {

        throw new Error(
            data?.message ||
            'The existing response could not be loaded.'
        );
    }


    /*
     * Return the response object to the caller.
     *
     * A null response is valid for a pending ticket.
     */
    return data.response || null;
};

/*
|--------------------------------------------------------------------------
| PREPARE SUPPORT REQUEST
|--------------------------------------------------------------------------
|
| Called when an administrator clicks Manage on a Support Request.
|
| The modal first loads the ticket information and then retrieves
| the latest saved official response, if one exists.
|--------------------------------------------------------------------------
*/

export const prepareSupportRequest = async (
    button
) => {

    if (
        !supportModal ||
        !replyForm ||
        !button
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | CURRENT REQUEST
    |--------------------------------------------------------------------------
    */

    currentRequestId =
        button.dataset.id ||
        null;


    if (!currentRequestId) {

        showClientMessage(
            'Unable to open request',
            'The selected support request could not be identified.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | RESET RESPONSE BUILDER
    |--------------------------------------------------------------------------
    |
    | Start clean because the modal can be reused for different tickets.
    |
    | We will then load the saved response, if this ticket already
    | has one.
    |--------------------------------------------------------------------------
    */

    resetResponseBuilder();


    /*
    |--------------------------------------------------------------------------
    | REQUEST INFORMATION
    |--------------------------------------------------------------------------
    */

    const userInput =
        document.getElementById(
            'sr-user'
        );


    const questionInput =
        document.getElementById(
            'sr-question'
        );


    const requestIdInput =
        document.getElementById(
            'sr-id'
        );


    /*
    |--------------------------------------------------------------------------
    | POPULATE REQUEST INFORMATION
    |--------------------------------------------------------------------------
    */

    if (requestIdInput) {

        requestIdInput.value =
            currentRequestId;
    }


    if (userInput) {

        userInput.value =
            button.dataset.user ||
            'Guest';
    }


    if (questionInput) {

        questionInput.value =
            button.dataset.question ||
            '';
    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSE ENDPOINT
    |--------------------------------------------------------------------------
    */

    if (
        replyForm.dataset.forwardUrl
    ) {

        replyForm.action =
            replyForm.dataset.forwardUrl;
    }


    if (methodInput) {

        methodInput.value =
            'POST';
    }


    /*
    |--------------------------------------------------------------------------
    | APPLY WORKFLOW SUBMIT STATE
    |--------------------------------------------------------------------------
    */

    applyWorkflowSubmitState(
        button.dataset.status || ''
    );


    /*
    |--------------------------------------------------------------------------
    | SELECT ASSIGNED AGENCY
    |--------------------------------------------------------------------------
    */

    selectAgency(
        button.dataset.agencyId ||
        ''
    );


    /*
    |--------------------------------------------------------------------------
    | OPEN MODAL
    |--------------------------------------------------------------------------
    |
    | Open immediately so the administrator receives visual feedback
    | while the existing response is being retrieved.
    |--------------------------------------------------------------------------
    */

    setModalState(
        supportModal,
        true
    );


    /*
    |--------------------------------------------------------------------------
    | LOAD SAVED RESPONSE
    |--------------------------------------------------------------------------
    */

    try {

        const savedResponse =
            await loadLatestResponse(
                currentRequestId
            );


        /*
        |--------------------------------------------------------------------------
        | EXISTING RESPONSE
        |--------------------------------------------------------------------------
        |
        | If a response already exists, give it to the Response Builder.
        |--------------------------------------------------------------------------
        */

        if (savedResponse) {

            loadSavedResponse(
                savedResponse
            );
        }

    } catch (error) {

        /*
        |--------------------------------------------------------------------------
        | FAIL SAFELY
        |--------------------------------------------------------------------------
        |
        | The ticket itself can still be viewed even if loading the
        | saved response fails.
        |--------------------------------------------------------------------------
        */

        console.error(
            'Failed to load saved support response.',
            error
        );


        showClientMessage(
            'Unable to load response',
            error?.message ||
                'The existing official response could not be loaded.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FOCUS RESPONSE BUILDER
    |--------------------------------------------------------------------------
    */

    window.setTimeout(
        () => {

            document
                .getElementById(
                    'support-add-component'
                )
                ?.focus();

        },
        100
    );
};


/*
|--------------------------------------------------------------------------
| CLOSE SUPPORT MODAL
|--------------------------------------------------------------------------
*/

export const closeSupportModal = () => {

    /*
    |--------------------------------------------------------------------------
    | CLOSE MODAL
    |--------------------------------------------------------------------------
    */

    setModalState(
        supportModal,
        false
    );


    currentRequestId =
        null;


    /*
    |--------------------------------------------------------------------------
    | RESET FORM
    |--------------------------------------------------------------------------
    */

    if (replyForm) {

        replyForm.reset();
    }


    /*
    |--------------------------------------------------------------------------
    | RESET RESPONSE BUILDER
    |--------------------------------------------------------------------------
    */

    resetResponseBuilder();


    /*
    |--------------------------------------------------------------------------
    | RESTORE ENDPOINT
    |--------------------------------------------------------------------------
    */

    if (
        replyForm?.dataset.forwardUrl
    ) {

        replyForm.action =
            replyForm.dataset.forwardUrl;
    }


    if (methodInput) {

        methodInput.value =
            'POST';
    }


    /*
    |--------------------------------------------------------------------------
    | RESET AGENCY
    |--------------------------------------------------------------------------
    */

    if (agencySelect) {

        agencySelect.value =
            '';
    }


    if (agencySearchInput) {

        agencySearchInput.value =
            '';

        agencySearchInput.setCustomValidity(
            'Please select an agency from the list.'
        );
    }


    closeAgencyDropdown();


    /*
    |--------------------------------------------------------------------------
    | RESET SUBMIT BUTTON
    |--------------------------------------------------------------------------
    */

    resetSubmitButton();
};


/*
|--------------------------------------------------------------------------
| SUBMIT OFFICIAL RESPONSE
|--------------------------------------------------------------------------
|
| This module owns the actual HTTP request because forwarding a response
| is part of the Manage Support Request feature.
|--------------------------------------------------------------------------
*/

const submitOfficialResponse = async (
    event
) => {

    /*
    |--------------------------------------------------------------------------
    | STOP NORMAL FORM NAVIGATION
    |--------------------------------------------------------------------------
    */

    event.preventDefault();

    /*
|--------------------------------------------------------------------------
| DEFENSIVE WORKFLOW CHECK
|--------------------------------------------------------------------------
|
| The button is already disabled for locked states, but this additional
| client-side check prevents accidental submission if the DOM state
| changes unexpectedly.
|
| Laravel remains the authoritative security boundary.
|--------------------------------------------------------------------------
*/

if (
    saveButton?.dataset.workflowLocked ===
    'true'
) {
    return;
}


    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE SUBMISSIONS
    |--------------------------------------------------------------------------
    */

    if (
        saveButton?.dataset.submitting ===
        'true'
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | NATIVE FORM VALIDATION
    |--------------------------------------------------------------------------
    */

    if (
        !replyForm.checkValidity()
    ) {

        replyForm.reportValidity();

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSE BUILDER VALIDATION
    |--------------------------------------------------------------------------
    */

    const validation =
        validateResponseBuilder();


    if (
        !validation.valid
    ) {

        showClientMessage(
            'Response required',
            validation.message
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | LOCK SUBMIT BUTTON
    |--------------------------------------------------------------------------
    */

    lockSubmitButton();


    try {

        /*
        |--------------------------------------------------------------------------
        | BUILD MULTIPART REQUEST
        |--------------------------------------------------------------------------
        |
        | FormData automatically collects:
        | - CSRF hidden input
        | - request_id
        | - agency
        | - response components
        | - uploaded files
        |--------------------------------------------------------------------------
        */

        const formData =
            new FormData(
                replyForm
            );


        /*
        |--------------------------------------------------------------------------
        | SEND REQUEST
        |--------------------------------------------------------------------------
        */

        const response =
            await fetch(
                replyForm.dataset.forwardUrl ||
                    replyForm.action,
                {
                    method:
                        'POST',

                    headers: {
                        'X-CSRF-TOKEN':
                            csrfToken,

                        'Accept':
                            'application/json',
                    },

                    body:
                        formData,

                    credentials:
                        'same-origin',
                }
            );


        /*
        |--------------------------------------------------------------------------
        | PARSE SERVER RESPONSE
        |--------------------------------------------------------------------------
        */

        let data =
            null;


        try {

            data =
                await response.json();

        } catch {

            throw new Error(
                'The server returned an invalid response.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | HANDLE LARAVEL ERRORS
        |--------------------------------------------------------------------------
        */

        if (
            !response.ok ||
            !data?.success
        ) {

            throw new Error(
                data?.message ||
                    'The official response could not be forwarded.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CLOSE MODAL
        |--------------------------------------------------------------------------
        */

        closeSupportModal();


        /*
        |--------------------------------------------------------------------------
        | SUCCESS MESSAGE
        |--------------------------------------------------------------------------
        */

        if (
            typeof window.showAlertModal ===
            'function'
        ) {

            window.showAlertModal({

                title:
                    'Response Forwarded',

                text:
                    data.message ||
                    'The official response has been forwarded to the citizen.',

                icon:
                    'ph-light ph-paper-plane-tilt',

                variant:
                    'success',

                confirmText:
                    'OK',

                showCancel:
                    false,

                onConfirm: () => {

                    if (
                        typeof window.closeAlertModal ===
                        'function'
                    ) {

                        window.closeAlertModal();
                    }
                },
            });
        }


        /*
        |--------------------------------------------------------------------------
        | REFRESH PAGE
        |--------------------------------------------------------------------------
        |
        | The backend is the source of truth for the new ticket state.
        |--------------------------------------------------------------------------
        */

        window.setTimeout(
            () => {

                window.location.reload();

            },
            800
        );

    } catch (error) {

        /*
        |--------------------------------------------------------------------------
        | RESTORE SUBMIT STATE
        |--------------------------------------------------------------------------
        */

        console.error(
            'Support response submission failed.',
            error
        );


        resetSubmitButton();


        /*
        |--------------------------------------------------------------------------
        | DISPLAY SAFE ERROR
        |--------------------------------------------------------------------------
        */

        showClientMessage(
            'Unable to forward response',
            error?.message ||
                'Something went wrong while forwarding the official response.'
        );
    }
};


/*
|--------------------------------------------------------------------------
| INITIALIZE MANAGE MODAL
|--------------------------------------------------------------------------
*/

export function initializeManageModal() {

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

    supportModal =
        document.getElementById(
            'support-modal-back'
        );


    replyForm =
        document.getElementById(
            'reply-form'
        );


    methodInput =
        document.getElementById(
            'form-method'
        );


    saveButton =
        document.getElementById(
            'forward-response-btn'
        );


    agencySelect =
        document.getElementById(
            'sr-agency'
        );


    agencySearchInput =
        document.getElementById(
            'sr-agency-search'
        );


    agencyOptionsContainer =
        document.getElementById(
            'sr-agency-options'
        );


    agencySelector =
        document.getElementById(
            'support-agency-searchable'
        );


    /*
    |--------------------------------------------------------------------------
    | REQUIRED MODAL ELEMENTS
    |--------------------------------------------------------------------------
    */

    if (
        !supportModal ||
        !replyForm
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | AGENCY INITIALIZATION
    |--------------------------------------------------------------------------
    */

    buildAgencyData();

    initializeAgencySelector();


        /*
    |--------------------------------------------------------------------------
    | MANAGE BUTTON
    |--------------------------------------------------------------------------
    |
    | Event delegation is used because realtime.js can create new rows
    | after the page has already loaded.
    |
    | This means both server-rendered and realtime-created Manage buttons
    | use the same handler.
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'click',
        event => {
            const manageButton =
                event.target.closest('.view-btn');

            if (!manageButton) {
                return;
            }

            event.preventDefault();

            prepareSupportRequest(
                manageButton
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMISSION
    |--------------------------------------------------------------------------
    */

    replyForm.addEventListener(
        'submit',
        submitOfficialResponse
    );


    /*
    |--------------------------------------------------------------------------
    | BACKDROP CLICK
    |--------------------------------------------------------------------------
    */

    supportModal.addEventListener(
        'click',
        event => {

            if (
                event.target ===
                supportModal
            ) {

                closeSupportModal();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | INITIAL STATE
    |--------------------------------------------------------------------------
    */

    resetSubmitButton();


    /*
    |--------------------------------------------------------------------------
    | MARK INITIALIZED
    |--------------------------------------------------------------------------
    */

    isInitialized =
        true;
}

/*
|--------------------------------------------------------------------------
| LEGACY BLADE BRIDGE
|--------------------------------------------------------------------------
|
| The modal Blade currently uses onclick="closeSupportModal()".
|
| ES-module functions are not global by default, so expose only this
| specific function rather than exposing the entire module.
|--------------------------------------------------------------------------
*/

window.closeSupportModal = closeSupportModal;