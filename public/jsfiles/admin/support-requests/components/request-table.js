/*
|--------------------------------------------------------------------------
| KNOWURLOCAL — Support Requests Table
|--------------------------------------------------------------------------
|
| This module owns behavior that belongs directly to the Support
| Requests table.
|
| Responsibilities:
|
| - Action menu behavior
| - Lifecycle confirmation dialogs
| - Dynamically created lifecycle forms
| - Realtime row construction helpers
| - Realtime filter matching
|
| Responsibilities intentionally kept elsewhere:
|
| - Manage modal          → manage-modal.js
| - Response builder      → response-builder.js
| - Similar FAQ checking  → similar-faq.js
| - Echo connection       → realtime.js
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| MODULE STATE
|--------------------------------------------------------------------------
|
| These references are initialized once the module starts.
|
| Keeping DOM references inside the module prevents other features
| from depending on the table's internal implementation.
|--------------------------------------------------------------------------
*/

let supportRequestsTableBody = null;

let csrfToken = "";


/*
|--------------------------------------------------------------------------
| INITIALIZATION GUARD
|--------------------------------------------------------------------------
|
| ES modules are normally loaded once, but keeping an explicit guard
| makes the module safer if initialization is accidentally called twice.
|--------------------------------------------------------------------------
*/

let isInitialized = false;


/*
|--------------------------------------------------------------------------
| SAFE VALUE HELPERS
|--------------------------------------------------------------------------
*/

/**
 * Safely convert an unknown value to a string.
 *
 * Realtime data comes from the server and should never be assumed
 * to already have the expected JavaScript type.
 */
function safeString(value, fallback = "") {
    if (
        value === null ||
        value === undefined
    ) {
        return fallback;
    }

    return String(value);
}


/**
 * Read the filters currently represented by the server-rendered table.
 *
 * The Blade table stores these values in data-* attributes so the
 * JavaScript does not need to duplicate server-side filter logic.
 */
function getCurrentTableFilters() {
    if (!supportRequestsTableBody) {
        return {
            datasetStatus: "",
            statusFilter: "",
            agency: "",
            search: "",
        };
    }

    return {
        datasetStatus:
            supportRequestsTableBody.dataset.status || "",

        statusFilter:
            supportRequestsTableBody.dataset.statusFilter || "",

        agency:
            supportRequestsTableBody.dataset.agency || "",

        search:
            (
                supportRequestsTableBody.dataset.search ||
                ""
            )
                .trim()
                .toLowerCase(),
    };
}


/*
|--------------------------------------------------------------------------
| ACTION MENUS
|--------------------------------------------------------------------------
|
| Each table row can have a secondary action menu.
|
| Only one menu should remain open at a time.
|--------------------------------------------------------------------------
*/


/**
 * Close every action menu except an optional menu.
 *
 * @param {HTMLElement|null} exceptMenu
 */
function closeAllActionMenus(
    exceptMenu = null
) {
    document
        .querySelectorAll(
            ".support-action-menu.is-open"
        )
        .forEach(menu => {
            if (menu === exceptMenu) {
                return;
            }

            menu.classList.remove(
                "is-open"
            );


            const trigger =
                menu.querySelector(
                    ".support-action-menu-trigger"
                );

            trigger?.setAttribute(
                "aria-expanded",
                "false"
            );


            /*
             * Remove viewport coordinates so that the
             * next opening calculates a fresh position.
             */
            const menuContent =
                menu.querySelector(
                    ".support-action-menu-content"
                );

            if (menuContent) {
                menuContent.style.top = "";
                menuContent.style.left = "";
            }
        });
}


/**
 * Toggle one action menu.
 *
 * The menu is positioned relative to the viewport rather than
 * the table container. This prevents the table's overflow rules
 * from clipping the menu when the row is near the bottom.
 *
 * @param {HTMLElement} trigger
 */
