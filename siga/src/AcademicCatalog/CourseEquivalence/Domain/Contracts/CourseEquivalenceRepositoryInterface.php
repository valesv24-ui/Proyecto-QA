<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\CourseEquivalence\Domain\Contracts;

use Src\AcademicCatalog\CourseEquivalence\Domain\Entities\CourseEquivalence;

interface CourseEquivalenceRepositoryInterface
{
    public function find(int $id): ?CourseEquivalence;

    /**
     * @return array<int, CourseEquivalence>
     */
    public function forTargetCourse(int $targetCourseId): array;

    public function save(CourseEquivalence $courseEquivalence): CourseEquivalence;

    public function delete(int $id): void;
}
