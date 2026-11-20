<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Course\Domain\Contracts;

use Src\AcademicCatalog\Course\Domain\Entities\Course;

interface CourseRepositoryInterface
{
    public function find(int $id): ?Course;

    public function findByCode(string $code): ?Course;

    /**
     * @return array<int, Course>
     */
    public function all(): array;

    /**
     * Carga solo los cursos pedidos, indexados por id.
     *
     * @param  array<int, int>  $ids
     * @return array<int, Course>
     */
    public function findManyByIds(array $ids): array;

    public function save(Course $course): Course;

    public function delete(int $id): void;
}
