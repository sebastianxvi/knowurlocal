/**
 * KNOWURLOCAL — My Inquiries QR Viewer
 *
 * Loaded as an independent page module so QR preview interaction does not
 * depend on the larger inbox feature loader or realtime ticket renderer.
 */

const modal = document.getElementById('response-qr-lightbox');

if (modal && modal.dataset.initialized !== '1') {
    modal.dataset.initialized = '1';

    const code = modal.querySelector('.response-qr-lightbox-code');
    const title = modal.querySelector('#response-qr-lightbox-title');
    const destination = modal.querySelector('.response-qr-lightbox-destination');
    const closeButton = modal.querySelector('.response-qr-lightbox-close');
    let previousFocus = null;
    let renderToken = 0;

    const close = () => {
        renderToken += 1;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('response-qr-viewer-open');
        code.replaceChildren();
        code.removeAttribute('data-qr-rendered');
        code.dataset.qrValue = '';
        if (previousFocus?.isConnected) previousFocus.focus();
    };

    // Delegation supports both the original ticket DOM and dynamically cloned
    // conversation content. Use the composed path for reliable mobile taps.
    document.addEventListener('click', (event) => {
        const path = typeof event.composedPath === 'function' ? event.composedPath() : [];
        let trigger = path.find((node) => node instanceof Element && node.matches('.response-qr-trigger'));
        if (!trigger) {
            const target = event.target instanceof Element
                ? event.target
                : event.target?.parentElement;
            trigger = target?.closest?.('.response-qr-trigger') || null;
        }
        if (!trigger) return;

        const value = trigger.dataset.qrValue || '';
        if (!isSafeHttpUrl(value)) return;

        event.preventDefault();
        event.stopPropagation();

        previousFocus = trigger;
        title.textContent = trigger.dataset.qrLabel || 'QR code';
        destination.href = value;
        code.replaceChildren();
        code.dataset.qrValue = value;
        code.setAttribute('aria-label', `${title.textContent} QR code`);

        // Reveal the modal immediately. QR matrix generation is synchronous,
        // so defer it one paint to avoid making a mobile tap appear unresponsive.
        renderToken += 1;
        const currentToken = renderToken;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('response-qr-viewer-open');

        window.requestAnimationFrame(() => {
            if (currentToken !== renderToken || !modal.classList.contains('is-open')) return;
            renderInquiryQRCodes(code);
            closeButton?.focus({ preventScroll: true });
        });
    }, true);

    closeButton?.addEventListener('click', (event) => {
        event.preventDefault();
        close();
    });

    modal.addEventListener('click', (event) => {
        if (event.target === modal) close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) close();
    });
}

function isSafeHttpUrl(value) {
    try {
        const url = new URL(value, window.location.origin);
        return ['http:', 'https:'].includes(url.protocol);
    } catch {
        return false;
    }
}

function renderInquiryQRCodes(container) {
    if (!container || container.dataset.qrRendered === '1') return;

    const value = container.dataset.qrValue || '';
    if (!isSafeHttpUrl(value)) return;

    const QRCore = window.QRCodeCore;
    if (typeof QRCore !== 'function') {
        showQrFallback(container, value);
        return;
    }

    try {
        let qr = null;
        let lastError = null;

        for (const level of [QRCore.CorrectLevel?.M, QRCore.CorrectLevel?.L]) {
            if (level === undefined) continue;
            try {
                const candidate = new QRCore(0, level);
                candidate.addData(value);
                candidate.make();
                qr = candidate;
                break;
            } catch (error) {
                lastError = error;
            }
        }

        if (!qr) throw lastError || new Error('Unable to encode QR destination.');

        const count = qr.getModuleCount();
        const quietZone = 4;
        const size = count + quietZone * 2;
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', container.getAttribute('aria-label') || 'Scannable QR code');
        svg.setAttribute('shape-rendering', 'crispEdges');
        svg.classList.add('response-qr-svg');

        const background = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        background.setAttribute('width', String(size));
        background.setAttribute('height', String(size));
        background.setAttribute('fill', '#ffffff');
        svg.appendChild(background);

        for (let row = 0; row < count; row++) {
            for (let col = 0; col < count; col++) {
                if (!qr.isDark(row, col)) continue;
                const module = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                module.setAttribute('x', String(col + quietZone));
                module.setAttribute('y', String(row + quietZone));
                module.setAttribute('width', '1');
                module.setAttribute('height', '1');
                module.setAttribute('fill', '#111827');
                svg.appendChild(module);
            }
        }

        container.replaceChildren(svg);
        container.dataset.qrRendered = '1';
    } catch (error) {
        console.error('Unable to render inquiry QR code:', error);
        showQrFallback(container, value);
    }
}

function showQrFallback(container, value) {
    const link = document.createElement('a');
    link.href = value;
    link.target = '_blank';
    link.rel = 'noopener noreferrer';
    link.className = 'response-qr-open';
    link.textContent = 'Open destination';
    container.replaceChildren(link);
}
