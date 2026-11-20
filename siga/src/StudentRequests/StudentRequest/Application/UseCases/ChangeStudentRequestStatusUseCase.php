<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\RecordStudentRequestStatusHistoryUseCase;

/** ES-03: acción del revisor (Docencia/Comisión) al cambiar el estado. */
final class ChangeStudentRequestStatusUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
        private readonly EnsureEstimatedResolutionDateUseCase $ensureEstimatedResolutionDate,
        private readonly RecordStudentRequestStatusHistoryUseCase $recordHistory,
        private readonly StudentRequestNotifierInterface $notifier,
    ) {}

    public function handle(int $id, string $newStatus, int $reviewerId, ?string $comment = null, bool $bypassWorkflow = false): StudentRequest
    {
        // Idempotente si ya tiene fecha asignada.
        $studentRequest = $this->ensureEstimatedResolutionDate->handle($id);

        $previousStatus = $studentRequest->status();

        // El salto libre solo llega hasta acá cuando quien llama.
        $studentRequest->changeStatus($newStatus, $reviewerId, $bypassWorkflow);

        $saved = $this->repository->save($studentRequest);

        $this->recordHistory->handle($id, $previousStatus, $newStatus, $reviewerId, $comment);

        // Verificada por Registro → En revisión es automático.
        if ($newStatus === StudentRequest::STATUS_VERIFIED && ! $bypassWorkflow) {
            $saved->changeStatus(StudentRequest::STATUS_IN_REVIEW, $reviewerId);
            $saved = $this->repository->save($saved);
            $this->recordHistory->handle($id, $newStatus, StudentRequest::STATUS_IN_REVIEW, null);
        }

        // SOLO se notifica la resolución final.
        if ($saved->isResolved()) {
            // El mismo comentario que quedó en el historial viaja.
            $this->notifier->notifyResolved($saved, $comment);
        }

        return $saved;
    }
}
