{{-- Figma "04 · Inscription". Rules live in App\Actions\Fortify\CreateNewUser. --}}
<x-auth.page
    title="Créer un compte"
    eyebrow="Votre premier pas"
    heading="Apprendre commence ici."
    intro="Créez votre compte et explorez les cours."
>
    <x-slot:panel-title>Chaque ligne compte.<br>Surtout la première.</x-slot:panel-title>
    <x-slot:panel-text>Pas besoin de tout savoir pour commencer.<br>Choisissez un cours et avancez pas à pas.</x-slot:panel-text>

    <form method="POST" action="{{ route('register') }}" class="flex flex-col gap-[22px]" novalidate>
        @csrf

        <x-input
            name="name"
            label="Nom complet"
            placeholder="Camille Dupont"
            required
            autofocus
            autocomplete="name"
        />

        <x-input
            name="email"
            type="email"
            label="Adresse email"
            placeholder="camille@exemple.fr"
            required
            autocomplete="email"
        />

        <x-input
            name="password"
            type="password"
            label="Mot de passe"
            placeholder="12 caractères minimum"
            hint="Au moins 12 caractères, avec une majuscule, une minuscule et un chiffre."
            required
            autocomplete="new-password"
        />

        <x-input
            name="password_confirmation"
            type="password"
            label="Confirmer le mot de passe"
            placeholder="Saisissez à nouveau votre mot de passe"
            required
            autocomplete="new-password"
        />

        <div class="mt-2 flex flex-col gap-2">
            <x-checkbox
                name="terms"
                value="1"
                label="J’accepte les conditions d’utilisation et la confidentialité."
                :aria-invalid="$errors->has('terms') ? 'true' : null"
                :aria-describedby="$errors->has('terms') ? 'terms-error' : null"
            />
            @error('terms')
                <p id="terms-error" class="text-2xs font-semibold text-error">{{ $message }}</p>
            @enderror
        </div>

        <x-button class="mt-2 w-full">Créer mon compte <span aria-hidden="true">→</span></x-button>
    </form>

    <p class="mt-[30px] flex flex-wrap gap-x-4 text-sm text-muted">
        Déjà un compte ?
        <a href="{{ route('login') }}" class="font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Se connecter</a>
    </p>
</x-auth.page>
