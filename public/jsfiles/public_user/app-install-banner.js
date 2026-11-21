(() => {
    // Capture the browser's install event as early as this shared component loads.
    // beforeinstallprompt is Chromium-family only; other browsers use their own menus.
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
    if ('serviceWorker' in navigator && (window.isSecureContext || window.location.protocol === 'https:')) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/service-worker.js', { scope: '/' }).catch(error => {
                console.warn('[KNOWURLOCAL] Service worker registration failed:', error);
            });
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
    const isNativeAndroidApp = /KNOWURLOCAL-AndroidApp/i.test(navigator.userAgent || '');
    const isAndroidBrowser = /Android/i.test(navigator.userAgent || '') && !isNativeAndroidApp;

    // The native Android shell is already installed. Hide this browser/PWA prompt
    // immediately, before asynchronous install events or status checks can run.
    if (isNativeAndroidApp) {
        banner.hidden = true;
        banner.setAttribute('aria-hidden', 'true');
        document.documentElement.style.setProperty('--kyl-install-banner-height', '0px');
        // MainActivity already applies Android system-bar/cutout insets to the WebView.
        // Prevent page CSS from applying those same safe-area insets a second time.
        document.documentElement.style.setProperty('--kyl-safe-area-inset-top', '0px');
        document.documentElement.style.setProperty('--kyl-safe-area-inset-bottom', '0px');
    }
    const androidDownloadUrl = banner.dataset.androidApkUrl || '/app/android/download';
    const androidStatusUrl = banner.dataset.androidApkStatusUrl || '/app/android/download/status';
    let androidApkState = isAndroidBrowser ? 'checking' : 'unavailable';

    const isInstalled = () =>
        isNativeAndroidApp ||
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
        const platform = navigator.platform || '';
        const isIOS = /iPad|iPhone|iPod/i.test(ua) ||
            (platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        const isAndroid = /Android/i.test(ua);
        const isFirefox = /Firefox|FxiOS/i.test(ua);
        const isSamsungInternet = /SamsungBrowser/i.test(ua);
        const isSafari = /Safari/i.test(ua) && !/Chrome|CriOS|Edg|FxiOS/i.test(ua);
        const isWindows = /Windows NT/i.test(ua);

        if (isIOS) {
            return 'To add KNOWURLOCAL to your Home Screen, tap Share, then choose “Add to Home Screen”. On iOS 16.4 or later, this is also available in Firefox and other supported browsers. On older iOS versions, open the page in Safari.';
        }

        if (isAndroid && isFirefox) {
            return 'In Firefox for Android, tap the three-dot menu (⋮), then choose “Install” (or “Add to Home screen”, depending on your version). Follow the prompt to add KNOWURLOCAL to your Home Screen.';
        }

        if (isAndroid && isSamsungInternet) {
            return 'In Samsung Internet, open the browser menu, choose “Add page to”, then select “Home screen”. Follow the confirmation steps to add KNOWURLOCAL.';
        }

        if (isAndroid) {
            return 'Open your browser menu (⋮) and look for “Install app” or “Add to Home screen”. The exact label depends on your browser. If neither option is available, try Chrome, Firefox for Android, or Samsung Internet.';
        }

        if (isFirefox && isWindows) {
            return 'In Firefox for Windows, look for the web-app icon in the address bar and select it to add KNOWURLOCAL as a web app. This feature requires Firefox 143 or later (150 or later for Microsoft Store installations). If the icon is unavailable, use a supported browser such as Chrome or Edge.';
        }

        if (isFirefox) {
            return 'This Firefox version does not provide a one-click web-app install option on this platform. Open KNOWURLOCAL in Chrome or Edge and use the browser menu’s “Install” option. On macOS Sonoma or later, Safari also offers File → Add to Dock.';
        }

        if (isSafari) {
            return 'In Safari on macOS Sonoma or later, choose File → Add to Dock to use KNOWURLOCAL like an app.';
        }

        return 'Open your browser menu and look for “Install KNOWURLOCAL”, “Install page as app”, or “Add to Home screen”. If your browser does not offer an install option, try Chrome or Edge.';
    };

    const showHelp = message => {
        if (!help) return;
        help.textContent = message || instructions();
        help.hidden = false;
        if (actionLabel) actionLabel.textContent = 'How to install';
        syncBannerLayout();
    };

    if (isAndroidBrowser && action) {
        action.disabled = true;
        if (actionLabel) actionLabel.textContent = 'Checking...';
        fetch(androidStatusUrl, { method: 'GET', credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } })
            .then(response => response.ok ? response.json() : null)
            .then(payload => {
                androidApkState = payload?.available === true ? 'available' : 'unavailable';
                action.disabled = false;
                if (androidApkState === 'available') {
                    if (actionLabel) actionLabel.textContent = 'Download App';
                    if (help) help.hidden = true;
                } else if (installState.deferredPrompt) {
                    if (actionLabel) actionLabel.textContent = 'Install App';
                } else if (actionLabel) {
                    actionLabel.textContent = 'How to install';
                }
                syncBannerLayout();
            })
            .catch(() => {
                androidApkState = 'unavailable';
                if (action) action.disabled = false;
                if (actionLabel) actionLabel.textContent = installState.deferredPrompt ? 'Install App' : 'How to install';
            });
    }

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
        if (actionLabel && !(isAndroidBrowser && androidApkState === 'available')) {
            actionLabel.textContent = 'Install App';
        }
        if (help) help.hidden = true;
        show();
    });

    action?.addEventListener('click', () => {
        requestInstall();
    });

    // Each entry point calls prompt() from its own trusted click handler.
    const requestInstall = async () => {
        if (isAndroidBrowser && androidApkState === 'checking') {
            showHelp('Checking whether the Android app download is available. Please try again in a moment.');
            return;
        }

        if (isAndroidBrowser && androidApkState === 'available') {
            window.location.assign(androidDownloadUrl);
            return;
        }

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
