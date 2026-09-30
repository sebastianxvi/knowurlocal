(() => {
function initFaqFeedbackModal() {
    const backdrop = document.getElementById('faqFeedbackModal');
    if (!backdrop) return;

    // Render the dialog directly under <body> so ancestor stacking contexts,
    // transformed containers, and the FAQ editor backdrop cannot hide it.
    if (backdrop.parentElement !== document.body) {
        document.body.appendChild(backdrop);
    }

    const dialog = backdrop.querySelector('.faq-feedback-modal');
    const questionEl = document.getElementById('faqFeedbackModalQuestion');
    const agencyEl = document.getElementById('faqFeedbackModalAgency');
    const totalEl = document.getElementById('faqFeedbackTotal');
    const likesEl = document.getElementById('faqFeedbackLikes');
    const dislikesEl = document.getElementById('faqFeedbackDislikes');
    const negativeBar = document.getElementById('faqFeedbackNegativeBar');
    const statusEl = document.getElementById('faqFeedbackModalStatus');
    const listEl = document.getElementById('faqFeedbackList');
    const paginationInfo = document.getElementById('faqFeedbackPaginationInfo');
    const previousButton = document.getElementById('faqFeedbackPrev');
    const nextButton = document.getElementById('faqFeedbackNext');
    const tabButtons = Array.from(backdrop.querySelectorAll('[data-feedback-rating]'));
    const closeButtons = Array.from(backdrop.querySelectorAll('[data-faq-feedback-close]'));

    const reasonLabels = {
        incorrect: 'Incorrect information', incomplete: 'Incomplete answer', outdated: 'Outdated information',
        unclear: 'Unclear instructions', attachments: 'Attachment or link issue', other: 'Other reason'
    };
    const state = { url: null, rating: 'all', page: 1, lastPage: 1, loading: false, previousFocus: null, abortController: null };

    const setText = (element, value) => { if (element) element.textContent = value ?? ''; };

    function openModal(button) {
        state.url = button.dataset.feedbackUrl;
        state.rating = 'all';
        state.page = 1;
        state.previousFocus = document.activeElement;
        tabButtons.forEach(tab => tab.classList.toggle('is-active', tab.dataset.feedbackRating === 'all'));
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('faq-feedback-modal-open');
        dialog.focus();
        loadFeedback();
    }

    function closeModal() {
        if (state.abortController) state.abortController.abort();
        state.abortController = null;
        backdrop.classList.remove('is-open');
        backdrop.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('faq-feedback-modal-open');
        if (state.previousFocus && typeof state.previousFocus.focus === 'function') state.previousFocus.focus();
    }

    function renderFeedback(items) {
        listEl.replaceChildren();
        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'faq-feedback-empty';
            empty.textContent = state.rating === 'all' ? 'No feedback has been submitted for this FAQ yet.' : 'No ratings match this filter.';
            listEl.appendChild(empty);
            return;
        }

        items.forEach(item => {
            const card = document.createElement('article');
            card.className = 'faq-feedback-item';
            const head = document.createElement('div');
            head.className = 'faq-feedback-item-head';
            const rating = document.createElement('span');
            const liked = item.rating === 'helpful';
            rating.className = `faq-feedback-item-rating ${liked ? 'is-like' : 'is-dislike'}`;
            const icon = document.createElement('i');
            icon.className = `ph-light ${liked ? 'ph-thumbs-up' : 'ph-thumbs-down'}`;
            icon.setAttribute('aria-hidden', 'true');
            rating.append(icon, document.createTextNode(liked ? 'Liked answer' : 'Disliked answer'));
            const date = document.createElement('time');
            date.className = 'faq-feedback-item-date';
            date.textContent = item.submitted_at || 'Date unavailable';
            head.append(rating, date);
            card.appendChild(head);

            if (item.reason) {
                const reason = document.createElement('div');
                reason.className = 'faq-feedback-item-reason';
                reason.textContent = reasonLabels[item.reason] || 'Feedback reason';
                card.appendChild(reason);
            }
            const comment = document.createElement('p');
            comment.className = 'faq-feedback-item-comment';
            comment.textContent = item.comment?.trim() || 'No written comment provided.';
            card.appendChild(comment);
            listEl.appendChild(card);
        });
    }

    async function loadFeedback() {
        if (!state.url) return;
        if (state.abortController) state.abortController.abort();
        state.abortController = new AbortController();
        const controller = state.abortController;
        state.loading = true;
        listEl.replaceChildren();
        const loading = document.createElement('div');
        loading.className = 'faq-feedback-loading';
        loading.textContent = 'Loading feedback…';
        listEl.appendChild(loading);
        previousButton.disabled = true;
        nextButton.disabled = true;

        try {
            const url = new URL(state.url, window.location.origin);
            url.searchParams.set('rating', state.rating);
            url.searchParams.set('page', String(state.page));
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                signal: controller.signal
            });
            if (!response.ok) throw new Error(response.status === 403 ? 'You do not have permission to view feedback.' : 'Feedback could not be loaded. Please try again.');
            const payload = await response.json();
            if (!backdrop.classList.contains('is-open')) return;

            const faq = payload.faq || {};
            const feedback = payload.feedback || {};
            setText(questionEl, faq.question || 'FAQ question unavailable');
            setText(agencyEl, faq.agency || 'Agency unavailable');
            setText(totalEl, Number(faq.total || 0).toLocaleString());
            setText(likesEl, Number(faq.likes || 0).toLocaleString());
            setText(dislikesEl, Number(faq.dislikes || 0).toLocaleString());
            negativeBar.style.width = `${Math.max(0, Math.min(100, Number(faq.negative_rate || 0)))}%`;

            statusEl.replaceChildren();
            const status = document.createElement('span');
            status.className = 'status-pill';
            if (faq.priority_review) {
                status.classList.add('priority');
                status.textContent = 'Priority review';
            } else if (faq.needs_review) {
                status.textContent = 'Needs review';
            } else if (Number(faq.total || 0) < Number(faq.minimum_ratings || 5)) {
                status.classList.add('collecting');
                status.textContent = `Collecting feedback · ${Number(faq.minimum_ratings || 5)} ratings needed before review`;
            } else {
                status.classList.add('collecting');
                status.textContent = 'No aggregate review flag';
            }
            statusEl.appendChild(status);

            state.lastPage = Math.max(1, Number(feedback.last_page || 1));
            state.page = Math.max(1, Number(feedback.current_page || 1));
            setText(paginationInfo, `${Number(feedback.total || 0).toLocaleString()} matching responses · Page ${state.page} of ${state.lastPage}`);
            previousButton.disabled = state.page <= 1;
            nextButton.disabled = state.page >= state.lastPage;
            renderFeedback(Array.isArray(feedback.data) ? feedback.data : []);
        } catch (error) {
            if (error.name === 'AbortError') return;
            listEl.replaceChildren();
            const message = document.createElement('div');
            message.className = 'faq-feedback-empty';
            message.textContent = error.message || 'Feedback could not be loaded. Please try again.';
            listEl.appendChild(message);
            setText(paginationInfo, '');
        } finally {
            if (state.abortController === controller) {
                state.loading = false;
                state.abortController = null;
            }
        }
    }

    // Delegate in the capture phase so the feedback action is handled before
    // the FAQ row's click-to-view handler can react to the same click. This
    // also supports rows/buttons inserted after initial page load.
    document.addEventListener('click', event => {
        const button = event.target.closest('.faq-feedback-open');
        if (!button) return;

        event.preventDefault();
        // Prevent the same action from reaching other document-level handlers.
        event.stopImmediatePropagation();
        openModal(button);
    }, true);
    closeButtons.forEach(button => button.addEventListener('click', closeModal));
    backdrop.addEventListener('click', event => { if (event.target === backdrop) closeModal(); });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && backdrop.classList.contains('is-open')) closeModal();
    });
    tabButtons.forEach(button => button.addEventListener('click', () => {
        if (state.rating === button.dataset.feedbackRating) return;
        state.rating = button.dataset.feedbackRating;
        state.page = 1;
        tabButtons.forEach(tab => tab.classList.toggle('is-active', tab === button));
        loadFeedback();
    }));
    previousButton.addEventListener('click', () => { if (state.page > 1) { state.page -= 1; loadFeedback(); } });
    nextButton.addEventListener('click', () => { if (state.page < state.lastPage) { state.page += 1; loadFeedback(); } });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initFaqFeedbackModal, { once: true });
} else {
    initFaqFeedbackModal();
}
})();
