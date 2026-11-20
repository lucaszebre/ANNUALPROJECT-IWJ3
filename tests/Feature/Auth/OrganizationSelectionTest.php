<?php

use App\Enums\MemberRole;
use App\Models\Organization;
use App\Models\User;

function membershipAttributes(): array
{
    return [
        'id' => fake()->uuid(),
        'role' => MemberRole::cases()[0]->value,
    ];
}

it('automatically selects the only accessible organization', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->organizations()->attach(
        $organization->id,
        membershipAttributes()
    );

    $response = $this
        ->actingAs($user)
        ->get(route('post-login'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas(
        'current_organization_id',
        $organization->id
    );
});

it('allows selecting an organization the user belongs to', function () {
    $user = User::factory()->create();
    $organization = Organization::factory()->create();

    $user->organizations()->attach(
        $organization->id,
        membershipAttributes()
    );

    $response = $this
        ->actingAs($user)
        ->post(route('organizations.select.store'), [
            'organization_id' => $organization->id,
        ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas(
        'current_organization_id',
        $organization->id
    );
});

it('does not allow selecting a foreign organization', function () {
    $user = User::factory()->create();
    $foreignOrganization = Organization::factory()->create();

    $response = $this
        ->actingAs($user)
        ->post(route('organizations.select.store'), [
            'organization_id' => $foreignOrganization->id,
        ]);

    $response->assertNotFound();
    $response->assertSessionMissing('current_organization_id');
});

it('allows a new account without organizations to complete login', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get(route('post-login'));

    $response->assertRedirect(route('organizations.select'));
    $response->assertSessionMissing('current_organization_id');
});
