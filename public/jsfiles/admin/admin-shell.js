
/*
 * KNOWURLOCAL admin shell.
 *
 * Keeps the Support Requests badge synchronized while an administrator
 * works elsewhere in the admin module. Echo is optional here: if realtime
 * is unavailable, the server-rendered count still remains authoritative.
 */
(function () {
    const isSupportPage = () => window.location.pathname.endsWith('/support-requests');

    const badges = () => Array.from(document.querySelectorAll('.js-support-pending-badge'));

    const setPendingCount = (count) => {
        const safeCount = Math.max(0, Number.parseInt(count, 10) || 0);

        badges().forEach((badge) => {
            const shouldHide = safeCount < 1 || isSupportPage();
            badge.hidden = shouldHide;
            badge.textContent = safeCount > 99 ? '99+' : String(safeCount);
            badge.setAttribute('aria-label', `${safeCount} pending support requests`);
        });
    };

    const subscribe = () => {
        if (!window.Echo || typeof window.Echo.private !== 'function') {
            return false;
        }

        try {
            window.Echo.private('admin.support-requests')
                .listen('.support.request.created', () => {
                    const current = Number.parseInt(
                        badges()[0]?.textContent?.replace('+', '') || '0',
                        10
                    ) || 0;

                    if (!isSupportPage()) {
                        setPendingCount(current + 1);
                    }
                });

            return true;
        } catch (error) {
            console.warn('Admin support notification channel unavailable.', error);
            return false;
        }
    };

    let attempts = 0;
    const timer = window.setInterval(() => {
        attempts += 1;
        if (subscribe() || attempts >= 20) {
            window.clearInterval(timer);
        }
    }, 500);
})();
