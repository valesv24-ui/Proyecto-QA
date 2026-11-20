<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Prerequisite\Application\UseCases;

use Src\AcademicCatalog\Prerequisite\Domain\Contracts\PrerequisiteRepositoryInterface;

/** Las dos preguntas que se le hacen a la malla. */
final class ListCoursePrerequisitesUseCase
{
    public function __construct(
        private readonly PrerequisiteRepositoryInterface $repository,
    ) {}

    /**
     * IDs de los cursos que hay que aprobar antes.
     *
     * @return array<int, int>
     */
    public function requiredFor(int $courseId, ?int $studyPlanId = null): array
    {
        return array_map(
            fn ($prerequisite): int => $prerequisite->requiredCourseId(),
            $this->repository->requiredFor($courseId, $studyPlanId),
        );
    }

    /**
     * Requisitos de varios cursos, en una sola consulta.
     *
     * @param  array<int, int>  $courseIds
     * @return array<int, array<int, int>>
     */
    public function requiredForMany(array $courseIds, ?int $studyPlanId = null): array
    {
        return $this->repository->requiredForMany($courseIds, $studyPlanId);
    }

    /**
     * IDs de los cursos que $courseId habilita.
     *
     * @return array<int, int>
     */
    public function unlockedBy(int $courseId, ?int $studyPlanId = null): array
    {
        return array_map(
            fn ($prerequisite): int => $prerequisite->targetCourseId(),
            $this->repository->unlockedBy($courseId, $studyPlanId),
        );
    }

    /** ¿El requisito que dice la boleta es un requisito real. */
    public function confirms(int $studyPlanId, int $requiredCourseId, int $targetCourseId): ?bool
    {
        if ($this->repository->requiredFor($targetCourseId, $studyPlanId) === []) {
            return null;
        }

        return $this->repository->exists($studyPlanId, $requiredCourseId, $targetCourseId);
    }
}
