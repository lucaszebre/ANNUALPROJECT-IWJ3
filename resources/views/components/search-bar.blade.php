{{--
    Search bar of the design system (Figma "Recherche / Barre").
    sm: header of a signed-in student (258 x 42), md: visitor header (360 x 42),
    lg: search page (54 px high, white with a primary border).
--}}
@props([
    'action' => '#',
    'name' => 'q',
    'value' => null,
    'placeholder' => 'Un cours, une notion…',
    'size' => 'md',
    'label' => 'Rechercher',
])

@php
    $sizes = [
        'sm' => ['form' => 'h-[42px] w-full lg:w-[258px] bg-canvas rounded-field', 'input' => 'pl-[42px] text-2xs', 'icon' => 'search', 'iconPos' => 'left-[13px]'],
        'md' => ['form' => 'h-[42px] w-full lg:w-[360px] bg-canvas rounded-field', 'input' => 'pl-11 text-label', 'icon' => 'search', 'iconPos' => 'left-[14px]'],
        'lg' => ['form' => 'h-[54px] w-full bg-white border border-primary rounded-tile', 'input' => 'pl-[53px] text-base', 'icon' => 'search-lg', 'iconPos' => 'left-[15px]'],
    ][$size];
@endphp

<form method="GET" action="{{ $action }}" role="search" {{ $attributes->merge(['class' => 'relative flex items-center '.$sizes['form']]) }}>
    <x-icon :name="$sizes['icon']" class="pointer-events-none absolute {{ $sizes['iconPos'] }}" />
    <label for="search-{{ $size }}" class="sr-only">{{ $label }}</label>
    <input
        id="search-{{ $size }}"
        type="search"
        name="{{ $name }}"
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        class="h-full w-full rounded-[inherit] bg-transparent pr-4 text-ink placeholder:text-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-primary {{ $sizes['input'] }}"
    >
</form>
