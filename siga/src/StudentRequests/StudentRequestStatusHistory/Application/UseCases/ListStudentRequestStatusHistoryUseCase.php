<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases;

use Src\StudentRequests\StudentRequestStatusHistory\Domain\Contracts\StudentRequestStatusHistoryRepositoryInterface;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Entities\StudentRequestStatusHistory;

final class ListStudentRequestStatusHistoryUseCase
{
    public function __construct(
        private readonly StudentRequestStatusHistoryRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, StudentRequestStatusHistory>
     */
    public function handle(int $studentRequestId): array
    {
        return $this->repository->forStudentRequest($studentRequestId);
    }
}
