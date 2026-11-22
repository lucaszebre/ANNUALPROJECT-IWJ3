{{--
    Page opened from the reset email. No mockup of its own: it reuses the layout of
    "06 · Mot de passe oublié". Fortify then redirects to the login page with a status.
--}}
<x-auth.page
    title="Nouveau mot de passe"
    eyebrow="Retrouver votre compte"
    heading="Choisissez un nouveau mot de passe."
    intro="Il remplacera l’ancien sur tous vos appareils."
>
    <x-slot:panel-title>Reprenez le fil.<br>En toute simplicité.</x-slot:panel-title>
    <x-slot:panel-text>Votre progression reste enregistrée.<br>Elle vous attendra à votre retour.</x-slot:panel-text>

    <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-[22px]" novalidate>
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-input
            name="email"
            type="email"
            label="Adresse email"
            :value="$request->email"
            required
            autocomplete="email"
        />

        <x-input
            name="password"
            type="password"
            label="Nouveau mot de passe"
            placeholder="12 caractères minimum"
            hint="Au moins 12 caractères, avec une majuscule, une minuscule et un chiffre."
            required
            autofocus
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

        <x-button class="mt-4 w-full">Réinitialiser le mot de passe</x-button>
    </form>
</x-auth.page>
