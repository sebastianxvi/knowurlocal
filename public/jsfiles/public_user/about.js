/* =========================================================
   KNOWURLOCAL
   ABOUT PAGE
   ========================================================= */


/* =========================================================
   DOM REFERENCES
   ========================================================= */

/*
 * Store references to the elements used by this page.
 *
 * querySelector() returns null when an element does not exist,
 * so every optional interaction below checks its reference
 * before using it.
 */

const aboutPage =
    document.querySelector(".about-page");


const revealElements =
    document.querySelectorAll(".about-reveal");


const preview =
    document.getElementById("aboutPreview");


const previewSearch =
    document.getElementById("aboutPreviewSearch");


const previewResults =
    document.getElementById("aboutPreviewResults");


const previewEmpty =
    document.getElementById("aboutPreviewEmpty");


const previewAgency =
    document.getElementById("aboutPreviewAgency");


const previewMap =
    document.getElementById("aboutPreviewMap");


const previewRows =
    document.querySelectorAll(
        "[data-preview-action]"
    );


const previewBackButtons =
    document.querySelectorAll(
        "[data-preview-back]"
    );


/* =========================================================
   REVEAL ANIMATIONS
   ========================================================= */

/*
 * IntersectionObserver lets the browser tell us when an
 * element enters the viewport.
 *
 * This is more efficient than listening to scroll events and
 * repeatedly calculating getBoundingClientRect().
 *
 * That distinction matters on mobile because scroll handlers
 * can become unnecessarily expensive when a page contains
 * many animated elements.
 */

const initializeRevealAnimations = () => {

    /*
     * If the browser does not support IntersectionObserver,
     * reveal everything immediately.
     *
     * The content should never depend on animation support
     * to remain accessible.
     */
    if (
        !("IntersectionObserver" in window)
    ) {

        revealElements.forEach((element) => {

            element.classList.add(
                "is-visible"
            );

        });

        return;

    }


    /*
     * Respect the operating system's reduced-motion
     * preference.
     *
     * The CSS already disables transitions, but revealing
     * everything immediately also avoids unnecessary observer
     * work for users who requested reduced motion.
     */
    const prefersReducedMotion =
        window.matchMedia(
            "(prefers-reduced-motion: reduce)"
        ).matches;


    if (prefersReducedMotion) {

        revealElements.forEach((element) => {

            element.classList.add(
                "is-visible"
            );

        });

        return;

    }


    /*
     * Create one observer for the entire About page.
     *
     * A single observer is preferable to creating one observer
     * for every individual element.
     */
    const observer =
    new IntersectionObserver(
        (entries) => {

            entries.forEach((entry) => {

                /*
                 * When the element enters the viewport,
                 * activate its reveal animation.
                 */
                if (entry.isIntersecting) {

                    entry.target.classList.add(
                        "is-visible"
                    );

                    return;
                }


                /*
                 * When the element leaves the viewport,
                 * remove the state class.
                 *
                 * This resets the element so the animation
                 * can play again when the user scrolls back.
                 */
                entry.target.classList.remove(
                    "is-visible"
                );

            });

        },
        {
            /*
             * Start the animation slightly before the
             * element reaches the center of the viewport.
             */
            rootMargin:
                "0px 0px -8% 0px",

            /*
             * Trigger when at least 8% of the element
             * is visible.
             */
            threshold:
                0.08
        }
    );


    /*
     * Register every reveal element with the same observer.
     */
    revealElements.forEach((element) => {

        observer.observe(element);

    });

};


/* =========================================================
   HERO PREVIEW STATE
   ========================================================= */

/*
 * The preview has three main visual states:
 *
 *     results
 *     agency
 *     map
 *
 * Keeping those states in JavaScript makes the interaction
 * predictable and prevents multiple unrelated CSS classes
 * from controlling the same component.
 */

const showPreviewResults = () => {

    if (previewResults) {

        previewResults.style.display =
            "flex";

    }


    if (previewEmpty) {

        previewEmpty.style.display =
            "none";

    }


    if (previewAgency) {

        previewAgency.classList.remove(
            "is-active"
        );

    }


    if (previewMap) {

        previewMap.classList.remove(
            "is-active"
        );

    }

};


const showPreviewAgency = () => {

    if (previewResults) {

        previewResults.style.display =
            "none";

    }


    if (previewEmpty) {

        previewEmpty.style.display =
            "none";

    }


    if (previewAgency) {

        previewAgency.classList.add(
            "is-active"
        );

    }


    if (previewMap) {

        previewMap.classList.remove(
            "is-active"
        );

    }

};


