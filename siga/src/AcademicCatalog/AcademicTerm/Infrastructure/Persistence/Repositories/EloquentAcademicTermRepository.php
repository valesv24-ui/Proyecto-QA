<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\AcademicTerm\Infrastructure\Persistence\Repositories;

use App\Models\AcademicTerm as AcademicTermModel;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Src\AcademicCatalog\AcademicTerm\Domain\Contracts\AcademicTermRepositoryInterface;
use Src\AcademicCatalog\AcademicTerm\Domain\Entities\AcademicTerm;

final class EloquentAcademicTermRepository implements AcademicTermRepositoryInterface
{
    public function find(int $id): ?AcademicTerm
    {
        $model = AcademicTermModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findForDate(DateTimeImmutable $date): ?AcademicTerm
    {
        // Comparación por fecha sola (Y-m-d) y no por el DateTimeImmutable.
        $model = AcademicTermModel::query()
            ->whereDate('start_date', '<=', $date->format('Y-m-d'))
            ->whereDate('end_date', '>=', $date->format('Y-m-d'))
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function all(): array
    {
        return AcademicTermModel::query()
            ->orderByDesc('year')
            ->orderByDesc('term_number')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(AcademicTerm $academicTerm): AcademicTerm
    {
        $model = $academicTerm->id()
            ? AcademicTermModel::query()->findOrFail($academicTerm->id())
            : new AcademicTermModel;

        $model->year = $academicTerm->year();
        $model->term_number = $academicTerm->termNumber();
        // The Domain entity deliberately returns plain DateTimeImmutable.
        $model->start_date = Carbon::instance($academicTerm->startDate());
        $model->end_date = Carbon::instance($academicTerm->endDate());
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        AcademicTermModel::query()->whereKey($id)->delete();
    }

    private function toDomain(AcademicTermModel $model): AcademicTerm
    {
        return AcademicTerm::reconstitute(
            id: $model->id,
            year: $model->year,
            termNumber: $model->term_number,
            startDate: new DateTimeImmutable($model->start_date->toDateString()),
            endDate: new DateTimeImmutable($model->end_date->toDateString()),
        );
    }
}
