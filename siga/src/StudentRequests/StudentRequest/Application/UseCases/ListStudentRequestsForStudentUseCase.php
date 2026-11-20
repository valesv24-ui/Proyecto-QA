<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** ES-03. */
final class ListStudentRequestsForStudentUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, StudentRequest>
     */
    public function handle(int $studentId): array
    {
        return $this->repository->forStudent($studentId);
    }
}
