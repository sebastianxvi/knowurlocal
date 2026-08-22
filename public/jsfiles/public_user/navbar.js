/* =========================================================
   KNOWURLOCAL
   PUBLIC NAVBAR
   ========================================================= */


/*
 * Find the main menu button and navigation drawer.
 *
 * getElementById() returns null when the element does not
 * exist, so the code below checks for the elements before
 * attaching event listeners.
 */
const menuToggle = document.getElementById("menuToggle");
const navDrawer = document.getElementById("navDrawer");


/*
 * Find all navigation links inside the drawer.
 *
 * querySelectorAll() safely returns an empty NodeList when
 * there are no matching elements.
 */
const navLinks = document.querySelectorAll(".nav-link");


/*
 * Find the account controls.
 */
const accountToggle = document.getElementById("accountToggle");
const accountDropdown = document.getElementById("accountDropdown");


/* =========================================================
   MENU DRAWER
   ========================================================= */


/*
 * Only initialize the menu toggle when both required
 * elements exist.
 *
 * This prevents:
 *
 * "Cannot read properties of null"
 *
 * errors on pages that do not contain the drawer.
 */
if (menuToggle && navDrawer) {

    menuToggle.addEventListener("click", () => {

        navDrawer.classList.toggle("is-open");

    });

}


/*
 * Close the navigation drawer when a navigation link
 * is selected.
 */
navLinks.forEach((link) => {

    link.addEventListener("click", closeMenu);

});


/* =========================================================
   ACCOUNT DROPDOWN
   ========================================================= */


/*
 * The account dropdown is optional.
 *
 * Therefore, only attach the click listener when both
 * account elements actually exist.
 */
if (accountToggle && accountDropdown) {

    accountToggle.addEventListener("click", (event) => {

        /*
         * Prevent the document-level click handler from
         * immediately closing the dropdown again.
         */
        event.stopPropagation();


        /*
         * Toggle the dropdown's active state.
         */
        accountDropdown.classList.toggle("active");

    });

}


/* =========================================================
   GLOBAL CLICK HANDLER
   ========================================================= */


/*
 * One document-level listener handles clicks outside both
 * the navigation drawer and account dropdown.
 *
 * This avoids creating multiple document click listeners.
 */
document.addEventListener("click", (event) => {


    /*
     * Handle the navigation drawer only when it exists.
     */
    if (menuToggle && navDrawer) {

        /*
         * If the user clicked outside both the drawer and
         * its toggle button, close the drawer.
         */
        if (
            !navDrawer.contains(event.target) &&
            !menuToggle.contains(event.target)
        ) {

            closeMenu();

        }

    }


    /*
     * Handle the account dropdown only when both account
     * elements exist.
     */
    if (accountToggle && accountDropdown) {

        /*
         * If the user clicked outside the account controls,
         * remove the active state from the dropdown.
         */
        if (
            !accountToggle.contains(event.target) &&
            !accountDropdown.contains(event.target)
        ) {

            accountDropdown.classList.remove("active");

        }

    }

});


/* =========================================================
   CLOSE MENU
   ========================================================= */


/*
 * Centralized function for closing the navigation drawer.
 *
 * Keeping this in one function means every part of the
 * navbar uses the same behavior.
 */
function closeMenu() {

    /*
     * There is nothing to close if the drawer does not exist.
     */
    if (!navDrawer) {

        return;

    }


    /*
     * Remove the drawer's open state.
     */
    navDrawer.classList.remove("is-open");


    /*
     * Update the menu icon when the toggle exists.
     */
    if (menuToggle) {

        const icon = menuToggle.querySelector("i");


        /*
         * Only modify the icon when an icon element exists.
         */
        if (icon) {

            /*
             * Your navbar uses Phosphor icons, so there is no
             * need to manipulate Font Awesome classes here.
             *
             * The drawer's visual state is controlled by CSS,
             * while the actual icon remains the Phosphor menu icon.
             */

        }

    }

}