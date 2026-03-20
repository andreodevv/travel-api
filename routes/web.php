<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service'     => 'Travel Orders API',
        'status'      => 'online',
        'framework'   => 'Laravel 13',
        'auth_method' => 'JWT',
    ]);
});