function toggleActionMenu(trigger) {
    const menu =
        trigger.closest(
            ".support-action-menu"
        );

    if (!menu) {
        return;
    }

    const menuContent =
        menu.querySelector(
            ".support-action-menu-content"
        );

    if (!menuContent) {
        return;
    }

    const shouldOpen =
        !menu.classList.contains(
            "is-open"
        );


    /*
     * Close every other open action menu first.
     */
    closeAllActionMenus(menu);


    /*
     * If the menu is currently open, close it and
     * remove any previously calculated viewport position.
     */
    if (!shouldOpen) {
        menu.classList.remove(
            "is-open"
        );

        trigger.setAttribute(
            "aria-expanded",
            "false"
        );

        menuContent.style.top = "";
        menuContent.style.left = "";

        return;
    }


    /*
     * Open the menu first so the browser can calculate
     * its actual dimensions.
     */
    menu.classList.add(
        "is-open"
    );

    trigger.setAttribute(
        "aria-expanded",
        "true"
    );


    /*
     * Read the trigger's position relative to the viewport.
     *
     * getBoundingClientRect() is appropriate here because
     * the menu itself will use position: fixed.
     */
    const triggerRect =
        trigger.getBoundingClientRect();


    /*
     * Read the rendered menu dimensions.
     */
    const menuRect =
        menuContent.getBoundingClientRect();


    /*
     * Small viewport safety margin.
     *
     * This prevents the menu from touching the very edge
     * of the browser window.
     */
    const viewportPadding = 8;


    /*
     * Calculate the horizontal position.
     *
     * The right edge of the menu aligns with the right edge
     * of the three-dot trigger.
     */
    let left =
        triggerRect.right -
        menuRect.width;


    /*
     * Prevent the menu from going outside the left edge
     * of the viewport.
     */
    left = Math.max(
        viewportPadding,
        left
    );


    /*
     * Prevent the menu from going outside the right edge
     * of the viewport.
     */
    left = Math.min(
        left,
        window.innerWidth -
            menuRect.width -
            viewportPadding
    );


    /*
     * Normally the menu opens below the trigger.
     */
    const spaceBelow =
        window.innerHeight -
        triggerRect.bottom -
        viewportPadding;


    /*
     * Calculate the position above the trigger as well.
     */
    const spaceAbove =
        triggerRect.top -
        viewportPadding;


    let top;


    /*
     * If there isn't enough room below but there is
     * enough room above, open upward.
     */
    if (
        spaceBelow < menuRect.height &&
        spaceAbove >= menuRect.height
    ) {
        top =
            triggerRect.top -
            menuRect.height -
            6;
    } else {
        /*
         * Otherwise open below the trigger.
         */
        top =
            triggerRect.bottom +
            6;
    }


    /*
     * Final vertical safety clamp.
     *
     * This handles very small browser windows where
     * neither side has enough space.
     */
    top = Math.max(
        viewportPadding,
        Math.min(
            top,
            window.innerHeight -
                menuRect.height -
                viewportPadding
        )
    );


    /*
     * Apply the calculated viewport coordinates.
     */
    menuContent.style.left =
        `${Math.round(left)}px`;

    menuContent.style.top =
        `${Math.round(top)}px`;
}


/*
|--------------------------------------------------------------------------
| LIFECYCLE FORMS
|--------------------------------------------------------------------------
|
| Realtime rows do not come from Blade.
|
| Therefore, their lifecycle forms must be constructed safely in
| JavaScript instead of injecting raw HTML.
|--------------------------------------------------------------------------
*/


/**
 * Create a hidden form input.
 *
 * Using createElement() and text/value properties prevents values
 * from becoming executable HTML.
 *
 * @param {string} name
 * @param {string|number} value
 * @returns {HTMLInputElement}
 */
function createHiddenInput(
    name,
    value
) {
    const input =
        document.createElement(
            "input"
        );

    input.type =
        "hidden";

    input.name =
        name;

    input.value =
        String(value ?? "");

    return input;
}


/**
 * Create a lifecycle form for a dynamically generated row.
 *
 * Laravel uses POST + method spoofing for DELETE requests.
 *
 * @param {string} action
 * @param {string} method
 * @param {string} buttonClass
 * @param {string} iconClass
 * @param {string} label
 * @returns {HTMLFormElement}
 */
