import Echo from "@ably/laravel-echo";
import * as Ably from "ably";

/* Ably is the only realtime transport used by this application. */
window.Ably = Ably;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
const broadcastAuthEndpoint = document.querySelector(
    'meta[name="broadcast-auth-endpoint"]'
)?.content;
const dispatchEchoReady = () => {
    window.dispatchEvent(new CustomEvent("knowurlocal:echo-ready"));
};

if (!csrfToken || !broadcastAuthEndpoint) {
    console.warn("Ably realtime is disabled because its CSRF token or broadcast authorization endpoint is unavailable.");
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
            if (
                import.meta.env.VITE_REALTIME_DEBUG === "true" &&
                ["disconnected", "failed", "suspended"].includes(stateChange.current)
            ) {
                console.warn(`Ably connection state: ${stateChange.current}`);
            }
        });
    }
}
