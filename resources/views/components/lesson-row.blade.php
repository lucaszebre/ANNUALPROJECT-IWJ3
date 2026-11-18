{{--
    Lesson of a course programme (Figma "Listes / Leçon du programme"), 50 px high.
    $state: default, completed (✓ before the title), current (soft background, primary border)
    or locked (not in Figma: muted, not clickable, says which lessons to finish first).
--}}
@props(['lesson', 'number', 'state' => 'default', 'href' => null, 'missing' => collect()])

@php
    $locked = $state === 'locked';
    $tag = $href && ! $locked ? 'a' : 'div';
    $minutes = $lesson->durationMinutes();
@endphp

<li data-title="{{ mb_strtolower($lesson->title) }}" x-show="matches($el.dataset.title)">
    <{{ $tag }}
        @if ($tag === 'a') href="{{ $href }}" @endif
        @if ($state === 'current') aria-current="step" @endif
        @class([
            // Four columns when the row is wide enough: phones and the two-column layout from lg stack the type under the title.
            'grid min-h-[50px] grid-cols-[44px_minmax(0,1fr)] items-center gap-x-4 rounded-field border px-[15px] py-2 sm:grid-cols-[44px_minmax(0,1fr)_150px_70px] lg:grid-cols-[44px_minmax(0,1fr)] xl:grid-cols-[44px_minmax(0,1fr)_230px_86px]',
            'border-line bg-white' => in_array($state, ['default', 'completed']),
            'border-primary bg-soft' => $state === 'current',
            'border-line bg-canvas' => $locked,
            'hover:border-primary focus-visible:outline-2 focus-visible:outline-primary' => $tag === 'a',
        ])
    >
        <span class="text-label font-semibold text-muted">{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</span>
        <span @class(['font-semibold', 'text-ink' => ! $locked, 'text-muted' => $locked])>
            @if ($state === 'completed')<span aria-hidden="true">✓&nbsp;&nbsp;</span><span class="sr-only">Terminée : </span>@endif{{ $lesson->title }}
        </span>
        <span class="col-start-2 text-2xs text-muted sm:col-start-auto lg:col-start-2 xl:col-start-auto">
            @if ($locked)
                Verrouillée
                <span class="sr-only">: terminez d’abord {{ $missing->pluck('title')->implode(', ') }}</span>
            @else
                {{ $lesson->contentSummary() }}
            @endif
        </span>
        <span class="hidden text-2xs text-muted sm:block lg:hidden xl:block">{{ $minutes ? $minutes.' min' : '' }}</span>
    </{{ $tag }}>
    @if ($locked && $missing->isNotEmpty())
        <p class="mt-1 pl-[15px] text-2xs text-muted" aria-hidden="true">Terminez d’abord : {{ $missing->pluck('title')->implode(', ') }}</p>
    @endif
</li>
