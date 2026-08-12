document.addEventListener("DOMContentLoaded", function () {

    const modal = document.getElementById("modal-back");
    const form = document.getElementById("faqForm");

    const title = document.getElementById("faq-modal-title");
    const methodInput = document.getElementById("faq-method");

    const agencySelect = document.getElementById("faq_agency");
    const questionInput = document.getElementById("faq_question");
    const answerInput = document.getElementById("faq_answer");

    const questionFilInput = document.getElementById("faq_question_fil");
    const answerFilInput = document.getElementById("faq_answer_fil");

    const keywordsInput = document.getElementById("faq_keywords");

    const uploadPlaceholder = document.getElementById("upload-placeholder");
    const previewImg = document.getElementById("preview-img");
    const imageInput = document.getElementById("faq_image");
    const translateFaqBtn = document.getElementById("translateFaqBtn");

    /*
 * Support Request → FAQ conversion data.
 *
 * These variables are injected by the FAQ Blade.
 * They are null during normal FAQ management.
 */
const supportFaqData =
    window.SUPPORT_FAQ_DATA || null;

const supportFaqPrepareUrl =
    window.SUPPORT_FAQ_PREPARE_URL || null;


    let userEditedKeywords = false;

    let currentMode = "add";

    // ================= AI KEYWORD SELECTION =================

let selectedKeywordSuggestions = new Set();

const keywordSuggestionsBox =
    document.getElementById("keywordSuggestions");

const keywordSuggestionList =
    document.getElementById("keywordSuggestionList");

const addKeywordSuggestionsBtn =
    document.getElementById("addKeywordSuggestions");


    // ================= KEYWORD SUGGESTION UI =================

function updateKeywordSelectionButton() {

    const count = selectedKeywordSuggestions.size;

    addKeywordSuggestionsBtn.textContent =
        `Add selected (${count})`;

    addKeywordSuggestionsBtn.disabled =
        count === 0;
}


/**
 * ================= SUPPORT REQUEST → FAQ =================
 *
 * Generates a bilingual FAQ draft from the selected
 * Support Request.
 *
 * IMPORTANT:
 * This does NOT save anything.
 * It only fills the existing FAQ form.
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
     * The Save button should remain disabled while
     * the AI is preparing the draft.
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
     * Give the admin immediate visual feedback.
     */
    title.textContent =
        "Preparing FAQ...";

    try {

        /*
         * The browser sends only the CSRF token.
         *
         * The actual question and answer remain on
         * the server and are retrieved by ID.
         */
        const response = await fetch(
            supportFaqPrepareUrl,
            {
                method: "POST",

                headers: {
                    "Accept": "application/json",

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
         *
         * The admin can still change it afterwards.
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
         * Fill the Filipino/Taglish fields.
         */
        questionFilInput.value =
            draft.question_fil || "";

        answerFilInput.value =
            draft.answer_fil || "";

        /*
         * Use the AI-generated keyword suggestions.
         *
         * We do NOT automatically insert them into
         * the final keyword field.
         *
         * The admin must approve the suggestions.
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

        updateKeywordSelectionButton();

        /*
         * Generate a basic fallback keyword set
         * only when the AI returned no suggestions.
         */
        if (
            suggestions.length === 0 &&
            !keywordsInput.value.trim()
        ) {
            userEditedKeywords = false;
            generateKeywords();
        }

        /*
         * Trigger input events so the floating-label
         * system recognizes populated fields.
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
         * The AI draft is now ready for human review.
         */
        title.textContent =
            "Create FAQ from Support Request";

        enableInputs(true);

        if (saveBtn) {
            saveBtn.disabled = false;
        }

        /*
         * Tell the admin what happened.
         */
        showAlertModal({
            title: "FAQ draft ready",
            text:
                "The support request has been prepared as a bilingual FAQ. Please review the information before saving.",
            icon: "✓",
            variant: "success",
            confirmText: "Review",
            showCancel: false
        });

    } catch (error) {

        console.error(
            "Support Request FAQ preparation error:",
            error
        );

        /*
         * Re-enable the form so the admin isn't
         * trapped inside a disabled modal.
         */
        enableInputs(true);

        if (saveBtn) {
            saveBtn.disabled = false;
        }

        showAlertModal({
            title: "Unable to prepare FAQ",
            text:
                "The bilingual FAQ draft could not be generated. Please try again.",
            icon: "!",
            variant: "danger",
            confirmText: "OK",
            showCancel: false
        });

        /*
         * Restore a useful modal title.
         */
        title.textContent =
            "Create FAQ from Support Request";
    }
}

function toggleKeywordSuggestion(keyword, chip) {

    if (selectedKeywordSuggestions.has(keyword)) {

        selectedKeywordSuggestions.delete(keyword);

        chip.classList.remove("selected");

        chip.setAttribute("aria-pressed", "false");

    } else {

        selectedKeywordSuggestions.add(keyword);

        chip.classList.add("selected");

        chip.setAttribute("aria-pressed", "true");
    }

    updateKeywordSelectionButton();
}

    // ================= AI TRANSLATION =================

if (translateFaqBtn) {

    translateFaqBtn.addEventListener("click", async function () {

        const question = questionInput.value.trim();
        const answer = answerInput.value.trim();

        // -----------------------------------------
        // 1. Validate English source content
        // -----------------------------------------

        if (!question || !answer) {

            showAlertModal({
                title: "English content required",
                text: "Please enter the English question and answer first.",
                icon: "!",
                variant: "danger",
                confirmText: "OK",
                showCancel: false
            });

            return;
        }

        // -----------------------------------------
        // 2. Prevent duplicate requests
        // -----------------------------------------

        if (translateFaqBtn.disabled) {
            return;
        }

        const originalButtonHTML = translateFaqBtn.innerHTML;

        translateFaqBtn.disabled = true;

        translateFaqBtn.innerHTML = `
            <i class="ph-light ph-spinner"></i>
            Translating...
        `;

        try {

            // -----------------------------------------
            // 3. Send request to Laravel
            // -----------------------------------------

            const response = await fetch(
                window.FAQ_TRANSLATE_URL,
                {
                    method: "POST",

                    headers: {
                        "Content-Type": "application/json",
                        "Accept": "application/json",
                        "X-CSRF-TOKEN":
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.getAttribute("content")
                    },

                    body: JSON.stringify({
                        question: question,
                        answer: answer,
                        keywords: keywordsInput.value.trim()
                    })
                }
            );

            // -----------------------------------------
            // 4. Parse Laravel response
            // -----------------------------------------

            const result = await response.json();

            // -----------------------------------------
            // 5. Handle server-side failure
            // -----------------------------------------

            if (!response.ok || !result.success) {

                throw new Error(
                    result.message ||
                    "Translation failed."
                );
            }

            // -----------------------------------------
            // 6. Put AI draft into Filipino fields
            // -----------------------------------------

            questionFilInput.value =
                result.translation.question_fil || "";

            answerFilInput.value =
                result.translation.answer_fil || "";

                
const keywordSuggestions =
    Array.isArray(
        result.translation.keyword_suggestions
    )
        ? result.translation.keyword_suggestions
        : [];

// Reset previous AI selections.
selectedKeywordSuggestions.clear();

keywordSuggestionList.innerHTML = "";

if (keywordSuggestions.length > 0) {

    keywordSuggestions.forEach(keyword => {

        const cleanKeyword =
            String(keyword).trim();

        // Ignore empty suggestions.
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

    keywordSuggestionsBox.hidden = false;

} else {

    keywordSuggestionsBox.hidden = true;
}

updateKeywordSelectionButton();



            // -----------------------------------------
            // 7. Notify floating-label system
            // -----------------------------------------

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
                title: "Translation failed",
                text: "The AI translation could not be generated. Please try again.",
                icon: "!",
                variant: "danger",
                confirmText: "OK",
                showCancel: false
            });

        } finally {

            // -----------------------------------------
            // 8. Always restore the button
            // -----------------------------------------

            translateFaqBtn.disabled = false;

            translateFaqBtn.innerHTML =
                originalButtonHTML;
        }

    });

}

// ================= ADD SELECTED KEYWORDS =================

if (addKeywordSuggestionsBtn) {

    addKeywordSuggestionsBtn.addEventListener(
        "click",
        function () {

            if (
                selectedKeywordSuggestions.size === 0
            ) {
                return;
            }

            const existingKeywords =
                keywordsInput.value
                    .split(",")
                    .map(keyword =>
                        keyword.trim()
                    )
                    .filter(Boolean);

            const combinedKeywords = [
                ...existingKeywords,
                ...selectedKeywordSuggestions
            ];

            const uniqueKeywords = [];

            const seen = new Set();

            combinedKeywords.forEach(keyword => {

                const normalized =
                    keyword.toLowerCase();

                if (!seen.has(normalized)) {

                    seen.add(normalized);

                    uniqueKeywords.push(
                        keyword
                    );
                }
            });

            keywordsInput.value =
                uniqueKeywords.join(", ");

            // The admin intentionally modified
            // the keyword field.
            userEditedKeywords = true;

            keywordsInput.dataset.auto = "false";

            keywordsInput.dispatchEvent(
                new Event("input", {
                    bubbles: true
                })
            );

            // Clear the current selection.
            selectedKeywordSuggestions.clear();

            // Hide suggestions after approval.
            keywordSuggestionsBox.hidden = true;

            updateKeywordSelectionButton();
        }
    );
}

    function resetImageState(){
        previewImg.src = "";
        previewImg.style.display = "none";
        uploadPlaceholder.style.display = "block";
        imageInput.value = "";
    }

    // ================= SUCCESS ALERT (SAME AS NGA) =================
    if (window.__FLASH_SUCCESS__) {

        showAlertModal({
            title: "Success",
            text: window.__FLASH_SUCCESS__,
            icon: "✓",
            variant: "success",
            confirmText: "OK",
            showCancel: false,

            onConfirm: () => {
                closeAlertModal();
            }
        });

        // 🔥 Auto close after 1.5s (SaaS feel)
        setTimeout(() => {
            closeAlertModal();
        }, 1500);

        // 🔒 Clear to prevent repeat
        window.__FLASH_SUCCESS__ = null;
    }

    // ================= ENABLE / DISABLE INPUTS =================
    function enableInputs(enable = true) {

    const inputs =
        form.querySelectorAll(
            "input, textarea, select"
        );

    inputs.forEach(input => {

        /*
         * Hidden fields are never disabled because
         * Laravel still needs them during submission.
         */
        if (
            input.name === "_method" ||
            input.type === "hidden"
        ) {
            return;
        }

        /*
         * File inputs do not support readonly.
         *
         * They must be disabled explicitly when
         * the form is temporarily locked.
         */
        if (input.type === "file") {

            input.disabled = !enable;

            return;
        }

        if (enable) {

            input.removeAttribute("readonly");
            input.disabled = false;

            if (input.tagName === "SELECT") {
                input.style.pointerEvents = "auto";
            }

        } else {

            /*
             * Text fields remain technically enabled so
             * their values can still be manipulated by
             * the application, but the administrator
             * cannot edit them.
             */
            input.setAttribute(
                "readonly",
                "readonly"
            );

            if (input.tagName === "SELECT") {
                input.style.pointerEvents = "none";
            }
        }
    });
}

    // ================= KEYWORD GENERATOR =================
    function generateKeywords(){

        if (userEditedKeywords) return;

        const selectedOption = agencySelect.options[agencySelect.selectedIndex];

        const agencyText = selectedOption?.text || "";
        const agencyAbbr = selectedOption?.dataset.abbr || "";
        const questionText = questionInput.value || "";

        const raw = `${agencyText} ${agencyAbbr} ${questionText}`
            .toLowerCase()
            .replace(/[^\w\s]/g, '')
            .replace(/\b(how|what|where|when|why|is|are|to|for|the)\b/g, '')
            .replace(/\s+/g, ' ')
            .trim();

        const words = raw.split(" ");
        const unique = [...new Set(words)];

        keywordsInput.value = unique.join(", ");

        // 🔥 MARK AS AUTO GENERATED
        keywordsInput.dataset.auto = "true";
    }

    keywordsInput.addEventListener("input", () => {
    userEditedKeywords = true;
    keywordsInput.dataset.auto = "false";
});

    agencySelect.addEventListener("change", generateKeywords);
    questionInput.addEventListener("input", generateKeywords);

    // ================= OPEN MODAL =================
    // ================= OPEN MODAL =================
    function openFaqModal(mode = 'add', data = null) {

        currentMode = mode;

        if (!modal) return;

        // 🔥 HARD SHOW (no dependency on CSS bugs)
        modal.classList.remove("hidden");
        modal.style.display = "flex";

        // force reflow for animation (professional trick)
        void modal.offsetWidth;

        modal.classList.add("active");

        const saveBtn = document.querySelector('.btn-save');

        // RESET
        form.reset();
        resetImageState();

        userEditedKeywords = false;
        keywordsInput.dataset.auto = "true";

        // Reset AI keyword suggestions.
        selectedKeywordSuggestions.clear();

        keywordSuggestionList.innerHTML = "";

        keywordSuggestionsBox.hidden = true;

        updateKeywordSelectionButton();

        // ================= ADD =================
        if (mode === 'add') {

            form.action = "/faqs";
            methodInput.value = "POST";
            title.textContent = "Add FAQ";

            

            enableInputs(true);
            saveBtn.disabled = false;

            if(mode === 'add'){
                previewImg.style.display = "none";
                uploadPlaceholder.style.display = "block";
            }
        }

        // ================= EDIT =================
        if (mode === 'edit' && data) {

            form.action = `/faqs/${data.id}`;
            methodInput.value = "PUT";

            agencySelect.value = data.agency || "";

            questionInput.value = data.question || "";
            answerInput.value = data.answer || "";

            questionFilInput.value = data.questionFil || "";
            answerFilInput.value = data.answerFil || "";

            keywordsInput.value = data.keywords || "";

// 🔥 FIX STARTS HERE
keywordsInput.dataset.auto = "true";
userEditedKeywords = false;

// 🔥 re-run generator safely
if (!data.keywords || data.keywords.trim() === "") {
    generateKeywords();
}

            title.textContent = "Update FAQ";

            enableInputs(true);
            saveBtn.disabled = false;

            if(mode === 'edit' && data){

                // existing code...

                if (data.image) {
                    previewImg.src = `/storage/${data.image}`;
                    previewImg.style.display = "block";
                    uploadPlaceholder.style.display = "none";
                } else {
                    previewImg.style.display = "none";
                    uploadPlaceholder.style.display = "block";
                }
            }
        }

        // ================= VIEW =================
                // ================= VIEW =================
        if (mode === 'view' && data) {

            agencySelect.value = data.agency || "";

            questionInput.value = data.question || "";
            answerInput.value = data.answer || "";

            questionFilInput.value = data.questionFil || "";
            answerFilInput.value = data.answerFil || "";

            keywordsInput.value = data.keywords || "";

            // Force the floating-label state when
            // the keyword field already contains data.
            if (keywordsInput.value.trim() !== "") {
                keywordsInput.classList.add("has-value");
            } else {
                keywordsInput.classList.remove("has-value");
            }

            title.textContent = "View FAQ";

            // Disable editing while viewing.
            enableInputs(false);

            // Viewing an FAQ should never allow saving.
            saveBtn.disabled = true;

            // Show the existing image when available.
            if (data.image) {
                previewImg.src = `/storage/${data.image}`;
                previewImg.style.display = "block";
                uploadPlaceholder.style.display = "none";
            } else {
                previewImg.style.display = "none";
                uploadPlaceholder.style.display = "block";
            }
        }

        // ================= SUPPORT REQUEST → FAQ =================
        if (mode === 'convert' && data) {

            // A Support Request becomes a new FAQ,
            // so use the normal FAQ creation endpoint.
            form.action = "/faqs";

            // Laravel expects POST for creating a new FAQ.
            methodInput.value = "POST";

            title.textContent =
                "Create FAQ from Support Request";

            // Preselect the agency attached to the
            // original Support Request.
            agencySelect.value =
                data.agency_id || "";

            // Prevent editing while AI prepares
            // the bilingual FAQ draft.
            enableInputs(false);

            // Prevent premature saving.
            saveBtn.disabled = true;

            // Ask Laravel to prepare the bilingual draft.
            prepareSupportFaq();
        }
    }

    // Make the function available to the Blade
    // because the Add FAQ button calls it inline.
    window.openFaqModal = openFaqModal;



    // ================= ROW CLICK (VIEW) =================
    document.addEventListener('click', function (e) {

        const row = e.target.closest('.faq-row');
        if (!row) return;

        if (e.target.closest('button') || e.target.closest('form')) return;

        openFaqModal('view', {
            id: row.dataset.id,
            agency: row.dataset.agency,

            question: row.dataset.question,
            answer: row.dataset.answer,

            questionFil: row.dataset.questionFil,
            answerFil: row.dataset.answerFil,

            keywords: row.dataset.keywords,
            image: row.dataset.image
        });
    });

    // ================= EDIT BUTTON =================
    document.addEventListener('click', function(e){

        const btn = e.target.closest('.edit-btn');
        if(!btn) return;

        openFaqModal('edit', {
            id: btn.dataset.id,
            agency: btn.dataset.agency,

            question: btn.dataset.question,
            answer: btn.dataset.answer,

            questionFil: btn.dataset.questionFil,
            answerFil: btn.dataset.answerFil,

            keywords: btn.dataset.keywords,
            image: btn.dataset.image
        });
    });

    // ================= ADD BUTTON =================
    document.addEventListener('click', function(e){

        const btn = e.target.closest('.add-agencybtn');
        if(!btn) return;

        openFaqModal('add');
    });

    
    // ================= CLOSE =================
    function closeFaqModal(){

        modal.classList.remove("active");

        setTimeout(() => {
            modal.style.display = "none";
            modal.classList.add("hidden");

            form.reset();
            resetImageState();

            // 🔥 RESET IMAGE STATE
            previewImg.src = "";
            previewImg.style.display = "none";
            uploadPlaceholder.style.display = "block";

            imageInput.value = ""; // 🔒 reset file input
        }, 200);
    }

    window.closeFaqModal = closeFaqModal;

    modal.addEventListener("click", e => {
        if (e.target === modal) closeFaqModal();
    });

    document.addEventListener("keydown", e => {
        if (e.key === "Escape") closeFaqModal();
    });

    // ================= DELETE (ALERT MODAL LIKE NGA) =================
    document.addEventListener('click', function(e){

        const btn = e.target.closest('.delete-btn');
        if (!btn) return;

        const deleteForm = btn.closest('form');
        if (!deleteForm) return;

        e.preventDefault();

        showAlertModal({
            title: "Delete this FAQ?",
            text: "This action cannot be undone.",
            icon: "!",
            variant: "danger",
            confirmText: "Delete",
            showCancel: true,

            onConfirm: () => {
                deleteForm.submit();
            }
        });

    });




imageInput.addEventListener("change", function(){

    const file = this.files[0];

    // ================= EMPTY FILE =================
    if (!file) {
        previewImg.style.display = "none";
        uploadPlaceholder.style.display = "block";
        return;
    }

    // ================= TYPE VALIDATION =================
    const allowedTypes = ["image/jpeg", "image/png", "image/jpg"];

    if (!allowedTypes.includes(file.type)) {
        showAlertModal({
            title: "Invalid file type",
            text: "Only JPG and PNG images are allowed.",
            icon: "!",
            variant: "danger",
            confirmText: "OK",
            showCancel: false
        });

        imageInput.value = "";
        return;
    }

    // ================= SIZE VALIDATION =================
    if (file.size > 2 * 1024 * 1024) {
        showAlertModal({
            title: "File too large",
            text: "Maximum file size is 2MB.",
            icon: "!",
            variant: "danger",
            confirmText: "OK",
            showCancel: false
        });

        imageInput.value = "";
        return;
    }

    // ================= PREVIEW =================
    const reader = new FileReader();

    reader.onload = function(e){
        previewImg.src = e.target.result;
        previewImg.style.display = "block";
        uploadPlaceholder.style.display = "none";
    };

    reader.readAsDataURL(file);
});

const uploadBox = document.getElementById("image-upload-box");


if (uploadBox && imageInput) {
    uploadBox.addEventListener("click", () => {
        if (currentMode === "view") return; // 🔥 BLOCK
        imageInput.click();
    });
}

/**
 * ================= CONFIRM SAVE =================
 */
form.addEventListener("submit", function(e){

    // 🔒 prevent instant submit
    e.preventDefault();

    showAlertModal({
        title: "Save changes?",
        text: "Make sure all information is correct.",
        icon: "✓",
        variant: "success",
        confirmText: "Save",
        showCancel: true,

        onConfirm: () => {
            form.submit(); // ✅ real submit
        }
    });
});

/*
 * ================= AUTO OPEN SUPPORT FAQ =================
 *
 * When the FAQ page was opened through
 * "Support Request → To FAQ", automatically
 * open the existing FAQ modal in conversion mode.
 */
if (supportFaqData) {

    openFaqModal(
        "convert",
        supportFaqData
    );
}

});

