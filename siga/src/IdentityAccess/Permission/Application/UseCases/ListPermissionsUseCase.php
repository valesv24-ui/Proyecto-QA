<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Application\UseCases;

use Src\IdentityAccess\Permission\Domain\Contracts\PermissionRepositoryInterface;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;

final class ListPermissionsUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
    ) {}

    /**
     * Full, unpaginated collection.
     *
     * @return array<int, Permission>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        return $this->repository->all($search, $sortBy, $sortDir);
    }

    /**
     * Server-paginated collection.
     *
     * @return array{items: array<int, Permission>, total: int}
     */
    public function paginate(
        ?string $search = null,
        ?string $module = null,
        int $perPage = 10,
        int $page = 1,
        ?string $sortBy = null,
        string $sortDir = 'asc',
    ): array {
        return $this->repository->paginate($search, $module, $perPage, $page, $sortBy, $sortDir);
    }
}