function createLifecycleForm(
    action,
    method,
    buttonClass,
    iconClass,
    label
) {
    const form =
        document.createElement(
            "form"
        );

    form.method =
        "POST";

    form.action =
        action;

    form.className =
        "support-lifecycle-form";


    /*
     * Laravel CSRF protection.
     *
     * The token was read from the page's meta tag during
     * module initialization.
     */
    form.appendChild(
        createHiddenInput(
            "_token",
            csrfToken
        )
    );


    /*
     * Laravel method spoofing allows a normal POST form
     * to represent DELETE and other HTTP verbs.
     */
    form.appendChild(
        createHiddenInput(
            "_method",
            method
        )
    );


    const button =
        document.createElement(
            "button"
        );

    button.type =
        "submit";

    button.className =
        buttonClass;

    button.setAttribute(
        "role",
        "menuitem"
    );


    const icon =
        document.createElement(
            "i"
        );

    icon.className =
        iconClass;

    icon.setAttribute(
        "aria-hidden",
        "true"
    );


    const text =
        document.createElement(
            "span"
        );

    text.textContent =
        label;


    button.appendChild(
        icon
    );

    button.appendChild(
        text
    );

    form.appendChild(
        button
    );

    return form;
}


/*
|--------------------------------------------------------------------------
| LIFECYCLE CONFIRMATIONS
|--------------------------------------------------------------------------
|
| These actions modify or delete support-request records.
|
| Confirmation is therefore handled before the form is submitted.
|--------------------------------------------------------------------------
*/


/**
 * Display the appropriate confirmation dialog for a lifecycle action.
 *
 * @param {HTMLElement} button
 */
function confirmLifecycleAction(button) {
    const form =
        button.closest("form");

    if (!form) {
        return;
    }

    let config = null;


    /*
     * Move request to trash.
     */
    if (
        button.classList.contains(
            "delete-btn"
        )
    ) {
        config = {
            title:
                "Move Support Request to Trash",

            text:
                "Are you sure you want to move this support request to trash? You can restore it later.",

            icon:
                "ph-light ph-trash",

            variant:
                "danger",

            confirmText:
                "Move to Trash",
        };
    }


    /*
     * Restore request.
     */
    if (
        button.classList.contains(
            "restore-btn"
        )
    ) {
        config = {
            title:
                "Restore Support Request",

            text:
                "Are you sure you want to restore this support request?",

            icon:
                "ph-light ph-arrow-counter-clockwise",

            variant:
                "success",

            confirmText:
                "Restore",
        };
    }


    /*
     * Permanently delete request.
     */
    if (
        button.classList.contains(
            "permanent-delete-btn"
        )
    ) {
        config = {
            title:
                "Delete Support Request Permanently",

            text:
                "This action permanently deletes the support request and cannot be undone.",

            icon:
                "ph-light ph-trash-simple",

            variant:
                "danger",

            confirmText:
                "Delete Permanently",
        };
    }


    /*
     * If this isn't a lifecycle action, do nothing.
     */
    if (!config) {
        return;
    }


    /*
     * Fall back to native form submission if the shared
     * alert component is unavailable.
     *
     * This keeps the destructive operation functional without
     * introducing another custom modal system.
     */
    if (typeof window.showAlertModal !== "function") {
        console.error(
            "Support request confirmation modal is unavailable."
        );

        return;
    }


    window.showAlertModal({
        title:
            config.title,

        text:
            config.text,

        icon:
            config.icon,

        variant:
            config.variant,

        confirmText:
            config.confirmText,

        showCancel:
            true,

        onConfirm: () => {
            form.submit();
        },
    });
}


/*
|--------------------------------------------------------------------------
| REALTIME ROW DATA HELPERS
|--------------------------------------------------------------------------
|
| These functions are exported because realtime.js needs them when
| Laravel Echo reports a newly created support request.
|--------------------------------------------------------------------------
*/


/**
 * Create a complete table row from a support-request payload.
 *
 * All user/database values are inserted using textContent or
 * safe DOM properties instead of innerHTML.
 *
 * This is important because realtime payloads should be treated
 * as untrusted input at the browser boundary.
 *
 * @param {Object} request
 * @returns {HTMLTableRowElement}
 */
