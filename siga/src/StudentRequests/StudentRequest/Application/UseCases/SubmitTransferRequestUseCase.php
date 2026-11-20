<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Application\DTOs\SubmitTransferRequestDTO;
use Src\StudentRequests\StudentRequest\Application\Support\ResolutionStatement;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\RequestedCourse;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\RecordStudentRequestStatusHistoryUseCase;
use Src\StudentRequests\TransferCreditPrecedent\Domain\Contracts\TransferCreditPrecedentRepositoryInterface;

/** ES-02: a diferencia de ES-01. */
final class SubmitTransferRequestUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $studentRequestRepository,
        private readonly TransferCreditPrecedentRepositoryInterface $precedentRepository,
        private readonly RecordStudentRequestStatusHistoryUseCase $recordHistory,
        private readonly ResolutionStatement $statement,
        private readonly EnsureEstimatedResolutionDateUseCase $ensureEstimatedResolutionDate,
        private readonly StudentRequestNotifierInterface $notifier,
        private readonly RequestedCourseRepositoryInterface $requestedCourses,
    ) {}

    public function handle(SubmitTransferRequestDTO $dto): StudentRequest
    {
        // La solicitud conserva el PRIMER curso en sus columnas propias.
        $headline = $dto->headline();

        $studentRequest = StudentRequest::forTransfer(
            $dto->studentId,
            $headline['courseId'],
            $headline['sourceInstitution'],
            $headline['externalCourse'],
        );

        $precedent = $this->precedentRepository->findByInstitutionAndExternalCourse(
            $headline['sourceInstitution'],
            $headline['externalCourse'],
        );

        if ($precedent !== null && $precedent->isApproved() && $precedent->id() !== null) {
            $studentRequest->attachPrecedent($precedent->id());
        }

        // ES-03: el plazo se fija al recibir, antes de guardar.
        $this->ensureEstimatedResolutionDate->assignOnReception($studentRequest);

        $saved = $this->studentRequestRepository->save($studentRequest);

        // Los cursos van DESPUÉS de guardar la solicitud porque necesitan.
        $this->requestedCourses->addAll((int) $saved->id(), array_map(
            fn (array $course): RequestedCourse => RequestedCourse::requested(
                courseId: $course['courseId'],
                externalInstitution: $course['sourceInstitution'],
                externalCourseName: $course['externalCourse'],
            ),
            $dto->courses,
        ));

        // Primer asiento del expediente, con user_id NULL (= el sistema).
        $this->recordHistory->handle(
            (int) $saved->id(),
            previousStatus: null,
            newStatus: $saved->status(),
            userId: null,
            comment: $this->statement->forTransferSubmission($precedent),
        );

        // Acuse de recibo.
        $this->notifier->notifySubmitted($saved);

        return $saved;
    }
}
