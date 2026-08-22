/*
|--------------------------------------------------------------------------
| KNOWURLOCAL
| ABOUT PAGE JAVASCRIPT
|--------------------------------------------------------------------------
|
| This file contains ONLY behavior belonging to the About page.
|
| Navbar behavior stays inside navbar.js.
|
| This separation is intentional:
|
| navbar.js
|     -> shared navigation behavior
|
| about.js
|     -> About-page behavior
|
|--------------------------------------------------------------------------
*/


/* =========================================================
   PAGE INITIALIZATION
   ========================================================= */


/*
 * Add this class immediately.
 *
 * The CSS uses it to activate the starting state of the
 * scroll-reveal animation.
 *
 * If JavaScript fails completely, the class is never added,
 * meaning the page content remains visible.
 */
document.documentElement.classList.add(
    "js-enabled"
);


/* =========================================================
   REDUCED MOTION
   ========================================================= */


/*
 * Respect the user's operating-system accessibility setting.
 *
 * Users who prefer reduced motion should not be forced to
 * watch animated transitions.
 */
const prefersReducedMotion =
    window.matchMedia(
        "(prefers-reduced-motion: reduce)"
    ).matches;


/* =========================================================
   SCROLL REVEAL
   ========================================================= */


/*
 * Select all About-page elements that should animate
 * into view.
 */
const revealElements =
    document.querySelectorAll(
        ".about-reveal"
    );


/*
 * If the browser supports reduced motion, immediately show
 * all content without animation.
 */
if (prefersReducedMotion) {

    revealElements.forEach(
        (element) => {

            element.classList.add(
                "is-visible"
            );

        }
    );


} else {


    /*
     * IntersectionObserver is preferred over listening to
     * scroll events because the browser can optimize the
     * visibility checks more efficiently.
     */
    const revealObserver =
        new IntersectionObserver(
            (
                entries
            ) => {

                entries.forEach(
                    (entry) => {


                        /*
                         * Ignore elements that have not
                         * entered the viewport.
                         */
                        if (
                            !entry.isIntersecting
                        ) {

                            return;

                        }


                        /*
                         * Trigger the CSS transition.
                         */
                        entry.target.classList.add(
                            "is-visible"
                        );


                        /*
                         * Once revealed, stop observing
                         * that element.
                         */
                        revealObserver.unobserve(
                            entry.target
                        );

                    }
                );

            },
            {
                threshold:
                    0.12
            }
        );


    /*
     * Register each element with the observer.
     */
    revealElements.forEach(
        (element) => {

            revealObserver.observe(
                element
            );

        }
    );

}


/* =========================================================
   SMOOTH INTERNAL NAVIGATION
   ========================================================= */


/*
 * Find links that point to an ID on the same page.
 *
 * Example:
 *
 *     href="#what-is"
 *
 * These links will scroll smoothly instead of jumping.
 */
const internalLinks =
    document.querySelectorAll(
        'a[href^="#"]'
    );


internalLinks.forEach(
    (link) => {

        link.addEventListener(
            "click",
            (event) => {


                /*
                 * Read the target selector from the link.
                 */
                const targetSelector =
                    link.getAttribute(
                        "href"
                    );


                /*
                 * Ignore empty anchors.
                 */
                if (
                    !targetSelector ||
                    targetSelector === "#"
                ) {

                    return;

                }


                /*
                 * Find the destination section.
                 */
                const target =
                    document.querySelector(
                        targetSelector
                    );


                /*
                 * Only intercept the click when the
                 * destination actually exists.
                 */
                if (target) {

                    event.preventDefault();


                    /*
                     * Scroll to the section.
                     *
                     * Reduced-motion users receive an
                     * instant scroll instead.
                     */
                    target.scrollIntoView(
                        {
                            behavior:
                                prefersReducedMotion
                                    ? "auto"
                                    : "smooth",

                            block:
                                "start"
                        }
                    );

                }

            }
        );

    }
);


