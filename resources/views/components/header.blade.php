{{--
    Site header (Figma "Navigation / Header desktop"), 64 px high, stuck to the top of the page.
    Visitor: links, search, "Connexion" and "Créer un compte".
    Signed-in student: links with "Mes apprentissages", search, organisation, account menu.
    Teachers also get "Mes cours" and admins "Administration" (gates in AppServiceProvider).
    $active: home, catalog, learning, teaching or admin.
    The links show from 1024 px (lg), the search and the organisation from 1280 px (xl);
    below 1024 px the menu button opens everything.
--}}
@props(['active' => null])

@php
    $user = auth()->user();
    $membership = $user?->memberships()->with('organization')->first();
    $link = fn (string $name) => Route::has($name) ? route($name) : '#';
@endphp

<header
    x-data="{ menuOpen: false }"
    x-on:keydown.escape.window="menuOpen = false"
    class="sticky top-0 z-40 border-b border-line bg-white/90 backdrop-blur-md"
>
    <div class="page-x flex h-header items-center gap-6">
        <x-brand class="shrink-0" />

        <nav aria-label="Navigation principale" class="hidden items-center gap-7 lg:flex">
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

        <div class="ml-auto hidden items-center gap-3 lg:flex">
            @auth
                <x-search-bar :action="$link('search')" size="sm" placeholder="Rechercher une notion…" class="hidden! xl:flex!" />
                @if ($membership)
                    <span class="hidden max-w-[136px] truncate text-2xs text-muted xl:inline">{{ $membership->organization->name }}</span>
                @endif
                <x-account-menu :user="$user" :organization="$membership?->organization->name" :role="$membership?->role->label()" />
            @else
                <x-search-bar :action="$link('catalog')" size="sm" class="mr-2 hidden! xl:flex!" />
                <x-button variant="ghost" size="sm" :href="$link('login')">Connexion</x-button>
                <x-button size="sm" :href="$link('register')">Créer un compte</x-button>
            @endauth
        </div>

        <button
            type="button"
            class="ml-auto flex size-10 items-center justify-center rounded-button border border-line text-ink hover:bg-canvas focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-primary lg:hidden"
            x-on:click="menuOpen = ! menuOpen"
            x-bind:aria-expanded="menuOpen"
            aria-controls="mobile-menu"
        >
            <x-icon name="menu" x-show="! menuOpen" />
            <x-icon name="close" x-cloak x-show="menuOpen" />
            <span class="sr-only" x-text="menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'">Ouvrir le menu</span>
        </button>
    </div>

    <div id="mobile-menu" x-cloak x-show="menuOpen" class="page-x flex flex-col gap-4 border-t border-line py-4 lg:hidden">
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
