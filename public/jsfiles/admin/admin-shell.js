/*
 * KNOWURLOCAL admin shell controller.
 *
 * Navigation is intentionally modeled after a modern repository workspace:
 * the application is full-width and the menu is opened explicitly with a
 * hamburger button. The drawer works on desktop and mobile alike.
 */
(function () {
    'use strict';

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
            if (!raw) return { count: 0, ids: [] };

            const parsed = JSON.parse(raw);
            return {
                count: Math.max(0, Number.parseInt(parsed?.count, 10) || 0),
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
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
                count: Math.max(0, state.count),
                ids: state.ids.slice(-MAX_EVENT_IDS)
            }));
        } catch (_) {
            // Storage restrictions must never break admin navigation.
        }
    };

    const renderNotificationCount = (count) => {
        const safeCount = Math.max(0, Number.parseInt(count, 10) || 0);
        badges().forEach((badge) => {
            const hidden = safeCount < 1 || isSupportPage();
            badge.hidden = hidden;
            badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
            badge.setAttribute('aria-label', `${safeCount} new support requests`);
        });
    };

    const clearNotificationState = () => {
        try { window.localStorage.removeItem(STORAGE_KEY); } catch (_) {}
        renderNotificationCount(0);
    };

    const syncNotificationFromStorage = () => {
        renderNotificationCount(readNotificationState().count);
    };

    const registerNewSupportRequest = (eventId) => {
        if (isSupportPage()) {
            clearNotificationState();
            return;
        }

        const state = readNotificationState();
        const normalizedId = String(eventId ?? '');

        if (normalizedId && state.ids.includes(normalizedId)) return;
        if (normalizedId) state.ids.push(normalizedId);

        state.count += 1;
        writeNotificationState(state);
        renderNotificationCount(state.count);
    };

    const setDrawerOpen = (open) => {
        if (!sidebar) return;

        body.classList.toggle('admin-drawer-open', open);
        sidebar.setAttribute('aria-hidden', String(!open));

        if (toggle) {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute(
                'aria-label',
                open ? 'Close navigation' : 'Open navigation'
            );
        }
    };

    const toggleDrawer = () => {
        setDrawerOpen(!body.classList.contains('admin-drawer-open'));
    };

    const subscribeRealtime = () => {
        if (!window.Echo || typeof window.Echo.private !== 'function') return;

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

    if (toggle) toggle.addEventListener('click', toggleDrawer);
    if (closeButton) closeButton.addEventListener('click', () => setDrawerOpen(false));

    /* Clicking the scrim closes the drawer. */
    document.addEventListener('click', (event) => {
        if (event.target.matches('[data-admin-shell-close]')) {
            setDrawerOpen(false);
            return;
        }

        const link = event.target.closest('.sidebar a');
        if (link) setDrawerOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && body.classList.contains('admin-drawer-open')) {
            setDrawerOpen(false);
            toggle?.focus();
        }
    });

    window.addEventListener('storage', (event) => {
        if (event.key !== STORAGE_KEY) return;
        if (isSupportPage()) clearNotificationState();
        else syncNotificationFromStorage();
    });

    setDrawerOpen(false);

    if (isSupportPage()) clearNotificationState();
    else syncNotificationFromStorage();

    subscribeRealtime();
})();
