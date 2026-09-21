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
    realtimeRequestMatchesCurrentView,
} from './request-table.js';


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
}


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
        .subscribed(
            () => {
                /*
                |--------------------------------------------------------------------------
                | Successful subscription
                |--------------------------------------------------------------------------
                |
                | No additional UI action is required.
                |--------------------------------------------------------------------------
                */
            }
        )
        .error(
            () => {
                /*
                |--------------------------------------------------------------------------
                | Connection errors
                |--------------------------------------------------------------------------
                |
                | Echo manages the underlying connection lifecycle.
                |
                | We intentionally avoid noisy console logging here.
                |--------------------------------------------------------------------------
                */
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