<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Domain\Contracts;

use Src\Shared\Attachment\Domain\Entities\Attachment;

interface AttachmentRepositoryInterface
{
    public function find(int $id): ?Attachment;

    public function findByUuid(string $uuid): ?Attachment;

    /**
     * @return array<int, Attachment>
     */
    public function forAttachable(string $attachableType, int $attachableId): array;

    public function save(Attachment $attachment): Attachment;

    public function delete(int $id): void;
}
