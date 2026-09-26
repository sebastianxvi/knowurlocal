import Echo from "laravel-echo";
import Pusher from "pusher-js";

/*
|--------------------------------------------------------------------------
| Laravel Reverb / Echo
|--------------------------------------------------------------------------
|
| Reverb speaks the Pusher-compatible WebSocket protocol. Only public
| connection settings are exposed to the browser. The private Reverb
| application secret never belongs in VITE_* variables.
|
*/

window.Pusher = Pusher;

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
const reverbDebug = import.meta.env.VITE_REVERB_DEBUG === "true";

const forceTLS = reverbScheme === "https";

/*
 * Missing public configuration should disable realtime gracefully rather
 * than breaking every admin page. Features using Echo already include their
 * own fallback/error handling.
 */
const reverbReady =
    Boolean(reverbAppKey) &&
    Boolean(reverbHost) &&
    Boolean(broadcastAuthEndpoint) &&
    Boolean(csrfToken);

if (!reverbReady) {
    console.warn(
        "Laravel Reverb is not initialized because its public frontend configuration is incomplete."
    );

    window.Echo = null;
} else {
    Pusher.logToConsole = reverbDebug;

    window.Echo = new Echo({
        broadcaster: "reverb",
        key: reverbAppKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS: forceTLS,
        enabledTransports: forceTLS ? ["wss"] : ["ws"],
        authEndpoint: broadcastAuthEndpoint,
        auth: {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        },
    });

    /*
     * Diagnostics are intentionally quiet unless debugging is enabled.
     * Never log secrets, cookies, CSRF values, or authentication headers.
     */
    if (reverbDebug) {
        const connection =
            window.Echo?.connector?.pusher?.connection;

        if (connection) {
            connection.bind("connected", () => {
                console.info("Reverb WebSocket connected.");
            });

            connection.bind("disconnected", () => {
                console.warn("Reverb WebSocket disconnected.");
            });

            connection.bind("unavailable", () => {
                console.warn("Reverb WebSocket unavailable.");
            });

            connection.bind("failed", () => {
                console.warn("Reverb WebSocket connection failed.");
            });

            connection.bind("error", (error) => {
                console.warn("Reverb WebSocket error.", error);
            });
        }
    }
}
