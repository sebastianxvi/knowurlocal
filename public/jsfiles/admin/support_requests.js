document.addEventListener("DOMContentLoaded", () => {
    /*
    |--------------------------------------------------------------------------
    | GENERAL SUPPORT REQUEST ELEMENTS
    |--------------------------------------------------------------------------
    */

    let currentRequestId = null;

    const modal = document.getElementById("support-modal-back");
    const form = document.getElementById("reply-form");
    const methodInput = document.getElementById("form-method");
    const saveBtn = document.querySelector(".btn-save");


    /*
|--------------------------------------------------------------------------
| SUPPORT AGENCY SEARCHABLE SELECT
|--------------------------------------------------------------------------
*/

/*
 * Native select submitted to Laravel.
 */
const supportAgencySelect =
    document.getElementById("sr-agency");

/*
 * Visible search input.
 */
const supportAgencySearchInput =
    document.getElementById("sr-agency-search");

/*
 * Dropdown result container.
 */
const supportAgencyOptionsContainer =
    document.getElementById("sr-agency-options");

/*
 * Main searchable-select wrapper.
 */
const supportAgencySearchableWrapper =
    document.getElementById("support-agency-searchable");

/*
 * Stores normalized agency data for searching.
 */
let searchableSupportAgencies = [];

/*
 * Tracks whether the dropdown is open.
 */
let supportAgencyDropdownOpen = false;


/*
|--------------------------------------------------------------------------
| BUILD SEARCHABLE AGENCY DATA
|--------------------------------------------------------------------------
*/
function buildSupportAgencyOptions() {

    if (
        !supportAgencySelect ||
        !supportAgencyOptionsContainer
    ) {
        return;
    }

    searchableSupportAgencies =
        Array.from(
            supportAgencySelect.options
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
|--------------------------------------------------------------------------
| OPEN DROPDOWN
|--------------------------------------------------------------------------
*/

function openSupportAgencyDropdown() {
    if (
        !supportAgencySearchInput ||
        !supportAgencySearchableWrapper
    ) {
        return;
    }

    /*
     * Do not allow searching in view-only mode.
     */
    if (
        supportAgencySearchInput.readOnly ||
        supportAgencySearchInput.disabled
    ) {
        return;
    }

    supportAgencyDropdownOpen = true;

    supportAgencySearchableWrapper.classList.add(
        "is-open"
    );

    supportAgencySearchInput.setAttribute(
        "aria-expanded",
        "true"
    );

    renderSupportAgencyOptions(
        supportAgencySearchInput.value
    );
}


/*
|--------------------------------------------------------------------------
| CLOSE DROPDOWN
|--------------------------------------------------------------------------
*/

function closeSupportAgencyDropdown() {
    supportAgencyDropdownOpen = false;

    if (supportAgencySearchableWrapper) {
        supportAgencySearchableWrapper.classList.remove(
            "is-open"
        );
    }

    if (supportAgencySearchInput) {
        supportAgencySearchInput.setAttribute(
            "aria-expanded",
            "false"
        );
    }
}


/*
|--------------------------------------------------------------------------
| RENDER SEARCH RESULTS
|--------------------------------------------------------------------------
*/

function renderSupportAgencyOptions(searchTerm = "") {
    if (!supportAgencyOptionsContainer) {
        return;
    }

const normalizedSearch =
    searchTerm
        .toLowerCase()
        .trim();

const filteredAgencies =
    searchableSupportAgencies.filter(
        agency => {

            return agency.searchText.includes(
                normalizedSearch
            );

        }
    );

    /*
     * Clear previous results safely.
     */
    supportAgencyOptionsContainer.replaceChildren();

    /*
     * Display an empty state when no agency matches.
     */
    if (filteredAgencies.length === 0) {
        const emptyState =
            document.createElement("div");

        emptyState.className =
            "searchable-select-empty";

        emptyState.textContent =
            "No matching agencies found.";

        supportAgencyOptionsContainer.appendChild(
            emptyState
        );

        return;
    }

    /*
     * Create each result as a real button.
     */
    filteredAgencies.forEach(agency => {

        const optionButton =
            document.createElement("button");

        optionButton.type = "button";

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
         * Display the abbreviation when available.
         */
        if (agency.abbreviation) {
            optionButton.textContent +=
                ` (${agency.abbreviation.toUpperCase()})`;
        }

        /*
         * Mark the currently selected agency.
         */
        if (
            supportAgencySelect &&
            supportAgencySelect.value === agency.value
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

        /*
         * Select the agency when clicked.
         */
        optionButton.addEventListener(
            "click",
            () => {
                selectSupportAgency(agency.value);
            }
        );

        supportAgencyOptionsContainer.appendChild(
            optionButton
        );
    });
}


/*
|--------------------------------------------------------------------------
| SELECT AGENCY
|--------------------------------------------------------------------------
*/

function selectSupportAgency(agencyValue) {
    if (
        !supportAgencySelect ||
        !supportAgencySearchInput
    ) {
        return;
    }

    const selectedOption =
        Array.from(supportAgencySelect.options)
            .find(option =>
                option.value === String(agencyValue)
            );

    /*
     * Reject values that do not exist in the native select.
     */
    if (!selectedOption) {
        supportAgencySelect.value = "";

        supportAgencySearchInput.value = "";

        supportAgencySearchInput.setCustomValidity(
            "Please select an agency from the list."
        );

        closeSupportAgencyDropdown();

        return;
    }

    /*
     * Update the real submitted field.
     */
    supportAgencySelect.value =
        selectedOption.value;

    /*
     * Update the visible field.
     */
    supportAgencySearchInput.value =
        selectedOption.dataset.fullName ||
        selectedOption.textContent.trim();

    /*
     * Clear the custom validation error.
     */
    supportAgencySearchInput.setCustomValidity("");

    closeSupportAgencyDropdown();

    /*
     * Preserve compatibility with existing code.
     */
    supportAgencySelect.dispatchEvent(
        new Event("change", {
            bubbles: true
        })
    );
}


/*
|--------------------------------------------------------------------------
| SYNC SEARCH FIELD WITH NATIVE SELECT
|--------------------------------------------------------------------------
*/

function syncSupportAgencySearchInput() {
    if (
        !supportAgencySelect ||
        !supportAgencySearchInput
    ) {
        return;
    }

    const selectedOption =
        supportAgencySelect.options[
            supportAgencySelect.selectedIndex
        ];

    if (
        !selectedOption ||
        !selectedOption.value
    ) {
        supportAgencySearchInput.value = "";

        supportAgencySearchInput.setCustomValidity(
            "Please select an agency from the list."
        );

        return;
    }

    supportAgencySearchInput.value =
        selectedOption.dataset.fullName ||
        selectedOption.textContent.trim();

    supportAgencySearchInput.setCustomValidity("");
}


/*
|--------------------------------------------------------------------------
| SEARCH INPUT EVENTS
|--------------------------------------------------------------------------
*/

if (supportAgencySearchInput) {

    supportAgencySearchInput.addEventListener(
        "focus",
        openSupportAgencyDropdown
    );

    supportAgencySearchInput.addEventListener(
        "click",
        openSupportAgencyDropdown
    );

    supportAgencySearchInput.addEventListener(
        "input",
        function () {

            /*
             * Typing invalidates the previous selection.
             */
            if (supportAgencySelect) {
                supportAgencySelect.value = "";
            }

            this.setCustomValidity(
                "Please select an agency from the list."
            );

            openSupportAgencyDropdown();

            renderSupportAgencyOptions(
                this.value
            );
        }
    );

    supportAgencySearchInput.addEventListener(
        "keydown",
        function (event) {

            if (event.key === "Escape") {
                closeSupportAgencyDropdown();
                return;
            }

            /*
             * Select the first visible result with Enter.
             */
            if (
                event.key === "Enter" &&
                supportAgencyDropdownOpen
            ) {

                const firstOption =
                    supportAgencyOptionsContainer?.querySelector(
                        ".searchable-select-option"
                    );

                if (firstOption) {
                    event.preventDefault();

                    selectSupportAgency(
                        firstOption.dataset.value
                    );
                }
            }
        }
    );
}


/*
|--------------------------------------------------------------------------
| CLOSE WHEN CLICKING OUTSIDE
|--------------------------------------------------------------------------
*/

document.addEventListener("click", event => {

    if (!supportAgencySearchableWrapper) {
        return;
    }

    if (
        !supportAgencySearchableWrapper.contains(
            event.target
        )
    ) {
        closeSupportAgencyDropdown();
    }
});


/*
|--------------------------------------------------------------------------
| INITIALIZE AGENCY DATA
|--------------------------------------------------------------------------
*/

buildSupportAgencyOptions();

console.table(searchableSupportAgencies);

    /*
    |--------------------------------------------------------------------------
    | SUPPORT ANSWER IMAGE ELEMENTS
    |--------------------------------------------------------------------------
    */

    const supportImageUploadBox = document.getElementById(
        "support-image-upload-box"
    );

    const supportUploadPlaceholder = document.getElementById(
        "support-upload-placeholder"
    );

    const supportPreviewImg = document.getElementById(
        "support-preview-img"
    );

    const supportImageInput = document.getElementById(
        "support_answer_image"
    );

    const supportImageGroup = document.querySelector(
        ".support-image-group"
    );

    /*
     * Button used to remove the current image.
     */
    const removeSupportImageButton = document.getElementById(
        "remove-support-image-btn"
    );

    /*
     * Hidden input used to tell Laravel that the existing
     * answer image should be deleted.
     */
    const removeAnswerImageInput = document.getElementById(
        "remove_answer_image"
    );



    /*
    |--------------------------------------------------------------------------
    | SUPPORT ANSWER IMAGE HELPERS
    |--------------------------------------------------------------------------
    */

    /*
     * Show the remove-image button.
     */
    function showRemoveSupportImageButton() {
        if (!removeSupportImageButton) {
            return;
        }

        removeSupportImageButton.style.display = "flex";
    }


    /*
     * Hide the remove-image button.
     */
    function hideRemoveSupportImageButton() {
        if (!removeSupportImageButton) {
            return;
        }

        removeSupportImageButton.style.display = "none";
    }


    /*
     * Reset the uploader to its default empty state.
     *
     * This clears:
     * - The preview image
     * - The selected file
     * - The removal flag
     * - The remove button
     */
    function resetSupportImageState() {
    /*
     * Clear the displayed preview image.
     */
    if (supportPreviewImg) {
        supportPreviewImg.removeAttribute("src");
        supportPreviewImg.style.display = "none";
    }

    /*
     * Show the upload instructions again.
     */
    if (supportUploadPlaceholder) {
        supportUploadPlaceholder.style.display = "flex";
    }

    /*
     * Clear the selected file from the browser input.
     */
    if (supportImageInput) {
        supportImageInput.value = "";
        supportImageInput.disabled = false;
    }

    /*
     * Reset the deletion flag.
     *
     * This is only for opening or closing another request.
     * Clicking the X button uses "1" through
     * removeSupportImage().
     */
    if (removeAnswerImageInput) {
        removeAnswerImageInput.value = "0";
    }

    /*
     * Restore the uploader's normal editable state.
     */
    if (supportImageGroup) {
        supportImageGroup.classList.remove("is-disabled");
    }

    /*
     * Hide the remove-image button.
     */
    hideRemoveSupportImageButton();
}


    /*
     * Display an existing answer image.
     *
     * imagePath is the stored Laravel path, for example:
     *
     * support-answers/example.webp
     */
    function showSupportImage(imagePath) {
        if (!supportPreviewImg || !supportUploadPlaceholder) {
            return;
        }

        /*
         * If there is no stored image, keep the uploader empty.
         */
        if (!imagePath) {
            resetSupportImageState();
            return;
        }

        /*
         * Load the stored image from Laravel public storage.
         */
        supportPreviewImg.src = `/storage/${imagePath}`;

        /*
         * Show the image preview.
         */
        supportPreviewImg.style.display = "block";

        /*
         * Hide the upload instructions.
         */
        supportUploadPlaceholder.style.display = "none";

        /*
         * The image currently exists, so the default state
         * should not request deletion.
         */
        if (removeAnswerImageInput) {
            removeAnswerImageInput.value = "0";
        }

        /*
         * Show the X button.
         */
        showRemoveSupportImageButton();
    }


    /*
     * Remove the currently displayed image.
     *
     * This clears the preview and sets the hidden removal
     * field to 1 so Laravel can delete the stored image.
     */
    function removeSupportImage() {
    /*
     * Clear the selected file from the file input.
     */
    if (supportImageInput) {
        supportImageInput.value = "";
    }

    /*
     * Remove the preview image from the interface.
     */
    if (supportPreviewImg) {
        supportPreviewImg.removeAttribute("src");
        supportPreviewImg.style.display = "none";
    }

    /*
     * Show the upload placeholder.
     */
    if (supportUploadPlaceholder) {
        supportUploadPlaceholder.style.display = "flex";
    }

    /*
     * Mark the existing database image for deletion.
     *
     * Laravel must read this value when the form is submitted.
     */
    if (removeAnswerImageInput) {
        removeAnswerImageInput.value = "1";
    }

    /*
     * Hide the X button because no image is currently shown.
     */
    hideRemoveSupportImageButton();
}


    /*
     * Set the uploader's view/edit state.
     *
     * true  = view-only mode
     * false = editable mode
     */
    function setSupportImageViewMode(isViewMode) {
        if (!supportImageInput || !supportImageGroup) {
            return;
        }

        /*
         * Disable or enable the file input.
         */
        supportImageInput.disabled = isViewMode;

        /*
         * Apply or remove the disabled visual state.
         */
        supportImageGroup.classList.toggle(
            "is-disabled",
            isViewMode
        );

        /*
         * The remove button should also be disabled visually
         * when the uploader is in view-only mode.
         */
        if (removeSupportImageButton) {
            removeSupportImageButton.disabled = isViewMode;
        }
    }



    /*
    |--------------------------------------------------------------------------
    | SUPPORT ANSWER IMAGE UPLOAD
    |--------------------------------------------------------------------------
    */

    /*
     * Open the native file picker when the upload box
     * is clicked.
     */
    if (supportImageUploadBox && supportImageInput) {
        supportImageUploadBox.addEventListener("click", (event) => {
            /*
             * Do not open the file picker when the X button
             * itself was clicked.
             */
            if (
                removeSupportImageButton &&
                event.target.closest("#remove-support-image-btn")
            ) {
                return;
            }

            /*
             * Do not open the file picker if the input
             * is disabled.
             */
            if (supportImageInput.disabled) {
                return;
            }

            /*
             * Open the native file picker.
             */
            supportImageInput.click();
        });
    }


    /*
     * Remove the image when the X button is clicked.
     */
    if (removeSupportImageButton) {
        removeSupportImageButton.addEventListener(
            "click",
            (event) => {
                /*
                 * Prevent the button from submitting the form.
                 */
                event.preventDefault();

                /*
                 * Prevent the click from bubbling to the
                 * upload box and reopening the file picker.
                 */
                event.stopPropagation();

                removeSupportImage();
            }
        );
    }


    /*
     * Validate the selected image and display its preview.
     */
    if (supportImageInput) {
        supportImageInput.addEventListener(
            "change",
            function () {
                const file = this.files?.[0];

                /*
                 * Stop if the administrator cancelled
                 * the file picker.
                 */
                if (!file) {
                    return;
                }

                /*
                 * Allowed image MIME types.
                 */
                const allowedTypes = [
                    "image/jpeg",
                    "image/png",
                    "image/webp"
                ];

                /*
                 * Maximum file size: 5 MB.
                 */
                const maximumSize = 5 * 1024 * 1024;

                /*
                 * Reject unsupported file types.
                 */
                if (!allowedTypes.includes(file.type)) {
                    alert(
                        "Only JPG, PNG, or WebP images are allowed."
                    );

                    resetSupportImageState();
                    return;
                }

                /*
                 * Reject images larger than 5 MB.
                 */
                if (file.size > maximumSize) {
                    alert(
                        "The image must not be larger than 5MB."
                    );

                    resetSupportImageState();
                    return;
                }

                /*
                 * FileReader allows the image to be previewed
                 * before the form is submitted.
                 */
                const reader = new FileReader();

                /*
                 * This runs after the file has been read.
                 */
                reader.onload = function (event) {
                    if (supportPreviewImg) {
                        supportPreviewImg.src =
                            event.target.result;

                        supportPreviewImg.style.display =
                            "block";
                    }

                    if (supportUploadPlaceholder) {
                        supportUploadPlaceholder.style.display =
                            "none";
                    }

                    /*
                     * A newly selected image should not be
                     * marked for deletion.
                     */
                    if (removeAnswerImageInput) {
                        removeAnswerImageInput.value = "0";
                    }

                    /*
                     * Show the X button for the new image.
                     */
                    showRemoveSupportImageButton();
                };

                /*
                 * Read the image as a temporary browser data URL.
                 */
                reader.readAsDataURL(file);
            }
        );
    }



    /*
    |--------------------------------------------------------------------------
    | SIMILAR FAQ MODAL ELEMENTS
    |--------------------------------------------------------------------------
    */

    const similarFaqModal = document.getElementById(
        "similar-faq-modal-back"
    );

    const similarFaqMessage = document.getElementById(
        "similar-faq-message"
    );

    const similarFaqResults = document.getElementById(
        "similar-faq-results"
    );

    const similarFaqCancel = document.getElementById(
        "similar-faq-cancel"
    );

    const similarFaqContinue = document.getElementById(
        "similar-faq-continue"
    );

    /*
     * Stores the original Laravel-generated FAQ URL.
     */
    let pendingFaqUrl = null;



    /*
    |--------------------------------------------------------------------------
    | SUCCESS ALERT
    |--------------------------------------------------------------------------
    */

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

        setTimeout(() => {
            closeAlertModal();
        }, 1500);

        window.__FLASH_SUCCESS__ = null;
    }



    /*
    |--------------------------------------------------------------------------
    | SIMILAR FAQ MODAL FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
     * Open the Similar FAQ modal.
     */
    function openSimilarFaqModal() {
        if (!similarFaqModal) {
            return;
        }

        similarFaqModal.classList.add("active");

        similarFaqModal.setAttribute(
            "aria-hidden",
            "false"
        );
    }


    /*
     * Close the Similar FAQ modal.
     */
    function closeSimilarFaqModal() {
        if (!similarFaqModal) {
            return;
        }

        similarFaqModal.classList.remove("active");

        similarFaqModal.setAttribute(
            "aria-hidden",
            "true"
        );

        /*
         * Clear the stored FAQ URL so an old URL cannot
         * accidentally be reused later.
         */
        pendingFaqUrl = null;
    }


    /*
     * Check the database for potentially similar FAQs.
     */
    async function checkSimilarFaqs(faqBtn) {
        const similarUrl = faqBtn.dataset.similarUrl;

        /*
         * Preserve the original To FAQ destination.
         */
        pendingFaqUrl = faqBtn.href;

        /*
         * Stop safely if the endpoint is missing.
         */
        if (!similarUrl) {
            console.error(
                "Similar FAQ URL is missing."
            );

            window.location.href = pendingFaqUrl;
            return;
        }

        /*
         * Open the modal immediately.
         */
        openSimilarFaqModal();

        /*
         * Show the loading message.
         */
        if (similarFaqMessage) {
            similarFaqMessage.textContent =
                "Checking existing FAQs for similar questions...";
        }

        /*
         * Display a loading state.
         */
        if (similarFaqResults) {
            similarFaqResults.innerHTML = `
                <div class="similar-faq-loading">
                    <i class="ph-light ph-spinner-gap"></i>
                    <span>Checking existing FAQs...</span>
                </div>
            `;
        }

        /*
         * Prevent the Continue button from being clicked
         * while the similarity check is running.
         */
        if (similarFaqContinue) {
            similarFaqContinue.disabled = true;
        }

        try {
            /*
             * Send a POST request to Laravel.
             */
            const response = await fetch(
                similarUrl,
                {
                    method: "POST",

                    headers: {
                        "X-CSRF-TOKEN":
                            document.querySelector(
                                'meta[name="csrf-token"]'
                            )?.getAttribute("content") || "",

                        "Accept": "application/json"
                    },

                    /*
                     * Keep the Laravel session attached.
                     */
                    credentials: "same-origin"
                }
            );

            /*
             * Convert the response to JSON.
             */
            const data = await response.json();

            /*
             * Treat HTTP errors and success:false responses
             * as failed requests.
             */
            if (!response.ok || !data.success) {
                throw new Error(
                    data.message ||
                    "Unable to check similar FAQs."
                );
            }

            /*
             * Clear the loading state.
             */
            if (similarFaqResults) {
                similarFaqResults.innerHTML = "";
            }

            /*
             * Check whether matching FAQs were found.
             */
            if (
                !Array.isArray(data.matches) ||
                data.matches.length === 0
            ) {
                if (similarFaqMessage) {
                    similarFaqMessage.textContent =
                        "No similar FAQs were found. You can continue creating this FAQ.";
                }

                /*
                 * Create the empty state safely.
                 */
                const emptyState =
                    document.createElement("div");

                emptyState.className =
                    "similar-faq-empty";

                const icon =
                    document.createElement("i");

                icon.className =
                    "ph-light ph-check-circle";

                const message =
                    document.createElement("div");

                message.textContent =
                    "No existing FAQ appears to closely match this Support Request.";

                emptyState.appendChild(icon);
                emptyState.appendChild(message);

                if (similarFaqResults) {
                    similarFaqResults.appendChild(
                        emptyState
                    );
                }
            } else {
                /*
                 * Similar FAQs were found.
                 */
                if (similarFaqMessage) {
                    similarFaqMessage.textContent =
                        "We found existing FAQs that may be related to this Support Request. Review them before continuing.";
                }

                /*
                 * Render each matching FAQ.
                 */
                data.matches.forEach((match) => {
                    const item =
                        document.createElement("div");

                    item.className =
                        "similar-faq-item";

                    const score =
                        document.createElement("div");

                    score.className =
                        "similar-faq-score";

                    score.textContent =
                        `${Number(match.percentage) || 0}%`;

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

                    content.appendChild(question);
                    content.appendChild(agency);

                    item.appendChild(score);
                    item.appendChild(content);

                    if (similarFaqResults) {
                        similarFaqResults.appendChild(item);
                    }
                });
            }

            /*
             * Allow the administrator to continue.
             */
            if (similarFaqContinue) {
                similarFaqContinue.disabled = false;
            }
        } catch (error) {
            /*
             * Keep technical details in the browser console.
             */
            console.error(
                "Similar FAQ check failed:",
                error
            );

            /*
             * Show a safe user-facing message.
             */
            if (similarFaqMessage) {
                similarFaqMessage.textContent =
                    "We could not check existing FAQs right now.";
            }

            if (similarFaqResults) {
                similarFaqResults.innerHTML = `
                    <div class="similar-faq-empty">
                        <i class="ph-light ph-warning-circle"></i>

                        <div>
                            The similarity check could not be completed.
                            You can still continue to the FAQ creation page.
                        </div>
                    </div>
                `;
            }

            /*
             * Allow the administrator to continue even
             * when the similarity check fails.
             */
            if (similarFaqContinue) {
                similarFaqContinue.disabled = false;
            }
        }
    }



    /*
    |--------------------------------------------------------------------------
    | GLOBAL CLICK HANDLER
    |--------------------------------------------------------------------------
    */

    document.addEventListener("click", (event) => {
        /*
        |--------------------------------------------------------------------------
        | TO FAQ
        |--------------------------------------------------------------------------
        */

        const faqBtn = event.target.closest(".faq-btn");

        if (faqBtn) {
            /*
             * Stop the normal anchor navigation.
             */
            event.preventDefault();

            /*
             * Prevent duplicate similarity requests.
             */
            if (
                faqBtn.dataset.processing === "true"
            ) {
                return;
            }

            faqBtn.dataset.processing = "true";

            /*
             * Run the similarity check.
             */
            checkSimilarFaqs(faqBtn).finally(() => {
                faqBtn.dataset.processing = "false";
            });

            return;
        }



        /*
        |--------------------------------------------------------------------------
        | SUPPORT REQUEST LIFECYCLE ACTIONS
        |--------------------------------------------------------------------------
        */

        const lifecycleBtn = event.target.closest(
            ".delete-btn, .restore-btn, .permanent-delete-btn"
        );

        if (lifecycleBtn) {
            /*
             * Prevent the form from submitting immediately.
             */
            event.preventDefault();

            /*
             * Find the form connected to the clicked button.
             */
            const lifecycleForm =
                lifecycleBtn.closest("form");

            if (!lifecycleForm) {
                console.error(
                    "Support request lifecycle form not found."
                );

                return;
            }

            let config;

            /*
             * MOVE TO TRASH
             */
            if (
                lifecycleBtn.classList.contains(
                    "delete-btn"
                )
            ) {
                config = {
                    title:
                        "Move Support Request to Trash",

                    text:
                        "Are you sure you want to move this support request to trash? You can restore it later.",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "Move to Trash"
                };
            }

            /*
             * RESTORE
             */
            else if (
                lifecycleBtn.classList.contains(
                    "restore-btn"
                )
            ) {
                config = {
                    title:
                        "Restore Support Request",

                    text:
                        "Are you sure you want to restore this support request?",

                    icon:
                        "↶",

                    variant:
                        "success",

                    confirmText:
                        "Restore"
                };
            }

            /*
             * PERMANENT DELETE
             */
            else if (
                lifecycleBtn.classList.contains(
                    "permanent-delete-btn"
                )
            ) {
                config = {
                    title:
                        "Delete Support Request Permanently",

                    text:
                        "This action permanently deletes the support request and cannot be undone. Are you sure you want to continue?",

                    icon:
                        "!",

                    variant:
                        "danger",

                    confirmText:
                        "Delete Permanently"
                };
            }

            /*
             * Show the confirmation dialog.
             */
            showAlertModal({
                title: config.title,
                text: config.text,
                icon: config.icon,
                variant: config.variant,
                confirmText: config.confirmText,
                showCancel: true,

                /*
                 * Submit the original Laravel form only
                 * after explicit confirmation.
                 */
                onConfirm: () => {
                    lifecycleForm.submit();
                }
            });

            return;
        }



        /*
        |--------------------------------------------------------------------------
        | MANAGE SUPPORT REQUEST
        |--------------------------------------------------------------------------
        */

        const viewBtn = event.target.closest(".view-btn");

        if (viewBtn) {
            currentRequestId = viewBtn.dataset.id;

            document.getElementById("sr-id").value =
                currentRequestId;

            document.getElementById("sr-user").value =
                viewBtn.dataset.user;

            document.getElementById("sr-question").value =
                viewBtn.dataset.question;

            document.getElementById("sr-reply").value =
                viewBtn.dataset.answer || "";

            selectSupportAgency(
    viewBtn.dataset.agencyId || ""
);

            /*
             * Clear the previous request's image.
             */
            resetSupportImageState();

            /*
             * Load the selected request's existing image.
             */
            showSupportImage(
                viewBtn.dataset.answerImage
            );

            /*
             * IMPORTANT:
             *
             * Manage mode must remain editable so the
             * administrator can upload, replace, or remove
             * the answer image.
             */
            setSupportImageViewMode(false);

            /*
             * Select update or reply mode.
             */
            if (viewBtn.dataset.answer) {
                form.action =
                    `${form.dataset.updateUrl}/${currentRequestId}`;

                methodInput.value = "PUT";

                saveBtn.innerText =
                    "Update Answer";
            } else {
                form.action =
                    form.dataset.replyUrl;

                methodInput.value = "POST";

                saveBtn.innerText =
                    "Mark as Answered";
            }

            /*
             * Open the support request modal.
             */
            modal.classList.add("active");

            return;
        }



        /*
        |--------------------------------------------------------------------------
        | EDIT MODE
        |--------------------------------------------------------------------------
        */

        const editBtn = event.target.closest(".edit-btn");

        if (editBtn) {
            const id = editBtn.dataset.id;

            const answer =
                editBtn.dataset.answer || "";

            currentRequestId = id;

            document.getElementById("sr-id").value =
                id;

            document.getElementById("sr-reply").value =
                answer;

            selectSupportAgency(
    editBtn.dataset.agencyId || ""
);

            /*
             * Clear the previous image.
             */
            resetSupportImageState();

            /*
             * Load the existing image.
             */
            showSupportImage(
                editBtn.dataset.answerImage
            );

            /*
             * Keep image uploading enabled.
             */
            setSupportImageViewMode(false);

            /*
             * Use the existing update route.
             */
            form.action =
                `${form.dataset.updateUrl}/${id}`;

            methodInput.value = "PUT";

            saveBtn.innerText =
                "Update Answer";

            /*
             * Open the support request modal.
             */
            modal.classList.add("active");

            return;
        }
    });



    /*
    |--------------------------------------------------------------------------
    | SIMILAR FAQ MODAL CONTROLS
    |--------------------------------------------------------------------------
    */

    /*
     * Cancel button.
     */
    if (similarFaqCancel) {
        similarFaqCancel.addEventListener(
            "click",
            closeSimilarFaqModal
        );
    }


    /*
     * Clicking the backdrop closes the Similar FAQ modal.
     */
    if (similarFaqModal) {
        similarFaqModal.addEventListener(
            "click",
            (event) => {
                if (event.target === similarFaqModal) {
                    closeSimilarFaqModal();
                }
            }
        );
    }


    /*
     * Escape closes the Similar FAQ modal.
     */
    document.addEventListener(
        "keydown",
        (event) => {
            if (
                event.key === "Escape" &&
                similarFaqModal &&
                similarFaqModal.classList.contains("active")
            ) {
                closeSimilarFaqModal();
            }
        }
    );


    /*
     * Continue to FAQ creation.
     */
    if (similarFaqContinue) {
        similarFaqContinue.addEventListener(
            "click",
            () => {
                if (!pendingFaqUrl) {
                    return;
                }

                window.location.href =
                    pendingFaqUrl;
            }
        );
    }



    /*
    |--------------------------------------------------------------------------
    | CLOSE SUPPORT REQUEST MODAL
    |--------------------------------------------------------------------------
    */

    /*
     * Close the support request modal and reset
     * the image uploader.
     */
    function closeSupportModal() {
    if (modal) {
        modal.classList.remove("active");
    }

    /*
     * Reset the native agency field.
     */
    if (supportAgencySelect) {
        supportAgencySelect.value = "";
    }

    /*
     * Reset the visible search field.
     */
    if (supportAgencySearchInput) {
        supportAgencySearchInput.value = "";

        supportAgencySearchInput.setCustomValidity(
            "Please select an agency from the list."
        );
    }

    /*
     * Close the agency dropdown.
     */
    closeSupportAgencyDropdown();

    /*
     * Reset the image uploader.
     */
    resetSupportImageState();

    /*
     * Restore editable mode for the next request.
     */
    setSupportImageViewMode(false);
}


    /*
     * Make the function available to Blade inline
     * onclick attributes.
     */
    window.closeSupportModal = closeSupportModal;
});