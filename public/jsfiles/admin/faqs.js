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

    const translateFaqBtn =
        document.getElementById("translateFaqBtn");


        /*
 * =========================================================
 * AGENCY SELECT OPTION TRUNCATION
 * =========================================================
 *
 * Shortens long agency names inside the native select.
 *
 * The original agency name is preserved in data-full-name,
 * so this only changes what the administrator sees.
 */
function truncateAgencyOptions() {

    /*
     * Stop safely if the agency selector is unavailable.
     */
    if (!agencySelect) {
        return;
    }


    /*
     * Measure the actual width of the select element.
     *
     * A small amount of space is reserved for the native
     * select arrow and internal browser padding.
     */
    const availableWidth =
        Math.max(
            agencySelect.clientWidth - 48,
            120
        );


    /*
     * Read the select's actual font configuration.
     *
     * This ensures the text measurement matches the
     * typography displayed inside the control.
     */
    const styles =
        window.getComputedStyle(
            agencySelect
        );


    const font =
        `${styles.fontWeight} ${styles.fontSize} ${styles.fontFamily}`;


    /*
     * Create an off-screen canvas used only for measuring
     * rendered text width.
     */
    const canvas =
        document.createElement("canvas");


    const context =
        canvas.getContext("2d");


    /*
     * Stop safely if text measurement is unavailable.
     */
    if (!context) {
        return;
    }


    context.font = font;


    /*
     * Process every agency option.
     */
    Array.from(
        agencySelect.options
    ).forEach(option => {

        /*
         * The placeholder has no full agency name,
         * so there is nothing to truncate.
         */
        if (!option.dataset.fullName) {
            return;
        }


        /*
         * Always use the original name as the source.
         *
         * This prevents repeated calls from truncating
         * an already-truncated string.
         */
        const fullName =
            option.dataset.fullName.trim();


        /*
         * If the full agency name already fits,
         * restore the complete name.
         */
        if (
            context.measureText(fullName).width
            <= availableWidth
        ) {

            option.textContent =
                fullName;

            return;
        }


        const ellipsis =
            "...";


        /*
         * Binary search finds the maximum number of
         * characters that can fit without repeatedly
         * measuring every possible substring.
         */
        let low = 0;
        let high = fullName.length;


        while (
            low < high
        ) {

            const middle =
                Math.ceil(
                    (low + high) / 2
                );


            const candidate =
                fullName.slice(
                    0,
                    middle
                ) + ellipsis;


            if (
                context.measureText(
                    candidate
                ).width <= availableWidth
            ) {

                low =
                    middle;

            } else {

                high =
                    middle - 1;

            }

        }


        /*
         * Replace only the visible option label.
         *
         * The original name remains untouched inside
         * data-full-name.
         */
        option.textContent =
            fullName.slice(
                0,
                low
            ) + ellipsis;

    });
}


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

    let userEditedKeywords = false;

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
     * AI KEYWORD SELECTION
     * =========================================================
     */

    let selectedKeywordSuggestions = new Set();

    const keywordSuggestionsBox =
        document.getElementById("keywordSuggestions");

    const keywordSuggestionList =
        document.getElementById("keywordSuggestionList");

    const addKeywordSuggestionsBtn =
        document.getElementById("addKeywordSuggestions");

    const regenerateKeywordSuggestionsBtn =
        document.getElementById(
            "regenerateKeywordSuggestions"
        );


    /*
     * =========================================================
     * KEYWORD SUGGESTION UI
     * =========================================================
     */

    function updateKeywordSelectionButton() {

        const count =
            selectedKeywordSuggestions.size;

        addKeywordSuggestionsBtn.textContent =
            `Add selected (${count})`;

        addKeywordSuggestionsBtn.disabled =
            count === 0;
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
             * Set the agency from the original
             * Support Request.
             */
            agencySelect.value =
                result.agency_id || "";


            /*
             * Fill the English FAQ fields.
             */
            questionInput.value =
                draft.question || "";

            answerInput.value =
                draft.answer || "";


            /*
             * Fill the Filipino / Taglish fields.
             */
            questionFilInput.value =
                draft.question_fil || "";

            answerFilInput.value =
                draft.answer_fil || "";


            /*
             * Use AI-generated keyword suggestions.
             *
             * Suggestions are NOT automatically inserted
             * into the final keyword field.
             *
             * The administrator must approve them.
             */
            selectedKeywordSuggestions.clear();

            keywordSuggestionList.innerHTML = "";


            const suggestions =
                Array.isArray(
                    draft.keyword_suggestions
                )
                    ? draft.keyword_suggestions
                    : [];


            suggestions.forEach(keyword => {

                const cleanKeyword =
                    String(keyword).trim();

                if (!cleanKeyword) {
                    return;
                }


                const chip =
                    document.createElement("button");

                chip.type = "button";

                chip.className =
                    "keyword-suggestion";

                chip.textContent =
                    cleanKeyword;

                chip.setAttribute(
                    "aria-pressed",
                    "false"
                );

                chip.title =
                    "Select this keyword";


                chip.addEventListener(
                    "click",
                    () => {

                        toggleKeywordSuggestion(
                            cleanKeyword,
                            chip
                        );

                    }
                );


                keywordSuggestionList.appendChild(
                    chip
                );

            });


            keywordSuggestionsBox.hidden =
                suggestions.length === 0;


            /*
             * Generate fallback keywords only when AI
             * returned no suggestions and the field is empty.
             */
            if (
                suggestions.length === 0 &&
                !keywordsInput.value.trim()
            ) {

                userEditedKeywords = false;

                generateKeywords();

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

            answerInput.dispatchEvent(
                new Event("input", {
                    bubbles: true
                })
            );

            questionFilInput.dispatchEvent(
                new Event("input", {
                    bubbles: true
                })
            );

            answerFilInput.dispatchEvent(
                new Event("input", {
                    bubbles: true
                })
            );

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
     * TOGGLE KEYWORD SUGGESTION
     * =========================================================
     */

    function toggleKeywordSuggestion(
        keyword,
        chip
    ) {

        if (
            selectedKeywordSuggestions.has(
                keyword
            )
        ) {

            selectedKeywordSuggestions.delete(
                keyword
            );

            chip.classList.remove(
                "selected"
            );

            chip.setAttribute(
                "aria-pressed",
                "false"
            );

        } else {

            selectedKeywordSuggestions.add(
                keyword
            );

            chip.classList.add(
                "selected"
            );

            chip.setAttribute(
                "aria-pressed",
                "true"
            );

        }


        updateKeywordSelectionButton();

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

                const answer =
                    answerInput.value.trim();


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
                    const response =
                        await fetch(
                            window.FAQ_TRANSLATE_URL,
                            {
                                method:
                                    "POST",

                                headers: {

                                    "Content-Type":
                                        "application/json",

                                    "Accept":
                                        "application/json",

                                    "X-CSRF-TOKEN":
                                        document.querySelector(
                                            'meta[name="csrf-token"]'
                                        )?.getAttribute(
                                            "content"
                                        )
                                },

                                body:
                                    JSON.stringify({

                                        question:
                                            question,

                                        answer:
                                            answer,

                                        keywords:
                                            keywordsInput
                                                .value
                                                .trim()

                                    })
                            }
                        );


                    /*
                     * Parse Laravel's JSON response.
                     */
                    const result =
                        await response.json();


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

                    answerFilInput.value =
                        result.translation
                            .answer_fil ||
                        "";


                    /*
                     * Read AI keyword suggestions.
                     */
                    const keywordSuggestions =
                        Array.isArray(
                            result.translation
                                .keyword_suggestions
                        )
                            ? result.translation
                                .keyword_suggestions
                            : [];


                    /*
                     * Reset previous AI selections.
                     */
                    selectedKeywordSuggestions.clear();

                    keywordSuggestionList.innerHTML =
                        "";


                    if (
                        keywordSuggestions.length > 0
                    ) {

                        keywordSuggestions.forEach(
                            keyword => {

                                const cleanKeyword =
                                    String(
                                        keyword
                                    ).trim();


                                if (
                                    !cleanKeyword
                                ) {
                                    return;
                                }


                                const chip =
                                    document.createElement(
                                        "button"
                                    );

                                chip.type =
                                    "button";

                                chip.className =
                                    "keyword-suggestion";

                                chip.textContent =
                                    cleanKeyword;

                                chip.setAttribute(
                                    "aria-pressed",
                                    "false"
                                );

                                chip.title =
                                    "Select this keyword";


                                chip.addEventListener(
                                    "click",
                                    () => {

                                        toggleKeywordSuggestion(
                                            cleanKeyword,
                                            chip
                                        );

                                    }
                                );


                                keywordSuggestionList
                                    .appendChild(
                                        chip
                                    );

                            }
                        );


                        keywordSuggestionsBox.hidden =
                            false;

                    } else {

                        keywordSuggestionsBox.hidden =
                            true;

                    }


                    updateKeywordSelectionButton();


                    /*
                     * Resize the Filipino fields immediately.
                     *
                     * JavaScript changing .value does not fire
                     * the input event automatically.
                     */
                    resizeTextarea(
                        questionFilInput
                    );

                    resizeTextarea(
                        answerFilInput
                    );


                    /*
                     * Trigger input events for any other UI
                     * functionality that listens for them.
                     */
                    questionFilInput.dispatchEvent(
                        new Event("input", {
                            bubbles: true
                        })
                    );

                    answerFilInput.dispatchEvent(
                        new Event("input", {
                            bubbles: true
                        })
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
     * ADD SELECTED KEYWORDS
     * =========================================================
     */

    if (addKeywordSuggestionsBtn) {

        addKeywordSuggestionsBtn.addEventListener(
            "click",
            function () {

                if (
                    selectedKeywordSuggestions.size === 0
                ) {
                    return;
                }


                /*
                 * Read existing comma-separated keywords.
                 */
                const existingKeywords =
                    keywordsInput.value
                        .split(",")
                        .map(keyword =>
                            keyword.trim()
                        )
                        .filter(Boolean);


                /*
                 * Combine existing keywords with
                 * administrator-approved AI suggestions.
                 */
                const combinedKeywords = [
                    ...existingKeywords,
                    ...selectedKeywordSuggestions
                ];


                /*
                 * Remove duplicates case-insensitively.
                 */
                const uniqueKeywords = [];

                const seen = new Set();


                combinedKeywords.forEach(
                    keyword => {

                        const normalized =
                            keyword.toLowerCase();


                        if (
                            !seen.has(normalized)
                        ) {

                            seen.add(
                                normalized
                            );

                            uniqueKeywords.push(
                                keyword
                            );

                        }

                    }
                );


                /*
                 * Write the approved keywords back
                 * into the textarea.
                 */
                keywordsInput.value =
                    uniqueKeywords.join(", ");


                /*
                 * The administrator intentionally approved
                 * these keywords.
                 */
                userEditedKeywords =
                    true;

                keywordsInput.dataset.auto =
                    "false";


                /*
                 * Trigger the normal input pipeline.
                 *
                 * This resizes the textarea and also lets
                 * other UI listeners react to the change.
                 */
                keywordsInput.dispatchEvent(
                    new Event("input", {
                        bubbles: true
                    })
                );


                /*
                 * Remove the keywords that were just approved
                 * from the temporary AI suggestion list.
                 */
                selectedKeywordSuggestions.forEach(keyword => {

                    const chips =
                        keywordSuggestionList.querySelectorAll(
                            ".keyword-suggestion"
                        );

                    chips.forEach(chip => {

                        if (
                            chip.textContent.trim().toLowerCase() ===
                            keyword.trim().toLowerCase()
                        ) {

                            chip.remove();

                        }

                    });

                });


                /*
                 * The approved keywords are no longer selected
                 * because their suggestion chips have been removed.
                 */
                selectedKeywordSuggestions.clear();


                /*
                 * Keep the suggestion panel open.
                 */
                keywordSuggestionsBox.hidden = false;


                /*
                 * Update the button count.
                 */
                updateKeywordSelectionButton();

            }
        );

    }


    /*
     * =========================================================
     * IMAGE RESET
     * =========================================================
     */

    function resetImageState() {

        previewImg.src = "";

        previewImg.style.display =
            "none";

        uploadPlaceholder.style.display =
            "block";

        imageInput.value = "";

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

    }


    /*
     * =========================================================
     * AI KEYWORD REGENERATION
     * =========================================================
     */

    async function regenerateKeywordSuggestions() {

        /*
         * The backend requires English question and answer
         * as the source material for keyword generation.
         */
        const question =
            questionInput.value.trim();

        const answer =
            answerInput.value.trim();


        /*
         * Do not send an incomplete FAQ to the AI.
         */
        if (
            !question ||
            !answer
        ) {

            showAlertModal({

                title:
                    "English content required",

                text:
                    "Please enter the English question and answer before generating keyword suggestions.",

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
         * Prevent duplicate AI requests.
         */
        if (
            !regenerateKeywordSuggestionsBtn ||
            regenerateKeywordSuggestionsBtn.disabled
        ) {
            return;
        }


        /*
         * Preserve the original button appearance.
         */
        const originalButtonHTML =
            regenerateKeywordSuggestionsBtn.innerHTML;


        regenerateKeywordSuggestionsBtn.disabled =
            true;


        regenerateKeywordSuggestionsBtn.innerHTML = `
            <i class="ph-light ph-spinner"></i>
            Generating...
        `;


        try {

            /*
             * Ask Laravel to generate a fresh set of
             * keyword suggestions.
             */
            const response =
                await fetch(
                    window.FAQ_KEYWORDS_URL,
                    {
                        method:
                            "POST",

                        headers: {

                            "Content-Type":
                                "application/json",

                            "Accept":
                                "application/json",

                            "X-CSRF-TOKEN":
                                document.querySelector(
                                    'meta[name="csrf-token"]'
                                )?.getAttribute(
                                    "content"
                                )
                        },

                        body:
                            JSON.stringify({

                                question:
                                    question,

                                answer:
                                    answer,

                                keywords:
                                    keywordsInput
                                        .value
                                        .trim()

                            })
                    }
                );


            /*
             * Parse Laravel's response.
             */
            const result =
                await response.json();


            /*
             * Treat both HTTP errors and application-level
             * failures as errors.
             */
            if (
                !response.ok ||
                !result.success
            ) {

                throw new Error(
                    result.message ||
                    "Keyword generation failed."
                );

            }


            /*
             * Safely read the returned suggestions.
             */
            const suggestions =
                Array.isArray(
                    result.keyword_suggestions
                )
                    ? result.keyword_suggestions
                    : [];


            /*
             * Clear previous temporary selections.
             */
            selectedKeywordSuggestions.clear();


            /*
             * Remove the previous suggestion chips.
             */
            keywordSuggestionList.innerHTML =
                "";


            /*
             * Build the new suggestion chips.
             */
            suggestions.forEach(
                keyword => {

                    const cleanKeyword =
                        String(
                            keyword
                        ).trim();


                    /*
                     * Ignore empty AI values.
                     */
                    if (
                        !cleanKeyword
                    ) {
                        return;
                    }


                    /*
                     * Create a real button rather than a
                     * clickable div.
                     *
                     * This gives us proper keyboard accessibility.
                     */
                    const chip =
                        document.createElement(
                            "button"
                        );


                    chip.type =
                        "button";


                    chip.className =
                        "keyword-suggestion";


                    /*
                     * textContent is intentionally used instead
                     * of innerHTML.
                     *
                     * This prevents AI-generated text from being
                     * interpreted as HTML.
                     */
                    chip.textContent =
                        cleanKeyword;


                    chip.setAttribute(
                        "aria-pressed",
                        "false"
                    );


                    chip.title =
                        "Select this keyword";


                    /*
                     * Clicking a chip toggles its selection.
                     */
                    chip.addEventListener(
                        "click",
                        () => {

                            toggleKeywordSuggestion(
                                cleanKeyword,
                                chip
                            );

                        }
                    );


                    keywordSuggestionList.appendChild(
                        chip
                    );

                }
            );


            /*
             * Show the suggestion container only when
             * suggestions were actually returned.
             */
            keywordSuggestionsBox.hidden =
                suggestions.length === 0;


            /*
             * Reset the Add Selected button.
             */
            updateKeywordSelectionButton();


            /*
             * Tell the administrator when no suggestions
             * were generated.
             */
            if (
                suggestions.length === 0
            ) {

                showAlertModal({

                    title:
                        "No suggestions generated",

                    text:
                        "The AI could not generate new keyword suggestions. Please try again.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });

            }


        } catch (error) {

            /*
             * Keep technical details in the browser console.
             *
             * Do not expose internal error information
             * to administrators.
             */
            console.error(
                "FAQ keyword regeneration error:",
                error
            );


            showAlertModal({

                title:
                    "Keyword generation failed",

                text:
                    "New keyword suggestions could not be generated. Please try again.",

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
             * Always restore the button, even if the request
             * fails.
             */
            regenerateKeywordSuggestionsBtn.disabled =
                false;

            regenerateKeywordSuggestionsBtn.innerHTML =
                originalButtonHTML;

        }

    }


    /*
     * =========================================================
     * REGENERATE KEYWORDS BUTTON
     * =========================================================
     */

    if (
        regenerateKeywordSuggestionsBtn
    ) {

        regenerateKeywordSuggestionsBtn.addEventListener(
            "click",
            regenerateKeywordSuggestions
        );

    }


    /*
     * =========================================================
     * KEYWORD GENERATOR
     * =========================================================
     */

    function generateKeywords() {

        /*
         * =====================================================
         * 1. READ THE CURRENT FAQ DATA
         * =====================================================
         */

        const selectedAgency =
            agencySelect.options[agencySelect.selectedIndex];

        const abbreviation =
            selectedAgency?.dataset?.abbr?.trim() || "";

        const agencyText =
            selectedAgency?.dataset?.fullName
            || selectedAgency?.text
            || "";

        const question =
            questionInput.value?.trim() || "";


        /*
         * =====================================================
         * 2. STOP IF THERE IS NO QUESTION
         * =====================================================
         */

        if (!question) {

            keywordsInput.value = "";

            keywordsInput.dataset.auto =
                "true";


            /*
             * Use the actual shared resize function.
             *
             * The previous code called
             * autoResizeTextarea(), which does not exist.
             */
            resizeTextarea(
                keywordsInput
            );

            return;
        }


        /*
         * =====================================================
         * 3. NORMALIZE THE QUESTION
         * =====================================================
         */

        const normalizedQuestion = question
            .toLowerCase()
            .replace(/[^\p{L}\p{N}\s-]/gu, " ")
            .replace(/\s+/g, " ")
            .trim();


        /*
         * =====================================================
         * 4. REMOVE COMMON QUESTION / GRAMMAR WORDS
         * =====================================================
         */

        const stopwords = new Set([
            "a",
            "an",
            "and",
            "ang",
            "are",
            "ba",
            "can",
            "do",
            "does",
            "for",
            "from",
            "how",
            "i",
            "in",
            "is",
            "it",
            "ko",
            "may",
            "my",
            "ng",
            "of",
            "on",
            "pwede",
            "sa",
            "the",
            "to",
            "what",
            "when",
            "where",
            "which",
            "who",
            "why",
            "with",
            "need"
        ]);


        /*
         * =====================================================
         * 5. CREATE CLEAN WORDS
         * =====================================================
         */

        const words = normalizedQuestion
            .split(/\s+/)
            .map(word =>
                word.replace(/^-+|-+$/g, "")
            )
            .filter(Boolean)
            .filter(word => !stopwords.has(word))
            .filter(word => word.length >= 3);


        /*
         * =====================================================
         * 6. IDENTIFY POSSIBLE CONCEPT WORDS
         * =====================================================
         */

        const conceptWords = words.filter(
            word => word.length >= 5
        );


        /*
         * =====================================================
         * 7. GENERATE PHRASES
         * =====================================================
         */

        const phrases = [];

        for (
            let i = 0;
            i < words.length;
            i++
        ) {

            const current =
                words[i];


            /*
             * Two-word phrase.
             */
            if (
                i + 1 < words.length
            ) {

                const next =
                    words[i + 1];

                if (
                    current.length >= 5 ||
                    next.length >= 5
                ) {

                    phrases.push(
                        `${current} ${next}`
                    );

                }

            }


            /*
             * Three-word phrase.
             */
            if (
                i + 2 < words.length
            ) {

                const next =
                    words[i + 1];

                const afterNext =
                    words[i + 2];

                const meaningfulCount = [
                    current,
                    next,
                    afterNext
                ].filter(
                    word => word.length >= 5
                ).length;


                if (
                    meaningfulCount >= 2
                ) {

                    phrases.push(
                        `${current} ${next} ${afterNext}`
                    );

                }

            }

        }


        /*
         * =====================================================
         * 8. BUILD CANDIDATES
         * =====================================================
         */

        const candidates = [
            abbreviation,
            ...phrases,
            ...conceptWords
        ];


        /*
         * =====================================================
         * 9. REMOVE DUPLICATES
         * =====================================================
         */

        const unique = [];

        const seen =
            new Set();


        for (
            const candidate of candidates
        ) {

            const keyword =
                candidate
                    .replace(/\s+/g, " ")
                    .trim();


            if (!keyword) {
                continue;
            }


            const normalized =
                keyword.toLowerCase();


            if (
                seen.has(normalized)
            ) {
                continue;
            }


            seen.add(normalized);

            unique.push(
                keyword
            );

        }


        /*
         * =====================================================
         * 10. LIMIT AUTOMATIC KEYWORDS
         * =====================================================
         */

        const finalKeywords =
            unique.slice(0, 15);


        /*
         * =====================================================
         * 11. UPDATE THE FORM
         * =====================================================
         */

        keywordsInput.value =
            finalKeywords.join(", ");


        keywordsInput.dataset.auto =
            "true";


        /*
         * IMPORTANT:
         *
         * JavaScript assigning .value does not automatically
         * fire an input event.
         *
         * Therefore we explicitly resize the field here.
         */
        resizeTextarea(
            keywordsInput
        );

    }


    /*
     * =========================================================
     * KEYWORD MANUAL EDIT TRACKING
     * =========================================================
     *
     * This listener only tracks whether the administrator
     * intentionally modified the keyword field.
     *
     * Resizing itself is handled by the shared listener above.
     */

    keywordsInput.addEventListener(
        "input",
        () => {

            userEditedKeywords =
                true;

            keywordsInput.dataset.auto =
                "false";

        }
    );


    /*
     * =========================================================
     * KEYWORD GENERATION TRIGGERS
     * =========================================================
     */

    agencySelect.addEventListener(
        "change",
        generateKeywords
    );

    questionInput.addEventListener(
        "input",
        generateKeywords
    );


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

        /*
 * Wait until the modal has been rendered so the
 * selector has its final width before measuring text.
 */
requestAnimationFrame(() => {
    truncateAgencyOptions();
});

        const saveBtn =
            document.querySelector(
                ".btn-save"
            );


        /*
         * Reset the form before loading
         * the requested FAQ data.
         */
        form.reset();

        resetImageState();

        resetTextareaHeights();


        /*
         * Reset keyword state.
         */
        userEditedKeywords =
            false;

        keywordsInput.dataset.auto =
            "true";


        /*
         * Reset AI keyword suggestions.
         */
        selectedKeywordSuggestions.clear();

        keywordSuggestionList.innerHTML =
            "";

        keywordSuggestionsBox.hidden =
            true;

        updateKeywordSelectionButton();


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

            title.textContent =
                "Add FAQ";


            enableInputs(true);

            saveBtn.disabled =
                false;


            previewImg.style.display =
                "none";

            uploadPlaceholder.style.display =
                "block";

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


            agencySelect.value =
                data.agency || "";


            questionInput.value =
                data.question || "";

            answerInput.value =
                data.answer || "";


            questionFilInput.value =
                data.questionFil || "";

            answerFilInput.value =
                data.answerFil || "";


            keywordsInput.value =
                data.keywords || "";


            /*
             * Existing database keywords are loaded as
             * existing content, not as a new manual edit.
             */
            keywordsInput.dataset.auto =
                "true";

            userEditedKeywords =
                false;


            /*
             * Resize all fields after loading the
             * existing FAQ.
             */
            resizeAllTextareas();


            /*
             * Generate keywords only when no keywords
             * were stored in the database.
             */
            if (
                !data.keywords ||
                !data.keywords.trim()
            ) {

                generateKeywords();

            }


            title.textContent =
                "Update FAQ";


            enableInputs(true);

            saveBtn.disabled =
                false;


            /*
             * Load existing image.
             */
            if (data.image) {

                previewImg.src =
                    `/storage/${data.image}`;

                previewImg.style.display =
                    "block";

                uploadPlaceholder.style.display =
                    "none";

            } else {

                previewImg.style.display =
                    "none";

                uploadPlaceholder.style.display =
                    "block";

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

            agencySelect.value =
                data.agency || "";


            questionInput.value =
                data.question || "";

            answerInput.value =
                data.answer || "";


            questionFilInput.value =
                data.questionFil || "";

            answerFilInput.value =
                data.answerFil || "";


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

                previewImg.src =
                    `/storage/${data.image}`;

                previewImg.style.display =
                    "block";

                uploadPlaceholder.style.display =
                    "none";

            } else {

                previewImg.style.display =
                    "none";

                uploadPlaceholder.style.display =
                    "block";

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


            title.textContent =
                "Create FAQ from Support Request";


            /*
             * Preselect the agency attached to
             * the original Support Request.
             */
            agencySelect.value =
                data.agency_id || "";


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
                        row.dataset.image

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
                        btn.dataset.image

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

            resetImageState();

            resetTextareaHeights();


            /*
             * Reset image state.
             */
            previewImg.src =
                "";

            previewImg.style.display =
                "none";

            uploadPlaceholder.style.display =
                "block";


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

                previewImg.style.display =
                    "none";

                uploadPlaceholder.style.display =
                    "block";

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
                "image/jpg"
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
                        "Only JPG and PNG images are allowed.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });


                imageInput.value =
                    "";

                return;

            }


            /*
             * Limit the client-side preview to 2MB.
             *
             * Laravel must enforce the same limit
             * server-side.
             */
            if (
                file.size >
                2 * 1024 * 1024
            ) {

                showAlertModal({

                    title:
                        "File too large",

                    text:
                        "Maximum file size is 2MB.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "OK",

                    showCancel:
                        false

                });


                imageInput.value =
                    "";

                return;

            }


            /*
             * Generate a local preview.
             */
            const reader =
                new FileReader();


            reader.onload =
                function (e) {

                    previewImg.src =
                        e.target.result;

                    previewImg.style.display =
                        "block";

                    uploadPlaceholder.style.display =
                        "none";

                };


            reader.readAsDataURL(
                file
            );

        }
    );


    /*
     * =========================================================
     * IMAGE UPLOAD BOX
     * =========================================================
     */

    const uploadBox =
        document.getElementById(
            "image-upload-box"
        );


    if (
        uploadBox &&
        imageInput
    ) {

        uploadBox.addEventListener(
            "click",
            () => {

                /*
                 * Viewing an FAQ must not allow
                 * image selection.
                 */
                if (
                    currentMode === "view"
                ) {

                    return;

                }


                imageInput.click();

            }
        );

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
             * Prevent immediate submission so the
             * administrator can confirm the operation.
             */
            e.preventDefault();


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
                     * Native form submission bypasses this
                     * submit listener and sends the form
                     * normally to Laravel.
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

    /*
 * Recalculate the visible agency names when the
 * browser viewport changes size.
 */
window.addEventListener(
    "resize",
    () => {
        truncateAgencyOptions();
    }
);

});