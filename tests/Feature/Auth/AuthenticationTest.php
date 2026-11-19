<?php

use App\Models\User;

it('logs in with valid credentials', function () {
    $user = User::factory()->create([
        'password' => 'StrongPassword42',
    ]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'StrongPassword42',
    ]);

    $response->assertRedirect('/post-login');
    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials with a generic validation error', function () {
    $user = User::factory()->create([
        'password' => 'StrongPassword42',
    ]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'incorrect-password',
    ]);

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});
