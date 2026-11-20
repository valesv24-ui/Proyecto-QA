<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Student\Infrastructure\Persistence\Repositories;

use App\Models\AcademicRecord as AcademicRecordModel;
use App\Models\Student as StudentModel;
use Illuminate\Support\Facades\DB;
use Src\AcademicCatalog\Student\Domain\Contracts\StudentRepositoryInterface;
use Src\AcademicCatalog\Student\Domain\Entities\Student;

final class EloquentStudentRepository implements StudentRepositoryInterface
{
    /**
     * Estados de academic_records que cuentan como "aprobado".
     *
     * @var array<int, string>
     */
    private const APPROVED_STATUSES = [
        'Aprobado',
        'Acreditado por equiparación',
        'Acreditado por convalidación',
    ];

    /**
     * Estados que impiden pedir un levantamiento o una convalidación.
     *
     * Los cuatro en que el curso ya está ganado, más Matriculado: si lo está
     * cursando, el trámite tampoco tiene objeto.
     *
     * @var array<int, string>
     */
    private const BLOCKING_STATUSES = [
        'Aprobado',
        'Acreditado por equiparación',
        'Acreditado por convalidación',
        'Requisito levantado',
        'Matriculado',
    ];

    public function find(int $id): ?Student
    {
        $model = StudentModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByNationalId(string $nationalId): ?Student
    {
        $model = StudentModel::query()->where('national_id', $nationalId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUserId(int $userId): ?Student
    {
        $model = StudentModel::query()->where('user_id', $userId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, Student>
     */
    public function findManyByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $indexed = [];
        foreach (StudentModel::query()->whereIn('id', array_unique($ids))->get() as $model) {
            $indexed[(int) $model->id] = $this->toDomain($model);
        }

        return $indexed;
    }

    public function save(Student $student): Student
    {
        $model = $student->id()
            ? StudentModel::query()->findOrFail($student->id())
            : new StudentModel;

        $model->user_id = $student->userId();
        $model->national_id = $student->nationalId();
        $model->first_name = $student->firstName();
        $model->last_name = $student->lastName();
        $model->second_last_name = $student->secondLastName();
        $model->is_active = $student->isActive();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        StudentModel::query()->whereKey($id)->delete();
    }

    public function accumulatedApprovedCredits(int $studentId): int
    {
        // Mismo criterio de "aprobado" que AcademicRecord::satisfiesMinimumGrade().
        $courseIds = AcademicRecordModel::query()
            ->where('student_id', $studentId)
            ->whereIn('status', self::APPROVED_STATUSES)
            ->pluck('course_id');

        if ($courseIds->isEmpty()) {
            return 0;
        }

        return (int) DB::table('course_level')
            ->whereIn('course_id', $courseIds)
            ->sum('credits');
    }

    /**
     * @return array<int, int>
     */
    public function enrolledDegreeProgramIds(int $studentId): array
    {
        return DB::table('student_study_plan')
            ->join('study_plans', 'study_plans.id', '=', 'student_study_plan.study_plan_id')
            ->where('student_study_plan.student_id', $studentId)
            ->distinct()
            ->pluck('study_plans.degree_program_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    public function approvedCourseIds(int $studentId): array
    {
        return AcademicRecordModel::query()
            ->where('student_id', $studentId)
            ->whereIn('status', self::APPROVED_STATUSES)
            ->pluck('course_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function blockedCourseIds(int $studentId): array
    {
        return AcademicRecordModel::query()
            ->where('student_id', $studentId)
            ->whereIn('status', self::BLOCKING_STATUSES)
            ->pluck('course_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function isEnrolledInTerminalPlan(int $studentId): bool
    {
        return StudentModel::query()
            ->whereKey($studentId)
            ->whereHas('studyPlans', fn ($query) => $query->where('classification', 'Terminal'))
            ->exists();
    }

    private function toDomain(StudentModel $model): Student
    {
        return Student::reconstitute(
            id: $model->id,
            userId: $model->user_id,
            nationalId: $model->national_id,
            firstName: $model->first_name,
            lastName: $model->last_name,
            secondLastName: $model->second_last_name,
            isActive: $model->is_active,
        );
    }
}
