<?php

use App\Http\Controllers\LessonVideoUploadController;
use App\Http\Controllers\OrganizationSelectionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VideoPlaylistController;
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

        Route::post('/lessons/{lesson}/video/upload-url', [LessonVideoUploadController::class, 'create'])
            ->name('lessons.video.upload-url');
        Route::post('/lessons/{lesson}/video', [LessonVideoUploadController::class, 'store'])
            ->name('lessons.video.store');

        Route::get('/videos/{video}/hls/master.m3u8', [VideoPlaylistController::class, 'master'])
            ->name('videos.hls.master');
        Route::get('/videos/{video}/hls/{rendition}/index.m3u8', [VideoPlaylistController::class, 'rendition'])
            ->name('videos.hls.rendition');
    });
});

// Local and tests only: the design system, to compare the components with Figma.
if (app()->environment(['local', 'testing'])) {
    Route::view('/design-system', 'design-system')->name('design-system');
}
