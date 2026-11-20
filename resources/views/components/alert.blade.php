{{-- Inline message: info, success, warning or error (functional colours). --}}
@props(['type' => 'info', 'title' => null])

@php
    $types = [
        'info' => ['box' => 'bg-sky text-sky-ink', 'icon' => 'info'],
        'success' => ['box' => 'bg-soft text-success', 'icon' => 'check-circle'],
        'warning' => ['box' => 'bg-warning-soft text-warning', 'icon' => 'alert'],
        'error' => ['box' => 'bg-error-soft text-error', 'icon' => 'alert'],
    ][$type];
@endphp

<div
    role="{{ $type === 'error' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex gap-3 rounded-tile px-4 py-3 text-sm '.$types['box']]) }}
>
    <x-icon :name="$types['icon']" class="mt-0.5" />
    <div class="flex flex-col gap-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div class="text-ink">{{ $slot }}</div>
    </div>
</div>
