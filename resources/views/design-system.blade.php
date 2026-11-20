{{-- Local only: every token and component of the design system, to compare with Figma. --}}
<x-layouts.app title="Design system">
    @php
        $semantic = ['primary' => '#196747', 'dark' => '#102B24', 'lime' => '#D9F59A', 'soft' => '#E9F3ED', 'canvas' => '#F6F8F6', 'white' => '#FFFFFF', 'ink' => '#162E27', 'muted' => '#5C7066'];
        $functional = ['line' => '#DFE7E1', 'primary-hover' => '#125338', 'success' => '#19724C', 'error' => '#B73B39', 'error-soft' => '#FDEDEC', 'warning' => '#895F17', 'warning-soft' => '#FFF5DD', 'purple' => '#ECE8FB', 'purple-ink' => '#6850A5', 'sky' => '#E6F1F8', 'sky-ink' => '#32657C', 'code' => '#162C27', 'code-muted' => '#A7BEB2'];
    @endphp

    <div class="page-x flex flex-col gap-14 py-11">
        <div>
            <h1 class="text-display tracking-[-0.03em]">{{ config('app.brand') }}<br>Design system</h1>
            <p class="mt-4 text-body-lg text-muted">Fondations et composants reliés aux mockups · Desktop 1440 px · Étudiant</p>
        </div>

        <section class="flex flex-col gap-6">
            <h2 class="text-title-sm">01 Couleurs sémantiques</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-8">
                @foreach ($semantic as $name => $hex)
                    <div>
                        <div class="h-[124px] rounded-card {{ $name === 'white' ? 'border border-line' : '' }}" style="background-color: {{ $hex }}"></div>
                        <p class="mt-3 text-label font-semibold">{{ str($name)->headline() }}</p>
                        <p class="text-2xs text-muted">{{ $hex }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="flex flex-col gap-6">
            <h2 class="text-title-sm">02 Typographie</h2>
            <div class="grid gap-8 lg:grid-cols-2">
                <div>
                    <p class="font-display text-title-lg font-bold tracking-[-0.03em]">Aa Manrope</p>
                    <p class="mt-2 text-[15px] text-muted">Titres 44 / 36 / 28 / 22</p>
                </div>
                <div>
                    <p class="text-2xl">Inter · Lire, naviguer, comprendre.</p>
                    <p class="mt-2 text-[15px] text-muted">Corps 16 px · Labels 14 px · Aides 12 px</p>
                    <p class="mt-1 font-mono text-sm text-muted">JetBrains Mono pour les exemples de programmation.</p>
                </div>
            </div>
        </section>

        <section class="flex flex-col gap-6">
            <h2 class="text-title-sm">03 Boutons</h2>
            <div class="grid max-w-[800px] grid-cols-1 gap-x-6 gap-y-4 sm:grid-cols-3">
                <div class="flex flex-col gap-1"><x-button>Continuer</x-button><span class="text-2xs text-muted">Principal</span></div>
                <div class="flex flex-col gap-1"><x-button variant="secondary">Continuer</x-button><span class="text-2xs text-muted">Secondaire</span></div>
                <div class="flex flex-col gap-1"><x-button variant="ghost">Continuer</x-button><span class="text-2xs text-muted">Discret</span></div>
                <div class="flex flex-col gap-1"><x-button disabled>Continuer</x-button><span class="text-2xs text-muted">Désactivé</span></div>
                <div class="flex flex-col gap-1"><x-button loading>Continuer</x-button><span class="text-2xs text-muted">Chargement</span></div>
                <div class="flex flex-col gap-1"><x-button href="#">Continuer</x-button><span class="text-2xs text-muted">Lien (survol et focus au clavier)</span></div>
            </div>
        </section>

        <section class="flex flex-col gap-6">
            <h2 class="text-title-sm">04 Formulaires et recherche</h2>
            <div class="grid max-w-[1160px] gap-6 lg:grid-cols-2">
                <x-input name="ds_name" label="Nom complet" placeholder="Camille Dupont" />
                <x-input name="ds_password" type="password" label="Mot de passe" placeholder="12 caractères minimum" hint="12 caractères minimum." />
                <x-checkbox name="ds_terms" label="J’accepte les conditions d’utilisation" checked />
                <x-checkbox name="ds_news" label="Recevoir les nouveautés" />
            </div>
            <div class="flex flex-wrap items-center gap-6">
                <x-search-bar size="md" />
                <x-search-bar size="sm" placeholder="Rechercher une notion…" />
            </div>
            <x-search-bar size="lg" value="return" class="max-w-[1072px]" />
        </section>

        <section class="flex flex-col gap-6">
            <h2 class="text-title-sm">05 Champs complémentaires</h2>
            <div class="grid max-w-[1160px] gap-6 lg:grid-cols-2">
                <x-select name="ds_level" label="Niveau" placeholder="Choisir un niveau" :options="['beginner' => 'Débutant', 'intermediate' => 'Intermédiaire']" />
                <x-textarea name="ds_question" label="Votre question" placeholder="Décrivez le passage qui bloque…" hint="Visible par l’enseignant du cours." />
            </div>
        </section>

        <section class="flex flex-col gap-6">
            <h2 class="text-title-sm">06 Messages et états</h2>
            <div class="grid max-w-[1160px] gap-4 lg:grid-cols-2">
                <x-alert type="info" title="Information">La vidéo est en cours de traitement.</x-alert>
                <x-alert type="success" title="Réussi">Tous les tests passent.</x-alert>
                <x-alert type="warning" title="Attention">Il vous reste 2 tentatives.</x-alert>
                <x-alert type="error" title="Erreur">La soumission a échoué.</x-alert>
            </div>
            <div class="grid max-w-[1160px] gap-6 lg:grid-cols-2">
                <x-card title="Carte">
                    <p class="text-sm text-muted">Surface blanche, rayon 12 px, bordure Line.</p>
                </x-card>
                <x-empty-state title="Aucun cours pour l’instant">
                    Les cours publiés apparaîtront ici.
                </x-empty-state>
            </div>
            {{-- x-data: Alpine only runs x-on inside a component. --}}
            <div x-data class="flex flex-wrap gap-4">
                <x-button type="button" variant="secondary" x-on:click="$dispatch('open-modal', 'ds-modal')">Ouvrir la modale</x-button>
                <x-button type="button" variant="secondary" x-on:click="$dispatch('toast', { type: 'success', message: 'Cours enregistré.' })">Afficher un toast</x-button>
            </div>
            <x-modal name="ds-modal" title="Supprimer la leçon ?">
                La leçon et ses commentaires seront supprimés.
                <x-slot:footer>
                    <x-button type="button" variant="ghost" x-on:click="$dispatch('close-modal', 'ds-modal')">Annuler</x-button>
                    <x-button type="button">Supprimer</x-button>
                </x-slot:footer>
            </x-modal>
        </section>

        <section class="flex flex-col gap-6">
            <h2 class="text-title-sm">07 Couleurs fonctionnelles</h2>
            <div class="grid grid-cols-2 gap-6 sm:grid-cols-4 lg:grid-cols-7">
                @foreach ($functional as $name => $hex)
                    <div>
                        <div class="h-[88px] rounded-tile border border-line" style="background-color: {{ $hex }}"></div>
                        <p class="mt-3 text-label font-semibold">{{ str($name)->headline()->replace(' ', '') }}</p>
                        <p class="text-2xs text-muted">{{ $hex }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-layouts.app>
