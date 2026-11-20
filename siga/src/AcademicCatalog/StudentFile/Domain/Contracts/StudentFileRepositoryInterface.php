<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudentFile\Domain\Contracts;

use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFile;

interface StudentFileRepositoryInterface
{
    /** Expediente completo de un estudiante, o null si no existe. */
    public function findForStudent(int $studentId): ?StudentFile;
}
