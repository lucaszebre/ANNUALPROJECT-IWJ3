{{--
    Text field of the design system (Figma "Formulaires / Champ"), 48 px high.
    type="password" adds the "Afficher" / "Masquer" toggle.
--}}
@props([
    'name',
    'label' => null,
    'type' => 'text',
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

    <div class="relative" @if ($type === 'password') x-data="{ shown: false }" @endif>
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            @if ($type === 'password') x-bind:type="shown ? 'text' : 'password'" @endif
            value="{{ $type === 'password' ? '' : old($name, $attributes->get('value')) }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->except(['class', 'id', 'value'])->merge([
                'class' => 'h-field w-full rounded-field border bg-white px-[13px] text-sm text-ink placeholder:text-muted '
                    .'focus:border-primary focus:outline-none focus:ring-3 focus:ring-primary/15 '
                    .($type === 'password' ? 'pr-24 ' : '')
                    .($error ? 'border-error' : 'border-line'),
            ]) }}
        >

        @if ($type === 'password')
            <button
                type="button"
                class="absolute inset-y-0 right-3 my-auto h-8 rounded-field px-1 text-2xs font-semibold text-primary focus-visible:outline-2 focus-visible:outline-primary"
                x-on:click="shown = ! shown"
                x-text="shown ? 'Masquer' : 'Afficher'"
                x-bind:aria-pressed="shown"
                aria-controls="{{ $id }}"
            >Afficher</button>
        @endif
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-2xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-2xs font-semibold text-error">{{ $error }}</p>
    @endif
</div>
