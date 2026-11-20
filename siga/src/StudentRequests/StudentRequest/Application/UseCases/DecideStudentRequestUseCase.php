<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use InvalidArgumentException;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\TransferResolutionTally;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\RecordStudentRequestStatusHistoryUseCase;

/** DOCENCIA resuelve y devuelve la solicitud a Registro. */
final class DecideStudentRequestUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
        private readonly RequestedCourseRepositoryInterface $requestedCourses,
        private readonly TransferResolutionTally $tally,
        private readonly RecordStudentRequestStatusHistoryUseCase $recordHistory,
    ) {}

    /**
     * @param  string|null  $decision  Null en una convalidación.
     */
    public function handle(
        int $id,
        int $reviewerId,
        ?string $decision = null,
        ?string $comment = null,
    ): StudentRequest {
        $studentRequest = $this->repository->find($id);

        if ($studentRequest === null) {
            throw new InvalidArgumentException("No existe la solicitud #{$id}.");
        }

        $previousStatus = $studentRequest->status();
        $decision = $this->resolveDecision($studentRequest, $decision);

        $studentRequest->decideByTeaching($decision, $reviewerId);

        $saved = $this->repository->save($studentRequest);

        $this->recordHistory->handle(
            $id,
            $previousStatus,
            $saved->status(),
            $reviewerId,
            $comment,
        );

        return $saved;
    }

    /** En una convalidación la decisión NO se elige: se cuenta. */
    private function resolveDecision(StudentRequest $studentRequest, ?string $decision): string
    {
        if ($studentRequest->type() !== StudentRequest::TYPE_TRANSFER) {
            if ($decision === null) {
                throw new InvalidArgumentException('Un levantamiento exige indicar si se aprueba o se deniega.');
            }

            return $decision;
        }

        $fromCourses = $this->tally->statusFor(
            $this->requestedCourses->forRequest((int) $studentRequest->id())
        );

        if ($fromCourses === null) {
            throw new InvalidArgumentException(
                'Todos los cursos deben tener resolución antes de devolver la solicitud a Registro.'
            );
        }

        return $fromCourses;
    }
}
