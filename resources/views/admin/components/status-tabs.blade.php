{{--
    Shared admin dataset/status navigation.

    Every admin list page uses this same structure so Active,
    Trashed, Pending, Deactivated, and other dataset views keep
    identical spacing, icon treatment, active state, and counts.
--}}
<nav class="support-dataset-tabs admin-status-tabs-unified" aria-label="{{ $ariaLabel ?? 'Dataset views' }}">
    @foreach($tabs as $tab)
        <a
            href="{{ $tab['href'] }}"
            class="support-dataset-tab {{ !empty($tab['active']) ? 'is-active' : '' }}"
            aria-current="{{ !empty($tab['active']) ? 'page' : 'false' }}"
        >
            @if(!empty($tab['icon']))
                <i class="ph-light {{ $tab['icon'] }}" aria-hidden="true"></i>
            @endif

            <span>{{ $tab['label'] }}</span>

            @if(array_key_exists('count', $tab))
                <span class="support-dataset-count">
                    {{ $tab['count'] }}
                </span>
            @endif
        </a>
    @endforeach
</nav>
