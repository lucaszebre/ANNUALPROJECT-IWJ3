<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;

it('shows the visitor header with sign-in actions', function () {
    $this->get(route('design-system'))
        ->assertOk()
        ->assertSee('CodeCampus')
        ->assertSee('Connexion')
        ->assertSee('Créer un compte')
        ->assertDontSee('Mon compte');
});

it('shows the student header with the organisation and the account menu', function () {
    $user = User::factory()->create(['name' => 'Camille Dupont']);
    Membership::factory()->for(Organization::factory()->create(['name' => 'École du Web']))->for($user)->create();

    $this->actingAs($user)->get(route('design-system'))
        ->assertOk()
        ->assertSee('Mes apprentissages')
        ->assertSee('École du Web')
        ->assertSee('École du Web · Étudiant')
        ->assertSee('Mon compte')
        ->assertDontSee('Créer un compte')
        ->assertDontSee('Mes cours');
});

it('adds the teaching link for teachers', function () {
    $user = User::factory()->create();
    Membership::factory()->teacher()->for($user)->create();

    $this->actingAs($user)->get(route('design-system'))
        ->assertOk()
        ->assertSee('Mes cours')
        ->assertDontSee('Administration');
});
