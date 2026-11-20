<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Application\Support;

use Src\StudentRequests\TransferCreditPrecedent\Domain\Entities\TransferCreditPrecedent;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** Redacta la CONSTANCIA de una resolución. */
final class ResolutionStatement
{
    /** Tope de la columna `comment` en la migración. */
    private const MAX_LENGTH = 255;

    /** ES-01: el motor aprobó porque una regla configurada se cumplió. */
    public function forAutomaticApproval(?WaiverRule $rule): string
    {
        return $this->trim(__('Automatically approved by the rules engine.').' '.$this->describeRule($rule));
    }

    /** ES-01: el motor denegó porque una regla configurada. */
    public function forAutomaticDenial(?WaiverRule $rule): string
    {
        return $this->trim(__('Automatically denied by the rules engine.').' '.$this->describeRule($rule));
    }

    /** ES-01: ninguna regla resolvió el caso. */
    public function forManualReview(?WaiverRule $rule): string
    {
        return $this->trim(__('Sent to Docencia for manual review.').' '.$this->describeRule($rule));
    }

    /** ES-02: la convalidación siempre la resuelve la Comisión. */
    public function forTransferSubmission(?TransferCreditPrecedent $precedent): string
    {
        if ($precedent === null || ! $precedent->isApproved()) {
            return $this->trim(__('Sent to the Technical Commission. No approved precedent on record for this institution and external course.'));
        }

        return $this->trim(__('Sent to the Technical Commission. Approved precedent found: :institution — :course, resolution :number.', [
            'institution' => $precedent->institution(),
            'course' => $precedent->externalCourse(),
            'number' => $precedent->resolutionNumber(),
        ]));
    }

    /** Resume la regla con sus parámetros reales. */
    private function describeRule(?WaiverRule $rule): string
    {
        if ($rule === null) {
            return __('No active rule was configured for this course.');
        }

        // Sin parámetros que describir.
        return __('Rule :order: :type', [
            'order' => $rule->evaluationOrder(),
            'type' => $rule->type(),
        ]);
    }

    private function trim(string $statement): string
    {
        if (mb_strlen($statement) <= self::MAX_LENGTH) {
            return $statement;
        }

        return mb_substr($statement, 0, self::MAX_LENGTH - 1).'…';
    }
}
