<?php

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('requires authentication', function () {
    $this->get(route('profile'))->assertRedirect(route('login'));
});

it('shows the profile with the current organisation', function () {
    $user = User::factory()->create(['name' => 'Camille Dupont', 'email' => 'camille@example.test']);
    $organization = Organization::factory()->create(['name' => 'École du Web']);
    Membership::factory()->for($organization)->for($user)->create();

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $organization->id])
        ->get(route('profile'))
        ->assertOk()
        ->assertSee('Mon profil')
        ->assertSee('value="Camille Dupont"', false)
        ->assertSee('value="camille@example.test"', false)
        ->assertSee('École du Web')
        ->assertSee('Étudiant')
        ->assertSee('Se déconnecter')
        ->assertDontSee('Changer d’organisation');
});

it('shows the organisation chosen in the session and offers to switch', function () {
    $user = User::factory()->create();
    $first = Organization::factory()->create(['name' => 'Académie Alpha']);
    $chosen = Organization::factory()->create(['name' => 'École Bêta']);
    Membership::factory()->for($first)->for($user)->create();
    Membership::factory()->teacher()->for($chosen)->for($user)->create();

    $this->actingAs($user)
        ->withSession(['current_organization_id' => $chosen->id])
        ->get(route('profile'))
        ->assertOk()
        ->assertSeeInOrder(['Vous suivez les cours de', 'École Bêta', 'Enseignant'])
        ->assertSee('Changer d’organisation');
});

it('updates the name and email', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile'))
        ->put(route('user-profile-information.update'), [
            'name' => 'Camille Martin',
            'email' => 'camille.martin@example.test',
        ])
        ->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'profile-information-updated');

    expect($user->refresh())
        ->name->toBe('Camille Martin')
        ->email->toBe('camille.martin@example.test');
});

it('shows the confirmation dialog after saving the profile', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile'))
        ->followingRedirects()
        ->put(route('user-profile-information.update'), ['name' => 'Camille Martin', 'email' => $user->email])
        ->assertSee('Votre profil est à jour.');
});

it('shows profile errors next to the profile fields only', function () {
    $other = User::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile'))
        ->followingRedirects()
        ->put(route('user-profile-information.update'), ['name' => '', 'email' => $other->email])
        ->assertSee('Le champ nom complet est obligatoire.')
        ->assertSee('Cette adresse email est déjà utilisée.')
        ->assertDontSee('Votre profil est à jour.');
});

it('updates the password', function () {
    $user = User::factory()->create(['password' => 'OldPassword4242']);

    $this->actingAs($user)
        ->from(route('profile'))
        ->put(route('user-password.update'), [
            'current_password' => 'OldPassword4242',
            'password' => 'NewPassword4242',
            'password_confirmation' => 'NewPassword4242',
        ])
        ->assertRedirect(route('profile'))
        ->assertSessionHas('status', 'password-updated');

    expect(Hash::check('NewPassword4242', $user->refresh()->password))->toBeTrue();
});

it('rejects a wrong current password or a weak new one', function () {
    $user = User::factory()->create(['password' => 'OldPassword4242']);

    $this->actingAs($user)
        ->from(route('profile'))
        ->followingRedirects()
        ->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])
        ->assertSee('Le mot de passe actuel est incorrect.')
        ->assertSee('Le champ mot de passe doit contenir au moins 12 caractères.');

    expect(Hash::check('OldPassword4242', $user->refresh()->password))->toBeTrue();
});
