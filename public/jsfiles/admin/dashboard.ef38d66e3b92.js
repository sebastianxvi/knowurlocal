(() => {
    const initCollaboration = () => {
        const modal = document.getElementById('collaboration-modal');
        const openButton = document.getElementById('open-collaboration-modal');
        const form = document.getElementById('collaboration-form');
        if (!modal || !openButton || !form) return;

        const typeSelect = document.getElementById('collaboration-target-type');
        const targetId = document.getElementById('collaboration-target-id');
        const targetSearch = form.querySelector('[data-target-search]');
        const targetResults = form.querySelector('[data-target-results]');
        const targetSelected = form.querySelector('[data-target-selected]');
        const targetPicker = form.querySelector('[data-target-picker]');
        const recipientPicker = form.querySelector('[data-recipient-picker]');
        const recipientSearch = form.querySelector('[data-recipient-search]');
        const recipientTrigger = form.querySelector('[data-recipient-trigger]');
        const recipientDropdown = form.querySelector('[data-recipient-dropdown]');
        const recipientTriggerLabel = form.querySelector('[data-recipient-trigger-label]');
        const targetTrigger = form.querySelector('[data-target-trigger]');
        const targetDropdown = form.querySelector('[data-target-dropdown]');
        const targetTriggerLabel = form.querySelector('[data-target-trigger-label]');
        const recipientCount = form.querySelector('[data-recipient-count]');
        const selectAllAgencies = form.querySelector('[data-select-all-agencies]');
        const recipientCheckboxes = Array.from(form.querySelectorAll('[data-recipient-checkbox]'));
        const targetTypeOptions = typeSelect ? Array.from(typeSelect.options) : [];
        const errorEl = document.getElementById('collaboration-form-error');
        const submitButton = document.getElementById('collaboration-submit');
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const storeUrl = form.dataset.storeUrl || '';
        const targetsUrl = form.dataset.targetsUrl || '';
        const statusBaseUrl = form.dataset.statusBaseUrl || '';
        const recipientLimit = Number(form.dataset.recipientLimit || 0);
        let targetRequest = 0;

        const setError = (message = '') => {
            if (!errorEl) return;
            errorEl.textContent = message;
            errorEl.hidden = !message;
        };
        const selectedRecipients = () => recipientCheckboxes.filter((input) => input.checked);
        const setTargetSelection = (id = '', label = '') => {
            if (targetId) targetId.value = id;
            if (targetSelected) targetSelected.textContent = label || 'No record selected';
            if (targetTriggerLabel) targetTriggerLabel.textContent = label || 'Choose a record';
            targetResults?.replaceChildren();
        };
        const updateRecipientSummary = () => {
            const selected = selectedRecipients();
            if (recipientCount) recipientCount.textContent = `${selected.length} selected`;
            if (recipientTriggerLabel) {
                const names = selected.map((input) => input.closest('[data-recipient-option]')?.querySelector('strong')?.textContent?.trim()).filter(Boolean);
                recipientTriggerLabel.textContent = names.length === 0 ? (recipientLimit === 1 ? 'Choose administrator' : 'Choose administrator(s)')
                    : names.length === 1 ? names[0] : `${names.length} administrators selected`;
            }
            if (selectAllAgencies) {
                const agencyInputs = recipientCheckboxes.filter((input) => input.dataset.role === 'agency_admin');
                selectAllAgencies.checked = agencyInputs.length > 0 && agencyInputs.every((input) => input.checked);
                selectAllAgencies.indeterminate = agencyInputs.some((input) => input.checked) && !selectAllAgencies.checked;
            }
        };
        const syncRecipientSelection = (changedInput = null) => {
            if (recipientLimit === 1 && changedInput?.checked) {
                recipientCheckboxes.forEach((input) => { if (input !== changedInput) input.checked = false; });
            }
            const selected = selectedRecipients();
            const isBulkAgency = selected.length > 1 && selected.every((input) => input.dataset.role === 'agency_admin');
            if (selected.length > 1 && !isBulkAgency) {
                if (changedInput) changedInput.checked = false;
                setError('Bulk assignment is available for Agency Admins only. Select one System Admin or multiple Agency Admins.');
                return;
            }
            setError('');
            updateRecipientSummary();
            setTargetSelection();
            if (targetSearch) targetSearch.value = '';
            syncTargetOptions(isBulkAgency);
        };
        const syncTargetOptions = (isBulkAgency = false) => {
            if (!typeSelect) return;
            const selected = selectedRecipients();
            const recipientRole = selected.length === 1 ? selected[0].dataset.role : '';
            const agencyRecipient = recipientRole === 'agency_admin';
            targetTypeOptions.forEach((option) => {
                if (!option.value) return;
                const allowedForAgency = ['agency', 'faq', 'support_request'].includes(option.value);
                const allowedForBulk = option.value === 'agency';
                option.hidden = isBulkAgency ? !allowedForBulk : (agencyRecipient && !allowedForAgency);
                option.disabled = isBulkAgency ? !allowedForBulk : (agencyRecipient && !allowedForAgency);
            });
            if (isBulkAgency) {
                typeSelect.value = 'agency';
                typeSelect.setAttribute('aria-label', 'Each recipient agency profile will be linked automatically');
                targetSearch && (targetSearch.disabled = true);
                if (targetTrigger) targetTrigger.disabled = true;
                targetSearch && (targetSearch.value = '');
                setTargetSelection('', 'Each selected administrator will receive a task linked to their own agency profile.');
                if (targetPicker) targetPicker.classList.add('is-auto-target');
                const note = form.querySelector('[data-collaboration-guidance]');
                if (note) note.textContent = 'Bulk assignment creates a separate task for every selected Agency Admin and automatically links each task to that administrator’s own agency profile.';
                return;
            }
            if (targetPicker) targetPicker.classList.remove('is-auto-target');
            if (typeSelect.value && ((agencyRecipient && !['agency', 'faq', 'support_request'].includes(typeSelect.value)) || (selected.length === 0))) {
                typeSelect.value = '';
            }
            if (targetSearch) targetSearch.disabled = !typeSelect.value;
            if (targetTrigger) targetTrigger.disabled = !typeSelect.value;
            const note = form.querySelector('[data-collaboration-guidance]');
            if (note) note.textContent = agencyRecipient
                ? 'Agency Admin assignments must reference a record in that recipient’s agency and include clear instructions.'
                : 'Choose work that matches the recipient’s responsibilities and link the relevant record.';
            loadTargets();
        };

        const loadTargets = async () => {
            if (!typeSelect || !targetSearch || !targetResults) return;
            const type = typeSelect.value;
            const search = targetSearch.value.trim();
            const selected = selectedRecipients();
            const bulkAgency = selected.length > 1 && selected.every((input) => input.dataset.role === 'agency_admin');
            if (!type || bulkAgency) {
                targetSearch.disabled = true;
                targetResults.replaceChildren();
                return;
            }
            targetSearch.disabled = false;
            const requestId = ++targetRequest;
            targetResults.replaceChildren();
            if (!search) {
                const helper = document.createElement('div');
                helper.className = 'dashboard-target-empty';
                helper.textContent = 'Enter a few letters to find a record.';
                targetResults.append(helper);
                return;
            }
            const loading = document.createElement('div');
            loading.className = 'dashboard-target-empty';
            loading.textContent = 'Searching…';
            targetResults.append(loading);
            if (!targetsUrl) return;
            try {
                const url = new URL(targetsUrl, window.location.origin);
                url.searchParams.set('type', type);
                if (search) url.searchParams.set('search', search);
                if (selected.length === 1) url.searchParams.set('assigned_to_id', selected[0].value);
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
                    const empty = document.createElement('div');
                    empty.className = 'dashboard-target-empty';
                    empty.textContent = search ? 'No matching records found.' : 'No records available. Try a search term.';
                    targetResults.append(empty);
                    return;
                }
                items.forEach((item) => {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.className = 'dashboard-target-option';
                    option.setAttribute('role', 'option');
                    option.textContent = String(item.label ?? 'Record');
                    option.addEventListener('click', () => {
                        setTargetSelection(String(item.id), String(item.label ?? 'Record'));
                        targetSearch.value = String(item.label ?? '');
                        targetResults.replaceChildren();
                        if (targetDropdown) targetDropdown.hidden = true;
                        targetTrigger?.setAttribute('aria-expanded', 'false');
                    });
                    targetResults.append(option);
                });
            } catch (error) {
                if (requestId !== targetRequest) return;
                targetResults.replaceChildren();
                const failure = document.createElement('div');
                failure.className = 'dashboard-target-empty is-error';
                failure.textContent = error.message || 'Unable to load records.';
                targetResults.append(failure);
            }
        };

        const openModal = () => {
            modal.hidden = false;
            if (recipientDropdown) recipientDropdown.hidden = true;
            if (targetDropdown) targetDropdown.hidden = true;
            recipientTrigger?.setAttribute('aria-expanded', 'false');
            targetTrigger?.setAttribute('aria-expanded', 'false');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('dashboard-collaboration-modal-open');
            form.querySelector('[name="title"]')?.focus();
        };
        const closeModal = () => {
            modal.hidden = true;
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('dashboard-collaboration-modal-open');
            setError('');
        };
        openButton.addEventListener('click', (event) => { event.preventDefault(); openModal(); });
        modal.querySelectorAll('[data-close-collaboration-modal]').forEach((element) => {
            element.addEventListener('click', (event) => { event.preventDefault(); closeModal(); });
        });
        recipientTrigger?.addEventListener('click', () => {
            const willOpen = recipientDropdown?.hidden ?? false;
            if (recipientDropdown) recipientDropdown.hidden = !willOpen;
            recipientTrigger.setAttribute('aria-expanded', String(willOpen));
            if (willOpen) recipientSearch?.focus();
        });
        targetTrigger?.addEventListener('click', () => {
            if (targetTrigger.disabled || !targetDropdown) return;
            const willOpen = targetDropdown.hidden;
            targetDropdown.hidden = !willOpen;
            targetTrigger.setAttribute('aria-expanded', String(willOpen));
            if (willOpen) { targetSearch?.focus(); loadTargets(); }
        });
        document.addEventListener('click', (event) => {
            if (recipientPicker && !recipientPicker.contains(event.target) && recipientDropdown) {
                recipientDropdown.hidden = true;
                recipientTrigger?.setAttribute('aria-expanded', 'false');
            }
            if (targetPicker && !targetPicker.contains(event.target) && targetDropdown) {
                targetDropdown.hidden = true;
                targetTrigger?.setAttribute('aria-expanded', 'false');
            }
        });
        recipientSearch?.addEventListener('input', () => {
            const query = recipientSearch.value.trim().toLocaleLowerCase();
            recipientPicker?.querySelectorAll('[data-recipient-option]').forEach((option) => {
                option.hidden = query !== '' && !(option.dataset.search || '').includes(query);
            });
        });
        recipientCheckboxes.forEach((input) => input.addEventListener('change', () => {
            syncRecipientSelection(input);
            if (recipientLimit === 1 && input.checked && recipientDropdown) {
                recipientDropdown.hidden = true;
                recipientTrigger?.setAttribute('aria-expanded', 'false');
            }
        }));
        selectAllAgencies?.addEventListener('change', () => {
            recipientCheckboxes.filter((input) => input.dataset.role === 'agency_admin')
                .forEach((input) => { input.checked = selectAllAgencies.checked; });
            syncRecipientSelection();
        });
        typeSelect?.addEventListener('change', () => {
            setTargetSelection();
            if (targetSearch) targetSearch.value = '';
            loadTargets();
        });
        targetSearch?.addEventListener('input', () => {
            setTargetSelection();
            loadTargets();
        });
        updateRecipientSummary();
        syncTargetOptions(false);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            setError('');
            if (!selectedRecipients().length) { setError('Choose at least one administrator.'); return; }
            const selected = selectedRecipients();
            const bulkAgency = selected.length > 1 && selected.every((input) => input.dataset.role === 'agency_admin');
            if (!storeUrl) { setError('The collaboration service endpoint is unavailable. Please refresh the page and try again.'); return; }
            if (!csrf) { setError('Your session token is unavailable. Please refresh the page and try again.'); return; }
            if (!typeSelect?.value) { setError('Choose a related record type.'); return; }
            if (!bulkAgency && !targetId?.value) { setError('Search for and select a related record.'); targetSearch?.focus(); return; }
            submitButton?.setAttribute('disabled', 'disabled');
            const originalText = submitButton?.textContent?.trim() || 'Create task';
            if (submitButton) submitButton.textContent = bulkAgency ? `Creating ${selected.length} tasks…` : 'Creating…';
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
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.hidden) closeModal();
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
