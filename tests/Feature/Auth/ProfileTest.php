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
            'email_current_password' => 'password',
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

it('refuses to change the email without the current password', function () {
    $user = User::factory()->create(['email' => 'camille@example.test']);

    $this->actingAs($user)
        ->from(route('profile'))
        ->put(route('user-profile-information.update'), [
            'name' => $user->name,
            'email' => 'attacker@example.test',
        ])
        ->assertRedirect(route('profile'))
        ->assertSessionHasErrors('email_current_password');

    expect($user->refresh()->email)->toBe('camille@example.test');
});

it('refuses to change the email with a wrong current password', function () {
    $user = User::factory()->create(['email' => 'camille@example.test']);

    $this->actingAs($user)
        ->from(route('profile'))
        ->put(route('user-profile-information.update'), [
            'name' => $user->name,
            'email' => 'attacker@example.test',
            'email_current_password' => 'wrong-password',
        ])
        ->assertRedirect(route('profile'))
        ->assertSessionHasErrors('email_current_password');

    expect($user->refresh()->email)->toBe('camille@example.test');
});

it('asks for the current password in French when changing the email without it', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from(route('profile'))
        ->followingRedirects()
        ->put(route('user-profile-information.update'), ['name' => $user->name, 'email' => 'new@example.test'])
        ->assertSee('Saisissez votre mot de passe actuel pour changer d’adresse email.', false);
});

it('blocks both forms after five wrong current passwords', function () {
    $user = User::factory()->create(['password' => 'OldPassword4242']);
    $this->actingAs($user)->from(route('profile'));

    foreach (range(1, 3) as $_) {
        $this->put(route('user-password.update'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword4242',
            'password_confirmation' => 'NewPassword4242',
        ])->assertSessionHasErrors('current_password');
    }

    foreach (range(1, 2) as $_) {
        $this->put(route('user-profile-information.update'), [
            'name' => $user->name,
            'email' => 'attacker@example.test',
            'email_current_password' => 'wrong-password',
        ])->assertSessionHasErrors('email_current_password');
    }

    // Even the right password is refused until the limit resets.
    $this->put(route('user-password.update'), [
        'current_password' => 'OldPassword4242',
        'password' => 'NewPassword4242',
        'password_confirmation' => 'NewPassword4242',
    ])->assertStatus(429)->assertHeader('Retry-After');

    $this->put(route('user-profile-information.update'), [
        'name' => $user->name,
        'email' => $user->email,
    ])->assertStatus(429);

    expect(Hash::check('OldPassword4242', $user->refresh()->password))->toBeTrue();
});

it('only counts wrong current passwords and resets after a right one', function () {
    $user = User::factory()->create(['password' => 'OldPassword4242']);
    $this->actingAs($user)->from(route('profile'));

    $wrongGuess = fn () => $this->put(route('user-password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'NewPassword4242',
        'password_confirmation' => 'NewPassword4242',
    ])->assertSessionHasErrors('current_password');

    foreach (range(1, 4) as $_) {
        $wrongGuess();
    }

    // Saves without a password, or with a weak new one, are not guesses.
    foreach (range(1, 3) as $_) {
        $this->put(route('user-profile-information.update'), ['name' => 'Camille', 'email' => $user->email])
            ->assertSessionHasNoErrors();
    }

    $this->put(route('user-password.update'), [
        'current_password' => 'OldPassword4242',
        'password' => 'weak',
        'password_confirmation' => 'weak',
    ])->assertSessionHasErrors('password');

    // The right password above reset the count.
    foreach (range(1, 4) as $_) {
        $wrongGuess();
    }
});