function createRealtimeSupportRequestRow(
    request
) {
    const row =
        document.createElement(
            "tr"
        );

    row.className =
        "support-request-row";


    /*
     * Normalize event data.
     */
    const requestId =
        safeString(
            request.id
        );

    const question =
        safeString(
            request.question
        );

    const status =
        safeString(
            request.status,
            "pending"
        );

    const agencyName =
        safeString(
            request.agency_name
        );

    const agencyId =
        safeString(
            request.agency_id ??
            request.agency?.id
        );


    /*
     * User information.
     *
     * The current system uses first_name rather than inventing
     * a separate profile/avatar structure.
     */
    const userName =
        request.user?.first_name ||
        request.first_name ||
        request.user_name ||
        "Guest";


    row.dataset.requestId =
        requestId;


    /*
    |--------------------------------------------------------------------------
    | ID
    |--------------------------------------------------------------------------
    */

    const idCell =
        document.createElement(
            "td"
        );

    idCell.className =
        "support-request-id";

    const idSpan =
        document.createElement(
            "span"
        );

    idSpan.textContent =
        `#${requestId}`;

    idCell.appendChild(
        idSpan
    );

    row.appendChild(
        idCell
    );


    /*
    |--------------------------------------------------------------------------
    | USER
    |--------------------------------------------------------------------------
    */

    const userCell =
        document.createElement(
            "td"
        );

    userCell.className =
        "support-request-user";

    const userWrapper =
        document.createElement(
            "div"
        );

    userWrapper.className =
        "support-user-cell";

    const userDetails =
        document.createElement(
            "div"
        );

    userDetails.className =
        "support-user-details";

    const userNameElement =
        document.createElement(
            "span"
        );

    userNameElement.className =
        "support-user-name";

    userNameElement.textContent =
        userName;

    userDetails.appendChild(
        userNameElement
    );

    userWrapper.appendChild(
        userDetails
    );

    userCell.appendChild(
        userWrapper
    );

    row.appendChild(
        userCell
    );


    /*
    |--------------------------------------------------------------------------
    | QUESTION
    |--------------------------------------------------------------------------
    */

    const questionCell =
        document.createElement(
            "td"
        );

    questionCell.className =
        "support-request-question";

    const questionWrapper =
        document.createElement(
            "div"
        );

    questionWrapper.className =
        "support-question-cell";

    const questionElement =
        document.createElement(
            "span"
        );

    questionElement.className =
        "support-question-text";

    questionElement.title =
        question;

    questionElement.textContent =
        question.length > 90
            ? `${question.substring(0, 90)}...`
            : question;

    questionWrapper.appendChild(
        questionElement
    );

    questionCell.appendChild(
        questionWrapper
    );

    row.appendChild(
        questionCell
    );


    /*
    |--------------------------------------------------------------------------
    | AGENCY
    |--------------------------------------------------------------------------
    */

    const agencyCell =
        document.createElement(
            "td"
        );

    agencyCell.className =
        "support-request-agency";

    const agencyElement =
        document.createElement(
            "span"
        );

    agencyElement.className =
        agencyName
            ? "support-agency-name"
            : "support-agency-empty";

    agencyElement.textContent =
        agencyName ||
        "Unassigned";

    agencyCell.appendChild(
        agencyElement
    );

    row.appendChild(
        agencyCell
    );


    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    const statusCell =
        document.createElement(
            "td"
        );

    statusCell.className =
        "support-request-status";

    const statusBadge =
        document.createElement(
            "span"
        );

    statusBadge.className =
        `support-status-badge ${status}`;

    const statusDot =
        document.createElement(
            "span"
        );

    statusDot.className =
        "support-status-dot";

    statusDot.setAttribute(
        "aria-hidden",
        "true"
    );

    const statusText =
        document.createTextNode(
            status.charAt(0).toUpperCase() +
            status.slice(1)
        );

    statusBadge.appendChild(
        statusDot
    );

    statusBadge.appendChild(
        statusText
    );

    statusCell.appendChild(
        statusBadge
    );

    row.appendChild(
        statusCell
    );


    /*
    |--------------------------------------------------------------------------
    | DATE
    |--------------------------------------------------------------------------
    */

    const dateCell =
        document.createElement(
            "td"
        );

    dateCell.className =
        "support-request-date";

    const dateElement =
        document.createElement(
            "time"
        );

    const createdAt =
        request.created_at
            ? new Date(
                request.created_at
            )
            : new Date();

    if (
        !Number.isNaN(
            createdAt.getTime()
        )
    ) {
        dateElement.dateTime =
            createdAt.toISOString();

        dateElement.textContent =
            createdAt.toLocaleDateString(
                "en-US",
                {
                    month:
                        "short",

                    day:
                        "2-digit",

                    year:
                        "numeric",
                }
            );
    } else {
        dateElement.textContent =
            "—";
    }

    dateCell.appendChild(
        dateElement
    );

    row.appendChild(
        dateCell
    );


    /*
    |--------------------------------------------------------------------------
    | ACTIONS
    |--------------------------------------------------------------------------
    */

    const actionCell =
        document.createElement(
            "td"
        );

    actionCell.className =
        "support-request-actions";

    const actionWrapper =
        document.createElement(
            "div"
        );

    actionWrapper.className =
        "support-row-actions";


    /*
     * Primary Manage button.
     *
     * manage-modal.js listens for .view-btn.
     */
    const manageButton =
        document.createElement(
            "button"
        );

    manageButton.type =
        "button";

    manageButton.className =
        "support-action-primary view-btn";

    manageButton.dataset.id =
        requestId;

    manageButton.dataset.user =
        userName;

    manageButton.dataset.question =
        question;

    manageButton.dataset.agency =
        agencyName;

    manageButton.dataset.agencyId =
        agencyId;


    /*
     * Legacy values remain empty because the new response
     * workflow does not use answer/answer-image data.
     */
    manageButton.dataset.answer =
        "";

    manageButton.dataset.answerImage =
        "";

    manageButton.setAttribute(
        "aria-label",
        `Manage support request #${requestId}`
    );


    const manageIcon =
        document.createElement(
            "i"
        );

    manageIcon.className =
        "ph-light ph-chat-centered-text";

    manageIcon.setAttribute(
        "aria-hidden",
        "true"
    );


    const manageText =
        document.createElement(
            "span"
        );

    manageText.textContent =
        "Manage";


    manageButton.appendChild(
        manageIcon
    );

    manageButton.appendChild(
        manageText
    );

    actionWrapper.appendChild(
        manageButton
    );


    /*
    |--------------------------------------------------------------------------
    | SUPERADMIN ACTION MENU
    |--------------------------------------------------------------------------
    */

    const isSuperadmin =
        supportRequestsTableBody?.dataset
            .isSuperadmin === "true";


    if (isSuperadmin) {
        const menu =
            document.createElement(
                "div"
            );

        menu.className =
            "support-action-menu";


        const trigger =
            document.createElement(
                "button"
            );

        trigger.type =
            "button";

        trigger.className =
            "support-action-menu-trigger";

        trigger.setAttribute(
            "aria-label",
            `More actions for support request #${requestId}`
        );

        trigger.setAttribute(
            "aria-expanded",
            "false"
        );

        trigger.setAttribute(
            "aria-haspopup",
            "menu"
        );


        const triggerIcon =
            document.createElement(
                "i"
            );

        triggerIcon.className =
            "ph-light ph-dots-three-vertical";

        triggerIcon.setAttribute(
            "aria-hidden",
            "true"
        );

        trigger.appendChild(
            triggerIcon
        );


        const menuContent =
            document.createElement(
                "div"
            );

        menuContent.className =
            "support-action-menu-content";

        menuContent.setAttribute(
            "role",
            "menu"
        );


        /*
        |--------------------------------------------------------------------------
        | MOVE TO TRASH
        |--------------------------------------------------------------------------
        */

        const deleteBase =
            supportRequestsTableBody?.dataset
                .deleteUrl || "";

        const deleteForm =
            createLifecycleForm(
                `${deleteBase}/${encodeURIComponent(requestId)}`,
                "DELETE",
                "support-menu-action support-menu-danger delete-btn",
                "ph-light ph-trash",
                "Move to trash"
            );

        menuContent.appendChild(
            deleteForm
        );


        /*
        |--------------------------------------------------------------------------
        | ADD TO FAQ
        |--------------------------------------------------------------------------
        |
        | The actual similarity check is owned by similar-faq.js.
        |
        | This module only constructs the navigational element.
        |--------------------------------------------------------------------------
        */

        const faqBase =
            supportRequestsTableBody?.dataset
                .faqUrl || "";

        const similarFaqBase =
            supportRequestsTableBody?.dataset
                .similarFaqUrl || "";


        const faqLink =
            document.createElement(
                "a"
            );

        faqLink.href =
            `${faqBase}/${encodeURIComponent(requestId)}/to-faq`;

        faqLink.className =
            "support-menu-action support-menu-faq faq-btn";

        faqLink.dataset.id =
            requestId;

        faqLink.dataset.similarUrl =
            `${similarFaqBase}/${encodeURIComponent(requestId)}/similar-faqs`;

        faqLink.setAttribute(
            "role",
            "menuitem"
        );


        const faqIcon =
            document.createElement(
                "i"
            );

        faqIcon.className =
            "ph-light ph-chat-centered-dots";

        faqIcon.setAttribute(
            "aria-hidden",
            "true"
        );


        const faqText =
            document.createElement(
                "span"
            );

        faqText.textContent =
            "Add to FAQ";


        faqLink.appendChild(
            faqIcon
        );

        faqLink.appendChild(
            faqText
        );

        menuContent.appendChild(
            faqLink
        );


        menu.appendChild(
            trigger
        );

        menu.appendChild(
            menuContent
        );

        actionWrapper.appendChild(
            menu
        );
    }


    actionCell.appendChild(
        actionWrapper
    );

    row.appendChild(
        actionCell
    );


    return row;
}


