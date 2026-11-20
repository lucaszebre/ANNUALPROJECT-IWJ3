{{--
    Site header (Figma "Navigation / Header desktop"), 88 px high.
    Visitor: links, search, "Connexion" and "Créer un compte".
    Signed-in student: links with "Mes apprentissages", search, organisation, account menu.
    Teachers also get "Mes cours" and admins "Administration" (gates in AppServiceProvider).
    $active: home, catalog, learning, teaching or admin.
    The full header needs 1280 px (xl); below that, the "Menu" button opens the links.
--}}
@props(['active' => null])

@php
    $user = auth()->user();
    $membership = $user?->memberships()->with('organization')->first();
    $link = fn (string $name) => Route::has($name) ? route($name) : '#';
@endphp

<header x-data="{ menuOpen: false }" x-on:keydown.escape.window="menuOpen = false" class="border-b border-line bg-white">
    <div class="page-x flex h-header items-center gap-6">
        <x-brand class="shrink-0" />

        <nav aria-label="Navigation principale" class="ml-6 hidden items-center gap-11 xl:flex">
            <x-nav-link :href="url('/')" :active="$active === 'home'">Accueil</x-nav-link>
            <x-nav-link :href="$link('catalog')" :active="$active === 'catalog'">Catalogue</x-nav-link>
            @auth
                <x-nav-link :href="$link('learning')" :active="$active === 'learning'">Mes apprentissages</x-nav-link>
            @endauth
            @can('teach')
                <x-nav-link :href="$link('teaching')" :active="$active === 'teaching'">Mes cours</x-nav-link>
            @endcan
            @can('administer')
                <x-nav-link :href="$link('admin')" :active="$active === 'admin'">Administration</x-nav-link>
            @endcan
        </nav>

        <div class="ml-auto hidden items-center gap-4 xl:flex">
            @auth
                <x-search-bar :action="$link('search')" size="sm" placeholder="Rechercher une notion…" class="mr-2" />
                @if ($membership)
                    <span class="max-w-[136px] truncate text-2xs text-muted">{{ $membership->organization->name }}</span>
                @endif
                <x-account-menu :user="$user" :organization="$membership?->organization->name" :role="$membership?->role->label()" />
            @else
                <x-search-bar :action="$link('catalog')" size="md" class="mr-10" />
                <x-button variant="ghost" :href="$link('login')" class="w-36">Connexion</x-button>
                <x-button :href="$link('register')" class="w-[194px]">Créer un compte</x-button>
            @endauth
        </div>

        <button
            type="button"
            class="ml-auto flex h-11 items-center rounded-button border border-line px-4 text-label font-semibold text-ink xl:hidden"
            x-on:click="menuOpen = ! menuOpen"
            x-bind:aria-expanded="menuOpen"
            aria-controls="mobile-menu"
            x-text="menuOpen ? 'Fermer' : 'Menu'"
        >Menu</button>
    </div>

    <div id="mobile-menu" x-cloak x-show="menuOpen" class="page-x flex flex-col gap-4 border-t border-line py-4 xl:hidden">
        <x-search-bar :action="$link(auth()->check() ? 'search' : 'catalog')" size="md" />
        <nav aria-label="Navigation principale (mobile)" class="flex flex-col gap-3">
            <x-nav-link :href="url('/')" :active="$active === 'home'">Accueil</x-nav-link>
            <x-nav-link :href="$link('catalog')" :active="$active === 'catalog'">Catalogue</x-nav-link>
            @auth
                <x-nav-link :href="$link('learning')" :active="$active === 'learning'">Mes apprentissages</x-nav-link>
                @can('teach')
                    <x-nav-link :href="$link('teaching')" :active="$active === 'teaching'">Mes cours</x-nav-link>
                @endcan
                @can('administer')
                    <x-nav-link :href="$link('admin')" :active="$active === 'admin'">Administration</x-nav-link>
                @endcan
                <x-nav-link :href="$link('profile')">Mon profil</x-nav-link>
            @endauth
        </nav>
        @guest
            <div class="grid grid-cols-2 gap-3">
                <x-button variant="ghost" :href="$link('login')">Connexion</x-button>
                <x-button :href="$link('register')">Créer un compte</x-button>
            </div>
        @endguest
    </div>
</header>
