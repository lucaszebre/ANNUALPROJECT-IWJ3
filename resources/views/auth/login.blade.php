{{-- Figma "05 · Connexion". Fortify sends back a generic error on "email" (auth.failed). --}}
<x-auth.page
    title="Connexion"
    eyebrow="Content de vous retrouver"
    heading="On reprend ?"
    intro="Connectez-vous pour continuer à votre rythme."
>
    <x-slot:panel-title>Votre prochain déclic<br>n’est plus très loin.</x-slot:panel-title>
    <x-slot:panel-text>Retrouvez vos cours, vos exercices<br>et le fil de votre apprentissage.</x-slot:panel-text>

    <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-[34px]" novalidate>
        @csrf

        <x-input
            name="email"
            type="email"
            label="Adresse email"
            placeholder="camille@exemple.fr"
            required
            autofocus
            autocomplete="username"
        />

        <x-input
            name="password"
            type="password"
            label="Mot de passe"
            placeholder="••••••••••••"
            required
            autocomplete="current-password"
        />

        <div class="-mt-1 flex flex-wrap items-center justify-between gap-4">
            <x-checkbox name="remember" label="Se souvenir de moi" />
            <a href="{{ route('password.request') }}" class="rounded-field text-sm font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Mot de passe oublié ?</a>
        </div>

        <x-button class="w-full">Se connecter <span aria-hidden="true">→</span></x-button>
    </form>

    <p class="mt-11 flex flex-wrap gap-x-4 text-sm text-muted">
        Pas encore de compte ?
        <a href="{{ route('register') }}" class="font-semibold text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary">Créer un compte</a>
    </p>
</x-auth.page>
