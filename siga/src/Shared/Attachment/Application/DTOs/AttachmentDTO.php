<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Application\DTOs;

/** Solo primitivos: el UseCase no conoce Illuminate\Http\UploadedFile ni Livewire\WithFileUploads. */
final class AttachmentDTO
{
    public function __construct(
        public readonly string $uuid,
        public readonly ?int $userId,
        public readonly string $attachableType,
        public readonly int $attachableId,
        public readonly string $documentType,
        public readonly string $originalName,
        public readonly string $disk,
        public readonly string $path,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $sha256Hash,
    ) {}
}
