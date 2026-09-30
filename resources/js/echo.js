import Echo from "@ably/laravel-echo";
import Pusher from "pusher-js";
import * as Ably from "ably";

/*
 * Local development can continue using Reverb. Set VITE_REALTIME_DRIVER=ably
 * for Vercel deployments. The server-side Ably broadcaster handles token
 * authentication through Laravel's existing broadcasting auth endpoint.
 */
window.Pusher = Pusher;
window.Ably = Ably;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const realtimeDriver =
    document.querySelector('meta[name="realtime-driver"]')?.content ||
    import.meta.env.VITE_REALTIME_DRIVER ||
    "reverb";
const dispatchEchoReady = () => {
    window.dispatchEvent(new CustomEvent("knowurlocal:echo-ready"));
};

if (!csrfToken) {
    console.warn("Realtime is disabled because the CSRF token is unavailable.");
    window.Echo = null;
} else if (realtimeDriver === "ably") {
    const broadcastAuthEndpoint = document.querySelector(
        'meta[name="broadcast-auth-endpoint"]'
    )?.content;

    if (!broadcastAuthEndpoint) {
        console.warn("Ably is not initialized because the broadcast authorization endpoint is unavailable.");
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

        const connection = window.Echo?.connector?.ably?.connection;
        if (connection) {
            connection.on((stateChange) => {
                if (stateChange.current === "connected") dispatchEchoReady();
                if (import.meta.env.VITE_REALTIME_DEBUG === "true") {
                    if (["disconnected", "failed", "suspended"].includes(stateChange.current)) {
                        console.warn(`Ably connection state: ${stateChange.current}`);
                    }
                }
            });
        }
    }
} else {
    const broadcastAuthEndpoint = document.querySelector(
        'meta[name="broadcast-auth-endpoint"]'
    )?.content;
    const reverbAppKey = import.meta.env.VITE_REVERB_APP_KEY;
    const reverbHost = import.meta.env.VITE_REVERB_HOST;
    const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || 8080);
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || "http";
    const forceTLS = reverbScheme === "https";

    if (!reverbAppKey || !reverbHost || !broadcastAuthEndpoint) {
        console.warn("Reverb is not initialized because its public configuration is incomplete.");
        window.Echo = null;
    } else {
        Pusher.logToConsole = import.meta.env.VITE_REALTIME_DEBUG === "true";
        window.Echo = new Echo({
            broadcaster: "reverb",
            key: reverbAppKey,
            wsHost: reverbHost,
            wsPort: reverbPort,
            wssPort: reverbPort,
            forceTLS,
            enabledTransports: forceTLS ? ["wss"] : ["ws"],
            disableStats: true,
            authEndpoint: broadcastAuthEndpoint,
            auth: {
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
            },
        });

        const connection = window.Echo?.connector?.pusher?.connection;
        if (connection) {
            if (connection.state === "connected") dispatchEchoReady();
            else connection.bind("connected", dispatchEchoReady);

            if (import.meta.env.VITE_REALTIME_DEBUG === "true") {
                connection.bind("disconnected", () => console.warn("Reverb disconnected."));
                connection.bind("unavailable", () => console.warn("Reverb unavailable."));
                connection.bind("failed", () => console.warn("Reverb connection failed."));
            }
        }
    }
}
