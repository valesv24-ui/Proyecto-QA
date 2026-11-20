<?php

declare(strict_types=1);

use Src\Shared\Attachment\Domain\Entities\Attachment;

/** Solo el bloque de subidas temporales de Livewire. */
return [
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK'),

        // El MISMO tope del Dominio, en kilobytes.
        'rules' => ['required', 'file', 'max:'.(int) (Attachment::MAX_SIZE_BYTES / 1024)],

        'directory' => null,
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
            'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma',
        ],
        'max_upload_time' => 5,
        'cleanup' => true,
    ],
];
