document.addEventListener("DOMContentLoaded", () => {
    /*
    |--------------------------------------------------------------------------
    | SUPPORT REQUEST PAGE
    |--------------------------------------------------------------------------
    |
    | This script is responsible for:
    |
    | 1. Searchable agency selection
    | 2. Support request management modal
    | 3. Official response submission
    | 4. Similar FAQ checking
    | 5. Lifecycle confirmations
    | 6. Realtime Support Request updates through Laravel Echo
    |
    | The Response Builder has its own JavaScript module.
    | This file only coordinates the builder with the support ticket.
    |
    | The backend remains the source of truth.
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | DOM REFERENCES
    |--------------------------------------------------------------------------
    */

    const supportModal =
        document.getElementById("support-modal-back");

    const replyForm =
        document.getElementById("reply-form");

    const methodInput =
        document.getElementById("form-method");

    /*
     * The new response form uses #forward-response-btn.
     *
     * The previous selector looked for ".btn-save", which belonged
     * to the old answer workflow.
     */
    const saveButton =
        document.getElementById("forward-response-btn");

    const supportRequestsTableBody =
        document.getElementById("support-requests-table-body");


    /*
    |--------------------------------------------------------------------------
    | CURRENT REQUEST STATE
    |--------------------------------------------------------------------------
    */

    let currentRequestId = null;


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
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || "";


    /*
    |--------------------------------------------------------------------------
    | UTILITY HELPERS
    |--------------------------------------------------------------------------
    */


    /*
     * Safely convert an unknown value to a string.
     *
     * This prevents values received from realtime events
     * from being accidentally treated as objects.
     */
    function safeString(value, fallback = "") {
        if (
            value === null ||
            value === undefined
        ) {
            return fallback;
        }

        return String(value);
    }


    /*
     * Return the currently active table filters.
     *
     * These values come from the server-rendered Blade dataset.
     */
    function getCurrentTableFilters() {
        if (!supportRequestsTableBody) {
            return {
                datasetStatus: "",
                statusFilter: "",
                agency: "",
                search: ""
            };
        }

        return {
            datasetStatus:
                supportRequestsTableBody.dataset.status || "",

            statusFilter:
                supportRequestsTableBody.dataset.statusFilter || "",

            agency:
                supportRequestsTableBody.dataset.agency || "",

            search:
                (
                    supportRequestsTableBody.dataset.search ||
                    ""
                )
                    .trim()
                    .toLowerCase()
        };
    }


    /*
     * Safely change modal visibility.
     *
     * aria-hidden is updated together with the visual state
     * so assistive technologies receive the same state.
     */
    function setModalState(modal, isOpen) {
        if (!modal) {
            return;
        }

        modal.classList.toggle(
            "active",
            isOpen
        );

        modal.setAttribute(
            "aria-hidden",
            isOpen ? "false" : "true"
        );
    }


    /*
     * Display an application-level alert.
     *
     * The project already has a shared modal-system.js.
     * We use it whenever available instead of creating another
     * alert implementation.
     */
    function showClientMessage(
        title,
        text
    ) {
        if (
            typeof window.showAlertModal ===
            "function"
        ) {
            window.showAlertModal({
                title,
                text,
                icon: "!",
                variant: "danger",
                confirmText: "OK",
                showCancel: false,

                onConfirm: () => {
                    if (
                        typeof window.closeAlertModal ===
                        "function"
                    ) {
                        window.closeAlertModal();
                    }
                }
            });

            return;
        }

        /*
         * Fallback only exists in case the shared alert
         * component has not been loaded.
         */
        window.alert(text);
    }


    /*
    |--------------------------------------------------------------------------
    | SEARCHABLE AGENCY SELECT
    |--------------------------------------------------------------------------
    */

    const agencySelect =
        document.getElementById("sr-agency");

    const agencySearchInput =
        document.getElementById("sr-agency-search");

    const agencyOptionsContainer =
        document.getElementById("sr-agency-options");

    const agencySelector =
        document.getElementById(
            "support-agency-searchable"
        );

    let searchableAgencies = [];

    let agencyDropdownOpen = false;


    /*
     * Build a normalized JavaScript representation
     * of the agencies provided by Laravel.
     */
    function buildAgencyData() {
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
                        option.value !== ""
                )
                .map(option => {
                    const fullName =
                        option.dataset.fullName ||
                        option.textContent.trim();

                    const abbreviation =
                        option.dataset.abbr || "";

                    return {
                        value: option.value,

                        fullName:
                            fullName.trim(),

                        abbreviation:
                            abbreviation.trim(),

                        searchText:
                            `${fullName} ${abbreviation}`
                                .toLowerCase()
                                .trim()
                    };
                });
    }


    /*
     * Render the agency dropdown.
     *
     * textContent is intentionally used instead of innerHTML
     * because agency names originate from database data.
     */
    function renderAgencyOptions(
        searchTerm = ""
    ) {
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

        agencyOptionsContainer.replaceChildren();

        if (matches.length === 0) {
            const emptyState =
                document.createElement("div");

            emptyState.className =
                "searchable-select-empty";

            emptyState.textContent =
                "No matching agencies found.";

            agencyOptionsContainer.appendChild(
                emptyState
            );

            return;
        }

        matches.forEach(agency => {
            const option =
                document.createElement("button");

            option.type = "button";

            option.className =
                "searchable-select-option";

            option.setAttribute(
                "role",
                "option"
            );

            option.setAttribute(
                "aria-selected",
                agencySelect?.value === agency.value
                    ? "true"
                    : "false"
            );

            option.dataset.value =
                agency.value;

            option.textContent =
                agency.fullName;

            if (agency.abbreviation) {
                option.textContent +=
                    ` (${agency.abbreviation.toUpperCase()})`;
            }

            if (
                agencySelect &&
                agencySelect.value === agency.value
            ) {
                option.classList.add(
                    "is-selected"
                );
            }

            option.addEventListener(
                "click",
                () => {
                    selectAgency(
                        agency.value
                    );
                }
            );

            agencyOptionsContainer.appendChild(
                option
            );
        });
    }


    /*
     * Open the agency dropdown.
     */
    function openAgencyDropdown() {
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

        agencyDropdownOpen = true;

        agencySelector.classList.add(
            "is-open"
        );

        agencySearchInput.setAttribute(
            "aria-expanded",
            "true"
        );

        renderAgencyOptions(
            agencySearchInput.value
        );
    }


    /*
     * Close the agency dropdown.
     */
    function closeAgencyDropdown() {
        agencyDropdownOpen = false;

        agencySelector?.classList.remove(
            "is-open"
        );

        agencySearchInput?.setAttribute(
            "aria-expanded",
            "false"
        );
    }


    /*
     * Select an agency using the actual native select.
     *
     * The native select remains the actual form field
     * submitted to Laravel.
     */
    function selectAgency(agencyValue) {
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

        if (!selectedOption) {
            agencySelect.value = "";

            agencySearchInput.value = "";

            agencySearchInput.setCustomValidity(
                "Please select an agency from the list."
            );

            closeAgencyDropdown();

            return;
        }

        agencySelect.value =
            selectedOption.value;

        agencySearchInput.value =
            selectedOption.dataset.fullName ||
            selectedOption.textContent.trim();

        agencySearchInput.setCustomValidity("");

        closeAgencyDropdown();

        /*
         * Keep the native select synchronized with
         * any other code listening for change events.
         */
        agencySelect.dispatchEvent(
            new Event("change", {
                bubbles: true
            })
        );
    }


    /*
     * Synchronize the visible search input
     * with the native select.
     */
    function syncAgencySearchInput() {
        if (
            !agencySelect ||
            !agencySearchInput
        ) {
            return;
        }

        const selectedOption =
            agencySelect.options[
                agencySelect.selectedIndex
            ];

        if (
            !selectedOption ||
            !selectedOption.value
        ) {
            agencySearchInput.value = "";

            agencySearchInput.setCustomValidity(
                "Please select an agency from the list."
            );

            return;
        }

        agencySearchInput.value =
            selectedOption.dataset.fullName ||
            selectedOption.textContent.trim();

        agencySearchInput.setCustomValidity("");
    }


    /*
     * Agency search events.
     */
    if (agencySearchInput) {
        agencySearchInput.addEventListener(
            "focus",
            openAgencyDropdown
        );

        agencySearchInput.addEventListener(
            "click",
            openAgencyDropdown
        );

        agencySearchInput.addEventListener(
            "input",
            () => {
                /*
                 * Typing means the previously selected
                 * native value is no longer guaranteed to match.
                 */
                if (agencySelect) {
                    agencySelect.value = "";
                }

                agencySearchInput.setCustomValidity(
                    "Please select an agency from the list."
                );

                openAgencyDropdown();

                renderAgencyOptions(
                    agencySearchInput.value
                );
            }
        );

        agencySearchInput.addEventListener(
            "keydown",
            event => {
                if (event.key === "Escape") {
                    closeAgencyDropdown();
                    return;
                }

                if (
                    event.key === "Enter" &&
                    agencyDropdownOpen
                ) {
                    const firstOption =
                        agencyOptionsContainer?.querySelector(
                            ".searchable-select-option"
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
    }


    /*
     * Close the dropdown when clicking outside.
     */
    document.addEventListener(
        "click",
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


    /*
     * Build agency data once after the DOM is ready.
     */
    buildAgencyData();


    /*
    |--------------------------------------------------------------------------
    | SUPPORT REQUEST MODAL
    |--------------------------------------------------------------------------
    */


    /*
     * Open a Support Request in the official response modal.
     *
     * The Response Builder is reset because this same modal
     * can be reused for multiple tickets.
     */
    function prepareSupportRequest(
        button
    ) {
        if (
            !supportModal ||
            !replyForm
        ) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Reset official response builder
        |--------------------------------------------------------------------------
        */

        if (
            window.SupportResponseBuilder &&
            typeof window.SupportResponseBuilder.reset ===
                "function"
        ) {
            window.SupportResponseBuilder.reset();
        }


        /*
        |--------------------------------------------------------------------------
        | Set current request
        |--------------------------------------------------------------------------
        */

        currentRequestId =
            button.dataset.id || null;


        /*
        |--------------------------------------------------------------------------
        | Request information references
        |--------------------------------------------------------------------------
        */

        const userInput =
            document.getElementById(
                "sr-user"
            );

        const questionInput =
            document.getElementById(
                "sr-question"
            );

        const requestIdInput =
            document.getElementById(
                "sr-id"
            );


        /*
        |--------------------------------------------------------------------------
        | Populate readonly request information
        |--------------------------------------------------------------------------
        */

        if (requestIdInput) {
            requestIdInput.value =
                currentRequestId || "";
        }

        if (userInput) {
            userInput.value =
                button.dataset.user ||
                "Guest";
        }

        if (questionInput) {
            questionInput.value =
                button.dataset.question ||
                "";
        }


        /*
        |--------------------------------------------------------------------------
        | Configure official response endpoint
        |--------------------------------------------------------------------------
        |
        | The new workflow always creates a response attempt.
        | There is no longer a separate "Mark as Answered"
        | or "Update Answer" JavaScript branch.
        |
        */

        if (replyForm.dataset.forwardUrl) {
            replyForm.action =
                replyForm.dataset.forwardUrl;
        }

        if (methodInput) {
            methodInput.value =
                "POST";
        }


        /*
        |--------------------------------------------------------------------------
        | Reset submit state
        |--------------------------------------------------------------------------
        */

        if (saveButton) {
            saveButton.disabled =
                false;

            saveButton.dataset.submitting =
                "false";

            const label =
                saveButton.querySelector(
                    "[data-submit-label]"
                );

            if (label) {
                label.textContent =
                    "Forward to Citizen";
            }
        }


        /*
        |--------------------------------------------------------------------------
        | Select assigned agency
        |--------------------------------------------------------------------------
        */

        selectAgency(
            button.dataset.agencyId ||
            ""
        );


        /*
        |--------------------------------------------------------------------------
        | Open modal
        |--------------------------------------------------------------------------
        */

        setModalState(
            supportModal,
            true
        );


        /*
        |--------------------------------------------------------------------------
        | Focus response builder
        |--------------------------------------------------------------------------
        |
        | The old #sr-reply textarea no longer exists.
        | The Add Response Component button is now the
        | first interaction point.
        |
        */

        window.setTimeout(
            () => {
                document
                    .getElementById(
                        "support-add-component"
                    )
                    ?.focus();
            },
            100
        );
    }


    /*
     * Close the support modal.
     */
    function closeSupportModal() {
        setModalState(
            supportModal,
            false
        );

        currentRequestId =
            null;


        /*
        |--------------------------------------------------------------------------
        | Reset form
        |--------------------------------------------------------------------------
        */

        if (replyForm) {
            replyForm.reset();
        }


        /*
        |--------------------------------------------------------------------------
        | Reset official response builder
        |--------------------------------------------------------------------------
        */

        if (
            window.SupportResponseBuilder &&
            typeof window.SupportResponseBuilder.reset ===
                "function"
        ) {
            window.SupportResponseBuilder.reset();
        }


        /*
        |--------------------------------------------------------------------------
        | Restore official response endpoint
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
                "POST";
        }


        /*
        |--------------------------------------------------------------------------
        | Reset agency selector
        |--------------------------------------------------------------------------
        */

        if (agencySelect) {
            agencySelect.value = "";
        }

        if (agencySearchInput) {
            agencySearchInput.value = "";

            agencySearchInput.setCustomValidity(
                "Please select an agency from the list."
            );
        }

        closeAgencyDropdown();


        /*
        |--------------------------------------------------------------------------
        | Reset submit button
        |--------------------------------------------------------------------------
        */

        if (saveButton) {
            saveButton.disabled =
                false;

            saveButton.dataset.submitting =
                "false";

            const label =
                saveButton.querySelector(
                    "[data-submit-label]"
                );

            if (label) {
                label.textContent =
                    "Forward to Citizen";
            }
        }
    }


    /*
     * Make the function available to the Blade's
     * existing inline onclick handlers.
     */
    window.closeSupportModal =
        closeSupportModal;


    /*
     * Clicking the backdrop closes the modal.
     */
    supportModal?.addEventListener(
        "click",
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
    | SUPPORT FORM SUBMISSION
    |--------------------------------------------------------------------------
    |
    | support-request.js owns the actual HTTP request.
    |
    | response-builder.js owns the creation and validation
    | of individual response components.
    |
    */


    replyForm?.addEventListener(
        "submit",
        async event => {
            /*
            |--------------------------------------------------------------------------
            | Stop normal browser navigation
            |--------------------------------------------------------------------------
            |
            | The Laravel endpoint returns JSON.
            | A normal browser submission would therefore display
            | the raw JSON response instead of keeping the user
            | inside the admin interface.
            |
            */

            event.preventDefault();


            /*
            |--------------------------------------------------------------------------
            | Prevent duplicate submissions
            |--------------------------------------------------------------------------
            */

            if (
                saveButton?.dataset.submitting ===
                "true"
            ) {
                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Run native browser validation
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
            | Verify Response Builder availability
            |--------------------------------------------------------------------------
            */

            if (
                !window.SupportResponseBuilder ||
                typeof window.SupportResponseBuilder.getComponentCount !==
                    "function"
            ) {
                showClientMessage(
                    "Response unavailable",
                    "The official response builder is not ready. Please try again."
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Require at least one response component
            |--------------------------------------------------------------------------
            */

            const componentCount =
                window.SupportResponseBuilder
                    .getComponentCount();

            if (
                componentCount < 1
            ) {
                showClientMessage(
                    "Response required",
                    "Please add at least one component to the official response."
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Lock submit button
            |--------------------------------------------------------------------------
            */

            if (saveButton) {
                saveButton.dataset.submitting =
                    "true";

                saveButton.disabled =
                    true;

                const label =
                    saveButton.querySelector(
                        "[data-submit-label]"
                    );

                if (label) {
                    label.textContent =
                        "Forwarding...";
                }
            }


            try {
                /*
                |--------------------------------------------------------------------------
                | Build multipart request
                |--------------------------------------------------------------------------
                |
                | FormData automatically includes:
                |
                | - CSRF hidden field
                | - request_id
                | - agency
                | - response component fields
                | - uploaded files
                |
                */

                const formData =
                    new FormData(
                        replyForm
                    );


                /*
                |--------------------------------------------------------------------------
                | Send request to Laravel
                |--------------------------------------------------------------------------
                */

                const response =
                    await fetch(
                        replyForm.dataset.forwardUrl ||
                            replyForm.action,
                        {
                            method: "POST",

                            headers: {
                                "X-CSRF-TOKEN":
                                    csrfToken,

                                "Accept":
                                    "application/json"
                            },

                            body:
                                formData,

                            credentials:
                                "same-origin"
                        }
                    );


                /*
                |--------------------------------------------------------------------------
                | Parse JSON response
                |--------------------------------------------------------------------------
                */

                let data = null;

                try {
                    data =
                        await response.json();
                } catch {
                    throw new Error(
                        "The server returned an invalid response."
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Handle Laravel errors
                |--------------------------------------------------------------------------
                */

                if (
                    !response.ok ||
                    !data?.success
                ) {
                    throw new Error(
                        data?.message ||
                            "The official response could not be forwarded."
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | Close the ticket modal
                |--------------------------------------------------------------------------
                */

                closeSupportModal();


                /*
                |--------------------------------------------------------------------------
                | Show success message
                |--------------------------------------------------------------------------
                */

                if (
                    typeof window.showAlertModal ===
                    "function"
                ) {
                    window.showAlertModal({
                        title:
                            "Response Forwarded",

                        text:
                            data.message ||
                            "The official response has been forwarded to the citizen.",

                        icon:
                            "ph-light ph-paper-plane-tilt",

                        variant:
                            "success",

                        confirmText:
                            "OK",

                        showCancel:
                            false,

                        onConfirm: () => {
                            if (
                                typeof window.closeAlertModal ===
                                "function"
                            ) {
                                window.closeAlertModal();
                            }
                        }
                    });
                }


                /*
                |--------------------------------------------------------------------------
                | Refresh the page
                |--------------------------------------------------------------------------
                |
                | The server is the source of truth for:
                |
                | - ticket status
                | - response state
                | - available actions
                | - filters
                |
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
                | Restore submit button after failure
                |--------------------------------------------------------------------------
                */

                console.error(
                    "Support response submission failed.",
                    error
                );

                if (saveButton) {
                    saveButton.dataset.submitting =
                        "false";

                    saveButton.disabled =
                        false;

                    const label =
                        saveButton.querySelector(
                            "[data-submit-label]"
                        );

                    if (label) {
                        label.textContent =
                            "Forward to Citizen";
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Display safe error message
                |--------------------------------------------------------------------------
                */

                showClientMessage(
                    "Unable to forward response",
                    error?.message ||
                        "Something went wrong while forwarding the official response."
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | SIMILAR FAQ MODAL
    |--------------------------------------------------------------------------
    */

    const similarFaqModal =
        document.getElementById(
            "similar-faq-modal-back"
        );

    const similarFaqMessage =
        document.getElementById(
            "similar-faq-message"
        );

    const similarFaqResults =
        document.getElementById(
            "similar-faq-results"
        );

    const similarFaqCancel =
        document.getElementById(
            "similar-faq-cancel"
        );

    const similarFaqContinue =
        document.getElementById(
            "similar-faq-continue"
        );

    let pendingFaqUrl = null;


    /*
     * Open Similar FAQ modal.
     */
    function openSimilarFaqModal() {
        setModalState(
            similarFaqModal,
            true
        );
    }


    /*
     * Close Similar FAQ modal.
     */
    function closeSimilarFaqModal() {
        setModalState(
            similarFaqModal,
            false
        );

        pendingFaqUrl =
            null;
    }


    /*
     * Render the loading state safely.
     */
    function renderSimilarFaqLoading() {
        if (!similarFaqResults) {
            return;
        }

        similarFaqResults.replaceChildren();

        const wrapper =
            document.createElement("div");

        wrapper.className =
            "similar-faq-loading";

        const icon =
            document.createElement("i");

        icon.className =
            "ph-light ph-spinner-gap";

        const text =
            document.createElement("span");

        text.textContent =
            "Checking existing FAQs...";

        wrapper.appendChild(icon);

        wrapper.appendChild(text);

        similarFaqResults.appendChild(
            wrapper
        );
    }


    /*
     * Render a Similar FAQ empty/error state.
     */
    function renderSimilarFaqMessage(
        iconClass,
        message
    ) {
        if (!similarFaqResults) {
            return;
        }

        similarFaqResults.replaceChildren();

        const wrapper =
            document.createElement("div");

        wrapper.className =
            "similar-faq-empty";

        const icon =
            document.createElement("i");

        icon.className =
            iconClass;

        const text =
            document.createElement("div");

        text.textContent =
            message;

        wrapper.appendChild(icon);

        wrapper.appendChild(text);

        similarFaqResults.appendChild(
            wrapper
        );
    }


    /*
     * Render returned FAQ matches.
     *
     * Database values are inserted with textContent
     * rather than innerHTML.
     */
    function renderSimilarFaqMatches(
        matches
    ) {
        if (!similarFaqResults) {
            return;
        }

        similarFaqResults.replaceChildren();

        matches.forEach(match => {
            const item =
                document.createElement("div");

            item.className =
                "similar-faq-item";


            const score =
                document.createElement("div");

            score.className =
                "similar-faq-score";

            const percentage =
                Number(
                    match.percentage
                );

            score.textContent =
                `${Number.isFinite(percentage)
                    ? percentage
                    : 0}%`;


            const content =
                document.createElement("div");

            content.className =
                "similar-faq-content";


            const question =
                document.createElement("p");

            question.className =
                "similar-faq-question";

            question.textContent =
                match.question ||
                "Untitled FAQ";


            const agency =
                document.createElement("p");

            agency.className =
                "similar-faq-agency";

            agency.textContent =
                match.agency_name ||
                "Unknown agency";


            content.appendChild(
                question
            );

            content.appendChild(
                agency
            );

            item.appendChild(
                score
            );

            item.appendChild(
                content
            );

            similarFaqResults.appendChild(
                item
            );
        });
    }


    /*
     * Validate that a server-provided URL
     * belongs to the current application origin.
     *
     * This prevents an unexpected payload from
     * redirecting the administrator to another origin.
     */
    function isSameOriginUrl(
        value
    ) {
        try {
            const url =
                new URL(
                    value,
                    window.location.origin
                );

            return (
                url.origin ===
                window.location.origin
            );
        } catch {
            return false;
        }
    }


    /*
     * Check for similar FAQs before navigating
     * to the FAQ creation page.
     */
    async function checkSimilarFaqs(
        faqButton
    ) {
        const similarUrl =
            faqButton.dataset.similarUrl;

        const faqUrl =
            faqButton.href;


        /*
        |--------------------------------------------------------------------------
        | Validate destination
        |--------------------------------------------------------------------------
        */

        if (
            !faqUrl ||
            !isSameOriginUrl(faqUrl)
        ) {
            showClientMessage(
                "Invalid destination",
                "The FAQ destination could not be verified."
            );

            return;
        }


        pendingFaqUrl =
            faqUrl;


        /*
        |--------------------------------------------------------------------------
        | Missing similarity endpoint
        |--------------------------------------------------------------------------
        |
        | Safely continue to the server-generated FAQ route.
        |
        */

        if (
            !similarUrl ||
            !isSameOriginUrl(similarUrl)
        ) {
            window.location.href =
                pendingFaqUrl;

            return;
        }


        openSimilarFaqModal();


        if (similarFaqMessage) {
            similarFaqMessage.textContent =
                "Checking existing FAQs for similar questions...";
        }

        renderSimilarFaqLoading();


        if (similarFaqContinue) {
            similarFaqContinue.disabled =
                true;
        }


        try {
            const response =
                await fetch(
                    similarUrl,
                    {
                        method: "POST",

                        headers: {
                            "X-CSRF-TOKEN":
                                csrfToken,

                            "Accept":
                                "application/json"
                        },

                        credentials:
                            "same-origin"
                    }
                );


            /*
             * Some server errors return HTML instead of JSON,
             * so parse defensively.
             */
            let data = null;

            try {
                data =
                    await response.json();
            } catch {
                throw new Error(
                    "The server returned an invalid response."
                );
            }


            if (
                !response.ok ||
                !data?.success
            ) {
                throw new Error(
                    data?.message ||
                    "Unable to check similar FAQs."
                );
            }


            const matches =
                Array.isArray(
                    data.matches
                )
                    ? data.matches
                    : [];


            if (
                matches.length === 0
            ) {
                if (similarFaqMessage) {
                    similarFaqMessage.textContent =
                        "No similar FAQs were found. You can continue creating this FAQ.";
                }

                renderSimilarFaqMessage(
                    "ph-light ph-check-circle",
                    "No existing FAQ appears to closely match this Support Request."
                );

            } else {
                if (similarFaqMessage) {
                    similarFaqMessage.textContent =
                        "We found existing FAQs that may be related to this Support Request. Review them before continuing.";
                }

                renderSimilarFaqMatches(
                    matches
                );
            }


            if (similarFaqContinue) {
                similarFaqContinue.disabled =
                    false;
            }

        } catch (error) {
            /*
             * Technical errors are not exposed to
             * the administrator.
             */
            console.error(
                "Similar FAQ request failed.",
                error
            );

            if (similarFaqMessage) {
                similarFaqMessage.textContent =
                    "We could not check existing FAQs right now.";
            }

            renderSimilarFaqMessage(
                "ph-light ph-warning-circle",
                "The similarity check could not be completed. You can still continue to the FAQ creation page."
            );

            if (similarFaqContinue) {
                similarFaqContinue.disabled =
                    false;
            }
        }
    }


    /*
     * Similar FAQ cancel button.
     */
    similarFaqCancel?.addEventListener(
        "click",
        closeSimilarFaqModal
    );


    /*
     * Similar FAQ backdrop.
     */
    similarFaqModal?.addEventListener(
        "click",
        event => {
            if (
                event.target ===
                similarFaqModal
            ) {
                closeSimilarFaqModal();
            }
        }
    );


    /*
     * Continue to FAQ.
     */
    similarFaqContinue?.addEventListener(
        "click",
        () => {
            if (
                !pendingFaqUrl ||
                !isSameOriginUrl(
                    pendingFaqUrl
                )
            ) {
                return;
            }

            window.location.href =
                pendingFaqUrl;
        }
    );


    /*
    |--------------------------------------------------------------------------
    | ACTION MENU
    |--------------------------------------------------------------------------
    */

    function closeAllActionMenus(
        exceptMenu = null
    ) {
        document
            .querySelectorAll(
                ".support-action-menu.is-open"
            )
            .forEach(menu => {
                if (menu !== exceptMenu) {
                    menu.classList.remove(
                        "is-open"
                    );

                    menu
                        .querySelector(
                            ".support-action-menu-trigger"
                        )
                        ?.setAttribute(
                            "aria-expanded",
                            "false"
                        );
                }
            });
    }


    function toggleActionMenu(
        trigger
    ) {
        const menu =
            trigger.closest(
                ".support-action-menu"
            );

        if (!menu) {
            return;
        }

        const shouldOpen =
            !menu.classList.contains(
                "is-open"
            );

        closeAllActionMenus(
            menu
        );

        menu.classList.toggle(
            "is-open",
            shouldOpen
        );

        trigger.setAttribute(
            "aria-expanded",
            shouldOpen
                ? "true"
                : "false"
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LIFECYCLE CONFIRMATIONS
    |--------------------------------------------------------------------------
    */

    function confirmLifecycleAction(
        button
    ) {
        const form =
            button.closest("form");

        if (!form) {
            return;
        }

        let config = null;


        if (
            button.classList.contains(
                "delete-btn"
            )
        ) {
            config = {
                title:
                    "Move Support Request to Trash",

                text:
                    "Are you sure you want to move this support request to trash? You can restore it later.",

                icon:
                    "ph-light ph-trash",

                variant:
                    "danger",

                confirmText:
                    "Move to Trash"
            };
        }


        if (
            button.classList.contains(
                "restore-btn"
            )
        ) {
            config = {
                title:
                    "Restore Support Request",

                text:
                    "Are you sure you want to restore this support request?",

                icon:
                    "ph-light ph-arrow-counter-clockwise",

                variant:
                    "success",

                confirmText:
                    "Restore"
            };
        }


        if (
            button.classList.contains(
                "permanent-delete-btn"
            )
        ) {
            config = {
                title:
                    "Delete Support Request Permanently",

                text:
                    "This action permanently deletes the support request and cannot be undone.",

                icon:
                    "ph-light ph-trash-simple",

                variant:
                    "danger",

                confirmText:
                    "Delete Permanently"
            };
        }


        if (!config) {
            return;
        }


        if (
            typeof window.showAlertModal !==
            "function"
        ) {
            form.submit();
            return;
        }


        window.showAlertModal({
            title:
                config.title,

            text:
                config.text,

            icon:
                config.icon,

            variant:
                config.variant,

            confirmText:
                config.confirmText,

            showCancel:
                true,

            onConfirm: () => {
                form.submit();
            }
        });
    }


    /*
    |--------------------------------------------------------------------------
    | REALTIME ROW HELPERS
    |--------------------------------------------------------------------------
    */


    /*
     * Create a hidden input.
     *
     * Used by dynamically created lifecycle forms.
     */
    function createHiddenInput(
        name,
        value
    ) {
        const input =
            document.createElement(
                "input"
            );

        input.type =
            "hidden";

        input.name =
            name;

        input.value =
            value;

        return input;
    }


    /*
     * Create a lifecycle form.
     *
     * Laravel receives the same CSRF and method-spoofing
     * fields that a normal Blade form would contain.
     */
    function createLifecycleForm(
        action,
        method,
        buttonClass,
        iconClass,
        label
    ) {
        const form =
            document.createElement(
                "form"
            );

        form.method =
            "POST";

        form.action =
            action;

        form.className =
            "support-lifecycle-form";


        form.appendChild(
            createHiddenInput(
                "_token",
                csrfToken
            )
        );


        form.appendChild(
            createHiddenInput(
                "_method",
                method
            )
        );


        const button =
            document.createElement(
                "button"
            );

        button.type =
            "submit";

        button.className =
            buttonClass;

        button.setAttribute(
            "role",
            "menuitem"
        );


        const icon =
            document.createElement(
                "i"
            );

        icon.className =
            iconClass;

        icon.setAttribute(
            "aria-hidden",
            "true"
        );


        const text =
            document.createElement(
                "span"
            );

        text.textContent =
            label;


        button.appendChild(
            icon
        );

        button.appendChild(
            text
        );

        form.appendChild(
            button
        );

        return form;
    }


    /*
     * Create a realtime row using the same
     * semantic structure as the Blade table.
     */
    function createRealtimeSupportRequestRow(
        request
    ) {
        const row =
            document.createElement(
                "tr"
            );

        row.className =
            "support-request-row";

        row.dataset.requestId =
            safeString(
                request.id
            );


        /*
        |--------------------------------------------------------------------------
        | NORMALIZE EVENT DATA
        |--------------------------------------------------------------------------
        */

        const requestId =
            safeString(
                request.id
            );

        const question =
            safeString(
                request.question
            );

        const status =
            safeString(
                request.status,
                "pending"
            );

        const agencyName =
            safeString(
                request.agency_name
            );

        const agencyId =
            safeString(
                request.agency_id ??
                request.agency?.id
            );


        /*
         * The actual user field is first_name.
         * We do not invent an avatar or profile system.
         */
        const userName =
            request.user?.first_name ||
            request.first_name ||
            request.user_name ||
            "Guest";


        /*
        |--------------------------------------------------------------------------
        | ID
        |--------------------------------------------------------------------------
        */

        const idCell =
            document.createElement(
                "td"
            );

        idCell.className =
            "support-request-id";

        const idSpan =
            document.createElement(
                "span"
            );

        idSpan.textContent =
            `#${requestId}`;

        idCell.appendChild(
            idSpan
        );

        row.appendChild(
            idCell
        );


        /*
        |--------------------------------------------------------------------------
        | USER
        |--------------------------------------------------------------------------
        */

        const userCell =
            document.createElement(
                "td"
            );

        userCell.className =
            "support-request-user";

        const userWrapper =
            document.createElement(
                "div"
            );

        userWrapper.className =
            "support-user-cell";

        const userDetails =
            document.createElement(
                "div"
            );

        userDetails.className =
            "support-user-details";

        const userNameElement =
            document.createElement(
                "span"
            );

        userNameElement.className =
            "support-user-name";

        userNameElement.textContent =
            userName;

        userDetails.appendChild(
            userNameElement
        );

        userWrapper.appendChild(
            userDetails
        );

        userCell.appendChild(
            userWrapper
        );

        row.appendChild(
            userCell
        );


        /*
        |--------------------------------------------------------------------------
        | QUESTION
        |--------------------------------------------------------------------------
        */

        const questionCell =
            document.createElement(
                "td"
            );

        questionCell.className =
            "support-request-question";

        const questionWrapper =
            document.createElement(
                "div"
            );

        questionWrapper.className =
            "support-question-cell";

        const questionElement =
            document.createElement(
                "span"
            );

        questionElement.className =
            "support-question-text";

        questionElement.title =
            question;

        questionElement.textContent =
            question.length > 90
                ? `${question.substring(0, 90)}...`
                : question;

        questionWrapper.appendChild(
            questionElement
        );

        questionCell.appendChild(
            questionWrapper
        );

        row.appendChild(
            questionCell
        );


        /*
        |--------------------------------------------------------------------------
        | AGENCY
        |--------------------------------------------------------------------------
        */

        const agencyCell =
            document.createElement(
                "td"
            );

        agencyCell.className =
            "support-request-agency";

        const agencyElement =
            document.createElement(
                "span"
            );

        agencyElement.className =
            agencyName
                ? "support-agency-name"
                : "support-agency-empty";

        agencyElement.textContent =
            agencyName ||
            "Unassigned";

        agencyCell.appendChild(
            agencyElement
        );

        row.appendChild(
            agencyCell
        );


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        const statusCell =
            document.createElement(
                "td"
            );

        statusCell.className =
            "support-request-status";

        const statusBadge =
            document.createElement(
                "span"
            );

        statusBadge.className =
            `support-status-badge ${status}`;

        const statusDot =
            document.createElement(
                "span"
            );

        statusDot.className =
            "support-status-dot";

        statusDot.setAttribute(
            "aria-hidden",
            "true"
        );

        const statusText =
            document.createTextNode(
                status.charAt(0).toUpperCase() +
                status.slice(1)
            );

        statusBadge.appendChild(
            statusDot
        );

        statusBadge.appendChild(
            statusText
        );

        statusCell.appendChild(
            statusBadge
        );

        row.appendChild(
            statusCell
        );


        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        const dateCell =
            document.createElement(
                "td"
            );

        dateCell.className =
            "support-request-date";

        const dateElement =
            document.createElement(
                "time"
            );

        const createdAt =
            request.created_at
                ? new Date(
                    request.created_at
                )
                : new Date();

        if (
            !Number.isNaN(
                createdAt.getTime()
            )
        ) {
            dateElement.dateTime =
                createdAt.toISOString();

            dateElement.textContent =
                createdAt.toLocaleDateString(
                    "en-US",
                    {
                        month:
                            "short",

                        day:
                            "2-digit",

                        year:
                            "numeric"
                    }
                );

        } else {
            dateElement.textContent =
                "—";
        }

        dateCell.appendChild(
            dateElement
        );

        row.appendChild(
            dateCell
        );


        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */

        const actionCell =
            document.createElement(
                "td"
            );

        actionCell.className =
            "support-request-actions";

        const actionWrapper =
            document.createElement(
                "div"
            );

        actionWrapper.className =
            "support-row-actions";


        /*
         * Manage button.
         */
        const manageButton =
            document.createElement(
                "button"
            );

        manageButton.type =
            "button";

        manageButton.className =
            "support-action-primary view-btn";

        manageButton.dataset.id =
            requestId;

        manageButton.dataset.user =
            userName;

        manageButton.dataset.question =
            question;

        manageButton.dataset.agency =
            agencyName;

        manageButton.dataset.agencyId =
            agencyId;

        /*
         * These legacy dataset values are intentionally
         * empty because the new response system no longer
         * reads answer or answer-image data.
         */
        manageButton.dataset.answer =
            "";

        manageButton.dataset.answerImage =
            "";

        manageButton.setAttribute(
            "aria-label",
            `Manage support request #${requestId}`
        );


        const manageIcon =
            document.createElement(
                "i"
            );

        manageIcon.className =
            "ph-light ph-chat-centered-text";

        manageIcon.setAttribute(
            "aria-hidden",
            "true"
        );


        const manageText =
            document.createElement(
                "span"
            );

        manageText.textContent =
            "Manage";


        manageButton.appendChild(
            manageIcon
        );

        manageButton.appendChild(
            manageText
        );

        actionWrapper.appendChild(
            manageButton
        );


        /*
        |--------------------------------------------------------------------------
        | Superadmin-only secondary actions
        |--------------------------------------------------------------------------
        */

        const isSuperadmin =
            supportRequestsTableBody?.dataset
                .isSuperadmin === "true";


        if (isSuperadmin) {
            const menu =
                document.createElement(
                    "div"
                );

            menu.className =
                "support-action-menu";


            const trigger =
                document.createElement(
                    "button"
                );

            trigger.type =
                "button";

            trigger.className =
                "support-action-menu-trigger";

            trigger.setAttribute(
                "aria-label",
                `More actions for support request #${requestId}`
            );

            trigger.setAttribute(
                "aria-expanded",
                "false"
            );

            trigger.setAttribute(
                "aria-haspopup",
                "menu"
            );


            const triggerIcon =
                document.createElement(
                    "i"
                );

            triggerIcon.className =
                "ph-light ph-dots-three-vertical";

            triggerIcon.setAttribute(
                "aria-hidden",
                "true"
            );

            trigger.appendChild(
                triggerIcon
            );


            const menuContent =
                document.createElement(
                    "div"
                );

            menuContent.className =
                "support-action-menu-content";

            menuContent.setAttribute(
                "role",
                "menu"
            );


            /*
            |--------------------------------------------------------------------------
            | Move to trash
            |--------------------------------------------------------------------------
            */

            const deleteBase =
                supportRequestsTableBody?.dataset
                    .deleteUrl || "";

            const deleteForm =
                createLifecycleForm(
                    `${deleteBase}/${encodeURIComponent(requestId)}`,
                    "DELETE",
                    "support-menu-action support-menu-danger delete-btn",
                    "ph-light ph-trash",
                    "Move to trash"
                );

            menuContent.appendChild(
                deleteForm
            );


            /*
            |--------------------------------------------------------------------------
            | Similar FAQ / To FAQ
            |--------------------------------------------------------------------------
            |
            | These follow the existing Laravel route structure.
            |--------------------------------------------------------------------------
            */

            const faqBase =
                supportRequestsTableBody?.dataset
                    .faqUrl || "";

            const similarFaqBase =
                supportRequestsTableBody?.dataset
                    .similarFaqUrl || "";


            const faqLink =
                document.createElement(
                    "a"
                );

            faqLink.href =
                `${faqBase}/${encodeURIComponent(requestId)}/to-faq`;

            faqLink.className =
                "support-menu-action support-menu-faq faq-btn";

            faqLink.dataset.id =
                requestId;

            faqLink.dataset.similarUrl =
                `${similarFaqBase}/${encodeURIComponent(requestId)}/similar-faqs`;

            faqLink.setAttribute(
                "role",
                "menuitem"
            );


            const faqIcon =
                document.createElement(
                    "i"
                );

            faqIcon.className =
                "ph-light ph-chat-centered-dots";

            faqIcon.setAttribute(
                "aria-hidden",
                "true"
            );


            const faqText =
                document.createElement(
                    "span"
                );

            faqText.textContent =
                "Add to FAQ";


            faqLink.appendChild(
                faqIcon
            );

            faqLink.appendChild(
                faqText
            );

            menuContent.appendChild(
                faqLink
            );


            menu.appendChild(
                trigger
            );

            menu.appendChild(
                menuContent
            );

            actionWrapper.appendChild(
                menu
            );
        }


        actionCell.appendChild(
            actionWrapper
        );

        row.appendChild(
            actionCell
        );


        return row;
    }


    /*
    |--------------------------------------------------------------------------
    | REALTIME FILTER MATCHING
    |--------------------------------------------------------------------------
    */

    function realtimeRequestMatchesCurrentView(
        request
    ) {
        const filters =
            getCurrentTableFilters();


        /*
         * Realtime creation only belongs to
         * the active dataset.
         */
        if (
            filters.datasetStatus !==
            "active"
        ) {
            return false;
        }


        /*
         * Normalize event status.
         */
        const requestStatus =
            safeString(
                request.status,
                "pending"
            );


        /*
         * Respect the current status filter.
         */
        if (
            filters.statusFilter &&
            requestStatus !==
            filters.statusFilter
        ) {
            return false;
        }


        /*
         * Respect the current agency filter.
         */
        if (filters.agency) {
            const requestAgency =
                safeString(
                    request.agency_id ??
                    request.agency?.id
                );

            if (
                requestAgency !==
                filters.agency
            ) {
                return false;
            }
        }


        /*
         * Respect the current search term.
         *
         * The backend searches questions.
         */
        if (filters.search) {
            const question =
                safeString(
                    request.question
                )
                    .toLowerCase();

            if (
                !question.includes(
                    filters.search
                )
            ) {
                return false;
            }
        }


        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | REALTIME SUPPORT REQUEST HANDLER
    |--------------------------------------------------------------------------
    */

    function handleRealtimeSupportRequest(
        request
    ) {
        if (
            !supportRequestsTableBody ||
            !request?.id
        ) {
            return;
        }


        /*
         * Ignore events that do not belong
         * to the current filtered view.
         */
        if (
            !realtimeRequestMatchesCurrentView(
                request
            )
        ) {
            return;
        }


        const requestId =
            safeString(
                request.id
            );


        /*
         * Prevent duplicates.
         */
        const existingRow =
            supportRequestsTableBody.querySelector(
                `tr[data-request-id="${CSS.escape(
                    requestId
                )}"]`
            );

        if (existingRow) {
            return;
        }


        /*
         * Remove the server-rendered empty state.
         */
        supportRequestsTableBody
            .querySelector(
                ".support-empty-row"
            )
            ?.remove();


        /*
         * Create the new row.
         */
        const newRow =
            createRealtimeSupportRequestRow(
                request
            );


        /*
         * Put the newest request first.
         */
        supportRequestsTableBody.prepend(
            newRow
        );


        /*
         * Visually identify the newly received row.
         */
        newRow.classList.add(
            "realtime-new-row"
        );

        window.setTimeout(
            () => {
                newRow.classList.remove(
                    "realtime-new-row"
                );
            },
            2500
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REALTIME ECHO
    |--------------------------------------------------------------------------
    */

    let realtimeInitialized =
        false;

    let realtimeRetryTimer =
        null;


    /*
     * Register the private Laravel Echo channel.
     */
    function initializeRealtime() {
        if (
            realtimeInitialized
        ) {
            return true;
        }

        if (
            !supportRequestsTableBody
        ) {
            return false;
        }

        if (
            !window.Echo
        ) {
            return false;
        }


        const channel =
            window.Echo.private(
                "admin.support-requests"
            );


        /*
         * Laravel Echo calls this when
         * the private channel subscription succeeds.
         *
         * We intentionally do not log this in production.
         */
        if (
            typeof channel.subscribed ===
            "function"
        ) {
            channel.subscribed(
                () => {}
            );
        }


        /*
         * Listen for the Laravel broadcast event.
         */
        channel.listen(
            ".support.request.created",
            event => {
                handleRealtimeSupportRequest(
                    event
                );
            }
        );


        /*
         * Register exactly one channel error handler.
         *
         * We deliberately avoid logging the complete
         * error object because it can expose implementation
         * details in production browser consoles.
         */
        if (
            typeof channel.error ===
            "function"
        ) {
            channel.error(
                () => {}
            );
        }


        realtimeInitialized =
            true;

        return true;
    }


    /*
     * Retry initialization while Echo is loading.
     *
     * This is useful when the main application JavaScript
     * initializes Echo asynchronously.
     */
    function startRealtimeInitialization() {
        if (
            initializeRealtime()
        ) {
            return;
        }


        let attempts = 0;

        const maximumAttempts =
            40;


        realtimeRetryTimer =
            window.setInterval(
                () => {
                    attempts++;

                    if (
                        initializeRealtime()
                    ) {
                        window.clearInterval(
                            realtimeRetryTimer
                        );

                        realtimeRetryTimer =
                            null;

                        return;
                    }


                    if (
                        attempts >=
                        maximumAttempts
                    ) {
                        window.clearInterval(
                            realtimeRetryTimer
                        );

                        realtimeRetryTimer =
                            null;
                    }
                },
                500
            );
    }


    /*
     * Start realtime initialization.
     */
    startRealtimeInitialization();


    /*
    |--------------------------------------------------------------------------
    | GLOBAL CLICK HANDLING
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        event => {
            /*
            |--------------------------------------------------------------------------
            | Action menu trigger
            |--------------------------------------------------------------------------
            */

            const menuTrigger =
                event.target.closest(
                    ".support-action-menu-trigger"
                );

            if (menuTrigger) {
                event.preventDefault();

                toggleActionMenu(
                    menuTrigger
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | FAQ conversion
            |--------------------------------------------------------------------------
            */

            const faqButton =
                event.target.closest(
                    ".faq-btn"
                );

            if (faqButton) {
                event.preventDefault();

                if (
                    faqButton.dataset.processing ===
                    "true"
                ) {
                    return;
                }

                faqButton.dataset.processing =
                    "true";

                checkSimilarFaqs(
                    faqButton
                ).finally(() => {
                    faqButton.dataset.processing =
                        "false";
                });

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Lifecycle actions
            |--------------------------------------------------------------------------
            */

            const lifecycleButton =
                event.target.closest(
                    ".delete-btn, .restore-btn, .permanent-delete-btn"
                );

            if (lifecycleButton) {
                event.preventDefault();

                confirmLifecycleAction(
                    lifecycleButton
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Manage Support Request
            |--------------------------------------------------------------------------
            */

            const manageButton =
                event.target.closest(
                    ".view-btn"
                );

            if (manageButton) {
                event.preventDefault();

                closeAllActionMenus();

                prepareSupportRequest(
                    manageButton
                );

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Clicking anywhere else closes action menus.
            |--------------------------------------------------------------------------
            */

            closeAllActionMenus();
        }
    );


    /*
    |--------------------------------------------------------------------------
    | KEYBOARD HANDLING
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "keydown",
        event => {
            if (
                event.key !==
                "Escape"
            ) {
                return;
            }


            closeAllActionMenus();


            /*
             * Close Similar FAQ first if it is open.
             */
            if (
                similarFaqModal?.classList.contains(
                    "active"
                )
            ) {
                closeSimilarFaqModal();

                return;
            }


            /*
             * Otherwise close the Support Request modal.
             */
            if (
                supportModal?.classList.contains(
                    "active"
                )
            ) {
                closeSupportModal();
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | FLASH SUCCESS MESSAGE
    |--------------------------------------------------------------------------
    */

    if (
        window.__FLASH_SUCCESS__
    ) {
        if (
            typeof window.showAlertModal ===
            "function"
        ) {
            window.showAlertModal({
                title:
                    "Success",

                text:
                    window.__FLASH_SUCCESS__,

                icon:
                    "ph-light ph-check-circle",

                variant:
                    "success",

                confirmText:
                    "OK",

                showCancel:
                    false,

                onConfirm: () => {
                    if (
                        typeof window.closeAlertModal ===
                        "function"
                    ) {
                        window.closeAlertModal();
                    }
                }
            });


            window.setTimeout(
                () => {
                    if (
                        typeof window.closeAlertModal ===
                        "function"
                    ) {
                        window.closeAlertModal();
                    }
                },
                1500
            );
        }

        window.__FLASH_SUCCESS__ =
            null;
    }
});