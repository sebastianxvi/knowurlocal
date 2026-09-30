(() => {
    const initCollaboration = () => {
        const modal = document.getElementById('collaboration-modal');
        const openButton = document.getElementById('open-collaboration-modal');
        const form = document.getElementById('collaboration-form');

        if (!modal || !openButton || !form) {
            return;
        }

        const typeSelect = document.getElementById('collaboration-target-type');
        const targetSelect = document.getElementById('collaboration-target-id');
        const errorEl = document.getElementById('collaboration-form-error');
        const submitButton = document.getElementById('collaboration-submit');

        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const storeUrl = form.dataset.storeUrl || '';
        const targetsUrl = form.dataset.targetsUrl || '';
        const statusBaseUrl = form.dataset.statusBaseUrl || '';

        const setError = (message = '') => {
            if (!errorEl) return;
            errorEl.textContent = message;
            errorEl.hidden = !message;
        };

        const openModal = () => {
            modal.hidden = false;
            modal.removeAttribute('aria-hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('dashboard-collaboration-modal-open');
            form.querySelector('input[name="title"]')?.focus();
        };

        const closeModal = () => {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('dashboard-collaboration-modal-open');
            setError('');
        };

        openButton.addEventListener('click', (event) => {
            event.preventDefault();
            openModal();
        });

        modal.querySelectorAll('[data-close-collaboration-modal]').forEach((element) => {
            element.addEventListener('click', (event) => {
                event.preventDefault();
                closeModal();
            });
        });

        const loadTargets = async () => {
            if (!typeSelect || !targetSelect) return;

            const type = typeSelect.value;

            targetSelect.replaceChildren(
                new Option(type ? 'Loading records…' : 'Choose a record', '')
            );
            targetSelect.disabled = !type;

            if (!type) return;

            if (!targetsUrl) {
                targetSelect.replaceChildren(new Option('Unable to load records', ''));
                targetSelect.disabled = true;
                return;
            }

            try {
                const url = new URL(targetsUrl, window.location.origin);
                url.searchParams.set('type', type);

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(payload.message || 'Unable to load records.');
                }

                const items = Array.isArray(payload.data) ? payload.data : [];

                targetSelect.replaceChildren(new Option('Choose a record', ''));

                items.forEach((item) => {
                    targetSelect.append(
                        new Option(String(item.label ?? 'Record'), String(item.id))
                    );
                });

                if (!items.length) {
                    targetSelect.append(new Option('No records found', ''));
                }

                targetSelect.disabled = items.length === 0;
            } catch (error) {
                targetSelect.replaceChildren(new Option('Unable to load records', ''));
                targetSelect.disabled = true;
            }
        };

        typeSelect?.addEventListener('change', loadTargets);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setError('');

            if (!storeUrl) {
                setError('The collaboration service endpoint is unavailable. Please refresh the page and try again.');
                return;
            }

            if (!csrf) {
                setError('Your session token is unavailable. Please refresh the page and try again.');
                return;
            }

            submitButton?.setAttribute('disabled', 'disabled');

            const originalText = submitButton?.textContent?.trim() || 'Create task';
            if (submitButton) {
                submitButton.textContent = 'Creating…';
            }

            try {
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: new FormData(form),
                });

                const payload = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const validation = payload.errors
                        ? Object.values(payload.errors).flat()[0]
                        : null;

                    throw new Error(
                        validation ||
                        payload.message ||
                        `Unable to create the collaboration task (HTTP ${response.status}).`
                    );
                }

                window.location.reload();
            } catch (error) {
                setError(error.message || 'Unable to create the collaboration task.');

                if (submitButton) {
                    submitButton.removeAttribute('disabled');
                    submitButton.textContent = originalText;
                }
            }
        });

        document.querySelectorAll('[data-collaboration-status]').forEach((select) => {
            select.addEventListener('change', async () => {
                const taskId = select.dataset.taskId;
                const nextStatus = select.value;

                if (!taskId || !statusBaseUrl || !csrf) {
                    return;
                }

                select.disabled = true;

                try {
                    const response = await fetch(
                        `${statusBaseUrl}/${encodeURIComponent(taskId)}/status`,
                        {
                            method: 'PATCH',
                            headers: {
                                Accept: 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ status: nextStatus }),
                        }
                    );

                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        throw new Error(
                            payload.message ||
                            `Unable to update the task (HTTP ${response.status}).`
                        );
                    }

                    window.location.reload();
                } catch (error) {
                    window.notifyUser(error.message || 'Unable to update the task. Your change was not confirmed; please try again.', { title: 'Task was not updated', variant: 'danger', icon: 'fa-solid fa-circle-exclamation' });
                    select.disabled = false;
                }
            });
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) {
                closeModal();
            }
        });
    };


    const initCollaborationRealtime = () => {
        if (window.__knowurlocalCollaborationSubscribed) return;
        const realtimeRoot = document.querySelector('[data-collaboration-realtime="true"]');
        const echo = window.Echo;
        const adminId = realtimeRoot?.dataset.adminId;

        if (!realtimeRoot || !adminId) {
            return;
        }

        if (!echo) {
            return;
        }

        try {
            window.__knowurlocalCollaborationSubscribed = true;
            echo.private(`admin.${adminId}`)
                .listen('.collaboration.task.updated', (event) => {
                    /*
                     * The server remains authoritative. A small reload keeps
                     * counters, ordering, permissions, and task state perfectly
                     * synchronized without duplicating backend business logic
                     * in the browser.
                     */
                    if (event?.task?.id) {
                        window.dispatchEvent(new CustomEvent('collaboration:updated', {
                            detail: event,
                        }));

                        window.setTimeout(() => {
                            window.location.reload();
                        }, 250);
                    }
                });
        } catch (error) {
            console.warn('Realtime collaboration is unavailable.', error);
        }
    };

    const initDashboard = () => {
        initCollaboration();
        initCollaborationRealtime();
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDashboard, { once: true });
    } else {
        initDashboard();
    }

    /*
     * Vite loads Echo as a deferred module while this legacy page script can
     * execute earlier. Listen for Echo's readiness so realtime subscription
     * is not lost because of script execution order.
     */
    window.addEventListener('knowurlocal:echo-ready', initCollaborationRealtime, { once: true });
})();
