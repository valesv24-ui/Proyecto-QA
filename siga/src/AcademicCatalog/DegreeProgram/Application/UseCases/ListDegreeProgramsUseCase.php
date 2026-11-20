<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\DegreeProgram\Application\UseCases;

use Src\AcademicCatalog\DegreeProgram\Domain\Contracts\DegreeProgramRepositoryInterface;
use Src\AcademicCatalog\DegreeProgram\Domain\Entities\DegreeProgram;

final class ListDegreeProgramsUseCase
{
    public function __construct(
        private readonly DegreeProgramRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, DegreeProgram>
     */
    public function all(): array
    {
        return $this->repository->all();
    }
}
