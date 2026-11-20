{{--
    Sign-in screens (Figma "04 · Inscription", "05 · Connexion", "06 · Mot de passe oublié"):
    the form on the left, the editorial panel on the right from 1280 px (xl).
    The 616 / 568 columns and 112 px gap match the 1440 px mockups.

        <x-auth.page title="Connexion" eyebrow="…" heading="…" intro="…">
            <x-slot:panel-title>…</x-slot:panel-title>
            <x-slot:panel-text>…</x-slot:panel-text>
            <form>…</form>
        </x-auth.page>
--}}
@props(['title', 'eyebrow', 'heading', 'intro' => null, 'showStatus' => true])

<x-layouts.app :title="$title">
    <div class="page-x grid gap-16 py-10 xl:grid-cols-[616fr_568fr] xl:gap-28">
        <div class="mx-auto w-full max-w-[568px] pt-4 sm:pt-8 xl:mx-0 xl:ml-12 xl:pt-[18px]">
            <p class="text-2xs font-semibold text-primary uppercase">{{ $eyebrow }}</p>
            <h1 class="mt-5 text-title leading-[1.22] tracking-[-0.03em] sm:text-[40px]">{{ $heading }}</h1>
            @if ($intro)
                <p class="mt-5 text-body-lg text-muted">{{ $intro }}</p>
            @endif

            {{-- Fortify messages, e.g. "password reset" when coming back to the login page. --}}
            @if ($showStatus && session('status'))
                <x-alert type="success" class="mt-8">{{ session('status') }}</x-alert>
            @endif

            <div class="mt-10">
                {{ $slot }}
            </div>
        </div>

        <x-auth.panel class="hidden xl:flex">
            <x-slot:title>{{ $panelTitle }}</x-slot:title>
            {{ $panelText }}
        </x-auth.panel>
    </div>
</x-layouts.app>
