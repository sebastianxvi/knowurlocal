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
                `/my-inquiries/${requestId}/seen`,
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
         * notification request fails. This prevents a
         * temporary network problem from blocking the user
         * from reading their answer.
         */
        console.error(
            "KNOWURLOCAL: Failed to mark answer as seen.",
            error
        );

    }

}

});