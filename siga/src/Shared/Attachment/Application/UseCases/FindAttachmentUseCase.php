<?php

declare(strict_types=1);

namespace Src\Shared\Attachment\Application\UseCases;

use Src\Shared\Attachment\Domain\Contracts\AttachmentRepositoryInterface;
use Src\Shared\Attachment\Domain\Entities\Attachment;
use Src\Shared\Attachment\Domain\Exceptions\AttachmentNotFoundException;

final class FindAttachmentUseCase
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $repository,
    ) {}

    public function handle(int $id): Attachment
    {
        return $this->repository->find($id) ?? throw AttachmentNotFoundException::withId($id);
    }
}
