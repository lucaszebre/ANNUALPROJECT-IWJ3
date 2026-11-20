{{--
    Editorial panel of the sign-in screens (Figma "Compte / Panneau éditorial", 568 × 800).
    The title slot changes per page; the badge, code sample and footer line do not.
--}}
<aside {{ $attributes->merge(['class' => 'min-h-[800px] flex-col rounded-overlay bg-dark px-7 pt-[30px] pb-[53px]']) }}>
    <p class="self-start rounded-[14px] bg-lime px-3 py-[5px] text-2xs font-semibold text-ink">VOTRE APPRENTISSAGE, RÉUNI</p>

    <p class="mt-[76px] font-display text-[43px] leading-[1.22] font-bold tracking-[-0.03em] text-white">{{ $title }}</p>
    <p class="mt-12 text-[19px] leading-normal text-code-muted">{{ $slot }}</p>

    <div class="mt-auto rounded-card bg-code p-6 font-mono" aria-hidden="true">
        <p class="text-sm text-code-muted">// Prochaine étape : vous lancer.</p>
        <pre class="mt-7 text-[17px] leading-normal text-lime">function apprendre() {
  comprendre();
  pratiquer();
  progresser();
}</pre>
    </div>

    <p class="mt-[70px] text-[15px] text-code-muted">Un parcours clair. Des acquis concrets.</p>
</aside>
