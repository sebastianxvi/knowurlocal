import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const readMeta = (name) =>
    document.querySelector(`meta[name="${name}"]`)?.content?.trim() ?? '';

const csrfToken = readMeta('csrf-token');
const broadcastAuthEndpoint = readMeta('broadcast-auth-endpoint');
const appKey = readMeta('reverb-app-key');
const host = readMeta('reverb-host');
const port = Number(readMeta('reverb-port') || 443);
const scheme = (readMeta('reverb-scheme') || 'https').toLowerCase();
const realtimeDebug = readMeta('realtime-debug') === 'true';

const dispatchEchoReady = () => {
    window.dispatchEvent(new CustomEvent('knowurlocal:echo-ready'));
};

if (!csrfToken || !broadcastAuthEndpoint) {
    console.warn('KnowUrLocal realtime is unavailable: CSRF or broadcast authorization metadata is missing.');
    window.Echo = null;
} else if (!appKey || !host || !Number.isInteger(port) || port < 1 || port > 65535) {
    console.warn('KnowUrLocal realtime is unavailable: Reverb connection metadata is incomplete.');
    window.Echo = null;
} else {
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: appKey,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: broadcastAuthEndpoint,
        auth: {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                Accept: 'application/json',
            },
        },
    });

    const connection = window.Echo?.connector?.pusher?.connection;

    connection?.bind('connected', () => {
        if (realtimeDebug) console.info('[KnowUrLocal realtime] Reverb connected.');
        dispatchEchoReady();
    });

    connection?.bind('state_change', (states) => {
        if (realtimeDebug) {
            console.info('[KnowUrLocal realtime] Connection state:', states.previous, '→', states.current);
        }
    });

    connection?.bind('error', (error) => {
        console.error('[KnowUrLocal realtime] Reverb connection error.', {
            type: error?.type,
            code: error?.code,
        });
    });

    // Consumers can subscribe immediately if Echo was initialized before their module.
    if (connection?.state === 'connected') dispatchEchoReady();
}
