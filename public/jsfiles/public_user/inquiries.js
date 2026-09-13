document.addEventListener("DOMContentLoaded", () => {

    /*
     * =====================================================
     * FILTERING
     * =====================================================
     */

    const filterButtons =
        document.querySelectorAll(".filter-btn");

    const inquiryCards =
        document.querySelectorAll(".inquiry-card");


    filterButtons.forEach(button => {

        button.addEventListener("click", () => {

            filterButtons.forEach(item => {
                item.classList.remove("active");
            });

            button.classList.add("active");


            const filter =
                button.dataset.filter;


            inquiryCards.forEach(card => {

                const status =
                    card.dataset.status;

                const shouldShow =
                    filter === "all" ||
                    filter === status;


                card.hidden = !shouldShow;


                /*
                 * Collapse cards that become hidden.
                 */

                if (!shouldShow) {

                    card.classList.remove("expanded");

                    const toggle =
                        card.querySelector(".inquiry-toggle");

                    if (toggle) {

                        toggle.setAttribute(
                            "aria-expanded",
                            "false"
                        );

                    }

                }

            });

        });

    });


    /*
     * =====================================================
     * INQUIRY EXPANSION
     * =====================================================
     */

    inquiryCards.forEach(card => {

        const toggle =
            card.querySelector(".inquiry-toggle");

        if (!toggle) {
            return;
        }


        toggle.addEventListener("click", async () => {

            const isExpanded =
                card.classList.contains("expanded");


            /*
             * Collapse every other inquiry first.
             */

            inquiryCards.forEach(otherCard => {

                if (otherCard === card) {
                    return;
                }

                otherCard.classList.remove("expanded");

                const otherToggle =
                    otherCard.querySelector(".inquiry-toggle");

                if (otherToggle) {

                    otherToggle.setAttribute(
                        "aria-expanded",
                        "false"
                    );

                }

            });


            /*
             * Open or close the clicked inquiry.
             */

            card.classList.toggle(
                "expanded",
                !isExpanded
            );


            toggle.setAttribute(
                "aria-expanded",
                String(!isExpanded)
            );


            /*
             * Only mark the inquiry as seen when:
             *
             * 1. The user is opening it.
             * 2. The inquiry has already been answered.
             */

            if (
                !isExpanded &&
                card.dataset.status === "answered"
            ) {

                await markAnswerAsSeen(card);

            }

        });

    });


    /*
     * =====================================================
     * MARK ANSWER AS SEEN
     * =====================================================
     */

    async function markAnswerAsSeen(card) {

        const requestId =
            card.dataset.id;


        /*
         * Stop if the card does not have a valid ID.
         */

        if (!requestId) {
            return;
        }


        /*
         * Laravel's CSRF token is required for this POST request.
         */

        const csrfToken =
            document.querySelector(
                'meta[name="csrf-token"]'
            )?.getAttribute("content");


        if (!csrfToken) {

            console.error(
                "KNOWURLOCAL: CSRF token not found."
            );

            return;
        }


        try {

            const response =
                await fetch(
                    `/my-inquiries/${encodeURIComponent(requestId)}/seen`,
                    {
                        method: "POST",

                        headers: {
                            "X-CSRF-TOKEN": csrfToken,

                            "Accept": "application/json",

                            "Content-Type":
                                "application/json"
                        },

                        credentials: "same-origin"
                    }
                );


            if (!response.ok) {

                throw new Error(
                    `Request failed with status ${response.status}`
                );

            }


            const data =
                await response.json();


            if (!data.success) {

                console.error(
                    "KNOWURLOCAL: Answer could not be marked as seen."
                );

            }

        } catch (error) {

            /*
             * The inquiry remains visually open even if the
             * notification request fails.
             */

            console.error(
                "KNOWURLOCAL: Failed to mark answer as seen.",
                error
            );

        }

    }


    /*
     * =====================================================
     * IMAGE LIGHTBOX ELEMENTS
     * =====================================================
     */

    const imagePreviewTriggers =
        document.querySelectorAll("[data-image-preview]");

    const imageLightbox =
        document.getElementById("image-lightbox");

    const imageLightboxImage =
        document.getElementById("image-lightbox-image");

    const imageLightboxClose =
        document.getElementById("image-lightbox-close");


    /*
     * Stores the element that opened the lightbox.
     *
     * This allows keyboard users to return to the same
     * image button after closing the preview.
     */

    let previouslyFocusedElement = null;


    /*
     * Stores the body's original overflow value.
     *
     * This prevents the page from remaining locked if
     * another script has already changed body overflow.
     */

    let originalBodyOverflow = "";


    /*
     * =====================================================
     * OPEN IMAGE LIGHTBOX
     * =====================================================
     */

    function openImageLightbox(imageSource, imageAlt) {

        if (
            !imageLightbox ||
            !imageLightboxImage ||
            !imageLightboxClose ||
            !imageSource
        ) {
            return;
        }


        previouslyFocusedElement =
            document.activeElement;


        originalBodyOverflow =
            document.body.style.overflow;


        imageLightboxImage.src =
            imageSource;

        imageLightboxImage.alt =
            imageAlt || "Attached image";


        imageLightbox.classList.add("is-open");

        imageLightbox.setAttribute(
            "aria-hidden",
            "false"
        );


        /*
         * Prevents the page behind the lightbox from scrolling.
         */

        document.body.style.overflow =
            "hidden";


        /*
         * Moves keyboard focus to the close button.
         */

        imageLightboxClose.focus();

    }


    /*
     * =====================================================
     * CLOSE IMAGE LIGHTBOX
     * =====================================================
     */

    function closeImageLightbox() {

        if (
            !imageLightbox ||
            !imageLightboxImage
        ) {
            return;
        }


        imageLightbox.classList.remove("is-open");

        imageLightbox.setAttribute(
            "aria-hidden",
            "true"
        );


        /*
         * Clear the image source after closing.
         */

        imageLightboxImage.src =
            "";

        imageLightboxImage.alt =
            "";


        /*
         * Restore the body's previous scrolling behavior.
         */

        document.body.style.overflow =
            originalBodyOverflow;


        /*
         * Return focus to the image button that opened
         * the lightbox.
         */

        if (
            previouslyFocusedElement &&
            typeof previouslyFocusedElement.focus === "function"
        ) {

            previouslyFocusedElement.focus();

        }


        previouslyFocusedElement =
            null;

    }


    /*
     * =====================================================
     * IMAGE PREVIEW BUTTONS
     * =====================================================
     */

    imagePreviewTriggers.forEach(trigger => {

        trigger.addEventListener("click", () => {

            const imageSource =
                trigger.dataset.imageSrc;


            const image =
                trigger.querySelector("img");


            const imageAlt =
                image?.getAttribute("alt") ||
                "Image attached by the administrator";


            if (!imageSource) {

                console.error(
                    "KNOWURLOCAL: Image source is missing."
                );

                return;

            }


            openImageLightbox(
                imageSource,
                imageAlt
            );

        });

    });


    /*
     * =====================================================
     * CLOSE BUTTON
     * =====================================================
     */

    if (imageLightboxClose) {

        imageLightboxClose.addEventListener(
            "click",
            closeImageLightbox
        );

    }


    /*
     * =====================================================
     * CLOSE WHEN CLICKING THE OVERLAY
     * =====================================================
     */

    if (imageLightbox) {

        imageLightbox.addEventListener(
            "click",
            event => {

                /*
                 * Only close when the dark overlay itself
                 * is clicked, not the image or close button.
                 */

                if (
                    event.target === imageLightbox
                ) {

                    closeImageLightbox();

                }

            }
        );

    }


    /*
     * =====================================================
     * ESCAPE KEY SUPPORT
     * =====================================================
     */

    document.addEventListener(
        "keydown",
        event => {

            if (
                event.key === "Escape" &&
                imageLightbox &&
                imageLightbox.classList.contains("is-open")
            ) {

                closeImageLightbox();

            }

        }
    );


});