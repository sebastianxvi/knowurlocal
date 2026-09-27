(() => {
    'use strict';

    const root = document.querySelector('[data-admin-notifications]');
    if (!root) return;

    const trigger = root.querySelector('[data-admin-notifications] .admin-notification-trigger') || document.getElementById('admin-notification-trigger');
    const panel = root.querySelector('[data-notification-panel]');
    const badge = root.querySelector('[data-notification-count]');
    const list = root.querySelector('[data-notification-list]');
    const supportCountEl = root.querySelector('[data-support-count]');
    const collaborationCountEl = root.querySelector('[data-collaboration-count]');
    const echoReadyEvent = 'knowurlocal:echo-ready';

    if (!trigger || !panel) return;

    let subscriptionsStarted = false;

    let supportCount = Number.parseInt(supportCountEl?.textContent || '0', 10) || 0;
    let collaborationCount = Number.parseInt(collaborationCountEl?.textContent || '0', 10) || 0;
    const seenSupportIds = new Set();
    const seenCollaborationIds = new Set();

    root.querySelectorAll('[data-notification-kind="support"]').forEach((item) => {
        if (item.dataset.notificationId) seenSupportIds.add(String(item.dataset.notificationId));
    });
    root.querySelectorAll('[data-notification-kind="collaboration"]').forEach((item) => {
        if (item.dataset.notificationId) seenCollaborationIds.add(String(item.dataset.notificationId));
    });

    const updateBadge = () => {
        const total = Math.max(0, supportCount + collaborationCount);
        if (!badge) return;
        badge.hidden = total < 1;
        badge.textContent = total > 99 ? '99+' : String(total);
        badge.setAttribute('aria-label', `${total} current notifications`);
    };

    const updateSummary = () => {
        if (supportCountEl) supportCountEl.textContent = String(supportCount);
        if (collaborationCountEl) collaborationCountEl.textContent = String(collaborationCount);
        updateBadge();
    };

    const open = () => {
        panel.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        trigger.classList.add('active');
    };

    const close = () => {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        trigger.classList.remove('active');
    };

    trigger.addEventListener('click', (event) => {
        event.preventDefault();
        panel.hidden ? open() : close();
    });

    document.addEventListener('click', (event) => {
        if (!root.contains(event.target)) close();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') close();
    });

    const getNotificationItems = (kind) => {
        if (!list) return [];
        return Array.from(
            list.querySelectorAll(`[data-notification-kind="${kind}"]`)
        );
    };

    const removeEmptyState = () => {
        list?.querySelector('.admin-notification-empty')?.remove();
    };

    const restoreEmptyStateIfNeeded = () => {
        if (!list) return;

        if (
            getNotificationItems('support').length ||
            getNotificationItems('collaboration').length ||
            list.querySelector('.admin-notification-empty')
        ) {
            return;
        }

        const empty = document.createElement('div');
        empty.className = 'admin-notification-empty';

        const icon = document.createElement('i');
        icon.className = 'ph-light ph-check-circle';
        icon.setAttribute('aria-hidden', 'true');

        const title = document.createElement('strong');
        title.textContent = "You're all caught up.";

        const text = document.createElement('span');
        text.textContent =
            'No pending support requests or collaboration work needs your attention.';

        empty.append(icon, title, text);
        list.appendChild(empty);
    };

    const removeSupportNotification = (requestId) => {
        const id = String(requestId);

        const items = getNotificationItems('support')
            .filter((item) => String(item.dataset.notificationId) === id);

        if (!items.length) {
            seenSupportIds.delete(id);
            return false;
        }

        items.forEach((item) => item.remove());
        seenSupportIds.delete(id);

        const section = list?.querySelector(
            '.admin-notification-section-label[data-section="support"]'
        );

        if (
            section &&
            !getNotificationItems('support').length
        ) {
            section.remove();
        }

        restoreEmptyStateIfNeeded();
        return true;
    };

    const updateSupportNotification = ({
        id,
        status,
        title,
        meta,
        icon = 'ph-chat-circle-text',
        href,
    }) => {
        if (!list || !id) return false;

        const idString = String(id);
        const actionable = ['pending', 'needs_follow_up'].includes(String(status));

        const existing = getNotificationItems('support')
            .find((item) => String(item.dataset.notificationId) === idString);

        /*
         * Notifications represent CURRENT actionable work, not history.
         * Once a ticket is awaiting citizen confirmation or is answered,
         * it must disappear from this list.
         */
        if (!actionable) {
            return false;
        }

        removeEmptyState();

        let section = list.querySelector(
            '.admin-notification-section-label[data-section="support"]'
        );

        if (!section) {
            section = document.createElement('div');
            section.className = 'admin-notification-section-label';
            section.dataset.section = 'support';
            section.textContent = 'Support requests';

            const collaborationSection = list.querySelector(
                '.admin-notification-section-label[data-section="collaboration"]'
            );

            if (collaborationSection) {
                list.insertBefore(section, collaborationSection);
            } else {
                list.prepend(section);
            }
        }

        if (existing) {
            existing.dataset.notificationStatus = String(status);
            existing.href = href || '/admin/support-requests?status=active';

            const titleEl = existing.querySelector('strong');
            const metaEl = existing.querySelector('small');
            const iconEl = existing.querySelector(
                '.admin-notification-item-icon i'
            );

            if (titleEl) titleEl.textContent = title || `Support request #${id}`;
            if (metaEl) metaEl.textContent = meta || 'Needs attention';
            if (iconEl) iconEl.className = `ph-light ${icon}`;

            return false;
        }

        const item = document.createElement('a');
        item.className = 'admin-notification-item is-new';
        item.href = href || '/admin/support-requests?status=active';
        item.dataset.notificationKind = 'support';
        item.dataset.notificationId = idString;
        item.dataset.notificationStatus = String(status);

        const iconWrap = document.createElement('span');
        iconWrap.className = 'admin-notification-item-icon is-support';

        const iconEl = document.createElement('i');
        iconEl.className = `ph-light ${icon}`;
        iconEl.setAttribute('aria-hidden', 'true');
        iconWrap.appendChild(iconEl);

        const copy = document.createElement('span');
        copy.className = 'admin-notification-item-copy';

        const titleEl = document.createElement('strong');
        titleEl.textContent = title || `Support request #${id}`;

        const metaEl = document.createElement('small');
        metaEl.textContent = meta || 'Needs attention';

        copy.append(titleEl, metaEl);

        const arrow = document.createElement('i');
        arrow.className =
            'ph-light ph-arrow-up-right admin-notification-item-arrow';
        arrow.setAttribute('aria-hidden', 'true');

        item.append(iconWrap, copy, arrow);
        section.after(item);

        seenSupportIds.add(idString);

        window.setTimeout(
            () => item.classList.remove('is-new'),
            1600
        );

        return true;
    };

    const prependNotification = ({
        kind,
        id,
        title,
        meta,
        icon,
        href,
        status,
    }) => {
        if (!list || !id) return false;

        if (kind === 'support') {
            return updateSupportNotification({
                id,
                status: status || 'pending',
                title,
                meta,
                icon,
                href,
            });
        }

        const idString = String(id);

        if (seenCollaborationIds.has(idString)) return false;

        seenCollaborationIds.add(idString);
        removeEmptyState();

        let section = list.querySelector(
            '.admin-notification-section-label[data-section="collaboration"]'
        );

        if (!section) {
            section = document.createElement('div');
            section.className = 'admin-notification-section-label';
            section.dataset.section = 'collaboration';
            section.textContent = 'Collaboration';
            list.appendChild(section);
        }

        const item = document.createElement('a');
        item.className = 'admin-notification-item is-new';
        item.href = href;
        item.dataset.notificationKind = kind;
        item.dataset.notificationId = idString;

        item.innerHTML = `
            <span class="admin-notification-item-icon is-collaboration"><i class="ph-light ${icon}" aria-hidden="true"></i></span>
            <span class="admin-notification-item-copy"><strong></strong><small></small></span>
            <i class="ph-light ph-arrow-up-right admin-notification-item-arrow" aria-hidden="true"></i>
        `;

        item.querySelector('strong').textContent =
            title || 'Collaboration task updated';

        item.querySelector('small').textContent =
            meta || 'Just now';

        section.after(item);

        window.setTimeout(
            () => item.classList.remove('is-new'),
            1600
        );

        return true;
    };

    const subscribe = () => {
        if (subscriptionsStarted) return;

        const echo = window.Echo;
        const adminId = document.body.dataset.adminUserId;
        if (!echo || !adminId) return;

        try {
            subscriptionsStarted = true;

            echo.private('admin.support-requests')
                .listen('.support.request.created', (event) => {
                    if (!event?.id) return;

                    const added = prependNotification({
                        kind: 'support',
                        id: event.id,
                        status: event.status || 'pending',
                        title: event.question || 'New support request',
                        meta: event.user_name
                            ? `${event.user_name} · ${event.status === 'needs_follow_up' ? 'Follow-up needed' : 'Pending'}`
                            : (event.status === 'needs_follow_up'
                                ? 'Follow-up needed'
                                : 'New pending request'),
                        icon: event.status === 'needs_follow_up'
                            ? 'ph-arrow-counter-clockwise'
                            : 'ph-chat-circle-text',
                        href: event.status === 'needs_follow_up'
                            ? '/admin/support-requests?status=active&status_filter=needs_follow_up'
                            : '/admin/support-requests?status=active&status_filter=pending',
                    });

                    if (added) {
                        supportCount += 1;
                        updateSummary();
                        open();
                    }
                })
                .listen('.support.request.updated', (event) => {
                    if (!event?.id) return;

                    const requestId = String(event.id);

                    if (event.deleted) {
                        if (removeSupportNotification(requestId)) {
                            supportCount = Math.max(0, supportCount - 1);
                            updateSummary();
                        }
                        return;
                    }

                    const status = String(event.status || '');
                    const actionable = ['pending', 'needs_follow_up'].includes(status);

                    const existing = getNotificationItems('support')
                        .find((item) =>
                            String(item.dataset.notificationId) === requestId
                        );

                    /*
                     * Notifications are a current-work queue, not history.
                     * An official response moves the ticket to
                     * awaiting_confirmation, so remove the old pending item.
                     */
                    const action = event.action || 'updated';

                    if (!actionable) {
                        /*
                         * response_created can only happen from an actionable
                         * ticket (the controller rejects awaiting_confirmation
                         * and answered). Therefore the global count must drop
                         * even when that ticket was outside the five visible
                         * notification rows.
                         */
                        if (existing) {
                            removeSupportNotification(requestId);
                        }

                        if (action === 'response_created') {
                            supportCount = Math.max(0, supportCount - 1);
                            updateSummary();
                        } else if (existing) {
                            supportCount = Math.max(0, supportCount - 1);
                            updateSummary();
                        }

                        return;
                    }

                    const actionCopy = {
                        response_created: 'Official response sent',
                        answer_updated: 'Response updated',
                        confirmed: 'Citizen confirmed response',
                        follow_up_requested: 'Follow-up requested',
                    };

                    const added = prependNotification({
                        kind: 'support',
                        id: requestId,
                        status,
                        title: event.question || `Support request #${requestId} updated`,
                        meta: `${status === 'needs_follow_up'
                            ? 'Follow-up needed'
                            : (actionCopy[action] || 'Pending support request')}${event.user_name ? ` · ${event.user_name}` : ''}`,
                        icon: status === 'needs_follow_up'
                            ? 'ph-arrow-counter-clockwise'
                            : 'ph-chat-circle-text',
                        href: status === 'needs_follow_up'
                            ? '/admin/support-requests?status=active&status_filter=needs_follow_up'
                            : '/admin/support-requests?status=active&status_filter=pending',
                    });

                    if (added) {
                        supportCount += 1;
                        updateSummary();
                        open();
                    }
                })
                .listen('.support.request.deleted', (event) => {
                    if (!event?.id) return;

                    if (removeSupportNotification(event.id)) {
                        supportCount = Math.max(0, supportCount - 1);
                        updateSummary();
                    }
                })

                .listen('.collaboration.task.updated', (event) => {
                    const task = event?.task;
                    if (!task?.id) return;

                    const action = event?.action || 'updated';

                    if (
                        action === 'created' &&
                        (
                            Number(task.assigned_to_id) === Number(adminId) ||
                            Number(task.created_by_id) === Number(adminId)
                        )
                    ) {
                        collaborationCount += 1;
                    }

                    if (
                        action === 'status_updated' &&
                        ['completed', 'cancelled'].includes(task.status)
                    ) {
                        collaborationCount = Math.max(
                            0,
                            collaborationCount - 1
                        );
                    }

                    const icon = task.task_type === 'review'
                        ? 'ph-check-square'
                        : (
                            task.task_type === 'assist'
                                ? 'ph-handshake'
                                : 'ph-arrow-bend-up-right'
                        );

                    prependNotification({
                        kind: 'collaboration',
                        id: `${task.id}-${action}-${task.status}`,
                        title: task.title || 'Collaboration task updated',
                        meta: task.status === 'completed'
                            ? 'Completed'
                            : (
                                task.status === 'in_progress'
                                    ? 'In progress'
                                    : 'Open'
                            ),
                        icon,
                        href: `/admin/dashboard#collaboration-task-${encodeURIComponent(task.id)}`,
                    });

                    updateSummary();
                    open();
                });

        } catch (error) {
            subscriptionsStarted = false;
            console.warn('Admin realtime notifications are unavailable.', error);
        }
    };

    updateSummary();
    subscribe();
    window.addEventListener(echoReadyEvent, subscribe, { once: true });
})();
