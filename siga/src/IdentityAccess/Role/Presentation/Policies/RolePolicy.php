<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Role\Presentation\Policies;

use Src\Shared\Authorization\Presentation\Policies\ResourcePermissionPolicy;

/** Registered via Gate::policy() in DomainServiceProvider::$domainPolicies. */
class RolePolicy
{
    use ResourcePermissionPolicy;

    protected static function resourcePermissionPrefix(): string
    {
        return 'roles';
    }
}
