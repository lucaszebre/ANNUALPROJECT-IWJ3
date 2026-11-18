{{-- Progress bar (Figma "Progression / Barre"), 7 px high. --}}
@props(['percent', 'label' => 'Progression'])

<div
    role="progressbar"
    aria-label="{{ $label }}"
    aria-valuenow="{{ $percent }}"
    aria-valuemin="0"
    aria-valuemax="100"
    {{ $attributes->merge(['class' => 'h-[7px] overflow-hidden rounded-[3px] bg-line']) }}
>
    <div class="h-full rounded-[3px] bg-primary" style="width: {{ $percent }}%"></div>
</div>
