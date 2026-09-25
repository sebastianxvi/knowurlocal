/**
 * KNOWURLOCAL
 * My Inquiries — Realtime Updates
 *
 * Reverb tells us that an inquiry changed.
 * Laravel then provides the authoritative state.
 */

import { updateInquiryCard } from './inquiry-card.js';

let realtimeInitialized = false;
let realtimeRetryTimer = null;

const MAX_RETRIES = 40;
const RETRY_DELAY = 500;

/**
 * Read the authenticated user's ID from the page.
 *
 * This value is only used to determine which private
 * Echo channel to subscribe to.
 */
function getCurrentUserId() {
    const meta = document.querySelector(
        'meta[name="user-id"]'
    );

    if (!meta) {
        return null;
    }

    const value = meta.content.trim();

    if (!/^\d+$/.test(value)) {
        return null;
    }

    return value;
}

/**
 * Validate the small payload received from Reverb.
 *
 * We intentionally do not trust this payload as the
 * source of the actual inquiry contents.
 */
function isValidRealtimePayload(payload) {
    if (
        !payload ||
        typeof payload !== 'object'
    ) {
        return false;
    }

    const supportRequestId =
        Number(payload.support_request_id);

    const responseId =
        Number(payload.response_id);

    const status =
        typeof payload.status === 'string'
            ? payload.status
            : '';

    if (
        !Number.isSafeInteger(supportRequestId) ||
        supportRequestId <= 0
    ) {
        return false;
    }

    if (
        !Number.isSafeInteger(responseId) ||
        responseId <= 0
    ) {
        return false;
    }

    /**
     * Only statuses that make sense for a newly
     * forwarded official response are accepted.
     */
    if (status !== 'awaiting_confirmation') {
        return false;
    }

    return true;
}

/**
 * Find the inquiry card belonging to the event.
 */
function findInquiryCard(supportRequestId) {
    if (
        !Number.isSafeInteger(
            Number(supportRequestId)
        )
    ) {
        return null;
    }

    const escapedId = CSS.escape(
        String(supportRequestId)
    );

    return document.querySelector(
        `.inquiry-card[data-id="${escapedId}"]`
    );
}

/**
 * Fetch the authoritative inquiry state from Laravel.
 */
async function fetchInquiry(supportRequestId) {
    const response = await fetch(
        `/my-inquiries/${encodeURIComponent(
            supportRequestId
        )}`,
        {
            method: 'GET',

            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },

            credentials: 'same-origin',

            cache: 'no-store',
        }
    );

    /**
     * Authentication/authorization failures should not
     * be treated as valid realtime data.
     */
    if (!response.ok) {
        throw new Error(
            `Inquiry request failed with status ${response.status}.`
        );
    }

    const data = await response.json();

    if (
        !data ||
        data.success !== true ||
        !data.inquiry ||
        typeof data.inquiry !== 'object'
    ) {
        throw new Error(
            'Laravel returned an invalid inquiry response.'
        );
    }

    return data.inquiry;
}

/**
 * Handle a new official response notification.
 */
async function handleRealtimeResponse(payload) {
    if (!isValidRealtimePayload(payload)) {
        return;
    }

    const supportRequestId =
        Number(payload.support_request_id);

    const card =
        findInquiryCard(supportRequestId);

    /**
     * The event may arrive for an inquiry that isn't
     * currently rendered on this page.
     *
     * There is nothing to update in that case.
     */
    if (!card) {
        return;
    }

    try {
        /**
         * Fetch fresh server state rather than trusting
         * the broadcast payload.
         */
        const inquiry =
            await fetchInquiry(
                supportRequestId
            );

        /**
         * Make sure the returned inquiry still belongs
         * to the card we originally targeted.
         */
        if (
            Number(inquiry.id) !==
            supportRequestId
        ) {
            return;
        }

        updateInquiryCard(
            card,
            inquiry
        );
    } catch (error) {
        /**
         * Do not expose internal server details to users.
         *
         * Logging the technical error in the browser console
         * is useful during development.
         */
        console.error(
            'KNOWURLOCAL realtime inquiry update failed.',
            error
        );
    }
}

/**
 * Subscribe to the authenticated user's private channel.
 */
function connectRealtime() {
    if (realtimeInitialized) {
        return;
    }

    const currentUserId =
        getCurrentUserId();

    /**
     * Echo may not be initialized yet when this module
     * first executes.
     */
    if (
        !currentUserId ||
        !window.Echo
    ) {
        return false;
    }

    const channelName =
        `App.Models.User.${currentUserId}`;

    window.Echo
        .private(channelName)
        .listen(
            '.support.request.response.created',
            handleRealtimeResponse
        );

    realtimeInitialized = true;

    return true;
}

/**
 * Retry until Echo becomes available.
 *
 * This is bounded so a configuration problem cannot
 * create an infinite timer loop.
 */
function initializeRealtime() {
    if (connectRealtime()) {
        return;
    }

    let attempts = 0;

    const retry = () => {
        attempts++;

        if (connectRealtime()) {
            realtimeRetryTimer = null;

            return;
        }

        if (attempts >= MAX_RETRIES) {
            realtimeRetryTimer = null;

            console.warn(
                'KNOWURLOCAL realtime connection could not be initialized.'
            );

            return;
        }

        realtimeRetryTimer =
            window.setTimeout(
                retry,
                RETRY_DELAY
            );
    };

    retry();
}

/**
 * Public entry point used by index.js.
 */
export {
    initializeRealtime,
};