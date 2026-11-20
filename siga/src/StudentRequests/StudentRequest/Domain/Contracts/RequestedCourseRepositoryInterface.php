<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Contracts;

use DateTimeImmutable;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;

interface RequestedCourseRepositoryInterface
{
    /**
     * Cursos de una solicitud.
     *
     * @return array<int, RequestedCourse>
     */
    public function forRequest(int $studentRequestId): array;

    /**
     * @param  array<int, RequestedCourse>  $courses
     */
    public function addAll(int $studentRequestId, array $courses): void;

    public function save(int $studentRequestId, RequestedCourse $course): RequestedCourse;

    public function find(int $id): ?RequestedCourse;

    /** Los datos de la sesión que resolvió. */
    public function recordResolutionDetails(
        int $studentRequestId,
        string $resolutionNumber,
        string $sessionNumber,
        string $minutesNumber,
        DateTimeImmutable $sessionDate,
    ): void;

    /**
     * @return array{resolutionNumber: string|null, sessionNumber: string|null, minutesNumber: string|null, sessionDate: string|null}
     */
    public function resolutionDetails(int $studentRequestId): array;
}
