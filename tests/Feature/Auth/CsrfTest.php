<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;

it('rejects a login request without a csrf token', function () {
    $this->app->instance('env', 'local');

    try {
        $response = $this
            ->withMiddleware(PreventRequestForgery::class)
            ->withHeader('Sec-Fetch-Site', 'cross-site')
            ->post('/login', [
                'email' => 'person@example.test',
                'password' => 'StrongPassword42',
            ]);

        $response->assertStatus(419);
    } finally {
        $this->app->instance('env', 'testing');
    }
});
