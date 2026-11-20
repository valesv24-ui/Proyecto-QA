<?php

declare(strict_types=1);

namespace Src\AcademicCatalog\AcademicRecord\Domain\Entities;

use InvalidArgumentException;

/** AcademicRecord — Aggregate Root. */
final class AcademicRecord
{
    public const STATUS_ENROLLED = 'Matriculado';

    private const STATUSES = [
        'Aprobado', 'Reprobado', 'Acreditado por equiparación',
        'Acreditado por convalidación', 'Requisito levantado',
        self::STATUS_ENROLLED,
    ];

    /**
     * Estados en que el curso ya está GANADO, por la vía que sea.
     *
     * @var array<int, string>
     */
    private const EARNED = [
        'Aprobado', 'Acreditado por equiparación',
        'Acreditado por convalidación', 'Requisito levantado',
    ];

    private function __construct(
        private readonly ?int $id,
        private readonly int $studentId,
        private readonly int $courseId,
        private readonly ?int $academicTermId,
        private string $status,
        private ?float $grade,
        private ?int $courseEquivalenceId,
    ) {
        if (! in_array($status, self::STATUSES, true)) {
            throw new InvalidArgumentException("Estado de expediente inválido: {$status}");
        }
        if ($grade !== null && ($grade < 0 || $grade > 100)) {
            throw new InvalidArgumentException('La nota debe estar entre 0 y 100.');
        }
    }

    public static function create(int $studentId, int $courseId, ?int $academicTermId, string $status, ?float $grade = null): self
    {
        return new self(
            id: null,
            studentId: $studentId,
            courseId: $courseId,
            academicTermId: $academicTermId,
            status: $status,
            grade: $grade,
            courseEquivalenceId: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $studentId,
        int $courseId,
        ?int $academicTermId,
        string $status,
        ?float $grade,
        ?int $courseEquivalenceId,
    ): self {
        return new self($id, $studentId, $courseId, $academicTermId, $status, $grade, $courseEquivalenceId);
    }

    /** Regla de negocio central de ES-01 tipo (a). */
    public function satisfiesMinimumGrade(float $minimumGrade): bool
    {
        $grade = $this->passingGrade();

        return $grade !== null && $grade >= $minimumGrade;
    }

    /** La nota registrada. */
    public function passingGrade(): ?float
    {
        $countsAsPassed = in_array($this->status, [
            'Aprobado', 'Acreditado por equiparación', 'Acreditado por convalidación',
        ], true);

        return $countsAsPassed ? $this->grade : null;
    }

    /** ¿El curso está ganado? Matriculado todavía no lo está. */
    public function isPassed(): bool
    {
        return in_array($this->status, self::EARNED, true);
    }

    /** El curso se está cursando ahora mismo. */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_ENROLLED;
    }

    /**
     * ¿Impide pedir un levantamiento o una convalidación de este curso?
     *
     * Ganado o en curso: en ambos casos el trámite no tendría objeto, y
     * ofrecerlo en el selector solo invita a una solicitud que Registro va a
     * rechazar.
     */
    public function blocksNewRequest(): bool
    {
        return $this->isPassed() || $this->isInProgress();
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function studentId(): int
    {
        return $this->studentId;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function academicTermId(): ?int
    {
        return $this->academicTermId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function grade(): ?float
    {
        return $this->grade;
    }

    public function courseEquivalenceId(): ?int
    {
        return $this->courseEquivalenceId;
    }
}
