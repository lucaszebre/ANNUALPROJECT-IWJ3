{{-- Monogram and site name, linking to the home page. --}}
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 rounded-button focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-primary']) }}>
    <span class="flex size-7 items-center justify-center rounded-[8px] bg-primary font-display text-xl leading-none font-bold text-lime" aria-hidden="true">/</span>
    <span class="font-display text-lg font-bold text-ink">{{ config('app.brand') }}</span>
</a>
