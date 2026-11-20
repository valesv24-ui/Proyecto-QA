<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\AcademicCatalog\StudentFile\Presentation\Livewire\StudentFileComponent;

Route::middleware(['web', 'auth', 'verified'])
    ->get('academic-record', StudentFileComponent::class)
    ->name('academiccatalog.studentfile.index');
