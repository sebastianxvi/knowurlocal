(() => {
    'use strict';

    const root = document.querySelector('[data-analytics-root]');
    if (!root) return;

    const endpoint = root.dataset.analyticsUrl;
    const numberFormat = new Intl.NumberFormat();
    let refreshInFlight = false;
    let currentPeriod = root.dataset.analyticsCurrentPeriod || '7d';
    let currentMonth = root.dataset.analyticsCurrentMonth || new Date().toISOString().slice(0, 7);

    const setMetric = (name, value) => {
        const nodes = root.querySelectorAll(`[data-metric="${name}"]`);
        if (!nodes.length) return;
        const text = typeof value === 'number' ? numberFormat.format(value) : (value ?? '—');
        nodes.forEach(node => { node.textContent = text; });
    };

    const setNote = (name, value) => {
        const node = root.querySelector(`[data-metric-note="${name}"]`);
        if (node && value !== undefined && value !== null) node.textContent = value;
    };

    const renderList = (selector, items, emptyText, renderer) => {
        const list = root.querySelector(selector);
        if (!list) return;
        list.replaceChildren();
        if (!Array.isArray(items) || !items.length) {
            const empty = document.createElement('div');
            empty.className = 'analytics-empty';
            empty.textContent = emptyText;
            list.append(empty);
            return;
        }
        items.forEach((item, index) => list.append(renderer(item, index)));
    };

    const rankingItem = (item, index, subtitle) => {
        const row = document.createElement('div');
        row.className = 'analytics-ranking-item';
        row.innerHTML = `
            <span class="ranking-index">${index + 1}</span>
            <div>
                <strong></strong>
                <small></small>
            </div>
            <b>${numberFormat.format(Number(item.count || 0))}</b>
        `;
        row.querySelector('strong').textContent = item.name || item.question || 'Unavailable';
        row.querySelector('small').textContent = item.agency || subtitle;
        return row;
    };

    const renderTrend = (trend) => {
        const chart = root.querySelector('[data-trend-chart]');
        const grid = chart?.querySelector('.analytics-chart-grid');
        if (!grid || !Array.isArray(trend)) return;

        const max = Math.max(
            1,
            ...trend.flatMap(day => [Number(day.submitted || 0), Number(day.answered || 0)])
        );

        grid.replaceChildren();
        trend.forEach((day) => {
            const item = document.createElement('div');
            item.className = 'analytics-chart-day';
            item.dataset.chartDay = day.date;
            item.title = `${day.label || day.date}: ${Number(day.submitted || 0)} submitted, ${Number(day.answered || 0)} answered`;
            const submitted = Number(day.submitted || 0);
            const answered = Number(day.answered || 0);
            item.innerHTML = `
                <div class="analytics-chart-values">
                    <span class="analytics-chart-value submitted-value">${submitted}</span>
                    <span class="analytics-chart-value answered-value">${answered}</span>
                </div>
                <div class="analytics-bars">
                    <div class="analytics-bar analytics-bar-submitted" data-series="submitted"></div>
                    <div class="analytics-bar analytics-bar-answered" data-series="answered"></div>
                </div>
                <span class="analytics-day-label"></span>
            `;
            item.querySelector('[data-series="submitted"]').style.height = `${Math.max((submitted / max) * 100, 2)}%`;
            item.querySelector('[data-series="answered"]').style.height = `${Math.max((answered / max) * 100, 2)}%`;
            item.querySelector('.analytics-day-label').textContent = day.label || '';
            grid.append(item);
        });

        grid.classList.toggle('is-long-range', trend.length > 14);
        grid.classList.toggle('is-short-range', trend.length <= 7);
    };

    const setPeriodControls = (period, month) => {
        currentPeriod = period || currentPeriod;
        currentMonth = month || currentMonth;

        root.querySelectorAll('[data-analytics-period]').forEach(button => {
            const active = button.dataset.analyticsPeriod === currentPeriod;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
        });

        const monthPicker = root.querySelector('[data-analytics-month]');
        if (monthPicker) {
            monthPicker.value = currentMonth;
            monthPicker.closest('.analytics-month-picker')?.classList.toggle('is-visible', currentPeriod === 'month');
        }
    };

    const setTrendLabels = (payload) => {
        const label = payload.period_label || (currentPeriod === '30d' ? '30 days' : currentPeriod === 'month' ? currentMonth : '7 days');
        const description = root.querySelector('[data-trend-description]');
        const rateLabel = root.querySelector('[data-trend-rate-label]');
        if (description) {
            description.textContent = currentPeriod === 'month'
                ? `Daily activity for ${label}.`
                : `Daily activity for the last ${currentPeriod === '30d' ? '30 days' : '7 days'}.`;
        }
        if (rateLabel) rateLabel.textContent = `${label} answered`;
    };

    const applyPayload = (payload) => {
        const m = payload.metrics || {};
        setPeriodControls(payload.period || currentPeriod, payload.selected_month || currentMonth);
        setTrendLabels(payload);
        const s = payload.status_counts || {};

        Object.entries(m).forEach(([key, value]) => setMetric(key, value));
        setMetric('total_inquiries', m.total_inquiries);
        setMetric('open_inquiries', m.open_inquiries);
        setMetric('answered_inquiries', m.answered_inquiries);
        setMetric('response_rate', `${m.response_rate ?? 0}%`);
        setMetric('average_response_time', m.average_response_time || '—');
        setMetric('seen_answers', m.seen_answers);
        setMetric('unseen_answers', m.unseen_answers);
        setMetric('trend_answered', m.trend_answered ?? 0);
        setMetric('faq_answer_rate', `${m.faq_answer_rate ?? 0}%`);
        setMetric('fallback_rate', `${m.fallback_rate ?? 0}%`);

        setNote('response_rate', `${numberFormat.format(m.answered_inquiries || 0)} answered of ${numberFormat.format(m.total_inquiries || 0)}`);
        setNote('faq_answer_rate', `${numberFormat.format(m.faq_answered || 0)} FAQ-backed answers`);
        setNote('fallback_rate', `${numberFormat.format(m.fallback_questions || 0)} questions not answered by FAQ matching`);
        setNote('complete_agencies', `of ${numberFormat.format(m.total_agencies || 0)} active agencies`);
        setNote('incomplete_faqs', `${numberFormat.format(m.complete_faqs || 0)} complete of ${numberFormat.format(m.total_faqs || 0)}`);

        root.querySelectorAll('[data-status]').forEach(node => {
            node.textContent = numberFormat.format(Number(s[node.dataset.status] || 0));
        });

        renderTrend(payload.trend || []);

        renderList('[data-support-agencies]', payload.support_agencies, 'No agency-linked support requests have been recorded yet.', (item, index) => rankingItem(item, index, 'Support requests'));
        renderList('[data-popular-faqs]', payload.popular_faqs, 'No FAQ usage has been recorded yet.', (item, index) => rankingItem(item, index, item.agency || 'No agency'));
        renderList('[data-popular-agencies]', payload.popular_agencies, 'No agency-related chatbot questions have been recorded yet.', (item, index) => rankingItem(item, index, 'Chatbot questions'));

        const agenciesHealth = root.querySelector('[data-health="agencies"]');
        const faqsHealth = root.querySelector('[data-health="faqs"]');
        const collaborationHealth = root.querySelector('[data-health="collaboration"]');
        if (agenciesHealth) agenciesHealth.textContent = `${m.total_agencies ? Math.round((m.complete_agencies / m.total_agencies) * 100) : 0}%`;
        if (faqsHealth) faqsHealth.textContent = `${m.total_faqs ? Math.round((m.complete_faqs / m.total_faqs) * 100) : 0}%`;
        if (collaborationHealth) collaborationHealth.textContent = `${numberFormat.format(m.collaboration_overdue || 0)} overdue`;

    };

    // Analytics is intentionally not a live dashboard. The server-rendered
    // snapshot remains stable until the administrator changes the reporting
    // period. Only the selected period/month is fetched here.
    const loadPeriod = async () => {
        if (!endpoint || refreshInFlight) return;
        refreshInFlight = true;

        try {
            const url = new URL(endpoint, window.location.origin);
            url.searchParams.set('period', currentPeriod);
            if (currentPeriod === 'month') {
                url.searchParams.set('month', currentMonth);
            } else {
                url.searchParams.delete('month');
            }

            const response = await fetch(url.toString(), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                cache: 'no-store',
            });

            if (!response.ok) {
                throw new Error(`Analytics period load failed: ${response.status}`);
            }

            applyPayload(await response.json());
        } catch (error) {
            console.warn('Analytics period refresh is unavailable.', error);
        } finally {
            refreshInFlight = false;
        }
    };

    root.querySelectorAll('[data-analytics-period]').forEach(button => {
        button.addEventListener('click', () => {
            const nextPeriod = button.dataset.analyticsPeriod;
            if (!nextPeriod || nextPeriod === currentPeriod) return;
            currentPeriod = nextPeriod;
            setPeriodControls(currentPeriod, currentMonth);
            loadPeriod();
        });
    });

    root.querySelector('[data-analytics-month]')?.addEventListener('change', (event) => {
        currentMonth = event.target.value;
        currentPeriod = 'month';
        setPeriodControls(currentPeriod, currentMonth);
        loadPeriod();
    });

    setPeriodControls(currentPeriod, currentMonth);
})();
