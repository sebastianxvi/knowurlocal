/*
 * KNOWURLOCAL admin shell controller.
 *
 * Responsibilities:
 * 1. Persist desktop sidebar collapse state.
 * 2. Turn the sidebar into a drawer on small screens.
 * 3. Keep the pending-support badge synchronized without trusting
 *    browser-supplied counts.
 *
 * Security note:
 * The notification count is always obtained from the server.
 * Client-side state only controls presentation.
 */
(function () {
    'use strict';

    const STORAGE_KEY = 'knowurlocal.admin.sidebar.collapsed';
    const MOBILE_BREAKPOINT = 900;
    const POLL_INTERVAL = 20000;

    const root = document.documentElement;
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

    const setPendingCount = (count) => {
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
                `${safeCount} pending support requests`
            );
        });

        document.querySelectorAll('[data-pending-count]').forEach((node) => {
            node.textContent = safeCount > 99
                ? '99+'
                : String(safeCount);
        });
    };

    const setCollapsed = (collapsed, persist = true) => {
        root.classList.toggle('admin-sidebar-collapsed', collapsed);

        if (toggle) {
            toggle.setAttribute(
                'aria-expanded',
                String(!collapsed)
            );

            toggle.setAttribute(
                'aria-label',
                collapsed
                    ? 'Expand navigation'
                    : 'Collapse navigation'
            );
        }

        if (persist) {
            try {
                window.localStorage.setItem(
                    STORAGE_KEY,
                    collapsed ? '1' : '0'
                );
            } catch (_) {
                // Storage may be unavailable in privacy-restricted browsers.
            }
        }
    };

    const setDrawerOpen = (open) => {
        body.classList.toggle('admin-drawer-open', open);

        if (sidebar) {
            sidebar.setAttribute(
                'aria-hidden',
                String(!open && window.innerWidth <= MOBILE_BREAKPOINT)
            );
        }
    };

    const restoreShellState = () => {
        if (window.innerWidth <= MOBILE_BREAKPOINT) {
            setDrawerOpen(false);
            return;
        }

        let collapsed = false;

        try {
            collapsed =
                window.localStorage.getItem(STORAGE_KEY) === '1';
        } catch (_) {
            collapsed = false;
        }

        setCollapsed(collapsed, false);
    };

    const fetchPendingCount = async () => {
        if (!window.fetch || document.visibilityState === 'hidden') {
            return;
        }

        const endpoint = document.body.dataset.pendingSupportEndpoint;

        if (!endpoint) {
            return;
        }

        try {
            const response = await fetch(endpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                cache: 'no-store'
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();

            if (payload && payload.success === true) {
                setPendingCount(payload.count);
            }
        } catch (_) {
            // Realtime/polling failures must never break the admin UI.
        }
    };

    const subscribeRealtime = () => {
        if (!window.Echo || typeof window.Echo.private !== 'function') {
            return false;
        }

        try {
            window.Echo.private('admin.support-requests')
                .listen('.support.request.created', () => {
                    fetchPendingCount();
                });

            return true;
        } catch (error) {
            console.warn(
                'Admin support notification channel unavailable.',
                error
            );
            return false;
        }
    };

    if (toggle) {
        toggle.addEventListener('click', () => {
            if (window.innerWidth <= MOBILE_BREAKPOINT) {
                setDrawerOpen(true);
                return;
            }

            const collapsed =
                root.classList.contains('admin-sidebar-collapsed');

            setCollapsed(!collapsed);
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', () => {
            setDrawerOpen(false);
        });
    }

    document.addEventListener('click', (event) => {
        const link = event.target.closest('.sidebar a');

        if (link && window.innerWidth <= MOBILE_BREAKPOINT) {
            setDrawerOpen(false);
        }
    });

    window.addEventListener('resize', restoreShellState);

    restoreShellState();
    subscribeRealtime();
    fetchPendingCount();

    window.setInterval(
        fetchPendingCount,
        POLL_INTERVAL
    );
})();
