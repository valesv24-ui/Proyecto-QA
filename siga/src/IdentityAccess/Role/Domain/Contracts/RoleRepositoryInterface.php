<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Role\Domain\Contracts;

use Src\IdentityAccess\Role\Domain\Entities\Role;

/** Port (in the Hexagonal sense) that Infrastructure adapters must implement. */
interface RoleRepositoryInterface
{
    public function find(int $id): ?Role;

    /**
     * @return array<int, Role>
     */
    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array;

    /**
     * @return array{items: array<int, Role>, total: int}
     */
    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array;

    public function save(Role $role): Role;

    public function delete(int $id): void;
}
