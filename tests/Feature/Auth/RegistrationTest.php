<?php

use App\Models\User;

it('registers a user and hashes their password', function () {
    $response = $this->post('/register', [
        'name' => 'Pauline Ente',
        'email' => 'pauline@example.test',
        'password' => 'StrongPassword42',
        'password_confirmation' => 'StrongPassword42',
    ]);

    $response->assertRedirect('/post-login');
    $this->assertAuthenticated();

    $user = User::query()
        ->where('email', 'pauline@example.test')
        ->firstOrFail();

    expect($user->password)
        ->not->toBe('StrongPassword42')
        ->and(password_verify('StrongPassword42', $user->password))->toBeTrue();
});
