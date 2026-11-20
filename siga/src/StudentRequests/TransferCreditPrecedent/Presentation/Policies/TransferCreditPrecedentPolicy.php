<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Presentation\Policies;

use Src\Shared\Authorization\Presentation\Policies\ResourcePermissionPolicy;

/** Registrada vía Gate::policy() en DomainServiceProvider::$domainPolicies. */
class TransferCreditPrecedentPolicy
{
    use ResourcePermissionPolicy;

    protected static function resourcePermissionPrefix(): string
    {
        return 'transfer_credit_precedents';
    }
}
