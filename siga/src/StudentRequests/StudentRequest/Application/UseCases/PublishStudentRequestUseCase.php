<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Application\Contracts\TransferResolutionDocumentInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\RequestedCourseRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\TransferResolutionTally;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\RecordStudentRequestStatusHistoryUseCase;

/** REGISTRO publica lo que Docencia resolvió: emite el documento. */
final class PublishStudentRequestUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $repository,
        private readonly RequestedCourseRepositoryInterface $requestedCourses,
        private readonly TransferResolutionTally $tally,
        private readonly TransferResolutionDocumentInterface $document,
        private readonly CreditRecognizedCoursesUseCase $creditRecognizedCourses,
        private readonly RecordStudentRequestStatusHistoryUseCase $recordHistory,
        private readonly StudentRequestNotifierInterface $notifier,
    ) {}

    public function handle(
        int $id,
        int $registrarId,
        ?string $resolutionNumber = null,
        ?string $sessionNumber = null,
        ?string $minutesNumber = null,
        ?DateTimeImmutable $sessionDate = null,
    ): StudentRequest {
        $studentRequest = $this->repository->find($id);

        if ($studentRequest === null) {
            throw new InvalidArgumentException("No existe la solicitud #{$id}.");
        }

        if (! $studentRequest->awaitsPublication()) {
            throw new InvalidArgumentException(
                'Solo se publica una solicitud que Docencia ya resolvió.'
            );
        }

        $previousStatus = $studentRequest->status();
        $isTransfer = $studentRequest->type() === StudentRequest::TYPE_TRANSFER;

        if ($isTransfer) {
            $this->recordResolutionDetails($id, $resolutionNumber, $sessionNumber, $minutesNumber, $sessionDate);

            // El documento primero: si falla.
            $this->document->issueFor($id, $registrarId);
        }

        $studentRequest->publish($registrarId);

        $saved = $this->repository->save($studentRequest);

        // El expediente se toca DESPUÉS de que la solicitud quedó.
        if ($isTransfer && $saved->status() === StudentRequest::STATUS_APPROVED) {
            $this->creditRecognizedCourses->handle($id, $saved->studentId());
        }

        $statement = $isTransfer ? $this->summaryFor($id) : null;

        $this->recordHistory->handle($id, $previousStatus, $saved->status(), $registrarId, $statement);

        // El aviso al estudiante sale acá y en ningún otro.
        $this->notifier->notifyResolved($saved, $statement);

        return $saved;
    }

    /** El RSREC-001 imprime número de resolución, sesión y acta. */
    private function recordResolutionDetails(
        int $id,
        ?string $resolutionNumber,
        ?string $sessionNumber,
        ?string $minutesNumber,
        ?DateTimeImmutable $sessionDate,
    ): void {
        foreach ([$resolutionNumber, $sessionNumber, $minutesNumber] as $field) {
            if ($field === null || trim($field) === '') {
                throw new InvalidArgumentException(
                    'La resolución exige número de resolución, número de sesión y número de acta.'
                );
            }
        }

        $this->requestedCourses->recordResolutionDetails(
            $id,
            trim((string) $resolutionNumber),
            trim((string) $sessionNumber),
            trim((string) $minutesNumber),
            $sessionDate ?? new DateTimeImmutable,
        );
    }

    /** Lo que queda en el historial y viaja al correo. */
    private function summaryFor(int $id): string
    {
        $courses = $this->requestedCourses->forRequest($id);

        return __('Resolution :number of :total courses recognized.', [
            'number' => $this->tally->recognizedCount($courses),
            'total' => count($courses),
        ]);
    }
}
