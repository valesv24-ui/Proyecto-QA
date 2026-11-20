<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\DTOs;

use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** ES-01, criterio de aceptación. */
final class SubmitWaiverRequestResult
{
    private function __construct(
        public readonly bool $isDuplicate,
        public readonly ?StudentRequest $studentRequest,
    ) {}

    public static function duplicate(): self
    {
        return new self(true, null);
    }

    public static function created(StudentRequest $studentRequest): self
    {
        return new self(false, $studentRequest);
    }
}
