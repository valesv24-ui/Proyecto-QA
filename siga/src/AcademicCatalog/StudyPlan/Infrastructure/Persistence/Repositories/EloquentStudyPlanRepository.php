<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudyPlan\Infrastructure\Persistence\Repositories;

use App\Models\StudyPlan as StudyPlanModel;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Src\AcademicCatalog\StudyPlan\Domain\Contracts\StudyPlanRepositoryInterface;
use Src\AcademicCatalog\StudyPlan\Domain\Entities\StudyPlan;

final class EloquentStudyPlanRepository implements StudyPlanRepositoryInterface
{
    public function find(int $id): ?StudyPlan
    {
        $model = StudyPlanModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        return StudyPlanModel::query()
            ->orderBy('name')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(StudyPlan $studyPlan): StudyPlan
    {
        $model = $studyPlan->id()
            ? StudyPlanModel::query()->findOrFail($studyPlan->id())
            : new StudyPlanModel;

        $model->degree_program_id = $studyPlan->degreeProgramId();
        $model->name = $studyPlan->name();
        $model->implementation_year = $studyPlan->implementationYear();
        $model->classification = $studyPlan->classification();
        // Same DateTimeImmutable (Domain) -> Carbon (Eloquent cast) bridge as EloquentAcademicTermRepository.
        $model->enrollment_closing_date = $studyPlan->enrollmentClosingDate() !== null
            ? Carbon::instance($studyPlan->enrollmentClosingDate())
            : null;
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        StudyPlanModel::query()->whereKey($id)->delete();
    }

    private function toDomain(StudyPlanModel $model): StudyPlan
    {
        return StudyPlan::reconstitute(
            id: $model->id,
            degreeProgramId: $model->degree_program_id,
            name: $model->name,
            implementationYear: $model->implementation_year,
            classification: $model->classification,
            enrollmentClosingDate: $model->enrollment_closing_date
                ? new DateTimeImmutable($model->enrollment_closing_date->toDateString())
                : null,
        );
    }
}
