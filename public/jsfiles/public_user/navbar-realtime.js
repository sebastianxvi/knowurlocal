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

function updateBadges(count, trashedUnreadCount = 0, needsAttentionUnreadCount = 0) {
    const safeCount = Number.isSafeInteger(Number(count))
        ? Math.max(0, Number(count))
        : 0;

    const safeTrashedCount = Number.isFinite(Number(trashedUnreadCount))
        ? Math.max(0, Math.floor(Number(trashedUnreadCount)))
        : 0;

    const safeNeedsAttentionCount = Number.isFinite(Number(needsAttentionUnreadCount))
        ? Math.max(0, Math.floor(Number(needsAttentionUnreadCount)))
        : 0;

    document
        .querySelectorAll('[data-filter-unread-count][data-filter="trashed"]')
        .forEach((badge) => {
            badge.textContent = safeTrashedCount > 99 ? '99+' : String(safeTrashedCount);
            badge.hidden = safeTrashedCount <= 0;
            badge.setAttribute(
                'aria-label',
                `${safeTrashedCount} new trashed ${safeTrashedCount === 1 ? 'inquiry' : 'inquiries'}`
            );
        });

    document
        .querySelectorAll('[data-filter-unread-count][data-filter="needs_attention"]')
        .forEach((badge) => {
            badge.textContent = safeNeedsAttentionCount > 99 ? '99+' : String(safeNeedsAttentionCount);
            badge.hidden = safeNeedsAttentionCount <= 0;
            badge.setAttribute(
                'aria-label',
                `${safeNeedsAttentionCount} new ${safeNeedsAttentionCount === 1 ? 'response' : 'responses'} needing attention`
            );
        });

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

    updateBadges(data.count, data.trashed_unread_count, data.needs_attention_unread_count);
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

function handleRealtimeSupportRequestUpdate(payload) {
    if (!payload || typeof payload !== 'object') {
        return;
    }

    if (payload.action === 'trashed') {
        refreshNotificationCount().catch((error) => {
            console.warn(
                'KNOWURLOCAL: Unable to refresh trash notification count.',
                error
            );
        });

        window.dispatchEvent(
            new CustomEvent('inquiry:trashed', {
                detail: payload,
            })
        );
    }
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
        )
        .listen(
            '.support.request.updated',
            handleRealtimeSupportRequestUpdate
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
