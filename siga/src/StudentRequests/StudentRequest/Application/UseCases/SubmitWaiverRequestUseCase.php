<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\UseCases;

use InvalidArgumentException;
use Src\Shared\Clock\Domain\Contracts\ClockInterface;
use Src\StudentRequests\StudentRequest\Application\Contracts\StudentRequestNotifierInterface;
use Src\StudentRequests\StudentRequest\Application\DTOs\SubmitWaiverRequestDTO;
use Src\StudentRequests\StudentRequest\Application\DTOs\SubmitWaiverRequestResult;
use Src\StudentRequests\StudentRequest\Application\Support\ResolutionStatement;
use Src\StudentRequests\StudentRequest\Domain\Contracts\StudentRequestRepositoryInterface;
use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;
use Src\StudentRequests\StudentRequest\Domain\Services\WaiverEvaluationEngine;
use Src\StudentRequests\StudentRequestStatusHistory\Application\UseCases\RecordStudentRequestStatusHistoryUseCase;
use Src\StudentRequests\WaiverRule\Domain\Contracts\WaiverRuleRepositoryInterface;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** ES-01 de punta a punta, con la boleta SLR-002. */
final class SubmitWaiverRequestUseCase
{
    public function __construct(
        private readonly StudentRequestRepositoryInterface $studentRequestRepository,
        private readonly WaiverRuleRepositoryInterface $waiverRuleRepository,
        private readonly WaiverEvaluationEngine $engine,
        private readonly RecordStudentRequestStatusHistoryUseCase $recordHistory,
        private readonly ResolutionStatement $statement,
        private readonly StudentRequestNotifierInterface $notifier,
        private readonly EnsureEstimatedResolutionDateUseCase $ensureEstimatedResolutionDate,
        private readonly ClockInterface $clock,
    ) {}

    public function handle(SubmitWaiverRequestDTO $dto): SubmitWaiverRequestResult
    {
        if (! in_array($dto->justification, WaiverRule::STUDENT_JUSTIFICATIONS, true)) {
            // Solo las CINCO de la boleta.
            throw new InvalidArgumentException('La justificación no corresponde a la boleta SLR-002.');
        }

        if (! $dto->acceptsTerms) {
            throw new InvalidArgumentException('Debe aceptar las notas importantes de la boleta para continuar.');
        }

        $duplicate = $this->studentRequestRepository->findApprovedWaiverDuplicate(
            $dto->studentId,
            $dto->courseId,
            $dto->prerequisiteCourseId,
        );

        if ($duplicate !== null) {
            return SubmitWaiverRequestResult::duplicate();
        }

        $rules = $this->waiverRuleRepository->activeForCourseOrdered($dto->courseId);
        $outcome = $this->engine->evaluate($rules, $dto->justification);

        $studentRequest = StudentRequest::forWaiver(
            studentId: $dto->studentId,
            courseId: $dto->courseId,
            prerequisiteCourseId: $dto->prerequisiteCourseId,
            justification: $dto->justification,
            termsAcceptedAt: $this->clock->now(),
        );
        $studentRequest->resolveByEngine(
            $outcome->result(),
            $outcome->result() === StudentRequest::RESULT_DENIED ? $outcome->matchedRuleId() : null,
        );

        // ES-03: el plazo se fija al recibir, antes de guardar.
        $this->ensureEstimatedResolutionDate->assignOnReception($studentRequest);

        $saved = $this->studentRequestRepository->save($studentRequest);

        $comment = $this->recordEngineResolution($saved, $outcome->matchedRuleId(), $rules);

        // Acuse de recibo.
        $this->notifier->notifySubmitted($saved);

        // Y si el motor la denegó en el acto.
        if ($saved->isResolved()) {
            $this->notifier->notifyResolved($saved, $comment);
        }

        return SubmitWaiverRequestResult::created($saved);
    }

    /**
     * Constancia de lo que decidió el motor.
     *
     * @param  array<int, WaiverRule>  $rules
     */
    private function recordEngineResolution(StudentRequest $studentRequest, ?int $matchedRuleId, array $rules): string
    {
        $rule = $this->findRuleById($rules, $matchedRuleId);

        $comment = match ($studentRequest->engineResult()) {
            StudentRequest::RESULT_AUTOMATIC => $this->statement->forAutomaticApproval($rule),
            StudentRequest::RESULT_DENIED => $this->statement->forAutomaticDenial($rule),
            default => $this->statement->forManualReview($rule),
        };

        $this->recordHistory->handle(
            (int) $studentRequest->id(),
            // Sin estado previo: es el primer asiento del expediente.
            previousStatus: null,
            newStatus: $studentRequest->status(),
            // NULL = el sistema, no una persona.
            userId: null,
            comment: $comment,
        );

        return $comment;
    }

    /**
     * @param  array<int, WaiverRule>  $rules
     */
    private function findRuleById(array $rules, ?int $ruleId): ?WaiverRule
    {
        if ($ruleId === null) {
            return null;
        }

        foreach ($rules as $rule) {
            if ($rule->id() === $ruleId) {
                return $rule;
            }
        }

        return null;
    }
}
