(() => {
    'use strict';

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
    const dialog = document.querySelector('[data-kyl-install-dialog]');
    const dialogTitle = dialog?.querySelector('[data-kyl-install-title]');
    const dialogDescription = dialog?.querySelector('[data-kyl-install-description]');
    const dialogSteps = dialog?.querySelector('[data-kyl-install-steps]');
    const dialogNote = dialog?.querySelector('[data-kyl-install-note]');
    const closeDialog = dialog?.querySelector('[data-kyl-install-close]');
    const doneDialog = dialog?.querySelector('[data-kyl-install-done]');

    if (help) help.setAttribute('aria-live', 'polite');

    const ua = navigator.userAgent || '';
    const platform = navigator.platform || '';
    const isNativeAndroidApp = /KNOWURLOCAL-AndroidApp/i.test(ua);
    const isIOS = /iPad|iPhone|iPod/i.test(ua) ||
        (platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const isAndroid = /Android/i.test(ua);
    const isDesktop = !isIOS && !isAndroid;
    const isMacOS = /Macintosh|MacIntel|MacPPC|Mac68K/i.test(platform + ' ' + ua);
    const isFirefox = /Firefox|FxiOS/i.test(ua);
    const isSamsungInternet = /SamsungBrowser/i.test(ua);
    const isSafari = /Safari/i.test(ua) && !/Chrome|CriOS|Edg|FxiOS/i.test(ua);
    const dismissedKey = 'knowurlocal-app-banner-dismissed';
    const androidDownloadUrl = banner.dataset.androidApkUrl || '/app/android/download';
    const androidStatusUrl = banner.dataset.androidApkStatusUrl || '/app/android/download/status';
    let androidApkState = isAndroid && !isNativeAndroidApp ? 'checking' : 'not-applicable';

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

    const showBanner = () => {
        if (!isInstalled() && !wasDismissed()) {
            banner.hidden = false;
            syncBannerLayout();
        }
    };

    const hideBanner = () => {
        banner.hidden = true;
        syncBannerLayout();
    };

    const setDialogContent = ({ title, description, steps, note }) => {
        if (!dialog || !dialogTitle || !dialogDescription || !dialogSteps || !dialogNote) return;
        dialogTitle.textContent = title;
        dialogDescription.textContent = description;
        dialogSteps.replaceChildren();
        steps.forEach(step => {
            const item = document.createElement('li');
            item.textContent = step;
            dialogSteps.appendChild(item);
        });
        dialogNote.textContent = note || '';
        if (typeof dialog.showModal === 'function') {
            if (!dialog.open) dialog.showModal();
        } else {
            // Older embedded browsers: retain a readable, non-script-dependent fallback.
            dialog.setAttribute('open', '');
        }
        closeDialog?.focus();
    };

    const closeInstallDialog = () => {
        if (!dialog) return;
        if (typeof dialog.close === 'function' && dialog.open) dialog.close();
        else dialog.removeAttribute('open');
    };

    const showIOSInstructions = () => setDialogContent({
        title: 'Add KNOWURLOCAL to your Home Screen',
        description: 'There is no separate KNOWURLOCAL iOS app available yet. You can add this website to your iPhone or iPad Home Screen for an app-like shortcut.',
        steps: [
            'Open this page in Safari.',
            'Tap the Share button (the square with the upward arrow).',
            'Scroll the share menu and choose “Add to Home Screen”.',
            'Keep the suggested name KNOWURLOCAL, then tap Add.'
        ],
        note: 'The shortcut opens the KNOWURLOCAL website. On supported iOS versions, it can open in a standalone, app-like view.'
    });

    const showAndroidFallbackInstructions = () => setDialogContent({
        title: 'Add KNOWURLOCAL to your Home Screen',
        description: 'The native Android APK is not currently published for download. You can still add a KNOWURLOCAL shortcut using your browser’s built-in Home Screen or install option.',
        steps: isSamsungInternet ? [
            'Open the Samsung Internet menu.',
            'Choose “Add page to”, then select “Home screen”.',
            'Confirm the shortcut.'
        ] : isFirefox ? [
            'Open the Firefox menu (⋮).',
            'Choose “Install” or “Add to Home screen”.',
            'Follow the confirmation steps.'
        ] : [
            'Open the browser menu (⋮).',
            'Choose “Install app” or “Add to Home screen”.',
            'Confirm the installation.'
        ],
        note: 'This adds the web app or a website shortcut; it does not install the separate Android APK. If your browser offers an Install option, use it to get the app-like PWA experience.'
    });

    const showDesktopInstructions = () => {
        if (isMacOS && isSafari) {
            setDialogContent({
                title: 'Add KNOWURLOCAL to your Mac',
                description: 'Safari on macOS Sonoma or later can add a website to your Dock as a web app.',
                steps: [
                    'Open KNOWURLOCAL in Safari.',
                    'Choose File from the macOS menu bar.',
                    'Select “Add to Dock”.',
                    'Confirm the name and click Add.'
                ],
                note: 'On other desktop browsers, use the browser install option when available, or bookmark KNOWURLOCAL.'
            });
            return;
        }
        setDialogContent({
            title: 'Install KNOWURLOCAL on your computer',
            description: 'Add KNOWURLOCAL as a desktop app for quick access from your computer.',
            steps: [
                'Open KNOWURLOCAL in Chrome or Microsoft Edge.',
                'Look for the install icon in the address bar, or open the browser menu.',
                'Choose “Install KNOWURLOCAL” or “Install this site as an app”.',
                'Confirm the installation.'
            ],
            note: 'If your browser does not offer an install option, you can still bookmark KNOWURLOCAL for quick access.'
        });
    };

    const showGenericWebInstructions = () => setDialogContent({
        title: 'Add KNOWURLOCAL to your device',
        description: 'Your browser does not provide a one-tap installation prompt right now. You can use its menu to add a shortcut.',
        steps: [
            'Open your browser menu.',
            'Look for “Install app”, “Add to Home Screen”, or “Create shortcut”.',
            'Follow the browser instructions to finish.'
        ],
        note: 'The exact wording depends on your browser and device.'
    });

    const showPwaFallback = async () => {
        const promptEvent = installState.deferredPrompt;
        if (!promptEvent) {
            if (isAndroid) showAndroidFallbackInstructions();
            else if (isDesktop) showDesktopInstructions();
            else showGenericWebInstructions();
            return;
        }

        try {
            await promptEvent.prompt();
            const choice = await promptEvent.userChoice;
            installState.deferredPrompt = null;
            if (choice?.outcome === 'accepted') {
                hideBanner();
                if (help) help.hidden = true;
            } else {
                if (isAndroid) showAndroidFallbackInstructions();
                else showDesktopInstructions();
            }
        } catch (_) {
            installState.deferredPrompt = null;
            if (isAndroid) showAndroidFallbackInstructions();
            else showDesktopInstructions();
        }
    };

    const checkAndroidApk = async () => {
        if (!isAndroid || isNativeAndroidApp) return;
        if (action) action.disabled = true;
        if (actionLabel) actionLabel.textContent = 'Checking app...';
        try {
            const response = await fetch(androidStatusUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' }
            });
            if (!response.ok) throw new Error(`APK status returned ${response.status}`);
            const payload = await response.json();
            androidApkState = payload?.available === true ? 'available' : 'unavailable';
        } catch (error) {
            console.warn('[KNOWURLOCAL] Android APK availability check failed:', error);
            androidApkState = 'unavailable';
        } finally {
            if (action) action.disabled = false;
            if (actionLabel) actionLabel.textContent =
                androidApkState === 'available' ? 'Download Android App' : 'Install App';
            syncBannerLayout();
        }
    };

    const requestInstall = async () => {
        if (isIOS) {
            showIOSInstructions();
            return;
        }

        if (isAndroid) {
            if (androidApkState === 'checking') {
                if (help) {
                    help.textContent = 'Checking Android app availability. Please try again in a moment.';
                    help.hidden = false;
                }
                syncBannerLayout();
                return;
            }

            if (androidApkState === 'available') {
                // Verify the download route itself before navigating to the APK.
                // This catches a stale status result, missing file, permission error,
                // or server-side download failure before the browser starts a transfer.
                try {
                    const downloadCheck = await fetch(androidDownloadUrl, {
                        method: 'HEAD',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: { Accept: 'application/vnd.android.package-archive' }
                    });
                    if (!downloadCheck.ok) {
                        throw new Error(`APK download preflight returned ${downloadCheck.status}`);
                    }
                    window.location.assign(androidDownloadUrl);
                    return;
                } catch (error) {
                    console.warn('[KNOWURLOCAL] Android APK download preflight failed; using web-app fallback:', error);
                    androidApkState = 'unavailable';
                }
            }

            // Missing APK or failed preflight: use the browser's PWA prompt when
            // available; otherwise show browser-specific manual Home Screen steps.
            await showPwaFallback();
            return;
        }

        // Desktop/laptop: use the browser's PWA install prompt, never the Android APK.
        await showPwaFallback();
    };

    if (actionLabel) {
        if (isIOS) actionLabel.textContent = 'How to add';
        else if (isAndroid) actionLabel.textContent = 'Download App';
        else actionLabel.textContent = 'Install App';
    }

    if (isNativeAndroidApp) {
        hideBanner();
        document.documentElement.style.setProperty('--kyl-safe-area-inset-top', '0px');
        document.documentElement.style.setProperty('--kyl-safe-area-inset-bottom', '0px');
    } else {
        showBanner();
    }

    if (isAndroid && !isNativeAndroidApp) checkAndroidApk();

    if ('ResizeObserver' in window) {
        const observer = new ResizeObserver(syncBannerLayout);
        observer.observe(banner);
    } else {
        window.addEventListener('resize', syncBannerLayout, { passive: true });
    }

    window.addEventListener('kyl:pwa-install-available', () => {
        if (actionLabel && !(isAndroid && androidApkState === 'available')) {
            actionLabel.textContent = isAndroid ? 'Install App' : 'Install App';
        }
        syncBannerLayout();
    });

    action?.addEventListener('click', requestInstall);

    document.addEventListener('click', event => {
        const trigger = event.target.closest('[data-app-install-trigger]');
        if (!trigger) return;
        event.preventDefault();
        if (isInstalled()) {
            window.location.assign('/');
            return;
        }
        showBanner();
        requestInstall();
    });

    dismiss?.addEventListener('click', () => {
        hideBanner();
        try { sessionStorage.setItem(dismissedKey, '1'); } catch (_) {}
    });

    closeDialog?.addEventListener('click', closeInstallDialog);
    doneDialog?.addEventListener('click', closeInstallDialog);
    dialog?.addEventListener('click', event => {
        if (event.target === dialog) closeInstallDialog();
    });
    dialog?.addEventListener('cancel', event => {
        event.preventDefault();
        closeInstallDialog();
    });

    window.addEventListener('kyl:pwa-installed', () => {
        hideBanner();
        installState.deferredPrompt = null;
        try { localStorage.removeItem(dismissedKey); } catch (_) {}
    });

    window.matchMedia('(display-mode: standalone)').addEventListener?.('change', event => {
        if (event.matches) hideBanner();
    });
})();
