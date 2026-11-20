<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Level\Domain\Contracts;

use Src\AcademicCatalog\Level\Domain\Entities\Level;

interface LevelRepositoryInterface
{
    public function find(int $id): ?Level;

    /**
     * @return array<int, Level>
     */
    public function forStudyPlan(int $studyPlanId): array;

    public function save(Level $level): Level;

    public function delete(int $id): void;
}
