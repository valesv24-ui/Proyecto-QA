<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequestStatusHistory\Infrastructure\Persistence\Repositories;

use App\Models\StudentRequestStatusHistory as StudentRequestStatusHistoryModel;
use DateTimeImmutable;
use Illuminate\Support\Carbon;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Contracts\StudentRequestStatusHistoryRepositoryInterface;
use Src\StudentRequests\StudentRequestStatusHistory\Domain\Entities\StudentRequestStatusHistory;

final class EloquentStudentRequestStatusHistoryRepository implements StudentRequestStatusHistoryRepositoryInterface
{
    public function find(int $id): ?StudentRequestStatusHistory
    {
        $model = StudentRequestStatusHistoryModel::query()->find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function forStudentRequest(int $studentRequestId): array
    {
        return StudentRequestStatusHistoryModel::query()
            ->where('student_request_id', $studentRequestId)
            ->orderBy('created_at')
            ->get()
            ->map($this->toDomain(...))
            ->all();
    }

    public function save(StudentRequestStatusHistory $entry): StudentRequestStatusHistory
    {
        $model = $entry->id()
            ? StudentRequestStatusHistoryModel::query()->findOrFail($entry->id())
            : new StudentRequestStatusHistoryModel;

        $model->student_request_id = $entry->studentRequestId();
        $model->previous_status = $entry->previousStatus();
        $model->new_status = $entry->newStatus();
        $model->comment = $entry->comment();
        $model->user_id = $entry->userId();
        // Same DateTimeImmutable (Domain) -> Carbon (Eloquent cast) bridge as EloquentAcademicTermRepository.
        $model->notified_at = $entry->notifiedAt() !== null
            ? Carbon::instance($entry->notifiedAt())
            : null;
        $model->save();

        // El modelo tiene $timestamps = false y la columna created_at.
        $model->refresh();

        return $this->toDomain($model);
    }

    private function toDomain(StudentRequestStatusHistoryModel $model): StudentRequestStatusHistory
    {
        return StudentRequestStatusHistory::reconstitute(
            id: $model->id,
            studentRequestId: $model->student_request_id,
            previousStatus: $model->previous_status,
            newStatus: $model->new_status,
            comment: $model->comment,
            userId: $model->user_id,
            notifiedAt: $model->notified_at ? new DateTimeImmutable($model->notified_at->toDateTimeString()) : null,
            // created_at en la tabla, occurredAt en el Dominio.
            occurredAt: new DateTimeImmutable($model->created_at->toDateTimeString()),
        );
    }
}
