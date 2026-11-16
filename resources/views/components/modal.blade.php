{{--
    Modal dialog (overlay radius 20 px). Opened with an Alpine event:
        <x-button type="button" x-on:click="$dispatch('open-modal', 'delete-course')">…</x-button>
    and from Livewire with $this->dispatch('open-modal', 'delete-course').
    Closes on Escape, a click on the backdrop, the close button or 'close-modal'.
    The focus stays inside (x-trap, bundled with Livewire) and goes back to the opener.
--}}
@props(['name', 'title', 'show' => false, 'width' => 'md'])

@php
    $widths = ['sm' => 'sm:max-w-sm', 'md' => 'sm:max-w-lg', 'lg' => 'sm:max-w-2xl'];
@endphp

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="if ($event.detail === @js($name)) show = true"
    x-on:close-modal.window="if ($event.detail === @js($name)) show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
>
    <div x-show="show" x-transition.opacity class="fixed inset-0 bg-dark/50" x-on:click="show = false" aria-hidden="true"></div>

    <div
        x-show="show"
        x-trap.inert.noscroll="show"
        x-transition
        role="dialog"
        aria-modal="true"
        aria-labelledby="{{ $name }}-title"
        {{ $attributes->merge(['class' => 'relative flex max-h-[calc(100dvh-2rem)] w-full flex-col overflow-y-auto rounded-overlay bg-white p-5 shadow-xl shadow-ink/10 sm:p-6 '.$widths[$width]]) }}
    >
        <div class="flex items-start justify-between gap-4">
            <h2 id="{{ $name }}-title" class="text-title-sm text-ink">{{ $title }}</h2>
            <button
                type="button"
                x-on:click="show = false"
                class="-mt-1 -mr-2 flex size-8 shrink-0 items-center justify-center rounded-[7px] hover:bg-canvas focus-visible:outline-2 focus-visible:outline-primary"
            >
                <x-icon name="close" />
                <span class="sr-only">Fermer</span>
            </button>
        </div>

        <div class="mt-3 text-sm text-muted">{{ $slot }}</div>

        @isset($footer)
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">{{ $footer }}</div>
        @endisset
    </div>
</div>
