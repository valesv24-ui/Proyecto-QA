<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\AcademicTerm\Domain\Contracts;

use DateTimeImmutable;
use Src\AcademicCatalog\AcademicTerm\Domain\Entities\AcademicTerm;

interface AcademicTermRepositoryInterface
{
    public function find(int $id): ?AcademicTerm;

    /** El cuatrimestre que contiene una fecha. */
    public function findForDate(DateTimeImmutable $date): ?AcademicTerm;

    /**
     * @return array<int, AcademicTerm>
     */
    public function all(): array;

    public function save(AcademicTerm $academicTerm): AcademicTerm;

    public function delete(int $id): void;
}
