(() => {
    'use strict';

    const root = document.querySelector('[data-analytics-root]');
    if (!root) return;

    const endpoint = root.dataset.analyticsUrl;
    const numberFormat = new Intl.NumberFormat();
    let refreshInFlight = false;
    let currentPeriod = root.dataset.analyticsCurrentPeriod || '7d';
    let currentMonth = root.dataset.analyticsCurrentMonth || new Date().toISOString().slice(0, 7);

    const parseJson = (value, fallback) => {
        try { return JSON.parse(value || ''); } catch { return fallback; }
    };

    let state = {
        trend: parseJson(root.dataset.analyticsInitialTrend, []),
        status: parseJson(root.dataset.analyticsInitialStatus, {}),
        methods: parseJson(root.dataset.analyticsInitialChatbotMethods, {}),
        feedback: parseJson(root.dataset.analyticsInitialFeedback, {}),
    };

    const setMetric = (name, value) => {
        root.querySelectorAll(`[data-metric="${name}"]`).forEach(node => {
            node.textContent = typeof value === 'number' ? numberFormat.format(value) : (value ?? '—');
        });
    };

    const setNote = (name, value) => {
        const node = root.querySelector(`[data-metric-note="${name}"]`);
        if (node && value !== undefined && value !== null) node.textContent = value;
    };

    const ensureTooltip = (host) => {
        let tooltip = host.querySelector(':scope > .analytics-chart-tooltip');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.className = 'analytics-chart-tooltip';
            tooltip.setAttribute('role', 'status');
            tooltip.setAttribute('aria-hidden', 'true');
            host.append(tooltip);
        }
        return tooltip;
    };

    const showTooltip = (host, html, x, y = 12) => {
        const tooltip = ensureTooltip(host);
        tooltip.innerHTML = html;
        tooltip.style.left = `${Math.max(10, Math.min(90, x))}%`;
        tooltip.style.top = `${y}px`;
        tooltip.classList.add('is-visible');
        tooltip.setAttribute('aria-hidden', 'false');
    };

    const hideTooltip = (host) => {
        const tooltip = host.querySelector(':scope > .analytics-chart-tooltip');
        if (!tooltip) return;
        tooltip.classList.remove('is-visible');
        tooltip.setAttribute('aria-hidden', 'true');
    };

    const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, char => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[char]));

    const updateLastUpdated = (iso) => {
        const node = root.querySelector('[data-last-updated]');
        if (!node) return;
        const date = iso ? new Date(iso) : new Date();
        node.innerHTML = `<i class="ph-light ph-check-circle" aria-hidden="true"></i> Updated ${date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' })}`;
    };

    const buildTrendChart = (trend) => {
        const host = root.querySelector('[data-trend-chart]');
        if (!host || !Array.isArray(trend)) return;
        host.replaceChildren();
        host.classList.remove('is-empty');
        if (!trend.length) {
            host.classList.add('is-empty');
            host.textContent = 'No request activity is available for this period.';
            return;
        }

        const width = 1000;
        const height = 310;
        const pad = { top: 28, right: 22, bottom: 42, left: 38 };
        const innerW = width - pad.left - pad.right;
        const innerH = height - pad.top - pad.bottom;
        const maxValue = Math.max(1, ...trend.flatMap(d => [Number(d.submitted || 0), Number(d.answered || 0)]));
        const x = index => pad.left + (trend.length === 1 ? innerW / 2 : (index / (trend.length - 1)) * innerW);
        const y = value => pad.top + innerH - (Number(value || 0) / maxValue) * innerH;

        const pointsFor = key => trend.map((d, i) => `${x(i).toFixed(1)},${y(d[key]).toFixed(1)}`).join(' ');
        const submittedPoints = pointsFor('submitted');
        const answeredPoints = pointsFor('answered');
        const area = `${pad.left},${pad.top + innerH} ${submittedPoints} ${pad.left + innerW},${pad.top + innerH}`;

        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.setAttribute('role', 'img');
        svg.setAttribute('aria-label', 'Daily support requests received and answered');
        svg.classList.add('analytics-trend-svg');

        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        defs.innerHTML = `
            <linearGradient id="trendSubmittedFill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-opacity=".18"/><stop offset="100%" stop-opacity="0"/></linearGradient>
        `;
        svg.append(defs);

        const grid = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        grid.classList.add('trend-grid-lines');
        for (let i = 0; i <= 4; i++) {
            const value = Math.round((maxValue / 4) * i);
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            const yy = y(value);
            line.setAttribute('x1', pad.left); line.setAttribute('x2', width - pad.right);
            line.setAttribute('y1', yy); line.setAttribute('y2', yy);
            grid.append(line);
            const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            label.setAttribute('x', pad.left - 10); label.setAttribute('y', yy + 4); label.textContent = value;
            label.classList.add('trend-axis-label');
            grid.append(label);
        }
        svg.append(grid);

        const areaPath = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        areaPath.setAttribute('points', area);
        areaPath.classList.add('trend-area');
        svg.append(areaPath);

        const submittedLine = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        submittedLine.setAttribute('points', submittedPoints); submittedLine.classList.add('trend-line', 'submitted');
        const answeredLine = document.createElementNS('http://www.w3.org/2000/svg', 'polyline');
        answeredLine.setAttribute('points', answeredPoints); answeredLine.classList.add('trend-line', 'answered');
        svg.append(submittedLine, answeredLine);

        const labelEvery = trend.length > 14 ? Math.ceil(trend.length / 7) : 1;
        trend.forEach((day, index) => {
            const cx = x(index);
            const submitted = Number(day.submitted || 0);
            const answered = Number(day.answered || 0);
            const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            group.classList.add('trend-point-group');

            [['submitted', submitted, 'submitted'], ['answered', answered, 'answered']].forEach(([key, value, cls]) => {
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', cx); circle.setAttribute('cy', y(value)); circle.setAttribute('r', '4.5');
                circle.classList.add('trend-point', cls);
                circle.setAttribute('tabindex', '0');
                circle.setAttribute('aria-label', `${day.label}: ${value} ${key}`);
                circle.addEventListener('mouseenter', () => showTooltip(host, `<strong>${escapeHtml(day.label || day.date)}</strong><span>Requests received <b>${submitted}</b></span><span>Requests answered <b>${answered}</b></span><em>${submitted - answered > 0 ? `${submitted - answered} more came in than were answered.` : submitted - answered < 0 ? `${Math.abs(submitted - answered)} more were answered than came in.` : 'Received and answered were equal.'}</em>`, (cx / width) * 100));
                circle.addEventListener('focus', () => showTooltip(host, `<strong>${escapeHtml(day.label || day.date)}</strong><span>Requests received <b>${submitted}</b></span><span>Requests answered <b>${answered}</b></span><em>${submitted - answered > 0 ? `${submitted - answered} more came in than were answered.` : submitted - answered < 0 ? `${Math.abs(submitted - answered)} more were answered than came in.` : 'Received and answered were equal.'}</em>`, (cx / width) * 100));
                circle.addEventListener('mouseleave', () => hideTooltip(host));
                circle.addEventListener('blur', () => hideTooltip(host));
                group.append(circle);
            });
            svg.append(group);

            if (index % labelEvery === 0 || index === trend.length - 1) {
                const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                label.setAttribute('x', cx); label.setAttribute('y', height - 12); label.setAttribute('text-anchor', 'middle');
                label.textContent = day.label || '';
                label.classList.add('trend-date-label');
                svg.append(label);
            }
        });

        host.append(svg);
    };

    const polarPoint = (cx, cy, radius, angle) => [cx + radius * Math.cos(angle), cy + radius * Math.sin(angle)];
    const arcPath = (cx, cy, outer, inner, start, end) => {
        const [x1, y1] = polarPoint(cx, cy, outer, start);
        const [x2, y2] = polarPoint(cx, cy, outer, end);
        const [x3, y3] = polarPoint(cx, cy, inner, end);
        const [x4, y4] = polarPoint(cx, cy, inner, start);
        const large = end - start > Math.PI ? 1 : 0;
        return `M ${x1} ${y1} A ${outer} ${outer} 0 ${large} 1 ${x2} ${y2} L ${x3} ${y3} A ${inner} ${inner} 0 ${large} 0 ${x4} ${y4} Z`;
    };

    const buildDonut = (host, entries, centerLabel, centerValue) => {
        if (!host) return;
        host.replaceChildren();
        const total = entries.reduce((sum, item) => sum + Number(item.value || 0), 0);
        if (!total) {
            host.classList.add('is-empty');
            host.innerHTML = '<span>No data</span>';
            return;
        }
        host.classList.remove('is-empty');
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 200 200'); svg.classList.add('analytics-donut-svg');
        let angle = -Math.PI / 2;
        entries.forEach(entry => {
            const value = Number(entry.value || 0);
            if (!value) return;
            const end = angle + (value / total) * Math.PI * 2;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', arcPath(100, 100, 78, 55, angle, end));
            path.classList.add('donut-segment', entry.className);
            path.setAttribute('tabindex', '0');
            const share = Math.round((value / total) * 100);
            path.addEventListener('mouseenter', () => showTooltip(host, `<strong>${escapeHtml(entry.label)}</strong><span>${numberFormat.format(value)} requests</span><em>${share}% of total</em>`, 50, 0));
            path.addEventListener('focus', () => showTooltip(host, `<strong>${escapeHtml(entry.label)}</strong><span>${numberFormat.format(value)} requests</span><em>${share}% of total</em>`, 50, 0));
            path.addEventListener('mouseleave', () => hideTooltip(host));
            path.addEventListener('blur', () => hideTooltip(host));
            svg.append(path);
            angle = end;
        });
        const center = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        const value = document.createElementNS('http://www.w3.org/2000/svg', 'text'); value.setAttribute('x','100'); value.setAttribute('y','96'); value.setAttribute('text-anchor','middle'); value.classList.add('donut-center-value'); value.textContent = centerValue;
        const label = document.createElementNS('http://www.w3.org/2000/svg', 'text'); label.setAttribute('x','100'); label.setAttribute('y','116'); label.setAttribute('text-anchor','middle'); label.classList.add('donut-center-label'); label.textContent = centerLabel;
        center.append(value, label); svg.append(center); host.append(svg);
    };

    const renderStatus = (status) => {
        const entries = [
            { key:'pending', label:'Pending', className:'pending' },
            { key:'awaiting_confirmation', label:'Waiting for confirmation', className:'confirmation' },
            { key:'needs_follow_up', label:'Needs follow-up', className:'followup' },
            { key:'answered', label:'Answered', className:'answered' },
        ].map(item => ({ ...item, value:Number(status[item.key] || 0) }));
        entries.forEach(item => setMetric(`status_${item.key}`, item.value));
        root.querySelectorAll('[data-status]').forEach(node => { node.textContent = numberFormat.format(Number(status[node.dataset.status] || 0)); });
        const total = entries.reduce((sum, item) => sum + item.value, 0);
        buildDonut(root.querySelector('[data-status-chart]'), entries, 'requests', numberFormat.format(total));
    };

    const renderVisibility = (metrics) => {
        const answered = Number(metrics.answered_inquiries || 0);
        const seen = Number(metrics.seen_answers || 0);
        const unseen = Number(metrics.unseen_answers || 0);
        const rate = answered ? Math.round((seen / answered) * 100) : 0;
        root.querySelector('[data-visibility-rate]')?.replaceChildren(document.createTextNode(`${rate}%`));
        root.querySelector('[data-visibility-note]')?.replaceChildren(document.createTextNode(`${numberFormat.format(seen)} opened · ${numberFormat.format(unseen)} still waiting`));
        const ring = root.querySelector('[data-visibility-ring]'); if (ring) ring.style.setProperty('--value', `${rate}%`);
        const opened = root.querySelector('[data-visibility-opened]'); if (opened) opened.style.width = `${answered ? Math.min(100, (seen / answered) * 100) : 0}%`;
        const unopened = root.querySelector('[data-visibility-unopened]'); if (unopened) unopened.style.width = `${answered ? Math.min(100, (unseen / answered) * 100) : 0}%`;
    };

    const renderMethods = (methods) => {
        const host = root.querySelector('[data-chatbot-method-chart]'); if (!host) return;
        host.replaceChildren();
        const labels = { semantic:'Meaning-based', similarity:'Similar wording', rule:'Rule-based' };
        const classes = { semantic:'semantic', similarity:'similarity', rule:'rule' };
        const entries = Object.keys(labels).map(key => ({ key, label:labels[key], value:Number(methods[key] || 0), className:classes[key] }));
        const total = entries.reduce((sum, item) => sum + item.value, 0);
        if (!total) { host.innerHTML = '<div class="analytics-empty">No FAQ matching data has been recorded yet.</div>'; return; }
        entries.forEach(entry => {
            const row = document.createElement('div'); row.className = 'analytics-method-row';
            row.dataset.tooltip = entry.key === 'semantic' ? `Meaning-based matching: the chatbot understood that the question and FAQ had the same meaning. Used ${numberFormat.format(entry.value)} times (${Math.round((entry.value / total) * 100)}% of recorded matches).` : entry.key === 'similarity' ? `Similar-wording matching: the question closely matched the wording in an FAQ. Used ${numberFormat.format(entry.value)} times (${Math.round((entry.value / total) * 100)}% of recorded matches).` : `Rule-based matching: an older matching rule selected the FAQ. Used ${numberFormat.format(entry.value)} times (${Math.round((entry.value / total) * 100)}% of recorded matches).`;
            row.innerHTML = `<div class="method-label"><span><i class="legend-dot ${entry.className}"></i>${entry.label}</span><b>${numberFormat.format(entry.value)}</b></div><i class="method-track"><em class="${entry.className}" style="width:${(entry.value / total) * 100}%"></em></i>`;
            row.addEventListener('mouseenter', () => showTooltip(host, `<strong>${entry.label}</strong><span>${numberFormat.format(entry.value)} FAQ matches</span><em>${entry.key === 'semantic' ? 'Same meaning, even if wording differs.' : entry.key === 'similarity' ? 'Wording was closely related.' : 'Selected by an older matching rule.'}</em>`, 70, 0));
            row.addEventListener('mouseleave', () => hideTooltip(host));
            host.append(row);
        });
    };

    const renderFeedback = (feedback) => {
        const helpful = Number(feedback.helpful || 0); const notHelpful = Number(feedback.not_helpful || 0); const total = helpful + notHelpful;
        buildDonut(root.querySelector('[data-feedback-chart]'), [
            { label:'Helpful', value:helpful, className:'helpful' },
            { label:'Not helpful', value:notHelpful, className:'not-helpful' },
        ], 'ratings', numberFormat.format(total));
    };

    const renderList = (selector, items, emptyText, subtitle) => {
        const list = root.querySelector(selector); if (!list) return;
        list.replaceChildren();
        if (!Array.isArray(items) || !items.length) { const empty = document.createElement('div'); empty.className = 'analytics-empty'; empty.textContent = emptyText; list.append(empty); return; }
        items.forEach((item, index) => {
            const row = document.createElement('div'); row.className = 'analytics-list-item';
            const name = item.name || item.question || 'Unavailable';
            const secondary = item.agency || subtitle;
            const count = Number(item.count || 0);
            row.dataset.tooltip = `${name}: ${numberFormat.format(count)} ${subtitle.toLowerCase()}.`;
            row.innerHTML = `<span class="list-rank">${index + 1}</span><div><strong></strong><small></small></div><b>${numberFormat.format(count)}</b>`;
            row.querySelector('strong').textContent = name; row.querySelector('small').textContent = secondary; list.append(row);
        });
    };

    const renderSupportAgencies = (items) => {
        const list = root.querySelector('[data-support-agencies]'); if (!list) return;
        list.replaceChildren();
        if (!Array.isArray(items) || !items.length) { list.innerHTML = '<div class="analytics-empty">No agency-linked support requests have been recorded yet.</div>'; return; }
        const max = Math.max(1, ...items.map(item => Number(item.count || 0)));
        items.forEach((item, index) => {
            const count = Number(item.count || 0); const row = document.createElement('div'); row.className = 'analytics-ranking-bar';
            row.dataset.tooltip = `${numberFormat.format(count)} support requests linked to ${item.name || 'Unassigned agency'}.`;
            row.innerHTML = `<div class="rank-label"><span>${index + 1}</span><strong></strong><b>${numberFormat.format(count)}</b></div><i><em style="width:${(count / max) * 100}%"></em></i>`;
            row.querySelector('strong').textContent = item.name || 'Unassigned agency'; list.append(row);
        });
    };

    const setPeriodControls = (period, month) => {
        currentPeriod = period || currentPeriod; currentMonth = month || currentMonth;
        root.querySelectorAll('[data-analytics-period]').forEach(button => {
            const active = button.dataset.analyticsPeriod === currentPeriod; button.classList.toggle('is-active', active); button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        const picker = root.querySelector('[data-analytics-month]');
        if (picker) { picker.value = currentMonth; picker.closest('.analytics-month-picker')?.classList.toggle('is-visible', currentPeriod === 'month'); }
    };

    const setTrendLabels = payload => {
        const label = payload.period_label || (currentPeriod === 'month' ? currentMonth : currentPeriod === '30d' ? '30 days' : '7 days');
        const description = root.querySelector('[data-trend-description]'); if (description) description.textContent = `Daily requests submitted and answered during ${label}.`;
    };

    const applyPayload = payload => {
        const m = payload.metrics || {};
        state.trend = payload.trend || [];
        state.status = payload.status_counts || {};
        state.methods = payload.chatbot_match_methods || {};
        state.feedback = payload.feedback_breakdown || {};
        setPeriodControls(payload.period || currentPeriod, payload.selected_month || currentMonth);
        setTrendLabels(payload);
        Object.entries(m).forEach(([key, value]) => setMetric(key, value));
        setMetric('response_rate', `${m.response_rate ?? 0}%`);
        setMetric('faq_answer_rate', `${m.faq_answer_rate ?? 0}%`);
        setMetric('fallback_rate', `${m.fallback_rate ?? 0}%`);
        setNote('response_rate', `${numberFormat.format(m.answered_inquiries || 0)} of ${numberFormat.format(m.total_inquiries || 0)} requests are answered`);
        setNote('faq_answer_rate', `${numberFormat.format(m.faq_answered || 0)} questions answered from saved FAQs`);
        setNote('fallback_rate', `${numberFormat.format(m.fallback_questions || 0)} questions without a matching FAQ`);
        setNote('complete_agencies', `of ${numberFormat.format(m.total_agencies || 0)} active agencies`);
        setNote('incomplete_faqs', `${numberFormat.format(m.complete_faqs || 0)} complete of ${numberFormat.format(m.total_faqs || 0)}`);
        buildTrendChart(state.trend);
        renderStatus(state.status);
        renderVisibility(m);
        renderMethods(state.methods);
        renderFeedback(state.feedback);
        renderSupportAgencies(payload.support_agencies || []);
        renderList('[data-popular-faqs]', payload.popular_faqs, 'No FAQ usage has been recorded yet.', 'FAQ selections');
        renderList('[data-popular-agencies]', payload.popular_agencies, 'No agency-related chatbot questions have been recorded yet.', 'Chatbot questions');
        const agencyHealth = root.querySelector('[data-health="agencies"]'); if (agencyHealth) agencyHealth.textContent = `${m.total_agencies ? Math.round((m.complete_agencies / m.total_agencies) * 100) : 0}%`;
        const faqHealth = root.querySelector('[data-health="faqs"]'); if (faqHealth) faqHealth.textContent = `${m.total_faqs ? Math.round((m.complete_faqs / m.total_faqs) * 100) : 0}%`;
        const collab = root.querySelector('[data-health="collaboration"]'); if (collab) collab.textContent = `${numberFormat.format(m.collaboration_overdue || 0)} overdue`;
        updateLastUpdated(payload.updated_at);
    };

    const loadPeriod = async () => {
        if (!endpoint || refreshInFlight) return;
        refreshInFlight = true;
        root.classList.add('is-loading');
        try {
            const url = new URL(endpoint, window.location.origin); url.searchParams.set('period', currentPeriod);
            if (currentPeriod === 'month') url.searchParams.set('month', currentMonth); else url.searchParams.delete('month');
            const response = await fetch(url.toString(), { headers: { Accept:'application/json', 'X-Requested-With':'XMLHttpRequest' }, credentials:'same-origin', cache:'no-store' });
            if (!response.ok) throw new Error(`Analytics period load failed: ${response.status}`);
            applyPayload(await response.json());
        } catch (error) {
            console.warn('Analytics period refresh is unavailable.', error);
        } finally { refreshInFlight = false; root.classList.remove('is-loading'); }
    };

    root.querySelectorAll('[data-analytics-period]').forEach(button => button.addEventListener('click', () => {
        const next = button.dataset.analyticsPeriod; if (!next || next === currentPeriod) return; currentPeriod = next; setPeriodControls(currentPeriod, currentMonth); loadPeriod();
    }));
    root.querySelector('[data-analytics-month]')?.addEventListener('change', event => { currentMonth = event.target.value; currentPeriod = 'month'; setPeriodControls(currentPeriod, currentMonth); loadPeriod(); });

    // Tooltips for every metric/list card use the same accessible interaction model.
    root.querySelectorAll('[data-tooltip]').forEach(node => {
        const tip = node.dataset.tooltip; if (!tip) return;
        node.addEventListener('mouseenter', () => node.setAttribute('aria-label', tip));
        node.addEventListener('focus', () => node.setAttribute('aria-label', tip));
    });

    setPeriodControls(currentPeriod, currentMonth);
    buildTrendChart(state.trend);
    renderStatus(state.status);
    renderMethods(state.methods);
    renderFeedback(state.feedback);
})();
