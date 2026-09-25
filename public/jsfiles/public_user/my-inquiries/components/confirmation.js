/**
 * KNOWURLOCAL
 * My Inquiries — Response Confirmation
 *
 * Handles citizen confirmation of an official response
 * and requests for additional follow-up.
 */

function initializeConfirmation() {
    "use strict";


    /*
    |--------------------------------------------------------------------------
    | CSRF TOKEN
    |--------------------------------------------------------------------------
    */

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");


    /*
    |--------------------------------------------------------------------------
    | GET INQUIRY CARD
    |--------------------------------------------------------------------------
    */

    const getCard = (element) => {
        return element?.closest(".inquiry-card") ?? null;
    };


    /*
    |--------------------------------------------------------------------------
    | GET CONFIRMATION CONTAINER
    |--------------------------------------------------------------------------
    */

    const getConfirmationContainer = (card) => {
        return card?.querySelector(
            ".response-confirmation"
        ) ?? null;
    };


    /*
    |--------------------------------------------------------------------------
    | BUSY STATE
    |--------------------------------------------------------------------------
    */

    const setBusy = (container, busy) => {
        if (!container) {
            return;
        }

        container.setAttribute(
            "aria-busy",
            busy ? "true" : "false"
        );

        container
            .querySelectorAll("button, textarea")
            .forEach((element) => {
                element.disabled = busy;
            });
    };


    /*
    |--------------------------------------------------------------------------
    | ERROR MESSAGE
    |--------------------------------------------------------------------------
    */

    const showError = (container, message) => {
        if (!container) {
            return;
        }

        let error = container.querySelector(
            ".confirmation-error"
        );

        if (!error) {
            error = document.createElement("p");

            error.className =
                "confirmation-error";

            error.setAttribute(
                "role",
                "alert"
            );

            container.appendChild(error);
        }

        error.textContent = message;
    };


    /*
    |--------------------------------------------------------------------------
    | CLEAR ERROR
    |--------------------------------------------------------------------------
    */

    const clearError = (container) => {
        container
            ?.querySelector(
                ".confirmation-error"
            )
            ?.remove();
    };


    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS LABEL
    |--------------------------------------------------------------------------
    */

    const updateStatusLabel = (card, status) => {
        const statusElement = card?.querySelector(
            ".inquiry-status-badge"
        );

        if (!statusElement) {
            return;
        }

        const statusConfig = {
            answered: {
                label: "Resolved",
                icon: "ph-check",
            },

            needs_follow_up: {
                label: "Follow-up needed",
                icon: "ph-arrow-counter-clockwise",
            },
        };

        const config = statusConfig[status];

        if (!config) {
            return;
        }

        /*
        | Rebuild the complete badge through DOM APIs.
        | This keeps the status label and icon synchronized
        | without injecting server-provided HTML.
        */
        statusElement.className =
            `inquiry-status-badge ${status}`;

        const icon = document.createElement("i");

        icon.className =
            `ph-light ${config.icon}`;

        icon.setAttribute(
            "aria-hidden",
            "true"
        );

        statusElement.replaceChildren(
            icon,
            document.createTextNode(config.label)
        );
    };


    /*
    |--------------------------------------------------------------------------
    | CONFIRM RESPONSE
    |--------------------------------------------------------------------------
    */

    const confirmResponse = async (button) => {
        const card = getCard(button);

        if (!card) {
            return;
        }

        const requestId = card.dataset.id;

        if (!requestId) {
            return;
        }

        const container =
            getConfirmationContainer(card);

        if (!container) {
            return;
        }

        if (!csrfToken) {
            showError(
                container,
                "Your session token could not be found. Please refresh the page and try again."
            );

            return;
        }

        clearError(container);

        setBusy(
            container,
            true
        );

        try {
            const response = await fetch(
                `/my-inquiries/${encodeURIComponent(requestId)}/confirm`,
                {
                    method: "POST",
                    credentials: "same-origin",
                    headers: {
                        "X-CSRF-TOKEN": csrfToken,
                        "Accept": "application/json",
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({}),
                }
            );

            let data = null;

            try {
                data = await response.json();
            } catch {
                data = null;
            }

            if (!response.ok || !data?.success) {
                throw new Error(
                    data?.message ||
                    `Request failed with status ${response.status}.`
                );
            }

            /*
            | Laravel is the authoritative source for the new status.
            */
            card.dataset.status =
                data.status || "answered";

            updateStatusLabel(
                card,
                card.dataset.status
            );

            /*
            | The confirmation UI is no longer needed after
            | the citizen accepts the response.
            */
            container.remove();

            /*
            | Give the citizen immediate visual feedback.
            */
            const successMessage =
                document.createElement("p");

            successMessage.className =
                "confirmation-success";

            successMessage.setAttribute(
                "role",
                "status"
            );

            successMessage.textContent =
                data.message ||
                "Your inquiry has been marked as resolved.";

            const details =
                card.querySelector(
                    ".inquiry-details"
                );

            if (details) {
                details.appendChild(
                    successMessage
                );
            }

        } catch (error) {
            console.error(
                "KNOWURLOCAL: Unable to confirm response.",
                error
            );

            showError(
                container,
                error.message ||
                "We could not confirm this response. Please try again."
            );

        } finally {
            setBusy(
                container,
                false
            );
        }
    };


    /*
    |--------------------------------------------------------------------------
    | SHOW FOLLOW-UP FORM
    |--------------------------------------------------------------------------
    */

    const showFollowUp = (button) => {
        const container =
            button.closest(
                ".response-confirmation"
            );

        if (!container) {
            return;
        }

        const actions =
            container.querySelector(
                ".confirmation-actions"
            );

        const form =
            container.querySelector(
                ".follow-up-form"
            );

        const textarea =
            form?.querySelector(
                'textarea[name="reason"]'
            );

        if (!form) {
            return;
        }

        clearError(
            container
        );

        /*
        | Hide the two initial choices and show
        | the follow-up form.
        */
        if (actions) {
            actions.hidden = true;
        }

        form.hidden = false;

        textarea?.focus();
    };


    /*
    |--------------------------------------------------------------------------
    | SUBMIT FOLLOW-UP
    |--------------------------------------------------------------------------
    */

    const submitFollowUp = async (form) => {
        const card =
            form.closest(
                ".inquiry-card"
            );

        if (!card) {
            return;
        }

        const requestId =
            card.dataset.id;

        if (!requestId) {
            return;
        }

        const container =
            getConfirmationContainer(
                card
            );

        if (!container) {
            return;
        }

        const textarea =
            form.querySelector(
                'textarea[name="reason"]'
            );

        const reason =
            textarea?.value.trim() || "";

        if (!csrfToken) {
            showError(
                container,
                "Your session token could not be found. Please refresh the page and try again."
            );

            return;
        }

        clearError(
            container
        );

        setBusy(
            container,
            true
        );

        try {
            const response =
                await fetch(
                    `/my-inquiries/${encodeURIComponent(requestId)}/follow-up`,
                    {
                        method: "POST",
                        credentials: "same-origin",
                        headers: {
                            "X-CSRF-TOKEN":
                                csrfToken,

                            "Accept":
                                "application/json",

                            "Content-Type":
                                "application/json",
                        },

                        body: JSON.stringify({
                            reason,
                        }),
                    }
                );

            let data = null;

            try {
                data = await response.json();
            } catch {
                data = null;
            }

            if (
                !response.ok ||
                !data?.success
            ) {
                throw new Error(
                    data?.message ||
                    `Request failed with status ${response.status}.`
                );
            }

            /*
            | Update the card's authoritative status.
            */
            card.dataset.status =
                data.status ||
                "needs_follow_up";

            updateStatusLabel(
                card,
                card.dataset.status
            );

            /*
            | Remove the confirmation controls because
            | the current response has now been reviewed.
            */
            container.remove();

            /*
            | Show a clear result to the citizen.
            */
            const submittedMessage =
                document.createElement("p");

            submittedMessage.className =
                "confirmation-success";

            submittedMessage.setAttribute(
                "role",
                "status"
            );

            submittedMessage.textContent =
                data.message ||
                "Your inquiry has been returned for follow-up.";

            const details =
                card.querySelector(
                    ".inquiry-details"
                );

            if (details) {
                details.appendChild(
                    submittedMessage
                );
            }

        } catch (error) {
            console.error(
                "KNOWURLOCAL: Unable to request follow-up.",
                error
            );

            showError(
                container,
                error.message ||
                "We could not submit your follow-up request. Please try again."
            );

        } finally {
            setBusy(
                container,
                false
            );
        }
    };


    /*
    |--------------------------------------------------------------------------
    | CLICK EVENTS
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "click",
        (event) => {
            const confirmButton =
                event.target.closest(
                    "[data-confirm-response]"
                );

            if (confirmButton) {
                event.preventDefault();

                confirmResponse(
                    confirmButton
                );

                return;
            }


            const followUpButton =
                event.target.closest(
                    "[data-follow-up-response]"
                );

            if (followUpButton) {
                event.preventDefault();

                showFollowUp(
                    followUpButton
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | FORM SUBMISSION
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        "submit",
        (event) => {
            const form =
                event.target.closest(
                    ".follow-up-form"
                );

            if (!form) {
                return;
            }

            event.preventDefault();

            submitFollowUp(
                form
            );
        }
    );
}


/*
|--------------------------------------------------------------------------
| ES MODULE EXPORT
|--------------------------------------------------------------------------
|
| index.js imports this exact function:
|
| import {
|     initializeConfirmation
| } from './components/confirmation.js';
|
*/

export {
    initializeConfirmation
};