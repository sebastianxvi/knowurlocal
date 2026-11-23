(() => {
    const initCollaboration = () => {
        const modal = document.getElementById('collaboration-modal');
        const openButton = document.getElementById('open-collaboration-modal');
        const form = document.getElementById('collaboration-form');
        if (!modal || !openButton || !form || form.dataset.collaborationReady === 'true') return;
        form.dataset.collaborationReady = 'true';

        const typeSelect = form.querySelector('#collaboration-target-type');
        const targetId = form.querySelector('#collaboration-target-id');
        const targetSearch = form.querySelector('[data-target-search]');
        const targetResults = form.querySelector('[data-target-results]');
        const targetSelected = form.querySelector('[data-target-selected]');
        const targetPicker = form.querySelector('[data-target-picker]');
        const recipientPicker = form.querySelector('[data-recipient-picker]');
        const recipientSearch = form.querySelector('[data-recipient-search]');
        const recipientDropdown = form.querySelector('[data-recipient-dropdown]');
        const recipientValues = form.querySelector('[data-recipient-values]');
        const bulkAgencyFlag = form.querySelector('[data-bulk-agency-flag]');
        const recipientOptions = Array.from(form.querySelectorAll('[data-recipient-option][data-recipient-id]'));
        const selectAllOption = form.querySelector('[data-select-all-option]');
        const errorEl = form.querySelector('#collaboration-form-error');
        const submitButton = form.querySelector('#collaboration-submit');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const storeUrl = form.dataset.storeUrl || '';
        const targetsUrl = form.dataset.targetsUrl || '';
        const statusBaseUrl = form.dataset.statusBaseUrl || '';
        const recipientLimit = Number(form.dataset.recipientLimit || 0);
        let selectedRecipientIds = [];
        let selectedRecipientRole = '';
        let selectedRecipientLabel = '';
        let selectedAllAgencyAdmins = false;
        let targetRequest = 0;
        let targetTimer = null;

        const setError = (message = '') => {
            if (!errorEl) return;
            errorEl.textContent = message;
            errorEl.hidden = !message;
        };
        const setRecipientValues = (ids) => {
            selectedRecipientIds = ids.map(String);
            if (recipientValues) {
                recipientValues.replaceChildren();
                selectedRecipientIds.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'assigned_to_ids[]';
                    input.value = id;
                    recipientValues.append(input);
                });
            }
        };
        const closeRecipientDropdown = () => {
            if (recipientDropdown) recipientDropdown.hidden = true;
            recipientSearch?.setAttribute('aria-expanded', 'false');
        };
        const openRecipientDropdown = () => {
            if (!recipientDropdown) return;
            recipientDropdown.hidden = false;
            recipientSearch?.setAttribute('aria-expanded', 'true');
            filterRecipients();
        };
        const filterRecipients = () => {
            const query = (recipientSearch?.value || '').trim().toLocaleLowerCase();
            recipientOptions.forEach((option) => {
                option.hidden = Boolean(query) && !(option.dataset.search || '').toLocaleLowerCase().includes(query);
            });
            if (selectAllOption) {
                const hasAgencyAdmins = recipientOptions.some((option) => option.dataset.role === 'agency_admin' && !option.hidden);
                selectAllOption.hidden = recipientLimit === 1 || (Boolean(query) && !hasAgencyAdmins);
            }
        };
        const clearTarget = () => {
            if (targetId) targetId.value = '';
            if (targetSelected) targetSelected.textContent = 'No record selected';
        };
        const closeTargetDropdown = () => {
            if (targetResults) targetResults.hidden = true;
            targetSearch?.setAttribute('aria-expanded', 'false');
        };
        const updateTargetOptions = (bulkAgency = false) => {
            if (!typeSelect || !targetSearch) return;
            const agencyRecipient = selectedRecipientRole === 'agency_admin';
            Array.from(typeSelect.options).forEach((option) => {
                if (!option.value) return;
                const allowedForAgency = ['agency', 'faq', 'support_request'].includes(option.value);
                const allowedForBulk = option.value === 'agency';
                option.hidden = bulkAgency ? !allowedForBulk : (agencyRecipient && !allowedForAgency);
                option.disabled = bulkAgency ? !allowedForBulk : (agencyRecipient && !allowedForAgency);
            });
            if (bulkAgency) {
                typeSelect.value = 'agency';
                targetSearch.disabled = true;
                targetSearch.value = 'Each recipient agency profile will be linked automatically';
                clearTarget();
                closeTargetDropdown();
                targetPicker?.classList.add('is-auto-target');
                const note = form.querySelector('[data-collaboration-guidance]');
                if (note) note.textContent = 'A separate task will be created for every active Agency Admin and linked to that recipient’s own agency profile.';
                return;
            }
            targetPicker?.classList.remove('is-auto-target');
            if (!selectedRecipientIds.length || (agencyRecipient && !['agency', 'faq', 'support_request'].includes(typeSelect.value))) {
                typeSelect.value = '';
            }
            targetSearch.disabled = !typeSelect.value;
            if (targetSearch.disabled) targetSearch.value = '';
            const note = form.querySelector('[data-collaboration-guidance]');
            if (note) note.textContent = agencyRecipient
                ? 'Agency Admin assignments must reference a record in that recipient’s agency and include clear instructions.'
                : 'Choose work that matches the recipient’s responsibilities and link the relevant record.';
        };
        const selectRecipient = (option) => {
            const id = String(option.dataset.recipientId || '');
            const role = option.dataset.role || '';
            const label = option.querySelector('strong')?.textContent?.trim() || 'Administrator';
            if (!id) return;
            setRecipientValues([id]);
            selectedAllAgencyAdmins = false;
            if (bulkAgencyFlag) bulkAgencyFlag.value = '0';
            selectedRecipientRole = role;
            selectedRecipientLabel = label;
            if (recipientSearch) recipientSearch.value = label;
            closeRecipientDropdown();
            clearTarget();
            if (targetSearch) targetSearch.value = '';
            updateTargetOptions(false);
            setError('');
        };
        const selectAllAgencyAdmins = () => {
            const agencyOptions = recipientOptions.filter((option) => option.dataset.role === 'agency_admin');
            if (!agencyOptions.length) {
                setError('There are no active Agency Admins available for bulk assignment.');
                return;
            }
            setRecipientValues(agencyOptions.map((option) => option.dataset.recipientId));
            selectedAllAgencyAdmins = true;
            if (bulkAgencyFlag) bulkAgencyFlag.value = '1';
            selectedRecipientRole = 'agency_admin';
            selectedRecipientLabel = `All Agency Admins (${agencyOptions.length})`;
            if (recipientSearch) recipientSearch.value = selectedRecipientLabel;
            closeRecipientDropdown();
            clearTarget();
            updateTargetOptions(true);
            setError('');
        };
        const renderTargetMessage = (message, isError = false) => {
            if (!targetResults) return;
            targetResults.replaceChildren();
            const item = document.createElement('div');
            item.className = `dashboard-target-empty${isError ? ' is-error' : ''}`;
            item.textContent = message;
            targetResults.append(item);
            targetResults.hidden = false;
        };
        const loadTargets = async () => {
            if (!typeSelect || !targetSearch || !targetResults || targetSearch.disabled) return;
            const type = typeSelect.value;
            if (!type) { targetResults.hidden = true; return; }
            const search = targetSearch.value.trim();
            const requestId = ++targetRequest;
            targetResults.replaceChildren();
            renderTargetMessage('Loading records…');
            if (!targetsUrl) { renderTargetMessage('The record service is unavailable.', true); return; }
            try {
                const url = new URL(targetsUrl, window.location.origin);
                url.searchParams.set('type', type);
                if (search) url.searchParams.set('search', search);
                if (selectedRecipientIds.length === 1) url.searchParams.set('assigned_to_id', selectedRecipientIds[0]);
                const response = await fetch(url.toString(), {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Unable to load records.');
                if (requestId !== targetRequest) return;
                const items = Array.isArray(payload.data) ? payload.data : [];
                targetResults.replaceChildren();
                if (!items.length) {
                    renderTargetMessage(search ? 'No matching records found.' : 'No records are available for this type.');
                    return;
                }
                items.forEach((item) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'dashboard-target-option';
                    option.setAttribute('role', 'option');
                    option.textContent = String(item.label ?? 'Record');
                    option.addEventListener('click', () => {
                        if (targetId) targetId.value = String(item.id);
                        if (targetSearch) targetSearch.value = String(item.label ?? 'Record');
                        if (targetSelected) targetSelected.textContent = `Selected: ${String(item.label ?? 'Record')}`;
                        closeTargetDropdown();
                    });
                    targetResults.append(option);
                });
                targetResults.hidden = false;
            } catch (error) {
                if (requestId !== targetRequest) return;
                renderTargetMessage(error.message || 'Unable to load records.', true);
            }
        };
        const scheduleTargetSearch = () => {
            clearTimeout(targetTimer);
            clearTarget();
            targetTimer = setTimeout(loadTargets, 180);
        };

        const openModal = () => {
            modal.hidden = false;
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('dashboard-collaboration-modal-open');
            closeRecipientDropdown();
            closeTargetDropdown();
            form.querySelector('[name="title"]')?.focus();
        };
        const closeModal = () => {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('dashboard-collaboration-modal-open');
            closeRecipientDropdown();
            closeTargetDropdown();
            setError('');
        };
        openButton.addEventListener('click', (event) => { event.preventDefault(); openModal(); });
        modal.querySelectorAll('[data-close-collaboration-modal]').forEach((element) => {
            element.addEventListener('click', (event) => { event.preventDefault(); closeModal(); });
        });
        recipientSearch?.addEventListener('focus', openRecipientDropdown);
        recipientSearch?.addEventListener('click', openRecipientDropdown);
        recipientSearch?.addEventListener('input', () => {
            if (selectedRecipientIds.length) {
                setRecipientValues([]);
                selectedAllAgencyAdmins = false;
                if (bulkAgencyFlag) bulkAgencyFlag.value = '0';
                selectedRecipientRole = '';
                selectedRecipientLabel = '';
                clearTarget();
                if (targetSearch) targetSearch.value = '';
                updateTargetOptions(false);
            }
            openRecipientDropdown();
            filterRecipients();
        });
        recipientOptions.forEach((option) => option.addEventListener('click', () => selectRecipient(option)));
        selectAllOption?.addEventListener('click', selectAllAgencyAdmins);
        typeSelect?.addEventListener('change', () => {
            clearTarget();
            if (targetSearch) targetSearch.value = '';
            if (targetSearch) targetSearch.disabled = !typeSelect.value;
            if (typeSelect.value) {
                targetSearch?.focus();
                loadTargets();
            } else closeTargetDropdown();
        });
        targetSearch?.addEventListener('focus', () => { if (!targetSearch.disabled && typeSelect?.value) loadTargets(); });
        targetSearch?.addEventListener('click', () => { if (!targetSearch.disabled && typeSelect?.value) loadTargets(); });
        targetSearch?.addEventListener('input', scheduleTargetSearch);
        document.addEventListener('click', (event) => {
            if (recipientPicker && !recipientPicker.contains(event.target)) closeRecipientDropdown();
            if (targetPicker && !targetPicker.contains(event.target)) closeTargetDropdown();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) closeModal();
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setError('');
            if (!selectedRecipientIds.length) { setError('Search for and select an administrator.'); recipientSearch?.focus(); return; }
            if (!storeUrl) { setError('The collaboration service endpoint is unavailable. Please refresh the page and try again.'); return; }
            if (!csrf) { setError('Your session token is unavailable. Please refresh the page and try again.'); return; }
            if (!typeSelect?.value) { setError('Choose a related record type.'); return; }
            const bulkAgency = selectedAllAgencyAdmins || (selectedRecipientIds.length > 1 && selectedRecipientRole === 'agency_admin');
            if (!bulkAgency && !targetId?.value) { setError('Choose a record from the related-record suggestions.'); targetSearch?.focus(); return; }
            submitButton?.setAttribute('disabled', 'disabled');
            const originalText = submitButton?.textContent?.trim() || 'Create task';
            if (submitButton) submitButton.textContent = bulkAgency ? `Creating ${selectedRecipientIds.length} tasks…` : 'Creating…';
            try {
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                    body: new FormData(form),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                    throw new Error(validation || payload.message || `Unable to create the collaboration task (HTTP ${response.status}).`);
                }
                window.location.reload();
            } catch (error) {
                setError(error.message || 'Unable to create the collaboration task.');
                if (submitButton) { submitButton.removeAttribute('disabled'); submitButton.textContent = originalText; }
            }
        });

        document.querySelectorAll('[data-collaboration-status]').forEach((select) => {
            select.addEventListener('change', async () => {
                const taskId = select.dataset.taskId;
                const nextStatus = select.value;
                if (!taskId || !statusBaseUrl || !csrf) return;
                select.disabled = true;
                try {
                    const response = await fetch(`${statusBaseUrl}/${encodeURIComponent(taskId)}/status`, {
                        method: 'PATCH',
                        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ status: nextStatus }),
                    });
                    const payload = await response.json().catch(() => ({}));
                    if (!response.ok) throw new Error(payload.message || `Unable to update the task (HTTP ${response.status}).`);
                    window.location.reload();
                } catch (error) {
                    window.notifyUser(error.message || 'Unable to update the task. Your change was not confirmed; please try again.', { title: 'Task was not updated', variant: 'danger', icon: 'fa-solid fa-circle-exclamation' });
                    select.disabled = false;
                }
            });
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

    const initDashboardMetricMotion = () => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const formatter = new Intl.NumberFormat();
        document.querySelectorAll('.dashboard-kpi-copy strong:not([data-no-count-animation]), .dashboard-kpi strong:not([data-no-count-animation])').forEach(node => {
            const raw = (node.textContent || '').trim();
            const target = Number(raw.replace(/[^\d.-]/g, ''));
            if (!raw || !Number.isFinite(target) || target <= 0) return;
            const duration = 650;
            const startAt = performance.now();
            const tick = now => {
                const progress = Math.min(1, (now - startAt) / duration);
                const eased = 1 - Math.pow(1 - progress, 3);
                node.textContent = formatter.format(Math.round(target * eased));
                if (progress < 1) requestAnimationFrame(tick);
            };
            node.textContent = '0';
            requestAnimationFrame(tick);
        });
    };

    const initDashboard = () => {
        initCollaboration();
        initCollaborationRealtime();
        initDashboardMetricMotion();
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
