document.addEventListener("DOMContentLoaded", function () {

    /*
     * =========================================================
     * FAQ MODAL ELEMENTS
     * =========================================================
     *
     * Cache the DOM elements once when the page loads.
     *
     * This is more efficient than repeatedly searching the DOM
     * every time an action is performed.
     */

    const modal = document.getElementById("modal-back");
    const form = document.getElementById("faqForm");

    const title = document.getElementById("faq-modal-title");
    const methodInput = document.getElementById("faq-method");

    const agencySelect = document.getElementById("faq_agency");


    /*
 * =========================================================
 * SEARCHABLE AGENCY SELECT
 * =========================================================
 *
 * The native select remains responsible for form submission.
 *
 * The visible text input provides:
 *
 * 1. Full agency-name searching
 * 2. Abbreviation searching
 * 3. Keyboard accessibility
 * 4. Custom dropdown styling
 */

const agencySearchInput =
    document.getElementById("faq_agency_search");

const agencyOptionsContainer =
    document.getElementById("faq-agency-options");

const agencySearchableWrapper =
    document.getElementById("agency-searchable");


/*
 * Stores the currently visible agency options.
 *
 * Each item contains:
 *
 * 1. The original option value
 * 2. The full agency name
 * 3. The agency abbreviation
 * 4. A normalized search string
 */
let searchableAgencyOptions = [];


/*
 * Tracks whether the custom dropdown is active.
 */
let agencyDropdownOpen = false;


/*
 * =========================================================
 * BUILD SEARCH DATA
 * =========================================================
 *
 * Reads the existing native select options.
 *
 * This avoids duplicating agency data in JavaScript.
 */

function buildSearchableAgencyOptions() {

    if (
        !agencySelect ||
        !agencyOptionsContainer
    ) {
        return;
    }


    searchableAgencyOptions =
        Array.from(
            agencySelect.options
        )
        .filter(option => option.value !== "")
        .map(option => {

            const fullName =
                option.dataset.fullName ||
                option.textContent.trim();

            const abbreviation =
                option.dataset.abbr ||
                "";

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
                        .trim()

            };

        });

}


/*
 * =========================================================
 * OPEN DROPDOWN
 * =========================================================
 */

function openAgencyDropdown() {

    if (
        !agencySearchInput ||
        agencySearchInput.readOnly ||
        agencySearchInput.disabled
    ) {
        return;
    }


    agencyDropdownOpen =
        true;


    agencySearchableWrapper.classList.add(
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
 * =========================================================
 * CLOSE DROPDOWN
 * =========================================================
 */

function closeAgencyDropdown() {

    agencyDropdownOpen =
        false;


    if (agencySearchableWrapper) {

        agencySearchableWrapper.classList.remove(
            "is-open"
        );

    }


    if (agencySearchInput) {

        agencySearchInput.setAttribute(
            "aria-expanded",
            "false"
        );

    }

}


/*
 * =========================================================
 * RENDER FILTERED OPTIONS
 * =========================================================
 *
 * Uses textContent instead of innerHTML for agency data.
 *
 * This prevents agency names from being interpreted as HTML.
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


    const filteredOptions =
        searchableAgencyOptions.filter(
            agency => {

                return agency.searchText.includes(
                    normalizedSearch
                );

            }
        );


    agencyOptionsContainer.replaceChildren();


    if (
        filteredOptions.length === 0
    ) {

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


    filteredOptions.forEach(
        agency => {

            const optionButton =
                document.createElement("button");


            optionButton.type =
                "button";


            optionButton.className =
                "searchable-select-option";


            optionButton.setAttribute(
                "role",
                "option"
            );


            optionButton.dataset.value =
                agency.value;


            optionButton.textContent =
                agency.fullName;


            /*
             * Display the abbreviation only when available.
             */
            if (
                agency.abbreviation
            ) {

                optionButton.textContent +=
                    ` (${agency.abbreviation.toUpperCase()})`;

            }


            /*
             * Highlight the currently selected agency.
             */
            if (
                agencySelect.value === agency.value
            ) {

                optionButton.classList.add(
                    "is-selected"
                );

                optionButton.setAttribute(
                    "aria-selected",
                    "true"
                );

            } else {

                optionButton.setAttribute(
                    "aria-selected",
                    "false"
                );

            }


            optionButton.addEventListener(
                "click",
                function () {

                    selectAgency(
                        agency.value
                    );

                }
            );


            agencyOptionsContainer.appendChild(
                optionButton
            );

        }
    );

}


/*
 * =========================================================
 * SELECT AGENCY
 * =========================================================
 *
 * Updates both:
 *
 * 1. The visible search input
 * 2. The native Laravel form select
 */

function selectAgency(
    agencyValue
) {

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
                option.value === String(
                    agencyValue
                )
        );


    /*
     * Do not select values that do not exist
     * in the server-provided agency list.
     */
    if (!selectedOption) {

        agencySelect.value =
            "";

        agencySearchInput.value =
            "";

        closeAgencyDropdown();

        return;

    }


    /*
     * Update the real submitted value.
     */
    agencySelect.value =
        selectedOption.value;


    /*
     * Update the visible field.
     */
    agencySearchInput.value =
        selectedOption.dataset.fullName ||
        selectedOption.textContent.trim();


    closeAgencyDropdown();


    agencySelect.dispatchEvent(
        new Event("change", {
            bubbles: true
        })
    );

}


