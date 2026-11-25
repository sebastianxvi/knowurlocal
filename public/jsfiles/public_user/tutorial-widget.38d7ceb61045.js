(() => {
    'use strict';

    const init = () => {
        document.querySelectorAll('[data-tutorial-widget]').forEach((root) => {
            if (root.dataset.initialized === 'true') return;
            root.dataset.initialized = 'true';

            const launcher = root.querySelector('[data-tutorial-launcher]');
            const openButton = root.querySelector('.kul-tutorial-open');
            const dismissButton = root.querySelector('.kul-tutorial-dismiss');
            const dialog = root.querySelector('.kul-tutorial-dialog');
            const frame = root.querySelector('[data-tutorial-frame]');
            const modalClose = root.querySelector('.kul-tutorial-modal-close');
            const closeButtons = root.querySelectorAll('[data-tutorial-close]');
            const sessionKey = root.dataset.tutorialSession || 'anonymous';
            const dismissalKey = `kulTutorialDismissedV2:${sessionKey}`;
            const positionKey = 'kulTutorialLauncherPositionV2';
            let pointerStart = null;
            let dragged = false;
            let dragPointerId = null;
            let lastFocused = null;
            let dragResetTimer = null;

            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

            const restorePosition = () => {
                try {
                    const saved = JSON.parse(localStorage.getItem(positionKey) || 'null');
                    if (!saved || !Number.isFinite(saved.x) || !Number.isFinite(saved.y)) return;
                    const maxX = Math.max(8, window.innerWidth - launcher.offsetWidth - 8);
                    const maxY = Math.max(8, window.innerHeight - launcher.offsetHeight - 8);
                    launcher.style.left = `${clamp(saved.x, 8, maxX)}px`;
                    launcher.style.top = `${clamp(saved.y, 8, maxY)}px`;
                    launcher.style.right = 'auto';
                    launcher.style.bottom = 'auto';
                } catch (_) {
                    // Storage may be unavailable; default placement remains usable.
                }
            };

            const savePosition = () => {
                const rect = launcher.getBoundingClientRect();
                try {
                    localStorage.setItem(positionKey, JSON.stringify({ x: rect.left, y: rect.top }));
                } catch (_) {}
            };

            const constrainPosition = () => {
                if (!launcher.style.left || !launcher.style.top) return;
                const rect = launcher.getBoundingClientRect();
                const x = clamp(rect.left, 8, Math.max(8, window.innerWidth - rect.width - 8));
                const y = clamp(rect.top, 8, Math.max(8, window.innerHeight - rect.height - 8));
                launcher.style.left = `${x}px`;
                launcher.style.top = `${y}px`;
            };

            const openDialog = () => {
                lastFocused = document.activeElement;
                dialog.hidden = false;
                dialog.setAttribute('aria-hidden', 'false');
                document.body.classList.add('kul-tutorial-modal-open');
                // Always reload the Drive preview when opening. After closeDialog()
                // removes the iframe src, the iframe.src property may still resolve
                // to the current page URL, so testing `!frame.src` is unreliable.
                // Setting the attribute explicitly also resets a video that reached
                // the end, allowing users to replay it whenever they reopen the guide.
                const videoUrl = frame.dataset.src;
                if (videoUrl) frame.setAttribute('src', videoUrl);
                modalClose.focus();
            };

            const closeDialog = () => {
                if (dialog.hidden) return;
                dialog.hidden = true;
                dialog.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('kul-tutorial-modal-open');
                frame.removeAttribute('src');
                if (lastFocused && typeof lastFocused.focus === 'function') lastFocused.focus();
            };

            try {
                if (sessionStorage.getItem(dismissalKey) === '1') root.hidden = true;
            } catch (_) {}
            restorePosition();

            launcher.addEventListener('pointerdown', (event) => {
                if (event.target.closest('.kul-tutorial-dismiss')) return;
                if (event.pointerType === 'mouse' && event.button !== 0) return;

                const rect = launcher.getBoundingClientRect();
                pointerStart = {
                    x: event.clientX,
                    y: event.clientY,
                    left: rect.left,
                    top: rect.top
                };
                dragged = false;
                dragPointerId = event.pointerId;
                if (launcher.setPointerCapture) launcher.setPointerCapture(event.pointerId);
            });

            launcher.addEventListener('pointermove', (event) => {
                if (!pointerStart || event.pointerId !== dragPointerId) return;
                const dx = event.clientX - pointerStart.x;
                const dy = event.clientY - pointerStart.y;
                if (!dragged && Math.hypot(dx, dy) < 5) return;

                dragged = true;
                launcher.classList.add('is-dragging');
                const x = clamp(pointerStart.left + dx, 8, Math.max(8, window.innerWidth - launcher.offsetWidth - 8));
                const y = clamp(pointerStart.top + dy, 8, Math.max(8, window.innerHeight - launcher.offsetHeight - 8));
                launcher.style.left = `${x}px`;
                launcher.style.top = `${y}px`;
                launcher.style.right = 'auto';
                launcher.style.bottom = 'auto';
            });

            const endDrag = (event) => {
                if (!pointerStart || (event && event.pointerId !== dragPointerId)) return;
                if (dragged) savePosition();
                pointerStart = null;
                dragPointerId = null;
                launcher.classList.remove('is-dragging');

                if (dragResetTimer) window.clearTimeout(dragResetTimer);
                if (dragged) {
                    dragResetTimer = window.setTimeout(() => {
                        dragged = false;
                        dragResetTimer = null;
                    }, 80);
                }
            };

            launcher.addEventListener('pointerup', endDrag);
            launcher.addEventListener('pointercancel', endDrag);
            launcher.addEventListener('lostpointercapture', endDrag);

            openButton.addEventListener('click', (event) => {
                if (dragged) {
                    event.preventDefault();
                    return;
                }
                openDialog();
            });

            dismissButton.addEventListener('click', () => {
                root.hidden = true;
                try {
                    sessionStorage.setItem(dismissalKey, '1');
                } catch (_) {}
            });

            closeButtons.forEach((button) => button.addEventListener('click', closeDialog));

            document.addEventListener('keydown', (event) => {
                if (dialog.hidden) return;
                if (event.key === 'Escape') {
                    closeDialog();
                    return;
                }
                if (event.key !== 'Tab') return;

                const focusable = [...dialog.querySelectorAll('button,[href],iframe,[tabindex]:not([tabindex="-1"])')]
                    .filter((element) => !element.disabled);
                if (!focusable.length) return;
                const first = focusable[0];
                const last = focusable[focusable.length - 1];

                if (event.shiftKey && document.activeElement === first) {
                    event.preventDefault();
                    last.focus();
                } else if (!event.shiftKey && document.activeElement === last) {
                    event.preventDefault();
                    first.focus();
                }
            });

            window.addEventListener('resize', () => {
                constrainPosition();
                if (launcher.style.left) savePosition();
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