/*
|--------------------------------------------------------------------------
| REALTIME FILTER MATCHING
|--------------------------------------------------------------------------
|
| A newly created request should only be inserted when it belongs
| to the currently displayed dataset and filters.
|--------------------------------------------------------------------------
*/


/**
 * Determine whether a realtime request belongs to the current table view.
 *
 * @param {Object} request
 * @returns {boolean}
 */
function realtimeRequestMatchesCurrentView(
    request
) {
    const filters =
        getCurrentTableFilters();


    /*
     * Realtime creation belongs only to the active dataset.
     */
    if (
        filters.datasetStatus !==
        "active"
    ) {
        return false;
    }


    /*
     * Normalize event status.
     */
    const requestStatus =
        safeString(
            request.status,
            "pending"
        );


    /*
     * Respect status filtering.
     */
    if (
        filters.statusFilter &&
        requestStatus !==
            filters.statusFilter
    ) {
        return false;
    }


    /*
     * Respect agency filtering.
     */
    if (filters.agency) {
        const requestAgency =
            safeString(
                request.agency_id ??
                request.agency?.id
            );

        if (
            requestAgency !==
            filters.agency
        ) {
            return false;
        }
    }


    /*
     * Respect question search.
     */
    if (filters.search) {
        const question =
            safeString(
                request.question
            )
                .toLowerCase();

        if (
            !question.includes(
                filters.search
            )
        ) {
            return false;
        }
    }


    return true;
}


