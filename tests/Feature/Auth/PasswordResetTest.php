<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('sends a password reset link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertSessionHasNoErrors();
    Notification::assertSentTo($user, ResetPassword::class);
});

it('rejects a password reset for an unknown email', function () {
    Notification::fake();

    $response = $this->post('/forgot-password', [
        'email' => 'unknown@example.test',
    ]);

    $response->assertSessionHasErrors('email');
    Notification::assertNothingSent();
});

it('resets a password with a valid reset token', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewStrongPassword42',
        'password_confirmation' => 'NewStrongPassword42',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertTrue(
        password_verify('NewStrongPassword42', $user->refresh()->password)
    );
});

it('rejects a weak new password', function () {
    $user = User::factory()->create();
    $token = Password::broker()->createToken($user);

    $response = $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ]);

    $response->assertSessionHasErrors('password');
    $this->assertFalse(
        password_verify('weak', $user->refresh()->password)
    );
});
