document.addEventListener("DOMContentLoaded", () => {

    // =========================================================
    // APP CONFIGURATION
    // =========================================================

    /*
     * Base URL used when the user wants to navigate
     * to a selected agency.
     *
     * The actual URL is supplied by Blade through
     * window.APP_CONFIG.
     */
    const navigateBaseUrl =
        window.APP_CONFIG.navigateBaseUrl;


    /*
     * Container where search results will appear.
     */
    const resultsContainer =
        document.getElementById('searchResults');


    // =========================================================
    // MAP INITIALIZATION
    // =========================================================

    /*
     * Center coordinates of San Jose, Occidental Mindoro.
     */
    const sanJose = [
        12.353984,
        121.067504
    ];


    /*
     * Create the Leaflet map.
     *
     * Zoom controls are disabled because the map uses
     * a custom navigation interface.
     */
    const map =
        L.map('map', {
            zoomControl: false
        })
        .setView(
            sanJose,
            14
        );


    /*
     * Load OpenStreetMap tiles.
     */
    L.tileLayer(
        "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
        {
            maxZoom: 18,
            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    // =========================================================
    // INTRO MAP ANIMATION
    // =========================================================

    /*
     * Once Leaflet has finished initializing,
     * slightly zoom into San Jose.
     *
     * The delay prevents the animation from competing
     * with the initial map rendering.
     */
    map.whenReady(() => {

        setTimeout(() => {

            map.flyTo(
                sanJose,
                15,
                {
                    duration: 1.8,
                    easeLinearity: 0.15
                }
            );

        }, 400);

    });


    // =========================================================
    // MARKER STATE
    // =========================================================

    /*
     * Stores all agency markers.
     *
     * The agency name is used as the lookup key.
     */
    const markers = {};


    /*
     * Stores the ID of the currently selected agency.
     *
     * This is used by category filtering.
     */
    let selectedAgencyId = null;


    /*
     * Stores the currently selected Leaflet marker.
     *
     * Only one agency should use the selected red marker
     * at a time.
     */
    let selectedMarker = null;


    // =========================================================
    // SELECTED AGENCY MARKER
    // =========================================================

    /*
 * Traditional red map pin used to identify
 * the currently selected agency.
 *
 * We use the existing marker image supplied
 * through Blade's APP_CONFIG instead of creating
 * another custom marker with HTML/CSS.
 */
/*
 * Use Leaflet's built-in default marker.
 *
 * Passing no custom icon makes Leaflet use its
 * standard marker design.
 */
const selectedAgencyIcon =
    new L.Icon.Default();


    // =========================================================
    // AGENCY DETAILS PANEL
    // =========================================================

    /*
     * Get references to the custom agency details panel.
     *
     * This panel replaces the old Leaflet popup.
     */
    const agencyDetails =
        document.getElementById(
            'agencyDetails'
        );


    const agencyDetailsClose =
        document.getElementById(
            'agencyDetailsClose'
        );


    const agencyNavigate =
        document.getElementById(
            'agencyNavigate'
        );

        /*
        * Timer used to collapse the Navigate button
        * after its temporary explanation has been shown.
        */
        let navigateHintTimer = null;


    const agencyDetailsImage =
        document.getElementById(
            'agencyDetailsImage'
        );


    const agencyDetailsName =
        document.getElementById(
            'agencyDetailsName'
        );


    const agencyDetailsAbbreviation =
        document.getElementById(
            'agencyDetailsAbbreviation'
        );

    const agencyDetailsCategory =
        document.getElementById(
            'agencyDetailsCategory'
        );


    const agencyDetailsLocation =
        document.getElementById(
            'agencyDetailsLocation'
        );


    const agencyDetailsType =
        document.getElementById(
            'agencyDetailsType'
        );


    const agencyDetailsDescription =
        document.getElementById(
            'agencyDetailsDescription'
        );


    const agencyDetailsServices =
        document.getElementById(
            'agencyDetailsServices'
        );


    const agencyDetailsHours =
        document.getElementById(
            'agencyDetailsHours'
        );


    const agencyDetailsContacts =
        document.getElementById(
            'agencyDetailsContacts'
        );


    /*
     * Sections that can be hidden when an agency
     * does not have the corresponding information.
     */
    const agencyAboutSection =
        document.getElementById(
            'agencyAboutSection'
        );


    const agencyServicesSection =
        document.getElementById(
            'agencyServicesSection'
        );


    const agencyHoursSection =
        document.getElementById(
            'agencyHoursSection'
        );


    const agencyContactSection =
        document.getElementById(
            'agencyContactSection'
        );


    // =========================================================
    // AGENCY DETAILS HELPERS
    // =========================================================

    /*
     * Show a section when data exists.
     *
     * Hide the section when the agency does not
     * have that information.
     */
    function setAgencySection(
        section,
        element,
        value
    ) {

        /*
         * Stop if either required DOM element
         * does not exist.
         */
        if (!section || !element) {
            return;
        }


        /*
         * Convert the supplied value into clean text.
         */
        const text =
            value
                ? String(value).trim()
                : '';


        /*
         * Display the section when information exists.
         */
        if (text) {

            element.textContent =
                text;

            section.style.display =
                '';

        }

        /*
         * Otherwise hide the section.
         */
        else {

            element.textContent =
                '';

            section.style.display =
                'none';

        }

    }


    /*
 * =========================================================
 * CREATE CONTACT ITEM
 * =========================================================
 *
 * Builds one contact row using DOM APIs instead of
 * innerHTML. This prevents contact values from being
 * interpreted as HTML.
 */
function createContactItem(
    iconClass,
    label,
    value,
    href = null,
    isPrimary = false
) {

    /*
     * A contact without a value should never be rendered.
     */
    if (!value) {
        return null;
    }


    /*
     * Create the outer contact row.
     */
    const row =
        document.createElement('div');

    row.className =
        'agency-contact-item';


    /*
     * Create the icon.
     */
    const icon =
        document.createElement('i');

    icon.className =
        `ph-light ${iconClass}`;

    icon.setAttribute(
        'aria-hidden',
        'true'
    );


    /*
     * Create the content wrapper.
     *
     * Keeping the label and value separate makes the
     * contact information easier to scan.
     */
    const content =
        document.createElement('div');

    content.className =
        'agency-contact-content';


    /*
     * Create the contact label.
     */
    const labelElement =
        document.createElement('span');

    labelElement.className =
        'agency-contact-label';

    labelElement.textContent =
        label;


    /*
     * Create the actual contact value.
     */
    const valueElement =
        document.createElement(
            href
                ? 'a'
                : 'span'
        );

    valueElement.className =
        'agency-contact-value';

    valueElement.textContent =
        value;


    /*
     * Only configure link behavior when a safe
     * destination has been explicitly generated.
     */
    if (href) {

        valueElement.href =
            href;

        /*
         * External websites open in a new tab.
         *
         * tel: and mailto: links are handled by the
         * browser/device and do not need target="_blank".
         */
        if (
            href.startsWith('https://') ||
            href.startsWith('http://')
        ) {

            valueElement.target =
                '_blank';

            /*
             * Prevent the opened page from receiving
             * a reference to KNOWURLOCAL.
             */
            valueElement.rel =
                'noopener noreferrer';

        }

    }


    /*
     * Add the label and value to the content wrapper.
     */
    content.appendChild(
        labelElement
    );

    content.appendChild(
        valueElement
    );


    /*
     * Add the icon and content to the row.
     */
    row.appendChild(
        icon
    );

    row.appendChild(
        content
    );


    /*
     * Mark the primary contact without relying
     * exclusively on color.
     */
    if (isPrimary) {

        row.classList.add(
            'is-primary'
        );


        const primaryBadge =
            document.createElement('span');

        primaryBadge.className =
            'agency-contact-primary';

        primaryBadge.textContent =
            'Primary';

        content.appendChild(
            primaryBadge
        );

    }


    return row;
}

/*
 * =========================================================
 * CONTACT TYPE CONFIGURATION
 * =========================================================
 *
 * Maps database contact-type slugs to presentation rules.
 *
 * The database remains the source of truth for which
 * contact types exist. This object only controls how each
 * known type behaves in the public interface.
 */
const CONTACT_TYPE_CONFIG = {

    hotline: {
        icon: 'ph-phone',
        label: 'Hotline',
        buildHref: value =>
            `tel:${value}`
    },


    landline: {
        icon: 'ph-phone-call',
        label: 'Landline',
        buildHref: value =>
            `tel:${value}`
    },


    email: {
        icon: 'ph-envelope',
        label: 'Email',
        buildHref: value =>
            `mailto:${value}`
    },


    website: {
        icon: 'ph-globe',
        label: 'Website',
        buildHref: value =>
            normalizeExternalUrl(value)
    },


    facebook: {
        icon: 'ph-facebook-logo',
        label: 'Facebook',
        buildHref: value =>
            normalizeExternalUrl(value)
    }

};

/*
 * =========================================================
 * NORMALIZE EXTERNAL URL
 * =========================================================
 *
 * Ensures website/social-media values become valid
 * absolute URLs before they are assigned to href.
 */
function normalizeExternalUrl(
    value
) {

    /*
     * Remove accidental whitespace.
     */
    const cleanValue =
        String(value || '').trim();


    if (!cleanValue) {
        return null;
    }


    /*
     * Only allow HTTP(S) destinations.
     *
     * This prevents dangerous schemes such as:
     *
     * javascript:
     * data:
     * vbscript:
     */
    if (
        cleanValue.startsWith(
            'https://'
        ) ||
        cleanValue.startsWith(
            'http://'
        )
    ) {

        return cleanValue;
    }


    /*
     * If the administrator entered:
     *
     * www.example.com
     *
     * convert it into:
     *
     * https://www.example.com
     */
    if (
        cleanValue.startsWith(
            'www.'
        )
    ) {

        return `https://${cleanValue}`;
    }


    /*
     * For all other values, prepend HTTPS.
     *
     * This gives the public UI a predictable destination
     * without permitting arbitrary URL schemes.
     */
    return `https://${cleanValue}`;
}

    // =========================================================
    // SELECT AGENCY MARKER
    // =========================================================

    /*
     * Change the selected agency's marker into
     * the red KNOWURLOCAL selected marker.
     */
    function selectAgencyMarker(
        marker
    ) {

        /*
         * Restore the previously selected marker.
         */
        if (
            selectedMarker &&
            selectedMarker !== marker
        ) {

            selectedMarker.setIcon(
                selectedMarker.normalIcon
            );


            selectedMarker.setZIndexOffset(
                0
            );

        }


        /*
         * Remember the new selected marker.
         */
        selectedMarker =
            marker;


        /*
         * Replace its normal category marker
         * with the red selected marker.
         */
        marker.setIcon(
            selectedAgencyIcon
        );


        /*
         * Make sure the selected marker appears
         * above other agency markers.
         */
        marker.setZIndexOffset(
            1000
        );


        /*
         * Restart the marker entrance animation.
         */
        const element =
            marker.getElement();


        if (element) {

            element.classList.remove(
                'selected-agency-marker'
            );


            /*
             * Force a browser reflow so the animation
             * can restart when another agency is selected.
             */
            void element.offsetWidth;


            element.classList.add(
                'selected-agency-marker'
            );

        }

    }

    /*
 * Temporarily expand the Navigate button so the
 * user can understand what the navigation icon does.
 */
function showNavigateHint() {

    /*
     * Stop any previous collapse timer.
     *
     * This prevents multiple timers from being active
     * when the user changes agencies quickly.
     */
    if (navigateHintTimer) {

        clearTimeout(
            navigateHintTimer
        );

    }


    /*
     * Stop if the Navigate button doesn't exist.
     */
    if (!agencyNavigate) {
        return;
    }


    /*
     * Expand the button.
     *
     * CSS handles the actual animation.
     */
    agencyNavigate.classList.add(
        'is-expanded'
    );


    /*
     * Keep the explanatory text visible for
     * approximately 2.5 seconds.
     */
    navigateHintTimer =
        setTimeout(
            () => {

                /*
                 * Remove the expanded state.
                 *
                 * CSS smoothly collapses the button
                 * back into icon-only mode.
                 */
                agencyNavigate.classList.remove(
                    'is-expanded'
                );

            },
            2500
        );

}


// =========================================================
// USER ACTIVITY LOGGING
// =========================================================

/*
 * Sends a meaningful user interaction to Laravel.
 *
 * The browser only sends the action and agency ID.
 * The server determines the authenticated user, role,
 * IP address, and device information.
 */
async function logUserActivity(
    action,
    agencyId,
    contactType = null
) {

    /*
     * Read Laravel's CSRF token from the page.
     *
     * This protects the POST request against
     * cross-site request forgery.
     */
    const csrfToken =
        document.querySelector(
            'meta[name="csrf-token"]'
        )?.getAttribute('content');


    /*
     * Do not attempt the request if the page
     * doesn't contain a CSRF token.
     */
    if (!csrfToken) {

        console.error(
            'KNOWURLOCAL: CSRF token not found.'
        );

        return;

    }


    try {

        /*
         * Send the activity asynchronously.
         *
         * We intentionally do not make the map wait
         * for the logging request to finish.
         */
        await fetch(
    '/user/activity/agency',
    {
        method: 'POST',

        /*
         * Allow the request to continue even if the
         * browser immediately navigates to another page.
         */
        keepalive: true,

        headers: {
            'Content-Type':
                'application/json',

            'Accept':
                'application/json',

            'X-CSRF-TOKEN':
                csrfToken
        },

        body: JSON.stringify({

    action: action,

    agency_id: agencyId,

    contact_type: contactType

})
    }
);

    }

    catch (error) {

        /*
         * Logging should never break the actual
         * agency-details functionality.
         */
        console.error(
            'KNOWURLOCAL: Failed to record user activity.',
            error
        );

    }

}

// =========================================================
// NAVIGATE AGENCY
// =========================================================

/*
 * Record when the user intentionally requests
 * directions to the currently selected agency.
 */
if (agencyNavigate) {

    agencyNavigate.addEventListener(
        'click',
        event => {

            /*
             * Prevent the browser from navigating
             * before our activity request is started.
             */
            event.preventDefault();


            /*
             * Remember the destination URL that
             * openAgencyDetails() already created.
             */
            const destination =
                agencyNavigate.href;


            /*
             * Remember the selected agency.
             *
             * selectedAgencyId is maintained by
             * openAgencyDetails().
             */
            const agencyId =
                selectedAgencyId;


            /*
             * Only log navigation when an agency
             * is actually selected.
             */
            if (agencyId !== null) {

                logUserActivity(
                    'get_directions',
                    agencyId
                );

            }


            /*
             * Navigate to the directions page.
             *
             * The logging request uses keepalive,
             * allowing it to continue during navigation.
             */
            window.location.href =
                destination;

        }
    );

}

// =========================================================
// CATEGORY ACTIVITY LOGGING
// =========================================================

/*
 * Sends a category-filter interaction to Laravel.
 *
 * The browser only sends the category ID.
 * The server determines the authenticated user,
 * role, IP address, and device information.
 */
async function logCategoryActivity(
    categoryId
) {

    /*
     * Read Laravel's CSRF token from the page.
     */
    const csrfToken =
        document.querySelector(
            'meta[name="csrf-token"]'
        )?.getAttribute('content');


    /*
     * Stop if the CSRF token is unavailable.
     */
    if (!csrfToken) {

        console.error(
            'KNOWURLOCAL: CSRF token not found.'
        );

        return;

    }


    try {

        /*
         * Send the category interaction asynchronously.
         *
         * keepalive allows the request to finish even if
         * the browser immediately changes state or navigates.
         */
        await fetch(
            '/user/activity/category',
            {
                method: 'POST',

                keepalive: true,

                headers: {
                    'Content-Type':
                        'application/json',

                    'Accept':
                        'application/json',

                    'X-CSRF-TOKEN':
                        csrfToken
                },

                body: JSON.stringify({

                    action:
                        'filter_category',

                    category_id:
                        categoryId

                })
            }
        );

    }

    catch (error) {

        /*
         * Logging failures must never interfere
         * with the actual category filtering.
         */
        console.error(
            'KNOWURLOCAL: Failed to record category activity.',
            error
        );

    }

}


    // =========================================================
    // OPEN AGENCY DETAILS
    // =========================================================

    /*
     * Populate and display the custom agency panel.
     *
     * This completely replaces the old Leaflet popup.
     */
    function openAgencyDetails(agency) {

        /*
         * Stop if the panel does not exist.
         */
        if (!agencyDetails) {
            return;
        }

        /*
     * Log the intentional agency view.
     */
    logUserActivity(
        'view_agency',
        agency.id
    );


        /*
         * Remember which agency is selected.
         */
        selectedAgencyId =
            agency.id;


        // -----------------------------------------------------
        // IMAGE
        // -----------------------------------------------------

        /*
         * Use the agency's uploaded image when available.
         *
         * Otherwise use the default agency image.
         */
        const image =
            agency.agency_image
                ? `/storage/${agency.agency_image}`
                : '/images/default-agency.png';


        agencyDetailsImage.src =
            image;


        agencyDetailsImage.alt =
            agency.agency_name ||
            'Agency';


        // -----------------------------------------------------
        // NAME
        // -----------------------------------------------------

        agencyDetailsName.textContent =
            agency.agency_name ||
            'Agency';


        // -----------------------------------------------------
        // ABBREVIATION
        // -----------------------------------------------------

        const abbreviation =
            agency.agency_abbreviation?.trim();


        if (abbreviation) {

            agencyDetailsAbbreviation.textContent =
                abbreviation;

            agencyDetailsAbbreviation.style.display =
                'inline-flex';

        }

        else {

            agencyDetailsAbbreviation.textContent =
                '';

            agencyDetailsAbbreviation.style.display =
                'none';

        }


        // -----------------------------------------------------
// CATEGORY
// -----------------------------------------------------

/*
 * Read the category information returned by the API.
 *
 * The category name and color are both already available
 * because the API eager-loads the category relationship.
 */
const categoryName =
    agency.category?.category_name?.trim();

const categoryColor =
    agency.category?.display_color;


/*
 * Only show the category badge when a category exists.
 */
if (
    agencyDetailsCategory &&
    categoryName
) {

    agencyDetailsCategory.textContent =
        categoryName;

    agencyDetailsCategory.style.display =
        'inline-flex';


    /*
     * Only accept a valid six-digit hexadecimal color.
     *
     * This prevents arbitrary database values from
     * becoming CSS values.
     */
    if (
        /^#[0-9A-Fa-f]{6}$/.test(
            categoryColor
        )
    ) {

        agencyDetailsCategory.style
            .setProperty(
                '--category-color',
                categoryColor
            );

    }

    else {

        /*
         * Fall back to the default blue accent.
         */
        agencyDetailsCategory.style
            .setProperty(
                '--category-color',
                '#3B82F6'
            );

    }

}

else if (agencyDetailsCategory) {

    agencyDetailsCategory.textContent =
        '';

    agencyDetailsCategory.style.display =
        'none';

}


        // -----------------------------------------------------
        // LOCATION
        // -----------------------------------------------------

        const locationText =
            agency.agency_location?.trim();


        agencyDetailsLocation
            .querySelector('span')
            .textContent =
                locationText ||
                'Location unavailable';


        // -----------------------------------------------------
        // AGENCY TYPE
        // -----------------------------------------------------

        const typeName =
            agency.type?.name?.trim();


        agencyDetailsType
            .querySelector('span')
            .textContent =
                typeName ||
                '';


        agencyDetailsType.style.display =
            typeName
                ? 'flex'
                : 'none';


        // -----------------------------------------------------
        // ABOUT
        // -----------------------------------------------------

        setAgencySection(
            agencyAboutSection,
            agencyDetailsDescription,
            agency.agency_description
        );


        // -----------------------------------------------------
        // SERVICES
        // -----------------------------------------------------

        setAgencySection(
            agencyServicesSection,
            agencyDetailsServices,
            agency.services_offered
        );


        // -----------------------------------------------------
        // OFFICE HOURS
        // -----------------------------------------------------

        setAgencySection(
            agencyHoursSection,
            agencyDetailsHours,
            agency.office_hours
        );


        // -----------------------------------------------------
// CONTACTS
// -----------------------------------------------------

/*
 * Remove contact rows belonging to the previous agency
 * before rendering the newly selected agency.
 */
agencyDetailsContacts.innerHTML =
    '';


/*
 * Read the contacts returned by the API.
 *
 * The backend eager-loads:
 *
 * contacts.contactType
 *
 * so every contact should contain its predefined
 * contact type information.
 */

console.log(
    'KNOWURLOCAL agency contacts:',
    agency.agency_name,
    agency.contacts
);


const contacts =
    Array.isArray(
        agency.contacts
    )
        ? agency.contacts
        : [];


/*
 * Render contacts in their database-defined order.
 *
 * The backend already orders contacts using sort_order,
 * but we sort again defensively because the frontend
 * should not depend on response ordering.
 */
contacts
    .slice()
    .sort(
        (a, b) =>
            Number(
                a.sort_order || 0
            ) -
            Number(
                b.sort_order || 0
            )
    )
    .forEach(
        contact => {

            /*
             * Safely obtain the contact type slug.
             */
            const typeSlug =
    String(
        contact
            ?.contact_type
            ?.slug || ''
    )
    .trim()
    .toLowerCase();


/*
 * Find the frontend presentation rules for
 * this database contact type.
 *
 * Example:
 *
 * "hotline" → CONTACT_TYPE_CONFIG.hotline
 * "email"   → CONTACT_TYPE_CONFIG.email
 */
const config =
    CONTACT_TYPE_CONFIG[typeSlug];


console.log(
    'CONTACT DEBUG:',
    contact,
    'typeSlug:',
    typeSlug,
    'config:',
    config
);


if (!config) {

    console.warn(
        'Unsupported contact type:',
        typeSlug,
        contact
    );

    return;

}


            /*
             * Clean the actual contact value.
             */
            const value =
                String(
                    contact?.value || ''
                ).trim();


            /*
             * Ignore empty contact values.
             */
            if (!value) {
                return;
            }


            /*
             * Use the administrator-provided label when
             * available.
             *
             * Otherwise fall back to the predefined
             * contact type name.
             */
            const label =
    String(
        contact?.label ||
        contact
            ?.contact_type
            ?.name ||
        config.label
    ).trim();


            /*
             * Build the destination.
             *
             * Telephone and email contacts use their
             * respective browser protocols.
             *
             * Website and Facebook are validated through
             * normalizeExternalUrl().
             */
            const href =
                config.buildHref(
                    value
                );


            /*
             * Create the contact row.
             */
            const item =
                createContactItem(
                    config.icon,
                    label,
                    value,
                    href,
                    Boolean(
                        contact?.is_primary
                    )
                );


            /*
             * Only append successfully constructed
             * contact elements.
             */
            if (item) {

                agencyDetailsContacts.appendChild(
                    item
                );

            }

        }
    );


/*
 * Hide the entire Contact section when the agency
 * has no valid contact information.
 */
agencyContactSection.style.display =
    agencyDetailsContacts.children.length
        ? ''
        : 'none';


        // -----------------------------------------------------
        // NAVIGATION LINK
        // -----------------------------------------------------

        /*
         * Build the navigation URL using the agency ID.
         */
        agencyNavigate.href =
            `${navigateBaseUrl}/${agency.id}`;

            /*
            * Briefly explain the purpose of the navigation
            * icon whenever a new agency is selected.
            */
            showNavigateHint();


        // -----------------------------------------------------
        // SHOW PANEL
        // -----------------------------------------------------

        agencyDetails.classList.add(
            'active'
        );


        agencyDetails.setAttribute(
            'aria-hidden',
            'false'
        );

    }


    // =========================================================
    // CLOSE AGENCY DETAILS
    // =========================================================

    /*
     * Close the custom agency details panel.
     */
    function closeAgencyDetails() {

        if (!agencyDetails) {
            return;
        }


        agencyDetails.classList.remove(
            'active'
        );


        agencyDetails.setAttribute(
            'aria-hidden',
            'true'
        );


        /*
         * Restore the selected marker to its
         * original category color.
         */
        if (selectedMarker) {

            selectedMarker.setIcon(
                selectedMarker.normalIcon
            );


            selectedMarker.setZIndexOffset(
                0
            );


            selectedMarker
                .getElement()
                ?.classList.remove(
                    'selected-agency-marker'
                );


            selectedMarker =
                null;

        }


        /*
         * No agency remains selected.
         */
        selectedAgencyId =
            null;

    }


    // =========================================================
    // CLOSE BUTTON
    // =========================================================

    if (agencyDetailsClose) {

        agencyDetailsClose.addEventListener(
            'click',
            closeAgencyDetails
        );

    }


    


    // =========================================================
    // CATEGORY FILTER STATE
    // =========================================================

    /*
     * "all" means every agency is visible.
     */
    let activeCategoryId =
        'all';


    /*
     * Category filter container from the navbar.
     */
    const categoryFilters =
        document.getElementById(
            'categoryFilters'
        );


    // =========================================================
    // CATEGORY HORIZONTAL SCROLL
    // =========================================================

    if (categoryFilters) {

        categoryFilters.addEventListener(
            'wheel',
            event => {

                /*
                 * Stop the browser from scrolling the
                 * page/map vertically.
                 */
                event.preventDefault();


                /*
                 * Prevent the event from bubbling
                 * into the navbar.
                 */
                event.stopPropagation();


                /*
                 * Convert vertical wheel movement
                 * into horizontal scrolling.
                 */
                categoryFilters.scrollLeft +=
                    event.deltaY;

            },
            {
                passive: false
            }
        );

    }


    // =========================================================
    // PREVENT NAVBAR PAGE SCROLL
    // =========================================================

    const siteHeader =
        document.querySelector(
            '.site-header'
        );


    if (siteHeader) {

        siteHeader.addEventListener(
            'wheel',
            event => {

                /*
                 * The category row handles its own
                 * scrolling behavior.
                 */
                if (
                    event.target.closest(
                        '#categoryFilters'
                    )
                ) {

                    return;

                }


                /*
                 * Prevent accidental page scrolling
                 * while interacting with the navbar.
                 */
                event.preventDefault();

            },
            {
                passive: false
            }
        );

    }


    // =========================================================
    // AGENCY MAP LABELS
    // =========================================================

    /*
     * Update agency labels according to the
     * current map zoom level.
     */
    function updateAgencyLabels() {

        const zoom =
            map.getZoom();


        Object.values(markers).forEach(
            marker => {

                const agency =
                    marker.agencyData;


                /*
                 * Safety check.
                 */
                if (!agency) {
                    return;
                }


                /*
                 * Full agency name for close zoom.
                 */
                const fullName =
                    escapeHtml(
                        agency.agency_name ||
                        ''
                    );


                /*
                 * Shorter abbreviation for
                 * wider map views.
                 */
                const abbreviation =
                    escapeHtml(
                        agency.agency_abbreviation ||
                        agency.agency_name ||
                        ''
                    );


                /*
 * Use abbreviations at wider zoom levels.
 *
 * Full agency names are only shown when the user
 * is sufficiently zoomed in for the map to have
 * enough visual space.
 */
const label =
    zoom >= 17
        ? fullName
        : abbreviation;


                marker.setTooltipContent(
                    label
                );

            }
        );


        /*
         * Recalculate label positions after
         * their content changes.
         */
        repositionAgencyLabels();

    }


    // =========================================================
    // BUILD CATEGORY FILTERS
    // =========================================================

    function buildCategoryFilters(
        data
    ) {

        if (!categoryFilters) {
            return;
        }


        /*
         * Map keeps each category ID unique.
         */
        const categories =
            new Map();


        data.forEach(
            agency => {

                const category =
                    agency.category;


                if (
                    !category ||
                    !category.id
                ) {

                    return;

                }


                if (
                    !categories.has(
                        category.id
                    )
                ) {

                    categories.set(
                        category.id,
                        category
                    );

                }

            }
        );


        /*
         * Remove old filter buttons.
         */
        categoryFilters.innerHTML =
            '';


        // -----------------------------------------------------
        // ALL BUTTON
        // -----------------------------------------------------

        const allButton =
            document.createElement(
                'button'
            );


        allButton.type =
            'button';


        allButton.className =
            'category-filter active';


        allButton.dataset.categoryId =
            'all';


        allButton.textContent =
            'All';


        allButton.addEventListener(
            'click',
            () => {

                applyCategoryFilter(
                    'all'
                );

            }
        );


        categoryFilters.appendChild(
            allButton
        );


        // -----------------------------------------------------
        // CATEGORY BUTTONS
        // -----------------------------------------------------

        categories.forEach(
            category => {

                const button =
                    document.createElement(
                        'button'
                    );


                button.type =
                    'button';


                button.className =
                    'category-filter';


                button.dataset.categoryId =
                    category.id;


                /*
                 * Colored category dot.
                 */
                const colorDot =
                    document.createElement(
                        'span'
                    );


                colorDot.className =
                    'category-color-dot';


                /*
                 * Only accept a valid six-digit
                 * hexadecimal color.
                 */
                const categoryColor =
                    /^#[0-9A-Fa-f]{6}$/.test(
                        category.display_color
                    )
                        ? category.display_color
                        : '#3B82F6';


                colorDot.style.backgroundColor =
                    categoryColor;


                /*
                 * Category name.
                 */
                const categoryName =
                    document.createElement(
                        'span'
                    );


                categoryName.textContent =
                    category.category_name;


                /*
                 * Build the category button.
                 */
                button.appendChild(
                    colorDot
                );


                button.appendChild(
                    categoryName
                );


                /*
                 * Activate this category.
                 */
                button.addEventListener(
                    'click',
                    () => {

                        applyCategoryFilter(
                            category.id
                        );

                    }
                );


                categoryFilters.appendChild(
                    button
                );

            }
        );

    }


    // =========================================================
    // APPLY CATEGORY FILTER
    // =========================================================

    function applyCategoryFilter(
    categoryId
) {

    /*
     * Normalize the category ID.
     */
    const normalizedCategoryId =
        String(categoryId);


    /*
     * Log intentional category selections.
     *
     * "All" only clears the current filter, so it
     * does not represent a meaningful category choice
     * and therefore is not logged.
     */
    if (
        normalizedCategoryId !== 'all'
    ) {

        logCategoryActivity(
            normalizedCategoryId
        );

    }


    /*
     * Remember the active category.
     */
    activeCategoryId =
        normalizedCategoryId;


        /*
         * Clear search results.
         */
        if (resultsContainer) {

            resultsContainer.innerHTML =
                '';

        }


        /*
         * Process every marker.
         */
        Object.values(markers).forEach(
            marker => {

                const agency =
                    marker.agencyData;


                const agencyCategoryId =
                    agency?.category?.id;


                /*
                 * Determine whether the marker
                 * belongs to the selected category.
                 */
                const shouldShow =
                    activeCategoryId === 'all' ||
                    String(
                        agencyCategoryId
                    ) === activeCategoryId;


                /*
                 * Show or hide the marker.
                 */
                marker.setOpacity(
                    shouldShow
                        ? 1
                        : 0
                );


                /*
                 * Get the agency label.
                 */
                const tooltip =
                    marker.getTooltip();


                if (shouldShow) {

                    if (tooltip) {

                        marker.openTooltip();

                    }

                }

                else {

                    if (tooltip) {

                        marker.closeTooltip();

                    }


                    /*
                     * If the selected agency is being
                     * hidden by this filter, close
                     * its details panel.
                     */
                    if (
                        selectedAgencyId !== null &&
                        String(agency.id) ===
                            String(selectedAgencyId)
                    ) {

                        closeAgencyDetails();

                    }

                }

            }
        );


        /*
         * Update the active category button.
         */
        updateActiveCategoryButton();


        /*
         * Recalculate label positions.
         */
        repositionAgencyLabels();

    }


    // =========================================================
    // ACTIVE CATEGORY BUTTON
    // =========================================================

    function updateActiveCategoryButton() {

        if (!categoryFilters) {
            return;
        }


        const buttons =
            categoryFilters.querySelectorAll(
                '.category-filter'
            );


        buttons.forEach(
            button => {

                const buttonCategoryId =
                    button.dataset.categoryId;


                button.classList.toggle(
                    'active',
                    buttonCategoryId ===
                        activeCategoryId
                );

            }
        );

    }


   // =========================================================
// AGENCY LABEL COLLISION RESOLUTION
// =========================================================

/*
 * Repositions agency labels around their OWN markers.
 *
 * The important difference from the previous algorithm is
 * that labels are not given a permanent priority based on
 * the order in which they are processed.
 *
 * When two labels collide, BOTH labels are evaluated.
 *
 * The label that has the better alternative position
 * becomes the one that moves.
 *
 * The actual Leaflet markers are NEVER moved.
 */
function repositionAgencyLabels() {

    /*
     * Current map zoom.
     *
     * We use this to control how far labels are allowed
     * to move from their markers.
     */
    const zoom =
        map.getZoom();


    // =====================================================
    // COLLECT VISIBLE LABELS
    // =====================================================

    const labels = [];


    Object.values(markers).forEach(
        marker => {

            const agency =
                marker.agencyData;


            /*
             * Ignore invalid marker data.
             */
            if (!agency) {
                return;
            }


            /*
             * Respect the active category filter.
             */
            const categoryId =
                agency.category?.id;


            const isVisible =
                activeCategoryId === 'all' ||
                String(categoryId) ===
                    String(activeCategoryId);


            if (!isVisible) {
                return;
            }


            /*
             * Get the permanent Leaflet tooltip.
             */
            const tooltip =
                marker.getTooltip();


            if (!tooltip) {
                return;
            }


            /*
             * Make sure Leaflet has created the
             * tooltip element.
             */
            if (!tooltip.getElement()) {

                marker.openTooltip();

            }


            const element =
                tooltip.getElement();


            if (!element) {
                return;
            }


            labels.push({

                marker,

                element

            });

        }
    );


    /*
     * Nothing to reposition.
     */
    if (!labels.length) {
        return;
    }


    // =====================================================
    // POSITION CANDIDATES
    // =====================================================

    /*
     * At wider zoom levels, keep labels VERY close
     * to their markers.
     *
     * We don't want another DOLE/BIR situation where
     * the label is technically collision-free but looks
     * disconnected from its marker.
     */
    const closeDistance =
        zoom < 16
            ? 18
            : 22;


    /*
     * First ring of positions.
     *
     * The order is not used as priority between agencies.
     *
     * It only determines which equally good position
     * a single label prefers.
     */
    const candidates = [

        /*
         * Default Leaflet position.
         *
         * Because the tooltip direction is "top",
         * this is naturally above the marker.
         */
        {
            x: 0,
            y: 0
        },


        /*
         * Directly BELOW the marker.
         */
        {
            x: 0,
            y: closeDistance
        },


        /*
         * RIGHT.
         */
        {
            x: closeDistance,
            y: 0
        },


        /*
         * LEFT.
         */
        {
            x: -closeDistance,
            y: 0
        },


        /*
         * LOWER-RIGHT.
         */
        {
            x: closeDistance,
            y: closeDistance
        },


        /*
         * LOWER-LEFT.
         */
        {
            x: -closeDistance,
            y: closeDistance
        },


        /*
         * UPPER-RIGHT.
         */
        {
            x: closeDistance,
            y: -closeDistance
        },


        /*
         * UPPER-LEFT.
         */
        {
            x: -closeDistance,
            y: -closeDistance
        }

    ];


    /*
     * Only allow a second ring when the user is
     * sufficiently zoomed in.
     *
     * At wider views we would rather hide a label
     * than send it far away from its marker.
     */
    if (zoom >= 17) {

        candidates.push(

            {
                x: 38,
                y: 0
            },

            {
                x: -38,
                y: 0
            },

            {
                x: 0,
                y: 38
            },

            {
                x: 0,
                y: -38
            }

        );

    }


    // =====================================================
    // RESET LABEL POSITIONS
    // =====================================================

    labels.forEach(
        item => {

            /*
             * Start from Leaflet's normal tooltip position.
             */
            item.element.style.marginLeft =
                '0px';

            item.element.style.marginTop =
                '0px';

            /*
             * Keep labels visible while we calculate
             * their rectangles.
             */
            item.element.style.visibility =
                'visible';

        }
    );


    // =====================================================
    // RECTANGLE HELPERS
    // =====================================================

    /*
     * Return the current screen rectangle of a label
     * at a specific candidate position.
     */
    function getRect(
        item,
        candidate
    ) {

        item.element.style.marginLeft =
            `${candidate.x}px`;

        item.element.style.marginTop =
            `${candidate.y}px`;


        return item.element.getBoundingClientRect();

    }


    /*
     * Determine whether two rectangles overlap.
     */
    function overlaps(
        first,
        second
    ) {

        return (

            first.left <
                second.right &&

            first.right >
                second.left &&

            first.top <
                second.bottom &&

            first.bottom >
                second.top

        );

    }


    /*
     * Very small visual breathing room.
     *
     * We deliberately keep this low so labels are not
     * pushed unnecessarily far from their markers.
     */
    const padding = 2;


    /*
     * Expand a rectangle by the supplied padding.
     */
    function expandRect(
        rect
    ) {

        return {

            left:
                rect.left -
                padding,

            right:
                rect.right +
                padding,

            top:
                rect.top -
                padding,

            bottom:
                rect.bottom +
                padding

        };

    }


    // =====================================================
    // CURRENT LABEL STATE
    // =====================================================

    /*
     * Every label starts at its natural position.
     */
    const states =
        labels.map(
            item => {

                return {

                    item,

                    position:
                        candidates[0],

                    rect:
                        expandRect(
                            getRect(
                                item,
                                candidates[0]
                            )
                        )

                };

            }
        );


    // =====================================================
    // FIND COLLISIONS
    // =====================================================

    /*
     * Return all currently overlapping label pairs.
     */
    function getCollisions() {

        const collisions = [];


        for (
            let i = 0;
            i < states.length;
            i++
        ) {

            for (
                let j = i + 1;
                j < states.length;
                j++
            ) {

                if (
                    overlaps(
                        states[i].rect,
                        states[j].rect
                    )
                ) {

                    collisions.push({

                        first:
                            states[i],

                        second:
                            states[j]

                    });

                }

            }

        }


        return collisions;

    }


    // =====================================================
    // FIND THE BEST ALTERNATIVE FOR ONE LABEL
    // =====================================================

    /*
     * Evaluate every nearby position around one marker.
     *
     * The label is evaluated against ALL OTHER CURRENT
     * labels, not just the label it happens to collide with.
     */
    function findBestPosition(
        state
    ) {

        const otherStates =
            states.filter(
                other =>
                    other !== state
            );


        let best = null;


        candidates.forEach(
            candidate => {

                /*
                 * Get the label's screen rectangle
                 * at this candidate position.
                 */
                const rect =
                    expandRect(
                        getRect(
                            state.item,
                            candidate
                        )
                    );


                /*
                 * Count how many existing labels this
                 * candidate would collide with.
                 */
                let collisionCount =
                    0;


                otherStates.forEach(
                    other => {

                        if (
                            overlaps(
                                rect,
                                other.rect
                            )
                        ) {

                            collisionCount++;

                        }

                    }
                );


                /*
                 * Calculate how far the label moved
                 * from its natural position.
                 */
                const movement =
                    Math.sqrt(
                        (
                            candidate.x *
                            candidate.x
                        ) +
                        (
                            candidate.y *
                            candidate.y
                        )
                    );


                /*
                 * Count how many candidate positions
                 * could completely avoid other labels.
                 *
                 * This represents the label's flexibility.
                 */
                let freePositions =
                    0;


                candidates.forEach(
                    alternative => {

                        const alternativeRect =
                            expandRect(
                                getRect(
                                    state.item,
                                    alternative
                                )
                            );


                        const hasCollision =
                            otherStates.some(
                                other =>
                                    overlaps(
                                        alternativeRect,
                                        other.rect
                                    )
                            );


                        if (!hasCollision) {

                            freePositions++;

                        }

                    }
                );


                /*
                 * Build the candidate result.
                 */
                const result = {

                    candidate,

                    rect,

                    collisionCount,

                    movement,

                    freePositions

                };


                /*
                 * Determine whether this is better
                 * than the current best candidate.
                 */
                if (!best) {

                    best =
                        result;

                    return;

                }


                /*
                 * FIRST PRIORITY:
                 *
                 * Fewer collisions always wins.
                 */
                if (
                    collisionCount <
                    best.collisionCount
                ) {

                    best =
                        result;

                    return;

                }


                if (
                    collisionCount >
                    best.collisionCount
                ) {

                    return;

                }


                /*
                 * SECOND PRIORITY:
                 *
                 * If both candidates have the same number
                 * of collisions, prefer the one requiring
                 * less movement.
                 */
                if (
                    movement <
                    best.movement
                ) {

                    best =
                        result;

                    return;

                }


                if (
                    movement >
                    best.movement
                ) {

                    return;

                }


                /*
                 * THIRD PRIORITY:
                 *
                 * If movement is equal, prefer the label
                 * position that gives the label more
                 * available alternatives.
                 */
                if (
                    freePositions >
                    best.freePositions
                ) {

                    best =
                        result;

                }

            }
        );


        /*
         * Restore the currently selected best position.
         */
        if (best) {

            state.item.element.style.marginLeft =
                `${best.candidate.x}px`;

            state.item.element.style.marginTop =
                `${best.candidate.y}px`;

        }


        return best;

    }


    // =====================================================
    // COLLISION RESOLUTION
    // =====================================================

    /*
     * Limit the number of iterations so an extremely
     * crowded map can never create an infinite loop.
     */
    const maxIterations =
        labels.length * 6;


    let iteration =
        0;


    while (
        iteration <
        maxIterations
    ) {

        iteration++;


        /*
         * Find the current collisions.
         */
        const collisions =
            getCollisions();


        /*
         * Everything is clean.
         */
        if (!collisions.length) {
            break;
        }


        /*
         * Resolve the first collision.
         */
        const collision =
            collisions[0];


        const firstBest =
            findBestPosition(
                collision.first
            );


        const secondBest =
            findBestPosition(
                collision.second
            );


        /*
         * -------------------------------------------------
         * DECIDE WHICH LABEL SHOULD MOVE
         * -------------------------------------------------
         */

        let labelToMove;


        /*
         * If one label can completely eliminate its
         * collisions while the other cannot, move that one.
         */
        if (
            firstBest.collisionCount === 0 &&
            secondBest.collisionCount > 0
        ) {

            labelToMove =
                collision.first;

        }

        else if (
            secondBest.collisionCount === 0 &&
            firstBest.collisionCount > 0
        ) {

            labelToMove =
                collision.second;

        }


        /*
         * If both can completely resolve the collision,
         * compare their movement cost.
         */
        else if (
            firstBest.collisionCount === 0 &&
            secondBest.collisionCount === 0
        ) {

            /*
             * Prefer moving the label whose alternative
             * requires LESS movement.
             */
            if (
                firstBest.movement <
                secondBest.movement
            ) {

                labelToMove =
                    collision.first;

            }

            else if (
                secondBest.movement <
                firstBest.movement
            ) {

                labelToMove =
                    collision.second;

            }

            /*
             * If movement is identical, move the label
             * with MORE available alternatives.
             *
             * This is the important part for cases like
             * COMELEC vs DILG:
             *
             * the label that has more room to maneuver
             * gives up its current position.
             */
            else if (
                firstBest.freePositions >
                secondBest.freePositions
            ) {

                labelToMove =
                    collision.first;

            }

            else {

                labelToMove =
                    collision.second;

            }

        }


        /*
         * Neither label can completely eliminate its
         * collisions from its current state.
         *
         * Choose the label that improves the situation
         * the most.
         */
        else {

            if (
                firstBest.collisionCount <
                secondBest.collisionCount
            ) {

                labelToMove =
                    collision.first;

            }

            else if (
                secondBest.collisionCount <
                firstBest.collisionCount
            ) {

                labelToMove =
                    collision.second;

            }

            else {

                /*
                 * Equal collision counts:
                 *
                 * prefer the label that can move farther
                 * without becoming disconnected.
                 *
                 * More available positions means more
                 * flexibility.
                 */
                if (
                    firstBest.freePositions >
                    secondBest.freePositions
                ) {

                    labelToMove =
                        collision.first;

                }

                else {

                    labelToMove =
                        collision.second;

                }

            }

        }


        /*
         * -------------------------------------------------
         * APPLY MOVEMENT
         * -------------------------------------------------
         */

        if (labelToMove) {

            const best =
                labelToMove ===
                    collision.first
                    ? firstBest
                    : secondBest;


            /*
             * Apply the selected candidate.
             */
            labelToMove.position =
                best.candidate;


            labelToMove.rect =
                best.rect;


            /*
             * Keep the label visible.
             */
            labelToMove.item.element.style.visibility =
                'visible';

        }


        /*
         * -------------------------------------------------
         * REFRESH ALL RECTANGLES
         * -------------------------------------------------
         *
         * Other labels may now have a different
         * relationship to the moved label.
         */
        states.forEach(
            state => {

                state.rect =
                    expandRect(
                        getRect(
                            state.item,
                            state.position
                        )
                    );

            }
        );

    }


    // =====================================================
    // FINAL CLEANUP
    // =====================================================

    /*
     * After collision resolution, make sure every
     * visible label uses its final calculated position.
     */
    states.forEach(
        state => {

            state.item.element.style.marginLeft =
                `${state.position.x}px`;

            state.item.element.style.marginTop =
                `${state.position.y}px`;

            state.item.element.style.visibility =
                'visible';

        }
    );

}


    


    // =========================================================
    // LOAD AGENCIES
    // =========================================================

    fetch('/api/agencies')

        .then(
            response => {

                /*
                 * Convert the API response into JSON.
                 */
                return response.json();

            }
        )

        .then(
            data => {

                /*
                 * Build the category filter buttons.
                 */
                buildCategoryFilters(
                    data
                );


                /*
                 * Create a marker for every agency.
                 */
                data.forEach(
                    agency => {

                        // -------------------------------------------------
                        // CATEGORY COLOR
                        // -------------------------------------------------

                        const rawCategoryColor =
                            agency
                                .category
                                ?.display_color;


                        const categoryColor =
                            /^#[0-9A-Fa-f]{6}$/.test(
                                rawCategoryColor
                            )
                                ? rawCategoryColor
                                : '#3B82F6';


                        // -------------------------------------------------
                        // NORMAL CATEGORY MARKER
                        // -------------------------------------------------

                        const icon =
                            L.divIcon({

                                /*
                                 * Custom marker wrapper.
                                 */
                                className:
                                    'category-map-marker',


                                /*
                                 * Category-colored marker.
                                 */
                                html: `
                                    <div
                                        class="category-marker-circle"
                                        style="--marker-color: ${categoryColor}">
                                    </div>
                                `,


                                /*
                                 * Marker size.
                                 */
                                iconSize: [
                                    24,
                                    24
                                ],


                                /*
                                 * Center the marker
                                 * on its coordinate.
                                 */
                                iconAnchor: [
                                    12,
                                    12
                                ]

                            });


                        // -------------------------------------------------
                        // CREATE LEAFLET MARKER
                        // -------------------------------------------------

                        const marker =
                            L.marker(
                                [
                                    agency.lat,
                                    agency.lng
                                ],
                                {
                                    icon:
                                        icon
                                }
                            );


                        /*
                         * Remember the marker's normal icon.
                         *
                         * This allows us to restore it after
                         * another agency becomes selected.
                         */
                        marker.normalIcon =
                            icon;


                        /*
                         * Attach the complete agency object
                         * to the marker.
                         */
                        marker.agencyData =
                            agency;


                        /*
                         * Add the marker to the map.
                         */
                        marker.addTo(
                            map
                        );


                        // -------------------------------------------------
                        // MARKER CLICK
                        // -------------------------------------------------

                        marker.on(
                            'click',
                            () => {

                                /*
                                 * Use the same focus behavior
                                 * used by search.
                                 */
                                focusMarker(
                                    marker
                                );

                            }
                        );


                        // -------------------------------------------------
                        // AGENCY LABEL
                        // -------------------------------------------------

                        /*
                         * Permanent tooltip acts as the agency
                         * label on the map.
                         *
                         * This is NOT a Leaflet popup.
                         */
                        marker.bindTooltip(
                            '',
                            {
                                permanent: true,

                                direction:
                                    'top',

                                offset: [
                                    0,
                                    -14
                                ],

                                className:
                                    'agency-map-label'
                            }
                        );


                        // -------------------------------------------------
                        // STORE MARKER
                        // -------------------------------------------------

                        /*
                         * Store the marker by lowercase agency name
                         * for search functionality.
                         */
                        markers[
                            agency.agency_name
                                .toLowerCase()
                        ] =
                            marker;

                    }
                );


                /*
                 * Set initial agency labels.
                 */
                updateAgencyLabels();

            }
        )

        .catch(
            error => {

                /*
                 * Log the technical error for development.
                 *
                 * We don't expose internal API details
                 * directly to users.
                 */
                console.error(
                    'Unable to load agencies:',
                    error
                );

            }
        );


    // =========================================================
    // MAP EVENTS
    // =========================================================

    /*
     * Update labels when zoom changes.
     */
    map.on(
    'zoomend',
    () => {

        /*
         * Wait one browser frame so Leaflet can
         * finish updating tooltip positions.
         */
        requestAnimationFrame(() => {

            updateAgencyLabels();

        });

    }
);


    /*
     * Recalculate label positions whenever
     * the map moves.
     */
    map.on(
    'moveend',
    () => {

        /*
         * Wait one browser frame before measuring
         * the label rectangles.
         */
        requestAnimationFrame(() => {

            repositionAgencyLabels();

        });

    }
);


    // =========================================================
    // SEARCH
    // =========================================================

    const searchInput =
        document.getElementById(
            'searchInput'
        );


    const searchBtn =
        document.getElementById(
            'searchBtn'
        );


    /*
     * Process the search field.
     */
    function handleSearch(
        isSubmit = false
    ) {

        /*
         * Normalize the query.
         */
        const query =
            searchInput.value
                .toLowerCase()
                .trim();


        /*
         * Empty search clears the results.
         */
        if (!query) {

            resultsContainer.innerHTML =
                '';

            return;

        }


        /*
         * Convert marker object into an array
         * of [name, marker] entries.
         */
        const entries =
            Object.entries(
                markers
            );


        /*
         * Find matching agencies.
         */
        const matches =
            entries.filter(
                ([name, marker]) => {

                    const agencyCategoryId =
                        marker
                            .agencyData
                            ?.category
                            ?.id;


                    /*
                     * Search only inside the
                     * active category.
                     */
                    const matchesCategory =
                        activeCategoryId ===
                            'all' ||
                        String(
                            agencyCategoryId
                        ) ===
                            String(
                                activeCategoryId
                            );


                    if (!matchesCategory) {

                        return false;

                    }


                    /*
                     * Split multi-word searches.
                     */
                    const words =
                        query.split(
                            /\s+/
                        );


                    const agencyName =
                        marker
                            .agencyData
                            ?.agency_name
                            ?.toLowerCase() ||
                        '';


                    const agencyAbbreviation =
                        marker
                            .agencyData
                            ?.agency_abbreviation
                            ?.toLowerCase() ||
                        '';


                    /*
                     * Every search word must appear
                     * in the agency's full name.
                     */
                    const matchesFullName =
                        words.every(
                            word =>
                                agencyName.includes(
                                    word
                                )
                        );


                    /*
                     * Abbreviation can match
                     * directly or partially.
                     */
                    const matchesAbbreviation =
                        agencyAbbreviation ===
                            query ||
                        agencyAbbreviation.includes(
                            query
                        );


                    return (
                        matchesFullName ||
                        matchesAbbreviation
                    );

                }
            );


        // -----------------------------------------------------
        // NO RESULTS
        // -----------------------------------------------------

        if (
            matches.length === 0
        ) {

            resultsContainer.innerHTML =
                '<div class="search-item">No results</div>';

            return;

        }


        // -----------------------------------------------------
        // SUBMIT SEARCH
        // -----------------------------------------------------

        // -----------------------------------------------------
// SUBMIT SEARCH
// -----------------------------------------------------

if (isSubmit) {

    if (matches.length === 1) {

    const [
        ,
        marker
    ] = matches[0];


    /*
     * Record the user's intentional agency search.
     */
    logUserActivity(
        'search_agency',
        marker.agencyData.id
    );


    /*
     * Continue through the existing selection workflow.
     */
    focusMarker(
        marker
    );


    /*
     * Remove the suggestions because the agency
     * has already been selected.
     */
    resultsContainer.innerHTML =
        '';


    return;
}


    /*
     * If multiple agencies match the query,
     * keep the suggestions visible instead of
     * arbitrarily selecting the first result.
     */
    showResults(
        matches
    );


    return;

}


        // -----------------------------------------------------
        // SHOW SEARCH OPTIONS
        // -----------------------------------------------------

        showResults(
            matches
        );

    }


    // =========================================================
// FOCUS SELECTED AGENCY
// =========================================================

/*
 * Focus the selected agency with one smooth animation.
 *
 * The map simultaneously pans and zooms, but the zoom
 * is mathematically targeted around the selected marker.
 *
 * This prevents the marker from disappearing while the
 * map zooms toward the center of the screen.
 */
function focusMarker(marker) {

    /*
     * Get the selected agency's coordinates.
     */
    const latlng = marker.getLatLng();


    /*
     * Highlight the selected marker.
     */
    selectAgencyMarker(marker);


    /*
     * Open the agency details panel.
     */
    openAgencyDetails(
        marker.agencyData
    );


    /*
     * Wait one browser frame so the panel has started
     * opening before we calculate the final composition.
     */
    requestAnimationFrame(() => {

        /*
         * Target zoom level.
         */
        const targetZoom = 17;


        /*
         * Get the current map dimensions.
         */
        const mapSize =
            map.getSize();


        /*
         * Determine where the selected marker should
         * appear in the final composition.
         */
        let targetPoint;


        // -------------------------------------------------
        // MOBILE
        // -------------------------------------------------

        if (window.innerWidth <= 900) {

            /*
             * Keep the marker around 200px from the
             * top of the visible map.
             *
             * This is the position you previously
             * liked.
             */
            targetPoint = L.point(
                mapSize.x / 2,
                220
            );

        }


        // -------------------------------------------------
        // DESKTOP
        // -------------------------------------------------

        else {

            /*
             * The details panel occupies the LEFT side.
             *
             * Therefore, place the selected agency slightly
             * to the RIGHT of the normal map center.
             */
            targetPoint = L.point(
                mapSize.x / 2 + 180,
                mapSize.y / 2
            );

        }


        /*
         * -------------------------------------------------
         * IMPORTANT PART
         * -------------------------------------------------
         *
         * Project the selected agency using the TARGET
         * zoom level, not the current zoom level.
         *
         * This tells Leaflet exactly where the marker
         * will exist when the map reaches zoom 17.
         */
        const markerProjected =
            map.project(
                latlng,
                targetZoom
            );


        /*
         * The center of the map's screen.
         *
         * Leaflet's projected coordinates are based on
         * the world coordinate system at targetZoom.
         */
        const screenCenter = L.point(
            mapSize.x / 2,
            mapSize.y / 2
        );


        /*
         * Calculate the projected position that the
         * map center needs to have.
         *
         * In other words:
         *
         * marker position
         *        -
         * desired screen position
         *        +
         * screen center
         *
         * = required map center
         */
        const targetCenterProjected =
            markerProjected
                .subtract(targetPoint)
                .add(screenCenter);


        /*
         * Convert that projected point back into
         * latitude/longitude at the target zoom.
         */
        const targetCenter =
            map.unproject(
                targetCenterProjected,
                targetZoom
            );


        /*
         * ONE animation.
         *
         * Leaflet now simultaneously:
         *
         * - pans
         * - zooms
         *
         * while targeting the selected agency's
         * final screen position.
         */
        map.flyTo(
            targetCenter,
            targetZoom,
            {
                animate: true,

                /*
                 * Fast enough to feel responsive.
                 */
                duration: 0.55,

                /*
                 * Slightly stronger easing gives the
                 * transition a more deliberate feel.
                 */
                easeLinearity: 0.25
            }
        );

    });

}


    // =========================================================
// CLOSE SEARCH WHEN CLICKING OUTSIDE
// =========================================================

const searchForm =
    document.querySelector(
        '.search-form'
    );


document.addEventListener(
    'click',
    event => {

        /*
         * If the search interface isn't present,
         * nothing needs to happen.
         */
        if (
            !searchForm ||
            !resultsContainer
        ) {
            return;
        }


        /*
         * Don't close results when the user
         * is interacting with the search UI.
         */
        if (
            searchForm.contains(
                event.target
            )
        ) {
            return;
        }


        /*
         * Close search results.
         */
        resultsContainer.innerHTML =
            '';

    }
);


// =========================================================
// CLOSE AGENCY DETAILS WHEN CLICKING OUTSIDE
// =========================================================

document.addEventListener(
    'click',
    event => {

        /*
         * Do nothing when the agency details panel
         * is currently closed.
         */
        if (
            !agencyDetails ||
            !agencyDetails.classList.contains('active')
        ) {
            return;
        }


        /*
         * Keep the panel open when the user clicks
         * anywhere inside the panel.
         *
         * This includes:
         *
         * - Navigate
         * - Close
         * - Contact links
         * - Agency information
         */
        if (
            event.target.closest(
                '#agencyDetails'
            )
        ) {
            return;
        }


        /*
         * Keep the panel open when the user clicks
         * an agency marker.
         *
         * This is important because marker clicks
         * first open the panel, then the browser
         * bubbles the same click to document.
         */
        if (
            event.target.closest(
                '.leaflet-marker-icon'
            )
        ) {
            return;
        }


        /*
         * Keep the panel open when the user clicks
         * a search result.
         *
         * Search results can call focusMarker(),
         * which opens the agency panel.
         */
        if (
            resultsContainer &&
            resultsContainer.contains(
                event.target
            )
        ) {
            return;
        }


        /*
         * Keep the panel open when interacting
         * with the search interface.
         */
        if (
            searchForm &&
            searchForm.contains(
                event.target
            )
        ) {
            return;
        }


        /*
         * At this point the click happened outside:
         *
         * - the agency panel
         * - an agency marker
         * - search results
         * - the search interface
         *
         * Therefore it is safe to close the panel.
         */
        closeAgencyDetails();

    }
);

    


    // =========================================================
    // SEARCH RESULT UI
    // =========================================================

    function showResults(
        matches
    ) {

        /*
         * Clear old results.
         */
        resultsContainer.innerHTML =
            '';


        matches.forEach(
            ([name, marker]) => {

                /*
                 * Create the result element.
                 */
                const item =
                    document.createElement(
                        'div'
                    );


                item.classList.add(
                    'search-item'
                );


                /*
                 * Build the result interface.
                 *
                 * The agency name comes from our
                 * already-normalized search key.
                 */
                item.innerHTML = `
                    <div class="search-content">

                        <div class="search-top">

                            <i class="ph-light ph-buildings"></i>

                            <span class="search-name">
                                ${escapeHtml(name)}
                            </span>

                        </div>

                    </div>
                `;


                item.addEventListener(
    'click',
    event => {

        /*
         * Prevent the document-level click handler
         * from treating this as an outside click.
         */
        event.stopPropagation();


        /*
         * Record that the user intentionally selected
         * this agency from the search results.
         *
         * This is different from view_agency:
         *
         * search_agency = search intent
         * view_agency   = actually opening the agency
         */
        logUserActivity(
            'search_agency',
            marker.agencyData.id
        );


        /*
         * Reuse the existing agency-selection workflow.
         *
         * This handles:
         *
         * - marker selection
         * - agency details panel
         * - map animation
         * - navigation button
         */
        focusMarker(
            marker
        );


        /*
         * Search suggestions are no longer needed
         * after the agency has been selected.
         */
        resultsContainer.innerHTML =
            '';

    }
);


                resultsContainer.appendChild(
                    item
                );

            }
        );

    }


    // =========================================================
    // SEARCH BUTTON
    // =========================================================

    if (searchBtn) {

        searchBtn.addEventListener(
            'click',
            () => {

                handleSearch(
                    true
                );

            }
        );

    }


    // =========================================================
    // SEARCH INPUT
    // =========================================================

    if (searchInput) {

        /*
         * Update search suggestions while typing.
         */
        searchInput.addEventListener(
            'input',
            () => {

                handleSearch(
                    false
                );

            }
        );


        /*
         * Submit the search when Enter is pressed.
         */
        searchInput.addEventListener(
            'keyup',
            event => {

                if (
                    event.key ===
                    "Enter"
                ) {

                    if (searchBtn) {

                        searchBtn.click();

                    }

                }

            }
        );

    }


    // =========================================================
    // HTML ESCAPING
    // =========================================================

    /*
     * Escape text before placing it into HTML.
     *
     * This is especially important because agency names
     * originate from database data.
     */
    function escapeHtml(
        text
    ) {

        const div =
            document.createElement(
                'div'
            );


        /*
         * textContent treats the supplied value
         * as plain text instead of HTML.
         */
        div.textContent =
            text || '';


        /*
         * Reading innerHTML now returns
         * the safely escaped representation.
         */
        return div.innerHTML;

    }

});