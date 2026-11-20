<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Local and tests only: the design system, to compare the components with Figma.
if (app()->environment(['local', 'testing'])) {
    Route::view('/design-system', 'design-system')->name('design-system');
}
