<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard')->name('dashboard');

Route::get('/api/health', fn () => response()->json([
    'status' => 'ok',
    'stage' => 'starter',
    'stack' => ['database', 'mail', 'slack', 'webpush', 'queue'],
]));
