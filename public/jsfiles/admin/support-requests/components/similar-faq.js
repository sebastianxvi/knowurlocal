/*
|--------------------------------------------------------------------------
| KNOWURLOCAL — Similar FAQ
|--------------------------------------------------------------------------
|
| This module owns the "Add to FAQ" similarity-check workflow.
|
| Responsibilities:
|
| - Open/close Similar FAQ modal
| - Request similar FAQs from Laravel
| - Render loading/results/error states
| - Validate same-origin destinations
| - Navigate to FAQ creation after confirmation
|
| It does NOT:
|
| - Manage the Support Request modal
| - Manage the response builder
| - Build the Support Request table
| - Manage Laravel Echo
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| MODULE STATE
|--------------------------------------------------------------------------
*/

let similarFaqModal = null;

let similarFaqMessage = null;

let similarFaqResults = null;

let similarFaqCancel = null;

let similarFaqContinue = null;

let pendingFaqUrl = null;

let csrfToken = "";

let isInitialized = false;


/*
|--------------------------------------------------------------------------
| SHARED CLIENT MESSAGE
|--------------------------------------------------------------------------
|
| The application already provides modal-system.js.
|
| We use that system instead of introducing another notification
| implementation.
|--------------------------------------------------------------------------
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

            icon:
                "!",

            variant:
                "danger",

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
            },
        });

        return;
    }

    /*
     * Fallback for environments where the shared modal
     * infrastructure isn't available.
     */
    window.alert(text);
}


/*
|--------------------------------------------------------------------------
| MODAL STATE
|--------------------------------------------------------------------------
*/


/**
 * Safely update modal visibility and accessibility state.
 *
 * @param {HTMLElement|null} modal
 * @param {boolean} isOpen
 */
function setModalState(
    modal,
    isOpen
) {
    if (!modal) {
        return;
    }

    modal.classList.toggle(
        "active",
        isOpen
    );

    modal.setAttribute(
        "aria-hidden",
        isOpen
            ? "false"
            : "true"
    );
}


/**
 * Open the Similar FAQ modal.
 */
function openSimilarFaqModal() {
    setModalState(
        similarFaqModal,
        true
    );
}


/**
 * Close the Similar FAQ modal.
 */
function closeSimilarFaqModal() {
    setModalState(
        similarFaqModal,
        false
    );

    /*
     * The URL is cleared whenever the modal closes.
     *
     * This prevents an old FAQ destination from being reused
     * accidentally if the next request fails.
     */
    pendingFaqUrl =
        null;
}


/*
|--------------------------------------------------------------------------
| RENDERING
|--------------------------------------------------------------------------
|
| Database/API values are always assigned through textContent.
|
| We intentionally avoid innerHTML because FAQ content originates
| outside this JavaScript module.
|--------------------------------------------------------------------------
*/


/**
 * Render the loading state.
 */
function renderSimilarFaqLoading() {
    if (!similarFaqResults) {
        return;
    }

    similarFaqResults.replaceChildren();


    const wrapper =
        document.createElement(
            "div"
        );

    wrapper.className =
        "similar-faq-loading";


    const icon =
        document.createElement(
            "i"
        );

    icon.className =
        "ph-light ph-spinner-gap";


    const text =
        document.createElement(
            "span"
        );

    text.textContent =
        "Checking existing FAQs...";


    wrapper.appendChild(
        icon
    );

    wrapper.appendChild(
        text
    );

    similarFaqResults.appendChild(
        wrapper
    );
}


/**
 * Render an empty/error state.
 *
 * @param {string} iconClass
 * @param {string} message
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
        document.createElement(
            "div"
        );

    wrapper.className =
        "similar-faq-empty";


    const icon =
        document.createElement(
            "i"
        );

    icon.className =
        iconClass;


    const text =
        document.createElement(
            "div"
        );

    text.textContent =
        message;


    wrapper.appendChild(
        icon
    );

    wrapper.appendChild(
        text
    );

    similarFaqResults.appendChild(
        wrapper
    );
}


/**
 * Render FAQ matches returned by Laravel.
 *
 * @param {Array} matches
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
            document.createElement(
                "div"
            );

        item.className =
            "similar-faq-item";


        /*
         * Similarity percentage.
         */
        const score =
            document.createElement(
                "div"
            );

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


        /*
         * FAQ content container.
         */
        const content =
            document.createElement(
                "div"
            );

        content.className =
            "similar-faq-content";


        /*
         * Question.
         */
        const question =
            document.createElement(
                "p"
            );

        question.className =
            "similar-faq-question";

        question.textContent =
            match.question ||
            "Untitled FAQ";


        /*
         * Agency.
         */
        const agency =
            document.createElement(
                "p"
            );

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
|--------------------------------------------------------------------------
| URL SECURITY
|--------------------------------------------------------------------------
|
| The FAQ destination comes from server-rendered data attributes.
|
| Even though the server generates these URLs, we still validate
| the destination before allowing browser navigation.
|
| The FAQ flow must remain inside the current application origin.
|--------------------------------------------------------------------------
*/


/**
 * Determine whether a URL belongs to the current application origin.
 *
 * @param {string} value
 * @returns {boolean}
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
|--------------------------------------------------------------------------
| SIMILAR FAQ REQUEST
|--------------------------------------------------------------------------
*/


