{{-- Monogram and site name, linking to the home page. --}}
<a href="{{ url('/') }}" {{ $attributes->merge(['class' => 'inline-flex items-center gap-3 rounded-button focus-visible:outline-3 focus-visible:outline-offset-4 focus-visible:outline-primary']) }}>
    <span class="flex size-8 items-center justify-center rounded-button bg-primary font-display text-2xl leading-none font-bold text-lime" aria-hidden="true">/</span>
    <span class="font-display text-title-sm font-bold text-ink">{{ config('app.brand') }}</span>
</a>
