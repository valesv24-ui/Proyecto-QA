<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\ValueObjects;

use Src\StudentRequests\StudentRequest\Domain\Entities\StudentRequest;

/** WaiverEvaluationOutcome — Value Object. */
final class WaiverEvaluationOutcome
{
    private function __construct(
        private readonly string $result,
        private readonly ?int $matchedRuleId,
    ) {}

    public static function automatic(int $matchedRuleId): self
    {
        return new self(StudentRequest::RESULT_AUTOMATIC, $matchedRuleId);
    }

    public static function denied(int $failedRuleId): self
    {
        return new self(StudentRequest::RESULT_DENIED, $failedRuleId);
    }

    public static function manualReview(?int $matchedRuleId = null): self
    {
        return new self(StudentRequest::RESULT_MANUAL_REVIEW, $matchedRuleId);
    }

    public function result(): string
    {
        return $this->result;
    }

    public function matchedRuleId(): ?int
    {
        return $this->matchedRuleId;
    }
}