const showPreviewMap = () => {

    if (previewResults) {

        previewResults.style.display =
            "none";

    }


    if (previewEmpty) {

        previewEmpty.style.display =
            "none";

    }


    if (previewAgency) {

        previewAgency.classList.remove(
            "is-active"
        );

    }


    if (previewMap) {

        previewMap.classList.add(
            "is-active"
        );

    }

};


/* =========================================================
   HERO PREVIEW SEARCH
   ========================================================= */

/*
 * The hero search is only a visual demonstration.
 *
 * It does NOT perform a real database search.
 *
 * That is intentional:
 *
 *     About page preview
 *         ≠
 *     Actual agency search
 *
 * The real search functionality remains on the Map page.
 */

if (previewSearch) {

    previewSearch.addEventListener(
        "input",
        () => {

            /*
             * Normalize the value before comparing it.
             *
             * trim() removes unnecessary whitespace.
             *
             * toLowerCase() makes the comparison
             * case-insensitive.
             */
            const query =
                previewSearch.value
                    .trim()
                    .toLowerCase();


            /*
             * Empty search returns the preview to
             * its normal state.
             */
            if (!query) {

                showPreviewResults();

                return;

            }


            /*
             * These are intentionally simple demonstration
             * terms because this is a product preview rather
             * than the real agency search.
             */
            const supportedTerms = [
                "agency",
                "agencies",
                "office",
                "location",
                "map",
                "services",
                "information",
                "local"
            ];


            const hasMatch =
                supportedTerms.some(
                    (term) =>
                        term.includes(query) ||
                        query.includes(term)
                );


            /*
             * If the user enters something unrelated,
             * show the empty state.
             */
            if (!hasMatch) {

                if (previewResults) {

                    previewResults.style.display =
                        "none";

                }


                if (previewAgency) {

                    previewAgency.classList.remove(
                        "is-active"
                    );

                }


                if (previewMap) {

                    previewMap.classList.remove(
                        "is-active"
                    );

                }


                if (previewEmpty) {

                    previewEmpty.style.display =
                        "flex";

                }

                return;

            }


            /*
             * A recognized term returns to the normal
             * demonstration results.
             */
            showPreviewResults();

        }
    );

}


/* =========================================================
   HERO PREVIEW ACTIONS
   ========================================================= */

/*
 * Each preview row declares its intended action through
 * data-preview-action.
 *
 * This keeps the HTML semantic and prevents us from relying
 * on element positions such as :nth-child().
 */

previewRows.forEach((row) => {

    row.addEventListener(
        "click",
        () => {

            const action =
                row.dataset.previewAction;


            if (action === "agency") {

                showPreviewAgency();

                return;

            }


            if (action === "location") {

                showPreviewMap();

            }

        }
    );

});


/* =========================================================
   HERO PREVIEW BACK BUTTONS
   ========================================================= */

/*
 * Both the Agency and Map states use the same back behavior.
 *
 * Using a shared data attribute means we don't need separate
 * event handlers for each preview state.
 */

previewBackButtons.forEach((button) => {

    button.addEventListener(
        "click",
        () => {

            /*
             * Clear the demonstration search when returning
             * to the main preview.
             */
            if (previewSearch) {

                previewSearch.value = "";

            }


            showPreviewResults();

        }
    );

});


/* =========================================================
   KEYBOARD SAFETY FOR PREVIEW
   ========================================================= */

/*
 * The preview is already made from real buttons, which means
 * Enter and Space automatically work with keyboard navigation.
 *
 * This section only handles Escape so a user can quickly
 * return from a preview detail state.
 */

if (preview) {

    preview.addEventListener(
        "keydown",
        (event) => {

            if (event.key !== "Escape") {

                return;

            }


            /*
             * Only act when the preview is currently showing
             * one of its detail states.
             */
            const agencyIsOpen =
                previewAgency?.classList.contains(
                    "is-active"
                );


            const mapIsOpen =
                previewMap?.classList.contains(
                    "is-active"
                );


            if (
                !agencyIsOpen &&
                !mapIsOpen
            ) {

                return;

            }


            if (previewSearch) {

                previewSearch.value = "";

            }


            showPreviewResults();

        }
    );

}


/* =========================================================
   INITIALIZATION
   ========================================================= */

/*
 * Keep initialization in one place.
 *
 * This makes the script easier to maintain if more About-page
 * interactions are added later.
 */

const initializeAboutPage = () => {

    /*
     * Stop when this JavaScript is accidentally loaded on
     * another page.
     */
    if (!aboutPage) {

        return;

    }


    /*
     * Start viewport-based reveal animations.
     */
    initializeRevealAnimations();


    /*
     * Make sure the preview starts in its default state.
     */
    showPreviewResults();

};


/*
 * The script is loaded with defer, so the DOM is already
 * available when this runs.
 */
initializeAboutPage();