{{--
    Figma "06 · Mot de passe oublié". Once the link is sent, Fortify redirects back
    with session('status') and the "33 · Lien de réinitialisation envoyé" dialog opens.
--}}
<x-auth.page
    title="Mot de passe oublié"
    eyebrow="Retrouver votre compte"
    heading="Un nouveau départ."
    intro="Indiquez votre email pour recevoir un lien de réinitialisation."
    :show-status="false"
>
    <x-slot:panel-title>Reprenez le fil.<br>En toute simplicité.</x-slot:panel-title>
    <x-slot:panel-text>Votre progression reste enregistrée.<br>Elle vous attendra à votre retour.</x-slot:panel-text>

    <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-12 xl:mt-[56px]" novalidate>
        @csrf

        <x-input
            name="email"
            type="email"
            label="Adresse email"
            placeholder="camille@exemple.fr"
            required
            autofocus
            autocomplete="email"
        />

        <x-button class="w-full">Envoyer le lien</x-button>
    </form>

    <p class="mt-11 text-[15px] text-muted">Si un compte correspond, un email sera envoyé.<br class="hidden sm:inline"> Vérifiez aussi vos courriers indésirables.</p>

    <x-button variant="ghost" :href="route('login')" class="mt-[62px] w-full"><span aria-hidden="true">←</span> Retour à la connexion</x-button>

    <x-modal name="reset-link-sent" title="Consultez votre messagerie." :show="session()->has('status')">
        <p class="text-body-lg">Si un compte correspond à cette adresse,<br class="hidden sm:inline"> vous recevrez un lien de réinitialisation.</p>

        <x-slot:footer>
            <x-button :href="route('login')" class="w-full">Retour à la connexion</x-button>
        </x-slot:footer>
    </x-modal>
</x-auth.page>
