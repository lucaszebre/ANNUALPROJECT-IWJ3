{{--
    "Mon compte" button and its menu (Figma "Navigation / Déclencheur du compte"
    and "21 · Menu du compte étudiant"): closes on an outside click or Escape,
    and gives the focus back to the button.
--}}
@props(['user', 'organization' => null, 'role' => null])

<div
    x-data="{ open: false, close(focus = true) { this.open = false; if (focus) this.$refs.trigger.focus() } }"
    x-on:keydown.escape.window="if (open) close()"
    {{ $attributes->merge(['class' => 'relative']) }}
>
    <button
        x-ref="trigger"
        type="button"
        x-on:click="open = ! open"
        x-bind:aria-expanded="open"
        aria-haspopup="true"
        aria-controls="account-menu"
        class="flex h-11 w-[192px] items-center gap-3 rounded-button border border-line bg-white pr-2.5 pl-[13px] text-label font-semibold text-ink hover:bg-canvas focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary"
    >
        <x-icon name="user" />
        <span class="flex-1 text-left">Mon compte</span>
        <x-icon name="chevron-down" class="transition-transform" x-bind:class="open && 'rotate-180'" />
    </button>

    <div
        id="account-menu"
        x-cloak
        x-show="open"
        x-transition.opacity.duration.100ms
        x-on:click.outside="close(false)"
        class="absolute top-[calc(100%+8px)] right-0 z-20 w-[304px] rounded-overlay border border-line bg-white p-[19px] pt-[17px] shadow-lg shadow-ink/5"
    >
        <div class="flex items-start justify-between gap-3">
            <p class="text-body-lg font-semibold text-ink">{{ $user->name }}</p>
            <button
                type="button"
                x-on:click="close()"
                class="-mt-1.5 -mr-[7px] flex size-8 items-center justify-center rounded-[7px] hover:bg-canvas focus-visible:outline-2 focus-visible:outline-primary"
            >
                <x-icon name="close" />
                <span class="sr-only">Fermer le menu</span>
            </button>
        </div>
        <p class="mt-1 text-2xs text-muted">{{ $user->email }}</p>

        @if ($organization)
            <p class="mt-3 rounded-full bg-canvas px-3 py-[5px] text-2xs font-semibold text-muted">
                {{ $organization }}@if ($role) · {{ $role }}@endif
            </p>
        @endif

        <hr class="my-4 border-line">

        <div class="-mx-1 flex flex-col gap-4">
            <x-button variant="ghost" :href="Route::has('profile') ? route('profile') : '#'" class="w-full">Mon profil</x-button>

            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-button variant="ghost" class="w-full">Se déconnecter</x-button>
                </form>
            @endif
        </div>
    </div>
</div>
