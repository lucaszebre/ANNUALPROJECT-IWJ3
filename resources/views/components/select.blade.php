{{--
    Select field, styled like <x-input> (Figma "Formulaires / Champ"), 48 px high.
    $options: [value => label]. The slot can be used instead for custom <option>s.
--}}
@props([
    'name',
    'label' => null,
    'options' => [],
    'placeholder' => null,
    'hint' => null,
])

@php
    $id = $attributes->get('id', $name);
    $error = $errors->first($name);
    $selected = (string) old($name, $attributes->get('value'));
    $describedBy = collect([$hint ? "{$id}-hint" : null, $error ? "{$id}-error" : null])->filter()->implode(' ');
@endphp

<div {{ $attributes->only('class')->merge(['class' => 'flex flex-col gap-2']) }}>
    @if ($label)
        <label for="{{ $id }}" class="text-sm font-semibold text-ink">{{ $label }}</label>
    @endif

    <div class="relative">
        <select
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($error) aria-invalid="true" @endif
            {{ $attributes->except(['class', 'id', 'value'])->merge([
                'class' => 'h-field w-full cursor-pointer appearance-none rounded-field border bg-white pr-10 pl-[13px] text-sm text-ink '
                    .'focus:border-primary focus:outline-none focus:ring-3 focus:ring-primary/15 '
                    .($error ? 'border-error' : 'border-line'),
            ]) }}
        >
            @if ($placeholder)
                <option value="" disabled @selected($selected === '')>{{ $placeholder }}</option>
            @endif
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected($selected === (string) $value)>{{ $text }}</option>
            @endforeach
            {{ $slot }}
        </select>
        <x-icon name="chevron-down" class="pointer-events-none absolute inset-y-0 right-3 my-auto h-fit" />
    </div>

    @if ($hint)
        <p id="{{ $id }}-hint" class="text-2xs text-muted">{{ $hint }}</p>
    @endif

    @if ($error)
        <p id="{{ $id }}-error" class="text-2xs font-semibold text-error">{{ $error }}</p>
    @endif
</div>
