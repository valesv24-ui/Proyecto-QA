<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Infrastructure\Persistence\Repositories;

use App\Models\TransferCreditPrecedent as TransferCreditPrecedentModel;
use Illuminate\Database\Eloquent\Builder;
use Src\Shared\Persistence\Infrastructure\Concerns\ResolvesSortOrder;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts\TransferCreditPrecedentRepositoryInterface;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Services\CourseNameNormalizer;

final class EloquentTransferCreditPrecedentRepository implements TransferCreditPrecedentRepositoryInterface
{
    use ResolvesSortOrder;

    /**
     * @var array<int, string>
     */
    private const SORTABLE_COLUMNS = ['institution', 'external_course', 'outcome'];

    public function __construct(
        private readonly CourseNameNormalizer $normalizer,
    ) {}

    public function find(int $id): ?TransferCreditPrecedent
    {
        $model = TransferCreditPrecedentModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    /** ES-02, tarea 4.1: compara por forma normalizada. */
    public function findByInstitutionAndExternalCourse(string $institution, string $externalCourse): ?TransferCreditPrecedent
    {
        $normalizedInstitution = $this->normalizer->normalize($institution);
        $normalizedExternalCourse = $this->normalizer->normalize($externalCourse);

        $model = TransferCreditPrecedentModel::query()
            ->get()
            ->first(function (TransferCreditPrecedentModel $candidate) use ($normalizedInstitution, $normalizedExternalCourse) {
                return $this->normalizer->normalize($candidate->institution) === $normalizedInstitution
                    && $this->normalizer->normalize($candidate->external_course) === $normalizedExternalCourse;
            });

        return $model ? $this->toDomain($model) : null;
    }

    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $column = $this->resolveSortColumn($sortBy, self::SORTABLE_COLUMNS, 'institution');
        $direction = $this->resolveSortDirection($sortDir);

        return $this->searchQuery($search)
            ->orderBy($column, $direction)
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function paginate(?string $search, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $column = $this->resolveSortColumn($sortBy, self::SORTABLE_COLUMNS, 'institution');
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
     * @return Builder<TransferCreditPrecedentModel>
     */
    private function searchQuery(?string $search)
    {
        // Sin ->with('course'): mismo motivo que en EloquentWaiverRuleRepository.
        $query = TransferCreditPrecedentModel::query();

        if (filled($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('institution', 'like', "%{$search}%")
                    ->orWhere('external_course', 'like', "%{$search}%")
                    ->orWhere('resolution_number', 'like', "%{$search}%")
                    ->orWhereHas('course', function ($courseQuery) use ($search) {
                        $courseQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        return $query;
    }

    public function save(TransferCreditPrecedent $precedent): TransferCreditPrecedent
    {
        $model = $precedent->id()
            ? TransferCreditPrecedentModel::query()->findOrFail($precedent->id())
            : new TransferCreditPrecedentModel;

        $model->institution = $precedent->institution();
        $model->external_course = $precedent->externalCourse();
        $model->course_id = $precedent->courseId();
        $model->outcome = $precedent->outcome();
        $model->resolution_number = $precedent->resolutionNumber();
        $model->save();

        return $this->toDomain($model);
    }

    public function delete(int $id): void
    {
        TransferCreditPrecedentModel::query()->whereKey($id)->delete();
    }

    private function toDomain(TransferCreditPrecedentModel $model): TransferCreditPrecedent
    {
        return TransferCreditPrecedent::reconstitute(
            id: $model->id,
            institution: $model->institution,
            externalCourse: $model->external_course,
            courseId: $model->course_id,
            outcome: $model->outcome,
            resolutionNumber: $model->resolution_number,
        );
    }
}
