<?php

use App\Http\Controllers\OrganizationSelectionController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('auth')->group(function (): void {
    Route::get('/post-login', [OrganizationSelectionController::class, 'postLogin'])
        ->name('post-login');

    Route::get('/organizations/select', [OrganizationSelectionController::class, 'index'])
        ->name('organizations.select');

    Route::post('/organizations/select', [OrganizationSelectionController::class, 'store'])
        ->name('organizations.select.store');

    Route::middleware('organization.selected')->group(function (): void {
        Route::view('/dashboard', 'dashboard')->name('dashboard');
    });
});
