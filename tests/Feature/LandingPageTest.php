<?php

use App\Models\User;

it('shows the landing page to visitors with sign-up actions', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Apprenez à coder,', false)
        ->assertSee('Comment ça marche')
        ->assertSee('Pour les enseignants')
        ->assertSee('Commencer gratuitement')
        ->assertSee('aria-current="page"', false)
        ->assertDontSee('Coming soon');
});

it('sends signed-in users back to their courses', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk()
        ->assertSee('Reprendre mes cours')
        ->assertDontSee('Commencer gratuitement');
});

it('links the favicons in the layout', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('favicon.svg')
        ->assertSee('favicon.ico')
        ->assertSee('apple-touch-icon.png');

    expect(public_path('favicon.svg'))->toBeFile()
        ->and(filesize(public_path('favicon.ico')))->toBeGreaterThan(0)
        ->and(public_path('apple-touch-icon.png'))->toBeFile();
});
