@php
    $fieldId = $id ?? 'agency-filter';
    $fieldName = $name ?? 'agency';
    $fieldLabel = $label ?? 'Filter by agency';
    $selectedValue = (string) ($value ?? request($fieldName, ''));
    $selectedAgency = collect($agencies ?? [])->first(
        fn ($agency) => (string) $agency->id === $selectedValue
    );
    $selectedLabel = $selectedAgency?->agency_name ?? '';
@endphp

<div
    class="support-filter-field admin-searchable-filter"
    data-searchable-agency-filter
>
    <label for="{{ $fieldId }}-search" class="sr-only">
        {{ $fieldLabel }}
    </label>

    <i class="ph-light ph-buildings" aria-hidden="true"></i>

    <div class="admin-searchable-filter-control">
        <input
            type="search"
            id="{{ $fieldId }}-search"
            class="admin-searchable-filter-input"
            value="{{ $selectedLabel }}"
            placeholder="Search agency..."
            autocomplete="off"
            role="combobox"
            aria-expanded="false"
            aria-controls="{{ $fieldId }}-options"
            aria-autocomplete="list"
            data-search-input
        >

        <i
            class="ph-light ph-caret-down admin-searchable-filter-caret"
            aria-hidden="true"
        ></i>

        <div
            id="{{ $fieldId }}-options"
            class="admin-searchable-filter-options"
            role="listbox"
            aria-label="Agency options"
            data-search-options
        ></div>

        <select
            name="{{ $fieldName }}"
            id="{{ $fieldId }}"
            class="admin-searchable-filter-native"
            tabindex="-1"
            aria-hidden="true"
            data-search-select
        >
            <option value="">All Agencies</option>
            @foreach ($agencies ?? [] as $agency)
                <option
                    value="{{ $agency->id }}"
                    data-agency-name="{{ $agency->agency_name }}"
                    data-agency-abbr="{{ $agency->agency_abbreviation ?? '' }}"
                    {{ (string) $agency->id === $selectedValue ? 'selected' : '' }}
                >
                    {{ $agency->agency_name }}
                </option>
            @endforeach
        </select>
    </div>
</div>
