<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\AcademicRecord\Infrastructure\Persistence\Repositories;

use App\Models\AcademicRecord as AcademicRecordModel;
use Src\AcademicCatalog\AcademicRecord\Domain\Contracts\AcademicRecordRepositoryInterface;
use Src\AcademicCatalog\AcademicRecord\Domain\Entities\AcademicRecord;

final class EloquentAcademicRecordRepository implements AcademicRecordRepositoryInterface
{
    public function find(int $id): ?AcademicRecord
    {
        $model = AcademicRecordModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function forStudent(int $studentId): array
    {
        return AcademicRecordModel::query()
            ->where('student_id', $studentId)
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function findByStudentAndCourse(int $studentId, int $courseId): ?AcademicRecord
    {
        $model = AcademicRecordModel::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function save(AcademicRecord $academicRecord): AcademicRecord
    {
        $model = $academicRecord->id()
            ? AcademicRecordModel::query()->findOrFail($academicRecord->id())
            : new AcademicRecordModel;

        $model->student_id = $academicRecord->studentId();
        $model->course_id = $academicRecord->courseId();
        $model->academic_term_id = $academicRecord->academicTermId();
        $model->status = $academicRecord->status();
        $model->grade = $academicRecord->grade();
        $model->course_equivalence_id = $academicRecord->courseEquivalenceId();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        AcademicRecordModel::query()->whereKey($id)->delete();
    }

    private function toDomain(AcademicRecordModel $model): AcademicRecord
    {
        return AcademicRecord::reconstitute(
            id: $model->id,
            studentId: $model->student_id,
            courseId: $model->course_id,
            academicTermId: $model->academic_term_id,
            status: $model->status,
            grade: $model->grade !== null ? (float) $model->grade : null,
            courseEquivalenceId: $model->course_equivalence_id,
        );
    }
}
