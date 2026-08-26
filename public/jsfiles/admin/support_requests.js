document.addEventListener("DOMContentLoaded", () => {

    let currentRequestId = null;

    const modal = document.getElementById("support-modal-back");
    const form = document.getElementById("reply-form");
    const methodInput = document.getElementById("form-method");
    const saveBtn = document.querySelector(".btn-save");
    

    // ================= SIMILAR FAQ MODAL =================

/*
 * References to the Similar FAQ modal elements.
 *
 * These elements already exist in support_requests.blade.php.
 */
const similarFaqModal =
    document.getElementById("similar-faq-modal-back");


const similarFaqMessage =
    document.getElementById("similar-faq-message");

const similarFaqResults =
    document.getElementById("similar-faq-results");

const similarFaqCancel =
    document.getElementById("similar-faq-cancel");

const similarFaqContinue =
    document.getElementById("similar-faq-continue");

/*
 * Stores the original Laravel-generated "To FAQ" URL.
 *
 * We keep this instead of constructing the URL manually.
 */
let pendingFaqUrl = null;

    // ================= SUCCESS ALERT =================
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

    // =========================================================
// SIMILAR FAQ MODAL
// =========================================================

/*
 * Open the Similar FAQ modal.
 */
function openSimilarFaqModal() {

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

    similarFaqModal.classList.remove("active");

    similarFaqModal.setAttribute(
        "aria-hidden",
        "true"
    );

    /*
     * Clear the stored FAQ destination.
     *
     * This prevents an old request URL from accidentally
     * being reused later.
     */
    pendingFaqUrl = null;
}


/*
 * Check the database for potentially similar FAQs.
 */
async function checkSimilarFaqs(faqBtn) {

    /*
     * Laravel generated this endpoint in the Blade:
     *
     * data-similar-url="{{ route(...) }}"
     */
    const similarUrl =
        faqBtn.dataset.similarUrl;

    /*
     * The original To FAQ destination.
     *
     * We will use this if the administrator chooses
     * "Continue to FAQ".
     */
    pendingFaqUrl = faqBtn.href;


    /*
     * Fail safely if the Blade does not contain the
     * expected endpoint.
     */
    if (!similarUrl) {

        console.error(
            "Similar FAQ URL is missing."
        );

        /*
         * Do not permanently block the administrator.
         *
         * If the similarity feature itself is unavailable,
         * continue with the original FAQ flow.
         */
        window.location.href = pendingFaqUrl;

        return;
    }


    /*
     * Show the modal immediately.
     *
     * This gives the administrator feedback while the
     * server performs the similarity calculation.
     */
    openSimilarFaqModal();


    /*
     * Show the loading message.
     */
    similarFaqMessage.textContent =
        "Checking existing FAQs for similar questions...";


    /*
     * Show a lightweight loading state.
     */
    similarFaqResults.innerHTML = `
        <div class="similar-faq-loading">
            <i class="ph-light ph-spinner-gap"></i>
            <span>Checking existing FAQs...</span>
        </div>
    `;


    /*
     * Prevent Continue while the similarity request
     * is still running.
     */
    similarFaqContinue.disabled = true;


    try {

        /*
         * Call the endpoint that we already tested manually.
         *
         * The endpoint is POST and uses the URL generated
         * by Laravel.
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
                 * Keep the Laravel session/cookie attached.
                 */
                credentials: "same-origin"
            }
        );


        /*
         * Convert Laravel's JSON response into an object.
         */
        const data = await response.json();


        /*
         * Treat both HTTP failures and Laravel's explicit
         * success:false response as errors.
         */
        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                "Unable to check similar FAQs."
            );
        }


        /*
         * Remove the previous loading state.
         */
        similarFaqResults.innerHTML = "";


        /*
         * Check whether Laravel found any candidates.
         */
        if (
            !Array.isArray(data.matches) ||
            data.matches.length === 0
        ) {

            similarFaqMessage.textContent =
                "No similar FAQs were found. You can continue creating this FAQ.";


            /*
             * Use DOM APIs instead of inserting FAQ content
             * directly into HTML.
             *
             * This prevents database content from being
             * interpreted as executable HTML.
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

            similarFaqResults.appendChild(
                emptyState
            );


        } else {

            /*
             * Potentially similar FAQs were found.
             */
            similarFaqMessage.textContent =
                "We found existing FAQs that may be related to this Support Request. Review them before continuing.";


            /*
             * Render each similarity match.
             */
            data.matches.forEach((match) => {

                /*
                 * Create the outer result card.
                 */
                const item =
                    document.createElement("div");

                item.className =
                    "similar-faq-item";


                /*
                 * Create the similarity percentage.
                 */
                const score =
                    document.createElement("div");

                score.className =
                    "similar-faq-score";

                score.textContent =
                    `${Number(match.percentage) || 0}%`;


                /*
                 * Create the content container.
                 */
                const content =
                    document.createElement("div");

                content.className =
                    "similar-faq-content";


                /*
                 * Create the FAQ question.
                 */
                const question =
                    document.createElement("p");

                question.className =
                    "similar-faq-question";

                question.textContent =
                    match.question ||
                    "Untitled FAQ";


                /*
                 * Create the agency label.
                 */
                const agency =
                    document.createElement("p");

                agency.className =
                    "similar-faq-agency";

                agency.textContent =
                    match.agency_name ||
                    "Unknown agency";


                /*
                 * Build the result card.
                 */
                content.appendChild(question);
                content.appendChild(agency);

                item.appendChild(score);
                item.appendChild(content);

                similarFaqResults.appendChild(item);
            });
        }


        /*
         * Similarity checking has finished.
         *
         * The administrator is now allowed to continue.
         */
        similarFaqContinue.disabled = false;


    } catch (error) {

        /*
         * Keep the technical error in the browser console
         * for development/debugging.
         *
         * Do not expose backend internals to administrators.
         */
        console.error(
            "Similar FAQ check failed:",
            error
        );


        /*
         * Show a safe user-facing message.
         */
        similarFaqMessage.textContent =
            "We could not check existing FAQs right now.";


        /*
         * Explain that the administrator can still proceed.
         *
         * Similarity is an advisory feature, not a blocker.
         */
        similarFaqResults.innerHTML = `
            <div class="similar-faq-empty">
                <i class="ph-light ph-warning-circle"></i>
                <div>
                    The similarity check could not be completed.
                    You can still continue to the FAQ creation page.
                </div>
            </div>
        `;


        /*
         * IMPORTANT:
         *
         * A failure in the similarity feature should never
         * prevent the administrator from creating an FAQ.
         */
        similarFaqContinue.disabled = false;
    }
}

    document.addEventListener("click", (e) => {

    // ================= TO FAQ =================
    const faqBtn = e.target.closest(".faq-btn");

    if (faqBtn) {

        /*
         * Stop the normal <a> navigation.
         *
         * We need to check existing FAQs first.
         */
        e.preventDefault();


        /*
         * Prevent accidental double-clicks from creating
         * multiple similarity requests.
         */
        if (faqBtn.dataset.processing === "true") {
            return;
        }


        faqBtn.dataset.processing = "true";


        /*
         * Perform the similarity check.
         *
         * The existing FAQ destination is preserved inside
         * checkSimilarFaqs().
         */
        checkSimilarFaqs(faqBtn)
            .finally(() => {

                /*
                 * Allow the button to be used again after
                 * the request finishes.
                 */
                faqBtn.dataset.processing = "false";
            });


        /*
         * VERY IMPORTANT:
         *
         * Stop the global click handler here.
         *
         * Otherwise the click could continue into other
         * handlers below.
         */
        return;
    }

    // ================= SUPPORT REQUEST LIFECYCLE =================

/*
 * Handle all destructive/recovery actions through the same
 * confirmation system.
 *
 * We intentionally submit the original Laravel form instead
 * of manually making a fetch() request.
 *
 * This preserves:
 * - CSRF protection
 * - Laravel method spoofing
 * - server-side authorization
 * - the existing route definitions
 */
const lifecycleBtn = e.target.closest(
    ".delete-btn, .restore-btn, .permanent-delete-btn"
);

if (lifecycleBtn) {

    /*
     * Prevent the browser from submitting the form
     * before the administrator confirms the action.
     */
    e.preventDefault();


    /*
     * Locate the exact form containing the clicked button.
     */
    const lifecycleForm =
        lifecycleBtn.closest("form");


    /*
     * Fail safely if the button is not inside a form.
     */
    if (!lifecycleForm) {

        console.error(
            "Support request lifecycle form not found."
        );

        return;
    }


    /*
     * Determine which lifecycle operation was requested.
     *
     * We use the button's existing CSS class rather than
     * trusting a value supplied by the browser.
     */
    let config;


    /*
     * MOVE TO TRASH
     */
    if (lifecycleBtn.classList.contains("delete-btn")) {

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
        lifecycleBtn.classList.contains("restore-btn")
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
     * Show the appropriate confirmation dialog.
     */
    showAlertModal({

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


        /*
         * Only submit the original Laravel form after
         * explicit administrator confirmation.
         */
        onConfirm: () => {

            lifecycleForm.submit();

        }

    });


    /*
     * Stop the event from reaching other click handlers.
     */
    return;
}


    // ================= VIEW MODE =================
        const viewBtn = e.target.closest(".view-btn");
        if (viewBtn) {

            currentRequestId = viewBtn.dataset.id;

            document.getElementById("sr-id").value = currentRequestId;
            document.getElementById("sr-user").value = viewBtn.dataset.user;
            document.getElementById("sr-question").value = viewBtn.dataset.question;
            document.getElementById("sr-reply").value = viewBtn.dataset.answer || "";
            document.getElementById("sr-agency").value = viewBtn.dataset.agencyId || "";

            // 🔥 SMART MODE SWITCH
            if (viewBtn.dataset.answer) {
                form.action = `${form.dataset.updateUrl}/${currentRequestId}`;
                methodInput.value = "PUT";
                saveBtn.innerText = "Update Answer";
            } else {
                form.action = form.dataset.replyUrl;
                methodInput.value = "POST";
                saveBtn.innerText = "Mark as Answered";
            }

            modal.classList.add("active");
            return;
        }

        // ================= EDIT MODE =================
        const editBtn = e.target.closest(".edit-btn");
        if (editBtn) {

            const id = editBtn.dataset.id;
            const answer = editBtn.dataset.answer || "";

            currentRequestId = id;

            document.getElementById("sr-id").value = id;
            document.getElementById("sr-reply").value = answer;
            document.getElementById("sr-agency").value = editBtn.dataset.agencyId || "";

            // 🔒 Use consistent route structure (avoid hardcoded mismatch issues)
            form.action = `${form.dataset.updateUrl}/${id}`;
            methodInput.value = "PUT";
            saveBtn.innerText = "Update Answer";

            modal.classList.add("active");
            return;
        }

    });

    // =========================================================
// SIMILAR FAQ MODAL CONTROLS
// =========================================================

/*
 * Cancel button.
 *
 * The administrator decided not to continue with
 * the FAQ conversion.
 */
similarFaqCancel.addEventListener(
    "click",
    closeSimilarFaqModal
);


/*
 * Clicking the darkened backdrop closes the modal.
 *
 * Clicking inside the actual modal does not.
 */
similarFaqModal.addEventListener("click", (e) => {

    if (e.target === similarFaqModal) {
        closeSimilarFaqModal();
    }
});


/*
 * Allow keyboard users to close the modal with Escape.
 */
document.addEventListener("keydown", (e) => {

    if (
        e.key === "Escape" &&
        similarFaqModal.classList.contains("active")
    ) {
        closeSimilarFaqModal();
    }
});


/*
 * Continue to FAQ creation.
 *
 * We deliberately use the original Laravel-generated
 * href rather than constructing a route manually.
 */
similarFaqContinue.addEventListener("click", () => {

    if (!pendingFaqUrl) {
        return;
    }

    window.location.href =
        pendingFaqUrl;
});

    // ================= CLOSE MODAL =================
    function closeSupportModal(){
        modal.classList.remove("active");
    }

    window.closeSupportModal = closeSupportModal;

});