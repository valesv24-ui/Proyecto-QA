<?php

declare(strict_types=1);

namespace Src\StudentRequests\TransferCreditPrecedent\Domain\Entities;

use InvalidArgumentException;

/** TransferCreditPrecedent — Aggregate Root. */
final class TransferCreditPrecedent
{
    private const OUTCOMES = ['Aprobada', 'Denegada'];

    private function __construct(
        private readonly ?int $id,
        private string $institution,
        private string $externalCourse,
        private readonly int $courseId,
        private string $outcome,
        private string $resolutionNumber,
    ) {
        if (! in_array($outcome, self::OUTCOMES, true)) {
            throw new InvalidArgumentException("Resultado inválido: {$outcome}");
        }
    }

    public static function create(string $institution, string $externalCourse, int $courseId, string $outcome, string $resolutionNumber): self
    {
        return new self(id: null, institution: $institution, externalCourse: $externalCourse, courseId: $courseId, outcome: $outcome, resolutionNumber: $resolutionNumber);
    }

    public static function reconstitute(int $id, string $institution, string $externalCourse, int $courseId, string $outcome, string $resolutionNumber): self
    {
        return new self($id, $institution, $externalCourse, $courseId, $outcome, $resolutionNumber);
    }

    public function isApproved(): bool
    {
        return $this->outcome === 'Aprobada';
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function institution(): string
    {
        return $this->institution;
    }

    public function externalCourse(): string
    {
        return $this->externalCourse;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function outcome(): string
    {
        return $this->outcome;
    }

    public function resolutionNumber(): string
    {
        return $this->resolutionNumber;
    }
}
