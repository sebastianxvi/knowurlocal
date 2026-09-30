import Echo from "@ably/laravel-echo";
import * as Ably from "ably";

/*
|--------------------------------------------------------------------------
| Laravel Echo / Ably
|--------------------------------------------------------------------------
|
| The Ably API key stays on the Laravel server in ABLY_KEY. The browser
| authenticates private-channel subscriptions through Laravel's
| /broadcasting/auth endpoint; no API key or secret is bundled into Vite.
|
*/

window.Ably = Ably;

const csrfToken = document.querySelector(
    'meta[name="csrf-token"]'
)?.content;

const broadcastAuthEndpoint = document.querySelector(
    'meta[name="broadcast-auth-endpoint"]'
)?.content;

const ablyDebug = import.meta.env.VITE_ABLY_DEBUG === "true";

const ablyReady =
    Boolean(broadcastAuthEndpoint) &&
    Boolean(csrfToken);

if (!ablyReady) {
    console.warn(
        "Ably Echo was not initialized because broadcasting authentication configuration is incomplete."
    );

    window.Echo = null;
} else {
    window.Echo = new Echo({
        broadcaster: "ably",
        authEndpoint: broadcastAuthEndpoint,
        auth: {
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
        },
    });

    const dispatchEchoReady = () => {
        window.dispatchEvent(
            new CustomEvent("knowurlocal:echo-ready")
        );
    };

    const connection = window.Echo?.connector?.ably?.connection;

    if (connection) {
        connection.on((stateChange) => {
            if (stateChange.current === "connected") {
                dispatchEchoReady();

                if (ablyDebug) {
                    console.info("Ably connection established.");
                }
            } else if (
                ablyDebug &&
                ["failed", "suspended", "disconnected"].includes(
                    stateChange.current
                )
            ) {
                console.warn(
                    "Ably connection state:",
                    stateChange.current
                );
            }
        });

        if (connection.state === "connected") {
            dispatchEchoReady();
        }
    }

    /*
     * Diagnostics are intentionally quiet unless debugging is enabled.
     * Never log API keys, cookies, CSRF values, or authentication headers.
     */
}
