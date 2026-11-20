{{-- Shown after login when the user belongs to several organisations. No mockup: styled like the account pages. --}}
<x-layouts.app title="Choisir une organisation">
    <div class="page-x py-10">
        <div class="mx-auto max-w-[568px] pt-4 sm:pt-8">
            <p class="text-2xs font-semibold text-primary uppercase">Mon compte</p>
            <h1 class="mt-5 text-title leading-[1.22] tracking-[-0.03em] sm:text-[40px]">Choisir une organisation</h1>
            <p class="mt-5 text-body-lg text-muted">Les cours et votre progression dépendent de l’organisation choisie.</p>

            @if ($organizations->isEmpty())
                <x-alert type="warning" class="mt-10">Votre compte n’est rattaché à aucune organisation.</x-alert>
            @else
                <form method="POST" action="{{ route('organizations.select.store') }}" class="mt-10 flex flex-col gap-[34px]" novalidate>
                    @csrf

                    <x-select
                        name="organization_id"
                        label="Organisation"
                        placeholder="Sélectionner…"
                        :options="$organizations->pluck('name', 'id')->all()"
                        required
                        autofocus
                    />

                    <x-button class="w-full">Continuer <span aria-hidden="true">→</span></x-button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.app>
