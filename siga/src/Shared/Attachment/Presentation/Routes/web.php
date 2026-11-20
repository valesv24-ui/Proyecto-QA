<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Shared\Attachment\Presentation\Http\Controllers\PreviewAttachmentController;

// Ruta HTTP normal y no una acción de Livewire.
Route::middleware(['web', 'auth', 'verified'])
    ->get('attachments/{attachment}/preview', PreviewAttachmentController::class)
    ->whereNumber('attachment')
    ->name('shared.attachment.preview');
