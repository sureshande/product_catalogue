<?php

use Illuminate\Support\Facades\Route;

// API-only backend; the React client is served separately.
Route::get('/', fn () => ['name' => config('app.name'), 'api' => url('/api')]);
