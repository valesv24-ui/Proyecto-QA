<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\IdentityAccess\Jwt\Presentation\Http\Controllers\ApiAuthController;

// Único endpoint público de la capa de API.
Route::middleware(['api'])
    ->post('api/auth/login', [ApiAuthController::class, 'login'])
    ->name('api.auth.login');
