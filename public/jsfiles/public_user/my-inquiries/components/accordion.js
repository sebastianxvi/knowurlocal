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
            ".unread-inquiry-dot, .unread-dot"
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
        | The legacy "seen" endpoint is only relevant once
        | the inquiry has reached the answered state.
        */
        if (card.dataset.status === "answered") {
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