<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a login request without a csrf token', function () {
    $this->withMiddleware(PreventRequestForgery::class);

    $response = $this
        ->withServerVariables([
            'HTTP_SEC_FETCH_SITE' => 'cross-site',
        ])
        ->post('/login', [
            'email' => 'person@example.test',
            'password' => 'StrongPassword42',
        ]);

    $response->assertStatus(419);
});
