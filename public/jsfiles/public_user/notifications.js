(() => {
    'use strict';

    const list = document.querySelector('[data-notifications-list]');
    if (!list) return;

    const countEl = document.querySelector('[data-notifications-unread-count]');
    const readAllButton = document.querySelector('[data-notifications-read-all]');
    const updateUnreadCount = (delta = 0) => {
        if (!countEl) return;
        const current = Math.max(0, Number.parseInt(countEl.textContent || '0', 10) || 0);
        const next = Math.max(0, current + delta);
        countEl.textContent = String(next);
        if (readAllButton) readAllButton.hidden = next <= 0;
    };

    const makeItem = (notification) => {
        if (!notification || !notification.id || !notification.open_url) return;
        if (list.querySelector(`[data-notification-id="${CSS.escape(String(notification.id))}"]`)) return;

        list.querySelector('[data-notifications-empty]')?.remove();
        const item = document.createElement('a');
        item.className = 'user-notification-item is-unread';
        item.href = notification.open_url;
        item.dataset.userNotificationItem = '';
        item.dataset.notificationId = String(notification.id);
        item.dataset.read = '0';

        const type = String(notification.type || '');
        const isEvaluation = type.startsWith('evaluation_') && type !== 'evaluation_response';
        const isAgencyResponse = type === 'evaluation_response';
        const isTrash = ['ticket_trashed', 'ticket_permanently_deleted', 'evaluation_removed'].includes(type);
        const iconName = ({
            ticket_trashed: 'ph-trash',
            ticket_restored: 'ph-arrow-counter-clockwise',
            ticket_permanently_deleted: 'ph-trash',
            evaluation_hidden: 'ph-eye-slash',
            evaluation_restored: 'ph-eye',
            evaluation_removed: 'ph-trash',
            evaluation_response: 'ph-chat-circle-text',
        })[type] || 'ph-chat-circle-text';
        const icon = document.createElement('span');
        icon.className = `user-notification-icon ${isTrash ? 'is-trash' : ((isEvaluation || isAgencyResponse) ? 'is-evaluation' : 'is-response')}`;
        const iconEl = document.createElement('i');
        iconEl.className = `ph-light ${iconName}`;
        iconEl.setAttribute('aria-hidden', 'true');
        icon.appendChild(iconEl);

        const copy = document.createElement('span');
        copy.className = 'user-notification-copy';
        const title = document.createElement('strong');
        title.textContent = notification.title || 'Ticket update';
        const message = document.createElement('span');
        message.textContent = notification.message || 'There is an update to your ticket.';
        const time = document.createElement('small');
        time.textContent = notification.created_at ? new Date(notification.created_at).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }) : 'Just now';
        copy.append(title, message, time);

        const dot = document.createElement('span');
        dot.className = 'user-notification-unread-dot';
        dot.setAttribute('aria-label', 'Unread');
        const arrow = document.createElement('i');
        arrow.className = 'ph-light ph-arrow-up-right user-notification-arrow';
        arrow.setAttribute('aria-hidden', 'true');
        item.append(icon, copy, dot, arrow);
        list.prepend(item);
        updateUnreadCount(1);
    };

    const userId = document.querySelector('meta[name="user-id"]')?.content || '';
    const connect = () => {
        if (!window.Echo || !/^\d+$/.test(userId)) return false;
        window.Echo.private(`App.Models.User.${userId}`)
            .listen('.user.notification.created', makeItem);
        return true;
    };

    if (!connect()) {
        window.addEventListener('knowurlocal:echo-ready', connect, { once: true });
    }
})();
