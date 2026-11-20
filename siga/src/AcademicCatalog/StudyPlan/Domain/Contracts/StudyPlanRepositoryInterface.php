<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudyPlan\Domain\Contracts;

use Src\AcademicCatalog\StudyPlan\Domain\Entities\StudyPlan;

interface StudyPlanRepositoryInterface
{
    public function find(int $id): ?StudyPlan;

    /**
     * @return array<int, StudyPlan>
     */
    public function all(): array;

    public function save(StudyPlan $studyPlan): StudyPlan;

    public function delete(int $id): void;
}
