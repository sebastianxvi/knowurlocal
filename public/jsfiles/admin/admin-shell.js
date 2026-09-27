/*
 * KNOWURLOCAL admin shell controller.
 * The header notification center owns support/collaboration alerts;
 * this file is responsible only for navigation drawer behavior.
 */
(function () {
    'use strict';

    const body = document.body;
    const toggle = document.querySelector('[data-admin-shell-toggle]');
    const closeButton = document.querySelector('[data-admin-shell-close]');
    const sidebar = document.querySelector('.sidebar');

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

            const icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('ph-list', !open);
                icon.classList.toggle('ph-x', open);
            }
        }
    };

    const toggleDrawer = () => {
        setDrawerOpen(!body.classList.contains('admin-drawer-open'));
    };

    if (toggle) toggle.addEventListener('click', toggleDrawer);
    if (closeButton) closeButton.addEventListener('click', () => setDrawerOpen(false));

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-admin-shell-close]')) {
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

    setDrawerOpen(false);
})();
