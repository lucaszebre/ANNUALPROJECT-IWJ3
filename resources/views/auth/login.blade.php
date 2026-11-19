
<x-guest-layout>
    <h1>Connexion</h1>

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <label for="email">Adresse e-mail</label>
        <input
            id="email"
            name="email"
            type="email"
            value="{{ old('email') }}"
            required
            autofocus
            autocomplete="username"
        >
        @error('email') <p>{{ $message }}</p> @enderror

        <label for="password">Mot de passe</label>
        <input
            id="password"
            name="password"
            type="password"
            required
            autocomplete="current-password"
        >
        @error('password') <p>{{ $message }}</p> @enderror

        <label>
            <input name="remember" type="checkbox">
            Se souvenir de moi
        </label>

        <button type="submit">Se connecter</button>
    </form>

    <a href="{{ route('password.request') }}">Mot de passe oublié ?</a>
    <a href="{{ route('register') }}">Créer un compte</a>
    
<x-guest-layout>