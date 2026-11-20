<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\IdentityAccess\Jwt\Presentation\Http\Middleware\AuthenticateJwt;
use Src\StudentRequests\StudentRequest\Presentation\Http\Controllers\Api\StudentRequestStatusController;

// ES-03 vía API.
Route::middleware(['api', AuthenticateJwt::class])
    ->get('api/student-requests', [StudentRequestStatusController::class, 'index'])
    ->name('api.studentrequests.index');
