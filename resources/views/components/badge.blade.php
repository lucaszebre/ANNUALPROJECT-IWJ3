{{-- Status badge (Figma "Statuts / Badge"), 28 px high. --}}
@props(['tone' => 'soft'])

@php
    $tones = [
        'soft' => 'bg-soft text-primary',
        'white' => 'bg-white text-primary',
        'canvas' => 'bg-canvas text-muted',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex h-7 items-center rounded-[14px] px-3 text-2xs font-semibold '.$tones[$tone]]) }}>{{ $slot }}</span>
