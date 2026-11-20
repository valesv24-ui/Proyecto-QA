<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Src\Shared\Attachment\Application\DTOs\AttachmentDTO;
use Src\Shared\Attachment\Application\UseCases\CreateAttachmentUseCase;
use Src\Shared\Attachment\Application\UseCases\ListAttachmentsForUseCase;
use Src\Shared\Attachment\Domain\Entities\Attachment;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Subida, listado y descarga de documentos adjuntos para cualquier componente. */
trait ManagesAttachments
{
    /**
     * Reglas de validación de formulario para un archivo adjunto.
     *
     * @return array<int, string>
     */
    protected function attachmentRules(bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'file',
            'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx',
            'max:'.(Attachment::MAX_SIZE_BYTES / 1024),
        ];
    }

    /**
     * Guarda el archivo en disco y registra sus metadatos.
     *
     * @param  mixed  $file  Instancia de TemporaryUploadedFile de Livewire, o null.
     */
    protected function persistAttachment(
        CreateAttachmentUseCase $useCase,
        mixed $file,
        string $attachableType,
        int $attachableId,
        string $documentType,
        string $directory = 'attachments',
    ): void {
        if ($file === null) {
            return;
        }

        $path = $file->store($directory, 'local');

        // Auth::id() viene tipado int|string|null por el framework.
        $userId = Auth::id();

        // hash_file() devuelve false si no puede leer el archivo.
        $hash = hash_file('sha256', $file->getRealPath());
        if ($hash === false) {
            throw new RuntimeException('No se pudo calcular el hash SHA-256 del archivo subido.');
        }

        $useCase->handle(new AttachmentDTO(
            uuid: (string) Str::uuid(),
            userId: $userId !== null ? (int) $userId : null,
            attachableType: $attachableType,
            attachableId: $attachableId,
            documentType: $documentType,
            originalName: $file->getClientOriginalName(),
            disk: 'local',
            path: $path,
            mimeType: $file->getMimeType(),
            sizeBytes: $file->getSize(),
            sha256Hash: $hash,
        ));
    }

    /**
     * Tipos que un navegador sabe MOSTRAR sin descargar.
     *
     * @var array<int, string>
     */
    private const PREVIEWABLE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    /**
     * Proyección plana de los adjuntos de un modelo.
     *
     * @return array<int, array{id: int|null, documentType: string, originalName: string, sizeLabel: string, previewUrl: string|null}>
     */
    protected function attachmentRows(ListAttachmentsForUseCase $useCase, string $attachableType, int $attachableId): array
    {
        return array_map(
            fn (Attachment $attachment): array => [
                'id' => $attachment->id(),
                'documentType' => $attachment->documentType(),
                'originalName' => $attachment->originalName(),
                'sizeLabel' => $this->humanFileSize($attachment->sizeBytes()),
                'previewUrl' => $this->previewUrlFor($attachment),
            ],
            $useCase->handle($attachableType, $attachableId),
        );
    }

    private function previewUrlFor(Attachment $attachment): ?string
    {
        $id = $attachment->id();

        if ($id === null || ! in_array($attachment->mimeType(), self::PREVIEWABLE_MIME_TYPES, true)) {
            return null;
        }

        return route('shared.attachment.preview', ['attachment' => $id]);
    }

    protected function streamAttachmentDownload(Attachment $attachment): StreamedResponse
    {
        return Storage::disk($attachment->disk())->download($attachment->path(), $attachment->originalName());
    }

    private function humanFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 1).' MB';
    }
}
