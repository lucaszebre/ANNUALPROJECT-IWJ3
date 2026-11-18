{{--
    Course cover (Figma "Médias / Couverture de cours"): short label and a line of code,
    in the course colour. size="card" in the catalogue, size="hero" on the course page.
--}}
@props(['course', 'size' => 'card'])

@php
    $hero = $size === 'hero';
    // 57 px fits "PHP" in the hero tile (Figma); longer labels get smaller so they never get cut.
    $heroText = match (true) {
        mb_strlen($course->coverLabel()) <= 4 => 'text-[57px]',
        mb_strlen($course->coverLabel()) <= 7 => 'text-[40px]',
        default => 'text-[30px]',
    };
@endphp

<div aria-hidden="true" {{ $attributes->merge(['class' => $course->cover_tone->classes().' overflow-hidden '
    .($hero ? 'flex h-[220px] w-[224px] flex-col rounded-panel px-6 pt-[35px]' : 'h-[92px] rounded-button px-5 pt-4')]) }}>
    <p @class([
        'font-display font-bold leading-[1.22]',
        'truncate' => ! $hero,
        'break-words' => $hero,
        $heroText.' tracking-[-0.03em]' => $hero,
        'text-title-lg tracking-[-0.03em]' => ! $hero,
    ])>{{ $course->coverLabel() }}</p>
    @if ($course->cover_code)
        <p @class([
            'font-mono whitespace-pre-wrap',
            'mt-auto mb-[35px] text-[13px] leading-[1.5]' => $hero,
            'mt-1 truncate text-[11px] leading-[1.5]' => ! $hero,
        ])>{{ $course->cover_code }}</p>
    @endif
</div>
