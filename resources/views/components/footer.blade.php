{{-- Site footer (Figma "Navigation / Footer"). --}}
<footer class="bg-canvas">
    <div class="page-x flex flex-col gap-4 py-6 md:flex-row md:items-start md:justify-between">
        <div>
            <p class="text-[21px] leading-normal font-semibold text-ink">{{ config('app.brand') }}</p>
            <p class="mt-2 text-label text-muted">Apprendre, pratiquer, progresser.</p>
        </div>
        <div class="flex flex-col gap-3 md:items-end">
            <nav aria-label="Liens du pied de page">
                <ul class="flex flex-wrap gap-x-6 gap-y-2 text-label text-muted">
                    @foreach (['Aide', 'Contact', 'Conditions', 'Confidentialité', 'Accessibilité'] as $item)
                        <li><a href="#" class="hover:text-ink focus-visible:outline-2 focus-visible:outline-primary">{{ $item }}</a></li>
                    @endforeach
                </ul>
            </nav>
            <p class="text-2xs text-muted">© {{ now()->year }} {{ config('app.brand') }}</p>
        </div>
    </div>
</footer>
