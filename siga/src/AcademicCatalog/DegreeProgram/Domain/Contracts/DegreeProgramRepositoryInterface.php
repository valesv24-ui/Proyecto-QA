<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\DegreeProgram\Domain\Contracts;

use Src\AcademicCatalog\DegreeProgram\Domain\Entities\DegreeProgram;

/** Port (en el sentido Hexagonal) que los adaptadores de Infrastructure. */
interface DegreeProgramRepositoryInterface
{
    public function find(int $id): ?DegreeProgram;

    /**
     * @return array<int, DegreeProgram>
     */
    public function all(): array;

    public function save(DegreeProgram $degreeProgram): DegreeProgram;

    public function delete(int $id): void;
}
