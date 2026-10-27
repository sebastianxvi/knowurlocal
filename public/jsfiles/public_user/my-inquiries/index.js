import { renderInquiryQRCodes } from './components/inquiry-card.js';

import {
    initializeFilters,
} from './components/filters.js';

import {
    initializeConfirmation,
} from './components/confirmation.js';

import {
    initializeLightbox,
} from './components/lightbox.js';

import {
    initializeRealtime,
} from './components/realtime.js';
import { initializeCreateTicket } from './components/create-ticket.js';


/*
|--------------------------------------------------------------------------
| INITIALIZE MY INQUIRIES MODULES
|--------------------------------------------------------------------------
|
| Each feature owns its own interaction logic.
|
| This file acts as the single entry point for the page and keeps the
| initialization order explicit and predictable.
|--------------------------------------------------------------------------
*/

initializeMessengerInbox();

initializeFilters();

initializeConfirmation();

initializeLightbox();

initializeRealtime();
initializeCreateTicket();

// Render QR codes already present in the server-rendered inquiry cards.
renderInquiryQRCodes(document);


function initializeMessengerInbox() {
    const originalList = document.querySelector('[data-messenger-chat-list]');
    const pane = document.querySelector('[data-messenger-conversation-content]');
    const welcome = document.querySelector('[data-messenger-welcome]');
    const layout = document.querySelector('.messenger-inquiries');
    if (!originalList || !pane || !layout) return;

    const cards = Array.from(originalList.querySelectorAll('.inquiry-card'));
    if (!cards.length) return;

    // Keep the original server-rendered records in the DOM so existing filters,
    // response-confirmation handlers, realtime updates and read tracking continue
    // to operate on the canonical ticket markup.
    const source = document.createElement('div');
    source.className = 'messenger-source-records';
    source.setAttribute('aria-label', 'Ticket records used by inquiry filters');
    cards.forEach(card => source.appendChild(card));
    originalList.replaceChildren(source);

    const agencyKey = card => {
        // Prefer the stable database relationship ID so abbreviations or renamed
        // agency labels cannot accidentally merge two different agencies.
        return card.dataset.agencyId
            ? `agency:${card.dataset.agencyId}`
            : (card.querySelector('.inquiry-agency')?.textContent.trim()
                || card.querySelector('.messenger-agency-name')?.textContent.trim()
                || 'agency').toLocaleLowerCase();
    };
    const agencyName = card => {
        const fullName = card.querySelector('.inquiry-agency')?.textContent.trim();
        const abbreviation = card.querySelector('.messenger-agency-name')?.textContent.trim();
        return fullName || abbreviation || 'Agency';
    };
    const latestTime = card => {
        const time = card.querySelector('.messenger-row-time, .inquiry-date');
        return time?.getAttribute('datetime') || time?.textContent.trim() || '';
    };
    const latestActivity = group => group.slice().sort((a, b) => {
        const av = Date.parse(latestTime(a)) || 0;
        const bv = Date.parse(latestTime(b)) || 0;
        return bv - av;
    })[0];
    const preview = card => {
        const value = card.querySelector('.messenger-row-preview')?.textContent.trim()
            || card.querySelector('.inquiry-question')?.textContent.trim()
            || 'Ticket submitted';
        return value.length > 58 ? `${value.slice(0, 55)}…` : value;
    };

    let selectedKey = null;
    const pageParams = new URLSearchParams(window.location.search);
    const initialTicketId = pageParams.get('ticket') || pageParams.get('ticket_created');
    const expandedStateKey = 'knowurlocal:my-inquiries:expanded-tickets';
    const readExpandedState = () => {
        try { return JSON.parse(localStorage.getItem(expandedStateKey) || '{}') || {}; }
        catch (_) { return {}; }
    };
    const saveExpandedState = (ticketId, expanded) => {
        try {
            const state = readExpandedState();
            state[String(ticketId)] = Boolean(expanded);
            localStorage.setItem(expandedStateKey, JSON.stringify(state));
        } catch (_) { /* Storage may be disabled; accordion still works for this view. */ }
    };

    const markTicketResponseSeen = async (card, ticket) => {
        if (!card?.querySelector('[data-unread-response], .inquiry-unread-badge')) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!csrf) return;
        try {
            const response = await fetch(`/my-inquiries/${encodeURIComponent(card.dataset.id)}/seen`, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            });
            if (!response.ok) return;
            card.querySelectorAll('[data-unread-response], .inquiry-unread-badge').forEach(node => node.remove());
            card.dataset.answerSeen = '1';
            ticket?.querySelector('.messenger-unread-dot')?.remove();
            window.dispatchEvent(new CustomEvent('inquiry:notification-changed'));
        } catch (error) {
            console.warn('Unable to mark ticket response as seen.', error);
        }
    };

    function visibleCards() {
        return cards.filter(card => !card.hidden && card.style.display !== 'none');
    }

    function makeAvatar(card) {
        const avatar = card.querySelector('.messenger-avatar')?.cloneNode(true);
        if (avatar) {
            avatar.classList.add('messenger-agency-avatar');
            return avatar;
        }
        const fallback = document.createElement('div');
        fallback.className = 'messenger-avatar messenger-avatar-fallback';
        fallback.textContent = 'AG';
        return fallback;
    }

    function groupsFromVisible() {
        const groups = new Map();
        visibleCards().forEach(card => {
            const key = agencyKey(card);
            if (!groups.has(key)) groups.set(key, []);
            groups.get(key).push(card);
        });
        return groups;
    }

    function openAgency(key, updateHistory = false) {
        const groups = groupsFromVisible();
        const group = groups.get(key);
        if (!group?.length) return;
        selectedKey = key;

        originalList.querySelectorAll('.messenger-agency-row').forEach(row => {
            const selected = row.dataset.agencyKey === key;
            row.classList.toggle('is-selected', selected);
            row.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });

        pane.replaceChildren();
        const header = document.createElement('header');
        header.className = 'messenger-conversation-header';
        if (window.matchMedia('(max-width: 760px)').matches) {
            const back = document.createElement('button');
            back.type = 'button';
            back.className = 'messenger-mobile-back';
            back.setAttribute('aria-label', 'Back to conversations');
            back.innerHTML = '<i class="ph-light ph-arrow-left" aria-hidden="true"></i>';
            back.addEventListener('click', () => layout.classList.remove('messenger-mobile-conversation'));
            header.appendChild(back);
        }
        header.appendChild(makeAvatar(group[0]));
        const title = document.createElement('div');
        title.className = 'messenger-conversation-title';
        const h2 = document.createElement('h2');
        h2.textContent = agencyName(group[0]);
        const sub = document.createElement('p');
        sub.textContent = `${group.length} ${group.length === 1 ? 'ticket' : 'tickets'} · Your conversations with this agency`;
        title.append(h2, sub);
        header.appendChild(title);

        const body = document.createElement('div');
        body.className = 'messenger-conversation-body messenger-agency-thread';
        const date = document.createElement('div');
        date.className = 'messenger-date-divider';
        date.textContent = 'Your tickets with this agency';
        body.appendChild(date);

        group.slice().sort((a, b) => (Number(b.dataset.id) || 0) - (Number(a.dataset.id) || 0)).forEach(card => {
            const ticket = document.createElement('section');
            ticket.className = 'messenger-ticket-thread';
            ticket.dataset.ticketId = card.dataset.id;
            const isTrashed = card.classList.contains('is-trashed') || (card.dataset.status || '').toLowerCase() === 'trashed';
            if (isTrashed) ticket.classList.add('is-trashed');

            const ticketHeader = document.createElement('div');
            ticketHeader.className = 'messenger-ticket-header';

            const ticketButton = document.createElement('button');
            ticketButton.type = 'button';
            ticketButton.className = 'messenger-ticket-bubble';
            ticketButton.setAttribute('aria-expanded', 'false');
            ticket.classList.remove('is-expanded');
            const ticketCopy = document.createElement('span');
            ticketCopy.className = 'messenger-ticket-copy';
            const ticketTop = document.createElement('span');
            ticketTop.className = 'messenger-ticket-top';
            ticketTop.textContent = `Ticket #${card.dataset.id}`;
            const subject = document.createElement('span');
            subject.className = 'messenger-ticket-subject';
            subject.textContent = card.querySelector('.inquiry-question')?.textContent.trim() || 'Support ticket';
            const status = document.createElement('span');
            status.className = 'messenger-ticket-status';
            const ticketStatusKey = (card.dataset.status || 'pending').toLowerCase();
            const statusPresentation = {
                pending: { icon: 'ph-clock', label: 'Waiting for response' },
                awaiting_confirmation: { icon: 'ph-chat-circle-dots', label: 'Response available' },
                needs_follow_up: { icon: 'ph-arrow-counter-clockwise', label: 'Follow-up needed' },
                answered: { icon: 'ph-check-circle', label: 'Resolved' },
                trashed: { icon: 'ph-trash', label: 'Trashed by administration' },
            };
            const statusInfo = statusPresentation[ticketStatusKey] || {
                icon: 'ph-clock',
                label: card.querySelector('.inquiry-status-badge')?.textContent.trim() || 'Inquiry',
            };
            const statusIcon = document.createElement('i');
            statusIcon.className = `ph-light ${statusInfo.icon}`;
            statusIcon.setAttribute('aria-hidden', 'true');
            const statusLabel = document.createElement('span');
            statusLabel.textContent = statusInfo.label;
            status.append(statusIcon, statusLabel);
            ticketCopy.append(ticketTop, subject, status);
            if (card.querySelector('[data-unread-response], .inquiry-unread-badge')) {
                const unreadDot = document.createElement('span');
                unreadDot.className = 'messenger-unread-dot';
                unreadDot.setAttribute('role', 'img');
                unreadDot.setAttribute('aria-label', 'New admin response');
                unreadDot.title = 'New admin response';
                ticketCopy.appendChild(unreadDot);
            }
            const chevron = document.createElement('i');
            chevron.className = 'ph-light ph-caret-down';
            chevron.setAttribute('aria-hidden', 'true');
            ticketButton.append(ticketCopy, chevron);

            const details = card.querySelector('.inquiry-details');
            const detailClone = details?.cloneNode(true);
            let headerEditButton = null;
            const ticketStatus = (card.dataset.status || '').toLowerCase();
            const hasAdminResponse = Boolean(
                card.querySelector('.official-response, .inquiry-response, .inquiry-response-surface, [data-official-response]')
                || ['awaiting_confirmation', 'needs_follow_up', 'answered'].includes(ticketStatus)
            );
            const canEditPendingQuestion = ticketStatus === 'pending'
                && !isTrashed
                && !hasAdminResponse;
            if (detailClone) {
                detailClone.hidden = true;
                detailClone.removeAttribute('aria-hidden');
                detailClone.classList.add('messenger-detail-clone');
                // Confirmation handlers resolve the ticket through .inquiry-card.
                // The conversation copy is intentionally outside the hidden source
                // record, so give it the same ticket identity for the API actions.
                detailClone.classList.add('inquiry-card');
                detailClone.dataset.id = card.dataset.id;
                detailClone.dataset.status = card.dataset.status || 'pending';
                detailClone.dataset.sourceCardId = card.dataset.id;
                detailClone.querySelectorAll('[id]').forEach(node => { node.id = `${node.id}-agency-${card.dataset.id}`; });
                detailClone.querySelectorAll('[aria-controls]').forEach(node => {
                    const value = node.getAttribute('aria-controls');
                    if (value) node.setAttribute('aria-controls', `${value}-agency-${card.dataset.id}`);
                });
                if (hasAdminResponse) {
                    // The ticket header already shows the original question.
                    // Inside the expanded conversation, show only the official
                    // response and the citizen's response actions.
                    detailClone.querySelector('.inquiry-question-full')?.remove();
                }
                // Keep trashed-ticket details compact: show only the moderation
                // reason when one exists, without the nested notice/card UI.
                if (isTrashed) {
                    const reasonText = detailClone.querySelector('.inquiry-trash-reason')?.textContent
                        ?.replace(/^\s*Reason from administration:\s*/i, '')
                        ?.trim();
                    detailClone.replaceChildren();
                    if (reasonText) {
                        const reason = document.createElement('p');
                        reason.className = 'messenger-trash-reason';
                        const label = document.createElement('strong');
                        label.textContent = 'Reason from administration: ';
                        const value = document.createElement('span');
                        value.textContent = reasonText;
                        reason.append(label, value);
                        detailClone.appendChild(reason);
                    }
                }

                // All ticket details start collapsed. The hidden attribute and
                // explicit display state prevent older status-specific CSS from
                // exposing a panel during the initial render.
                detailClone.hidden = true;
                detailClone.style.setProperty('display', 'none', 'important');
                ticketButton.setAttribute('aria-expanded', 'false');
                ticket.classList.remove('is-expanded');

                if (!hasAdminResponse && !isTrashed) {
                    detailClone.replaceChildren();
                    const waiting = document.createElement('div');
                    waiting.className = 'messenger-waiting-state';
                    waiting.innerHTML = '<i class="ph-light ph-clock" aria-hidden="true"></i><span>Waiting for admin response</span>';
                    detailClone.appendChild(waiting);

                    if (canEditPendingQuestion) {
                        const editButton = document.createElement('button');
                        editButton.type = 'button';
                        editButton.className = 'messenger-edit-question-trigger';
                        editButton.innerHTML = '<i class="ph-light ph-pencil-simple" aria-hidden="true"></i>';
                        editButton.setAttribute('aria-label', 'Edit your question');
                        editButton.setAttribute('title', 'Edit your question');

                        const editForm = document.createElement('form');
                        editForm.className = 'messenger-edit-question-form';
                        editForm.hidden = true;
                        const editField = document.createElement('textarea');
                        editField.name = 'question';
                        editField.maxLength = 500;
                        editField.required = true;
                        editField.rows = 3;
                        editField.value = card.querySelector('.inquiry-question')?.textContent.trim() || '';
                        editField.setAttribute('aria-label', 'Edit your original question');
                        const editActions = document.createElement('div');
                        editActions.className = 'messenger-edit-question-actions';
                        const cancelEdit = document.createElement('button');
                        cancelEdit.type = 'button';
                        cancelEdit.className = 'messenger-edit-cancel';
                        cancelEdit.textContent = 'Cancel';
                        const saveEdit = document.createElement('button');
                        saveEdit.type = 'submit';
                        saveEdit.className = 'messenger-edit-save';
                        saveEdit.textContent = 'Save changes';
                        const editError = document.createElement('p');
                        editError.className = 'messenger-edit-question-error';
                        editError.hidden = true;
                        editActions.append(cancelEdit, saveEdit);
                        editForm.append(editField, editError, editActions);
                        editButton.addEventListener('click', () => {
                            ticketButton.setAttribute('aria-expanded', 'true');
                            ticket.classList.add('is-expanded');
                            if (detailClone) { detailClone.hidden = false; detailClone.style.setProperty('display', 'block', 'important'); }
                            editForm.hidden = false;
                            editButton.hidden = true;
                            editField.focus();
                        });
                        cancelEdit.addEventListener('click', () => {
                            editField.value = card.querySelector('.inquiry-question')?.textContent.trim() || '';
                            editError.hidden = true;
                            editForm.hidden = true;
                            editButton.hidden = false;
                        });
                        editForm.addEventListener('submit', async event => {
                            event.preventDefault();
                            const question = editField.value.trim();
                            if (question.length < 3) {
                                editError.textContent = 'Please enter at least 3 characters.';
                                editError.hidden = false;
                                return;
                            }
                            saveEdit.disabled = true;
                            editError.hidden = true;
                            try {
                                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                const response = await fetch(`/my-inquiries/${encodeURIComponent(card.dataset.id)}/question`, {
                                    method: 'PATCH',
                                    credentials: 'same-origin',
                                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                                    body: JSON.stringify({ question })
                                });
                                const result = await response.json().catch(() => ({}));
                                if (!response.ok || result.success !== true) throw new Error(result.message || 'Unable to update this question.');
                                const url = new URL(window.location.href);
                                url.searchParams.set('ticket', card.dataset.id);
                                window.location.assign(url.toString());
                            } catch (error) {
                                editError.textContent = error.message || 'Unable to update this question.';
                                editError.hidden = false;
                            } finally {
                                saveEdit.disabled = false;
                            }
                        });
                        detailClone.append(editForm);
                        headerEditButton = editButton;
                    }
                    ticket.classList.add('is-awaiting-response');
                }
            }

            ticketButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();

                // Use the rendered class as the source of truth. aria-expanded can
                // become stale when the conversation is rebuilt after filtering or
                // when the initial ticket query parameter selects an agency.
                const shouldExpand = !ticket.classList.contains('is-expanded');

                // Keep the accordion state consistent before changing the selected
                // ticket, including the native hidden attribute and inline display.
                if (shouldExpand) {
                    body.querySelectorAll('.messenger-ticket-thread.is-expanded').forEach(openTicket => {
                        if (openTicket === ticket) return;
                        openTicket.classList.remove('is-expanded');
                        const openButton = openTicket.querySelector('.messenger-ticket-bubble');
                        const openDetails = openTicket.querySelector('.messenger-detail-clone');
                        openButton?.setAttribute('aria-expanded', 'false');
                        if (openDetails) {
                            openDetails.hidden = true;
                            openDetails.setAttribute('aria-hidden', 'true');
                            openDetails.style.setProperty('display', 'none', 'important');
                        }
                    });
                }

                ticket.classList.toggle('is-expanded', shouldExpand);
                ticketButton.setAttribute('aria-expanded', shouldExpand ? 'true' : 'false');
                saveExpandedState(card.dataset.id, shouldExpand);

                if (detailClone) {
                    detailClone.hidden = !shouldExpand;
                    detailClone.setAttribute('aria-hidden', shouldExpand ? 'false' : 'true');
                    detailClone.style.setProperty(
                        'display',
                        shouldExpand ? 'block' : 'none',
                        'important'
                    );
                }
                if (shouldExpand) markTicketResponseSeen(card, ticket);
            });
            // Add safe citizen-owned lifecycle actions to the expanded ticket.
            if (detailClone && !isTrashed) {
                const actions = document.createElement('div');
                actions.className = 'messenger-ticket-management-actions';
                const deleteButton = document.createElement('button');
                deleteButton.type = 'button';
                deleteButton.className = 'messenger-ticket-delete';
                deleteButton.dataset.deleteTicket = card.dataset.id;
                deleteButton.innerHTML = '<i class="ph-light ph-trash" aria-hidden="true"></i><span>Delete ticket</span>';
                actions.appendChild(deleteButton);
                if (ticketStatus === 'answered') {
                    const reopenButton = document.createElement('button');
                    reopenButton.type = 'button';
                    reopenButton.className = 'messenger-ticket-reopen';
                    reopenButton.dataset.reopenTicket = card.dataset.id;
                    reopenButton.innerHTML = '<i class="ph-light ph-arrow-counter-clockwise" aria-hidden="true"></i><span>Reopen ticket</span>';
                    actions.appendChild(reopenButton);
                }
                detailClone.appendChild(actions);
            }
            // Keep the compact edit icon before the expand/collapse chevron.
            if (headerEditButton) ticketHeader.appendChild(headerEditButton);
            ticketHeader.appendChild(ticketButton);
            ticket.appendChild(ticketHeader);
            if (detailClone) ticket.append(detailClone);
            body.appendChild(ticket);
        });

        const composer = document.createElement('form');
        composer.className = 'messenger-ticket-composer';
        composer.setAttribute('aria-label', `Send a new ticket to ${agencyName(group[0])}`);
        const composerMain = document.createElement('div');
        composerMain.className = 'messenger-composer-main';
        const composerWrap = document.createElement('div');
        composerWrap.className = 'messenger-composer-field';
        const textarea = document.createElement('textarea');
        textarea.name = 'question';
        textarea.rows = 1;
        textarea.maxLength = 500;
        textarea.required = true;
        textarea.placeholder = 'Ask a question…';
        textarea.setAttribute('aria-label', `Ask a question to ${agencyName(group[0])}`);
        const composerActions = document.createElement('div');
        composerActions.className = 'messenger-composer-actions';
        const hint = document.createElement('span');
        hint.className = 'messenger-composer-hint';
        hint.textContent = 'A new message creates a separate ticket.';
        const send = document.createElement('button');
        send.type = 'submit';
        send.className = 'messenger-composer-send';
        send.setAttribute('aria-label', 'Send ticket');
        send.innerHTML = '<i class="ph-light ph-arrow-up" aria-hidden="true"></i>';
        composerWrap.append(textarea, send);
        composerMain.appendChild(composerWrap);
        composerActions.append(hint);
        composer.append(composerMain, composerActions);
        textarea.addEventListener('input', () => {
            textarea.style.height = 'auto';
            textarea.style.height = `${Math.min(textarea.scrollHeight, 180)}px`;
        });
        composer.addEventListener('submit', async event => {
            event.preventDefault();
            const question = textarea.value.trim();
            const agencyId = group.find(card => card.dataset.agencyId)?.dataset.agencyId;
            if (!question || !agencyId) return;
            send.disabled = true;
            send.setAttribute('aria-busy', 'true');
            send.innerHTML = '<i class="ph-light ph-spinner" aria-hidden="true"></i><span>Sending…</span>';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response = await fetch('/chat/support', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({ question, agency_id: Number(agencyId) })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.success !== true) {
                    const validation = data.errors ? Object.values(data.errors).flat()[0] : null;
                    throw new Error(validation || data.message || 'Unable to send your ticket. Please try again.');
                }
                const next = new URL(window.location.href);
                next.searchParams.delete('ticket');
                next.searchParams.set('ticket_created', String(data.support_request_id || ''));
                window.location.assign(next.toString());
            } catch (error) {
                let alert = composer.querySelector('.messenger-composer-error');
                if (!alert) {
                    alert = document.createElement('p');
                    alert.className = 'messenger-composer-error';
                    alert.setAttribute('role', 'alert');
                    composer.insertBefore(alert, composerActions);
                }
                alert.textContent = error.message || 'Unable to send your ticket. Please try again.';
                send.disabled = false;
                send.removeAttribute('aria-busy');
                send.innerHTML = '<i class="ph-light ph-arrow-up" aria-hidden="true"></i>';
            }
        });

        pane.append(header, body, composer);
        // Restore each ticket's last explicit state across refreshes. A ticket
        // supplied in the URL is opened intentionally (e.g. after follow-up).
        const expandedState = readExpandedState();
        const initialId = initialTicketId && group.some(card => card.dataset.id === initialTicketId)
            ? String(initialTicketId)
            : null;
        const restoreTicketId = initialId && expandedState[initialId] === undefined
            ? initialId
            : group.map(card => String(card.dataset.id)).find(id => expandedState[id] === true);
        if (restoreTicketId) {
            const target = Array.from(body.querySelectorAll('.messenger-ticket-thread'))
                .find(item => item.dataset.ticketId === restoreTicketId);
            if (target) {
                target.classList.add('is-expanded');
                const button = target.querySelector('.messenger-ticket-bubble');
                const details = target.querySelector('.messenger-detail-clone');
                button?.setAttribute('aria-expanded', 'true');
                if (details) {
                    details.hidden = false;
                    details.setAttribute('aria-hidden', 'false');
                    details.style.setProperty('display', 'block', 'important');
                }
                const sourceCard = group.find(card => String(card.dataset.id) === restoreTicketId);
                if (sourceCard) markTicketResponseSeen(sourceCard, target);
                saveExpandedState(restoreTicketId, true);
            }
        }
        pane.hidden = false;
        if (welcome) welcome.hidden = true;
        if (window.matchMedia('(max-width: 760px)').matches) layout.classList.add('messenger-mobile-conversation');
        if (updateHistory) {
            const newest = latestActivity(group);
            try {
                const url = new URL(window.location.href);
                if (newest?.dataset.id) url.searchParams.set('ticket', newest.dataset.id);
                history.replaceState({}, '', url);
            } catch (_) {}
        }
    }

    function renderAgencyRows() {
        const groups = groupsFromVisible();

        // Do not silently select the first agency. My Inquiries should open
        // with the agency list visible and wait for the citizen to choose.
        // A selection is retained only while that agency remains in the
        // current filtered results; explicit ticket URLs set selectedKey
        // before the first render.
        const currentKey = selectedKey && groups.has(selectedKey)
            ? selectedKey
            : null;

        if (!currentKey) {
            selectedKey = null;
        }

        const rows = document.createDocumentFragment();

        [...groups.entries()].sort((a, b) => {
            const at = Date.parse(latestTime(latestActivity(a[1]))) || 0;
            const bt = Date.parse(latestTime(latestActivity(b[1]))) || 0;
            return bt - at;
        }).forEach(([key, group]) => {
            const latest = latestActivity(group);
            const row = document.createElement('button');
            row.type = 'button';
            row.className = `messenger-agency-row${key === currentKey ? ' is-selected' : ''}`;
            row.dataset.agencyKey = key;
            row.setAttribute('aria-pressed', key === currentKey ? 'true' : 'false');
            row.appendChild(makeAvatar(latest));
            const copy = document.createElement('span');
            copy.className = 'messenger-agency-row-copy';
            const heading = document.createElement('span');
            heading.className = 'messenger-row-heading';
            const name = document.createElement('strong');
            name.textContent = agencyName(latest);
            const time = document.createElement('time');
            time.className = 'messenger-row-time';
            time.textContent = latest.querySelector('.messenger-row-time')?.textContent.trim()
                || latest.querySelector('.inquiry-date')?.textContent.trim() || '';
            heading.append(name, time);
            if (group.some(card => card.querySelector('[data-unread-response], .inquiry-unread-badge'))) {
                const unreadDot = document.createElement('span');
                unreadDot.className = 'messenger-agency-unread-dot';
                unreadDot.setAttribute('role', 'img');
                unreadDot.setAttribute('aria-label', 'Unread admin response');
                unreadDot.title = 'Unread admin response';
                heading.appendChild(unreadDot);
            }
            const last = document.createElement('span');
            last.className = 'messenger-row-preview';
            last.textContent = preview(latest);
            const meta = document.createElement('span');
            meta.className = 'messenger-agency-row-meta';
            meta.textContent = `${group.length} ${group.length === 1 ? 'ticket' : 'tickets'}`;
            copy.append(heading, last, meta);
            row.appendChild(copy);
            row.addEventListener('click', () => openAgency(key, true));
            rows.appendChild(row);
        });

        originalList.querySelectorAll('.messenger-agency-row').forEach(row => row.remove());
        originalList.appendChild(rows);
        const count = document.querySelector('[data-inquiry-result-meta]');
        if (count) count.textContent = String(groups.size);

        if (currentKey) {
            openAgency(currentKey, false);
        } else {
            // Clear any previous conversation, including the mobile
            // conversation-pane mode, when no agency was explicitly selected.
            pane.replaceChildren();
            pane.hidden = true;
            layout.classList.remove('messenger-mobile-conversation');
            if (welcome) welcome.hidden = false;
        }
    }

    // Search/filter code updates the canonical ticket elements. Observe those
    // visibility changes and rebuild the agency inbox without replacing them.
    const observer = new MutationObserver(() => {
        if (renderAgencyRows.scheduled) return;
        renderAgencyRows.scheduled = true;
        queueMicrotask(() => {
            renderAgencyRows.scheduled = false;
            renderAgencyRows();
        });
    });
    cards.forEach(card => observer.observe(card, { attributes: true, attributeFilter: ['style', 'hidden', 'class'] }));

    const requestedCard = cards.find(card => card.dataset.id === initialTicketId);
    selectedKey = requestedCard ? agencyKey(requestedCard) : null;
    renderAgencyRows();

    function syncAgencyUnreadDots() {
        const groups = groupsFromVisible();
        originalList.querySelectorAll('.messenger-agency-row').forEach(row => {
            const group = groups.get(row.dataset.agencyKey) || [];
            const hasUnread = group.some(card => card.querySelector('[data-unread-response], .inquiry-unread-badge'));
            const heading = row.querySelector('.messenger-row-heading');
            const current = heading?.querySelector('.messenger-agency-unread-dot');
            if (hasUnread && !current && heading) {
                const dot = document.createElement('span');
                dot.className = 'messenger-agency-unread-dot';
                dot.setAttribute('role', 'img');
                dot.setAttribute('aria-label', 'Unread admin response');
                dot.title = 'Unread admin response';
                heading.appendChild(dot);
            } else if (!hasUnread) {
                current?.remove();
            }
        });
    }

    window.addEventListener('inquiry:notification-changed', syncAgencyUnreadDots);

    // Reconcile the red dot in the visible conversation whenever authoritative
    // Reverb data updates the canonical ticket record.
    window.addEventListener('inquiry:updated', event => {
        const card = event.detail?.card;
        const id = String(card?.dataset?.id || event.detail?.id || '');
        if (!id) return;
        const thread = pane.querySelector(`.messenger-ticket-thread[data-ticket-id="${CSS.escape(id)}"]`);
        const copy = thread?.querySelector('.messenger-ticket-copy');
        if (!thread || !copy || !card) return;
        const shouldShow = Boolean(card.querySelector('[data-unread-response], .inquiry-unread-badge'));
        const current = copy.querySelector('.messenger-unread-dot');
        if (shouldShow && !current) {
            const dot = document.createElement('span');
            dot.className = 'messenger-unread-dot';
            dot.setAttribute('role', 'img');
            dot.setAttribute('aria-label', 'New admin response');
            dot.title = 'New admin response';
            copy.appendChild(dot);
        } else if (!shouldShow) {
            current?.remove();
        }
        syncAgencyUnreadDots();
    });

    document.addEventListener('click', async event => {
        const deleteButton = event.target.closest('[data-delete-ticket]');
        const reopenButton = event.target.closest('[data-reopen-ticket]');
        const button = deleteButton || reopenButton;
        if (!button) return;
        event.preventDefault();
        const id = button.dataset.deleteTicket || button.dataset.reopenTicket;
        if (!id) return;
        const isDelete = Boolean(deleteButton);
        if (typeof window.showAlertModal !== 'function') {
            console.error('KNOWURLOCAL modal system is unavailable.');
            return;
        }
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        if (!csrf) {
            window.showAlertModal({ title: 'Session expired', text: 'Refresh the page and try again.', variant: 'danger', confirmText: 'OK' });
            return;
        }
        const sendRequest = async (message = '') => {
            button.disabled = true;
            try {
                const response = await fetch(`/my-inquiries/${encodeURIComponent(id)}${isDelete ? '' : '/reopen'}`, {
                    method: isDelete ? 'DELETE' : 'POST', credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json' },
                    body: JSON.stringify(isDelete ? {} : { message })
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.success !== true) throw new Error(data.message || 'The ticket could not be updated.');
                window.showAlertModal({ title: 'Success', text: data.message || (isDelete ? 'Ticket deleted.' : 'Ticket reopened.'), variant: 'success', showConfirm: false, showCancel: false, autoCloseMs: 900 });
                window.setTimeout(() => window.location.reload(), 850);
            } catch (error) {
                button.disabled = false;
                window.showAlertModal({ title: 'Unable to complete action', text: error.message || 'Please try again.', variant: 'danger', confirmText: 'OK' });
            }
        };
        if (isDelete) {
            window.showAlertModal({
                title: 'Delete ticket?', text: 'This ticket will disappear from your inquiry list. The agency history will be retained.',
                variant: 'danger', icon: 'ph-light ph-trash', confirmText: 'Delete ticket', showCancel: true, cancelText: 'Keep ticket',
                onConfirm: () => { window.closeAlertModal(); sendRequest(); }
            });
        } else {
            window.showAlertModal({
                title: 'Reopen ticket', text: 'Tell the agency what you still need help with. Your message will be sent with the reopening request.',
                variant: 'info', icon: 'ph-light ph-arrow-counter-clockwise', confirmText: 'Reopen and send', showCancel: true,
                input: { label: 'Your follow-up question', placeholder: 'Explain what you need help with...', required: true, minLength: 3, maxLength: 2000, validationMessage: 'Please enter at least 3 characters.' },
                onConfirm: (message) => { window.closeAlertModal(); sendRequest(String(message || '').trim()); }
            });
        }
    });
}
