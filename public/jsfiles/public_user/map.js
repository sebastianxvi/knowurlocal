document.addEventListener("DOMContentLoaded", () => {

    // =========================================================
    // APP CONFIGURATION
    // =========================================================

    /*
     * Base URL used when the user wants to navigate
     * to a selected agency.
     *
     * Blade supplies this value through window.APP_CONFIG.
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
     * The map immediately starts at San Jose with
     * zoom level 14.
     *
     * There is intentionally NO initial flyTo()
     * animation because the map should become usable
     * immediately after loading.
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
     *
     * Leaflet handles loading and positioning the
     * individual map tiles automatically.
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
    // MARKER STATE
    // =========================================================

    /*
     * Stores all agency markers.
     *
     * The lowercase agency name is used as the
     * lookup key for search functionality.
     */
    const markers = {};


    /*
     * Stores the ID of the currently selected agency.
     *
     * This is used by category filtering and navigation.
     */
    let selectedAgencyId = null;


    /*
     * Stores the currently selected Leaflet marker.
     *
     * Only one marker can use the selected icon
     * at a time.
     */
    let selectedMarker = null;


    // =========================================================
    // SELECTED AGENCY MARKER
    // =========================================================

    /*
     * Use Leaflet's built-in default marker for
     * the selected agency.
     *
     * No entrance animation is applied.
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


    // =========================================================
    // CREATE CONTACT ITEM
    // =========================================================

    /*
     * Builds one contact row using DOM APIs instead of
     * innerHTML.
     *
     * This prevents contact values from being interpreted
     * as HTML.
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
             * External HTTP(S) websites open in a new tab.
             *
             * tel: and mailto: links are handled by
             * the browser/device directly.
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
         * Mark the primary contact.
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


    // =========================================================
    // CONTACT TYPE CONFIGURATION
    // =========================================================

    /*
     * Maps database contact-type slugs to presentation rules.
     *
     * The database remains the source of truth for
     * contact types.
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


    // =========================================================
    // NORMALIZE EXTERNAL URL
    // =========================================================

    /*
     * Ensures website and social-media values become
     * valid absolute HTTP(S) URLs before being assigned
     * to href.
     */
    function normalizeExternalUrl(
        value
    ) {

        /*
         * Remove accidental whitespace.
         */
        const cleanValue =
            String(value || '').trim();


        /*
         * Stop when there is no usable value.
         */
        if (!cleanValue) {
            return null;
        }


        /*
         * Only allow HTTP(S) destinations.
         *
         * This prevents dangerous URL schemes such as:
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
         * Convert www.example.com into
         * https://www.example.com.
         */
        if (
            cleanValue.startsWith(
                'www.'
            )
        ) {

            return `https://${cleanValue}`;
        }


        /*
         * For other values, prepend HTTPS.
         */
        return `https://${cleanValue}`;

    }


    // =========================================================
    // SELECT AGENCY MARKER
    // =========================================================

    /*
     * Change the selected agency marker into
     * the selected Leaflet marker.
     *
     * No CSS animation or forced browser reflow
     * is performed here.
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
         * with the selected marker.
         */
        marker.setIcon(
            selectedAgencyIcon
        );


        /*
         * Make the selected marker appear above
         * the other markers.
         */
        marker.setZIndexOffset(
            1000
        );

    }


    // =========================================================
    // NAVIGATE BUTTON HINT
    // =========================================================

    /*
     * Temporarily expand the Navigate button so the
     * user can understand what the navigation icon does.
     */
    function showNavigateHint() {

        /*
         * Stop any previous collapse timer.
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
         * CSS handles the actual transition.
         */
        agencyNavigate.classList.add(
            'is-expanded'
        );


        /*
         * Keep the explanatory text visible
         * for approximately 2.5 seconds.
         */
        navigateHintTimer =
            setTimeout(
                () => {

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
     * The server determines the authenticated user,
     * role, IP address, and device information.
     */
    async function logUserActivity(
        action,
        agencyId,
        contactType = null
    ) {

        /*
         * Read Laravel's CSRF token from the page.
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
             * keepalive allows the request to continue
             * if the browser navigates immediately afterward.
             */
            await fetch(
                '/user/activity/agency',
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
                            action,

                        agency_id:
                            agencyId,

                        contact_type:
                            contactType

                    })

                }
            );

        }


        catch (error) {

            /*
             * Logging failures must never break
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
                 * Prevent navigation before the activity
                 * request is started.
                 */
                event.preventDefault();


                /*
                 * Remember the destination URL.
                 */
                const destination =
                    agencyNavigate.href;


                /*
                 * Remember the selected agency.
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
             * with category filtering.
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
    function openAgencyDetails(
        agency
    ) {

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
         * Read category information returned by the API.
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
         * Remove contact rows belonging to the previous agency.
         */
        agencyDetailsContacts.innerHTML =
            '';


        /*
         * Read contacts returned by the API.
         */
        const contacts =
            Array.isArray(
                agency.contacts
            )
                ? agency.contacts
                : [];


        /*
         * Render contacts in database-defined order.
         *
         * Sorting defensively prevents the frontend
         * from depending on response ordering.
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
                     * Find presentation rules for this
                     * database contact type.
                     */
                    const config =
                        CONTACT_TYPE_CONFIG[
                            typeSlug
                        ];


                    /*
                     * Ignore unsupported contact types.
                     */
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
                     * Use administrator-provided label
                     * when available.
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
                     * Build the safe destination.
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
                     * Only append successfully created elements.
                     */
                    if (item) {

                        agencyDetailsContacts.appendChild(
                            item
                        );

                    }

                }
            );


        /*
         * Hide the Contact section when no valid
         * contact information exists.
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
         * Briefly explain the navigation icon.
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

        /*
         * Stop when the panel doesn't exist.
         */
        if (!agencyDetails) {
            return;
        }


        /*
         * Hide the panel.
         */
        agencyDetails.classList.remove(
            'active'
        );


        agencyDetails.setAttribute(
            'aria-hidden',
            'true'
        );


        /*
         * Restore the selected marker to
         * its normal category icon.
         */
        if (selectedMarker) {

            selectedMarker.setIcon(
                selectedMarker.normalIcon
            );


            selectedMarker.setZIndexOffset(
                0
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
     * Update the text displayed by agency labels.
     *
     * IMPORTANT:
     *
     * This function does NOT perform collision detection.
     * It does NOT calculate DOM rectangles.
     * It does NOT manually move labels.
     *
     * Leaflet automatically keeps each permanent tooltip
     * attached to its corresponding marker.
     */
    function updateAgencyLabels() {

    /*
     * Get the current zoom level.
     *
     * We use abbreviations when zoomed out and
     * full agency names when zoomed in.
     */
    const zoom = map.getZoom();

    /*
     * Every label already has a fixed width in CSS:
     *
     *     width: 110px;
     *
     * So we can use that known value instead of
     * measuring the DOM.
     */
    const labelWidth = 110;

    /*
     * Three lines at approximately 14.4px line-height
     * gives us roughly 43px of label height.
     */
    const labelHeight = 44;

    /*
     * Small gap around labels.
     *
     * This prevents labels from touching each other.
     */
    const gap = 6;

    /*
     * These are the only positions a label may use.
     *
     * We start with the normal position above
     * the marker, then try alternative positions.
     */
    const positions = [
        [0, -14],      // top
        [62, -14],     // top-right
        [-62, -14],    // top-left
        [0, 38],       // bottom
        [62, 38],      // bottom-right
        [-62, 38]      // bottom-left
    ];

    /*
     * Store the labels that have already been placed.
     *
     * Future labels will avoid these rectangles.
     */
    const placedLabels = [];

    /*
     * Process every marker exactly once.
     */
    Object.values(markers).forEach(marker => {

    /*
     * Retrieve the agency associated with
     * this marker.
     */
    const agency = marker.agencyData;

    /*
     * Ignore invalid markers.
     */
    if (!agency) {
        return;
    }

    /*
     * Retrieve the tooltip attached to the marker.
     */
    const tooltip = marker.getTooltip();

    /*
     * Ignore markers without labels.
     */
    if (!tooltip) {
        return;
    }

    /*
     * Determine whether this agency belongs to
     * the currently selected category.
     *
     * The label must follow the same visibility
     * rules as its marker.
     */
    const agencyCategoryId =
        agency.category?.id;

    const shouldShow =
        activeCategoryId === 'all' ||
        String(agencyCategoryId) ===
            String(activeCategoryId);

    /*
     * Hide the label when its agency is outside
     * the active category.
     *
     * This is necessary because Leaflet tooltips
     * are separate layers from markers.
     */
    if (!shouldShow) {

        tooltip.setOpacity(0);

        return;
    }

        /*
         * Decide which text should be displayed.
         *
         * At zoom 17 and above we show the full name.
         * Otherwise we prefer the abbreviation.
         */
        const label =
            zoom >= 17
                ? (
                    agency.agency_name ||
                    ""
                )
                : (
                    agency.agency_abbreviation ||
                    agency.agency_name ||
                    ""
                );

        /*
         * Update the tooltip text.
         */
        tooltip.setContent(label);

        /*
         * Convert the marker's geographic position
         * into pixels relative to the map container.
         */
        const point =
            map.latLngToContainerPoint(
                marker.getLatLng()
            );

        /*
         * We haven't found a safe position yet.
         */
        let selectedPosition = null;

        /*
         * Try each predefined position.
         *
         * There is no endless loop here.
         */
        for (
            const offset of positions
        ) {

            /*
             * Calculate the center of the label.
             */
            const centerX =
                point.x +
                offset[0];

            const centerY =
                point.y +
                offset[1];

            /*
             * Calculate the rectangle boundaries.
             */
            const left =
                centerX -
                labelWidth / 2;

            const right =
                centerX +
                labelWidth / 2;

            const top =
                centerY -
                labelHeight / 2;

            const bottom =
                centerY +
                labelHeight / 2;

            /*
             * Assume this position is safe.
             */
            let collision = false;

            /*
             * Compare the candidate rectangle
             * against labels that were already placed.
             */
            for (
                const placed of placedLabels
            ) {

                /*
                 * Standard rectangle collision test.
                 *
                 * If the rectangles overlap,
                 * this position cannot be used.
                 */
                const overlaps =
                    !(
                        right + gap <
                        placed.left ||

                        left - gap >
                        placed.right ||

                        bottom + gap <
                        placed.top ||

                        top - gap >
                        placed.bottom
                    );

                /*
                 * Stop immediately after finding
                 * a collision.
                 */
                if (overlaps) {
                    collision = true;
                    break;
                }
            }

            /*
             * If no collision was found,
             * this is our selected position.
             */
            if (!collision) {

                selectedPosition = {
                    offset,
                    left,
                    right,
                    top,
                    bottom
                };

                break;
            }
        }

        /*
         * If none of the positions are available,
         * hide the label.
         *
         * We do NOT keep calculating positions.
         */
        if (!selectedPosition) {

            tooltip.setOpacity(0);

            return;
        }

        /*
         * Make sure the label is visible.
         */
        tooltip.setOpacity(1);

        /*
         * Apply the selected offset.
         */
        tooltip.options.offset =
            selectedPosition.offset;

        /*
         * Update only this tooltip.
         *
         * We do not close and reopen every tooltip.
         */
        tooltip.update();

        /*
         * Save this label's rectangle.
         *
         * Later labels will avoid it.
         */
        placedLabels.push({
            left:
                selectedPosition.left,

            right:
                selectedPosition.right,

            top:
                selectedPosition.top,

            bottom:
                selectedPosition.bottom
        });
    });
}


    // =========================================================
    // BUILD CATEGORY FILTERS
    // =========================================================

    function buildCategoryFilters(
        data
    ) {

        /*
         * Stop when the category filter container
         * does not exist.
         */
        if (!categoryFilters) {
            return;
        }


        /*
         * Map keeps each category ID unique.
         */
        const categories =
            new Map();


        /*
         * Extract categories from agency data.
         */
        data.forEach(
            agency => {

                const category =
                    agency.category;


                /*
                 * Ignore agencies without valid categories.
                 */
                if (
                    !category ||
                    !category.id
                ) {

                    return;

                }


                /*
                 * Store each category only once.
                 */
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
                 * Create the colored category dot.
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
                 * Create the category name.
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
         * "All" only clears the current filter,
         * so it is not logged as a category choice.
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
         *
         * No tooltip calculations are performed here.
         */
        Object.values(markers).forEach(
            marker => {

                const agency =
                    marker.agencyData;


                const agencyCategoryId =
                    agency?.category?.id;


                /*
                 * Determine whether the marker belongs
                 * to the selected category.
                 */
                const shouldShow =
                    activeCategoryId === 'all' ||
                    String(
                        agencyCategoryId
                    ) === activeCategoryId;


                /*
                 * Show or hide the marker by opacity.
                 *
                 * The marker remains in Leaflet's layer system,
                 * avoiding unnecessary creation/destruction.
                 */
                marker.setOpacity(
                    shouldShow
                        ? 1
                        : 0
                );

                /*
                * Agency labels are separate Leaflet tooltip layers,
                * so their visibility must be updated independently
                * from the marker itself.
                */
                const tooltip =
                    marker.getTooltip();

                if (tooltip) {

                    tooltip.setOpacity(
                        shouldShow
                            ? 1
                            : 0
                    );

                }


                /*
                 * If the selected agency is being hidden
                 * by the filter, close its details panel.
                 */
                if (
                    !shouldShow &&
                    selectedAgencyId !== null &&
                    String(agency.id) ===
                        String(selectedAgencyId)
                ) {

                    closeAgencyDetails();

                }

            }
        );


        /*
         * Update the active category button.
         */
        updateActiveCategoryButton();

    }


    // =========================================================
    // ACTIVE CATEGORY BUTTON
    // =========================================================

    function updateActiveCategoryButton() {

        /*
         * Stop when the filter container doesn't exist.
         */
        if (!categoryFilters) {
            return;
        }


        const buttons =
            categoryFilters.querySelectorAll(
                '.category-filter'
            );


        /*
         * Toggle the active state of each button.
         */
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
    // LOAD AGENCIES
    // =========================================================

    fetch('/api/agencies')

        .then(
            response => {

                /*
                 * A non-success HTTP response should not
                 * be treated as valid agency data.
                 */
                if (!response.ok) {

                    throw new Error(
                        `Agency API request failed with status ${response.status}.`
                    );

                }


                /*
                 * Convert the response into JSON.
                 */
                return response.json();

            }
        )

        .then(
            data => {

                /*
                 * Make sure the API returned an array.
                 */
                if (!Array.isArray(data)) {

                    throw new Error(
                        'Agency API returned an invalid response.'
                    );

                }


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


                        /*
                         * Only allow valid six-digit
                         * hexadecimal colors.
                         */
                        const categoryColor =
                            /^#[0-9A-Fa-f]{6}$/.test(
                                rawCategoryColor
                            )
                                ? rawCategoryColor
                                : '#3B82F6';


                        // -------------------------------------------------
                        // NORMAL CATEGORY MARKER
                        // -------------------------------------------------

                        /*
                         * Create a lightweight custom marker.
                         *
                         * No permanent label is included inside
                         * the marker HTML itself.
                         */
                        const icon =
                            L.divIcon({

                                className:
                                    'category-map-marker',


                                html: `
                                    <div
                                        class="category-marker-circle"
                                        style="--marker-color: ${categoryColor}">
                                    </div>
                                `,


                                iconSize: [
                                    24,
                                    24
                                ],


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
                         * Create a permanent Leaflet tooltip
                         * that acts as the agency's map label.
                         *
                         * Leaflet automatically moves this tooltip
                         * together with the marker.
                         *
                         * There is intentionally NO collision
                         * detection or manual positioning.
                         */
                        marker.bindTooltip(
                            '',
                            {
                                permanent:
                                    true,

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
                 * Set the initial agency labels.
                 *
                 * This only sets their text.
                 *
                 * It does not reposition them manually.
                 */
                updateAgencyLabels();

            }
        )

        .catch(
            error => {

                /*
                 * Log the technical error for development.
                 *
                 * Internal API details are not displayed
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
     * Update label text only after the zoom level changes.
     *
     * We do NOT listen to moveend because dragging the map
     * does not change whether the label should display the
     * abbreviation or full agency name.
     */
    map.on(
        'zoomend',
        () => {

            updateAgencyLabels();

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
         * Stop if the search input doesn't exist.
         */
        if (!searchInput) {
            return;
        }


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

            if (resultsContainer) {

                resultsContainer.innerHTML =
                    '';

            }

            return;

        }


        /*
         * Convert marker object into
         * [name, marker] entries.
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
                     * Search only inside the active category.
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
                     * Abbreviation can match directly
                     * or partially.
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

            if (resultsContainer) {

                resultsContainer.innerHTML =
                    '<div class="search-item">No results</div>';

            }

            return;

        }


        // -----------------------------------------------------
        // SUBMIT SEARCH
        // -----------------------------------------------------

        if (isSubmit) {

            /*
             * Automatically select the agency only
             * when exactly one result exists.
             */
            if (matches.length === 1) {

                const [
                    ,
                    marker
                ] = matches[0];


                /*
                 * Record the user's intentional
                 * agency search.
                 */
                logUserActivity(
                    'search_agency',
                    marker.agencyData.id
                );


                /*
                 * Continue through the normal
                 * agency-selection workflow.
                 */
                focusMarker(
                    marker
                );


                /*
                 * Remove suggestions after selection.
                 */
                if (resultsContainer) {

                    resultsContainer.innerHTML =
                        '';

                }

                return;

            }


            /*
             * Keep multiple matching agencies visible
             * instead of arbitrarily selecting one.
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
     * IMPORTANT:
     *
     * This is the ONLY automatic flyTo() in this file.
     *
     * It happens only after the user intentionally selects
     * an agency.
     */
    function focusMarker(
        marker
    ) {

        /*
         * Get the selected agency's coordinates.
         */
        const latlng =
            marker.getLatLng();


        /*
         * Highlight the selected marker.
         */
        selectAgencyMarker(
            marker
        );


        /*
         * Open the agency details panel.
         */
        openAgencyDetails(
            marker.agencyData
        );


        /*
         * Wait one browser frame so the panel has started
         * opening before calculating the final composition.
         */
        requestAnimationFrame(
            () => {

                /*
                 * Target zoom level.
                 */
                const targetZoom =
                    17;


                /*
                 * Get the current map dimensions.
                 */
                const mapSize =
                    map.getSize();


                /*
                 * Determine where the selected marker
                 * should appear in the final composition.
                 */
                let targetPoint;


                // -------------------------------------------------
                // MOBILE
                // -------------------------------------------------

                if (
                    window.innerWidth <= 900
                ) {

                    /*
                     * Keep the marker around 220px from
                     * the top of the visible map.
                     */
                    targetPoint =
                        L.point(
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
                     * Place the selected agency slightly
                     * to the RIGHT of map center.
                     */
                    targetPoint =
                        L.point(
                            mapSize.x / 2 + 180,
                            mapSize.y / 2
                        );

                }


                /*
                 * Project the selected agency using
                 * the target zoom level.
                 */
                const markerProjected =
                    map.project(
                        latlng,
                        targetZoom
                    );


                /*
                 * Determine the center of the map screen.
                 */
                const screenCenter =
                    L.point(
                        mapSize.x / 2,
                        mapSize.y / 2
                    );


                /*
                 * Calculate the projected location required
                 * for the map center.
                 */
                const targetCenterProjected =
                    markerProjected
                        .subtract(
                            targetPoint
                        )
                        .add(
                            screenCenter
                        );


                /*
                 * Convert the projected point back into
                 * latitude/longitude at target zoom.
                 */
                const targetCenter =
                    map.unproject(
                        targetCenterProjected,
                        targetZoom
                    );


                /*
                 * Smoothly pan and zoom toward the
                 * selected agency.
                 *
                 * This animation only happens because
                 * the user selected an agency.
                 */
                map.flyTo(
                    targetCenter,
                    targetZoom,
                    {
                        animate:
                            true,

                        /*
                         * Short and responsive animation.
                         */
                        duration:
                            0.55,

                        /*
                         * Controlled easing.
                         */
                        easeLinearity:
                            0.25

                    }
                );

            }
        );

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
             * Don't close results when interacting
             * with the search interface.
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
                !agencyDetails.classList.contains(
                    'active'
                )
            ) {

                return;

            }


            /*
             * Keep the panel open when clicking
             * anywhere inside the panel.
             */
            if (
                event.target.closest(
                    '#agencyDetails'
                )
            ) {

                return;

            }


            /*
             * Keep the panel open when clicking
             * an agency marker.
             */
            if (
                event.target.closest(
                    '.leaflet-marker-icon'
                )
            ) {

                return;

            }


            /*
             * Keep the panel open when clicking
             * a search result.
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
             * Otherwise close the agency details panel.
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
         * Stop when the results container doesn't exist.
         */
        if (!resultsContainer) {
            return;
        }


        /*
         * Clear old results.
         */
        resultsContainer.innerHTML =
            '';


        /*
         * Create one result for every match.
         */
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
                 * Build the search result interface.
                 *
                 * Agency names are escaped before being
                 * inserted into HTML.
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


                /*
                 * Select the agency when its search
                 * result is clicked.
                 */
                item.addEventListener(
                    'click',
                    event => {

                        /*
                         * Prevent the document-level
                         * outside-click handler from
                         * immediately closing the panel.
                         */
                        event.stopPropagation();


                        /*
                         * Record intentional search selection.
                         */
                        logUserActivity(
                            'search_agency',
                            marker.agencyData.id
                        );


                        /*
                         * Reuse the normal agency-selection workflow.
                         */
                        focusMarker(
                            marker
                        );


                        /*
                         * Remove suggestions after selection.
                         */
                        resultsContainer.innerHTML =
                            '';

                    }
                );


                /*
                 * Add the result to the interface.
                 */
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
                    'Enter'
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
     * This is important because agency names originate
     * from database data.
     */
    function escapeHtml(
        text
    ) {

        /*
         * Create a temporary DOM element.
         */
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
         * Reading innerHTML now returns the safely
         * escaped representation.
         */
        return div.innerHTML;

    }

});