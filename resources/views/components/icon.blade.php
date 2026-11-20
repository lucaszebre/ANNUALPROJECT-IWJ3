{{-- Inline SVG from resources/icons (exported from the Figma design system). --}}
@props(['name'])

<span {{ $attributes->merge(['class' => 'inline-flex shrink-0', 'aria-hidden' => 'true']) }}>
    {!! file_get_contents(resource_path("icons/{$name}.svg")) !!}
</span>
