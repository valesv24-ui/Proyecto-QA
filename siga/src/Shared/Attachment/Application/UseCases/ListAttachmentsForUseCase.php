<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Application\UseCases;

use Src\Shared\Attachment\Domain\Contracts\AttachmentRepositoryInterface;
use Src\Shared\Attachment\Domain\Entities\Attachment;

final class ListAttachmentsForUseCase
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, Attachment>
     */
    public function handle(string $attachableType, int $attachableId): array
    {
        return $this->repository->forAttachable($attachableType, $attachableId);
    }
}
