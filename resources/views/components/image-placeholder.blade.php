@props([
    'type' => 'image',
    'hidden' => false,
])

@php
    $placeholderIcon = match ($type) {
        'agency' => 'ph-buildings',
        'user' => 'ph-user',
        default => 'ph-image',
    };
@endphp

<span {{ $attributes->merge(['class' => 'kyl-image-placeholder kyl-image-placeholder--' . $type]) }} @if($hidden) hidden @endif aria-hidden="true">
    <i class="ph-light {{ $placeholderIcon }}"></i>
</span>