/* =========================================================
   INTERACTIVE HERO PREVIEW
   ========================================================= */


/*
 * The hero preview is a demonstration of the KNOWURLOCAL
 * experience.
 *
 * It is NOT connected to the real agency database.
 *
 * This is important because marketing/demo UI should not
 * accidentally present fake information as actual records.
 */
const aboutPreview =
    document.getElementById(
        "aboutPreview"
    );


/*
 * Stop if the interactive preview does not exist.
 *
 * This protects the rest of the page from JavaScript errors.
 */
if (aboutPreview) {


    /* =====================================================
       ELEMENT REFERENCES
       ===================================================== */

    const previewSearch =
        document.getElementById(
            "aboutPreviewSearch"
        );


    const previewResults =
        document.getElementById(
            "aboutPreviewResults"
        );


    const previewEmpty =
        document.getElementById(
            "aboutPreviewEmpty"
        );


    const previewAgency =
        document.getElementById(
            "aboutPreviewAgency"
        );


    const previewMap =
        document.getElementById(
            "aboutPreviewMap"
        );


    const previewRows =
        aboutPreview.querySelectorAll(
            ".about-preview-row"
        );


    const previewBackButtons =
        aboutPreview.querySelectorAll(
            "[data-preview-back]"
        );


    /* =====================================================
       RESET SEARCH RESULTS
       ===================================================== */

    const resetPreviewResults =
        () => {

            previewRows.forEach(
                (row) => {

                    row.hidden =
                        false;

                }
            );

        };


    /* =====================================================
       SHOW DEFAULT STATE
       ===================================================== */

    const showSearchState =
        () => {

            /*
             * Restore all results.
             */
            resetPreviewResults();


            /*
             * Show the result container.
             */
            previewResults?.classList.remove(
                "is-hidden"
            );


            /*
             * Hide the empty state.
             */
            previewEmpty?.classList.remove(
                "is-visible"
            );


            /*
             * Hide agency details.
             */
            previewAgency?.classList.remove(
                "is-active"
            );


            /*
             * Hide the map preview.
             */
            previewMap?.classList.remove(
                "is-active"
            );

        };


    /* =====================================================
       SHOW AGENCY STATE
       ===================================================== */

    const showAgencyState =
        () => {

            previewResults?.classList.add(
                "is-hidden"
            );


            previewEmpty?.classList.remove(
                "is-visible"
            );


            previewAgency?.classList.add(
                "is-active"
            );


            previewMap?.classList.remove(
                "is-active"
            );

        };


    /* =====================================================
       SHOW MAP STATE
       ===================================================== */

    const showMapState =
        () => {

            previewResults?.classList.add(
                "is-hidden"
            );


            previewEmpty?.classList.remove(
                "is-visible"
            );


            previewAgency?.classList.remove(
                "is-active"
            );


            previewMap?.classList.add(
                "is-active"
            );

        };


    /* =====================================================
       HERO SEARCH
       ===================================================== */

    if (previewSearch) {

        previewSearch.addEventListener(
            "input",
            () => {


                /*
                 * Normalize the input.
                 *
                 * trim()
                 *     removes unnecessary spaces.
                 *
                 * toLowerCase()
                 *     makes matching case-insensitive.
                 */
                const searchTerm =
                    previewSearch.value
                        .trim()
                        .toLowerCase();


                /*
                 * Return to the normal search state
                 * whenever the user starts typing.
                 */
                previewResults?.classList.remove(
                    "is-hidden"
                );


                previewEmpty?.classList.remove(
                    "is-visible"
                );


                previewAgency?.classList.remove(
                    "is-active"
                );


                previewMap?.classList.remove(
                    "is-active"
                );


                let visibleRows =
                    0;


                /*
                 * Compare the search term with each
                 * demonstration result.
                 */
                previewRows.forEach(
                    (row) => {

                        const rowText =
                            row.textContent
                                .toLowerCase();


                        const matches =
                            rowText.includes(
                                searchTerm
                            );


                        /*
                         * The hidden property is native HTML
                         * behavior and does not require a
                         * custom CSS class.
                         */
                        row.hidden =
                            !matches;


                        if (matches) {

                            visibleRows++;

                        }

                    }
                );


                /*
                 * If the user typed something and nothing
                 * matches, show the empty state.
                 */
                if (
                    searchTerm &&
                    visibleRows === 0
                ) {

                    previewResults?.classList.add(
                        "is-hidden"
                    );


                    previewEmpty?.classList.add(
                        "is-visible"
                    );

                }

            }
        );

    }


    /* =====================================================
       RESULT BUTTONS
       ===================================================== */

    previewRows.forEach(
        (row) => {

            row.addEventListener(
                "click",
                () => {


                    /*
                     * Read the action from:
                     *
                     * data-preview-action
                     */
                    const action =
                        row.dataset.previewAction;


                    /*
                     * Agency information.
                     */
                    if (
                        action === "agency"
                    ) {

                        showAgencyState();

                    }


                    /*
                     * Office location.
                     */
                    if (
                        action === "location"
                    ) {

                        showMapState();

                    }

                }
            );

        }
    );


    /* =====================================================
       BACK BUTTONS
       ===================================================== */

    previewBackButtons.forEach(
        (button) => {

            button.addEventListener(
                "click",
                () => {

                    showSearchState();

                }
            );

        }
    );


    /* =====================================================
       SEARCH RESET
       ===================================================== */

    if (previewSearch) {

        previewSearch.addEventListener(
            "focus",
            () => {

                /*
                 * Determine whether one of the detail
                 * states is currently open.
                 */
                const agencyIsOpen =
                    previewAgency?.classList.contains(
                        "is-active"
                    );


                const mapIsOpen =
                    previewMap?.classList.contains(
                        "is-active"
                    );


                /*
                 * If a detail state is open, reset the
                 * preview to its normal search state.
                 */
                if (
                    agencyIsOpen ||
                    mapIsOpen
                ) {

                    previewSearch.value =
                        "";


                    showSearchState();

                }

            }
        );

    }

}


