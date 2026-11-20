<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\Level\Infrastructure\Persistence\Repositories;

use App\Models\Level as LevelModel;
use Src\AcademicCatalog\Level\Domain\Contracts\LevelRepositoryInterface;
use Src\AcademicCatalog\Level\Domain\Entities\Level;

final class EloquentLevelRepository implements LevelRepositoryInterface
{
    public function find(int $id): ?Level
    {
        $model = LevelModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function forStudyPlan(int $studyPlanId): array
    {
        return LevelModel::query()
            ->where('study_plan_id', $studyPlanId)
            ->orderBy('number')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(Level $level): Level
    {
        $model = $level->id()
            ? LevelModel::query()->findOrFail($level->id())
            : new LevelModel;

        $model->study_plan_id = $level->studyPlanId();
        $model->number = $level->number();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        LevelModel::query()->whereKey($id)->delete();
    }

    private function toDomain(LevelModel $model): Level
    {
        return Level::reconstitute(
            id: $model->id,
            studyPlanId: $model->study_plan_id,
            number: $model->number,
        );
    }
}
