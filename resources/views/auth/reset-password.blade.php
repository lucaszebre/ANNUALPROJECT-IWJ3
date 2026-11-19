<x-guest-layout>

    <h1>Choisir un nouveau mot de passe</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email"
               value="{{ old('email', $request->email) }}" required>
        @error('email') <p>{{ $message }}</p> @enderror

        <label for="password">Nouveau mot de passe</label>
        <input id="password" name="password" type="password" required>
        @error('password') <p>{{ $message }}</p> @enderror

        <label for="password_confirmation">Confirmation</label>
        <input id="password_confirmation" name="password_confirmation"
               type="password" required>

        <button type="submit">Réinitialiser</button>
    </form>
</x-guest-layout>
