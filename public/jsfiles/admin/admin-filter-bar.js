document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    const normalize = (value) =>
        String(value ?? '')
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();

    const escapeHtml = (value) =>
        String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');

    const createClearButton = (form) => {
        if (form.querySelector('.support-filter-clear')) {
            return;
        }

        const controls = Array.from(form.elements).filter((element) => {
            if (!element.name || element.type === 'hidden' || element.disabled) {
                return false;
            }

            return String(element.value ?? '').trim() !== '';
        });

        const hasActiveFilter = controls.some((element) => {
            const name = element.name;
            const value = String(element.value ?? '').trim();

            if (!value) {
                return false;
            }

            const defaults = {
                sort: ['desc', 'latest', 'newest'],
            };

            return !(defaults[name] || []).includes(value);
        });

        if (!hasActiveFilter) {
            return;
        }

        const clearUrl = new URL(form.action || window.location.href, window.location.origin);
        const currentUrl = new URL(window.location.href);
        const formData = new FormData(form);

        // Preserve hidden state such as the active dataset/status or log role.
        for (const [key, value] of formData.entries()) {
            if (form.elements[key] && form.elements[key].type === 'hidden') {
                clearUrl.searchParams.set(key, value);
            }
        }

        // Remove every visible filter and pagination state.
        const filterNames = new Set(
            Array.from(form.elements)
                .filter((element) => element.name && element.type !== 'hidden')
                .map((element) => element.name)
        );

        filterNames.forEach((name) => clearUrl.searchParams.delete(name));
        clearUrl.searchParams.delete('page');

        // Keep unrelated query parameters only when they are not part of this form.
        for (const [key] of currentUrl.searchParams.entries()) {
            if (filterNames.has(key) || key === 'page') {
                continue;
            }
            if (!clearUrl.searchParams.has(key)) {
                clearUrl.searchParams.set(key, currentUrl.searchParams.get(key));
            }
        }

        const clear = document.createElement('a');
        clear.href = clearUrl.toString();
        clear.className = 'support-filter-clear admin-icon-button';
        clear.setAttribute('aria-label', 'Clear filters');
        clear.setAttribute('title', 'Clear filters');
        clear.innerHTML = '<i class="ph-light ph-x" aria-hidden="true"></i><span class="sr-only">Clear filters</span>';

        const submit = form.querySelector('.support-filter-submit');
        if (submit) {
            submit.insertAdjacentElement('afterend', clear);
        } else {
            form.appendChild(clear);
        }
    };

    document.querySelectorAll('[data-searchable-agency-filter]').forEach((wrapper) => {
        const input = wrapper.querySelector('[data-search-input]');
        const select = wrapper.querySelector('[data-search-select]');
        const options = wrapper.querySelector('[data-search-options]');

        if (!input || !select || !options) {
            return;
        }

        const agencyOptions = Array.from(select.options).map((option) => ({
            value: option.value,
            name: option.dataset.agencyName || option.textContent.trim(),
            abbreviation: option.dataset.agencyAbbr || '',
        }));

        let isOpen = false;
        let activeIndex = -1;

        const selectedAgency = () =>
            agencyOptions.find((agency) => agency.value !== '' && agency.value === select.value) || null;

        const close = () => {
            isOpen = false;
            wrapper.classList.remove('is-open');
            input.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        };

        const render = (term = '') => {
            const normalizedTerm = normalize(term);
            const filtered = agencyOptions.filter((agency) => {
                if (!normalizedTerm) {
                    return true;
                }

                return normalize(`${agency.name} ${agency.abbreviation}`).includes(normalizedTerm);
            });

            options.innerHTML = '';

            if (!filtered.length) {
                options.innerHTML = '<div class="admin-searchable-filter-empty">No agencies found</div>';
                activeIndex = -1;
                return;
            }

            const fragment = document.createDocumentFragment();

            filtered.forEach((agency, index) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'admin-searchable-filter-option';
                button.dataset.value = agency.value;
                button.dataset.index = String(index);
                button.setAttribute('role', 'option');
                button.innerHTML = `
                    <span class="admin-searchable-filter-option-name">${escapeHtml(agency.name)}</span>
                    ${agency.abbreviation ? `<span class="admin-searchable-filter-option-abbr">${escapeHtml(agency.abbreviation)}</span>` : ''}
                `;

                button.addEventListener('mousedown', (event) => {
                    event.preventDefault();
                });

                button.addEventListener('click', () => {
                    select.value = agency.value;
                    input.value = agency.name;
                    close();
                });

                fragment.appendChild(button);
            });

            options.appendChild(fragment);
        };

        const open = () => {
            isOpen = true;
            wrapper.classList.add('is-open');
            input.setAttribute('aria-expanded', 'true');
            render('');
        };

        // Keep the selected agency readable while still allowing the admin to edit/search it.
        input.addEventListener('focus', () => {
            open();
            render(input.value === selectedAgency()?.name ? '' : input.value);
        });

        input.addEventListener('input', () => {
            const selected = selectedAgency();
            if (selected && input.value !== selected.name) {
                select.value = '';
            }

            open();
            render(input.value);
        });

        input.addEventListener('keydown', (event) => {
            const visibleOptions = Array.from(
                options.querySelectorAll('.admin-searchable-filter-option')
            );

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (!isOpen) {
                    open();
                }
                activeIndex = Math.min(activeIndex + 1, visibleOptions.length - 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
            } else if (event.key === 'Enter' && isOpen) {
                if (activeIndex >= 0 && visibleOptions[activeIndex]) {
                    event.preventDefault();
                    visibleOptions[activeIndex].click();
                } else if (visibleOptions.length === 1) {
                    event.preventDefault();
                    visibleOptions[0].click();
                }
            } else if (event.key === 'Escape') {
                close();
                const selected = selectedAgency();
                input.value = selected?.value ? selected.name : '';
            }

            visibleOptions.forEach((option, index) => {
                option.classList.toggle('is-active', index === activeIndex);
            });
        });

        document.addEventListener('click', (event) => {
            if (!wrapper.contains(event.target)) {
                close();
            }
        });

        // Restore the selected label after the browser repopulates the form from history.
        const selected = selectedAgency();
        input.value = selected?.value ? selected.name : '';
    });

    document.querySelectorAll('.support-filter-form').forEach(createClearButton);
});
