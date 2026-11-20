<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Presentation\Policies;

use Src\Shared\Authorization\Presentation\Policies\ResourcePermissionPolicy;

/** Registrada vía Gate::policy() en DomainServiceProvider::$domainPolicies. */
class WaiverRulePolicy
{
    use ResourcePermissionPolicy;

    protected static function resourcePermissionPrefix(): string
    {
        return 'waiver_rules';
    }
}
