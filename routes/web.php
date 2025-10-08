<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// NOTE: `routes/api.php` is intentionally not required here. API routes
// are registered in their own file and should be loaded under the
// 'api' middleware group to keep them stateless (no CSRF/session).
