<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\DTOs;

/** Lo que el estudiante llena en la boleta SLR-002. */
final class SubmitWaiverRequestDTO
{
    public function __construct(
        public readonly int $studentId,
        public readonly int $courseId,
        public readonly int $prerequisiteCourseId,
        public readonly string $justification,
        /** Se recibe el hecho de que aceptó. */
        public readonly bool $acceptsTerms,
    ) {}
}
