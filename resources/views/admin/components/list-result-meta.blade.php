@props([
    'count' => 0,
    'label' => 'record',
    'icon' => 'ph-list-dashes',
])

@php
    $displayLabel = \Illuminate\Support\Str::plural($label, (int) $count);
@endphp

<div class="admin-list-result-meta" role="status" aria-live="polite">
    <i class="ph-light {{ $icon }}" aria-hidden="true"></i>
    <span>{{ number_format((int) $count) }} {{ $displayLabel }}</span>
</div>
