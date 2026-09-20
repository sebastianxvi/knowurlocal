document.addEventListener("DOMContentLoaded", () => {
    "use strict";

    /*
    |--------------------------------------------------------------------------
    | DOM REFERENCES
    |--------------------------------------------------------------------------
    |
    | Cache elements once instead of repeatedly querying the document.
    | This keeps the interaction logic easier to read and avoids unnecessary
    | DOM lookups.
    |
    */

    const inquiryCards = Array.from(
        document.querySelectorAll(".inquiry-card")
    );

    const filterButtons = Array.from(
        document.querySelectorAll("[data-filter]")
    );

    const inquiryToggles = Array.from(
        document.querySelectorAll(".inquiry-toggle")
    );

    const imageTriggers = Array.from(
        document.querySelectorAll("[data-image-preview]")
    );

    const imageLightbox = document.getElementById("image-lightbox");

    const imageLightboxImage = document.getElementById(
        "image-lightbox-image"
    );

    const imageLightboxClose = document.getElementById(
        "image-lightbox-close"
    );


    /*
    |--------------------------------------------------------------------------
    | REDUCED MOTION
    |--------------------------------------------------------------------------
    |
    | Respect users who have requested reduced motion at the operating-system
    | level. The CSS also handles this, but keeping the preference available
    | here lets us avoid unnecessary animation-related behavior in JavaScript.
    |
    */

    const prefersReducedMotion = window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;


    /*
    |--------------------------------------------------------------------------
    | ACCORDION HELPERS
    |--------------------------------------------------------------------------
    |
    | Keeping accordion state changes inside small functions prevents the
    | click handlers from becoming difficult to maintain.
    |
    */

    const closeInquiry = (card) => {
        if (!card) {
            return;
        }

        const toggle = card.querySelector(".inquiry-toggle");
        const details = card.querySelector(".inquiry-details");

        if (!toggle || !details) {
            return;
        }

        card.classList.remove("expanded");

        toggle.setAttribute("aria-expanded", "false");

        /*
        | Keep the details region semantically hidden when collapsed.
        | The CSS controls the visual animation.
        */
        details.setAttribute("aria-hidden", "true");
    };


    const openInquiry = (card) => {
        if (!card) {
            return;
        }

        const toggle = card.querySelector(".inquiry-toggle");
        const details = card.querySelector(".inquiry-details");

        if (!toggle || !details) {
            return;
        }

        card.classList.add("expanded");

        toggle.setAttribute("aria-expanded", "true");

        details.setAttribute("aria-hidden", "false");

        /*
        | Answered inquiries may contain an unread response.
        | Marking it as seen happens only when the user actually opens it.
        */
        if (card.dataset.status === "answered") {
            markAnswerAsSeen(card);
        }
    };


    const toggleInquiry = (card) => {
        if (!card) {
            return;
        }

        const toggle = card.querySelector(".inquiry-toggle");

        if (!toggle) {
            return;
        }

        const isExpanded =
            toggle.getAttribute("aria-expanded") === "true";

        /*
        | Close every other card first.
        | This keeps the page visually calm and prevents several large
        | answers from being expanded simultaneously.
        */
        inquiryCards.forEach((otherCard) => {
            if (otherCard !== card) {
                closeInquiry(otherCard);
            }
        });

        if (isExpanded) {
            closeInquiry(card);
            return;
        }

        openInquiry(card);
    };


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE ACCORDION ACCESSIBILITY STATE
    |--------------------------------------------------------------------------
    |
    | The Blade now gives every details region an ID and connects it to its
    | button using aria-controls.
    |
    | aria-hidden provides an additional semantic indication of whether the
    | details content is currently exposed.
    |
    */

    inquiryCards.forEach((card) => {
        const toggle = card.querySelector(".inquiry-toggle");
        const details = card.querySelector(".inquiry-details");

        if (!toggle || !details) {
            return;
        }

        const detailsId = details.id;

        if (detailsId) {
            toggle.setAttribute("aria-controls", detailsId);
        }

        closeInquiry(card);
    });


    /*
    |--------------------------------------------------------------------------
    | ACCORDION EVENTS
    |--------------------------------------------------------------------------
    */

    inquiryToggles.forEach((toggle) => {
        toggle.addEventListener("click", () => {
            const card = toggle.closest(".inquiry-card");

            toggleInquiry(card);
        });
    });


    /*
|--------------------------------------------------------------------------
| FILTERING
|--------------------------------------------------------------------------
|
| My Inquiries intentionally has only two states:
|
|   answered → questions that received a response
|   pending  → questions still waiting for a response
|
| Answered is the default because it provides the user with useful
| information immediately when they open the page.
|
*/

const applyFilter = (filter) => {
    inquiryCards.forEach((card) => {
        const status = card.dataset.status;

        const shouldShow = status === filter;

        card.hidden = !shouldShow;

        /*
        | Collapse cards that are being hidden.
        | This prevents an expanded inquiry from remaining visually open
        | when the user switches between Answered and Pending.
        */
        if (!shouldShow) {
            closeInquiry(card);
        }
    });

    updateEmptyState(filter);
};


filterButtons.forEach((button) => {
    button.addEventListener("click", () => {
        const selectedFilter = button.dataset.filter;

        if (!selectedFilter) {
            return;
        }

        /*
        | Update the selected tab visually and for assistive technology.
        */
        filterButtons.forEach((filterButton) => {
            const isActive =
                filterButton === button;

            filterButton.classList.toggle(
                "active",
                isActive
            );

            filterButton.setAttribute(
                "aria-pressed",
                isActive ? "true" : "false"
            );
        });

        applyFilter(selectedFilter);
    });
});


/*
|--------------------------------------------------------------------------
| DEFAULT FILTER
|--------------------------------------------------------------------------
|
| Answered is intentionally selected when the page first loads.
|
*/

const initialFilter = "answered";

applyFilter(initialFilter);

/*
|--------------------------------------------------------------------------
| CONTEXTUAL EMPTY STATE
|--------------------------------------------------------------------------
|
| The empty state changes depending on which tab the user selected.
| This gives the user a useful explanation instead of a generic
| "No inquiries yet" message.
|
*/

const updateEmptyState = (filter) => {
    const emptyState = document.querySelector(".empty-state");

    if (!emptyState) {
        return;
    }

    const emptyIcon = emptyState.querySelector(".empty-icon");
    const emptyHeading = emptyState.querySelector("h2");
    const emptyMessage = emptyState.querySelector("p");

    const visibleCards = inquiryCards.filter(
        (card) => !card.hidden
    );

    const hasVisibleCards = visibleCards.length > 0;

    emptyState.hidden = hasVisibleCards;

    if (hasVisibleCards) {
        return;
    }

    if (filter === "pending") {
        if (emptyIcon) {
            emptyIcon.innerHTML =
                '<i class="ph-light ph-hourglass"></i>';
        }

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

    if (emptyIcon) {
        emptyIcon.innerHTML =
            '<i class="ph-light ph-chat-circle-dots"></i>';
    }

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
    | MARK ANSWER AS SEEN
    |--------------------------------------------------------------------------
    |
    | The CSRF token is read from the page instead of being hard-coded.
    | The request uses same-origin credentials and JSON headers.
    |
    */

    async function markAnswerAsSeen(card) {
        if (!card) {
            return;
        }

        const requestId = card.dataset.id;

        if (!requestId) {
            return;
        }

        /*
        | If the unread indicator is already gone, there is nothing left
        | to update.
        */
        const unreadIndicator =
            card.querySelector(".unread-dot");

        if (!unreadIndicator) {
            return;
        }

        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')
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
            | Remove the unread indicator only after the server confirms
            | that the request succeeded.
            */
            unreadIndicator.remove();

        } catch (error) {
            /*
            | Do not block the user from reading their response if the
            | "seen" request fails.
            */
            console.warn(
                "KNOWURLOCAL: Unable to mark inquiry response as seen.",
                error
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | IMAGE LIGHTBOX
    |--------------------------------------------------------------------------
    |
    | The lightbox intentionally remains independent from the accordion.
    | This keeps image viewing from interfering with inquiry state.
    |
    */

    let previouslyFocusedElement = null;

    let originalBodyOverflow = "";


    const openImageLightbox = (trigger) => {
        if (!imageLightbox || !imageLightboxImage || !trigger) {
            return;
        }

        const imageSource =
            trigger.dataset.imagePreview ||
            trigger.dataset.imageSrc;

        if (!imageSource) {
            return;
        }

        const imageAlt =
            trigger.dataset.imageAlt ||
            trigger
                .closest(".inquiry-image-block")
                ?.querySelector(".inquiry-attached-image")
                ?.getAttribute("alt") ||
            "Attached inquiry image";

        previouslyFocusedElement =
            document.activeElement;

        originalBodyOverflow =
            document.body.style.overflow;

        imageLightboxImage.src = imageSource;
        imageLightboxImage.alt = imageAlt;

        imageLightbox.classList.add("is-open");

        imageLightbox.setAttribute(
            "aria-hidden",
            "false"
        );

        /*
        | Prevent background scrolling while the modal is open.
        */
        document.body.style.overflow = "hidden";

        /*
        | Move keyboard focus into the dialog.
        */
        imageLightboxClose?.focus();
    };


    const closeImageLightbox = () => {
        if (!imageLightbox || !imageLightboxImage) {
            return;
        }

        imageLightbox.classList.remove("is-open");

        imageLightbox.setAttribute(
            "aria-hidden",
            "true"
        );

        /*
        | Clear the image source after closing.
        | This avoids keeping a potentially large image loaded unnecessarily.
        */
        imageLightboxImage.removeAttribute("src");
        imageLightboxImage.removeAttribute("alt");

        document.body.style.overflow =
            originalBodyOverflow;

        /*
        | Return focus to the element that opened the dialog.
        | This is important for keyboard and assistive-technology users.
        */
        if (
            previouslyFocusedElement &&
            document.contains(previouslyFocusedElement)
        ) {
            previouslyFocusedElement.focus();
        }

        previouslyFocusedElement = null;
    };


    /*
    |--------------------------------------------------------------------------
    | IMAGE TRIGGER EVENTS
    |--------------------------------------------------------------------------
    */

    imageTriggers.forEach((trigger) => {
        trigger.addEventListener("click", () => {
            openImageLightbox(trigger);
        });
    });


    /*
    |--------------------------------------------------------------------------
    | LIGHTBOX CLOSE BUTTON
    |--------------------------------------------------------------------------
    */

    imageLightboxClose?.addEventListener(
        "click",
        closeImageLightbox
    );


    /*
    |--------------------------------------------------------------------------
    | LIGHTBOX BACKDROP
    |--------------------------------------------------------------------------
    |
    | Clicking the dark backdrop closes the modal.
    | Clicking the actual image does not.
    |
    */

    imageLightbox?.addEventListener("click", (event) => {
        if (event.target === imageLightbox) {
            closeImageLightbox();
        }
    });


    /*
    |--------------------------------------------------------------------------
    | GLOBAL KEYBOARD HANDLING
    |--------------------------------------------------------------------------
    |
    | Escape closes the lightbox first.
    |
    */

    document.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") {
            return;
        }

        if (
            imageLightbox &&
            imageLightbox.classList.contains("is-open")
        ) {
            closeImageLightbox();
        }
    });


    /*
    |--------------------------------------------------------------------------
    | IMAGE LOAD FAILURE
    |--------------------------------------------------------------------------
    |
    | If an attachment has been deleted or the storage URL becomes invalid,
    | prevent the browser from displaying a broken-image state inside the
    | modal.
    |
    */

    imageLightboxImage?.addEventListener(
        "error",
        () => {
            closeImageLightbox();

            console.warn(
                "KNOWURLOCAL: The inquiry image could not be loaded."
            );
        }
    );


    /*
    |--------------------------------------------------------------------------
    | REDUCED MOTION SAFETY
    |--------------------------------------------------------------------------
    |
    | The CSS handles the actual reduced-motion transition changes.
    | This variable is intentionally kept available for future interaction
    | enhancements without having to query the media preference repeatedly.
    |
    */

    if (prefersReducedMotion) {
        document.documentElement.classList.add(
            "prefers-reduced-motion"
        );
    }
});