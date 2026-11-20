<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Exceptions;

use DomainException;

final class StudentRequestNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("StudentRequest with id [{$id}] was not found.");
    }
}
