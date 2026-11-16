{{--
    Empty or error state of a list or a page: icon, title, text, optional action.
    type="error" uses the error colours.
--}}
@props(['title', 'icon' => 'inbox', 'type' => 'empty'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-4 rounded-panel border border-dashed border-line bg-white px-6 py-12 text-center']) }}>
    <span @class([
        'flex size-12 items-center justify-center rounded-full',
        'bg-soft text-primary' => $type !== 'error',
        'bg-error-soft text-error' => $type === 'error',
    ])>
        <x-icon :name="$type === 'error' ? 'alert' : $icon" />
    </span>
    <div class="flex max-w-md flex-col gap-2">
        <h3 class="text-body-lg text-ink">{{ $title }}</h3>
        @if ($slot->isNotEmpty())
            <p class="text-sm text-muted">{{ $slot }}</p>
        @endif
    </div>
    @isset($action)
        <div class="flex flex-wrap justify-center gap-3">{{ $action }}</div>
    @endisset
</div>
