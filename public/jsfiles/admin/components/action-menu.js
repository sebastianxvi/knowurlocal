/*
 * KNOWURLOCAL — Shared Admin Table Action Menus
 *
 * The menu panel is portalled to <body> while the page is running.
 * This is intentional: admin tables commonly sit inside scroll/overflow
 * containers, and transformed ancestors can otherwise make fixed menus
 * appear offset from the trigger. Keeping the panel at document level gives
 * every admin table the same reliable positioning behavior.
 */
(() => {
    "use strict";

    const selector = ".admin-action-menu";
    const triggerSelector = ".admin-action-menu-trigger";
    const contentSelector = ".admin-action-menu-content";
    let menuSequence = 0;

    function prepareMenus() {
        document.querySelectorAll(selector).forEach(menu => {
            const content = menu.querySelector(contentSelector);
            if (!content || content.dataset.portalReady === "true") return;

            menuSequence += 1;
            const id = `admin-action-menu-${menuSequence}`;

            menu.dataset.menuId = id;
            content.dataset.portalReady = "true";
            content.dataset.ownerMenu = id;
            content.id = id;

            // Move the panel outside table/scroll containers.
            document.body.appendChild(content);
        });
    }

    function getContent(menu) {
        if (!menu) return null;
        const id = menu.dataset.menuId;
        return id ? document.getElementById(id) : null;
    }

    function closeMenu(menu) {
        if (!menu) return;

        menu.classList.remove("is-open");

        const trigger = menu.querySelector(triggerSelector);
        trigger?.setAttribute("aria-expanded", "false");

        const content = getContent(menu);
        if (content) {
            content.classList.remove("is-visible");
            content.style.top = "";
            content.style.left = "";
        }
    }

    function closeAll(except = null) {
        document.querySelectorAll(`${selector}.is-open`).forEach(menu => {
            if (menu !== except) closeMenu(menu);
        });
    }

    function openMenu(trigger) {
        const menu = trigger.closest(selector);
        const content = getContent(menu);

        if (!menu || !content) return;

        const shouldOpen = !menu.classList.contains("is-open");
        closeAll(menu);

        if (!shouldOpen) {
            closeMenu(menu);
            return;
        }

        menu.classList.add("is-open");
        trigger.setAttribute("aria-expanded", "true");
        content.classList.add("is-visible");

        // Measure after the panel is visible so its real dimensions are used.
        const triggerRect = trigger.getBoundingClientRect();
        const contentRect = content.getBoundingClientRect();
        const pad = 8;
        const gap = 6;

        let left = triggerRect.right - contentRect.width;
        left = Math.max(pad, left);
        left = Math.min(left, window.innerWidth - contentRect.width - pad);

        const spaceBelow = window.innerHeight - triggerRect.bottom - pad;
        const spaceAbove = triggerRect.top - pad;

        let top;
        if (spaceBelow < contentRect.height && spaceAbove >= contentRect.height) {
            top = triggerRect.top - contentRect.height - gap;
        } else {
            top = triggerRect.bottom + gap;
        }

        top = Math.max(
            pad,
            Math.min(top, window.innerHeight - contentRect.height - pad)
        );

        content.style.left = `${Math.round(left)}px`;
        content.style.top = `${Math.round(top)}px`;
    }

    document.addEventListener("DOMContentLoaded", prepareMenus);
    prepareMenus();

    document.addEventListener("click", event => {
        const trigger = event.target.closest(triggerSelector);

        if (trigger) {
            event.preventDefault();
            event.stopPropagation();
            openMenu(trigger);
            return;
        }

        // Portalled menu content is no longer a descendant of the trigger's
        // wrapper, so don't treat clicks inside the panel as outside clicks.
        if (event.target.closest(contentSelector)) return;

        if (!event.target.closest(selector)) {
            closeAll();
        }
    });

    document.addEventListener("keydown", event => {
        if (event.key === "Escape") closeAll();
    });

    // Match Support Requests: close the menu when the table/page moves.
    window.addEventListener("resize", () => closeAll());
    window.addEventListener("scroll", () => closeAll(), true);
})();
