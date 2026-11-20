<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Presentation\Policies;

use Src\Shared\Authorization\Presentation\Policies\ResourcePermissionPolicy;

/** Registered via Gate::policy() in DomainServiceProvider::$domainPolicies. */
class PermissionPolicy
{
    use ResourcePermissionPolicy;

    protected static function resourcePermissionPrefix(): string
    {
        return 'permissions';
    }
}
