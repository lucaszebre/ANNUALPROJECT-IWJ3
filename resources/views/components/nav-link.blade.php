{{-- Header link: the current page is semibold ink, the others muted. --}}
@props(['href', 'active' => false])

<a
    href="{{ $href }}"
    @if ($active) aria-current="page" @endif
    {{ $attributes->merge(['class' => 'rounded-field text-sm whitespace-nowrap focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-primary '
        .($active ? 'font-semibold text-ink' : 'text-muted hover:text-ink')]) }}
>{{ $slot }}</a>
