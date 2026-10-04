/*
|--------------------------------------------------------------------------
| KNOWURLOCAL — Support Requests Realtime
|--------------------------------------------------------------------------
|
| This module owns the live-update connection for the Support Requests
| page.
|
| Responsibilities:
|
| - Wait for Laravel Echo to become available.
| - Subscribe to the admin support-request private channel.
| - Listen for newly-created support requests.
| - Respect the table's current filters.
| - Prevent duplicate rows.
| - Insert new requests into the table.
| - Apply the temporary "new row" animation.
|
| This module does NOT:
|
| - Build table rows.
| - Handle table filters.
| - Handle Manage modal interactions.
| - Handle response components.
| - Handle Similar FAQ interactions.
|
| Those responsibilities belong to their respective modules.
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| TABLE MODULE DEPENDENCIES
|--------------------------------------------------------------------------
|
| realtime.js needs the table module to:
|
| 1. Build a safe table row from the broadcast payload.
| 2. Determine whether the request belongs to the current table view.
|
| The table module does not import realtime.js.
| This keeps the dependency direction one-way and avoids circular imports.
|--------------------------------------------------------------------------
*/

import {
    createRealtimeSupportRequestRow,
    updateRealtimeSupportRequestRow,
    realtimeRequestMatchesCurrentView,
} from './request-table.js?v=202610042300';


/*
|--------------------------------------------------------------------------
| DOM REFERENCES
|--------------------------------------------------------------------------
|
| This module only needs access to the table body.
|
| The actual row construction remains inside request-table.js.
|--------------------------------------------------------------------------
*/

const tableBody = document.getElementById(
    'support-requests-table-body'
);


/*
|--------------------------------------------------------------------------
| REALTIME STATE
|--------------------------------------------------------------------------
|
| realtimeInitialized prevents multiple Echo subscriptions.
|
| realtimeRetryTimer stores the retry interval so it can be cleared once
| Echo becomes available or the retry limit is reached.
|--------------------------------------------------------------------------
*/

let realtimeInitialized = false;

let realtimeRetryTimer = null;

let authoritativeRefreshTimer = null;

let authoritativeRefreshInProgress = false;


/*
|--------------------------------------------------------------------------
| AUTHORITATIVE TABLE REFRESH
|--------------------------------------------------------------------------
|
| Realtime payloads are intentionally small. The server-rendered table is
| still authoritative for filters, pagination, status labels, counts, and
| any future row changes. After a creation event we therefore reconcile the
| visible table with the current URL instead of relying only on a client-
| constructed row.
|--------------------------------------------------------------------------
*/

async function refreshCurrentTableView() {
    if (!tableBody || authoritativeRefreshInProgress) {
        return;
    }

    authoritativeRefreshInProgress = true;

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('_realtime', String(Date.now()));

        const response = await fetch(url.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        });

        if (!response.ok) {
            throw new Error(
                `Support Requests refresh failed with status ${response.status}.`
            );
        }

        const html = await response.text();
        const documentParser = new DOMParser();
        const parsed = documentParser.parseFromString(
            html,
            'text/html'
        );

        const freshBody =
            parsed.getElementById(
                'support-requests-table-body'
            );

        if (!freshBody) {
            throw new Error(
                'Support Requests refresh did not return the table body.'
            );
        }

        tableBody.innerHTML = freshBody.innerHTML;

        [
            'data-status',
            'data-status-filter',
            'data-agency',
            'data-search',
        ].forEach((attribute) => {
            const value = freshBody.getAttribute(attribute);

            if (value === null) {
                tableBody.removeAttribute(attribute);
            } else {
                tableBody.setAttribute(attribute, value);
            }
        });

        const currentResultMeta =
            document.querySelector(
                '.admin-list-result-meta[role="status"]'
            );

        const freshResultMeta =
            parsed.querySelector(
                '.admin-list-result-meta[role="status"]'
            );

        if (currentResultMeta && freshResultMeta) {
            currentResultMeta.innerHTML =
                freshResultMeta.innerHTML;
        }

        const currentTabs =
            document.querySelector(
                '.support-dataset-tabs'
            );

        const freshTabs =
            parsed.querySelector(
                '.support-dataset-tabs'
            );

        if (currentTabs && freshTabs) {
            currentTabs.innerHTML =
                freshTabs.innerHTML;
        }

        const currentPagination =
            document.querySelector(
                '.support-pagination'
            );

        const freshPagination =
            parsed.querySelector(
                '.support-pagination'
            );

        if (currentPagination && freshPagination) {
            currentPagination.innerHTML =
                freshPagination.innerHTML;
        } else if (!currentPagination && freshPagination) {
            const section =
                document.querySelector(
                    '.support-request-section'
                );

            section?.appendChild(
                freshPagination.cloneNode(true)
            );
        } else if (currentPagination && !freshPagination) {
            currentPagination.remove();
        }
    } catch (error) {
        console.warn(
            'KNOWURLOCAL: Unable to reconcile the Support Requests table after a realtime event.',
            error
        );
    } finally {
        authoritativeRefreshInProgress = false;
    }
}

