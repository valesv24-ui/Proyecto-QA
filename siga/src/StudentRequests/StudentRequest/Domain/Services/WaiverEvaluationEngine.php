<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Services;

use Src\StudentRequests\StudentRequest\Domain\ValueObjects\WaiverEvaluationOutcome;
use Src\StudentRequests\WaiverRule\Domain\Entities\WaiverRule;

/** WaiverEvaluationEngine — Domain Service. */
final class WaiverEvaluationEngine
{
    /**
     * @param  array<int, WaiverRule>  $rules
     */
    public function evaluate(array $rules, string $justification): WaiverEvaluationOutcome
    {
        if ($rules === []) {
            // ES-01, criterio de aceptación original.
            return WaiverEvaluationOutcome::manualReview();
        }

        foreach ($rules as $rule) {
            if ($rule->admits($justification)) {
                return WaiverEvaluationOutcome::manualReview($rule->id());
            }
        }

        // Se devuelve la PRIMERA regla configurada como la que fundamenta.
        return WaiverEvaluationOutcome::denied((int) $rules[0]->id());
    }
}