/*
|--------------------------------------------------------------------------
| MODULE EVENT HANDLERS
|--------------------------------------------------------------------------
|
| Event delegation is used for table actions.
|
| This is important because realtime.js can insert rows after the
| page has already loaded.
|--------------------------------------------------------------------------
*/


function handleDocumentClick(event) {
    /*
     * Action menu trigger.
     */
    const menuTrigger =
        event.target.closest(
            ".support-action-menu-trigger"
        );

    if (menuTrigger) {
        event.preventDefault();

        toggleActionMenu(
            menuTrigger
        );

        return;
    }


    /*
     * Lifecycle actions.
     *
     * Similar FAQ and Manage actions are intentionally handled
     * by their own modules.
     */
    const lifecycleButton =
        event.target.closest(
            ".delete-btn, .restore-btn, .permanent-delete-btn"
        );

    if (lifecycleButton) {
        event.preventDefault();

        confirmLifecycleAction(
            lifecycleButton
        );

        return;
    }


    /*
     * Clicking outside an action menu closes all menus.
     */
    closeAllActionMenus();
}


/**
 * Initialize the Support Requests table module.
 *
 * @returns {void}
 */
function initializeRequestTable() {
    if (isInitialized) {
        return;
    }

    isInitialized = true;


    supportRequestsTableBody =
        document.getElementById(
            "support-requests-table-body"
        );


    csrfToken =
        document
            .querySelector(
                'meta[name="csrf-token"]'
            )
            ?.getAttribute(
                "content"
            ) || "";


    /*
     * The table is not necessarily present when this module
     * is reused elsewhere.
     *
     * If it isn't present, don't register table-specific behavior.
     */
    if (!supportRequestsTableBody) {
        return;
    }


    document.addEventListener(
        "click",
        handleDocumentClick
    );
}


/*
|--------------------------------------------------------------------------
| PUBLIC MODULE API
|--------------------------------------------------------------------------
|
| realtime.js needs the row builder and filter matcher.
|
| Keeping these functions exported is cleaner than exposing the
| entire module through window.SupportRequestTable.
|--------------------------------------------------------------------------
*/

export {
    createRealtimeSupportRequestRow,
    realtimeRequestMatchesCurrentView,
    closeAllActionMenus,
    initializeRequestTable,
};