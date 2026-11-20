<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Domain\Exceptions;

use DomainException;

final class TransferCreditPrecedentNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("TransferCreditPrecedent with id [{$id}] was not found.");
    }
}
