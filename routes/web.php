<?php

use App\Http\Controllers\OrganizationSelectionController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/post-login', [OrganizationSelectionController::class, 'postLogin'])
        ->name('post-login');

    Route::get('/organizations/select', [OrganizationSelectionController::class, 'index'])
        ->name('organizations.select');

    Route::post('/organizations/select', [OrganizationSelectionController::class, 'store'])
        ->name('organizations.select.store');

    // Outside 'organization.selected': the account belongs to the user, not to an organisation.
    Route::get('/profile', ProfileController::class)->name('profile');

    Route::middleware('organization.selected')->group(function (): void {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });
});

// Local and tests only: the design system, to compare the components with Figma.
if (app()->environment(['local', 'testing'])) {
    Route::view('/design-system', 'design-system')->name('design-system');
}
