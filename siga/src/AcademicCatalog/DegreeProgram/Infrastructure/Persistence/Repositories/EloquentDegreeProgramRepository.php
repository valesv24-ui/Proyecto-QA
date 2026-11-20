<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\DegreeProgram\Infrastructure\Persistence\Repositories;

use App\Models\DegreeProgram as DegreeProgramModel;
use Src\AcademicCatalog\DegreeProgram\Domain\Contracts\DegreeProgramRepositoryInterface;
use Src\AcademicCatalog\DegreeProgram\Domain\Entities\DegreeProgram;

final class EloquentDegreeProgramRepository implements DegreeProgramRepositoryInterface
{
    public function find(int $id): ?DegreeProgram
    {
        $model = DegreeProgramModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        return DegreeProgramModel::query()
            ->orderBy('name')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(DegreeProgram $degreeProgram): DegreeProgram
    {
        $model = $degreeProgram->id()
            ? DegreeProgramModel::query()->findOrFail($degreeProgram->id())
            : new DegreeProgramModel;

        $model->name = $degreeProgram->name();
        $model->is_active = $degreeProgram->isActive();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        DegreeProgramModel::query()->whereKey($id)->delete();
    }

    private function toDomain(DegreeProgramModel $model): DegreeProgram
    {
        return DegreeProgram::reconstitute(
            id: $model->id,
            name: $model->name,
            isActive: $model->is_active,
        );
    }
}
