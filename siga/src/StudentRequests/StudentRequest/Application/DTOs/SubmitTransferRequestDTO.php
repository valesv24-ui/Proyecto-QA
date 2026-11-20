<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\DTOs;

final class SubmitTransferRequestDTO
{
    /**
     * @param  array<int, array{courseId: int, externalCourse: string, sourceInstitution: string}>  $courses
     */
    public function __construct(
        public readonly int $studentId,
        public readonly array $courses,
    ) {}

    /**
     * El primer curso de la lista.
     *
     * @return array{courseId: int, externalCourse: string, sourceInstitution: string}
     */
    public function headline(): array
    {
        return $this->courses[0];
    }
}
