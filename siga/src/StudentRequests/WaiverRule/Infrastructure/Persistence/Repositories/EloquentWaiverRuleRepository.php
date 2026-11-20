<?php

declare(strict_types=1);

namespace Src\StudentRequests\WaiverRule\Infrastructure\Persistence\Repositories;

use App\Models\WaiverRule as WaiverRuleModel;
use Illuminate\Database\Eloquent\Builder;
use Src\Shared\Persistence\Infrastructure\Concerns\ResolvesSortOrder;
use Src\StudentRequests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

final class EloquentWaiverRuleRepository implements WaiverRuleRepositoryInterface
{
    use ResolvesSortOrder;

    /**
     * @var array<int, string>
     */
    private const SORTABLE_COLUMNS = ['evaluation_order', 'type', 'is_active'];

    public function find(int $id): ?WaiverRule
    {
        $model = WaiverRuleModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function activeForCourseOrdered(int $courseId): array
    {
        return WaiverRuleModel::query()
            ->where('course_id', $courseId)
            ->where('is_active', true)
            ->orderBy('evaluation_order')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $query = $this->searchQuery($search);

        $column = $this->resolveSortColumn($sortBy, self::SORTABLE_COLUMNS, 'evaluation_order');
        $direction = $this->resolveSortDirection($sortDir);

        return $query
            ->orderBy('course_id')
            ->orderBy($column, $direction)
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $column = $this->resolveSortColumn($sortBy, self::SORTABLE_COLUMNS, 'evaluation_order');
        $direction = $this->resolveSortDirection($sortDir);

        $paginator = $this->searchQuery($search)
            ->orderBy($column, $direction)
            ->paginate(perPage: $perPage, page: $page);

        return [
            'items' => array_map($this->toDomain(...), $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    /**
     * @return Builder<WaiverRuleModel>
     */
    private function searchQuery(?string $search)
    {
        // Sin ->with('course'): toDomain() solo guarda courseId, nunca lee la relación.
        $query = WaiverRuleModel::query();

        if (filled($search)) {
            $query->whereHas('course', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function save(WaiverRule $waiverRule): WaiverRule
    {
        $model = $waiverRule->id()
            ? WaiverRuleModel::query()->findOrFail($waiverRule->id())
            : new WaiverRuleModel;

        $model->course_id = $waiverRule->courseId();
        $model->evaluation_order = $waiverRule->evaluationOrder();
        $model->type = $waiverRule->type();
        $model->is_active = $waiverRule->isActive();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        WaiverRuleModel::query()->whereKey($id)->delete();
    }

    private function toDomain(WaiverRuleModel $model): WaiverRule
    {
        return WaiverRule::reconstitute(
            id: $model->id,
            courseId: $model->course_id,
            evaluationOrder: $model->evaluation_order,
            type: $model->type,
            isActive: $model->is_active,
        );
    }
}