/*
 * =========================================================
 * SYNCHRONIZE SEARCH INPUT
 * =========================================================
 *
 * Used after form.reset(), edit mode, view mode,
 * and Support Request conversion.
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

        agencySearchInput.value =
            "";

        return;

    }


    agencySearchInput.value =
        selectedOption.dataset.fullName ||
        selectedOption.textContent.trim();

}


/*
 * =========================================================
 * SEARCH INPUT EVENTS
 * =========================================================
 */

if (agencySearchInput) {

    /*
     * Open the dropdown when the field receives focus.
     */
    agencySearchInput.addEventListener(
        "focus",
        function () {

            openAgencyDropdown();

        }
    );


    /*
     * Filter agencies while typing.
     */
    agencySearchInput.addEventListener(
        "input",
        function () {

            /*
             * Typing is searching, not selecting.
             *
             * The native select remains unchanged until
             * an actual option is clicked.
             */
            openAgencyDropdown();

            renderAgencyOptions(
                this.value
            );

        }
    );


    /*
     * Open the dropdown when clicked.
     */
    agencySearchInput.addEventListener(
        "click",
        function () {

            openAgencyDropdown();

        }
    );

}


/*
 * =========================================================
 * CLOSE WHEN CLICKING OUTSIDE
 * =========================================================
 */

document.addEventListener(
    "click",
    function (event) {

        if (
            !agencySearchableWrapper
        ) {
            return;
        }


        if (
            !agencySearchableWrapper.contains(
                event.target
            )
        ) {

            closeAgencyDropdown();

        }

    }
);


/*
 * =========================================================
 * KEYBOARD ACCESSIBILITY
 * =========================================================
 */

