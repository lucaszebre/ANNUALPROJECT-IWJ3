<x-layouts.app title="Mon profil">
    <div class="page-x py-10">
        <p class="text-2xs font-semibold text-primary uppercase">Mon compte</p>
        <h1 class="mt-5 text-title leading-[1.22] tracking-[-0.03em] sm:text-[40px]">Mon profil</h1>
        <p class="mt-5 text-body-lg text-muted">Vos informations, votre sécurité et votre organisation.</p>

        <div class="mt-14 grid items-start gap-8 lg:grid-cols-2">
            <div class="flex flex-col gap-8">
                <x-card padding="none" class="p-6" aria-labelledby="profile-information-title">
                    <h2 id="profile-information-title" class="text-[25px] leading-[1.55] text-ink">Informations personnelles</h2>

                    <form method="POST" action="{{ route('user-profile-information.update') }}" class="mt-5 flex flex-col gap-[34px]" novalidate>
                        @csrf
                        @method('PUT')

                        <x-input
                            name="name"
                            label="Nom complet"
                            :value="$user->name"
                            required
                            autocomplete="name"
                        />

                        <x-input
                            name="email"
                            type="email"
                            label="Adresse email"
                            :value="$user->email"
                            required
                            autocomplete="email"
                        />

                        <x-input
                            name="email_current_password"
                            type="password"
                            label="Mot de passe actuel"
                            hint="Requis uniquement pour changer d'adresse email."
                            autocomplete="current-password"
                        />

                        <x-button class="mt-4 w-full">Enregistrer les modifications</x-button>
                    </form>
                </x-card>

                <section class="rounded-panel bg-soft p-[26px]" aria-labelledby="organization-title">
                    <h2 id="organization-title" class="text-[25px] leading-[1.55] text-ink">Mon organisation</h2>

                    @if ($membership)
                        <p class="mt-9 text-body-lg leading-[1.55] text-muted">
                            Vous suivez les cours de <span class="font-semibold text-ink">{{ $membership->organization->name }}</span>.
                        </p>
                        <p class="mt-5 text-sm text-muted">
                            {{ $membership->role->label() }}@if ($membership->created_at) · membre depuis {{ $membership->created_at->translatedFormat('F Y') }}@endif
                        </p>

                        @if ($canSwitchOrganization)
                            <x-button variant="secondary" :href="route('organizations.select')" class="mt-6">Changer d’organisation</x-button>
                        @endif
                    @else
                        <p class="mt-9 text-body-lg leading-[1.55] text-muted">Votre compte n’est rattaché à aucune organisation pour le moment.</p>
                    @endif
                </section>
            </div>

            <div class="flex flex-col items-start gap-[66px]">
                <x-card padding="none" class="w-full p-6" aria-labelledby="security-title">
                    <h2 id="security-title" class="text-[25px] leading-[1.55] text-ink">Sécurité</h2>

                    <form method="POST" action="{{ route('user-password.update') }}" class="mt-5 flex flex-col gap-[34px]" novalidate>
                        @csrf
                        @method('PUT')

                        <x-input
                            name="current_password"
                            type="password"
                            label="Mot de passe actuel"
                            placeholder="••••••••••••"
                            required
                            autocomplete="current-password"
                        />

                        <x-input
                            name="password"
                            type="password"
                            label="Nouveau mot de passe"
                            placeholder="12 caractères minimum"
                            hint="Au moins 12 caractères, avec une majuscule, une minuscule et un chiffre."
                            required
                            autocomplete="new-password"
                        />

                        <x-input
                            name="password_confirmation"
                            type="password"
                            label="Confirmer le nouveau mot de passe"
                            placeholder="••••••••••••"
                            required
                            autocomplete="new-password"
                        />

                        <x-button class="mt-[30px] w-full">Mettre à jour le mot de passe</x-button>
                    </form>
                </x-card>

                <form method="POST" action="{{ route('logout') }}" class="w-full sm:w-[290px]">
                    @csrf
                    <x-button variant="ghost" class="w-full">Se déconnecter</x-button>
                </form>
            </div>
        </div>
    </div>

    @php
        $saved = match (session('status')) {
            'profile-information-updated' => ['Votre profil est à jour.', 'Vos informations ont bien été enregistrées.'],
            'password-updated' => ['Votre mot de passe est à jour.', 'Utilisez-le dès votre prochaine connexion.'],
            default => null,
        };
    @endphp

    @if ($saved)
        <x-modal name="profile-saved" :title="$saved[0]" :show="true" width="md">
            <p class="text-base">{{ $saved[1] }}</p>

            <x-slot:footer>
                <x-button type="button" class="w-full" x-on:click="$dispatch('close-modal', 'profile-saved')">Continuer</x-button>
            </x-slot:footer>
        </x-modal>
    @endif
</x-layouts.app>
