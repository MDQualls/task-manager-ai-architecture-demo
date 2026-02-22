<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// SPA shell — serves the React app for /app and any sub-path
// (catch-all ensures deep links work if React Router is added later)
Route::get('/app/{any?}', function () {
    return view('app');
})->where('any', '.*');