/* =========================================================
   KEYBOARD ACCESSIBILITY FOR THE HERO PREVIEW
   ========================================================= */


/*
 * Allow Escape to return the preview to its default state.
 *
 * This gives keyboard users a quick way to leave the
 * agency/map preview.
 */
document.addEventListener(
    "keydown",
    (event) => {


        if (
            event.key !== "Escape"
        ) {

            return;

        }


        const agency =
            document.getElementById(
                "aboutPreviewAgency"
            );


        const map =
            document.getElementById(
                "aboutPreviewMap"
            );


        const search =
            document.getElementById(
                "aboutPreviewSearch"
            );


        const results =
            document.getElementById(
                "aboutPreviewResults"
            );


        const empty =
            document.getElementById(
                "aboutPreviewEmpty"
            );


        /*
         * Nothing to do if the preview elements aren't present.
         */
        if (
            !agency ||
            !map ||
            !search ||
            !results ||
            !empty
        ) {

            return;

        }


        /*
         * Only reset if a detail state is active.
         */
        if (
            agency.classList.contains(
                "is-active"
            ) ||
            map.classList.contains(
                "is-active"
            )
        ) {

            agency.classList.remove(
                "is-active"
            );


            map.classList.remove(
                "is-active"
            );


            empty.classList.remove(
                "is-visible"
            );


            results.classList.remove(
                "is-hidden"
            );


            search.value =
                "";


            /*
             * Restore the demonstration rows.
             */
            document
                .querySelectorAll(
                    ".about-preview-row"
                )
                .forEach(
                    (row) => {

                        row.hidden =
                            false;

                    }
                );

        }

    }
);