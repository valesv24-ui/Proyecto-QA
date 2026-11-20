<?php

declare(strict_types=1);

namespace Src\StudentRequests\StudentRequest\Domain\Entities;

use InvalidArgumentException;

/** Un curso dentro de una solicitud de convalidación. */
final class RequestedCourse
{
    public const OUTCOME_PENDING = 'Pendiente';

    public const OUTCOME_RECOGNIZED = 'Reconocido';

    public const OUTCOME_REJECTED = 'No reconocido';

    /**
     * @var array<int, string>
     */
    public const OUTCOMES = [
        self::OUTCOME_PENDING,
        self::OUTCOME_RECOGNIZED,
        self::OUTCOME_REJECTED,
    ];

    private function __construct(
        private readonly ?int $id,
        private readonly int $courseId,
        private readonly string $externalInstitution,
        private readonly string $externalCourseName,
        private ?string $externalCourseCode,
        private ?int $externalCredits,
        private ?float $externalGrade,
        private string $outcome,
        private ?string $denialReason,
    ) {
        if (! in_array($outcome, self::OUTCOMES, true)) {
            throw new InvalidArgumentException("Resultado de curso inválido: {$outcome}");
        }
        if (trim($externalInstitution) === '' || trim($externalCourseName) === '') {
            throw new InvalidArgumentException('Un curso a convalidar exige institución y nombre del curso externo.');
        }
    }

    public static function requested(
        int $courseId,
        string $externalInstitution,
        string $externalCourseName,
    ): self {
        return new self(
            id: null,
            courseId: $courseId,
            externalInstitution: trim($externalInstitution),
            externalCourseName: trim($externalCourseName),
            externalCourseCode: null,
            externalCredits: null,
            externalGrade: null,
            outcome: self::OUTCOME_PENDING,
            denialReason: null,
        );
    }

    public static function reconstitute(
        int $id,
        int $courseId,
        string $externalInstitution,
        string $externalCourseName,
        ?string $externalCourseCode,
        ?int $externalCredits,
        ?float $externalGrade,
        string $outcome,
        ?string $denialReason,
    ): self {
        return new self(
            $id, $courseId, $externalInstitution, $externalCourseName,
            $externalCourseCode, $externalCredits, $externalGrade, $outcome, $denialReason,
        );
    }

    /** Registro transcribe lo que dicen los documentos del estudiante. */
    public function completeExternalData(?string $code, ?int $credits, ?float $grade = null): void
    {
        if ($credits !== null && ($credits < 0 || $credits > 30)) {
            // No es una regla académica sino un filtro de transcripción.
            throw new InvalidArgumentException('Los créditos del curso externo deben estar entre 0 y 30.');
        }

        // Mismo rango que acepta la columna del expediente (chk_academic_records_grade) porque.
        if ($grade !== null && ($grade < 0 || $grade > 100)) {
            throw new InvalidArgumentException('La nota del curso externo debe estar entre 0 y 100.');
        }

        $this->externalCourseCode = $code === null || trim($code) === '' ? null : trim($code);
        $this->externalCredits = $credits;
        $this->externalGrade = $grade;
    }

    /** La casilla Sí/No del RSREC-001. */
    public function resolve(string $outcome, ?string $denialReason = null): void
    {
        if (! in_array($outcome, [self::OUTCOME_RECOGNIZED, self::OUTCOME_REJECTED], true)) {
            throw new InvalidArgumentException("Resultado de curso inválido: {$outcome}");
        }

        if ($outcome === self::OUTCOME_REJECTED && ($denialReason === null || trim($denialReason) === '')) {
            throw new InvalidArgumentException('Un curso no reconocido exige indicar la razón.');
        }

        $this->outcome = $outcome;
        $this->denialReason = $outcome === self::OUTCOME_REJECTED ? trim((string) $denialReason) : null;
    }

    public function isResolved(): bool
    {
        return $this->outcome !== self::OUTCOME_PENDING;
    }

    public function isRecognized(): bool
    {
        return $this->outcome === self::OUTCOME_RECOGNIZED;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function externalInstitution(): string
    {
        return $this->externalInstitution;
    }

    public function externalCourseName(): string
    {
        return $this->externalCourseName;
    }

    public function externalCourseCode(): ?string
    {
        return $this->externalCourseCode;
    }

    public function externalCredits(): ?int
    {
        return $this->externalCredits;
    }

    public function externalGrade(): ?float
    {
        return $this->externalGrade;
    }

    public function outcome(): string
    {
        return $this->outcome;
    }

    public function denialReason(): ?string
    {
        return $this->denialReason;
    }
}
