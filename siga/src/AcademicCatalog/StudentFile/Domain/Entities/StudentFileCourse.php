<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\StudentFile\Domain\Entities;

/** Una línea del expediente: qué curso. */
final class StudentFileCourse
{
    public function __construct(
        private readonly string $code,
        private readonly string $name,
        private readonly ?int $planLevel,
        private readonly ?string $termLabel,
        private readonly string $status,
        private readonly ?float $grade,
        private readonly ?int $credits,
    ) {}

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function planLevel(): ?int
    {
        return $this->planLevel;
    }

    public function termLabel(): ?string
    {
        return $this->termLabel;
    }

    public function status(): string
    {
        return $this->status;
    }

    /** El curso está ganado: ni reprobado ni todavía cursándose. */
    public function isEarned(): bool
    {
        return ! in_array($this->status, ['Reprobado', 'Matriculado'], true);
    }

    /** Se está cursando ahora mismo. */
    public function isInProgress(): bool
    {
        return $this->status === 'Matriculado';
    }

    public function grade(): ?float
    {
        return $this->grade;
    }

    public function credits(): ?int
    {
        return $this->credits;
    }

    /** Una acreditación (equiparación, convalidación, requisito levantado) no lleva nota. */
    public function hasGrade(): bool
    {
        return $this->grade !== null;
    }
}
