<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Application\DTOs;

/** Una regla de levantamiento. */
final class WaiverRuleDTO
{
    public function __construct(
        public readonly int $courseId,
        public readonly int $evaluationOrder,
        public readonly string $type,
        public readonly bool $isActive,
    ) {}
}
