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
            const video = root.querySelector('[data-tutorial-video]');
            const modalClose = root.querySelector('.kul-tutorial-modal-close');
            const closeButtons = root.querySelectorAll('[data-tutorial-close]');
            const sessionKey = root.dataset.tutorialSession || 'anonymous';
            const dismissalKey = `kulTutorialDismissedV2:${sessionKey}`;
            const positionKey = 'kulTutorialLauncherPositionV3';
            const legacyPositionKey = 'kulTutorialLauncherPositionV2';
            let pointerStart = null;
            let dragged = false;
            let dragPointerId = null;
            let lastFocused = null;
            let dragResetTimer = null;

            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

            const getSafeInsets = () => {
                const styles = window.getComputedStyle(root);
                const readInset = (name, fallback = 12) => {
                    const value = Number.parseFloat(styles.getPropertyValue(name));
                    return Number.isFinite(value) ? value : fallback;
                };
                return {
                    top: readInset('--kul-tutorial-safe-top'),
                    right: readInset('--kul-tutorial-safe-right'),
                    bottom: readInset('--kul-tutorial-safe-bottom'),
                    left: readInset('--kul-tutorial-safe-left')
                };
            };

            const setEdgePosition = (side, top) => {
                const insets = getSafeInsets();
                const minY = insets.top;
                const maxY = Math.max(minY, window.innerHeight - launcher.offsetHeight - insets.bottom);
                const safeTop = clamp(Number.isFinite(top) ? top : maxY, minY, maxY);
                launcher.style.top = `${safeTop}px`;
                launcher.style.bottom = 'auto';
                if (side === 'left') {
                    launcher.style.left = `${insets.left}px`;
                    launcher.style.right = 'auto';
                    launcher.dataset.dockSide = 'left';
                } else {
                    launcher.style.left = 'auto';
                    launcher.style.right = `${insets.right}px`;
                    launcher.dataset.dockSide = 'right';
                }
                return { side, y: safeTop };
            };

            const getCurrentSide = () => {
                if (launcher.dataset.dockSide === 'left' || launcher.dataset.dockSide === 'right') {
                    return launcher.dataset.dockSide;
                }
                const rect = launcher.getBoundingClientRect();
                return rect.left + rect.width / 2 < window.innerWidth / 2 ? 'left' : 'right';
            };

            const savePosition = () => {
                const rect = launcher.getBoundingClientRect();
                const side = getCurrentSide();
                try {
                    localStorage.setItem(positionKey, JSON.stringify({ side, y: rect.top }));
                } catch (_) {}
            };

            const snapToNearestEdge = () => {
                const rect = launcher.getBoundingClientRect();
                const side = rect.left + rect.width / 2 < window.innerWidth / 2 ? 'left' : 'right';
                setEdgePosition(side, rect.top);
                savePosition();
            };

            const restorePosition = () => {
                try {
                    const saved = JSON.parse(localStorage.getItem(positionKey) || localStorage.getItem(legacyPositionKey) || 'null');
                    if (!saved) return;

                    // Migrate positions saved by the previous free-floating launcher.
                    if (saved.side === 'left' || saved.side === 'right') {
                        setEdgePosition(saved.side, Number.isFinite(saved.y) ? saved.y : undefined);
                    } else if (Number.isFinite(saved.x) && Number.isFinite(saved.y)) {
                        const side = saved.x + launcher.offsetWidth / 2 < window.innerWidth / 2 ? 'left' : 'right';
                        setEdgePosition(side, saved.y);
                    }
                } catch (_) {
                    // Storage may be unavailable; default placement remains usable.
                }
            };

            const constrainPosition = () => {
                const side = getCurrentSide();
                const rect = launcher.getBoundingClientRect();
                setEdgePosition(side, rect.top);
            };

            const openDialog = () => {
                lastFocused = document.activeElement;
                dialog.hidden = false;
                dialog.setAttribute('aria-hidden', 'false');
                document.body.classList.add('kul-tutorial-modal-open');
                // Load metadata only when the guide is opened. Native browser controls
                // provide the most reliable playback behavior across mobile browsers.
                if (video && video.readyState === 0) video.load();
                modalClose.focus();
            };

            const closeDialog = () => {
                if (dialog.hidden) return;
                dialog.hidden = true;
                dialog.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('kul-tutorial-modal-open');
                // Stop playback and return to the beginning when the guide closes.
                if (video) {
                    video.pause();
                    try { video.currentTime = 0; } catch (_) {}
                }
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
                // Do not capture the pointer on the launcher: pointer capture can
                // retarget the click to this wrapper instead of its child button.
            });

            window.addEventListener('pointermove', (event) => {
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
                if (dragged) snapToNearestEdge();
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

            window.addEventListener('pointerup', endDrag);
            window.addEventListener('pointercancel', endDrag);

            launcher.addEventListener('click', (event) => {
                if (dragged) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            }, true);

            openButton.addEventListener('click', (event) => {
                if (dragged) {
                    event.preventDefault();
                    event.stopPropagation();
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

                const focusable = [...dialog.querySelectorAll('button,[href],video[controls],[tabindex]:not([tabindex="-1"])')]
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
