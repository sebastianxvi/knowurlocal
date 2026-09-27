(() => {
    const modal = document.getElementById('collaboration-modal');
    const openButton = document.getElementById('open-collaboration-modal');
    const form = document.getElementById('collaboration-form');
    const typeSelect = document.getElementById('collaboration-target-type');
    const targetSelect = document.getElementById('collaboration-target-id');
    const errorEl = document.getElementById('collaboration-form-error');
    const submitButton = document.getElementById('collaboration-submit');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!modal || !openButton || !form) return;

    const openModal = () => {
        modal.hidden = false;
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('dashboard-collaboration-modal-open');
        form.querySelector('input[name="title"]')?.focus();
    };

    const closeModal = () => {
        modal.setAttribute('aria-hidden', 'true');
        modal.hidden = true;
        document.body.classList.remove('dashboard-collaboration-modal-open');
        errorEl.hidden = true;
        errorEl.textContent = '';
    };

    openButton.addEventListener('click', openModal);
    modal.querySelectorAll('[data-close-collaboration-modal]').forEach((el) => el.addEventListener('click', closeModal));

    const setError = (message) => {
        errorEl.textContent = message;
        errorEl.hidden = !message;
    };

    const loadTargets = async () => {
        const type = typeSelect.value;
        targetSelect.replaceChildren();
        targetSelect.append(new Option(type ? 'Loading records…' : 'Choose a record', ''));
        targetSelect.disabled = !type;
        if (!type) return;

        try {
            const response = await fetch(`{{ route('admin.dashboard.collaboration.targets') }}?type=${encodeURIComponent(type)}`, {
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(payload.message || 'Unable to load records.');

            targetSelect.replaceChildren();
            targetSelect.append(new Option('Choose a record', ''));
            payload.data.forEach((item) => targetSelect.append(new Option(item.label, item.id)));
            targetSelect.disabled = payload.data.length === 0;
            if (!payload.data.length) targetSelect.append(new Option('No records found', ''));
        } catch (error) {
            targetSelect.replaceChildren(new Option('Unable to load records', ''));
            targetSelect.disabled = true;
        }
    };

    typeSelect.addEventListener('change', loadTargets);

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        setError('');
        submitButton.disabled = true;
        submitButton.dataset.originalText = submitButton.textContent;
        submitButton.textContent = 'Creating…';

        const body = new FormData(form);

        try {
            const response = await fetch('{{ route('admin.dashboard.collaboration.store') }}', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body,
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
                throw new Error(validation || payload.message || 'Unable to create the collaboration task.');
            }

            window.location.reload();
        } catch (error) {
            setError(error.message || 'Unable to create the collaboration task.');
            submitButton.disabled = false;
            submitButton.textContent = submitButton.dataset.originalText || 'Create task';
        }
    });

    document.querySelectorAll('[data-collaboration-status]').forEach((select) => {
        select.addEventListener('change', async () => {
            const taskId = select.dataset.taskId;
            const nextStatus = select.value;
            select.disabled = true;

            try {
                const response = await fetch(`{{ url('/admin/dashboard/collaboration') }}/${taskId}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ status: nextStatus }),
                });
                const payload = await response.json().catch(() => ({}));
                if (!response.ok) throw new Error(payload.message || 'Unable to update the task.');
                window.location.reload();
            } catch (error) {
                window.alert(error.message || 'Unable to update the task.');
                select.disabled = false;
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.hidden) closeModal();
    });
})();
