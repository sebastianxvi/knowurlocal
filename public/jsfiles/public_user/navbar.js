/* =========================================================
   KNOWURLOCAL
   PUBLIC NAVBAR
   ========================================================= */


/* =========================================================
   ELEMENT REFERENCES
   ========================================================= */

/*
 * Find the mobile menu button and navigation drawer.
 *
 * getElementById() returns null when an element does not
 * exist, so every interaction below checks its dependencies
 * before using them.
 */
const menuToggle =
    document.getElementById("menuToggle");

const navDrawer =
    document.getElementById("navDrawer");


/*
 * Find all navigation links inside the drawer.
 *
 * querySelectorAll() safely returns an empty NodeList when
 * no matching elements exist.
 */
const navLinks =
    document.querySelectorAll(".nav-link");


/*
 * Find the account dropdown controls.
 */
const accountToggle =
    document.getElementById("accountToggle");

const accountDropdown =
    document.getElementById("accountDropdown");


/* =========================================================
   MENU DRAWER
   ========================================================= */


/*
 * Open or close the mobile navigation drawer.
 *
 * The visual state is controlled by the CSS class
 * "is-open".
 *
 * aria-expanded is updated at the same time so the
 * accessibility state always matches the visual state.
 */
if (menuToggle && navDrawer) {

    menuToggle.addEventListener("click", () => {

        const isOpen =
            navDrawer.classList.toggle("is-open");


        /*
         * Tell assistive technologies whether the
         * navigation drawer is currently expanded.
         */
        menuToggle.setAttribute(
            "aria-expanded",
            String(isOpen)
        );


        /*
         * Update the accessible name of the button.
         *
         * This makes the action clearer depending on
         * the current state.
         */
        menuToggle.setAttribute(
            "aria-label",
            isOpen
                ? "Close navigation menu"
                : "Open navigation menu"
        );

    });

}


/*
 * Close the navigation drawer when a navigation link
 * is selected.
 *
 * This is especially important on mobile because the
 * drawer should disappear after navigation is selected.
 */
navLinks.forEach((link) => {

    link.addEventListener(
        "click",
        closeMenu
    );

});


/* =========================================================
   ACCOUNT DROPDOWN
   ========================================================= */


/*
 * Initialize the account dropdown only when both
 * required elements exist.
 */
if (accountToggle && accountDropdown) {


    /*
     * Keep account dropdown state in one function.
     *
     * This prevents click and keyboard interactions
     * from implementing slightly different behavior.
     */
    const toggleAccount = () => {

        const isOpen =
            accountDropdown.classList.toggle("active");


        /*
         * Add the visual state used by CSS.
         *
         * This rotates the account chevron.
         */
        accountToggle.classList.toggle(
            "is-open",
            isOpen
        );


        /*
         * Keep accessibility state synchronized.
         */
        accountToggle.setAttribute(
            "aria-expanded",
            String(isOpen)
        );

    };


    /*
     * Mouse/touch interaction.
     */
    accountToggle.addEventListener(
        "click",
        (event) => {

            /*
             * Prevent the document-level click handler
             * from immediately closing the dropdown.
             */
            event.stopPropagation();

            toggleAccount();

        }
    );


    /*
     * Keyboard interaction.
     *
     * Enter and Space both activate the account control.
     */
    accountToggle.addEventListener(
        "keydown",
        (event) => {

            if (
                event.key !== "Enter" &&
                event.key !== " "
            ) {

                return;

            }


            /*
             * Prevent Space from scrolling the page.
             */
            event.preventDefault();

            toggleAccount();

        }
    );

}


/* =========================================================
   GLOBAL CLICK HANDLER
   ========================================================= */


/*
 * One document-level listener handles interactions
 * outside the navigation drawer and account dropdown.
 *
 * Keeping this centralized prevents multiple global
 * click listeners from being created.
 */
document.addEventListener(
    "click",
    (event) => {


        /* -------------------------------------------------
           NAVIGATION DRAWER
           ------------------------------------------------- */

        if (menuToggle && navDrawer) {

            /*
             * If the user clicked outside both the drawer
             * and its toggle button, close the drawer.
             */
            if (
                !navDrawer.contains(event.target) &&
                !menuToggle.contains(event.target)
            ) {

                closeMenu();

            }

        }


        /* -------------------------------------------------
           ACCOUNT DROPDOWN
           ------------------------------------------------- */

        if (
            accountToggle &&
            accountDropdown
        ) {

            /*
             * If the user clicked outside both account
             * elements, close the dropdown.
             */
            if (
                !accountToggle.contains(event.target) &&
                !accountDropdown.contains(event.target)
            ) {

                closeAccount();

            }

        }

    }
);


/* =========================================================
   KEYBOARD ESCAPE
   ========================================================= */


/*
 * Escape should close temporary navigation UI.
 *
 * This provides a natural keyboard way to dismiss
 * the drawer or account dropdown.
 */
document.addEventListener(
    "keydown",
    (event) => {

        if (event.key !== "Escape") {

            return;

        }


        closeMenu();

        closeAccount();

    }
);


/* =========================================================
   CLOSE MENU
   ========================================================= */


/*
 * Centralized navigation drawer close function.
 *
 * Every part of the navbar uses this function so the
 * drawer state cannot become inconsistent.
 */
function closeMenu() {

    /*
     * There is nothing to close if the drawer does
     * not exist.
     */
    if (!navDrawer) {

        return;

    }


    /*
     * Remove the drawer's visual open state.
     */
    navDrawer.classList.remove(
        "is-open"
    );


    /*
     * Synchronize the mobile menu button.
     */
    if (menuToggle) {

        menuToggle.setAttribute(
            "aria-expanded",
            "false"
        );


        menuToggle.setAttribute(
            "aria-label",
            "Open navigation menu"
        );

    }

}


/* =========================================================
   CLOSE ACCOUNT
   ========================================================= */


/*
 * Centralized account dropdown close function.
 *
 * The CSS state and accessibility state are both reset
 * here so every closing path behaves identically.
 */
function closeAccount() {

    /*
     * There is nothing to close if the dropdown
     * does not exist.
     */
    if (!accountDropdown) {

        return;

    }


    /*
     * Remove the dropdown's active state.
     */
    accountDropdown.classList.remove(
        "active"
    );


    /*
     * Reset the chevron state.
     */
    if (accountToggle) {

        accountToggle.classList.remove(
            "is-open"
        );


        /*
         * Tell assistive technologies that the
         * dropdown is now collapsed.
         */
        accountToggle.setAttribute(
            "aria-expanded",
            "false"
        );

    }

}