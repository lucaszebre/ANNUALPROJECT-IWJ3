<?php

use App\Models\User;

it('logs out and clears access to the previous session', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->withSession(['current_organization_id' => fake()->uuid()])
        ->post('/logout');

    $response->assertRedirect('/');
    $this->assertGuest();
    $response->assertSessionMissing('current_organization_id');
});
