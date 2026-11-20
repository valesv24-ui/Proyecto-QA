<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** ES-04: bandeja centralizada. */
final class ListStudentRequestsForInboxUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
    ) {}

    /**
     * @param  array{search?: string, type?: string, status?: string, career?: string, dateFrom?: string, dateTo?: string, openOnly?: bool, resolvedOnly?: bool, deskStatuses?: array<int, string>}  $filters
     * @return array{items: array<int, StudentRequest>, total: int}
     */
    public function handle(array $filters, string $sortBy, string $sortDir, int $perPage, int $page): array
    {
        return $this->repository->paginateForInbox($filters, $sortBy, $sortDir, $perPage, $page);
    }
}
