<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    RateLimiter::clear('limited@example.test|127.0.0.1');
});

it('rate limits repeated login attempts', function () {
    $user = User::factory()->create([
        'email' => 'limited@example.test',
        'password' => 'StrongPassword42',
    ]);

    foreach (range(1, 5) as $_) {
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(429);
});
