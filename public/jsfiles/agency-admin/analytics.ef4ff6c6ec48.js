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
    let evaluationRatings = JSON.parse(root.dataset.analyticsInitialEvaluationRatings || '{}');
    let evaluationTrend = JSON.parse(root.dataset.analyticsInitialEvaluationTrend || '[]');
    let loading = false;
    let refreshQueued = false;

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

    const renderEvaluationRatings = distribution => {
        const host = root.querySelector('[data-evaluation-rating-chart]');
        if (!host) return;
        host.replaceChildren();
        const values = distribution || {};
        const total = Object.values(values).reduce((sum, value) => sum + Number(value || 0), 0);
        if (total === 0) {
            host.textContent = 'No published evaluations were received during this period.';
            return;
        }
        [5, 4, 3, 2, 1].forEach(rating => {
            const count = Number(values[rating] || 0);
            const row = document.createElement('div');
            row.className = 'analytics-rating-row';
            const label = document.createElement('span');
            label.textContent = `${rating} ★`;
            const track = document.createElement('i');
            track.className = 'analytics-rating-track';
            const fill = document.createElement('em');
            fill.style.width = `${total ? (count / total) * 100 : 0}%`;
            track.append(fill);
            const amount = document.createElement('b');
            amount.textContent = numberFormat.format(count);
            row.append(label, track, amount);
            row.title = `${count} published evaluations rated ${rating} out of 5`;
            host.append(row);
        });
    };

    const renderEvaluationTrend = items => {
        const host = root.querySelector('[data-evaluation-trend-chart]');
        if (!host) return;
        host.replaceChildren();
        if (!Array.isArray(items) || !items.some(item => item.average !== null && item.average !== undefined)) {
            const empty = document.createElement('div');
            empty.className = 'analytics-empty';
            empty.textContent = 'Not enough published evaluations to show a rating trend for this period.';
            host.append(empty);
            return;
        }
        const ns = 'http://www.w3.org/2000/svg';
        const width = 1000, height = 190, pad = { top: 18, right: 24, bottom: 34, left: 42 };
        const innerW = width - pad.left - pad.right, innerH = height - pad.top - pad.bottom;
        const x = index => pad.left + (items.length <= 1 ? innerW / 2 : index / (items.length - 1) * innerW);
        const y = value => pad.top + ((5 - Number(value)) / 4) * innerH;
        const svg = document.createElementNS(ns, 'svg');
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Average published evaluation rating over the selected period');
        svg.classList.add('analytics-rating-trend-svg');
        [1, 3, 5].forEach(value => {
            const line = document.createElementNS(ns, 'line');
            line.setAttribute('x1', pad.left); line.setAttribute('x2', width - pad.right);
            line.setAttribute('y1', y(value)); line.setAttribute('y2', y(value));
            line.setAttribute('class', 'analytics-rating-gridline'); svg.append(line);
            const label = document.createElementNS(ns, 'text');
            label.setAttribute('x', 4); label.setAttribute('y', y(value) + 4);
            label.setAttribute('class', 'analytics-rating-axis-label'); label.textContent = `${value}★`; svg.append(label);
        });
        let segment = [];
        const flush = () => {
            if (segment.length > 1) {
                const path = document.createElementNS(ns, 'path');
                path.setAttribute('d', segment.map((point, i) => `${i ? 'L' : 'M'} ${point.x} ${point.y}`).join(' '));
                path.setAttribute('class', 'analytics-rating-trend-line'); svg.append(path);
            }
            segment = [];
        };
        items.forEach((item, index) => {
            if (item.average === null || item.average === undefined) { flush(); return; }
            const point = { x: x(index), y: y(item.average) }; segment.push(point);
            const circle = document.createElementNS(ns, 'circle');
            circle.setAttribute('cx', point.x); circle.setAttribute('cy', point.y);
            circle.setAttribute('r', items.length > 90 ? 2.2 : 3.8);
            circle.setAttribute('class', 'analytics-rating-trend-point');
            circle.setAttribute('tabindex', '0');
            const pointDescription = `${item.label}: ${Number(item.average).toFixed(2)} / 5 (${numberFormat.format(Number(item.count || 0))} evaluations)`;
            circle.setAttribute('aria-label', pointDescription);
            const title = document.createElementNS(ns, 'title');
            title.textContent = pointDescription;
            circle.append(title); svg.append(circle);
        });
        flush();
        [0, Math.floor((items.length - 1) / 2), items.length - 1].filter((v, i, a) => a.indexOf(v) === i).forEach(index => {
            if (!items[index]) return;
            const label = document.createElementNS(ns, 'text');
            label.setAttribute('x', x(index)); label.setAttribute('y', height - 8);
            label.setAttribute('text-anchor', index === 0 ? 'start' : index === items.length - 1 ? 'end' : 'middle');
            label.setAttribute('class', 'analytics-rating-axis-label'); label.textContent = items[index].label;
            svg.append(label);
        });
        host.append(svg);
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
        svg.setAttribute('aria-label', period === 'all' ? 'Monthly submitted and answered agency support requests' : 'Daily submitted and answered agency support requests');
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
        if (node) node.textContent = `${period === 'all' ? 'Monthly' : 'Daily'} requests submitted and answered during ${label}.`;
        const hint = root.querySelector('[data-trend-hint]'); if (hint) hint.textContent = period === 'all' ? 'Hover a point to see the monthly count' : 'Hover a point to see the daily count';
    };

    const loadPeriod = async () => {
        if (!endpoint) return;
        if (loading) { refreshQueued = true; return; }
        loading = true;
        const requestedPeriod = period;
        const requestedMonth = month;
        root.classList.add('is-loading');
        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('period', requestedPeriod);
            if (requestedPeriod === 'month') url.searchParams.set('month', requestedMonth);
            else url.searchParams.delete('month');
            const response = await fetch(url.toString(), {
                credentials: 'same-origin', cache: 'no-store',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!response.ok) throw new Error(`Analytics request failed: ${response.status}`);
            const payload = await response.json();
            // Ignore stale responses if the administrator changed the period
            // while this request was still in flight.
            if (requestedPeriod !== period || (requestedPeriod === 'month' && requestedMonth !== month)) return;
            trend = payload.trend || [];
            metrics = payload.metrics || {};
            evaluationRatings = payload.evaluation_rating_distribution || {};
            evaluationTrend = payload.evaluation_trend || [];
            setPeriodControls();
            renderTrend();
            updateDescription(payload.period_label || (period === 'month' ? month : ({'30d':'30 days','90d':'90 days','365d':'12 months','all':'All time'}[period] || '7 days')));
            const ratingPeriodLabel = root.querySelector('[data-evaluation-trend-period-label]'); if (ratingPeriodLabel) ratingPeriodLabel.textContent = `${period === 'all' ? 'Monthly' : 'Daily'} average · published evaluations only`;
            setMetric('period_answered', Number(payload.metrics?.period_answered || 0));
            setMetric('evaluation_period_total', Number(metrics.evaluation_period_total || 0));
            setMetric('evaluation_period_responded', Number(metrics.evaluation_period_responded || 0));
            setMetric('evaluation_period_awaiting_response', Number(metrics.evaluation_period_awaiting_response || 0));
            setMetric('evaluation_period_average', metrics.evaluation_period_average === null || metrics.evaluation_period_average === undefined ? '—' : `${Number(metrics.evaluation_period_average).toFixed(1)} / 5`);
            setMetric('evaluation_period_response_rate', `${metrics.evaluation_period_response_rate ?? 0}%`);
            renderEvaluationRatings(evaluationRatings);
            renderEvaluationTrend(evaluationTrend);
            const satisfactionDescription = root.querySelector('.analytics-satisfaction-panel .analytics-panel-head p');
            if (satisfactionDescription) satisfactionDescription.textContent = `Published evaluations received during ${payload.period_label || period}. Only evaluations for your agency are included.`;
            const updated = root.querySelector('[data-last-updated]');
            if (updated) updated.textContent = `Updated ${new Date(payload.updated_at || Date.now()).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
            root.dataset.analyticsCurrentPeriod = period;
            root.dataset.analyticsCurrentMonth = month;
        } catch (error) {
            console.warn('Agency analytics period could not be refreshed.', error);
        } finally {
            loading = false;
            root.classList.remove('is-loading');
            if (refreshQueued) { refreshQueued = false; loadPeriod(); }
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
    renderEvaluationRatings(evaluationRatings);
    renderEvaluationTrend(evaluationTrend);
    root.querySelectorAll('[data-tooltip]').forEach(node => {
        node.addEventListener('focus', () => node.setAttribute('aria-label', node.dataset.tooltip));
        node.addEventListener('mouseenter', () => node.setAttribute('aria-label', node.dataset.tooltip));
    });
})();