function scheduleAuthoritativeTableRefresh() {
    if (authoritativeRefreshTimer !== null) {
        window.clearTimeout(
            authoritativeRefreshTimer
        );
    }

    authoritativeRefreshTimer =
        window.setTimeout(
            () => {
                authoritativeRefreshTimer = null;
                refreshCurrentTableView();
            },
            150
        );
}


/*
|--------------------------------------------------------------------------
| HANDLE NEW SUPPORT REQUEST
|--------------------------------------------------------------------------
|
| This function receives the request payload from Laravel Echo.
|
| Realtime event data must be treated as untrusted input.
| We therefore pass the payload to request-table.js, which constructs
| the DOM using safe DOM APIs.
|--------------------------------------------------------------------------
*/

function updateLiveActiveCount(delta) {
    if (!Number.isFinite(delta) || delta === 0) {
        return;
    }

    const countElement = document.querySelector(
        '.support-dataset-tabs .support-dataset-tab:first-child .support-dataset-count'
    );

    if (countElement) {
        const current = Number.parseInt(
            countElement.textContent.replace(/[^0-9]/g, ''),
            10
        );

        if (Number.isFinite(current)) {
            countElement.textContent = String(
                Math.max(0, current + delta)
            );
        }
    }

    const resultMeta = document.querySelector(
        '.admin-list-result-meta[role="status"] span'
    );

    if (resultMeta) {
        const match = resultMeta.textContent.match(/([0-9,]+)/);

        if (match) {
            const current = Number.parseInt(
                match[1].replace(/,/g, ''),
                10
            );

            if (Number.isFinite(current)) {
                const next = Math.max(0, current + delta);
                const label = next === 1 ? 'request' : 'requests';

                resultMeta.textContent =
                    `${next.toLocaleString()} ${label}`;
            }
        }
    }
}


/**
 * Process a newly-created support request.
 *
 * This is shared by the direct Echo listener and the notification
 * module's in-page relay. The latter is an intentional fallback:
 * both modules subscribe to the same admin channel.
 */
