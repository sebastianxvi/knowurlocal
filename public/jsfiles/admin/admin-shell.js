/*
 * KNOWURLOCAL admin shell controller.
 *
 * Desktop navigation is intentionally CSS-driven:
 * the sidebar stays as a compact rail and expands when hovered/focused.
 *
 * JavaScript is reserved for interactions that cannot be expressed safely
 * with CSS alone:
 * 1. mobile navigation drawer
 * 2. real-time "new support request" notifications
 *
 * The notification badge is NOT initialized from the existing pending count.
 * It only appears after Reverb delivers a new support-request event while this
 * browser is on another admin page.
 */
(function () {
    'use strict';

    const MOBILE_BREAKPOINT = 900;
    const ADMIN_ID = document.body.dataset.adminUserId || 'anonymous';
    const STORAGE_KEY = `knowurlocal.admin.new-support-requests.${ADMIN_ID}`;
    const MAX_EVENT_IDS = 100;

    const body = document.body;
    const toggle = document.querySelector('[data-admin-shell-toggle]');
    const closeButton = document.querySelector('[data-admin-shell-close]');
    const sidebar = document.querySelector('.sidebar');

    const badges = () => Array.from(
        document.querySelectorAll('.js-support-pending-badge')
    );

    const isSupportPage = () =>
        document.body.dataset.adminPage === 'support-requests' ||
        window.location.pathname.endsWith('/support-requests');

    const readNotificationState = () => {
        try {
            const raw = window.localStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return { count: 0, ids: [] };
            }

            const parsed = JSON.parse(raw);

            return {
                count: Math.max(
                    0,
                    Number.parseInt(parsed?.count, 10) || 0
                ),
                ids: Array.isArray(parsed?.ids)
                    ? parsed.ids.map(String).slice(-MAX_EVENT_IDS)
                    : []
            };
        } catch (_) {
            return { count: 0, ids: [] };
        }
    };

    const writeNotificationState = (state) => {
        try {
            window.localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    count: Math.max(0, state.count),
                    ids: state.ids.slice(-MAX_EVENT_IDS)
                })
            );
        } catch (_) {
            // Private browsing/storage restrictions must not break navigation.
        }
    };

    const clearNotificationState = () => {
        try {
            window.localStorage.removeItem(STORAGE_KEY);
        } catch (_) {
            // Ignore unavailable storage.
        }

        renderNotificationCount(0);
    };

    const renderNotificationCount = (count) => {
        const safeCount = Math.max(
            0,
            Number.parseInt(count, 10) || 0
        );

        badges().forEach((badge) => {
            const hidden = safeCount < 1 || isSupportPage();

            badge.hidden = hidden;
            badge.textContent = safeCount > 99
                ? '99+'
                : String(safeCount);

            badge.setAttribute(
                'aria-label',
                `${safeCount} new support requests`
            );
        });
    };

    const syncNotificationFromStorage = () => {
        const state = readNotificationState();
        renderNotificationCount(state.count);
    };

    const registerNewSupportRequest = (eventId) => {
        if (isSupportPage()) {
            clearNotificationState();
            return;
        }

        const state = readNotificationState();
        const normalizedId = String(eventId ?? '');

        /*
         * Reconnects can occasionally replay an event. Remember recent IDs
         * so one request cannot inflate the badge more than once.
         */
        if (normalizedId && state.ids.includes(normalizedId)) {
            return;
        }

        if (normalizedId) {
            state.ids.push(normalizedId);
        }

        state.count += 1;

        writeNotificationState(state);
        renderNotificationCount(state.count);
    };

    const setDrawerOpen = (open) => {
        if (!sidebar || window.innerWidth > MOBILE_BREAKPOINT) {
            return;
        }

        body.classList.toggle('admin-drawer-open', open);

        sidebar.setAttribute(
            'aria-hidden',
            String(!open)
        );

        if (toggle) {
            toggle.setAttribute(
                'aria-expanded',
                String(open)
            );
            toggle.setAttribute(
                'aria-label',
                open ? 'Close navigation' : 'Open navigation'
            );
        }
    };

    const restoreMobileState = () => {
        if (window.innerWidth > MOBILE_BREAKPOINT) {
            body.classList.remove('admin-drawer-open');

            if (sidebar) {
                sidebar.removeAttribute('aria-hidden');
            }

            if (toggle) {
                toggle.setAttribute('aria-expanded', 'false');
                toggle.setAttribute('aria-label', 'Open navigation');
            }

            return;
        }

        setDrawerOpen(false);
    };

    const subscribeRealtime = () => {
        if (
            !window.Echo ||
            typeof window.Echo.private !== 'function'
        ) {
            return;
        }

        try {
            window.Echo
                .private('admin.support-requests')
                .listen('.support.request.created', (event) => {
                    registerNewSupportRequest(event?.id);
                });
        } catch (error) {
            console.warn(
                'Admin support-request realtime notifications are unavailable.',
                error
            );
        }
    };

    if (toggle) {
        toggle.addEventListener('click', () => {
            setDrawerOpen(
                !body.classList.contains('admin-drawer-open')
            );
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', () => {
            setDrawerOpen(false);
        });
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.sidebar a');

        if (
            link &&
            window.innerWidth <= MOBILE_BREAKPOINT
        ) {
            setDrawerOpen(false);
        }
    });

    window.addEventListener('resize', restoreMobileState);

    /*
     * When another tab opens Support Requests, it clears the shared
     * notification state. The storage event keeps this tab synchronized.
     */
    window.addEventListener('storage', (event) => {
        if (event.key !== STORAGE_KEY) {
            return;
        }

        if (isSupportPage()) {
            clearNotificationState();
            return;
        }

        syncNotificationFromStorage();
    });

    restoreMobileState();

    if (isSupportPage()) {
        clearNotificationState();
    } else {
        syncNotificationFromStorage();
    }

    subscribeRealtime();
})();
