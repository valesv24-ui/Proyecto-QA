<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Application\UseCases;

use Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts\TransferCreditPrecedentRepositoryInterface;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;

final class ListTransferCreditPrecedentsUseCase
{
    public function __construct(
        private readonly TransferCreditPrecedentRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, TransferCreditPrecedent>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        return $this->repository->all($search, $sortBy, $sortDir);
    }

    /**
     * @return array{items: array<int, TransferCreditPrecedent>, total: int}
     */
    public function paginate(
        ?string $search = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array {
        return $this->repository->paginate($search, $perPage, $page, $sortBy, $sortDir);
    }
}
