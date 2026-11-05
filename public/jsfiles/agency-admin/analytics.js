(() => {
    'use strict';
    const root = document.querySelector('[data-analytics-root]');
    if (!root) return;

    const endpoint = root.dataset.analyticsUrl;
    const numberFormat = new Intl.NumberFormat();

    const animateInitialMetrics = () => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        root.querySelectorAll('[data-metric]').forEach(node => {
            const original = (node.textContent || '').trim();
            const match = original.match(/^([\d,]+)(.*)$/);
            if (!match) return;
            const target = Number(match[1].replace(/,/g, ''));
            if (!Number.isFinite(target) || target <= 0) return;
            const suffix = match[2];
            const started = performance.now();
            const duration = 650;
            node.textContent = `0${suffix}`;
            const tick = now => {
                const progress = Math.min(1, (now - started) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                node.textContent = `${numberFormat.format(Math.round(target * eased))}${suffix}`;
                if (progress < 1) requestAnimationFrame(tick);
            };
            requestAnimationFrame(tick);
        });
    };

    let period = root.dataset.analyticsCurrentPeriod || '7d';
    let month = root.dataset.analyticsCurrentMonth || new Date().toISOString().slice(0, 7);
    let trend = JSON.parse(root.dataset.analyticsInitialTrend || '[]');
    let metrics = JSON.parse(root.dataset.analyticsInitialMetrics || '{}');
    let loading = false;

    const setPeriodControls = () => {
        root.querySelectorAll('[data-analytics-period]').forEach(button => {
            const active = button.dataset.analyticsPeriod === period;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        const picker = root.querySelector('[data-analytics-month]');
        if (picker) {
            picker.value = month;
            picker.closest('.analytics-month-picker')?.classList.toggle('is-visible', period === 'month');
        }
    };

    const animateMetric = (node, value) => {
        if (!Number.isFinite(value) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            node.textContent = numberFormat.format(value);
            return;
        }
        const start = Number((node.textContent || '0').replace(/[^\d.-]/g, '')) || 0;
        const duration = 650;
        const began = performance.now();
        const tick = now => {
            const progress = Math.min(1, (now - began) / duration);
            const eased = 1 - Math.pow(1 - progress, 3);
            node.textContent = numberFormat.format(Math.round(start + (value - start) * eased));
            if (progress < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    };
    const setMetric = (key, value) => {
        root.querySelectorAll(`[data-metric="${key}"]`).forEach(node => {
            if (typeof value === 'number') animateMetric(node, value);
            else node.textContent = String(value ?? '—');
        });
    };

const smoothPath = points => {
        if (points.length < 2) return points.length ? `M ${points[0][0]} ${points[0][1]}` : '';
        let d = `M ${points[0][0]} ${points[0][1]}`;
        for (let i = 0; i < points.length - 1; i++) {
            const p0 = points[i], p1 = points[i + 1];
            const midX = (p0[0] + p1[0]) / 2;
            d += ` C ${midX} ${p0[1]}, ${midX} ${p1[1]}, ${p1[0]} ${p1[1]}`;
        }
        return d;
    };

    const renderTrend = () => {
        const host = root.querySelector('[data-trend-chart]');
        if (!host) return;
        host.replaceChildren();
        host.classList.remove('is-empty');
        if (!Array.isArray(trend) || trend.length === 0) {
            host.classList.add('is-empty');
            host.textContent = 'No request activity is available for this period.';
            return;
        }

        const width = 1000, height = 310;
        const pad = { top: 25, right: 22, bottom: 42, left: 42 };
        const innerW = width - pad.left - pad.right, innerH = height - pad.top - pad.bottom;
        const maxValue = Math.max(1, ...trend.flatMap(item => [Number(item.submitted || 0), Number(item.answered || 0)]));
        const x = index => pad.left + (trend.length === 1 ? innerW / 2 : index / (trend.length - 1) * innerW);
        const y = value => pad.top + innerH - Number(value || 0) / maxValue * innerH;
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Daily submitted and answered agency support requests');
        svg.classList.add('analytics-trend-svg');

        for (let tick = 0; tick <= 4; tick++) {
            const value = Math.round(maxValue * tick / 4), yy = y(value);
            const line = document.createElementNS(svg.namespaceURI, 'line');
            line.setAttribute('x1', pad.left); line.setAttribute('x2', width - pad.right);
            line.setAttribute('y1', yy); line.setAttribute('y2', yy);
            line.setAttribute('class', 'agency-trend-gridline'); svg.append(line);
            const label = document.createElementNS(svg.namespaceURI, 'text');
            label.setAttribute('x', pad.left - 10); label.setAttribute('y', yy + 4);
            label.setAttribute('text-anchor', 'end'); label.setAttribute('class', 'trend-axis-label');
            label.textContent = String(value); svg.append(label);
        }

        [['submitted', 'submitted'], ['answered', 'answered']].forEach(([key, css]) => {
            const points = trend.map((item, index) => [x(index), y(item[key])]);
            const line = document.createElementNS(svg.namespaceURI, 'path');
            line.setAttribute('d', smoothPath(points)); line.setAttribute('class', `trend-line ${css}`); svg.append(line);
            trend.forEach((item, index) => {
                const circle = document.createElementNS(svg.namespaceURI, 'circle');
                circle.setAttribute('cx', x(index)); circle.setAttribute('cy', y(item[key]));
                circle.setAttribute('r', trend.length > 30 ? 3 : 4);
                circle.setAttribute('class', `trend-point ${css}`);
                circle.setAttribute('tabindex', '0');
                circle.setAttribute('aria-label', `${item.label}: ${item[key]} ${key}`);
                circle.dataset.tip = `${item.label} · ${numberFormat.format(item[key] || 0)} ${key}`;
                circle.setAttribute('data-tip', circle.dataset.tip);
                const title = document.createElementNS(svg.namespaceURI, 'title');
                title.textContent = circle.dataset.tip;
                circle.append(title);
                svg.append(circle);
            });
        });

        const labelEvery = trend.length > 14 ? Math.ceil(trend.length / 7) : 1;
        trend.forEach((item, index) => {
            if (index % labelEvery !== 0 && index !== trend.length - 1) return;
            const label = document.createElementNS(svg.namespaceURI, 'text');
            label.setAttribute('x', x(index)); label.setAttribute('y', height - 12);
            label.setAttribute('text-anchor', 'middle'); label.setAttribute('class', 'trend-date-label');
            label.textContent = item.label; svg.append(label);
        });
        host.append(svg);
    };

    const updateDescription = (label) => {
        const node = root.querySelector('[data-trend-description]');
        if (node) node.textContent = `Daily requests submitted and answered during ${label}.`;
    };

    const loadPeriod = async () => {
        if (!endpoint || loading) return;
        loading = true;
        root.classList.add('is-loading');
        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('period', period);
            if (period === 'month') url.searchParams.set('month', month);
            else url.searchParams.delete('month');
            const response = await fetch(url.toString(), {
                credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error(`Analytics request failed: ${response.status}`);
            const payload = await response.json();
            trend = payload.trend || [];
            setPeriodControls();
            renderTrend();
            updateDescription(payload.period_label || (period === 'month' ? month : period === '30d' ? '30 days' : '7 days'));
            setMetric('period_answered', Number(payload.metrics?.period_answered || 0));
            const updated = root.querySelector('[data-last-updated]');
            if (updated) updated.textContent = `Updated ${new Date(payload.updated_at || Date.now()).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
            root.dataset.analyticsCurrentPeriod = period;
            root.dataset.analyticsCurrentMonth = month;
        } catch (error) {
            console.warn('Agency analytics period could not be refreshed.', error);
        } finally {
            loading = false;
            root.classList.remove('is-loading');
        }
    };

    root.querySelectorAll('[data-analytics-period]').forEach(button => button.addEventListener('click', () => {
        if (button.dataset.analyticsPeriod === period) return;
        period = button.dataset.analyticsPeriod;
        setPeriodControls();
        loadPeriod();
    }));
    root.querySelector('[data-analytics-month]')?.addEventListener('change', event => {
        month = event.target.value;
        period = 'month';
        setPeriodControls();
        loadPeriod();
    });

    animateInitialMetrics();
    setPeriodControls();
    renderTrend();
    root.querySelectorAll('[data-tooltip]').forEach(node => {
        node.addEventListener('focus', () => node.setAttribute('aria-label', node.dataset.tooltip));
        node.addEventListener('mouseenter', () => node.setAttribute('aria-label', node.dataset.tooltip));
    });
})();
