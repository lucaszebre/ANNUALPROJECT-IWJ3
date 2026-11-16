{{-- Multi-line text field, styled like <x-input> (Figma "Formulaires / Champ"). --}}
@props([
    'name',
    'label' => null,
    'rows' => 4,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $describedBy = collect([$hint ? "{$id}-hint" : null, $error ? "{$id}-error" : null])->filter()->implode(' ');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex flex-col gap-2']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-semibold text-ink">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($error) aria-invalid="true" @endif
        {{ $attributes->except(['class', 'id', 'value'])->merge([
            'class' => 'w-full resize-y rounded-field border bg-white px-[13px] py-3 text-sm text-ink placeholder:text-muted '
                .'focus:border-primary focus:outline-none focus:ring-3 focus:ring-primary/15 '
                .($error ? 'border-error' : 'border-line'),
        ]) }}
    >{{ old($name, $attributes->get('value', $slot->isEmpty() ? '' : $slot)) }}</textarea>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-2xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-2xs font-semibold text-error">{{ $error }}</p>
    @endif
</div>
