<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Prerequisite\Domain\Contracts;

use Src\AcademicCatalog\Prerequisite\Domain\Entities\Prerequisite;

interface PrerequisiteRepositoryInterface
{
    /**
     * Cursos que hay que aprobar antes de llevar $courseId.
     *
     * @return array<int, Prerequisite>
     */
    public function requiredFor(int $courseId, ?int $studyPlanId = null): array;

    /**
     * Cursos que $courseId habilita: los que lo tienen como requisito.
     *
     * @return array<int, Prerequisite>
     */
    public function unlockedBy(int $courseId, ?int $studyPlanId = null): array;

    /**
     * Requisitos de varios cursos: id del curso => ids requeridos.
     *
     * @param  array<int, int>  $courseIds
     * @return array<int, array<int, int>>
     */
    public function requiredForMany(array $courseIds, ?int $studyPlanId = null): array;

    /** ¿Es $requiredCourseId requisito de $targetCourseId en este plan? Existe. */
    public function exists(int $studyPlanId, int $requiredCourseId, int $targetCourseId): bool;
}
