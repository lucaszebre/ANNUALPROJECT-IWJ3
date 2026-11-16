{{-- Checkbox of the design system (Figma "Formulaires / Checkbox"), 20 px. --}}
@props(['name', 'label', 'checked' => false])

@php($id = $attributes->get('id', $name))

<label for="{{ $id }}" {{ $attributes->only('class')->merge(['class' => 'inline-flex cursor-pointer items-center gap-3 text-sm text-ink']) }}>
    <span class="relative inline-flex size-5 shrink-0">
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            @checked(old($name, $checked))
            {{ $attributes->except(['class', 'id'])->merge([
                'class' => 'peer size-5 cursor-pointer appearance-none rounded-checkbox border border-line bg-white checked:border-primary checked:bg-primary '
                    .'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
            ]) }}
        >
        <x-icon name="check" class="pointer-events-none absolute top-px left-px hidden peer-checked:inline-flex" />
    </span>
    {{ $label }}
</label>
