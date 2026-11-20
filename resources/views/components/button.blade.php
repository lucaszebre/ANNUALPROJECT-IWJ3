{{--
    Button of the design system (Figma "Actions / Bouton").
    Renders a link when href is given, a button otherwise.
    size: md (46 px, forms and pages) or sm (40 px, the header).
--}}
@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'submit',
    'loading' => false,
    'disabled' => false,
])

@php
    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary-hover',
        'secondary' => 'border border-line bg-white text-ink hover:bg-canvas',
        'ghost' => 'bg-canvas text-ink hover:bg-soft',
    ];

    $sizes = [
        'md' => 'h-button px-[18px]',
        'sm' => 'h-button-sm px-4',
    ];

    $classes = 'inline-flex items-center justify-center gap-2.5 rounded-button text-sm font-semibold whitespace-nowrap transition-colors '
        .$sizes[$size].' '
        .'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary '
        .($disabled ? 'cursor-not-allowed bg-line text-muted' : $variants[$variant]);
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button
        type="{{ $type }}"
        @disabled($disabled || $loading)
        @if ($loading) aria-busy="true" @endif
        {{ $attributes->merge(['class' => $classes]) }}
    >
        @if ($loading)
            <x-icon name="spinner" class="animate-spin" />
        @endif
        {{ $slot }}
    </button>
@endif
