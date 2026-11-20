<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\AcademicRecord\Domain\Contracts;

use Src\AcademicCatalog\AcademicRecord\Domain\Entities\AcademicRecord;

interface AcademicRecordRepositoryInterface
{
    public function find(int $id): ?AcademicRecord;

    /**
     * @return array<int, AcademicRecord>
     */
    public function forStudent(int $studentId): array;

    public function findByStudentAndCourse(int $studentId, int $courseId): ?AcademicRecord;

    public function save(AcademicRecord $academicRecord): AcademicRecord;

    public function delete(int $id): void;
}
