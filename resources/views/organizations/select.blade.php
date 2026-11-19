
<x-guest-layout>
    <h1>Choisir une organisation</h1>

    @if ($organizations->isEmpty())
        <p>Votre compte n’est rattaché à aucune organisation.</p>
    @else
        <form method="POST" action="{{ route('organizations.select.store') }}">
            @csrf

            <label for="organization_id">Organisation</label>
            <select id="organization_id" name="organization_id" required autofocus>
                <option value="">Sélectionner…</option>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}">
                        {{ $organization->name }}
                    </option>
                @endforeach
            </select>

            @error('organization_id') <p>{{ $message }}</p> @enderror

            <button type="submit">Continuer</button>
        </form>
    @endif
</x-guest-layout>