/**
 * Check whether similar FAQs already exist before opening
 * the FAQ creation page.
 *
 * @param {HTMLAnchorElement} faqButton
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
    | Validate FAQ destination
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


    /*
     * Store the verified destination temporarily.
     */
    pendingFaqUrl =
        faqUrl;


    /*
    |--------------------------------------------------------------------------
    | Validate similarity endpoint
    |--------------------------------------------------------------------------
    |
    | If the endpoint isn't available, we can still safely continue
    | to the server-generated FAQ URL.
    |--------------------------------------------------------------------------
    */

    if (
        !similarUrl ||
        !isSameOriginUrl(similarUrl)
    ) {
        window.location.href =
            pendingFaqUrl;

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Prepare modal
    |--------------------------------------------------------------------------
    */

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
        /*
        |--------------------------------------------------------------------------
        | Request similarity results
        |--------------------------------------------------------------------------
        */

        const response =
            await fetch(
                similarUrl,
                {
                    method:
                        "POST",

                    headers: {
                        "X-CSRF-TOKEN":
                            csrfToken,

                        "Accept":
                            "application/json",
                    },

                    credentials:
                        "same-origin",
                }
            );


        /*
        |--------------------------------------------------------------------------
        | Parse response
        |--------------------------------------------------------------------------
        |
        | Some Laravel errors can return HTML instead of JSON.
        | Therefore JSON parsing is deliberately defensive.
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
        | Handle HTTP/application errors
        |--------------------------------------------------------------------------
        */

        if (
            !response.ok ||
            !data?.success
        ) {
            throw new Error(
                data?.message ||
                "Unable to check similar FAQs."
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Normalize matches
        |--------------------------------------------------------------------------
        */

        const matches =
            Array.isArray(
                data.matches
            )
                ? data.matches
                : [];


        /*
        |--------------------------------------------------------------------------
        | Render result
        |--------------------------------------------------------------------------
        */

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


        /*
        * The Continue button becomes available only after
        * the server has returned a valid response.
        */
        if (similarFaqContinue) {
            similarFaqContinue.disabled =
                false;
        }

    } catch (error) {
        /*
        |--------------------------------------------------------------------------
        | Safe client-side error handling
        |--------------------------------------------------------------------------
        |
        | The administrator receives a generic message rather than
        | raw server exceptions or implementation details.
        |--------------------------------------------------------------------------
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


        /*
         * The administrator may still continue manually.
         */
        if (similarFaqContinue) {
            similarFaqContinue.disabled =
                false;
        }
    }
}


/*
|--------------------------------------------------------------------------
| EVENT HANDLERS
|--------------------------------------------------------------------------
*/


/**
 * Handle clicks on the Similar FAQ cancel button.
 */
function handleCancelClick() {
    closeSimilarFaqModal();
}


/**
 * Handle clicking the modal backdrop.
 *
 * Clicking inside the dialog itself should not close the modal.
 */
function handleBackdropClick(
    event
) {
    if (
        event.target ===
        similarFaqModal
    ) {
        closeSimilarFaqModal();
    }
}


/**
 * Continue to FAQ creation.
 */
function handleContinueClick() {
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


/**
 * Handle Add to FAQ links.
 *
 * Event delegation is intentional because realtime.js can create
 * table rows after initial page load.
 */
function handleDocumentClick(
    event
) {
    const faqButton =
        event.target.closest(
            ".faq-btn"
        );

    if (!faqButton) {
        return;
    }


    event.preventDefault();


    /*
     * Prevent duplicate requests caused by repeated clicks.
     */
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
}


/*
|--------------------------------------------------------------------------
| INITIALIZATION
|--------------------------------------------------------------------------
*/


/**
 * Initialize the Similar FAQ module.
 */
function initializeSimilarFaq() {
    if (isInitialized) {
        return;
    }

    isInitialized = true;


    /*
     * Locate modal elements.
     */
    similarFaqModal =
        document.getElementById(
            "similar-faq-modal-back"
        );

    similarFaqMessage =
        document.getElementById(
            "similar-faq-message"
        );

    similarFaqResults =
        document.getElementById(
            "similar-faq-results"
        );

    similarFaqCancel =
        document.getElementById(
            "similar-faq-cancel"
        );

    similarFaqContinue =
        document.getElementById(
            "similar-faq-continue"
        );


    /*
     * Read Laravel's CSRF token from the page.
     */
    csrfToken =
        document
            .querySelector(
                'meta[name="csrf-token"]'
            )
            ?.getAttribute(
                "content"
            ) || "";


    /*
     * Modal controls.
     */
    similarFaqCancel?.addEventListener(
        "click",
        handleCancelClick
    );

    similarFaqModal?.addEventListener(
        "click",
        handleBackdropClick
    );

    similarFaqContinue?.addEventListener(
        "click",
        handleContinueClick
    );


    /*
     * Delegated FAQ action.
     *
     * This also handles rows inserted later by realtime.js.
     */
    document.addEventListener(
        "click",
        handleDocumentClick
    );
}


/*
|--------------------------------------------------------------------------
| PUBLIC MODULE API
|--------------------------------------------------------------------------
*/

export {
    initializeSimilarFaq,
    openSimilarFaqModal,
    closeSimilarFaqModal,
};