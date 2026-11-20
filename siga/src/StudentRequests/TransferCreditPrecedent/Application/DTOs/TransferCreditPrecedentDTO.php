<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Application\DTOs;

final class TransferCreditPrecedentDTO
{
    public function __construct(
        public readonly string $institution,
        public readonly string $externalCourse,
        public readonly int $courseId,
        public readonly string $outcome,
        public readonly string $resolutionNumber,
    ) {}
}