function handleRealtimeSupportRequest(request) {

    /*
    |--------------------------------------------------------------------------
    | BASIC INPUT GUARD
    |--------------------------------------------------------------------------
    |
    | If Echo provides an invalid payload or the table does not exist,
    | there is nothing for this module to do.
    |--------------------------------------------------------------------------
    */

    if (
        !request ||
        !tableBody
    ) {
        return;
    }

    if (document.querySelector('meta[name="realtime-debug"]')?.content === 'true') {
        console.info('[KnowUrLocal realtime] Received support.request.created.', {
            supportRequestId: request.id ?? null,
        });
    }


    /*
    |--------------------------------------------------------------------------
    | RESPECT CURRENT TABLE FILTERS
    |--------------------------------------------------------------------------
    |
    | Do not insert a realtime request if it does not belong to the
    | currently displayed dataset/filter.
    |--------------------------------------------------------------------------
    */

    if (
        !realtimeRequestMatchesCurrentView(
            request
        )
    ) {
        scheduleAuthoritativeTableRefresh();
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE ROWS
    |--------------------------------------------------------------------------
    |
    | A request should only appear once in the table.
    |
    | CSS.escape() ensures that the request ID is safely represented
    | inside the CSS attribute selector.
    |--------------------------------------------------------------------------
    */

    if (
        request.id !== undefined &&
        request.id !== null
    ) {

        const existingRow =
            document.querySelector(
                `[data-request-id="${CSS.escape(String(request.id))}"]`
            );


        if (existingRow) {
            return;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REMOVE EMPTY STATE
    |--------------------------------------------------------------------------
    |
    | If the table previously had no matching requests, Blade may have
    | rendered the empty-state row.
    |
    | A newly-created request makes that empty state obsolete.
    |--------------------------------------------------------------------------
    */

    const emptyRow =
        tableBody.querySelector(
            '.support-empty-row'
        );


    if (emptyRow) {
        emptyRow.remove();
    }


    /*
    |--------------------------------------------------------------------------
    | BUILD NEW TABLE ROW
    |--------------------------------------------------------------------------
    |
    | request-table.js owns the actual DOM construction.
    |
    | Keeping this responsibility there prevents realtime.js from
    | becoming another large UI module.
    |--------------------------------------------------------------------------
    */

    const row =
        createRealtimeSupportRequestRow(
            request
        );


    if (!row) {
        return;
    }


    /*
|--------------------------------------------------------------------------
| INSERT NEW REQUEST
|--------------------------------------------------------------------------
|
| The table can be sorted in either direction:
|
| - newest → newest request goes to the beginning
| - oldest → newest request goes to the end
|
| The current sort is read from the URL so realtime.js
| follows the same state used by the server-side controller.
|--------------------------------------------------------------------------
*/

const currentSort =
    new URLSearchParams(window.location.search).get('sort')
    || 'newest';

if (currentSort === 'oldest') {
    tableBody.append(row);
} else {
    tableBody.prepend(row);
}

    updateLiveActiveCount(1);


    /*
    |--------------------------------------------------------------------------
    | TEMPORARY NEW-ROW STATE
    |--------------------------------------------------------------------------
    |
    | CSS is responsible for the actual animation.
    |
    | JavaScript only controls how long the state class exists.
    |--------------------------------------------------------------------------
    */

    row.classList.add(
        'realtime-new-row'
    );


    window.setTimeout(
        () => {

            row.classList.remove(
                'realtime-new-row'
            );

        },
        2500
    );

    /*
     * Reconcile against the authoritative server-rendered view.
     * This also handles pagination, active filters, and any mismatch
     * between a compact broadcast payload and the current table state.
     */
    scheduleAuthoritativeTableRefresh();
}


/**
 * Handle a server-authoritative Support Request update.
 *
 * This covers official responses, answer edits, citizen confirmations,
 * and follow-up requests. The existing row is replaced only when the
 * updated ticket still belongs to the current filtered view.
 */
function handleRealtimeSupportRequestUpdate(request) {
    if (!request?.id) {
        return;
    }

    updateRealtimeSupportRequestRow(request);
}


/*
|--------------------------------------------------------------------------
| IN-PAGE REALTIME RELAY
|--------------------------------------------------------------------------
|
| admin-notifications.js also subscribes to admin.support-requests.
| When it receives a creation event, it relays the same payload here.
| This gives the table a resilient same-page fallback without changing
| the server event contract.
|--------------------------------------------------------------------------
*/

window.addEventListener(
    'knowurlocal:support-request-created',
    (event) => {
        handleRealtimeSupportRequest(event.detail);
    }
);


/*
|--------------------------------------------------------------------------
| CONNECT TO LARAVEL ECHO
|--------------------------------------------------------------------------
|
| IMPORTANT:
|
| This function is intentionally named connectRealtime rather than
| initializeRealtime.
|
| initializeRealtime() is the public module initializer exported at
| the bottom of this file.
|
| Keeping these responsibilities separate prevents duplicate function
| declarations and makes the module's API clearer.
|--------------------------------------------------------------------------
*/

function connectRealtime() {

    /*
    |--------------------------------------------------------------------------
    | PREVENT DUPLICATE SUBSCRIPTIONS
    |--------------------------------------------------------------------------
    */

    if (
        realtimeInitialized
    ) {
        return true;
    }


    /*
    |--------------------------------------------------------------------------
    | WAIT FOR ECHO
    |--------------------------------------------------------------------------
    |
    | Echo may not yet exist when this module starts.
    |
    | Returning false allows startRealtimeInitialization() to retry
    | shortly afterward.
    |--------------------------------------------------------------------------
    */

    if (
        !window.Echo
    ) {
        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | PRIVATE ADMIN CHANNEL
    |--------------------------------------------------------------------------
    |
    | The server must authorize access to this private channel.
    |
    | The browser having the channel name is NOT sufficient authorization.
    |--------------------------------------------------------------------------
    */

    window.Echo
        .private(
            'admin.support-requests'
        )
        .listen(
            '.support.request.created',
            handleRealtimeSupportRequest
        )
        .listen(
            '.support.request.updated',
            handleRealtimeSupportRequestUpdate
        )
        .subscribed(
            () => {
                if (document.querySelector('meta[name="realtime-debug"]')?.content === 'true') {
                    console.info('[KnowUrLocal realtime] Subscribed to admin.support-requests.');
                }
            }
        )
        .error(
            (error) => {
                console.error('[KnowUrLocal realtime] Failed to subscribe to admin.support-requests.', {
                    type: error?.type,
                    status: error?.status,
                    code: error?.code,
                });
            }
        );


    /*
    |--------------------------------------------------------------------------
    | MARK AS INITIALIZED
    |--------------------------------------------------------------------------
    */

    realtimeInitialized = true;


    return true;
}


/*
|--------------------------------------------------------------------------
| START REALTIME INITIALIZATION
|--------------------------------------------------------------------------
|
| Echo may be initialized after this feature module.
|
| We therefore retry at a controlled interval instead of assuming
| window.Echo already exists.
|--------------------------------------------------------------------------
*/

function startRealtimeInitialization() {

    /*
    |--------------------------------------------------------------------------
    | TRY IMMEDIATELY
    |--------------------------------------------------------------------------
    */

    if (
        connectRealtime()
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | RETRY CONFIGURATION
    |--------------------------------------------------------------------------
    |
    | 40 attempts × 500ms = 20 seconds maximum.
    |
    | This is intentionally bounded so a failed Echo initialization
    | cannot leave an infinite timer running.
    |--------------------------------------------------------------------------
    */

    let attempts = 0;

    const maxAttempts = 40;

    const retryInterval = 500;


    /*
    |--------------------------------------------------------------------------
    | AVOID MULTIPLE RETRY TIMERS
    |--------------------------------------------------------------------------
    */

    if (
        realtimeRetryTimer !== null
    ) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | START RETRY TIMER
    |--------------------------------------------------------------------------
    */

    realtimeRetryTimer =
        window.setInterval(
            () => {

                attempts += 1;


                /*
                |--------------------------------------------------------------------------
                | TRY TO CONNECT
                |--------------------------------------------------------------------------
                */

                if (
                    connectRealtime()
                ) {

                    window.clearInterval(
                        realtimeRetryTimer
                    );

                    realtimeRetryTimer =
                        null;

                    return;
                }


                /*
                |--------------------------------------------------------------------------
                | STOP AFTER MAXIMUM ATTEMPTS
                |--------------------------------------------------------------------------
                */

                if (
                    attempts >= maxAttempts
                ) {

                    window.clearInterval(
                        realtimeRetryTimer
                    );

                    realtimeRetryTimer =
                        null;
                }

            },
            retryInterval
        );
}


/*
|--------------------------------------------------------------------------
| PUBLIC MODULE INITIALIZER
|--------------------------------------------------------------------------
|
| index.js imports this function.
|
| Keeping the public API small means index.js does not need to know
| anything about Echo's internal connection process.
|--------------------------------------------------------------------------
*/

export function initializeRealtime() {

    startRealtimeInitialization();
}