<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Prerequisite\Infrastructure\Persistence\Repositories;

use App\Models\Prerequisite as PrerequisiteModel;
use Illuminate\Database\Eloquent\Builder;
use Src\AcademicCatalog\Prerequisite\Domain\Contracts\PrerequisiteRepositoryInterface;
use Src\AcademicCatalog\Prerequisite\Domain\Entities\Prerequisite;

final class EloquentPrerequisiteRepository implements PrerequisiteRepositoryInterface
{
    public function requiredFor(int $courseId, ?int $studyPlanId = null): array
    {
        return $this->toDomainList(
            $this->scoped($studyPlanId)->where('target_course_id', $courseId)
        );
    }

    public function unlockedBy(int $courseId, ?int $studyPlanId = null): array
    {
        return $this->toDomainList(
            $this->scoped($studyPlanId)->where('required_course_id', $courseId)
        );
    }

    public function requiredForMany(array $courseIds, ?int $studyPlanId = null): array
    {
        if ($courseIds === []) {
            return [];
        }

        // Una consulta para todo el catálogo, no una por curso.
        $rows = $this->scoped($studyPlanId)
            ->whereIn('target_course_id', $courseIds)
            ->get(['target_course_id', 'required_course_id']);

        $byCourse = [];
        foreach ($rows as $row) {
            $byCourse[(int) $row->target_course_id][] = (int) $row->required_course_id;
        }

        return $byCourse;
    }

    public function exists(int $studyPlanId, int $requiredCourseId, int $targetCourseId): bool
    {
        return PrerequisiteModel::query()
            ->where('study_plan_id', $studyPlanId)
            ->where('required_course_id', $requiredCourseId)
            ->where('target_course_id', $targetCourseId)
            ->exists();
    }

    /**
     * El plan es opcional en las consultas de lectura.
     *
     * @return Builder<PrerequisiteModel>
     */
    private function scoped(?int $studyPlanId): Builder
    {
        $query = PrerequisiteModel::query();

        return $studyPlanId === null ? $query : $query->where('study_plan_id', $studyPlanId);
    }

    /**
     * @param  Builder<PrerequisiteModel>  $query
     * @return array<int, Prerequisite>
     */
    private function toDomainList(Builder $query): array
    {
        return $query->get()->map($this->toDomain(...))->all();
    }

    private function toDomain(PrerequisiteModel $model): Prerequisite
    {
        return Prerequisite::reconstitute(
            id: $model->id,
            studyPlanId: $model->study_plan_id,
            requiredCourseId: $model->required_course_id,
            targetCourseId: $model->target_course_id,
        );
    }
}
