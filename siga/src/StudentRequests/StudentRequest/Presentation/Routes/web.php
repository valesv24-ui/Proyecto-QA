<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\StudentRequests\StudentRequest\Presentation\Http\Controllers\PreviewPrecedentDocumentController;
use Src\StudentRequests\StudentRequest\Presentation\Livewire\StudentRequestComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('student-requests', StudentRequestComponent::class)
    ->name('studentrequests.studentrequest.index');

// Ver la resolución del precedente sin salir del expediente.
Route::middleware(['web', 'auth', 'verified'])
    ->get('student-requests/{studentRequest}/precedent-documents/{attachment}/preview', PreviewPrecedentDocumentController::class)
    ->whereNumber(['studentRequest', 'attachment'])
    ->name('studentrequests.studentrequest.precedent-document');
