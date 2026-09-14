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
| Temporary production diagnostics
|--------------------------------------------------------------------------
|
| Pusher logs the WebSocket connection process and detailed failures.
| Remove this after the issue is fixed.
|
*/

Pusher.logToConsole = true;

/*
|--------------------------------------------------------------------------
| Read required configuration
|--------------------------------------------------------------------------
|
| Vite exposes only variables prefixed with VITE_ to browser JavaScript.
| Never place private secrets such as REVERB_APP_SECRET in this file.
|
*/

const csrfToken = document.querySelector(
    'meta[name="csrf-token"]'
)?.content;

const broadcastAuthEndpoint = document.querySelector(
    'meta[name="broadcast-auth-endpoint"]'
)?.content;

const reverbAppKey = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost = import.meta.env.VITE_REVERB_HOST;
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || 8080);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || "http";

/*
|--------------------------------------------------------------------------
| Validate public configuration
|--------------------------------------------------------------------------
|
| Fail early when required frontend configuration is missing.
| This prevents confusing WebSocket connection errors later.
|
*/

if (!reverbAppKey) {
    throw new Error("VITE_REVERB_APP_KEY is missing.");
}

if (!reverbHost) {
    throw new Error("VITE_REVERB_HOST is missing.");
}

if (!broadcastAuthEndpoint) {
    throw new Error("Broadcast authentication endpoint is missing.");
}

/*
|--------------------------------------------------------------------------
| Determine WebSocket transport
|--------------------------------------------------------------------------
|
| Reverb uses ws for HTTP and wss for HTTPS.
|
*/

const forceTLS = reverbScheme === "https";

/*
|--------------------------------------------------------------------------
| Initialize Laravel Echo
|--------------------------------------------------------------------------
|
| Echo manages channel subscriptions and event listeners.
| Reverb handles the actual WebSocket connection.
|
*/

window.Echo = new Echo({
    broadcaster: "reverb",

    key: reverbAppKey,

    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,

    forceTLS,

    enabledTransports: forceTLS
        ? ["wss"]
        : ["ws"],

    authEndpoint: broadcastAuthEndpoint,

    auth: {
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
        },
    },
});

/*
|--------------------------------------------------------------------------
| Detailed Reverb connection diagnostics
|--------------------------------------------------------------------------
|
| These listeners help identify whether the failure is caused by:
|
| - DNS or hostname resolution
| - TLS / HTTPS certificate problems
| - Railway WebSocket routing
| - Incorrect Reverb application credentials
| - Browser transport restrictions
|
*/

const pusherConnection =
    window.Echo.connector.pusher.connection;

pusherConnection.bind("state_change", (states) => {
    console.info("Reverb state changed:", states);
});

pusherConnection.bind("connecting_in", (data) => {
    console.info("Reverb connecting in:", data);
});

pusherConnection.bind("connected", () => {
    console.info("Reverb WebSocket connected.");
});

pusherConnection.bind("disconnected", () => {
    console.warn("Reverb WebSocket disconnected.");
});

pusherConnection.bind("unavailable", () => {
    console.error("Reverb WebSocket unavailable.");
});

pusherConnection.bind("failed", () => {
    console.error("Reverb WebSocket failed.");
});

pusherConnection.bind("error", (error) => {
    console.error("DETAILED REVERB CONNECTION ERROR:", error);
});

/*
|--------------------------------------------------------------------------
| Diagnostics
|--------------------------------------------------------------------------
|
| These logs contain configuration status only.
| Never log private keys, secrets, or authentication tokens.
|
*/

console.info("Laravel Echo with Reverb initialized.");
console.info("Reverb host:", reverbHost);
console.info("Reverb port:", reverbPort);
console.info("Reverb TLS enabled:", forceTLS);
console.info("Broadcast auth endpoint:", broadcastAuthEndpoint);