<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Student\Domain\Contracts;

use Src\AcademicCatalog\Student\Domain\Entities\Student;

interface StudentRepositoryInterface
{
    public function find(int $id): ?Student;

    public function findByNationalId(string $nationalId): ?Student;

    public function findByUserId(int $userId): ?Student;

    /**
     * Carga varios estudiantes de una sola consulta, indexados por id.
     *
     * @param  array<int, int>  $ids
     * @return array<int, Student>
     */
    public function findManyByIds(array $ids): array;

    public function save(Student $student): Student;

    public function delete(int $id): void;

    /** Suma de créditos de los cursos que el estudiante tiene. */
    public function accumulatedApprovedCredits(int $studentId): int;

    /** ES-01, tipo (c). */
    public function isEnrolledInTerminalPlan(int $studentId): bool;

    /**
     * Carreras en las que el estudiante está matriculado.
     *
     * @return array<int, int>
     */
    public function enrolledDegreeProgramIds(int $studentId): array;

    /**
     * Cursos que el estudiante YA tiene aprobados en su expediente.
     *
     * @return array<int, int>
     */
    public function approvedCourseIds(int $studentId): array;

    /**
     * Cursos que el estudiante ya ganó o está cursando.
     *
     * @return array<int, int>
     */
    public function blockedCourseIds(int $studentId): array;
}
