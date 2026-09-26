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

    /*
 * Stores the Support Request ID when a completed
 * Support Request is being converted into an FAQ.
 *
 * Laravel uses this value to validate and import any
 * selected response attachments server-side.
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
     * Keyword generation is intentionally local and deterministic.
     * It is not an AI suggestion service. The administrator can still
     * edit the generated comma-separated keywords at any time.
     */
    let keywordsManuallyEdited = false;
    let lastAutoKeywords = "";
    let keywordGenerationTimer = null;

    const KEYWORD_STOP_WORDS = new Set([
        'a', 'an', 'and', 'are', 'as', 'at', 'be', 'can', 'could', 'do',
        'does', 'for', 'from', 'get', 'how', 'i', 'in', 'is', 'it', 'may',
        'me', 'my', 'of', 'on', 'or', 'please', 'the', 'there', 'this',
        'to', 'what', 'when', 'where', 'which', 'who', 'why', 'with',
        'you', 'your', 'pwede', 'po', 'ba', 'ang', 'ng', 'mga', 'sa',
        'para', 'ako', 'ko', 'ano', 'saan', 'paano', 'kailan', 'mayroon',
        'meron', 'mag', 'na', 'at', 'ito', 'iyon', 'yung', 'naman', 'lang'
    ]);

    function generateKeywordsFromQuestion(question) {
        const cleaned = String(question || '')
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s-]/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();

        if (!cleaned) return '';

        const words = cleaned
            .split(' ')
            .map(word => word.replace(/^-+|-+$/g, ''))
            .filter(word => word.length >= 3 && !KEYWORD_STOP_WORDS.has(word));

        const used = new Set();
        const keywords = [];

        // Prefer meaningful two-word concepts. Every word consumed by a
        // phrase is then unavailable to later keywords.
        for (let index = 0; index < words.length - 1 && keywords.length < 4; index += 1) {
            const first = words[index];
            const second = words[index + 1];
            if (!first || !second || used.has(first) || used.has(second)) continue;

            keywords.push(`${first} ${second}`);
            used.add(first);
            used.add(second);
        }

        // Add remaining meaningful words without ever repeating a word.
        for (const word of words) {
            if (keywords.length >= 8) break;
            if (used.has(word)) continue;
            keywords.push(word);
            used.add(word);
        }

        return keywords.join(', ');
    }

    function normalizeKeywordField(value) {
        const seen = new Set();
        const output = [];

        String(value || '')
            .split(/[,\n]+/)
            .forEach(entry => {
                const words = entry
                    .trim()
                    .split(/\s+/)
                    .map(word => word.replace(/^[.,;:!?()[\]{}"']+|[.,;:!?()[\]{}"']+$/g, ''))
                    .filter(Boolean);

                const uniqueWords = [];
                words.forEach(word => {
                    const key = word.toLocaleLowerCase();
                    if (seen.has(key)) return;
                    seen.add(key);
                    uniqueWords.push(word);
                });

                if (uniqueWords.length) output.push(uniqueWords.join(' '));
            });

        return output.join(', ');
    }

    function autoFillKeywordsFromQuestion() {
        if (!keywordsInput || !questionInput) {
            return;
        }

        const currentKeywords = keywordsInput.value.trim();

        /* Never overwrite an administrator's manual keyword edits. */
        if (
            keywordsManuallyEdited &&
            currentKeywords !== lastAutoKeywords
        ) {
            return;
        }

        const generated = generateKeywordsFromQuestion(questionInput.value);
        lastAutoKeywords = generated;
        keywordsInput.value = generated;
        keywordsInput.dispatchEvent(new Event('input', { bubbles: true }));
    }

    function scheduleKeywordGeneration() {
        window.clearTimeout(keywordGenerationTimer);
        keywordGenerationTimer = window.setTimeout(
            autoFillKeywordsFromQuestion,
            180
        );
    }


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


    if (keywordsInput) {
        keywordsInput.addEventListener('input', () => {
            const current = keywordsInput.value.trim();

            if (current !== lastAutoKeywords) {
                keywordsManuallyEdited = true;
            }
        });

        keywordsInput.addEventListener('blur', () => {
            const normalized = normalizeKeywordField(keywordsInput.value);
            keywordsInput.value = normalized;
            lastAutoKeywords = normalized;
        });
    }

    if (questionInput) {
        questionInput.addEventListener('input', scheduleKeywordGeneration);
    }


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
 * This hidden field is submitted with the FAQ form so
 * Laravel can securely validate source response attachments.
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
                window.FaqResponseBuilder.load(
                    draft.response_components || [],
                    false
                );
            }

            /*
             * Fill the Filipino / Taglish question field.
             */
            questionFilInput.value =
                draft.question_fil || "";

            /*
             * Let the normal question-input flow generate the
             * manual keyword field from the English question.
             */
            keywordsManuallyEdited = false;
            lastAutoKeywords = "";
            autoFillKeywordsFromQuestion();


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

        keywordsManuallyEdited = false;
        lastAutoKeywords = "";
        window.clearTimeout(keywordGenerationTimer);

/*
 * The native select resets automatically,
 * but the custom visible input does not.
 *
 * Synchronize the custom input manually.
 */
syncAgencySearchInput();

closeAgencyDropdown();

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
     * conversion could accidentally reuse an old
     * Support Request reference.
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

            keywordsManuallyEdited = false;
            lastAutoKeywords = keywordsInput.value.trim();


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
                    false,
                    data.id
                );
            }

            enableInputs(true);

            saveBtn.disabled =
                false;


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
                    buildFaqResponseComponents(data),
                    true,
                    data.id
                );
            }


            /*
             * Viewing is read-only.
             */
            enableInputs(false);


            saveBtn.disabled =
                true;


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
        if (Array.isArray(value)) {
            return value;
        }

        if (typeof value !== 'string' || value.trim() === '') {
            return [];
        }

        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
            console.warn('Unable to parse FAQ response components.', error);
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

resetTextareaHeights();

            if (window.FaqResponseBuilder) {
                window.FaqResponseBuilder.reset();
            }


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