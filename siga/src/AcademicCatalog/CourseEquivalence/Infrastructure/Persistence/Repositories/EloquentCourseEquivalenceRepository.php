<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\CourseEquivalence\Infrastructure\Persistence\Repositories;

use App\Models\CourseEquivalence as CourseEquivalenceModel;
use Src\AcademicCatalog\CourseEquivalence\Domain\Contracts\CourseEquivalenceRepositoryInterface;
use Src\AcademicCatalog\CourseEquivalence\Domain\Entities\CourseEquivalence;

final class EloquentCourseEquivalenceRepository implements CourseEquivalenceRepositoryInterface
{
    public function find(int $id): ?CourseEquivalence
    {
        $model = CourseEquivalenceModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function forTargetCourse(int $targetCourseId): array
    {
        return CourseEquivalenceModel::query()
            ->where('target_course_id', $targetCourseId)
            ->where('status', 'Vigente')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(CourseEquivalence $courseEquivalence): CourseEquivalence
    {
        $model = $courseEquivalence->id()
            ? CourseEquivalenceModel::query()->findOrFail($courseEquivalence->id())
            : new CourseEquivalenceModel;

        $model->source_course_id = $courseEquivalence->sourceCourseId();
        $model->target_course_id = $courseEquivalence->targetCourseId();
        $model->direction = $courseEquivalence->direction();
        $model->resolution_number = $courseEquivalence->resolutionNumber();
        $model->status = $courseEquivalence->status();
        $model->superseded_by_id = $courseEquivalence->supersededById();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        CourseEquivalenceModel::query()->whereKey($id)->delete();
    }

    private function toDomain(CourseEquivalenceModel $model): CourseEquivalence
    {
        return CourseEquivalence::reconstitute(
            id: $model->id,
            sourceCourseId: $model->source_course_id,
            targetCourseId: $model->target_course_id,
            direction: $model->direction,
            resolutionNumber: $model->resolution_number,
            status: $model->status,
            supersededById: $model->superseded_by_id,
        );
    }
}
