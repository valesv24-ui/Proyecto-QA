<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Role\Application\UseCases;

use Src\IdentityAccess\Role\Application\DTOs\RoleDTO;
use Src\IdentityAccess\Role\Domain\Contracts\RoleRepositoryInterface;
use Src\IdentityAccess\Role\Domain\Entities\Role;

/** Single-purpose orchestrator (SRP): turns a RoleDTO into a persisted Role. */
final class CreateRoleUseCase
{
    public function __construct(
        private readonly RoleRepositoryInterface $repository,
    ) {}

    public function handle(RoleDTO $dto): Role
    {
        $role = Role::create(name: $dto->name, permissions: $dto->permissions);

        return $this->repository->save($role);
    }
}
