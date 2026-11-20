<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Application\UseCases;

use Src\Shared\Attachment\Application\DTOs\AttachmentDTO;
use Src\Shared\Attachment\Domain\Contracts\AttachmentRepositoryInterface;
use Src\Shared\Attachment\Domain\Entities\Attachment;

final class CreateAttachmentUseCase
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repository,
    ) {}

    public function handle(AttachmentDTO $dto): Attachment
    {
        $attachment = Attachment::create(
            uuid: $dto->uuid,
            userId: $dto->userId,
            attachableType: $dto->attachableType,
            attachableId: $dto->attachableId,
            documentType: $dto->documentType,
            originalName: $dto->originalName,
            disk: $dto->disk,
            path: $dto->path,
            mimeType: $dto->mimeType,
            sizeBytes: $dto->sizeBytes,
            sha256Hash: $dto->sha256Hash,
        );

        return $this->repository->save($attachment);
    }
}
