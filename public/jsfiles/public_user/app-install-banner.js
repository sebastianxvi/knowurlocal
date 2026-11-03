(() => {
    // Capture the browser's install event as early as this shared component loads.
    // The event is only available when the browser considers this site installable.
    const installState = window.__kylPwaInstallState ||
        (window.__kylPwaInstallState = { deferredPrompt: null });

    if (!window.__kylBeforeInstallPromptBound) {
        window.__kylBeforeInstallPromptBound = true;
        window.addEventListener('beforeinstallprompt', event => {
            event.preventDefault();
            installState.deferredPrompt = event;
            window.dispatchEvent(new CustomEvent('kyl:pwa-install-available'));
        }, { capture: true });

        window.addEventListener('appinstalled', () => {
            installState.deferredPrompt = null;
            window.dispatchEvent(new CustomEvent('kyl:pwa-installed'));
        });
    }

    // Online-only service worker: no pages or private data are cached.
    if ('serviceWorker' in navigator && window.location.protocol === 'https:') {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).catch(() => {});
        }, { once: true });
    }

    const banner = document.querySelector('[data-app-install-banner]');
    if (!banner || banner.dataset.initialized === 'true') return;
    banner.dataset.initialized = 'true';

    const action = banner.querySelector('[data-app-install-action]');
    const dismiss = banner.querySelector('[data-app-install-dismiss]');
    const help = banner.querySelector('[data-app-install-help]');
    const actionLabel = action?.querySelector('span');
    if (help) help.setAttribute('aria-live', 'polite');

    const dismissedKey = 'knowurlocal-app-banner-dismissed';

    const isInstalled = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.matchMedia('(display-mode: fullscreen)').matches ||
        window.navigator.standalone === true;

    const wasDismissed = () => {
        try { return window.sessionStorage.getItem(dismissedKey) === '1'; }
        catch (_) { return false; }
    };

    const syncBannerLayout = () => {
        const height = banner.hidden ? 0 : Math.ceil(banner.getBoundingClientRect().height);
        document.documentElement.style.setProperty('--kyl-install-banner-height', `${height}px`);
    };

    const show = () => {
        if (!isInstalled() && !wasDismissed()) {
            banner.hidden = false;
            syncBannerLayout();
        }
    };

    const hide = () => {
        banner.hidden = true;
        syncBannerLayout();
    };

    const instructions = () => {
        const ua = navigator.userAgent || '';
        const ios = /iPad|iPhone|iPod/.test(ua) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

        if (ios) {
            return 'To install: open this page in Safari, tap Share, then choose “Add to Home Screen”.';
        }
        if (/Android/i.test(ua)) {
            return 'Your browser did not offer the install prompt. In Chrome, open ⋮ and choose “Install app” or “Add to Home screen”.';
        }
        return 'Your browser did not offer the install prompt. In Chrome or Edge, open the browser menu and choose “Install KNOWURLOCAL” or “Install page as app”.';
    };

    const showHelp = message => {
        if (!help) return;
        help.textContent = message || instructions();
        help.hidden = false;
        if (actionLabel) actionLabel.textContent = 'Install help';
        syncBannerLayout();
    };

    if (isInstalled()) hide();
    else show();

    if ('ResizeObserver' in window) {
        const bannerObserver = new ResizeObserver(syncBannerLayout);
        bannerObserver.observe(banner);
    } else {
        window.addEventListener('resize', syncBannerLayout, { passive: true });
    }

    // Re-render state if the install event arrives after this component initializes.
    window.addEventListener('kyl:pwa-install-available', () => {
        if (actionLabel) actionLabel.textContent = 'Install App';
        if (help) help.hidden = true;
        show();
    });

    action?.addEventListener('click', () => {
        requestInstall();
    });

    // Each entry point calls prompt() from its own trusted click handler.
    const requestInstall = async () => {
        const promptEvent = installState.deferredPrompt;

        if (!promptEvent) {
            banner.hidden = false;
            syncBannerLayout();
            showHelp();
            return;
        }

        try {
            await promptEvent.prompt();
            const choice = await promptEvent.userChoice;
            installState.deferredPrompt = null;

            if (choice?.outcome === 'accepted') {
                hide();
                if (help) help.hidden = true;
            } else {
                banner.hidden = false;
                syncBannerLayout();
                showHelp('Installation was not completed. You can try again from your browser menu.');
                if (actionLabel) actionLabel.textContent = 'Install App';
            }
        } catch (_) {
            installState.deferredPrompt = null;
            banner.hidden = false;
            syncBannerLayout();
            showHelp();
        }
    };

    // Delegated handling is required because this shared script is loaded
    // near the top of the page, before landing navigation/footer links exist.
    // Keep prompt() directly within the trusted click event.
    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-app-install-trigger]');
        if (!trigger) return;

        event.preventDefault();

        if (isInstalled()) {
            // On an already-installed PWA, the page is already running as the app.
            // From a normal browser tab, avoid a misleading install-help message.
            window.location.assign('/');
            return;
        }

        banner.hidden = false;
        syncBannerLayout();
        requestInstall();
    });

    dismiss?.addEventListener('click', () => {
        hide();
        try { sessionStorage.setItem(dismissedKey, '1'); } catch (_) {}
    });

    window.addEventListener('kyl:pwa-installed', () => {
        hide();
        installState.deferredPrompt = null;
        try { localStorage.removeItem(dismissedKey); } catch (_) {}
    });

    window.matchMedia('(display-mode: standalone)').addEventListener?.('change', event => {
        if (event.matches) hide();
    });
})();
