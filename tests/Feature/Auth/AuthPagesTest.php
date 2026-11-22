<?php

use App\Models\User;
use Illuminate\Support\Facades\Password;

it('shows the auth pages to visitors', function (string $route, string $heading) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee($heading)
        ->assertSee('name="_token"', false);
})->with([
    'login' => ['login', 'On reprend ?'],
    'register' => ['register', 'Apprendre commence ici.'],
    'forgot password' => ['password.request', 'Un nouveau départ.'],
]);

it('shows the reset password page from the emailed link', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('Choisissez un nouveau mot de passe.')
        ->assertSee($token)
        ->assertSee($user->email);
});

it('redirects signed-in users away from the auth pages', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('login'))
        ->assertRedirect();
});

it('shows the generic login error in French', function () {
    $user = User::factory()->create(['password' => 'StrongPassword42']);

    $this->from(route('login'))
        ->followingRedirects()
        ->post(route('login'), ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertSee('Identifiants non reconnus.')
        ->assertSee($user->email);
});

it('shows the registration errors next to the fields', function () {
    $this->from(route('register'))
        ->followingRedirects()
        ->post(route('register'), [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
        ->assertSee('Le champ nom complet est obligatoire.')
        ->assertSee('Le champ adresse email doit être une adresse email valide.')
        ->assertSee('Le champ mot de passe doit contenir au moins 12 caractères.')
        ->assertSee('Vous devez accepter les conditions d’utilisation.')
        ->assertSee('aria-invalid="true"', false);

    $this->assertGuest();
});

it('requires the terms to be accepted', function () {
    $this->post(route('register'), [
        'name' => 'Camille Dupont',
        'email' => 'camille@example.test',
        'password' => 'StrongPassword42',
        'password_confirmation' => 'StrongPassword42',
    ])->assertSessionHasErrors('terms');

    $this->assertGuest();
});

it('opens the confirmation dialog once the reset link is sent', function () {
    $user = User::factory()->create();

    $this->from(route('password.request'))
        ->followingRedirects()
        ->post(route('password.email'), ['email' => $user->email])
        ->assertSee('Consultez votre messagerie.')
        ->assertSee('show: true', false);
});
