<x-guest-layout>
    <h1>Créer un compte</h1>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <label for="name">Nom</label>
        <input id="name" name="name" value="{{ old('name') }}"
               required autofocus autocomplete="name">
        @error('name') <p>{{ $message }}</p> @enderror

        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email"
               value="{{ old('email') }}" required autocomplete="username">
        @error('email') <p>{{ $message }}</p> @enderror

        <label for="password">Mot de passe</label>
        <input id="password" name="password" type="password"
               required autocomplete="new-password">
        @error('password') <p>{{ $message }}</p> @enderror

        <label for="password_confirmation">Confirmer le mot de passe</label>
        <input id="password_confirmation" name="password_confirmation"
               type="password" required autocomplete="new-password">

        <button type="submit">S'inscrire</button>
    </form>
</x-guest-layout>
