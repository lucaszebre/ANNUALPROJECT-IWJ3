{{-- Catalogue (Figma "02 · Catalogue" and "08 · Catalogue connecté"). --}}
<x-layouts.app title="Catalogue" active="catalog">
    <div class="page-x pt-9 pb-16">
        <x-badge>LE CATALOGUE</x-badge>
        <h1 class="mt-6 text-[clamp(2rem,5vw,2.75rem)] leading-[1.22] tracking-[-0.03em]">Trouvez votre prochain déclic.</h1>
        <p class="mt-4 text-body-lg text-muted">Un premier langage ou une nouvelle compétence : choisissez votre point de départ.</p>

        <form method="GET" action="{{ route('catalog') }}" class="mt-12 flex flex-col gap-3 sm:flex-row sm:items-end sm:gap-6">
            <x-input name="q" label="Que souhaitez-vous apprendre ?" type="search" :value="$search"
                placeholder="Rechercher un cours, un langage ou un enseignant…" class="flex-1" />
            @if ($activeCategory)<input type="hidden" name="categorie" value="{{ $activeCategory }}">@endif
            @if ($activeLevel)<input type="hidden" name="niveau" value="{{ $activeLevel->value }}">@endif
            <x-button class="sm:mb-px sm:w-[216px]">Rechercher</x-button>
        </form>

        @php
            $filter = fn (array $params) => route('catalog', array_filter(['q' => $search ?: null] + $params));
            $noFilter = ! $activeCategory && ! $activeLevel;
        @endphp
        <nav aria-label="Filtrer les cours" class="mt-8 flex flex-wrap gap-[18px]">
            <x-button :href="$filter([])" :variant="$noFilter ? 'primary' : 'secondary'" :aria-current="$noFilter ? 'true' : null">Tous les cours</x-button>
            @foreach ($categories as $category)
                @php($active = $activeCategory === $category->slug)
                <x-button :href="$filter(['categorie' => $category->slug])" :variant="$active ? 'primary' : 'secondary'" :aria-current="$active ? 'true' : null">{{ $category->name }}</x-button>
            @endforeach
            @foreach ($levels as $level)
                @php($active = $activeLevel === $level)
                <x-button :href="$filter(['niveau' => $level->value])" :variant="$active ? 'primary' : 'secondary'" :aria-current="$active ? 'true' : null">{{ $level->label() }}</x-button>
            @endforeach
        </nav>

        <p class="mt-9 text-sm text-muted">
            {{ trans_choice('{0} Aucun cours ne correspond|{1} 1 cours pour faire le prochain pas|[2,*] :count cours pour faire le prochain pas', $courses->total()) }}
        </p>

        @if ($courses->isEmpty())
            <x-empty-state title="Aucun cours trouvé" class="mt-6">
                Essayez un autre mot ou retirez les filtres.
                <x-slot:action>
                    <x-button variant="secondary" :href="route('catalog')">Voir tous les cours</x-button>
                </x-slot:action>
            </x-empty-state>
        @else
            <div class="mt-6 grid gap-x-6 gap-y-[14px] sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <x-course-card :course="$course" />
                @endforeach
            </div>

            @if ($courses->hasPages())
                <nav aria-label="Pages du catalogue" class="mt-8 flex items-center justify-between gap-4">
                    <x-button variant="secondary" :href="$courses->previousPageUrl()" :disabled="$courses->onFirstPage()">← Précédent</x-button>
                    <span class="text-label text-muted">Page {{ $courses->currentPage() }} sur {{ $courses->lastPage() }}</span>
                    <x-button variant="secondary" :href="$courses->nextPageUrl()" :disabled="! $courses->hasMorePages()">Suivant →</x-button>
                </nav>
            @else
                <p class="mt-[30px] text-label text-muted">Vous avez vu tous les cours publiés.</p>
            @endif
        @endif
    </div>
</x-layouts.app>
