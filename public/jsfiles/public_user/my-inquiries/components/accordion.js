/**
 * KNOWURLOCAL
 * My Inquiries — Accordion
 *
 * Handles expanding and collapsing inquiry cards.
 * Uses event delegation so the behavior remains reliable
 * even when card content is updated by realtime JavaScript.
 */

function initializeAccordion() {
    "use strict";

    /*
    |--------------------------------------------------------------------------
    | CLOSE INQUIRY
    |--------------------------------------------------------------------------
    */

    const closeInquiry = (card) => {
        if (!card) {
            return;
        }

        const toggle = card.querySelector(
            ".inquiry-toggle"
        );

        const details = card.querySelector(
            ".inquiry-details"
        );

        if (!toggle || !details) {
            return;
        }

        card.classList.remove("expanded");

        toggle.setAttribute(
            "aria-expanded",
            "false"
        );

        details.setAttribute(
            "aria-hidden",
            "true"
        );

        /*
        | The hidden attribute is semantic and visual.
        | Removing it is required before the CSS grid transition
        | can reveal the detail region.
        */
        details.hidden = true;
    };


    /*
    |--------------------------------------------------------------------------
    | MARK ANSWER AS SEEN
    |--------------------------------------------------------------------------
    */

    const markAnswerAsSeen = async (card) => {
        const requestId = card?.dataset.id;

        if (!requestId) {
            return;
        }

        /*
        | Support both the current and legacy unread indicator names.
        */
        const unreadIndicator = card.querySelector(
            ".inquiry-unread-badge, .unread-inquiry-dot, .unread-dot"
        );

        /*
        | If there is nothing to mark as seen, there is no reason
        | to make an unnecessary request to Laravel.
        */
        if (!unreadIndicator) {
            return;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        if (!csrfToken) {
            console.warn(
                "KNOWURLOCAL: CSRF token was not found."
            );

            return;
        }

        try {
            const response = await fetch(
                `/my-inquiries/${encodeURIComponent(requestId)}/seen`,
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

            if (!response.ok) {
                throw new Error(
                    `Request failed with status ${response.status}.`
                );
            }

            /*
            | Only remove the indicator after Laravel confirms
            | that the request succeeded.
            */
            unreadIndicator.remove();

            // Keep the client-side card state aligned with the server so
            // subsequent UI refreshes do not treat this response as new.
            card.dataset.answerSeen = "1";

            window.dispatchEvent(
                new CustomEvent("inquiry:notification-changed")
            );

        } catch (error) {
            /*
            | A failed "seen" request must never prevent the citizen
            | from reading the inquiry or its response.
            */
            console.warn(
                "KNOWURLOCAL: Unable to mark inquiry response as seen.",
                error
            );
        }
    };



    /*
    |--------------------------------------------------------------------------
    | MARK TRASHED INQUIRY AS SEEN
    |--------------------------------------------------------------------------
    */

    const markTrashAsSeen = async (card) => {
        const requestId = card?.dataset.id;

        if (!requestId || card.dataset.status !== "trashed") {
            return;
        }

        const unreadIndicator = card.querySelector(
            '[data-unread-trash], .inquiry-unread-badge[data-unread-type="trash"]'
        );

        if (!unreadIndicator) {
            return;
        }

        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");

        if (!csrfToken) {
            console.warn("KNOWURLOCAL: CSRF token was not found.");
            return;
        }

        try {
            const response = await fetch(
                `/my-inquiries/${encodeURIComponent(requestId)}/trash-seen`,
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

            if (!response.ok) {
                throw new Error(
                    `Trash seen request failed with status ${response.status}.`
                );
            }

            const data = await response.json();

            if (data?.success !== true) {
                throw new Error("Invalid trash seen response.");
            }

            unreadIndicator.remove();

            window.setTrashedInquiryUnreadCount?.(
                data.trashed_unread_count ?? 0
            );

            window.dispatchEvent(
                new CustomEvent("inquiry:notification-changed")
            );
        } catch (error) {
            console.warn(
                "KNOWURLOCAL: Unable to mark trashed inquiry as seen.",
                error
            );
        }
    };


    /*
    |--------------------------------------------------------------------------
    | OPEN INQUIRY
    |--------------------------------------------------------------------------
    */

    const openInquiry = (card) => {
        if (!card) {
            return;
        }

        const toggle = card.querySelector(
            ".inquiry-toggle"
        );

        const details = card.querySelector(
            ".inquiry-details"
        );

        if (!toggle || !details) {
            return;
        }

        card.classList.add("expanded");

        toggle.setAttribute(
            "aria-expanded",
            "true"
        );

        details.setAttribute(
            "aria-hidden",
            "false"
        );

        /*
        | Release the native hidden state so the expandable
        | region can participate in the CSS grid animation.
        */
        details.hidden = false;

        if (
            card.dataset.status === "trashed"
        ) {
            markTrashAsSeen(card);
            return;
        }

        /*
        | Response notifications are acknowledged only after
        | the citizen opens the inquiry.
        */
        if (
            card.dataset.status === "awaiting_confirmation" ||
            card.dataset.status === "answered"
        ) {
            markAnswerAsSeen(card);
        }
    };


    /*
    |--------------------------------------------------------------------------
    | TOGGLE INQUIRY
    |--------------------------------------------------------------------------
    */

    const toggleInquiry = (card) => {
        if (!card) {
            return;
        }

        const toggle = card.querySelector(
            ".inquiry-toggle"
        );

        if (!toggle) {
            return;
        }

        const isExpanded =
            toggle.getAttribute("aria-expanded") === "true";


        /*
        | Find all currently rendered inquiry cards.
        |
        | We intentionally query the DOM here instead of storing
        | the cards when initialization happens. This makes the
        | accordion compatible with cards whose contents are
        | updated later by realtime JavaScript.
        */
        const inquiryCards = document.querySelectorAll(
            ".inquiry-card"
        );


        /*
        | Close every other inquiry first.
        |
        | This creates a single-open accordion, preventing several
        | large response sections from occupying the screen at once.
        */
        inquiryCards.forEach((otherCard) => {
            if (otherCard !== card) {
                closeInquiry(otherCard);
            }
        });


        /*
        | If the selected inquiry was already open,
        | close it instead.
        */
        if (isExpanded) {
            closeInquiry(card);
            return;
        }

        openInquiry(card);
    };


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE EXISTING INQUIRIES
    |--------------------------------------------------------------------------
    */

    const inquiryCards = document.querySelectorAll(
        ".inquiry-card"
    );

    inquiryCards.forEach((card) => {
        const toggle = card.querySelector(
            ".inquiry-toggle"
        );

        const details = card.querySelector(
            ".inquiry-details"
        );

        if (!toggle || !details) {
            return;
        }

        /*
        | Connect the toggle to the expandable region.
        |
        | This allows assistive technologies such as screen readers
        | to understand which element the button controls.
        */
        if (details.id) {
            toggle.setAttribute(
                "aria-controls",
                details.id
            );
        }

        /*
        | All inquiries start collapsed.
        */
        closeInquiry(card);
    });


    /*
    |--------------------------------------------------------------------------
    | EVENT DELEGATION
    |--------------------------------------------------------------------------
    |
    | Instead of attaching a click listener to every toggle,
    | listen once on the document.
    |
    | This is useful because KNOWURLOCAL can update inquiry
    | content through realtime events.
    |
    */

    document.addEventListener(
        "click",
        (event) => {
            const toggle = event.target.closest(
                ".inquiry-toggle"
            );

            if (!toggle) {
                return;
            }

            const card = toggle.closest(
                ".inquiry-card"
            );

            if (!card) {
                return;
            }

            toggleInquiry(card);
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
| import { initializeAccordion } from './components/accordion.js';
|
*/

export {
    initializeAccordion
};