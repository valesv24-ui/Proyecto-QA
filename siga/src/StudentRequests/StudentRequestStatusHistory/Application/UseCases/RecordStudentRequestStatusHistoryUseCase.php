<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases;

use Src\StudentRequests\StudentRequestStatusHistory\Domain\Contracts\StudentRequestStatusHistoryRepositoryInterface;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Entities\StudentRequestStatusHistory;

/** ES-03. */
final class RecordStudentRequestStatusHistoryUseCase
{
    public function __construct(
        private readonly StudentRequestStatusHistoryRepositoryInterface $repository,
    ) {}

    public function handle(int $studentRequestId, ?string $previousStatus, string $newStatus, ?int $userId, ?string $comment = null): StudentRequestStatusHistory
    {
        return $this->repository->save(
            StudentRequestStatusHistory::record($studentRequestId, $previousStatus, $newStatus, $userId, $comment),
        );
    }
}
