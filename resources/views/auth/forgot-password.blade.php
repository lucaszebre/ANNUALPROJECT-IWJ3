<x-guest-layout>
    <h1>Mot de passe oublié</h1>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email"
               value="{{ old('email') }}" required autofocus>
        @error('email') <p>{{ $message }}</p> @enderror

        <button type="submit">Envoyer le lien</button>
    </form>
</x-guest-layout>
