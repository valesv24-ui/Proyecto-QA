<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Domain\Entities;

use InvalidArgumentException;

/** Attachment — Aggregate Root compartido. */
final class Attachment
{
    public const MAX_SIZE_BYTES = 10 * 1024 * 1024;

    /**
     * Se listan los MIME reales que envían los navegadores.
     *
     * @var array<int, string>
     */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    private function __construct(
        private readonly ?int $id,
        private readonly string $uuid,
        private readonly ?int $userId,
        private readonly string $attachableType,
        private readonly int $attachableId,
        private readonly string $documentType,
        private readonly string $originalName,
        private readonly string $disk,
        private readonly string $path,
        private readonly string $mimeType,
        private readonly int $sizeBytes,
        private readonly string $sha256Hash,
    ) {
        if ($sizeBytes <= 0) {
            throw new InvalidArgumentException('El tamaño del archivo debe ser mayor que cero.');
        }
        if ($sizeBytes > self::MAX_SIZE_BYTES) {
            throw new InvalidArgumentException('El archivo excede el máximo de 10 MB permitido.');
        }
        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException("Tipo de archivo no permitido: {$mimeType}. Se acepta PDF, imagen, Word o Excel.");
        }
    }

    public static function create(
        string $uuid,
        ?int $userId,
        string $attachableType,
        int $attachableId,
        string $documentType,
        string $originalName,
        string $disk,
        string $path,
        string $mimeType,
        int $sizeBytes,
        string $sha256Hash,
    ): self {
        return new self(null, $uuid, $userId, $attachableType, $attachableId, $documentType, $originalName, $disk, $path, $mimeType, $sizeBytes, $sha256Hash);
    }

    public static function reconstitute(
        int $id,
        string $uuid,
        ?int $userId,
        string $attachableType,
        int $attachableId,
        string $documentType,
        string $originalName,
        string $disk,
        string $path,
        string $mimeType,
        int $sizeBytes,
        string $sha256Hash,
    ): self {
        return new self($id, $uuid, $userId, $attachableType, $attachableId, $documentType, $originalName, $disk, $path, $mimeType, $sizeBytes, $sha256Hash);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function attachableType(): string
    {
        return $this->attachableType;
    }

    public function attachableId(): int
    {
        return $this->attachableId;
    }

    public function documentType(): string
    {
        return $this->documentType;
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function disk(): string
    {
        return $this->disk;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function sizeBytes(): int
    {
        return $this->sizeBytes;
    }

    public function sha256Hash(): string
    {
        return $this->sha256Hash;
    }
}
