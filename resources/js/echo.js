import Echo from "laravel-echo";
import Pusher from "pusher-js";

/*
|--------------------------------------------------------------------------
| Make Pusher available globally
|--------------------------------------------------------------------------
|
| Laravel Reverb uses the Pusher-compatible WebSocket protocol.
| Echo therefore uses Pusher as its client transport.
|
*/

window.Pusher = Pusher;

/*
|--------------------------------------------------------------------------
| Reverb diagnostics
|--------------------------------------------------------------------------
|
| Enable Pusher's console logging while troubleshooting the WebSocket
| connection. Disable this in production once the connection is stable
| because verbose WebSocket logs are unnecessary for normal users.
|
*/

Pusher.logToConsole = import.meta.env.DEV;

/*
|--------------------------------------------------------------------------
| Read Laravel configuration
|--------------------------------------------------------------------------
|
| Vite exposes only variables prefixed with VITE_ to browser JavaScript.
|
| Never expose private values such as:
|
|     REVERB_APP_SECRET
|
| through VITE_ variables.
|
*/

const csrfToken = document.querySelector(
    'meta[name="csrf-token"]'
)?.content;

const broadcastAuthEndpoint = document.querySelector(
    'meta[name="broadcast-auth-endpoint"]'
)?.content;

const reverbAppKey =
    import.meta.env.VITE_REVERB_APP_KEY;

const reverbHost =
    import.meta.env.VITE_REVERB_HOST;

const reverbPort =
    Number(
        import.meta.env.VITE_REVERB_PORT || 8080
    );

const reverbScheme =
    import.meta.env.VITE_REVERB_SCHEME || "http";

/*
|--------------------------------------------------------------------------
| Validate required public configuration
|--------------------------------------------------------------------------
|
| Fail early if required frontend configuration is missing.
|
| This is preferable to allowing Echo to initialize with undefined
| connection settings and producing a much less useful WebSocket error.
|
*/

const reverbConfigured = Boolean(
    reverbAppKey &&
    reverbHost &&
    broadcastAuthEndpoint
);

if (!reverbConfigured) {
    console.warn(
        "Realtime features are disabled because Reverb is not configured."
    );
}

