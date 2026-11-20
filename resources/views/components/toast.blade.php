{{--
    Toast stack, bottom-right (full width on phones), placed once in the layout.
    Shows session('toast') after a redirect:
        return back()->with('toast', ['type' => 'success', 'message' => 'Cours enregistré.']);
    (a plain string works too), and listens to the 'toast' browser event:
        $this->dispatch('toast', type: 'success', message: 'Cours enregistré.');   // Livewire
        $dispatch('toast', { type: 'error', message: '…' })                      // Alpine
    Each toast closes after 5 s or with its button.
--}}
@php
    $flash = session('toast');
    $initial = $flash ? [is_array($flash) ? $flash + ['type' => 'success'] : ['type' => 'success', 'message' => $flash]] : [];
@endphp

<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, type: toast.type ?? 'info', message: toast.message });
            setTimeout(() => this.remove(id), 5000);
        },
        remove(id) { this.toasts = this.toasts.filter(t => t.id !== id) },
    }"
    x-init="@js($initial).forEach(t => add(t))"
    x-on:toast.window="add(Array.isArray($event.detail) ? $event.detail[0] : $event.detail)"
    aria-live="polite"
    class="pointer-events-none fixed inset-x-4 bottom-4 z-50 flex flex-col items-stretch gap-3 sm:left-auto sm:w-96"
>
    <template x-for="toast in toasts" x-bind:key="toast.id">
        <div
            x-transition.opacity
            role="status"
            class="pointer-events-auto flex items-start gap-3 rounded-card border border-line bg-white p-4 text-sm text-ink shadow-lg shadow-ink/10"
            x-bind:class="{
                'border-l-4 border-l-success': toast.type === 'success',
                'border-l-4 border-l-error': toast.type === 'error',
                'border-l-4 border-l-warning': toast.type === 'warning',
                'border-l-4 border-l-sky-ink': toast.type === 'info',
            }"
        >
            <p class="flex-1" x-text="toast.message"></p>
            <button
                type="button"
                x-on:click="remove(toast.id)"
                class="-m-1 flex size-7 shrink-0 items-center justify-center rounded-[7px] hover:bg-canvas focus-visible:outline-2 focus-visible:outline-primary"
            >
                <x-icon name="close" />
                <span class="sr-only">Fermer la notification</span>
            </button>
        </div>
    </template>
</div>
