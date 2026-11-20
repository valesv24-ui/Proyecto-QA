<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudentFile\Application\UseCases;

use Src\AcademicCatalog\StudentFile\Domain\Contracts\StudentFileRepositoryInterface;
use Src\AcademicCatalog\StudentFile\Domain\Entities\StudentFile;

/** Expediente del estudiante para el detalle de una solicitud (ES-04). */
final class FindStudentFileUseCase
{
    public function __construct(
        private readonly StudentFileRepositoryInterface $repository,
    ) {}

    public function handle(int $studentId): ?StudentFile
    {
        return $this->repository->findForStudent($studentId);
    }
}
