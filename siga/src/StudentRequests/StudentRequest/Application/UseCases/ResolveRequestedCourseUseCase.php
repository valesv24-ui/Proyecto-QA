<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use InvalidArgumentException;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;

/** Las dos cosas que Registro hace sobre un curso. */
final class ResolveRequestedCourseUseCase
{
    public function __construct(
        private readonly RequestedCourseRepositoryInterface $repository,
    ) {}

    public function completeExternalData(
        int $studentRequestId,
        int $requestedCourseId,
        ?string $code,
        ?int $credits,
        ?float $grade = null,
    ): RequestedCourse {
        $course = $this->find($studentRequestId, $requestedCourseId);

        $course->completeExternalData($code, $credits, $grade);

        return $this->repository->save($studentRequestId, $course);
    }

    public function resolve(
        int $studentRequestId,
        int $requestedCourseId,
        string $outcome,
        ?string $denialReason = null,
    ): RequestedCourse {
        $course = $this->find($studentRequestId, $requestedCourseId);

        $course->resolve($outcome, $denialReason);

        return $this->repository->save($studentRequestId, $course);
    }

    /** El id del curso llega desde el navegador. */
    private function find(int $studentRequestId, int $requestedCourseId): RequestedCourse
    {
        foreach ($this->repository->forRequest($studentRequestId) as $course) {
            if ($course->id() === $requestedCourseId) {
                return $course;
            }
        }

        throw new InvalidArgumentException('El curso no pertenece a esta solicitud.');
    }
}
