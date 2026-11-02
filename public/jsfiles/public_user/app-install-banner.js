(() => {
    // Keep installation support available from every public page; no pages or
    // private data are cached by this online-only service worker.
    if ("serviceWorker" in navigator && window.location.protocol === "https:") {
        window.addEventListener("load", () => {
            navigator.serviceWorker.register("/service-worker.js", { scope: "/" }).catch(() => {});
        }, { once: true });
    }

    const banner = document.querySelector('[data-app-install-banner]');
    if (!banner || banner.dataset.initialized === 'true') return;
    banner.dataset.initialized = 'true';

    const action = banner.querySelector('[data-app-install-action]');
    const dismiss = banner.querySelector('[data-app-install-dismiss]');
    const help = banner.querySelector('[data-app-install-help]');
    let deferredPrompt = null;
    const dismissedKey = 'knowurlocal-app-banner-dismissed';

    const isInstalled = () =>
        window.matchMedia('(display-mode: standalone)').matches ||
        window.matchMedia('(display-mode: fullscreen)').matches ||
        window.navigator.standalone === true;

    const wasDismissed = () => {
        try { return window.localStorage.getItem(dismissedKey) === '1'; }
        catch (_) { return false; }
    };

    const show = () => {
        if (!isInstalled() && !wasDismissed()) {
            banner.hidden = false;
            syncBannerLayout();
        }
    };
    const syncBannerLayout = () => {
        // The banner is in normal document flow. Publish its measured height
        // so fixed navigation and full-screen map surfaces start below it.
        const height = banner.hidden ? 0 : Math.ceil(banner.getBoundingClientRect().height);
        document.documentElement.style.setProperty('--kyl-install-banner-height', `${height}px`);
    };

    const hide = () => {
        banner.hidden = true;
        syncBannerLayout();
    };

    const instructions = () => {
        const ua = navigator.userAgent || '';
        const ios = /iPad|iPhone|iPod/.test(ua) ||
            (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        if (ios) return 'In Safari, tap Share, then choose “Add to Home Screen” to install KNOWURLOCAL.';
        if (/Android/i.test(ua)) return 'In your browser menu, choose “Install app” or “Add to Home screen”.';
        return 'In Chrome or Edge, open the browser menu and choose “Install KNOWURLOCAL” or “Install page as app”.';
    };

    if (isInstalled()) hide();
    else show();

    // Keep fixed surfaces aligned after responsive wrapping, orientation changes,
    // browser zoom, font loading, or dynamic banner help text.
    if ('ResizeObserver' in window) {
        const bannerObserver = new ResizeObserver(syncBannerLayout);
        bannerObserver.observe(banner);
    } else {
        window.addEventListener('resize', syncBannerLayout, { passive: true });
    }

    window.addEventListener('beforeinstallprompt', event => {
        event.preventDefault();
        deferredPrompt = event;
        show();
    });

    action?.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const choice = await deferredPrompt.userChoice;
            deferredPrompt = null;
            if (choice?.outcome === 'accepted') hide();
        } else if (help) {
            help.textContent = instructions();
            help.hidden = false;
        }
    });

    dismiss?.addEventListener('click', () => {
        hide();
        try { localStorage.setItem(dismissedKey, '1'); } catch (_) {}
    });

    window.addEventListener('appinstalled', () => {
        hide();
        deferredPrompt = null;
        try { localStorage.removeItem(dismissedKey); } catch (_) {}
    });

    window.matchMedia('(display-mode: standalone)').addEventListener?.('change', event => {
        if (event.matches) hide();
    });
})();
