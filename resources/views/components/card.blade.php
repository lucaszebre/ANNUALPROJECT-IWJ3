{{--
    Card: white surface, 12 px radius, line border.
    Optional $title (h3) and $actions slots; padding="none" for media cards.
--}}
@props(['title' => null, 'padding' => 'md'])

@php
    $paddings = ['none' => '', 'sm' => 'p-4', 'md' => 'p-5 sm:p-6'];
@endphp

<article {{ $attributes->merge(['class' => 'flex flex-col gap-4 rounded-card border border-line bg-white '.$paddings[$padding]]) }}>
    @if ($title)
        <h3 class="text-body-lg text-ink">{{ $title }}</h3>
    @endif

    {{ $slot }}

    @isset($actions)
        <div {{ $actions->attributes->merge(['class' => 'mt-auto flex flex-wrap gap-3']) }}>{{ $actions }}</div>
    @endisset
</article>
