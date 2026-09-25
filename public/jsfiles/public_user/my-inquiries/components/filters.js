/**
 * KNOWURLOCAL
 * My Inquiries — Filters
 *
 * Controls the inquiry status filters and empty-state messaging.
 */

function initializeFilters() {
    "use strict";


    /*
    |--------------------------------------------------------------------------
    | STATUS HELPERS
    |--------------------------------------------------------------------------
    |
    | Each citizen-facing filter maps directly to one backend status.
    |
    | needs_attention → awaiting_confirmation
    | pending         → pending
    | follow_up       → needs_follow_up
    | answered        → answered
    |
    */


    const isAnsweredStatus = (status) => {
        return status === "answered";
    };


    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE ICON
    |--------------------------------------------------------------------------
    |
    | Creates the Phosphor icon through DOM APIs rather than innerHTML.
    | This avoids injecting HTML when we only need to change an icon.
    |
    */

    const updateEmptyIcon = (
        emptyIcon,
        iconClass
    ) => {
        if (!emptyIcon) {
            return;
        }

        const icon =
            document.createElement("i");

        icon.className =
            `ph-light ${iconClass}`;

        emptyIcon.replaceChildren(icon);
    };


    /*
    |--------------------------------------------------------------------------
    | EMPTY STATE
    |--------------------------------------------------------------------------
    */

    const updateEmptyState = (
        filter,
        inquiryCards
    ) => {

        const emptyState =
            document.querySelector(
                ".empty-state"
            );

        if (!emptyState) {
            return;
        }


        const emptyIcon =
            emptyState.querySelector(
                ".empty-icon"
            );


        const emptyHeading =
            emptyState.querySelector(
                "h2"
            );


        const emptyMessage =
            emptyState.querySelector(
                "p"
            );


        /*
        |--------------------------------------------------------------------------
        | Count only cards that are currently visible.
        |--------------------------------------------------------------------------
        */

        const visibleCards =
            inquiryCards.filter(
                (card) => !card.hidden
            );


        emptyState.hidden =
            visibleCards.length > 0;


        /*
        |--------------------------------------------------------------------------
        | If at least one inquiry matches the filter,
        | the empty state does not need additional updates.
        |--------------------------------------------------------------------------
        */

        if (visibleCards.length > 0) {
            return;
        }


        /*
        |--------------------------------------------------------------------------
        | NEEDS ATTENTION EMPTY STATE
        |--------------------------------------------------------------------------
        |
        | awaiting_confirmation means the office has responded
        | and the citizen still needs to review the response.
        |
        */

        if (filter === "needs_attention") {

            updateEmptyIcon(
                emptyIcon,
                "ph-chat-circle-check"
            );


            if (emptyHeading) {
                emptyHeading.textContent =
                    "Nothing needs your attention";
            }


            if (emptyMessage) {
                emptyMessage.textContent =
                    "You have no responses waiting for your review.";
            }


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | PENDING EMPTY STATE
        |--------------------------------------------------------------------------
        */

        if (filter === "pending") {

            updateEmptyIcon(
                emptyIcon,
                "ph-hourglass"
            );


            if (emptyHeading) {
                emptyHeading.textContent =
                    "No pending inquiries";
            }


            if (emptyMessage) {
                emptyMessage.textContent =
                    "You have no questions waiting for a response.";
            }


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | FOLLOW-UP EMPTY STATE
        |--------------------------------------------------------------------------
        |
        | needs_follow_up means the citizen already requested
        | additional clarification and is waiting for the office.
        |
        */

        if (filter === "follow_up") {

            updateEmptyIcon(
                emptyIcon,
                "ph-arrow-u-up-left"
            );


            if (emptyHeading) {
                emptyHeading.textContent =
                    "No follow-up inquiries";
            }


            if (emptyMessage) {
                emptyMessage.textContent =
                    "You have no inquiries waiting for a follow-up response.";
            }


            return;
        }


        /*
        |--------------------------------------------------------------------------
        | ANSWERED EMPTY STATE
        |--------------------------------------------------------------------------
        */

        updateEmptyIcon(
            emptyIcon,
            "ph-chat-circle-dots"
        );


        if (emptyHeading) {
            emptyHeading.textContent =
                "No answered inquiries yet";
        }


        if (emptyMessage) {
            emptyMessage.textContent =
                "Your submitted questions will appear here once an office responds.";
        }
    };


    /*
    |--------------------------------------------------------------------------
    | APPLY FILTER
    |--------------------------------------------------------------------------
    */

    const applyFilter = (
        filter,
        inquiryCards
    ) => {

        inquiryCards.forEach((card) => {

            const status =
                card.dataset.status;

            let shouldShow = false;


            /*
            |--------------------------------------------------------------------------
            | NEEDS ATTENTION
            |--------------------------------------------------------------------------
            |
            | The office has already responded.
            | The citizen needs to review and confirm the response.
            |
            */

            if (filter === "needs_attention") {

                shouldShow =
                    status === "awaiting_confirmation";
            }


            /*
            |--------------------------------------------------------------------------
            | PENDING
            |--------------------------------------------------------------------------
            |
            | The inquiry is still waiting for its initial
            | official response.
            |
            */

            if (filter === "pending") {

                shouldShow =
                    status === "pending";
            }


            /*
            |--------------------------------------------------------------------------
            | FOLLOW-UP
            |--------------------------------------------------------------------------
            |
            | The citizen has already requested additional
            | clarification and is waiting for the office.
            |
            */

            if (filter === "follow_up") {

                shouldShow =
                    status === "needs_follow_up";
            }


            /*
            |--------------------------------------------------------------------------
            | ANSWERED
            |--------------------------------------------------------------------------
            |
            | The inquiry has been completely resolved.
            |
            */

            if (filter === "answered") {

                shouldShow =
                    isAnsweredStatus(status);
            }


            /*
            |--------------------------------------------------------------------------
            | Update card visibility.
            |--------------------------------------------------------------------------
            */

            card.hidden =
                !shouldShow;


            /*
            |--------------------------------------------------------------------------
            | A hidden card must never remain visually expanded.
            |--------------------------------------------------------------------------
            |
            | Otherwise, switching filters could leave an expanded
            | inquiry occupying space even though the card itself
            | is supposed to be hidden.
            |
            */

            if (!shouldShow) {

                const toggle =
                    card.querySelector(
                        ".inquiry-toggle"
                    );


                const details =
                    card.querySelector(
                        ".inquiry-details"
                    );


                if (toggle) {

                    toggle.setAttribute(
                        "aria-expanded",
                        "false"
                    );
                }


                if (details) {

                    details.setAttribute(
                        "aria-hidden",
                        "true"
                    );
                }


                card.classList.remove(
                    "expanded"
                );
            }
        });


        updateEmptyState(
            filter,
            inquiryCards
        );
    };


    /*
    |--------------------------------------------------------------------------
    | GET CURRENT INQUIRY CARDS
    |--------------------------------------------------------------------------
    |
    | Query the DOM when needed instead of keeping a permanently
    | cached NodeList. This works better with realtime updates.
    |
    */

    const getInquiryCards = () => {

        return Array.from(
            document.querySelectorAll(
                ".inquiry-card"
            )
        );
    };


    /*
    |--------------------------------------------------------------------------
    | FILTER BUTTON EVENTS
    |--------------------------------------------------------------------------
    */

    const filterButtons =
        Array.from(
            document.querySelectorAll(
                ".inquiries-filter-tab[data-filter], [data-filter].filter-btn"
            )
        );


    filterButtons.forEach((button) => {

        button.addEventListener(
            "click",
            () => {

                const selectedFilter =
                    button.dataset.filter;


                if (!selectedFilter) {
                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | Update the visual and accessibility state
                | of every filter button.
                |--------------------------------------------------------------------------
                */

                filterButtons.forEach(
                    (filterButton) => {

                        const isActive =
                            filterButton === button;


                        filterButton.classList.toggle(
                            "active",
                            isActive
                        );


                        filterButton.setAttribute(
                            "aria-pressed",
                            isActive
                                ? "true"
                                : "false"
                        );
                    }
                );


                applyFilter(
                    selectedFilter,
                    getInquiryCards()
                );
            }
        );
    });


    /*
    |--------------------------------------------------------------------------
    | REALTIME FILTER SYNCHRONIZATION
    |--------------------------------------------------------------------------
    |
    | Realtime updates can change an inquiry's status without the user
    | clicking a filter button.
    |
    | Reapply whichever filter is currently active so the visible cards
    | always match their current status.
    |
    */

    window.addEventListener(
        "inquiry:updated",
        () => {

            const activeButton =
                filterButtons.find(
                    (button) =>
                        button.classList.contains(
                            "active"
                        )
                );


            const currentFilter =
                activeButton?.dataset.filter ||
                "needs_attention";


            applyFilter(
                currentFilter,
                getInquiryCards()
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | DEFAULT FILTER
    |--------------------------------------------------------------------------
    |
    | Needs Attention is the default because it contains inquiries
    | where the citizen currently has an action to perform:
    |
    | - Review the official response
    | - Decide whether the response resolved the concern
    |
    */

    const initialCards =
        getInquiryCards();


    const initialFilter =
        "needs_attention";


    /*
    |--------------------------------------------------------------------------
    | Synchronize the button's visual and accessibility state
    | with the default filter.
    |--------------------------------------------------------------------------
    */

    filterButtons.forEach(
        (filterButton) => {

            const isActive =
                filterButton.dataset.filter ===
                initialFilter;


            filterButton.classList.toggle(
                "active",
                isActive
            );


            filterButton.setAttribute(
                "aria-pressed",
                isActive
                    ? "true"
                    : "false"
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Apply the default filter immediately.
    |--------------------------------------------------------------------------
    */

    applyFilter(
        initialFilter,
        initialCards
    );
}


/*
|--------------------------------------------------------------------------
| ES MODULE EXPORT
|--------------------------------------------------------------------------
|
| index.js imports this exact function:
|
| import { initializeFilters } from './components/filters.js';
|
*/

export {
    initializeFilters
};