if (!reverbConfigured) {
    // Keep the rest of the admin UI functional when WebSockets are
    // intentionally disabled (for example during local setup).
    window.Echo = null;
} else {

/*
|--------------------------------------------------------------------------
| Determine WebSocket security
|--------------------------------------------------------------------------
|
| Local development normally uses:
|
|     HTTP  → WS
|
| Production normally uses:
|
|     HTTPS → WSS
|
| Instead of hardcoding either environment, derive the WebSocket
| configuration from VITE_REVERB_SCHEME.
|
*/

const forceTLS =
    reverbScheme === "https";

/*
|--------------------------------------------------------------------------
| Initialize Laravel Echo
|--------------------------------------------------------------------------
|
| Echo manages channels, subscriptions, authentication, and events.
|
| Reverb handles the actual WebSocket server.
|
| The connection settings are intentionally environment-driven so the
| same JavaScript file works both locally and in production.
|
*/

window.Echo = new Echo({

    /*
     * Laravel Reverb communicates through the Pusher-compatible
     * WebSocket protocol.
     */
    broadcaster: "reverb",

    /*
     * Public Reverb application key.
     *
     * This is safe to expose to the browser.
     */
    key: reverbAppKey,

    /*
     * Reverb hostname.
     *
     * Local example:
     *
     *     127.0.0.1
     *
     * Production example:
     *
     *     your-reverb-domain
     */
    wsHost: reverbHost,

    /*
     * WebSocket port.
     *
     * Local:
     *
     *     8080
     *
     * Production:
     *
     *     Whatever port is exposed by the Reverb deployment.
     */
    wsPort: reverbPort,

    /*
     * Secure WebSocket port.
     *
     * We keep this environment-driven rather than hardcoding 443.
     */
    wssPort: reverbPort,

    /*
     * Enable TLS only when Reverb is configured to use HTTPS.
     *
     * Local:
     *
     *     false
     *
     * Production:
     *
     *     true
     */
    forceTLS: forceTLS,

    /*
     * Allow only the transport appropriate for the current environment.
     *
     * Local:
     *
     *     ws
     *
     * Production:
     *
     *     wss
     *
     * Restricting the transport is preferable to allowing unnecessary
     * fallback transports.
     */
    enabledTransports: forceTLS
        ? ["wss"]
        : ["ws"],

    /*
     * Laravel's private-channel authentication endpoint.
     *
     * This endpoint verifies whether the currently authenticated
     * Laravel user is allowed to subscribe to the private channel.
     */
    authEndpoint:
        broadcastAuthEndpoint,

    /*
     * Authentication headers.
     *
     * The CSRF token protects Laravel's POST authentication request.
     *
     * The token is read from Laravel's CSRF meta tag instead of being
     * hardcoded into JavaScript.
     */
    auth: {
        headers: {
            "X-CSRF-TOKEN":
                csrfToken,

            Accept:
                "application/json",
        },
    },
});

/*
|--------------------------------------------------------------------------
| Reverb connection diagnostics
|--------------------------------------------------------------------------
|
| These listeners make it possible to distinguish between:
|
| - connecting
| - successfully connected
| - disconnected
| - unavailable
| - failed
| - connection errors
|
| Keep these while troubleshooting.
|
*/

const pusherConnection =
    window.Echo
        .connector
        .pusher
        .connection;

/*
|--------------------------------------------------------------------------
| State changes
|--------------------------------------------------------------------------
*/

pusherConnection.bind(
    "state_change",
    (states) => {

        console.info(
            "Reverb state changed:",
            states
        );

    }
);

/*
|--------------------------------------------------------------------------
| Connecting
|--------------------------------------------------------------------------
*/

pusherConnection.bind(
    "connecting_in",
    (data) => {

        console.info(
            "Reverb connecting in:",
            data
        );

    }
);

/*
|--------------------------------------------------------------------------
| Successfully connected
|--------------------------------------------------------------------------
*/

pusherConnection.bind(
    "connected",
    () => {

        console.info(
            "Reverb WebSocket connected."
        );

    }
);

/*
|--------------------------------------------------------------------------
| Disconnected
|--------------------------------------------------------------------------
*/

pusherConnection.bind(
    "disconnected",
    () => {

        console.warn(
            "Reverb WebSocket disconnected."
        );

    }
);

/*
|--------------------------------------------------------------------------
| Temporarily unavailable
|--------------------------------------------------------------------------
*/

pusherConnection.bind(
    "unavailable",
    () => {

        console.error(
            "Reverb WebSocket unavailable."
        );

    }
);

/*
|--------------------------------------------------------------------------
| Connection failed
|--------------------------------------------------------------------------
*/

pusherConnection.bind(
    "failed",
    () => {

        console.error(
            "Reverb WebSocket failed."
        );

    }
);

/*
|--------------------------------------------------------------------------
| Detailed connection error
|--------------------------------------------------------------------------
*/

pusherConnection.bind(
    "error",
    (error) => {

        console.error(
            "DETAILED REVERB CONNECTION ERROR:",
            error
        );

    }
);

/*
|--------------------------------------------------------------------------
| Safe configuration diagnostics
|--------------------------------------------------------------------------
|
| These logs expose configuration information that is safe for the
| browser to know.
|
| Never log:
|
|     REVERB_APP_SECRET
|     database passwords
|     API secrets
|     authentication tokens
|
*/

console.info(
    "Laravel Echo with Reverb initialized."
);

console.info(
    "Reverb host:",
    reverbHost
);

console.info(
    "Reverb port:",
    reverbPort
);

console.info(
    "Reverb TLS enabled:",
    forceTLS
);

console.info(
    "Reverb auth endpoint:",
    broadcastAuthEndpoint
);
}
