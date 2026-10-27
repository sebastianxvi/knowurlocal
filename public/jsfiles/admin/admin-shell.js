/*
 * KNOWURLOCAL admin shell controller.
 * Handles the mobile navigation drawer for every protected admin workspace.
 */
(function () {
    'use strict';

    const body = document.body;
    const toggleSelector = '[data-admin-shell-toggle]';
    const closeSelector = '[data-admin-shell-close]';
    const sidebarSelector = '.sidebar';

    const setDrawerOpen = (open) => {
        const sidebar = document.querySelector(sidebarSelector);
        const toggle = document.querySelector(toggleSelector);
        if (!sidebar) return;

        body.classList.toggle('admin-drawer-open', open);
        sidebar.setAttribute('aria-hidden', String(!open));

        if (toggle) {
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
            const icon = toggle.querySelector('i');
            if (icon) {
                icon.classList.toggle('ph-list', !open);
                icon.classList.toggle('ph-x', open);
            }
        }
    };

    // Delegate clicks so the drawer remains functional if a layout fragment
    // is replaced or initialized after this script is evaluated.
    document.addEventListener('click', (event) => {
        const toggle = event.target.closest(toggleSelector);
        if (toggle) {
            event.preventDefault();
            setDrawerOpen(!body.classList.contains('admin-drawer-open'));
            return;
        }

        if (event.target.closest(closeSelector)) {
            setDrawerOpen(false);
            return;
        }

        if (event.target.closest('.sidebar a')) {
            setDrawerOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && body.classList.contains('admin-drawer-open')) {
            setDrawerOpen(false);
            document.querySelector(toggleSelector)?.focus();
        }
    });

    // Establish a predictable closed state on each page load.
    setDrawerOpen(false);
})();
