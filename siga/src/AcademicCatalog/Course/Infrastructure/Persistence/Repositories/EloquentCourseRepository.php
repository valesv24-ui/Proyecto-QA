<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Course\Infrastructure\Persistence\Repositories;

use App\Models\Course as CourseModel;
use Src\AcademicCatalog\Course\Domain\Contracts\CourseRepositoryInterface;
use Src\AcademicCatalog\Course\Domain\Entities\Course;

final class EloquentCourseRepository implements CourseRepositoryInterface
{
    public function find(int $id): ?Course
    {
        $model = CourseModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCode(string $code): ?Course
    {
        $model = CourseModel::query()->where('code', $code)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        return CourseModel::query()
            ->orderBy('code')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, Course>
     */
    public function findManyByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $indexed = [];
        foreach (CourseModel::query()->whereIn('id', array_unique($ids))->get() as $model) {
            $indexed[(int) $model->id] = $this->toDomain($model);
        }

        return $indexed;
    }

    public function save(Course $course): Course
    {
        $model = $course->id()
            ? CourseModel::query()->findOrFail($course->id())
            : new CourseModel;

        $model->degree_program_id = $course->degreeProgramId();
        $model->code = $course->code();
        $model->name = $course->name();
        $model->is_service_course = $course->isServiceCourse();
        $model->requires_lab = $course->requiresLab();
        $model->lab_type = $course->labType();
        $model->is_active = $course->isActive();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        CourseModel::query()->whereKey($id)->delete();
    }

    private function toDomain(CourseModel $model): Course
    {
        return Course::reconstitute(
            id: $model->id,
            degreeProgramId: $model->degree_program_id,
            code: $model->code,
            name: $model->name,
            isServiceCourse: $model->is_service_course,
            requiresLab: $model->requires_lab,
            labType: $model->lab_type,
            isActive: $model->is_active,
        );
    }
}
