/**
 * KNOWURLOCAL — Public Notification Realtime
 *
 * Reverb delivers the "a new official response exists" signal.
 * Laravel remains authoritative for the numeric unread count.
 */

let initialized = false;
let retryTimer = null;
let retryAttempts = 0;

const MAX_RETRIES = 40;
const RETRY_DELAY = 500;

function getUserId() {
    const meta = document.querySelector('meta[name="user-id"]');
    if (!meta) return null;

    const value = meta.content.trim();
    return /^\d+$/.test(value) ? value : null;
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function updateBadges(count) {
    const safeCount = Number.isSafeInteger(Number(count))
        ? Math.max(0, Number(count))
        : 0;

    document
        .querySelectorAll('[data-inquiry-notification-badge]')
        .forEach((badge) => {
            badge.dataset.count = String(safeCount);
            badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
            badge.hidden = safeCount <= 0;

            if (safeCount > 0) {
                badge.setAttribute(
                    'aria-label',
                    `${safeCount} unread inquiry ${safeCount === 1 ? 'response' : 'responses'}`
                );
            } else {
                badge.removeAttribute('aria-label');
            }
        });
}

async function refreshNotificationCount() {
    const response = await fetch(
        '/my-inquiries/notification-count',
        {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        }
    );

    if (!response.ok) {
        throw new Error(`Notification count request failed: ${response.status}`);
    }

    const data = await response.json();

    if (!data || data.success !== true) {
        throw new Error('Invalid notification count response.');
    }

    updateBadges(data.count);
    return Number(data.count) || 0;
}

function refreshFromBroadcast(payload, expectedStatus = null) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    const supportRequestId =
        Number(payload.support_request_id);

    if (
        !Number.isSafeInteger(supportRequestId) ||
        supportRequestId <= 0
    ) {
        return;
    }

    if (
        expectedStatus !== null &&
        payload.status !== expectedStatus
    ) {
        return;
    }

    /*
     * Never increment locally. A fresh server count prevents duplicate
     * broadcasts or reconnects from producing an incorrect badge.
     */
    refreshNotificationCount().catch((error) => {
        console.warn(
            'KNOWURLOCAL: Unable to refresh inquiry notification count.',
            error
        );
    });
}

function handleRealtimeResponse(payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    const supportRequestId = Number(payload.support_request_id);
    const responseId = Number(payload.response_id);

    if (
        !Number.isSafeInteger(supportRequestId) ||
        supportRequestId <= 0 ||
        !Number.isSafeInteger(responseId) ||
        responseId <= 0 ||
        payload.status !== 'awaiting_confirmation'
    ) {
        return;
    }

    refreshFromBroadcast(
        payload,
        'awaiting_confirmation'
    );
}

function handleRealtimeAnswer(payload) {
    refreshFromBroadcast(
        payload,
        'answered'
    );
}

function connectRealtime() {
    if (initialized) {
        return true;
    }

    const userId = getUserId();

    if (!userId || !window.Echo) {
        return false;
    }

    window.Echo
        .private(`App.Models.User.${userId}`)
        .listen(
            '.support.request.response.created',
            handleRealtimeResponse
        )
        .listen(
            '.support.request.answer.created',
            handleRealtimeAnswer
        );

    initialized = true;
    return true;
}

function initialize() {
    if (connectRealtime()) {
        return;
    }

    if (retryTimer) {
        return;
    }

    const retry = () => {
        retryAttempts += 1;

        if (connectRealtime()) {
            retryTimer = null;
            return;
        }

        if (retryAttempts >= MAX_RETRIES) {
            retryTimer = null;
            return;
        }

        retryTimer = window.setTimeout(() => {
            retryTimer = null;
            retry();
        }, RETRY_DELAY);
    };

    retry();
}

/*
 * A citizen opening an inquiry marks its response as seen.
 * Ask Laravel for the authoritative count immediately afterward.
 */
window.addEventListener(
    'inquiry:notification-changed',
    () => {
        refreshNotificationCount().catch(() => {});
    }
);

window.addEventListener(
    'knowurlocal:echo-ready',
    initialize
);

initialize();
