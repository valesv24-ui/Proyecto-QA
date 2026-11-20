<?php

declare(strict_types=1);

namespace Src\Shared\Authorization\Presentation\Policies;

use App\Models\User;

/** RolePolicy, PermissionPolicy. */
trait ResourcePermissionPolicy
{
    /** The permission prefix this policy checks against, e.g. */
    abstract protected static function resourcePermissionPrefix(): string;

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.view');
    }

    public function view(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.view');
    }

    public function search(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.search');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.edit');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.delete');
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.export_pdf');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo(static::resourcePermissionPrefix().'.export_excel');
    }
}
