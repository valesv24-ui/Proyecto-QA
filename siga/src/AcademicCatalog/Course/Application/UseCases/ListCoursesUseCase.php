<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Course\Application\UseCases;

use Src\AcademicCatalog\Course\Domain\Contracts\CourseRepositoryInterface;
use Src\AcademicCatalog\Course\Domain\Entities\Course;

/** Catálogo simple para selectores. */
final class ListCoursesUseCase
{
    public function __construct(
        private readonly CourseRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, Course>
     */
    public function all(): array
    {
        return $this->repository->all();
    }

    /**
     * Solo los cursos pedidos, indexados por id.
     *
     * @param  array<int, int>  $ids
     * @return array<int, Course>
     */
    public function findManyByIds(array $ids): array
    {
        return $this->repository->findManyByIds($ids);
    }
}