if (agencySearchInput) {

    agencySearchInput.addEventListener(
        "keydown",
        function (event) {

            /*
             * Escape closes the dropdown.
             */
            if (
                event.key === "Escape"
            ) {

                closeAgencyDropdown();

                return;

            }


            /*
             * Enter selects the first visible result.
             */
            if (
                event.key === "Enter" &&
                agencyDropdownOpen
            ) {

                const firstOption =
                    agencyOptionsContainer.querySelector(
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
 * =========================================================
 * INITIALIZE SEARCHABLE AGENCY SELECT
 * =========================================================
 */

buildSearchableAgencyOptions();


    const questionInput =
        document.getElementById("faq_question");

    const answerInput =
        document.getElementById("faq_answer");

    const questionFilInput =
        document.getElementById("faq_question_fil");

    const answerFilInput =
        document.getElementById("faq_answer_fil");

    const keywordsInput =
        document.getElementById("faq_keywords");

    const uploadPlaceholder =
        document.getElementById("upload-placeholder");

    const previewImg =
        document.getElementById("preview-img");

    const imageInput =
        document.getElementById("faq_image");

    const removeFaqImageBtn =
    document.getElementById("removeFaqImage");

    const removeImageInput =
    document.getElementById("removeImageInput");


    /*
 * Stores the Support Request ID when an answered
 * Support Request is being converted into an FAQ.
 *
 * Laravel uses this value to copy the original
 * Support Request answer image into the FAQ.
 */
const supportRequestIdInput =
    document.getElementById("support_request_id");

    

    const translateFaqBtn =
        document.getElementById("translateFaqBtn");




    /*
     * =========================================================
     * SUPPORT REQUEST → FAQ DATA
     * =========================================================
     */

    const supportFaqData =
        window.SUPPORT_FAQ_DATA || null;

    const supportFaqPrepareUrl =
        window.SUPPORT_FAQ_PREPARE_URL || null;


    /*
     * =========================================================
     * FAQ STATE
     * =========================================================
     */

    let currentMode = "add";


    /*
     * =========================================================
     * AUTO-RESIZE TEXTAREAS
     * =========================================================
     *
     * This is the single source of truth for textarea height.
     *
     * It is intentionally reusable so both:
     *
     * 1. User typing
     * 2. JavaScript-generated content
     *
     * use exactly the same resizing behavior.
     */

    function resizeTextarea(textarea) {

        /*
         * Stop safely if the textarea does not exist.
         */
        if (!textarea) {
            return;
        }


        /*
         * Temporarily remove the current height.
         *
         * This is important when content becomes shorter.
         * Without resetting the height first, scrollHeight
         * may remain based on the previous larger value.
         */
        textarea.style.height = "auto";


        /*
         * Read the maximum height defined by CSS.
         *
         * This keeps JavaScript synchronized with the
         * design rules defined in the stylesheet.
         */
        const styles =
            window.getComputedStyle(textarea);

        const maxHeight =
            parseFloat(styles.maxHeight);


        /*
         * Determine how much vertical space the content needs.
         */
        const requiredHeight =
            textarea.scrollHeight;


        /*
         * Never allow JavaScript to exceed the CSS limit.
         *
         * Math.min() chooses the smaller value between:
         *
         * 1. Required content height
         * 2. Maximum allowed height
         */
        const finalHeight =
            Number.isFinite(maxHeight)
                ? Math.min(
                    requiredHeight,
                    maxHeight
                )
                : requiredHeight;


        /*
         * Apply the calculated height.
         */
        textarea.style.height =
            `${finalHeight}px`;


        /*
         * Only enable vertical scrolling when the content
         * actually exceeds the maximum allowed height.
         *
         * This keeps normal short content clean.
         */
        textarea.style.overflowY =
            requiredHeight > maxHeight
                ? "auto"
                : "hidden";
    }


    /*
     * =========================================================
     * FAQ AUTO-RESIZE FIELD COLLECTION
     * =========================================================
     *
     * Every text-heavy FAQ field belongs to this collection.
     *
     * Centralizing the fields prevents duplicated resize logic.
     */

    const autoResizeFields = [
        keywordsInput,
        questionInput,
        answerInput,
        questionFilInput,
        answerFilInput
    ];


    /*
     * =========================================================
     * RESIZE ALL FAQ TEXTAREAS
     * =========================================================
     *
     * Used whenever JavaScript fills multiple FAQ fields at
     * the same time.
     *
     * Example:
     *
     * AI translation
     * Support Request conversion
     * Edit mode
     * View mode
     */

    function resizeAllTextareas() {

        autoResizeFields.forEach(field => {

            if (!field) {
                return;
            }

            resizeTextarea(field);

        });

    }


    /*
     * =========================================================
     * ATTACH AUTO-RESIZE BEHAVIOR
     * =========================================================
     *
     * The input event fires whenever an administrator types,
     * deletes, pastes, or otherwise edits the field normally.
     */

    autoResizeFields.forEach(field => {

        if (!field) {
            return;
        }

        field.addEventListener(
            "input",
            () => {

                resizeTextarea(field);

            }
        );

    });


    /*
     * =========================================================
     * RESET FAQ TEXTAREA HEIGHTS
     * =========================================================
     *
     * Returns all textareas to their compact starting height.
     *
     * The next time content is inserted, resizeTextarea()
     * will calculate the correct height again.
     */

    function resetTextareaHeights() {

        autoResizeFields.forEach(field => {

            if (!field) {
                return;
            }

            field.style.height = "38px";
            field.style.overflowY = "hidden";

        });

    }


    /*
     * =========================================================
     * SUPPORT REQUEST → FAQ PREPARATION
     * =========================================================
     */

    async function prepareSupportFaq() {

        /*
         * Make sure the server supplied the required data.
         */
        if (
            !supportFaqData ||
            !supportFaqPrepareUrl
        ) {
            return;
        }


        /*
         * Disable Save while the draft is being prepared.
         */
        const saveBtn =
            document.querySelector(".btn-save");

        if (saveBtn) {
            saveBtn.disabled = true;
        }


        /*
         * Temporarily prevent editing while the draft
         * is being generated.
         */
        enableInputs(false);


        /*
         * Give the administrator immediate feedback.
         */
        title.textContent =
            "Preparing FAQ...";


        try {

            /*
             * Only the Support Request ID is known by the
             * endpoint.
             *
             * The server retrieves the authoritative
             * Support Request information from the database.
             */
            const response = await fetch(
                supportFaqPrepareUrl,
                {
                    method: "POST",

                    headers: {

                        "Accept":
                            "application/json",

                        "X-CSRF-TOKEN":
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.getAttribute("content")
                    }
                }
            );


            /*
             * Parse Laravel's JSON response.
             */
            const result =
                await response.json();


            /*
             * Stop if the server or AI preparation failed.
             */
            if (
                !response.ok ||
                !result.success ||
                !result.draft
            ) {

                throw new Error(
                    result.message ||
                    "Unable to prepare FAQ."
                );

            }


            const draft =
                result.draft;

            /*
 * Preserve the original Support Request ID.
 *
 * This hidden field will be submitted together with
 * the new FAQ form so Laravel knows where to retrieve
 * the original answer image.
 */
if (supportRequestIdInput) {

    supportRequestIdInput.value =
        result.support_request_id || "";

}


            /*
             * Set the agency from the original
             * Support Request.
             */
            selectAgency(
    result.agency_id || ""
);


            /*
             * Fill the English FAQ fields.
             */
            questionInput.value =
                draft.question || "";

            if (window.FaqResponseBuilder) {
                window.FaqResponseBuilder.load([
                    ...(draft.answer ? [{ type: 'text', language: 'en', content: draft.answer }] : []),
                    ...(draft.answer_fil ? [{ type: 'text', language: 'fil', content: draft.answer_fil }] : [])
                ], false);
            }

            /*
             * Fill the Filipino / Taglish question field.
             */
            questionFilInput.value =
                draft.question_fil || "";

            // The Filipino answer is stored in the response builder.
            if (answerFilInput) {
                answerFilInput.value = draft.answer_fil || "";
            }

            /*
 * Display the original Support Request answer image
 * as a preview when one exists.
 *
 * This only displays the image. The actual image copy
 * will be handled securely by Laravel during saving.
 */
if (result.support_image) {

    showFaqImagePreview(
        `/storage/${result.support_image}`
    );

} else {

    resetImageState();

}


            /*
             * Resize every field after the server has
             * populated the form.
             *
             * This is important because assigning
             * textarea.value programmatically does NOT
             * automatically fire an input event.
             */
            resizeAllTextareas();


            /*
             * Trigger input events for other systems that
             * depend on the input event.
             *
             * The resize itself is already handled above.
             */
            questionInput.dispatchEvent(
                new Event("input", {
                    bubbles: true
                })
            );

            if (answerInput) {
                answerInput.dispatchEvent(
                    new Event("input", { bubbles: true })
                );
            }

            questionFilInput.dispatchEvent(
                new Event("input", { bubbles: true })
            );

            if (answerFilInput) {
                answerFilInput.dispatchEvent(
                    new Event("input", { bubbles: true })
                );
            }

            keywordsInput.dispatchEvent(
                new Event("input", {
                    bubbles: true
                })
            );


            /*
             * The AI draft is ready for human review.
             */
            title.textContent =
                "Create FAQ from Support Request";


            enableInputs(true);


            if (saveBtn) {
                saveBtn.disabled = false;
            }


            showAlertModal({

                title:
                    "FAQ draft ready",

                text:
                    "The support request has been prepared as a bilingual FAQ. Please review the information before saving.",

                icon:
                    "✓",

                variant:
                    "success",

                confirmText:
                    "Review",

                showCancel:
                    false

            });


        } catch (error) {

            console.error(
                "Support Request FAQ preparation error:",
                error
            );


            enableInputs(true);


            if (saveBtn) {
                saveBtn.disabled = false;
            }


            showAlertModal({

                title:
                    "Unable to prepare FAQ",

                text:
                    "The bilingual FAQ draft could not be generated. Please try again.",

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "OK",

                showCancel:
                    false

            });


            title.textContent =
                "Create FAQ from Support Request";

        }

    }


    /*
     * =========================================================
     * AI TRANSLATION
     * =========================================================
     */

    if (translateFaqBtn) {

        translateFaqBtn.addEventListener(
            "click",
            async function () {

                const question =
                    questionInput.value.trim();

                const englishResponseTexts =
                    Array.from(
                        document.querySelectorAll(
                            '#faq-response-english textarea[name*="[content]"]'
                        )
                    )
                    .map(field => field.value.trim())
                    .filter(Boolean);

                const answer = englishResponseTexts.join("\n\n");


                /*
                 * English content is required before
                 * translation can begin.
                 */
                if (
                    !question ||
                    !answer
                ) {

                    showAlertModal({

                        title:
                            "English content required",

                        text:
                            "Please enter the English question and answer first.",

                        icon:
                            "!",

                        variant:
                            "danger",

                        confirmText:
                            "OK",

                        showCancel:
                            false

                    });

                    return;
                }


                /*
                 * Prevent duplicate translation requests.
                 */
                if (
                    translateFaqBtn.disabled
                ) {
                    return;
                }


                const originalButtonHTML =
                    translateFaqBtn.innerHTML;


                translateFaqBtn.disabled =
                    true;


                translateFaqBtn.innerHTML = `
                    <i class="ph-light ph-spinner"></i>
                    Translating...
                `;


                try {

                    /*
                     * Send the English source content to Laravel.
                     */
                    const csrfToken =
                        document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.getAttribute("content");

                    if (!csrfToken || !window.FAQ_TRANSLATE_URL) {
                        throw new Error("Translation endpoint is not configured.");
                    }

                    const response =
                        await fetch(
                            window.FAQ_TRANSLATE_URL,
                            {
                                method: "POST",
                                credentials: "same-origin",
                                headers: {
                                    "Content-Type": "application/json",
                                    "Accept": "application/json",
                                    "X-Requested-With": "XMLHttpRequest",
                                    "X-CSRF-TOKEN": csrfToken
                                },
                                body: JSON.stringify({
                                    question,
                                    answer
                                })
                            }
                        );

                    /*
                     * Laravel may return JSON validation/errors or HTML
                     * when the request is rejected before the controller.
                     * Parse defensively so the button never fails silently.
                     */
                    const contentType =
                        response.headers.get("content-type") || "";

                    const result = contentType.includes("application/json")
                        ? await response.json()
                        : { success: false, message: `Server returned HTTP ${response.status}.` };


                    /*
                     * Handle server-side failure.
                     */
                    if (
                        !response.ok ||
                        !result.success
                    ) {

                        throw new Error(
                            result.message ||
                            "Translation failed."
                        );

                    }


                    /*
                     * Fill Filipino fields.
                     */
                    questionFilInput.value =
                        result.translation
                            .question_fil ||
                        "";

                    if (window.FaqResponseBuilder) {
                        window.FaqResponseBuilder.replaceLanguageTexts(
                            'fil',
                            [result.translation.answer_fil || '']
                        );
                    }

                    /*
                     * JavaScript changing .value does not fire the input
                     * event automatically, so refresh the question field.
                     */
                    resizeTextarea(questionFilInput);
                    questionFilInput.dispatchEvent(
                        new Event("input", { bubbles: true })
                    );


                } catch (error) {

                    console.error(
                        "FAQ translation error:",
                        error
                    );


                    showAlertModal({

                        title:
                            "Translation failed",

                        text:
                            "The AI translation could not be generated. Please try again.",

                        icon:
                            "!",

                        variant:
                            "danger",

                        confirmText:
                            "OK",

                        showCancel:
                            false

                    });


                } finally {

                    /*
                     * Always restore the translation button.
                     */
                    translateFaqBtn.disabled =
                        false;

                    translateFaqBtn.innerHTML =
                        originalButtonHTML;

                }

            }
        );

    }


    /*
     * =========================================================
     * IMAGE RESET
     * =========================================================
     */

    function resetImageState(
    markForRemoval = false
) {

    /*
     * Clear the image preview.
     */
    if (previewImg) {
        previewImg.src = "";
        previewImg.style.display = "none";
    }


    /*
     * Show the upload placeholder.
     */
    if (uploadPlaceholder) {
        uploadPlaceholder.style.display = "flex";
    }


    /*
     * Hide the X button.
     */
    if (removeFaqImageBtn) {
        removeFaqImageBtn.style.display = "none";
    }


    /*
     * Clear the selected file.
     */
    if (imageInput) {
        imageInput.value = "";
    }


    /*
     * Tell Laravel whether the saved image
     * should be removed.
     */
    if (removeImageInput) {
        removeImageInput.value =
            markForRemoval ? "1" : "0";
    }

}

function showFaqImagePreview(imageSource) {

    /*
     * Display the selected or existing image.
     */
    if (previewImg) {
        previewImg.src = imageSource;
        previewImg.style.display = "block";
    }


    /*
     * Hide the upload instructions
     * while an image is displayed.
     */
    if (uploadPlaceholder) {
        uploadPlaceholder.style.display = "none";
    }


    /*
     * Show the remove button only in Add/Edit mode.
     *
     * View mode must remain read-only.
     */
    if (removeFaqImageBtn) {

        removeFaqImageBtn.style.display =
            currentMode === "view"
                ? "none"
                : "inline-flex";

    }

}

if (removeFaqImageBtn) {

    removeFaqImageBtn.addEventListener(
        "click",
        function (event) {

            /*
             * Prevent the upload box from opening.
             */
            event.stopPropagation();


            /*
             * Do not allow removal in View mode.
             */
            if (currentMode === "view") {
                return;
            }


            /*
 * Clear the preview and mark the
 * database image for deletion.
 */
resetImageState(true);


/*
 * If this is a Support Request conversion,
 * clearing the Support Request reference prevents
 * Laravel from copying the original Support Request
 * image after the administrator removed it.
 */
if (
    currentMode === "convert" &&
    supportRequestIdInput
) {

    supportRequestIdInput.value =
        "";

}

        }
    );

}


    /*
     * =========================================================
     * SUCCESS ALERT
     * =========================================================
     */

    if (window.__FLASH_SUCCESS__) {

        showAlertModal({

            title:
                "Success",

            text:
                window.__FLASH_SUCCESS__,

            icon:
                "✓",

            variant:
                "success",

            confirmText:
                "OK",

            showCancel:
                false,

            onConfirm: () => {

                closeAlertModal();

            }

        });


        setTimeout(() => {

            closeAlertModal();

        }, 1500);


        window.__FLASH_SUCCESS__ =
            null;

    }


    /*
     * =========================================================
     * ENABLE / DISABLE INPUTS
     * =========================================================
     */

    function enableInputs(
        enable = true
    ) {

        const inputs =
            form.querySelectorAll(
                "input, textarea, select"
            );


        inputs.forEach(input => {

            /*
             * Hidden fields must remain enabled because
             * Laravel needs them during submission.
             */
            if (
                input.name === "_method" ||
                input.type === "hidden"
            ) {

                return;

            }


            /*
             * File inputs do not support readonly.
             */
            if (
                input.type === "file"
            ) {

                input.disabled =
                    !enable;

                return;

            }


            if (enable) {

                input.removeAttribute(
                    "readonly"
                );

                input.disabled =
                    false;


                if (
                    input.tagName === "SELECT"
                ) {

                    input.style.pointerEvents =
                        "auto";

                }

            } else {

                /*
                 * Text fields remain technically enabled
                 * but become readonly.
                 */
                input.setAttribute(
                    "readonly",
                    "readonly"
                );


                if (
                    input.tagName === "SELECT"
                ) {

                    input.style.pointerEvents =
                        "none";

                }

            }

        });

        /*
 * =====================================================
 * SEARCHABLE AGENCY INPUT STATE
 * =====================================================
 *
 * The native select is hidden, so the visible search input
 * must also follow the current Add/Edit/View state.
 */

if (agencySearchInput) {

    agencySearchInput.readOnly =
        !enable;

}


if (!enable) {

    closeAgencyDropdown();

}

    }


    /*
     * =========================================================
     * OPEN FAQ MODAL
     * =========================================================
     */

    function openFaqModal(
        mode = "add",
        data = null
    ) {

        currentMode =
            mode;


        if (!modal) {
            return;
        }


        /*
         * Show the modal.
         */
        modal.classList.remove(
            "hidden"
        );

        modal.style.display =
            "flex";


        /*
         * Force browser reflow so the CSS animation
         * starts correctly.
         */
        void modal.offsetWidth;


        modal.classList.add(
            "active"
        );

    

        const saveBtn =
            document.querySelector(
                ".btn-save"
            );


        /*
         * Reset the form before loading
         * the requested FAQ data.
         */
        form.reset();

/*
 * The native select resets automatically,
 * but the custom visible input does not.
 *
 * Synchronize the custom input manually.
 */
syncAgencySearchInput();

closeAgencyDropdown();

resetImageState();

resetTextareaHeights();

        if (window.FaqResponseBuilder) {
            window.FaqResponseBuilder.reset();
        }


        /*
         * =====================================================
         * ADD MODE
         * =====================================================
         */

        if (
    mode === "add"
) {

    form.action =
        "/faqs";

    methodInput.value =
        "POST";


    /*
     * Clear any previous Support Request reference.
     *
     * Without this reset, opening Add FAQ after a
     * conversion could accidentally copy an old
     * Support Request image.
     */
    if (supportRequestIdInput) {

        supportRequestIdInput.value =
            "";

    }


    title.textContent =
        "Add FAQ";


            enableInputs(true);

            saveBtn.disabled =
                false;


            previewImg.style.display =
                "none";

            uploadPlaceholder.style.display = "flex";

        }


        /*
         * =====================================================
         * EDIT MODE
         * =====================================================
         */

        if (
            mode === "edit" &&
            data
        ) {

            form.action =
                `/faqs/${data.id}`;

            methodInput.value =
                "PUT";


            selectAgency(
                data.agency || ""
            );


            questionInput.value =
                data.question || "";

            if (answerInput) {
                answerInput.value = data.answer || "";
            }


            questionFilInput.value =
                data.questionFil || "";

            if (answerFilInput) {
                answerFilInput.value = data.answerFil || "";
            }


            keywordsInput.value =
                data.keywords || "";


            /*
             * Resize all fields after loading the
             * existing FAQ.
             */
            resizeAllTextareas();


            title.textContent =
                "Update FAQ";

            if (window.FaqResponseBuilder) {
                window.FaqResponseBuilder.load(
                    buildFaqResponseComponents(data),
                    false
                );
            }

            enableInputs(true);

            saveBtn.disabled =
                false;


            /*
             * Load existing image.
             */
            if (data.image) {

    showFaqImagePreview(
        `/storage/${data.image}`
    );

} else {

    resetImageState();

}

        }


        /*
         * =====================================================
         * VIEW MODE
         * =====================================================
         */

        if (
            mode === "view" &&
            data
        ) {

            selectAgency(
    data.agency || ""
);


            questionInput.value =
                data.question || "";

            if (answerInput) {
                answerInput.value = data.answer || "";
            }


            questionFilInput.value =
                data.questionFil || "";

            if (answerFilInput) {
                answerFilInput.value = data.answerFil || "";
            }


            keywordsInput.value =
                data.keywords || "";


            /*
             * Resize every field so the administrator can
             * immediately see the complete content.
             */
            resizeAllTextareas();


            /*
             * Preserve floating-label state for keywords.
             */
            if (
                keywordsInput.value.trim() !== ""
            ) {

                keywordsInput.classList.add(
                    "has-value"
                );

            } else {

                keywordsInput.classList.remove(
                    "has-value"
                );

            }


            title.textContent =
                "View FAQ";

            if (window.FaqResponseBuilder) {
                window.FaqResponseBuilder.load(
                    data.responseComponents || [],
                    true
                );
            }


            /*
             * Viewing is read-only.
             */
            enableInputs(false);


            saveBtn.disabled =
                true;


            /*
             * Show existing image.
             */
            if (data.image) {

    showFaqImagePreview(
        `/storage/${data.image}`
    );

} else {

    resetImageState();

}

        }


        /*
         * =====================================================
         * SUPPORT REQUEST → FAQ
         * =====================================================
         */

        if (
    mode === "convert" &&
    data
) {

    /*
     * A Support Request becomes a new FAQ.
     */
    form.action =
        "/faqs";


    methodInput.value =
        "POST";


    /*
     * Store the Support Request ID immediately.
     *
     * This guarantees that the ID is available even
     * before the preparation request finishes.
     */
    if (supportRequestIdInput) {

        supportRequestIdInput.value =
            data.id || "";

    }


            title.textContent =
                "Create FAQ from Support Request";


            /*
             * Preselect the agency attached to
             * the original Support Request.
             */
            selectAgency(
    data.agency_id || ""
);


            /*
             * Disable the form while AI prepares
             * the bilingual draft.
             */
            enableInputs(false);


            saveBtn.disabled =
                true;


            prepareSupportFaq();

        }

    }


    /*
     * Make openFaqModal available to the Blade.
     */
    window.openFaqModal =
        openFaqModal;


    function parseResponseComponents(value) {
        if (!value) {
            return [];
        }

        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }


    function buildFaqResponseComponents(data) {
        const components = Array.isArray(data?.responseComponents)
            ? data.responseComponents
            : [];

        if (components.length > 0) {
            return components;
        }

        const legacy = [];

        if (data?.answer) {
            legacy.push({
                type: 'text',
                language: 'en',
                content: data.answer
            });
        }

        if (data?.answerFil) {
            legacy.push({
                type: 'text',
                language: 'fil',
                content: data.answerFil
            });
        }

        return legacy;
    }


    /*
     * =========================================================
     * FAQ ROW CLICK → VIEW
     * =========================================================
     */

    document.addEventListener(
        "click",
        function (e) {

            const row =
                e.target.closest(
                    ".faq-row"
                );


            if (!row) {
                return;
            }


            /*
             * Do not open View mode when the administrator
             * clicked an action button or form.
             */
            if (
                e.target.closest("button") ||
                e.target.closest("form")
            ) {

                return;

            }


            openFaqModal(
                "view",
                {

                    id:
                        row.dataset.id,

                    agency:
                        row.dataset.agency,

                    question:
                        row.dataset.question,

                    answer:
                        row.dataset.answer,

                    questionFil:
                        row.dataset.questionFil,

                    answerFil:
                        row.dataset.answerFil,

                    keywords:
                        row.dataset.keywords,

                    image:
                        row.dataset.image,

                    responseComponents:
                        buildFaqResponseComponents({
                            responseComponents: parseResponseComponents(row.dataset.responseComponents),
                            answer: row.dataset.answer,
                            answerFil: row.dataset.answerFil
                        })

                }
            );

        }
    );


    /*
     * =========================================================
     * EDIT BUTTON
     * =========================================================
     */

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".edit-btn"
                );


            if (!btn) {
                return;
            }


            openFaqModal(
                "edit",
                {

                    id:
                        btn.dataset.id,

                    agency:
                        btn.dataset.agency,

                    question:
                        btn.dataset.question,

                    answer:
                        btn.dataset.answer,

                    questionFil:
                        btn.dataset.questionFil,

                    answerFil:
                        btn.dataset.answerFil,

                    keywords:
                        btn.dataset.keywords,

                    image:
                        btn.dataset.image,

                    responseComponents:
                        buildFaqResponseComponents({
                            responseComponents: parseResponseComponents(btn.dataset.responseComponents),
                            answer: btn.dataset.answer,
                            answerFil: btn.dataset.answerFil
                        })

                }
            );

        }
    );


    /*
     * =========================================================
     * ADD BUTTON
     * =========================================================
     */

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".add-agencybtn"
                );


            if (!btn) {
                return;
            }


            openFaqModal(
                "add"
            );

        }
    );


    /*
     * =========================================================
     * CLOSE FAQ MODAL
     * =========================================================
     */

    function closeFaqModal() {

        modal.classList.remove(
            "active"
        );


        setTimeout(() => {

            modal.style.display =
                "none";

            modal.classList.add(
                "hidden"
            );


            form.reset();

/*
 * Reset the visible searchable agency field.
 */
syncAgencySearchInput();

closeAgencyDropdown();

resetImageState();

resetTextareaHeights();

            if (window.FaqResponseBuilder) {
                window.FaqResponseBuilder.reset();
            }


            /*
             * Reset image state.
             */
            previewImg.src =
                "";

            previewImg.style.display =
                "none";

            uploadPlaceholder.style.display = "flex";


            /*
             * Clear the file input.
             */
            imageInput.value =
                "";

        }, 200);

    }


    window.closeFaqModal =
        closeFaqModal;


    /*
     * Clicking the backdrop closes the modal.
     */
    modal.addEventListener(
        "click",
        e => {

            if (
                e.target === modal
            ) {

                closeFaqModal();

            }

        }
    );


    /*
     * Escape also closes the modal.
     */
    document.addEventListener(
        "keydown",
        e => {

            if (
                e.key === "Escape"
            ) {

                closeFaqModal();

            }

        }
    );


    /*
     * =========================================================
     * FAQ DATA RECOVERY ACTIONS
     * =========================================================
     */

    /*
     * =========================================================
     * TRASH FAQ
     * =========================================================
     */

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".delete-btn"
                );


            if (!btn) {
                return;
            }


            const deleteForm =
                btn.closest("form");


            if (!deleteForm) {
                return;
            }


            e.preventDefault();


            const question =
                btn.dataset.faqQuestion ||
                "this FAQ";


            showAlertModal({

                title:
                    "Move FAQ to trash?",

                text:
                    `"${question}" will be moved to the trash.`,

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "Move to Trash",

                showCancel:
                    true,

                onConfirm: () => {

                    deleteForm.submit();

                }

            });

        }
    );


    /*
     * =========================================================
     * RESTORE FAQ
     * =========================================================
     */

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".restore-btn"
                );


            if (!btn) {
                return;
            }


            const restoreForm =
                btn.closest("form");


            if (!restoreForm) {
                return;
            }


            e.preventDefault();


            const question =
                btn.dataset.faqQuestion ||
                "this FAQ";


            showAlertModal({

                title:
                    "Restore this FAQ?",

                text:
                    `"${question}" will be returned to the active FAQ list.`,

                icon:
                    "↶",

                variant:
                    "success",

                confirmText:
                    "Restore",

                showCancel:
                    true,

                onConfirm: () => {

                    restoreForm.submit();

                }

            });

        }
    );


    /*
     * =========================================================
     * PERMANENT DELETE
     * =========================================================
     */

    document.addEventListener(
        "click",
        function (e) {

            const btn =
                e.target.closest(
                    ".force-delete-btn"
                );


            if (!btn) {
                return;
            }


            const forceDeleteForm =
                btn.closest("form");


            if (!forceDeleteForm) {
                return;
            }


            e.preventDefault();


            const question =
                btn.dataset.faqQuestion ||
                "this FAQ";


            showAlertModal({

                title:
                    "Delete FAQ permanently?",

                text:
                    `"${question}" will be permanently deleted. This action cannot be undone.`,

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "Delete Permanently",

                showCancel:
                    true,

                onConfirm: () => {

                    forceDeleteForm.submit();

                }

            });

        }
    );


    /*
     * =========================================================
     * IMAGE UPLOAD
     * =========================================================
     */

    imageInput.addEventListener(
        "change",
        function () {

            const file =
                this.files[0];


            /*
             * No file selected.
             */
            if (!file) {

    resetImageState();

    return;

}


            /*
             * Client-side type validation.
             *
             * This improves UX only.
             *
             * Laravel MUST still validate the uploaded
             * file server-side.
             */
            const allowedTypes = [
                "image/jpeg",
                "image/png",
                "image/webp"
            ];


            if (
                !allowedTypes.includes(
                    file.type
                )
            ) {

                showAlertModal({

                    title:
                        "Invalid file type",

                    text:
                        "Only JPG, PNG, and WebP images are allowed.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });


                resetImageState();

return;

            }


            /*
 * Limit the client-side preview to 5MB.
 *
 * Laravel must enforce the same limit
 * server-side.
 */
            if (
                file.size >
                5 * 1024 * 1024
            ) {

                showAlertModal({

                    title:
                        "File too large",

                    text:
                        "Maximum file size is 5MB.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });


                resetImageState();

return;

            }


            /*
             * Generate a local preview.
             */
            const reader =
                new FileReader();


            reader.onload =
    function (e) {

        /*
         * A newly selected image should not be marked
         * for deletion.
         */
        if (removeImageInput) {
            removeImageInput.value = "0";
        }


        /*
         * Display the newly selected image.
         */
        showFaqImagePreview(
            e.target.result
        );

    };


            reader.readAsDataURL(
                file
            );

        }
    );


    /*
 * =========================================================
 * DUPLICATE FAQ VALIDATION
 * =========================================================
 *
 * Performs a frontend duplicate check before the form
 * confirmation modal is displayed.
 *
 * The backend check remains authoritative because:
 *
 * 1. The browser only sees FAQs on the current page.
 * 2. Another administrator may create the same FAQ.
 * 3. Frontend validation can be bypassed.
 */

function normalizeFaqValue(value) {

    /*
     * Convert null/undefined values into an empty string.
     *
     * trim() removes unnecessary spaces.
     *
     * toLocaleLowerCase() makes comparison
     * case-insensitive for the current locale.
     */
    return String(value ?? "")
        .trim()
        .toLocaleLowerCase();

}


function getCurrentFaqId() {

    /*
     * During edit mode, the current FAQ must be excluded
     * from the duplicate comparison.
     */
    if (
        currentMode === "edit" &&
        methodInput.value === "PUT"
    ) {

        const action =
            form.getAttribute("action") || "";

        const match =
            action.match(/\/faqs\/(\d+)$/);

        return match
            ? match[1]
            : null;

    }

    return null;

}


function findDuplicateFaq() {

    /*
     * Read and normalize the values currently entered
     * into the form.
     */
    const agencyId =
        String(agencySelect.value || "");

    const question =
        normalizeFaqValue(
            questionInput.value
        );

    const answer =
        normalizeFaqValue(
            Array.from(
                document.querySelectorAll('#faq-response-english textarea[name*="[content]"]')
            )
            .map(field => field.value)
            .filter(Boolean)
            .join("\n\n")
        );

    const keywords =
        normalizeFaqValue(
            keywordsInput.value
        );


    /*
     * Do not run duplicate comparison against
     * incomplete FAQ data.
     *
     * Laravel will perform the required-field validation.
     */
    if (
        !agencyId ||
        !question ||
        !answer
    ) {

        return null;

    }


    /*
     * Read the FAQ rows currently rendered on the page.
     *
     * This is only an early UX check.
     * Laravel still performs the real database check.
     */
    const faqRows =
        document.querySelectorAll(
            ".faq-row"
        );


    const currentFaqId =
        getCurrentFaqId();


    for (
        const row of faqRows
    ) {

        const rowId =
            String(
                row.dataset.id || ""
            );


        /*
         * Do not compare an FAQ against itself
         * while editing that FAQ.
         */
        if (
            currentFaqId &&
            rowId === currentFaqId
        ) {

            continue;

        }


        const rowAgency =
            String(
                row.dataset.agency || ""
            );


        const rowQuestion =
            normalizeFaqValue(
                row.dataset.question
            );

        const rowAnswer =
            normalizeFaqValue(
                row.dataset.answer
            );

        const rowKeywords =
            normalizeFaqValue(
                row.dataset.keywords
            );


        /*
         * A duplicate requires all four values
         * to match exactly after normalization:
         *
         * 1. Agency
         * 2. English question
         * 3. English answer
         * 4. Keywords
         */
        const isDuplicate =
            rowAgency === agencyId &&
            rowQuestion === question &&
            rowAnswer === answer &&
            rowKeywords === keywords;


        if (isDuplicate) {

            return {

                id:
                    rowId,

                question:
                    row.dataset.question ||
                    "This FAQ"

            };

        }

    }


    /*
     * No duplicate was found among the
     * currently visible FAQ rows.
     */
    return null;

}


    /*
 * =========================================================
 * CONFIRM SAVE
 * =========================================================
 */

form.addEventListener(
    "submit",
    function (e) {

        /*
         * Stop the browser's normal submission first.
         *
         * This allows validation and confirmation to run
         * before Laravel receives the request.
         */
        e.preventDefault();


        /*
         * Run the frontend duplicate check.
         */
        const duplicateFaq =
            findDuplicateFaq();


        /*
         * Stop immediately when a duplicate is detected.
         *
         * The existing shared alert modal is reused.
         */
        if (duplicateFaq) {

            showAlertModal({

                title:
                    "Duplicate FAQ detected",

                text:
                    "An FAQ with the same agency, question, answer, and keywords already exists. Please review the existing FAQ instead of creating another copy.",

                icon:
                    "!",

                variant:
                    "danger",

                confirmText:
                    "OK",

                showCancel:
                    false

            });


            /*
             * Do not display the Save confirmation modal.
             */
            return;

        }


        /*
         * No duplicate was found among the currently
         * visible FAQ rows, so request confirmation.
         *
         * Laravel will still perform the authoritative
         * database duplicate check.
         */
        showAlertModal({

            title:
                "Save changes?",

            text:
                "Make sure all information is correct.",

            icon:
                "✓",

            variant:
                "success",

            confirmText:
                "Save",

            showCancel:
                true,

            onConfirm: () => {

                /*
                 * Native form.submit() bypasses this submit
                 * event listener and submits the form normally.
                 */
                form.submit();

            }

        });

    }
);


    /*
     * =========================================================
     * AUTO OPEN SUPPORT FAQ
     * =========================================================
     */

    if (
        supportFaqData
    ) {

        openFaqModal(
            "convert",
            supportFaqData
        );

    }

    

});